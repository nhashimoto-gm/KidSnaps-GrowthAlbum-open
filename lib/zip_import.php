<?php
/**
 * KidSnaps Growth Album - ZIPファイルインポート処理
 * ZIPアーカイブからメディアファイルを抽出してアルバムを作成
 */

// 実行時間を延長
set_time_limit(600); // 10分
ini_set('max_execution_time', '600');
ini_set('memory_limit', '1024M'); // 1GB

// エラー表示設定
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
$logFile = __DIR__ . '/../uploads/temp/zip_import.log';
if (!file_exists(dirname($logFile))) {
    @mkdir(dirname($logFile), 0755, true);
}
ini_set('error_log', $logFile);

function logDebug($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] ZIP_IMPORT: {$message}\n";
    @file_put_contents($logFile, $logMessage, FILE_APPEND);
    error_log($message);
}

// Fatal Error時もJSONレスポンスを返すように設定
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // ヘッダーがまだ送信されていない場合のみ設定
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(500);
        }
        // JSONレスポンスを出力
        echo json_encode([
            'success' => false,
            'error' => 'サーバーエラーが発生しました: ' . $error['message'],
            'debug' => [
                'file' => basename($error['file']),
                'line' => $error['line']
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
});

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

session_start();

try {
    logDebug('=== ZIPインポート開始 ===');

    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/album_processor.php';
    require_once __DIR__ . '/../includes/heic_converter.php';
    require_once __DIR__ . '/../includes/image_thumbnail_helper.php';
    require_once __DIR__ . '/../includes/video_metadata_helper.php';
    require_once __DIR__ . '/../includes/exif_helper.php';
    require_once __DIR__ . '/../includes/google_photos_metadata_helper.php';

    $fileIdentifier = isset($_POST['fileIdentifier']) ? $_POST['fileIdentifier'] : '';
    $albumTitle = isset($_POST['albumTitle']) ? trim($_POST['albumTitle']) : '';
    $albumDescription = isset($_POST['albumDescription']) ? trim($_POST['albumDescription']) : '';

    // Google Photos peopleフィルタ（カンマ区切りの人物名）
    $peopleFilter = isset($_POST['peopleFilter']) ? trim($_POST['peopleFilter']) : '';
    $targetPeople = null;
    if (!empty($peopleFilter)) {
        $targetPeople = array_map('trim', explode(',', $peopleFilter));
    }

    logDebug('ファイル識別子: ' . $fileIdentifier);
    logDebug('アルバムタイトル: ' . $albumTitle);
    logDebug('Peopleフィルタ: ' . ($targetPeople ? implode(', ', $targetPeople) : 'なし'));

    if (empty($fileIdentifier)) {
        throw new Exception('ファイル識別子が指定されていません。');
    }

    // セッションからファイル情報を取得
    if (!isset($_SESSION['chunked_files'][$fileIdentifier])) {
        throw new Exception('アップロードされたファイルが見つかりません。');
    }

    $fileInfo = $_SESSION['chunked_files'][$fileIdentifier];
    $zipPath = $fileInfo['path'];
    $zipFileName = $fileInfo['name'];
    $zipSize = $fileInfo['size'];
    $tempDir = $fileInfo['temp_dir'];

    logDebug('ZIPファイル: ' . $zipPath);
    logDebug('ZIPサイズ: ' . $zipSize . ' bytes');

    // ZIPファイルかチェック
    if (!preg_match('/\.zip$/i', $zipFileName)) {
        throw new Exception('ZIPファイルのみがサポートされています。');
    }

    // ZIPファイルサイズ制限（5GB）
    $maxZipSize = 5 * 1024 * 1024 * 1024;
    if ($zipSize > $maxZipSize) {
        throw new Exception('ZIPファイルサイズは5GB以下にしてください。');
    }

    // ZIPファイル展開用ディレクトリ（プレビュー時に展開済みの場合は再利用）
    $extractDir = null;
    $zipAlreadyExtracted = false;

    if (isset($fileInfo['extract_dir']) && file_exists($fileInfo['extract_dir'])) {
        // プレビュー時に展開済みのディレクトリを再利用
        $extractDir = $fileInfo['extract_dir'];
        $zipAlreadyExtracted = true;
        logDebug('プレビュー時の展開ディレクトリを再利用: ' . $extractDir);
    } else {
        // まだ展開されていない場合は新規に展開
        $extractDir = __DIR__ . '/../uploads/temp/extract_' . $fileIdentifier;
        if (!file_exists($extractDir)) {
            if (!mkdir($extractDir, 0755, true)) {
                throw new Exception('展開ディレクトリの作成に失敗しました。');
            }
        }
        logDebug('新規展開ディレクトリ: ' . $extractDir);
    }

    // ZIPを展開（まだ展開されていない場合のみ）
    if (!$zipAlreadyExtracted) {
        // ZIPを開く
        $zip = new ZipArchive();
        $zipOpenResult = $zip->open($zipPath);

        if ($zipOpenResult !== true) {
            throw new Exception('ZIPファイルを開けません。エラーコード: ' . $zipOpenResult);
        }

        // ZIPボム対策: 展開後のサイズをチェック
        $totalUncompressedSize = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $totalUncompressedSize += $stat['size'];
        }

        $maxUncompressedSize = 20 * 1024 * 1024 * 1024; // 20GB
        if ($totalUncompressedSize > $maxUncompressedSize) {
            $zip->close();
            throw new Exception('ZIP展開後のサイズが大きすぎます（最大20GB）。');
        }

        logDebug('ZIP内ファイル数: ' . $zip->numFiles);
        logDebug('展開後サイズ: ' . round($totalUncompressedSize / 1024 / 1024, 2) . ' MB');

        // ZIP展開
        if (!$zip->extractTo($extractDir)) {
            $zip->close();
            throw new Exception('ZIPファイルの展開に失敗しました。');
        }

        $zip->close();
        logDebug('ZIP展開完了');
    } else {
        logDebug('ZIP展開スキップ（既に展開済み）');
    }

    // データベース接続
    $pdo = getDbConnection();
    $albumProcessor = new AlbumProcessor();

    // アルバムタイトルが未設定の場合、ZIPファイル名を使用
    if (empty($albumTitle)) {
        $albumTitle = pathinfo($zipFileName, PATHINFO_FILENAME);
    }

    // アルバムを作成
    $albumId = $albumProcessor->createAlbum($albumTitle, $albumDescription);
    logDebug('アルバム作成完了: ID=' . $albumId);

    // ZIPインポート履歴を作成
    $historySql = "INSERT INTO zip_import_history (album_id, zip_filename, zip_size, total_files, status) VALUES (?, ?, ?, ?, 'processing')";
    $historyStmt = $pdo->prepare($historySql);
    $historyStmt->execute([$albumId, $zipFileName, $zipSize, $zip->numFiles]);
    $historyId = $pdo->lastInsertId();

    // セッションに進捗情報を保存
    $_SESSION['zip_import_progress'][$historyId] = [
        'total' => 0,
        'processed' => 0,
        'imported' => 0,
        'failed' => 0,
        'current_file' => '',
        'status' => 'processing'
    ];

    // 対応するメディアファイル拡張子
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif', 'mp4', 'mov', 'avi', 'mpeg'];

    // 展開したファイルを再帰的に取得
    $mediaFiles = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($extractDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $extension = strtolower($file->getExtension());
            if (in_array($extension, $allowedExtensions)) {
                // __MACOSXなどのシステムファイルを除外
                $filePath = $file->getPathname();
                if (strpos($filePath, '__MACOSX') === false && strpos($filePath, '.DS_Store') === false) {
                    $mediaFiles[] = $filePath;
                }
            }
        }
    }

    logDebug('メディアファイル数: ' . count($mediaFiles));

    // 進捗情報を更新
    $_SESSION['zip_import_progress'][$historyId]['total'] = count($mediaFiles);

    // 各メディアファイルを処理
    $importedCount = 0;
    $failedCount = 0;
    $displayOrder = 0;

    $totalMediaFiles = count($mediaFiles);
    foreach ($mediaFiles as $index => $filePath) {
        try {
            $fileName = basename($filePath);
            $_SESSION['zip_import_progress'][$historyId]['current_file'] = $fileName;
            $_SESSION['zip_import_progress'][$historyId]['processed'] = $index + 1;

            logDebug("処理中 (" . ($index + 1) . "/{$totalMediaFiles}): {$fileName}");

            // ファイルサイズチェック（500MB）
            $fileSize = filesize($filePath);
            if ($fileSize > 500 * 1024 * 1024) {
                logDebug("スキップ（サイズ超過）: {$fileName}");
                $failedCount++;
                continue;
            }

            // MIMEタイプ取得
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);

            // HEIC特別処理
            $isHeic = false;
            if (in_array($mimeType, ['application/octet-stream', 'image/heic', 'image/heif']) &&
                preg_match('/\.(heic|heif)$/i', $fileName)) {
                $isHeic = true;
            }

            // ファイルタイプの判定
            $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/heic', 'image/heif', 'application/octet-stream'];
            $allowedVideoTypes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/mpeg'];

            $isVideo = in_array($mimeType, $allowedVideoTypes);
            $fileType = $isVideo ? 'video' : 'image';

            if (!in_array($mimeType, array_merge($allowedImageTypes, $allowedVideoTypes)) && !$isHeic) {
                logDebug("スキップ（未対応形式）: {$fileName} ({$mimeType})");
                $failedCount++;
                continue;
            }

            // 保存先ディレクトリ
            $uploadDir = __DIR__ . '/../uploads/' . ($isVideo ? 'videos' : 'images');
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // 安全なファイル名生成
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $safeFileName = date('YmdHis') . '_' . uniqid() . '.' . $fileExtension;
            $finalPath = $uploadDir . '/' . $safeFileName;
            $relativePath = 'uploads/' . ($isVideo ? 'videos' : 'images') . '/' . $safeFileName;

            // ファイルをコピー
            if (!copy($filePath, $finalPath)) {
                logDebug("スキップ（コピー失敗）: {$fileName}");
                $failedCount++;
                continue;
            }

            // ファイルハッシュを計算（重複チェック用）
            $fileHash = md5_file($finalPath);

            // 重複チェック
            $checkSql = "SELECT id FROM media_files WHERE file_hash = ?";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$fileHash]);
            if ($checkStmt->fetch()) {
                logDebug("スキップ（重複）: {$fileName}");
                @unlink($finalPath);
                continue;
            }

            // 以下、既存のfinalize_upload.phpと同様の処理
            $thumbnailPath = null;
            $thumbnailWebPPath = null;
            $rotation = 0;

            // 画像の場合の処理
            if (!$isVideo) {
                // HEICの場合は変換せず、クライアント側（heic2any）で変換
                if ($isHeic) {
                    // HEICファイルはそのまま保存し、サムネイルパスには元ファイルを設定
                    $thumbnailPath = $relativePath; // クライアント側で変換するため、元ファイルをサムネイルとして使用
                    logDebug("HEIC: クライアント側変換用に元ファイルを登録: {$fileName}");
                } else {
                    // HEIC以外の画像の場合のみサムネイル生成
                    $thumbnailDir = __DIR__ . '/../uploads/thumbnails';
                    if (!file_exists($thumbnailDir)) {
                        mkdir($thumbnailDir, 0755, true);
                    }

                    $thumbnailFileName = 'thumb_' . pathinfo($safeFileName, PATHINFO_FILENAME) . '.jpg';
                    $thumbnailFullPath = $thumbnailDir . '/' . $thumbnailFileName;

                    try {
                        if (generateImageThumbnail($finalPath, $thumbnailFullPath, 400, 85)) {
                            $thumbnailPath = 'uploads/thumbnails/' . $thumbnailFileName;

                            // WebP版サムネイル
                            $thumbnailWebPFileName = 'thumb_' . pathinfo($safeFileName, PATHINFO_FILENAME) . '.webp';
                            $thumbnailWebPFullPath = $thumbnailDir . '/' . $thumbnailWebPFileName;

                            if (generateWebPThumbnail($finalPath, $thumbnailWebPFullPath, 400, 85)) {
                                $thumbnailWebPPath = 'uploads/thumbnails/' . $thumbnailWebPFileName;
                            }
                        }
                    } catch (Exception $e) {
                        logDebug('サムネイル生成失敗: ' . $e->getMessage());
                    }
                }
            } else {
                // 動画のサムネイル生成（ffmpegがある場合のみ）
                // ここでは簡易実装としてスキップ
            }

            // メタデータ抽出（EXIF + Google Photos JSON）
            $datetime = null;
            $latitude = null;
            $longitude = null;
            $locationName = null;
            $cameraMake = null;
            $cameraModel = null;
            $orientation = null;
            $hasExif = false;
            $hasGooglePhotosMetadata = false;
            $people = [];

            // 画像の場合、EXIFデータを抽出
            $exifData = [];
            if (!$isVideo) {
                try {
                    $exifData = getExifData($finalPath);
                    if (!empty($exifData['datetime']) || !empty($exifData['latitude']) || !empty($exifData['longitude'])) {
                        $hasExif = true;
                        logDebug("EXIF情報を取得: {$fileName}");
                    }
                } catch (Exception $e) {
                    logDebug("EXIF取得エラー: {$fileName} - " . $e->getMessage());
                }
            }

            // Google Photos JSONメタデータを取得
            $googlePhotosData = getMediaInfoWithGooglePhotosMetadata($filePath, $extractDir, $targetPeople);

            // peopleフィルタで除外されたかチェック
            if ($googlePhotosData && $googlePhotosData['filtered_out']) {
                logDebug("スキップ（peopleフィルタ）: {$fileName}");
                @unlink($finalPath);
                if ($thumbnailPath && file_exists(__DIR__ . '/../' . $thumbnailPath)) {
                    @unlink(__DIR__ . '/../' . $thumbnailPath);
                }
                if ($thumbnailWebPPath && file_exists(__DIR__ . '/../' . $thumbnailWebPPath)) {
                    @unlink(__DIR__ . '/../' . $thumbnailWebPPath);
                }
                continue;
            }

            // メタデータがなく、フィルタが指定されている場合もスキップ
            if (!$googlePhotosData['has_json_metadata'] && $targetPeople !== null && !empty($targetPeople)) {
                logDebug("スキップ（メタデータなし、かつpeopleフィルタ指定）: {$fileName}");
                @unlink($finalPath);
                if ($thumbnailPath && file_exists(__DIR__ . '/../' . $thumbnailPath)) {
                    @unlink(__DIR__ . '/../' . $thumbnailPath);
                }
                if ($thumbnailWebPPath && file_exists(__DIR__ . '/../' . $thumbnailWebPPath)) {
                    @unlink(__DIR__ . '/../' . $thumbnailWebPPath);
                }
                continue;
            }

            // EXIFとGoogle Photosメタデータを統合
            $mergedMetadata = mergeExifAndGooglePhotosMetadata($exifData, $googlePhotosData);

            $datetime = $mergedMetadata['datetime'];
            $latitude = $mergedMetadata['latitude'];
            $longitude = $mergedMetadata['longitude'];
            $cameraMake = $mergedMetadata['camera_make'];
            $cameraModel = $mergedMetadata['camera_model'];
            $orientation = $exifData['orientation'] ?? 1;
            $hasExif = $mergedMetadata['has_exif'];
            $hasGooglePhotosMetadata = $mergedMetadata['has_google_photos_metadata'];
            $people = $mergedMetadata['people'];

            // 位置情報名の取得は後でバッチ処理で実行（処理時間短縮のため）
            // リバースジオコーディングはレート制限（1秒/リクエスト）があり、
            // 100ファイルで100秒以上かかるため、インポート時は緯度経度のみ保存
            // scripts/maintenance/process_pending_geocoding.php で後から位置情報名を取得
            $locationName = null;

            // Google Photosのdescriptionがあれば使用
            $description = $mergedMetadata['description'];

            logDebug("メタデータ統合完了: {$fileName} (EXIF: " . ($hasExif ? 'あり' : 'なし') . ", JSON: " . ($hasGooglePhotosMetadata ? 'あり' : 'なし') . ")");

            // データベースに登録
            $sql = "INSERT INTO media_files (
                filename, stored_filename, file_path, file_type, mime_type, file_size, file_hash,
                thumbnail_path, thumbnail_webp_path, rotation, title, description, upload_date,
                exif_datetime, exif_latitude, exif_longitude, exif_location_name, exif_camera_make, exif_camera_model, exif_orientation,
                google_photos_people, has_google_photos_metadata
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);

            // peopleデータをJSON形式に変換
            $peopleJson = null;
            if (!empty($people)) {
                $peopleJson = json_encode($people, JSON_UNESCAPED_UNICODE);
            }

            $stmt->execute([
                $fileName,
                $safeFileName,
                $relativePath,
                $fileType,
                $mimeType,
                $fileSize,
                $fileHash,
                $thumbnailPath,
                $thumbnailWebPPath,
                $rotation,
                null, // title
                $description, // description (Google Photos descriptionまたはnull)
                $datetime,
                $latitude,
                $longitude,
                $locationName,
                $cameraMake,
                $cameraModel,
                $orientation,
                $peopleJson, // google_photos_people
                $hasGooglePhotosMetadata ? 1 : 0 // has_google_photos_metadata
            ]);

            $mediaId = $pdo->lastInsertId();
            logDebug("メディア登録完了: ID={$mediaId}");

            // アルバムに追加
            $albumProcessor->addMediaToAlbum($albumId, $mediaId, $displayOrder++);

            $importedCount++;
            $_SESSION['zip_import_progress'][$historyId]['imported'] = $importedCount;

        } catch (Exception $e) {
            logDebug("ファイル処理エラー: {$fileName} - " . $e->getMessage());
            $failedCount++;
            $_SESSION['zip_import_progress'][$historyId]['failed'] = $failedCount;
        }
    }

    // カバー画像を設定
    $albumProcessor->setCoverImage($albumId);

    // ZIPインポート履歴を更新
    $updateHistorySql = "UPDATE zip_import_history SET
        imported_files = ?,
        failed_files = ?,
        status = 'completed',
        import_completed_at = NOW()
        WHERE id = ?";
    $updateStmt = $pdo->prepare($updateHistorySql);
    $updateStmt->execute([$importedCount, $failedCount, $historyId]);

    // 進捗情報を更新
    $_SESSION['zip_import_progress'][$historyId]['status'] = 'completed';

    // 一時ファイルを削除
    logDebug('一時ファイル削除中...');
    deleteDirectory($extractDir);
    deleteDirectory($tempDir);
    @unlink($zipPath);
    unset($_SESSION['chunked_files'][$fileIdentifier]);

    logDebug('=== ZIPインポート完了 ===');
    logDebug("インポート成功: {$importedCount}件");
    logDebug("インポート失敗: {$failedCount}件");

    echo json_encode([
        'success' => true,
        'message' => 'ZIPファイルのインポートが完了しました。',
        'albumId' => $albumId,
        'historyId' => $historyId,
        'importedCount' => $importedCount,
        'failedCount' => $failedCount,
        'totalCount' => count($mediaFiles)
    ]);

} catch (Exception $e) {
    // エラー時のクリーンアップ
    if (isset($extractDir) && file_exists($extractDir)) {
        deleteDirectory($extractDir);
    }
    if (isset($tempDir) && file_exists($tempDir)) {
        deleteDirectory($tempDir);
    }
    if (isset($zipPath) && file_exists($zipPath)) {
        @unlink($zipPath);
    }
    if (isset($fileIdentifier) && isset($_SESSION['chunked_files'][$fileIdentifier])) {
        unset($_SESSION['chunked_files'][$fileIdentifier]);
    }

    // 履歴更新
    if (isset($historyId)) {
        $errorSql = "UPDATE zip_import_history SET status = 'failed', error_message = ?, import_completed_at = NOW() WHERE id = ?";
        $errorStmt = $pdo->prepare($errorSql);
        $errorStmt->execute([$e->getMessage(), $historyId]);

        $_SESSION['zip_import_progress'][$historyId]['status'] = 'failed';
    }

    logDebug('===== ZIPインポートエラー =====');
    logDebug('エラーメッセージ: ' . $e->getMessage());
    logDebug('ファイル: ' . $e->getFile() . ':' . $e->getLine());
    logDebug('=====================================');

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * ディレクトリを再帰的に削除
 */
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }

    if (!is_dir($dir)) {
        return @unlink($dir);
    }

    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }

        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }

    return @rmdir($dir);
}
?>
