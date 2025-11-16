<?php
/**
 * EXIF書き込みAPI
 * データベース内のメタデータを画像ファイルに書き込みます
 */

header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';
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

    // メディア情報を取得
    $sql = "SELECT
                id,
                stored_filename,
                file_path,
                thumbnail_path,
                file_type,
                mime_type,
                exif_datetime,
                exif_latitude,
                exif_longitude,
                exif_camera_make,
                exif_camera_model,
                exif_orientation
            FROM media_files
            WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $mediaId]);
    $media = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$media) {
        http_response_code(404);
        echo json_encode(['error' => 'Media not found']);
        exit;
    }

    // 画像ファイルのみサポート
    if ($media['file_type'] !== 'image') {
        http_response_code(400);
        echo json_encode(['error' => 'Only image files are supported for EXIF writing']);
        exit;
    }

    // JPEGファイルのみサポート
    if ($media['mime_type'] !== 'image/jpeg') {
        http_response_code(400);
        echo json_encode(['error' => 'Only JPEG files are supported for EXIF writing']);
        exit;
    }

    // ファイルパスの構築
    $uploadsDir = __DIR__ . '/../uploads/';
    $filePath = $uploadsDir . $media['stored_filename'];

    if (!file_exists($filePath)) {
        http_response_code(404);
        echo json_encode(['error' => 'Image file not found on disk']);
        exit;
    }

    // EXIF書き込み前の状態をチェック
    $hadExifBefore = hasExifData($filePath);

    // EXIF情報を準備
    $exifData = [
        'datetime' => $media['exif_datetime'],
        'latitude' => $media['exif_latitude'],
        'longitude' => $media['exif_longitude'],
        'camera_make' => $media['exif_camera_make'],
        'camera_model' => $media['exif_camera_model'],
        'orientation' => $media['exif_orientation']
    ];

    // EXIF情報をファイルに書き込み
    try {
        writeExifToFile($filePath, $exifData);
    } catch (Exception $e) {
        error_log("EXIF write failed for media ID {$mediaId}: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'error' => 'Failed to write EXIF data to file',
            'message' => $e->getMessage()
        ]);
        exit;
    }

    // サムネイルを再生成
    $thumbnailPath = $uploadsDir . 'thumbnails/thumb_' . pathinfo($media['stored_filename'], PATHINFO_FILENAME) . '.jpg';
    $thumbnailRegenerated = false;
    if (file_exists($thumbnailPath)) {
        $thumbnailRegenerated = regenerateThumbnailAfterExifWrite($filePath, $thumbnailPath);
    }

    // EXIF書き込み後の状態をチェック
    $hasExifNow = hasExifData($filePath);

    echo json_encode([
        'success' => true,
        'message' => 'EXIFデータをファイルに書き込みました',
        'data' => [
            'media_id' => $mediaId,
            'had_exif_before' => $hadExifBefore,
            'has_exif_now' => $hasExifNow,
            'thumbnail_regenerated' => $thumbnailRegenerated,
            'written_data' => [
                'datetime' => $exifData['datetime'],
                'has_gps' => !empty($exifData['latitude']) && !empty($exifData['longitude']),
                'camera_make' => $exifData['camera_make'],
                'camera_model' => $exifData['camera_model']
            ]
        ]
    ]);

} catch (Exception $e) {
    error_log("EXIF write API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
