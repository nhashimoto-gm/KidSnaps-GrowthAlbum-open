<?php
/**
 * 回転設定更新API
 * アップロード済みメディアの回転角度を更新し、サムネイルを再生成します
 */

header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/image_thumbnail_helper.php';

try {
    // POSTリクエストのみ許可
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        exit;
    }

    session_start();

    // リクエストボディを取得
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || !isset($data['media_id']) || !isset($data['rotation'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request: media_id and rotation are required']);
        exit;
    }

    $mediaId = (int)$data['media_id'];
    $rotation = (int)$data['rotation'];

    // 回転角度の検証（0, 90, 180, 270のみ許可）
    if (!in_array($rotation, [0, 90, 180, 270])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid rotation value. Must be 0, 90, 180, or 270']);
        exit;
    }

    // データベース接続
    $pdo = getDbConnection();

    // メディア情報を取得
    $sql = "SELECT id, file_path, file_type, thumbnail_path, stored_filename
            FROM media_files
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $mediaId]);
    $media = $stmt->fetch();

    if (!$media) {
        http_response_code(404);
        echo json_encode(['error' => 'Media not found']);
        exit;
    }

    // 画像ファイルのフルパスを取得
    $fullFilePath = __DIR__ . '/../' . $media['file_path'];

    if (!file_exists($fullFilePath)) {
        http_response_code(404);
        echo json_encode(['error' => 'Media file not found on disk']);
        exit;
    }

    // サムネイルの再生成（画像の場合のみ）
    $newThumbnailPath = null;
    if ($media['file_type'] === 'image') {
        $thumbnailDir = __DIR__ . '/../uploads/thumbnails';
        if (!file_exists($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
        }

        $thumbnailFileName = 'thumb_' . pathinfo($media['stored_filename'], PATHINFO_FILENAME) . '.jpg';
        $thumbnailFullPath = $thumbnailDir . '/' . $thumbnailFileName;

        try {
            // サムネイルを再生成
            if (generateImageThumbnail($fullFilePath, $thumbnailFullPath, 400, 85)) {
                $newThumbnailPath = 'uploads/thumbnails/' . $thumbnailFileName;
            }
        } catch (Exception $e) {
            error_log('サムネイル再生成失敗: ' . $e->getMessage());
            // サムネイル再生成失敗は警告として扱い、回転設定は保存する
        }
    }

    // 回転角度をデータベースに保存
    $updateSql = "UPDATE media_files SET rotation = :rotation";
    $params = [':rotation' => $rotation, ':id' => $mediaId];

    // サムネイルパスも更新（画像の場合）
    if ($newThumbnailPath) {
        $updateSql .= ", thumbnail_path = :thumbnail_path";
        $params[':thumbnail_path'] = $newThumbnailPath;
    }

    $updateSql .= ", updated_at = CURRENT_TIMESTAMP WHERE id = :id";

    $stmt = $pdo->prepare($updateSql);
    $stmt->execute($params);

    echo json_encode([
        'success' => true,
        'message' => '回転設定を保存しました',
        'rotation' => $rotation,
        'thumbnail_path' => $newThumbnailPath ?: $media['thumbnail_path'],
        'timestamp' => time() // キャッシュバスター用
    ]);

} catch (Exception $e) {
    error_log("Rotation update error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
