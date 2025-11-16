<?php
/**
 * デバッグ用: セッション情報を確認
 */
session_start();

header('Content-Type: application/json');

echo json_encode([
    'session_id' => session_id(),
    'chunked_files' => $_SESSION['chunked_files'] ?? [],
    'session_data' => $_SESSION
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
