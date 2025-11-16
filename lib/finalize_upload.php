<?php
/**
 * KidSnaps Growth Album - チャンク分割アップロードの最終処理
 * チャンクで受信したファイルをデータベースに登録
 */

// 実行時間を延長
set_time_limit(600); // 10分
ini_set('max_execution_time', '600');
ini_set('memory_limit', '512M');

// エラー表示設定（環境変数 DEBUG_MODE=1 で有効化）
if (getenv('DEBUG_MODE') === '1') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
}

// ログファイル設定
$logFile = __DIR__ . '/../uploads/temp/upload_debug.log';
if (!file_exists(dirname($logFile))) {
    @mkdir(dirname($logFile), 0755, true);
}
ini_set('error_log', $logFile);

// カスタムログ関数
function logDebug($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] FINALIZE: {$message}\n";
    @file_put_contents($logFile, $logMessage, FILE_APPEND);
    error_log($message);
}

logDebug('=== finalize_upload.php開始 ===');

try {
    logDebug('config/database.php 読み込み中...');
    require_once __DIR__ . '/../config/database.php';
    logDebug('config/database.php 読み込み完了');

    logDebug('includes/heic_converter.php 読み込み中...');
    require_once __DIR__ . '/../includes/heic_converter.php';
    logDebug('includes/heic_converter.php 読み込み完了');

    logDebug('includes/image_thumbnail_helper.php 読み込み中...');
    require_once __DIR__ . '/../includes/image_thumbnail_helper.php';
    logDebug('includes/image_thumbnail_helper.php 読み込み完了');

    logDebug('includes/video_metadata_helper.php 読み込み中...');
    require_once __DIR__ . '/../includes/video_metadata_helper.php';
    logDebug('includes/video_metadata_helper.php 読み込み完了');
} catch (Exception $e) {
    logDebug('ファイル読み込みエラー: ' . $e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'System error: ' . $e->getMessage()]);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

session_start();

/**
 * 一時ディレクトリを再帰的に削除する関数
 */
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }

    if (!is_dir($dir)) {
        return unlink($dir);
    }

    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }

        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }

    return rmdir($dir);
}

// getLocationName()とapplyRateLimitForGeocoding()は
// includes/exif_helper.phpで定義されているので、ここでは削除
// （関数の重複定義を防ぐため）

