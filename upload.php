<?php
/**
 * KidSnaps Growth Album - メディアアップロード処理（複数ファイル対応）
 * セキュアなファイルアップロードとデータベース登録
 */

// 一時的なエラー表示（デバッグ用）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';
require_once 'includes/exif_helper.php';
require_once 'includes/heic_converter.php';
require_once 'includes/image_thumbnail_helper.php';
require_once 'includes/video_metadata_helper.php';

// エラーメッセージ格納用
$errors = [];
$uploadSuccess = false;
$uploadedCount = 0;

// POSTリクエストの確認
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// CSRFトークン検証（将来的な実装推奨）
// セッション開始
session_start();

try {
    // ファイルアップロードの確認
    if (!isset($_FILES['mediaFile']) || !is_array($_FILES['mediaFile']['name'])) {
        throw new Exception('ファイルが選択されていません。');
    }

    // 複数ファイルの場合、配列を再構成
    $files = [];
    $fileCount = count($_FILES['mediaFile']['name']);

    // 1つもファイルがアップロードされていない場合
    if ($fileCount === 0 || $_FILES['mediaFile']['error'][0] === UPLOAD_ERR_NO_FILE) {
        throw new Exception('ファイルが選択されていません。');
    }

    // ファイル配列を整理（PHPの $_FILES 配列構造を扱いやすくする）
    for ($i = 0; $i < $fileCount; $i++) {
        if ($_FILES['mediaFile']['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue; // スキップ
        }

        $files[] = [
            'name' => $_FILES['mediaFile']['name'][$i],
            'type' => $_FILES['mediaFile']['type'][$i],
            'tmp_name' => $_FILES['mediaFile']['tmp_name'][$i],
            'error' => $_FILES['mediaFile']['error'][$i],
            'size' => $_FILES['mediaFile']['size'][$i]
        ];
    }

    if (empty($files)) {
        throw new Exception('有効なファイルが選択されていません。');
    }

    // フォームデータ取得（全ファイル共通）
    $title = isset($_POST['title']) ? trim($_POST['title']) : null;
    $description = isset($_POST['description']) ? trim($_POST['description']) : null;

    // 許可されたMIMEタイプ
    $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/heic', 'image/heif', 'application/octet-stream'];
    $allowedVideoTypes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/mpeg'];
    $allowedTypes = array_merge($allowedImageTypes, $allowedVideoTypes);

    // データベース接続
    $pdo = getDbConnection();

    // サムネイル配列を準備（動画用）
    $thumbnails = [];
    if (isset($_FILES['videoThumbnail']) && is_array($_FILES['videoThumbnail']['name'])) {
        $thumbnailCount = count($_FILES['videoThumbnail']['name']);
        for ($i = 0; $i < $thumbnailCount; $i++) {
            if ($_FILES['videoThumbnail']['error'][$i] === UPLOAD_ERR_OK) {
                $thumbnails[$i] = [
                    'name' => $_FILES['videoThumbnail']['name'][$i],
                    'tmp_name' => $_FILES['videoThumbnail']['tmp_name'][$i],
                    'error' => $_FILES['videoThumbnail']['error'][$i]
                ];
            }
        }
    }

    // 各ファイルを処理
    $videoIndex = 0; // 動画ファイルのインデックス（サムネイル対応用）
    foreach ($files as $file) {
        try {
            // アップロードエラーチェック
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'ファイルサイズが大きすぎます（php.ini制限）。',
                    UPLOAD_ERR_FORM_SIZE => 'ファイルサイズが大きすぎます。',
                    UPLOAD_ERR_PARTIAL => 'ファイルが部分的にしかアップロードされませんでした。',
                    UPLOAD_ERR_NO_TMP_DIR => '一時フォルダがありません。',
                    UPLOAD_ERR_CANT_WRITE => 'ディスクへの書き込みに失敗しました。',
                    UPLOAD_ERR_EXTENSION => 'PHP拡張によってアップロードが中断されました。',
                ];
                $errorMsg = $errorMessages[$file['error']] ?? '不明なアップロードエラー。';
                error_log("ファイル '{$file['name']}' のアップロードエラー: {$errorMsg}");
                $errors[] = "{$file['name']}: {$errorMsg}";
                continue;
            }

            // ファイルサイズチェック（50MB制限）
            $maxFileSize = 50 * 1024 * 1024; // 50MB in bytes
            if ($file['size'] > $maxFileSize) {
                error_log("ファイル '{$file['name']}' はサイズが大きすぎます: " . $file['size']);
                $errors[] = "{$file['name']}: ファイルサイズは50MB以下にしてください。";
                continue;
            }

            // MIMEタイプの検証
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            // HEICファイルの特別処理
            $isHeic = isHeicFile($file['tmp_name'], $mimeType);

            if (!in_array($mimeType, $allowedTypes) && !$isHeic) {
                error_log("ファイル '{$file['name']}' はサポートされていない形式です: {$mimeType}");
                $errors[] = "{$file['name']}: サポートされていないファイル形式です。";
                continue;
            }

            // ファイルタイプの判定
            $fileType = in_array($mimeType, $allowedImageTypes) || $isHeic ? 'image' : 'video';

            // 元のファイル名取得とサニタイズ
            $originalFilename = basename($file['name']);
            $pathInfo = pathinfo($originalFilename);
            $extension = strtolower($pathInfo['extension']);

            // 安全なファイル名生成（タイムスタンプ + ユニークID）
            $storedFilename = date('YmdHis') . '_' . uniqid() . '.' . $extension;

            // 保存先ディレクトリ
            $uploadDir = ($fileType === 'image') ? 'uploads/images/' : 'uploads/videos/';

            // ディレクトリが存在しない場合は作成
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    throw new Exception('アップロードディレクトリの作成に失敗しました。');
                }
            }

            // .htaccess で直接実行を防ぐ（セキュリティ対策）
            $htaccessPath = $uploadDir . '.htaccess';
            if (!file_exists($htaccessPath)) {
                $htaccessContent = "# セキュリティ設定\n";
                $htaccessContent .= "Options -Indexes\n";
                $htaccessContent .= "<FilesMatch \"\.(php|phtml|php3|php4|php5|php7|phps)$\">\n";
                $htaccessContent .= "    Require all denied\n";
                $htaccessContent .= "</FilesMatch>\n";
                file_put_contents($htaccessPath, $htaccessContent);
            }

            // ファイルパス
            $filePath = $uploadDir . $storedFilename;

            // ファイルを移動
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                error_log("ファイル '{$file['name']}' の保存に失敗しました。");
                $errors[] = "{$file['name']}: ファイルの保存に失敗しました。";
                continue;
            }

            // HEICファイルの場合、JPEGに変換（サーバー側フォールバック）
            if ($isHeic) {
                $jpegFilePath = preg_replace('/\.(heic|heif)$/i', '.jpg', $filePath);
                $conversionSuccess = convertHeicToJpeg($filePath, $jpegFilePath);

                if ($conversionSuccess) {
                    // 変換成功：元のHEICファイルを削除し、JPEGファイルを使用
                    unlink($filePath);
                    $filePath = $jpegFilePath;
                    $storedFilename = basename($jpegFilePath);
                    $mimeType = 'image/jpeg';
                    $extension = 'jpg';
                }
                // 変換失敗時はHEICファイルのまま保存
            }

            // サムネイル処理
            $thumbnailPath = null;

            // 画像の場合、サムネイルを自動生成
            if ($fileType === 'image') {
                // サムネイル保存ディレクトリ
                $thumbnailDir = 'uploads/thumbnails/';
                if (!is_dir($thumbnailDir)) {
                    if (!mkdir($thumbnailDir, 0755, true)) {
                        error_log('サムネイルディレクトリの作成に失敗しました。');
                    }
                }

                // .htaccess でPHP実行を防ぐ
                $htaccessPath = $thumbnailDir . '.htaccess';
                if (!file_exists($htaccessPath)) {
                    $htaccessContent = "# セキュリティ設定\n";
                    $htaccessContent .= "Options -Indexes\n";
                    $htaccessContent .= "<FilesMatch \"\.(php|phtml|php3|php4|php5|php7|phps)$\">\n";
                    $htaccessContent .= "    Require all denied\n";
                    $htaccessContent .= "</FilesMatch>\n";
                    file_put_contents($htaccessPath, $htaccessContent);
                }

                // サムネイルのファイル名（元のファイル名と同じタイムスタンプを使用）
                $thumbnailFilename = date('YmdHis') . '_' . uniqid() . '_thumb.jpg';
                $thumbnailPath = $thumbnailDir . $thumbnailFilename;

                // サムネイルを生成
                $thumbnailSuccess = generateImageThumbnail($filePath, $thumbnailPath, 320, 85);
                if (!$thumbnailSuccess) {
                    // サムネイル生成失敗はエラーにしない（元画像は保存済み）
                    error_log('画像サムネイルの生成に失敗しました: ' . $filePath);
                    $thumbnailPath = null;
                } else {
                    error_log('画像サムネイルを生成しました: ' . $thumbnailPath);
                }
            }

            // 動画の場合、サムネイルを処理
            if ($fileType === 'video' && isset($thumbnails[$videoIndex])) {
                $thumbnailFile = $thumbnails[$videoIndex];

                // サムネイル保存ディレクトリ
                $thumbnailDir = 'uploads/thumbnails/';
                if (!is_dir($thumbnailDir)) {
                    if (!mkdir($thumbnailDir, 0755, true)) {
                        error_log('サムネイルディレクトリの作成に失敗しました。');
                    }
                }

                // サムネイルのファイル名（元のファイル名と同じタイムスタンプを使用）
                $thumbnailFilename = date('YmdHis') . '_' . uniqid() . '_thumb.jpg';
                $thumbnailPath = $thumbnailDir . $thumbnailFilename;

                // サムネイルを保存
                if (!move_uploaded_file($thumbnailFile['tmp_name'], $thumbnailPath)) {
                    // サムネイル保存失敗はエラーにしない（動画は保存済み）
                    error_log('サムネイルの保存に失敗しました: ' . $thumbnailPath);
                    $thumbnailPath = null;
                }
            }

            if ($fileType === 'video') {
                $videoIndex++;
            }

            // EXIF/メタデータ情報の取得
            $autoRotation = 0;
            $exifData = [
                'datetime' => null,
                'latitude' => null,
                'longitude' => null,
                'location_name' => null,
                'camera_make' => null,
                'camera_model' => null,
                'orientation' => 1
            ];

            if ($fileType === 'image') {
                // 画像：EXIF情報から回転角度と詳細情報を取得
                $autoRotation = getRotationFromExif($filePath);
                if ($autoRotation !== 0) {
                    error_log("EXIF自動回転検出: {$file['name']} - {$autoRotation}度");
                }

                // 詳細なEXIF情報を取得
                $exifData = getExifData($filePath);
                if ($exifData['datetime']) {
                    error_log("EXIF撮影日時: {$file['name']} - {$exifData['datetime']}");
                }
                if ($exifData['latitude'] && $exifData['longitude']) {
                    error_log("EXIF GPS: {$file['name']} - {$exifData['latitude']}, {$exifData['longitude']}");

                    // リバースジオコーディングで位置情報名を取得
                    applyRateLimitForGeocoding(); // レート制限を適用
                    $exifData['location_name'] = getLocationName($exifData['latitude'], $exifData['longitude']);

                    if ($exifData['location_name']) {
                        error_log("EXIF 位置情報: {$file['name']} - {$exifData['location_name']}");
                    }
                }
            } else if ($fileType === 'video') {
                // 動画：getID3ライブラリでメタデータを取得
                $videoMetadata = getVideoMetadata($filePath);

                if ($videoMetadata['datetime']) {
                    $exifData['datetime'] = $videoMetadata['datetime'];
                    error_log("動画撮影日時: {$file['name']} - {$exifData['datetime']}");
                }

                if ($videoMetadata['latitude'] && $videoMetadata['longitude']) {
                    $exifData['latitude'] = $videoMetadata['latitude'];
                    $exifData['longitude'] = $videoMetadata['longitude'];
                    error_log("動画GPS: {$file['name']} - {$exifData['latitude']}, {$exifData['longitude']}");

                    // リバースジオコーディングで位置情報名を取得
                    applyRateLimitForGeocoding();
                    $exifData['location_name'] = getLocationName($exifData['latitude'], $exifData['longitude']);

                    if ($exifData['location_name']) {
                        error_log("動画位置情報: {$file['name']} - {$exifData['location_name']}");
                    }
                }

                if ($videoMetadata['camera_make']) {
                    $exifData['camera_make'] = $videoMetadata['camera_make'];
                }
                if ($videoMetadata['camera_model']) {
                    $exifData['camera_model'] = $videoMetadata['camera_model'];
                }
            }

            // データベースに登録
            $sql = "INSERT INTO media_files (
                        filename, stored_filename, file_path, file_type, mime_type, file_size,
                        thumbnail_path, rotation, title, description, upload_date,
                        exif_datetime, exif_latitude, exif_longitude, exif_location_name,
                        exif_camera_make, exif_camera_model, exif_orientation
                    ) VALUES (
                        :filename, :stored_filename, :file_path, :file_type, :mime_type, :file_size,
                        :thumbnail_path, :rotation, :title, :description, NOW(),
                        :exif_datetime, :exif_latitude, :exif_longitude, :exif_location_name,
                        :exif_camera_make, :exif_camera_model, :exif_orientation
                    )";

            $params = [
                ':filename' => $originalFilename,
                ':stored_filename' => $storedFilename,
                ':file_path' => $filePath,
                ':file_type' => $fileType,
                ':mime_type' => $mimeType,
                ':file_size' => $file['size'],
                ':thumbnail_path' => $thumbnailPath,
                ':rotation' => $autoRotation,
                ':title' => !empty($title) ? $title : null,
                ':description' => !empty($description) ? $description : null,
                ':exif_datetime' => $exifData['datetime'],
                ':exif_latitude' => $exifData['latitude'],
                ':exif_longitude' => $exifData['longitude'],
                ':exif_location_name' => $exifData['location_name'],
                ':exif_camera_make' => $exifData['camera_make'],
                ':exif_camera_model' => $exifData['camera_model'],
                ':exif_orientation' => $exifData['orientation']
            ];

            executeQuery($pdo, $sql, $params);
            $uploadedCount++;

        } catch (Exception $e) {
            // 個別ファイルのエラーをログに記録して続行
            error_log("ファイル '{$file['name']}' の処理中にエラー: " . $e->getMessage());
            $errors[] = "{$file['name']}: " . $e->getMessage();

            // エラー時は一時ファイルを削除
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    // 結果に応じてリダイレクト
    if ($uploadedCount > 0) {
        if (!empty($errors)) {
            // 一部成功、一部失敗
            $_SESSION['upload_partial'] = "{$uploadedCount}件のファイルをアップロードしました。" . count($errors) . "件のエラーがありました。";
            header('Location: index.php?success=partial');
        } else {
            // 全て成功
            $_SESSION['upload_success'] = "{$uploadedCount}件のファイルをアップロードしました。";
            header('Location: index.php?success=upload');
        }
    } else {
        // 全て失敗
        $_SESSION['upload_error'] = 'ファイルのアップロードに失敗しました: ' . implode(', ', $errors);
        header('Location: index.php?error=upload');
    }
    exit;

} catch (Exception $e) {
    // エラー時は一時ファイルを削除
    if (isset($filePath) && file_exists($filePath)) {
        unlink($filePath);
    }

    // エラーメッセージをセッションに保存
    $_SESSION['upload_error'] = $e->getMessage();
    header('Location: index.php?error=upload');
    exit;
}
?>
