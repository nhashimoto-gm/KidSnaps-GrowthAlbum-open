<?php
/**
 * KidSnaps Growth Album - ZIPインポート進捗確認API
 * セッションから進捗情報を取得して返す
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

session_start();

try {
    $importId = isset($_GET['import_id']) ? (int)$_GET['import_id'] : 0;

    if ($importId <= 0) {
        throw new Exception('Invalid import ID');
    }

    // セッションから進捗情報を取得
    if (!isset($_SESSION['zip_import_progress'][$importId])) {
        throw new Exception('Import progress not found');
    }

    $progress = $_SESSION['zip_import_progress'][$importId];

    echo json_encode([
        'success' => true,
        'progress' => $progress
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