try {
    logDebug('=== 最終処理開始 ===');

    $fileIdentifier = isset($_POST['fileIdentifier']) ? $_POST['fileIdentifier'] : '';
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $thumbnailData = isset($_POST['thumbnailData']) ? $_POST['thumbnailData'] : null;

    logDebug('ファイル識別子: ' . $fileIdentifier);

    if (empty($fileIdentifier)) {
        logDebug('エラー: ファイル識別子が空');
        throw new Exception('ファイル識別子が指定されていません。');
    }

    // セッションからファイル情報を取得
    logDebug('セッション情報を確認中...');
    if (!isset($_SESSION['chunked_files'][$fileIdentifier])) {
        logDebug('エラー: セッションにファイル情報がありません');
        logDebug('セッション内容: ' . print_r($_SESSION, true));
        throw new Exception('アップロードされたファイルが見つかりません。');
    }
    logDebug('セッション情報取得成功');

    $fileInfo = $_SESSION['chunked_files'][$fileIdentifier];
    $filePath = $fileInfo['path'];
    $fileName = $fileInfo['name'];
    $fileSize = $fileInfo['size'];
    $mimeType = $fileInfo['mime_type'];
    $tempDir = $fileInfo['temp_dir'];

    logDebug('ファイル名: ' . $fileName);
    logDebug('ファイルサイズ: ' . $fileSize . ' bytes');
    logDebug('MIMEタイプ: ' . $mimeType);

    // ファイルサイズ制限チェック（500MB）
    $maxFileSize = 500 * 1024 * 1024;
    if ($fileSize > $maxFileSize) {
        throw new Exception('ファイルサイズは500MB以下にしてください。');
    }

    // MIME型チェック
    $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/heic', 'image/heif', 'application/octet-stream'];
    $allowedVideoTypes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/mpeg'];
    $allowedTypes = array_merge($allowedImageTypes, $allowedVideoTypes);

    // HEIC特別処理
    $isHeic = false;
    if (in_array($mimeType, ['application/octet-stream', 'image/heic', 'image/heif']) &&
        preg_match('/\.(heic|heif)$/i', $fileName)) {
        $isHeic = true;
    }

    if (!in_array($mimeType, $allowedTypes) && !$isHeic) {
        throw new Exception('サポートされていないファイル形式です。');
    }

    // ファイルタイプの判定
    $isVideo = in_array($mimeType, $allowedVideoTypes);
    $fileType = $isVideo ? 'video' : 'image';

    // 保存先ディレクトリ
    $uploadDir = __DIR__ . '/../uploads/' . ($isVideo ? 'videos' : 'images');
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // .htaccess作成（セキュリティ対策）
    $htaccessPath = $uploadDir . '/.htaccess';
    if (!file_exists($htaccessPath)) {
        file_put_contents($htaccessPath, "php_flag engine off\n");
    }

    // 安全なファイル名生成
    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
    $safeFileName = date('YmdHis') . '_' . uniqid() . '.' . $fileExtension;
    $finalPath = $uploadDir . '/' . $safeFileName;

    // データベース用の相対パス
    $relativePath = 'uploads/' . ($isVideo ? 'videos' : 'images') . '/' . $safeFileName;

    // ファイルを移動
    if (!rename($filePath, $finalPath)) {
        throw new Exception('ファイルの保存に失敗しました。');
    }

    // ファイルハッシュを計算（重複チェック用）
    $fileHash = md5_file($finalPath);

    // 以下、upload.phpと同様の処理
    $thumbnailPath = null;
    $thumbnailWebPPath = null;
    $rotation = 0;

    // 画像の場合の処理
    if (!$isVideo) {
        // HEICの場合は変換
        if ($isHeic) {
            $jpegPath = preg_replace('/\.(heic|heif)$/i', '.jpg', $finalPath);
            $conversionSuccess = convertHeicToJpeg($finalPath, $jpegPath);
            if ($conversionSuccess) {
                unlink($finalPath);
                $finalPath = $jpegPath;
                $safeFileName = basename($jpegPath);
                $fileExtension = 'jpg';
                $mimeType = 'image/jpeg';
                // 相対パスも更新
                $relativePath = 'uploads/images/' . $safeFileName;
            }
        }

        // サムネイル生成
        $thumbnailDir = __DIR__ . '/../uploads/thumbnails';
        if (!file_exists($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
        }

        $thumbnailFileName = 'thumb_' . pathinfo($safeFileName, PATHINFO_FILENAME) . '.jpg';
        $thumbnailFullPath = $thumbnailDir . '/' . $thumbnailFileName;

        try {
            if (generateImageThumbnail($finalPath, $thumbnailFullPath, 400, 85)) {
                // データベース用の相対パス
                $thumbnailPath = 'uploads/thumbnails/' . $thumbnailFileName;

                // WebP版サムネイルも生成
                $thumbnailWebPFileName = 'thumb_' . pathinfo($safeFileName, PATHINFO_FILENAME) . '.webp';
                $thumbnailWebPFullPath = $thumbnailDir . '/' . $thumbnailWebPFileName;

                if (generateWebPThumbnail($finalPath, $thumbnailWebPFullPath, 400, 85)) {
                    $thumbnailWebPPath = 'uploads/thumbnails/' . $thumbnailWebPFileName;
                    error_log('WebPサムネイルを生成しました: ' . $thumbnailWebPPath);
                } else {
                    error_log('WebPサムネイルの生成に失敗しました: ' . $finalPath);
                }
            }
        } catch (Exception $e) {
            error_log('サムネイル生成失敗: ' . $e->getMessage());
        }
    } else {
        // 動画のサムネイル処理
        if ($thumbnailData) {
            $thumbnailDir = __DIR__ . '/../uploads/thumbnails';
            if (!file_exists($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }

            $thumbnailFileName = 'thumb_' . pathinfo($safeFileName, PATHINFO_FILENAME) . '.jpg';
            $thumbnailFullPath = $thumbnailDir . '/' . $thumbnailFileName;

            // Base64デコード
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $thumbnailData));
            if ($imageData && file_put_contents($thumbnailFullPath, $imageData)) {
                // データベース用の相対パス
                $thumbnailPath = 'uploads/thumbnails/' . $thumbnailFileName;
                error_log('動画サムネイルを保存しました: ' . $thumbnailPath);

                // WebP版サムネイルも生成
                $thumbnailWebPFileName = 'thumb_' . pathinfo($safeFileName, PATHINFO_FILENAME) . '.webp';
                $thumbnailWebPFullPath = $thumbnailDir . '/' . $thumbnailWebPFileName;

                if (generateWebPThumbnail($thumbnailFullPath, $thumbnailWebPFullPath, 400, 85)) {
                    $thumbnailWebPPath = 'uploads/thumbnails/' . $thumbnailWebPFileName;
                    error_log('動画用WebPサムネイルを生成しました: ' . $thumbnailWebPPath);
                } else {
                    error_log('動画用WebPサムネイルの生成に失敗しました: ' . $thumbnailFullPath);
                }
            }
        }
    }

    // メタデータ抽出
    $datetime = null;
    $latitude = null;
    $longitude = null;
    $locationName = null;
    $cameraMake = null;
    $cameraModel = null;
    $orientation = null;
    $software = null;
    $focalLength = null;
    $locationAccuracy = null;

    try {
        if (!$isVideo) {
            // 画像のEXIF情報（JavaScriptから送信されたデータを使用）
            if (isset($_POST['exifData']) && !empty($_POST['exifData'])) {
                $exifDataJson = $_POST['exifData'];
                $exifData = json_decode($exifDataJson, true);

                if ($exifData) {
                    $datetime = $exifData['datetime'] ?? null;
                    $latitude = $exifData['latitude'] ?? null;
                    $longitude = $exifData['longitude'] ?? null;
                    $locationName = null; // 位置情報名は後でリバースジオコーディングで取得
                    $cameraMake = $exifData['camera_make'] ?? null;
                    $cameraModel = $exifData['camera_model'] ?? null;
                    $orientation = $exifData['orientation'] ?? 1;
                    $rotation = 0; // JavaScriptのOrientationを使用するため、rotationは0

                    // GPS情報がある場合、リバースジオコーディングで地名を取得
                    if ($latitude && $longitude) {
                        applyRateLimitForGeocoding(); // レート制限適用
                        $locationName = getLocationName($latitude, $longitude);
                    }
                }
            }
        } else {
            // 動画のメタデータ
            $videoMeta = getVideoMetadata($finalPath);

            if ($videoMeta) {
                $datetime = $videoMeta['datetime'] ?? null;
                $latitude = $videoMeta['latitude'] ?? null;
                $longitude = $videoMeta['longitude'] ?? null;
                $cameraMake = $videoMeta['camera_make'] ?? null;
                $cameraModel = $videoMeta['camera_model'] ?? null;
                $software = $videoMeta['software'] ?? null;
                $focalLength = $videoMeta['focal_length'] ?? null;
                $locationAccuracy = $videoMeta['location_accuracy'] ?? null;

                // GPS情報がある場合、リバースジオコーディングで地名を取得
                if ($latitude && $longitude && !$locationName) {
                    applyRateLimitForGeocoding(); // レート制限適用
                    $locationName = getLocationName($latitude, $longitude);
                }
            }
        }
    } catch (Exception $e) {
        error_log('メタデータ抽出エラー: ' . $e->getMessage());
    }

    // データベースに登録
    logDebug('データベース接続中...');
    $pdo = getDbConnection();
    logDebug('データベース接続成功');

    logDebug('データベースに登録中...');

    $sql = "INSERT INTO media_files (
        filename, stored_filename, file_path, file_type, mime_type, file_size, file_hash,
        thumbnail_path, thumbnail_webp_path, rotation, title, description, upload_date,
        exif_datetime, exif_latitude, exif_longitude, exif_location_name, exif_camera_make, exif_camera_model, exif_orientation,
        exif_software, exif_focal_length, exif_location_accuracy
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $fileName,
        $safeFileName,
        $relativePath,  // 相対パスを使用
        $fileType,
        $mimeType,
        $fileSize,
        $fileHash,
        $thumbnailPath,
        $thumbnailWebPPath,
        $rotation,
        $title ?: null,
        $description ?: null,
        $datetime,
        $latitude,
        $longitude,
        $locationName,
        $cameraMake,
        $cameraModel,
        $orientation,
        $software,
        $focalLength,
        $locationAccuracy
    ]);

    $insertedId = $pdo->lastInsertId();

    logDebug('データベース登録成功 - ID: ' . $insertedId);

    // 一時ディレクトリとセッション情報を削除（改善版）
    logDebug('一時ファイル削除中...');
    if (file_exists($tempDir)) {
        deleteDirectory($tempDir);
    }
    unset($_SESSION['chunked_files'][$fileIdentifier]);
    logDebug('一時ファイル削除完了');

    logDebug('=== 最終処理完了 ===');

    echo json_encode([
        'success' => true,
        'message' => 'ファイルのアップロードが完了しました。',
        'fileId' => $insertedId
    ]);

} catch (Exception $e) {
    // エラー時のクリーンアップ: 一時ディレクトリとファイルを削除
    if (isset($tempDir) && file_exists($tempDir)) {
        deleteDirectory($tempDir);
    }
    if (isset($finalPath) && file_exists($finalPath)) {
        @unlink($finalPath);
    }
    if (isset($fileIdentifier) && isset($_SESSION['chunked_files'][$fileIdentifier])) {
        unset($_SESSION['chunked_files'][$fileIdentifier]);
    }

    // 詳細なエラーログ
    error_log('===== 最終処理エラー（finalize_upload.php） =====');
    error_log('エラーメッセージ: ' . $e->getMessage());
    error_log('ファイル: ' . $e->getFile() . ':' . $e->getLine());
    error_log('スタックトレース: ' . $e->getTraceAsString());
    error_log('ファイル識別子: ' . ($fileIdentifier ?? 'N/A'));
    error_log('ファイル名: ' . ($fileName ?? 'N/A'));
    error_log('ファイルパス: ' . ($finalPath ?? 'N/A'));
    error_log('=====================================');

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ]);
}
?>
