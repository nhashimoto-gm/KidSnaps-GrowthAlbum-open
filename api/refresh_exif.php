<?php
/**
 * EXIF情報洗替API
 * 既存メディアファイルのEXIF情報を再抽出してデータベースを更新
 */

header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../config/database.php';

// セキュリティ: POSTリクエストのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

try {
    $pdo = getDbConnection();

    // パラメータ取得（JSONとform-urlencodedの両方に対応）
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? 'start';
    } else {
        $input = $_POST;
        $action = $_POST['action'] ?? 'start';
    }

    if ($action === 'start') {
        // 開始: 総件数を取得
        $countSql = "SELECT COUNT(*) FROM media_files";
        $totalCount = $pdo->query($countSql)->fetchColumn();

        // 進捗情報をセッションに保存
        $_SESSION['exif_refresh'] = [
            'total' => $totalCount,
            'processed' => 0,
            'updated' => 0,
            'errors' => 0,
            'start_time' => time()
        ];

        echo json_encode([
            'success' => true,
            'total' => $totalCount,
            'message' => "EXIF洗替を開始します。全{$totalCount}件のファイルを処理します。"
        ]);
        exit;
    }

    if ($action === 'list') {
        // ファイルリストを取得
        $sql = "SELECT id, filename, file_path, file_type FROM media_files ORDER BY id ASC";
        $stmt = $pdo->query($sql);
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'files' => $files
        ]);
        exit;
    }

    if ($action === 'update') {
        // クライアントから送信されたEXIF情報をデータベースに保存
        $fileId = $input['file_id'] ?? null;
        $exifData = $input['exif_data'] ?? null;

        if (!$fileId || !$exifData) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
            exit;
        }

        // データベースを更新
        $updateSql = "UPDATE media_files SET
                      exif_datetime = :exif_datetime,
                      exif_latitude = :exif_latitude,
                      exif_longitude = :exif_longitude,
                      exif_camera_make = :exif_camera_make,
                      exif_camera_model = :exif_camera_model,
                      exif_orientation = :exif_orientation,
                      exif_software = :exif_software,
                      exif_focal_length = :exif_focal_length,
                      exif_location_accuracy = :exif_location_accuracy,
                      updated_at = CURRENT_TIMESTAMP
                      WHERE id = :id";

        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([
            ':exif_datetime' => $exifData['datetime'],
            ':exif_latitude' => $exifData['latitude'],
            ':exif_longitude' => $exifData['longitude'],
            ':exif_camera_make' => $exifData['camera_make'],
            ':exif_camera_model' => $exifData['camera_model'],
            ':exif_orientation' => $exifData['orientation'] ?? 1,
            ':exif_software' => $exifData['software'] ?? null,
            ':exif_focal_length' => $exifData['focal_length'] ?? null,
            ':exif_location_accuracy' => $exifData['location_accuracy'] ?? null,
            ':id' => $fileId
        ]);

        // セッションの進捗情報を更新
        if (isset($_SESSION['exif_refresh'])) {
            $_SESSION['exif_refresh']['updated']++;
        }

        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'update_location') {
        // 位置情報名を更新
        $fileId = $input['file_id'] ?? null;
        $locationName = $input['location_name'] ?? null;

        if (!$fileId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
            exit;
        }

        $updateSql = "UPDATE media_files SET exif_location_name = :location_name WHERE id = :id";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([
            ':location_name' => $locationName,
            ':id' => $fileId
        ]);

        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'update_video') {
        // 動画ファイルのメタデータをサーバー側で抽出して更新
        require_once __DIR__ . '/../includes/video_metadata_helper.php';

        $fileId = $input['file_id'] ?? null;
        $filePath = $input['file_path'] ?? null;

        if (!$fileId || !$filePath) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
            exit;
        }

        // ファイルパスを絶対パスに変換
        $absolutePath = __DIR__ . '/../' . $filePath;

        if (!file_exists($absolutePath)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'File not found']);
            exit;
        }

        // 動画メタデータを抽出
        $videoMeta = getVideoMetadata($absolutePath);

        if (!$videoMeta) {
            echo json_encode(['success' => false, 'error' => 'Failed to extract metadata']);
            exit;
        }

        // データベースを更新
        $updateSql = "UPDATE media_files SET
                      exif_datetime = :exif_datetime,
                      exif_latitude = :exif_latitude,
                      exif_longitude = :exif_longitude,
                      exif_camera_make = :exif_camera_make,
                      exif_camera_model = :exif_camera_model,
                      exif_software = :exif_software,
                      exif_focal_length = :exif_focal_length,
                      exif_location_accuracy = :exif_location_accuracy,
                      updated_at = CURRENT_TIMESTAMP
                      WHERE id = :id";

        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([
            ':exif_datetime' => $videoMeta['datetime'],
            ':exif_latitude' => $videoMeta['latitude'],
            ':exif_longitude' => $videoMeta['longitude'],
            ':exif_camera_make' => $videoMeta['camera_make'],
            ':exif_camera_model' => $videoMeta['camera_model'],
            ':exif_software' => $videoMeta['software'],
            ':exif_focal_length' => $videoMeta['focal_length'],
            ':exif_location_accuracy' => $videoMeta['location_accuracy'],
            ':id' => $fileId
        ]);

        // セッションの進捗情報を更新
        if (isset($_SESSION['exif_refresh'])) {
            $_SESSION['exif_refresh']['updated']++;
        }

        // GPS情報を返す（リバースジオコーディング用）
        echo json_encode([
            'success' => true,
            'latitude' => $videoMeta['latitude'],
            'longitude' => $videoMeta['longitude']
        ]);
        exit;
    }

    if ($action === 'status') {
        // 進捗状況を取得
        if (isset($_SESSION['exif_refresh'])) {
            $status = $_SESSION['exif_refresh'];
            $elapsedTime = time() - $status['start_time'];
            $progress = $status['total'] > 0 ? ($status['processed'] / $status['total']) * 100 : 0;

            echo json_encode([
                'success' => true,
                'status' => $status,
                'progress' => round($progress, 2),
                'elapsedTime' => $elapsedTime
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => '進捗情報が見つかりません'
            ]);
        }
        exit;
    }

    if ($action === 'complete') {
        // 完了処理
        $finalStatus = $_SESSION['exif_refresh'] ?? null;
        unset($_SESSION['exif_refresh']);
        unset($_SESSION['last_geocode_time']);

        echo json_encode([
            'success' => true,
            'finalStatus' => $finalStatus,
            'message' => 'EXIF情報の洗替が完了しました。'
        ]);
        exit;
    }

    // 不正なアクション
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid action']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'サーバーエラー: ' . $e->getMessage()
    ]);
}
