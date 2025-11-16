<?php
/**
 * メタデータ更新API
 * アップロード済みメディアのタイトル、撮影日時、ロケーション情報を更新します
 */

header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';

try {
    // POSTリクエストのみ許可
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        exit;
    }

    session_start();

    // 管理者権限チェック
    if (!isset($_SESSION['admin_mode']) || $_SESSION['admin_mode'] !== true) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden: Admin access required']);
        exit;
    }

    // リクエストボディを取得
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || !isset($data['media_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request: media_id is required']);
        exit;
    }

    $mediaId = (int)$data['media_id'];

    // データベース接続
    $pdo = getDbConnection();

    // メディア情報を取得（存在確認）
    $sql = "SELECT id FROM media_files WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $mediaId]);
    $media = $stmt->fetch();

    if (!$media) {
        http_response_code(404);
        echo json_encode(['error' => 'Media not found']);
        exit;
    }

    // 更新するカラムとバインドパラメータを動的に構築
    $updateFields = [];
    $bindParams = [':id' => $mediaId];

    // タイトルの更新
    if (isset($data['title'])) {
        $updateFields[] = 'title = :title';
        $bindParams[':title'] = $data['title'];
    }

    // 撮影日時の更新
    if (isset($data['exif_datetime'])) {
        $exifDatetime = $data['exif_datetime'];

        // 空文字列の場合はNULLとして扱う
        if (empty($exifDatetime)) {
            $exifDatetime = null;
        } else {
            // 日時形式の検証
            $datetime = DateTime::createFromFormat('Y-m-d\TH:i', $exifDatetime);
            if (!$datetime) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid datetime format. Expected: YYYY-MM-DDTHH:MM']);
                exit;
            }
            // データベース用のフォーマットに変換
            $exifDatetime = $datetime->format('Y-m-d H:i:s');
        }

        $updateFields[] = 'exif_datetime = :exif_datetime';
        $bindParams[':exif_datetime'] = $exifDatetime;
    }

    // ロケーション名の更新
    if (isset($data['exif_location_name'])) {
        $locationName = trim($data['exif_location_name']);
        $updateFields[] = 'exif_location_name = :exif_location_name';
        $bindParams[':exif_location_name'] = empty($locationName) ? null : $locationName;
    }

    // 緯度の更新
    if (isset($data['exif_latitude'])) {
        $latitude = $data['exif_latitude'];
        if (empty($latitude) || $latitude === '') {
            $latitude = null;
        } else {
            $latitude = (float)$latitude;
            if ($latitude < -90 || $latitude > 90) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid latitude. Must be between -90 and 90']);
                exit;
            }
        }
        $updateFields[] = 'exif_latitude = :exif_latitude';
        $bindParams[':exif_latitude'] = $latitude;
    }

    // 経度の更新
    if (isset($data['exif_longitude'])) {
        $longitude = $data['exif_longitude'];
        if (empty($longitude) || $longitude === '') {
            $longitude = null;
        } else {
            $longitude = (float)$longitude;
            if ($longitude < -180 || $longitude > 180) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid longitude. Must be between -180 and 180']);
                exit;
            }
        }
        $updateFields[] = 'exif_longitude = :exif_longitude';
        $bindParams[':exif_longitude'] = $longitude;
    }

    // 更新するフィールドがない場合
    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No fields to update']);
        exit;
    }

    // updated_atを追加
    $updateFields[] = 'updated_at = CURRENT_TIMESTAMP';

    // SQL文を構築して実行
    $updateSql = "UPDATE media_files SET " . implode(', ', $updateFields) . " WHERE id = :id";
    $stmt = $pdo->prepare($updateSql);
    $stmt->execute($bindParams);

    // 更新後のデータを取得
    $sql = "SELECT title, exif_datetime, exif_location_name, exif_latitude, exif_longitude
            FROM media_files WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $mediaId]);
    $updatedMedia = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'メタデータを更新しました',
        'data' => $updatedMedia
    ]);

} catch (Exception $e) {
    error_log("Metadata update error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
