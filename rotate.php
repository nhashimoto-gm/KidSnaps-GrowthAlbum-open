<?php
/**
 * KidSnaps Growth Album - メディア回転処理
 * 画像・動画の回転角度を保存
 */

require_once 'config/database.php';

// セッション開始
session_start();

// JSONレスポンスを返す関数
function jsonResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// POSTリクエストの確認
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

// リクエストボディを取得（JSON）
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    jsonResponse(false, 'Invalid JSON data');
}

// 必須パラメータの確認
if (!isset($data['media_id']) || !isset($data['rotation'])) {
    jsonResponse(false, 'Missing required parameters');
}

$mediaId = intval($data['media_id']);
$rotation = intval($data['rotation']);

// 回転角度の検証（0, 90, 180, 270 のみ許可）
if (!in_array($rotation, [0, 90, 180, 270])) {
    jsonResponse(false, 'Invalid rotation angle. Must be 0, 90, 180, or 270');
}

try {
    // データベース接続
    $pdo = getDbConnection();

    // メディアの存在確認
    $checkSql = "SELECT id FROM media_files WHERE id = :media_id";
    $stmt = executeQuery($pdo, $checkSql, [':media_id' => $mediaId]);
    $media = $stmt->fetch();

    if (!$media) {
        jsonResponse(false, 'Media not found');
    }

    // 回転角度を更新
    $updateSql = "UPDATE media_files SET rotation = :rotation WHERE id = :media_id";
    executeQuery($pdo, $updateSql, [
        ':rotation' => $rotation,
        ':media_id' => $mediaId
    ]);

    jsonResponse(true, 'Rotation saved successfully', ['rotation' => $rotation]);

} catch (Exception $e) {
    error_log('Rotation update error: ' . $e->getMessage());
    jsonResponse(false, 'Failed to save rotation: ' . $e->getMessage());
}
