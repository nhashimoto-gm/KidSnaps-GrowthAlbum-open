<?php
/**
 * 撮影日更新API
 * アップロード済みメディアの撮影日時(exif_datetime)を更新します
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

    if (!$data || !isset($data['media_id']) || !isset($data['exif_datetime'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request: media_id and exif_datetime are required']);
        exit;
    }

    $mediaId = (int)$data['media_id'];
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

    // 撮影日時をデータベースに保存
    $updateSql = "UPDATE media_files
                  SET exif_datetime = :exif_datetime,
                      updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id";

    $stmt = $pdo->prepare($updateSql);
    $stmt->execute([
        ':exif_datetime' => $exifDatetime,
        ':id' => $mediaId
    ]);

    echo json_encode([
        'success' => true,
        'message' => '撮影日時を更新しました',
        'exif_datetime' => $exifDatetime
    ]);

} catch (Exception $e) {
    error_log("Photo date update error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
