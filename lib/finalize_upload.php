<?php
/**
 * KidSnaps Growth Album - チャンク分割アップロードの最終処理
 * チャンクで受信したファイルをデータベースに登録
 */

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

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/heic_converter.php';
require_once __DIR__ . '/../includes/image_thumbnail_helper.php';
require_once __DIR__ . '/../includes/video_metadata_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

session_start();

// デバッグログ関数
function debugLog($message, $data = null) {
    $logDir = __DIR__ . '/../uploads/temp';
    if (!file_exists($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/upload_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [FINALIZE] $message";
    if ($data !== null) {
        $logMessage .= ': ' . print_r($data, true);
    }
    $logMessage .= "\n";
    @file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * 緯度経度から位置情報名を取得（リバースジオコーディング）
 */
function getLocationName($latitude, $longitude) {
    if (empty($latitude) || empty($longitude)) {
        return null;
    }

    try {
        $url = sprintf(
            'https://nominatim.openstreetmap.org/reverse?format=json&lat=%s&lon=%s&zoom=18&addressdetails=1&accept-language=ja',
            $latitude,
            $longitude
        );

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: KidSnaps-GrowthAlbum/1.0 (Family Photo Album)',
                    'Accept: application/json'
                ],
                'timeout' => 5
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            error_log('Nominatim API request failed');
            return null;
        }

        $data = json_decode($response, true);

        if (!$data || !isset($data['address'])) {
            return null;
        }

        $address = $data['address'];
        $locationParts = [];

        if (isset($address['country']) && $address['country'] === '日本') {
            if (isset($address['state'])) $locationParts[] = $address['state'];
            if (isset($address['city'])) $locationParts[] = $address['city'];
            elseif (isset($address['town'])) $locationParts[] = $address['town'];
            elseif (isset($address['village'])) $locationParts[] = $address['village'];
            if (isset($address['suburb'])) $locationParts[] = $address['suburb'];
        } else {
            if (isset($address['city'])) $locationParts[] = $address['city'];
            elseif (isset($address['town'])) $locationParts[] = $address['town'];
            if (isset($address['state'])) $locationParts[] = $address['state'];
            if (isset($address['country'])) $locationParts[] = $address['country'];
        }

        if (empty($locationParts)) {
            return isset($data['display_name']) ? mb_substr($data['display_name'], 0, 100) : null;
        }

        return implode(', ', $locationParts);

    } catch (Exception $e) {
        error_log('Reverse geocoding error: ' . $e->getMessage());
        return null;
    }
}

/**
 * リバースジオコーディングのレート制限
 */
function applyRateLimitForGeocoding() {
    $lastRequestFile = sys_get_temp_dir() . '/kidsnaps_geocoding_last_request.txt';

    if (file_exists($lastRequestFile)) {
        $lastRequestTime = (float)file_get_contents($lastRequestFile);
        $timeSinceLastRequest = microtime(true) - $lastRequestTime;

        if ($timeSinceLastRequest < 1.0) {
            usleep((int)((1.0 - $timeSinceLastRequest) * 1000000));
        }
    }

    file_put_contents($lastRequestFile, microtime(true));
}

try {
    debugLog('最終処理開始', [
        'POST' => $_POST,
        'SESSION_FILES' => isset($_SESSION['chunked_files']) ? array_keys($_SESSION['chunked_files']) : 'なし',
        'USER_AGENT' => $_SERVER['HTTP_USER_AGENT'] ?? '不明'
    ]);

    $fileIdentifier = isset($_POST['fileIdentifier']) ? $_POST['fileIdentifier'] : '';
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $thumbnailData = isset($_POST['thumbnailData']) ? $_POST['thumbnailData'] : null;

    debugLog('パラメータ取得', [
        'fileIdentifier' => $fileIdentifier,
        'title' => $title,
        'has_thumbnail' => !empty($thumbnailData)
    ]);

    if (empty($fileIdentifier)) {
        throw new Exception('ファイル識別子が指定されていません。');
    }

    // セッションからファイル情報を取得
    if (!isset($_SESSION['chunked_files'][$fileIdentifier])) {
        throw new Exception('アップロードされたファイルが見つかりません。');
    }

    $fileInfo = $_SESSION['chunked_files'][$fileIdentifier];
    $filePath = $fileInfo['path'];
    $fileName = $fileInfo['name'];
    $fileSize = $fileInfo['size'];
    $mimeType = $fileInfo['mime_type'];
    $tempDir = $fileInfo['temp_dir'];

    // ファイルサイズ制限チェック（100MB）
    $maxFileSize = 100 * 1024 * 1024;
    if ($fileSize > $maxFileSize) {
        throw new Exception('ファイルサイズは100MB以下にしてください。');
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
    debugLog('ファイルハッシュ計算完了', ['hash' => $fileHash]);

    // 以下、upload.phpと同様の処理
    $thumbnailPath = null;
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

    debugLog('メタデータ抽出開始', ['isVideo' => $isVideo]);

    try {
        if (!$isVideo) {
            // 画像のEXIF情報（JavaScriptから送信されたデータを使用）
            debugLog('EXIF情報取得開始（JavaScriptから）');

            if (isset($_POST['exifData']) && !empty($_POST['exifData'])) {
                $exifDataJson = $_POST['exifData'];
                $exifData = json_decode($exifDataJson, true);

                debugLog('EXIF情報取得完了', ['exifData' => $exifData ? 'あり' : 'なし']);

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
                        debugLog('リバースジオコーディング開始', ['lat' => $latitude, 'lon' => $longitude]);
                        applyRateLimitForGeocoding(); // レート制限適用
                        $locationName = getLocationName($latitude, $longitude);
                        if ($locationName) {
                            debugLog('地名取得成功', ['locationName' => $locationName]);
                        } else {
                            debugLog('地名取得失敗');
                        }
                    }
                }
            } else {
                debugLog('EXIF情報なし（JavaScriptから送信されていない）');
            }
        } else {
            // 動画のメタデータ
            debugLog('動画メタデータ抽出開始');
            $videoMeta = getVideoMetadata($finalPath);
            debugLog('動画メタデータ抽出完了', $videoMeta);

            if ($videoMeta) {
                $datetime = $videoMeta['datetime'] ?? null;
                $latitude = $videoMeta['latitude'] ?? null;
                $longitude = $videoMeta['longitude'] ?? null;
                $cameraMake = $videoMeta['camera_make'] ?? null;
                $cameraModel = $videoMeta['camera_model'] ?? null;
                $software = $videoMeta['software'] ?? null;
                $focalLength = $videoMeta['focal_length'] ?? null;
                $locationAccuracy = $videoMeta['location_accuracy'] ?? null;

                debugLog('取得したメタデータ詳細', [
                    'datetime' => $datetime,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'camera_make' => $cameraMake,
                    'camera_model' => $cameraModel,
                    'software' => $software,
                    'focal_length' => $focalLength,
                    'location_accuracy' => $locationAccuracy
                ]);

                // GPS情報がある場合、リバースジオコーディングで地名を取得
                if ($latitude && $longitude && !$locationName) {
                    debugLog('リバースジオコーディング開始（動画）', ['lat' => $latitude, 'lon' => $longitude]);
                    applyRateLimitForGeocoding(); // レート制限適用
                    $locationName = getLocationName($latitude, $longitude);
                    if ($locationName) {
                        debugLog('地名取得成功（動画）', ['locationName' => $locationName]);
                    } else {
                        debugLog('地名取得失敗（動画）');
                    }
                }
            }
        }
    } catch (Exception $e) {
        debugLog('メタデータ抽出エラー', ['error' => $e->getMessage()]);
        error_log('メタデータ抽出エラー: ' . $e->getMessage());
    }

    debugLog('メタデータ抽出完了');

    // データベースに登録
    debugLog('データベース登録開始', [
        'fileName' => $fileName,
        'fileType' => $fileType,
        'fileSize' => $fileSize
    ]);

    $pdo = getDbConnection();

    $sql = "INSERT INTO media_files (
        filename, stored_filename, file_path, file_type, mime_type, file_size, file_hash,
        thumbnail_path, rotation, title, description, upload_date,
        exif_datetime, exif_latitude, exif_longitude, exif_location_name, exif_camera_make, exif_camera_model, exif_orientation,
        exif_software, exif_focal_length, exif_location_accuracy
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

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
    debugLog('データベース登録成功', ['fileId' => $insertedId]);

    // 一時ディレクトリとセッション情報を削除
    if (file_exists($tempDir)) {
        rmdir($tempDir);
    }
    unset($_SESSION['chunked_files'][$fileIdentifier]);

    debugLog('アップロード完了', ['fileId' => $insertedId]);

    echo json_encode([
        'success' => true,
        'message' => 'ファイルのアップロードが完了しました。',
        'fileId' => $insertedId
    ]);

} catch (Exception $e) {
    debugLog('最終処理例外エラー', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    error_log('最終処理エラー: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
