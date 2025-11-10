<?php
/**
 * 重複チェックAPI
 * クライアント側で計算したファイルハッシュを受け取り、
 * データベースに既に同じファイルが存在するかチェックします
 */

header('Content-Type: application/json');

// エラー表示を無効化（JSON出力のため）
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

    // リクエストボディを取得
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || !isset($data['hash'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request: hash is required']);
        exit;
    }

    $hash = $data['hash'];
    $filename = $data['filename'] ?? null;
    $filesize = $data['filesize'] ?? null;

    // ハッシュの検証（MD5は32文字の16進数）
    if (!preg_match('/^[a-f0-9]{32}$/i', $hash)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid hash format']);
        exit;
    }

    // データベース接続
    $pdo = getDbConnection();

    // ハッシュで重複チェック（全ての重複ファイルを取得）
    $sql = "SELECT id, filename, stored_filename, file_path, file_type, upload_date
            FROM media_files
            WHERE file_hash = :hash
            ORDER BY upload_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':hash' => $hash]);
    $existingFiles = $stmt->fetchAll();

    if (!empty($existingFiles)) {
        // 重複ファイルが見つかった（複数ある可能性も考慮）
        $existingList = array_map(function($file) {
            return [
                'id' => $file['id'],
                'filename' => $file['filename'],
                'file_type' => $file['file_type'],
                'upload_date' => $file['upload_date']
            ];
        }, $existingFiles);

        echo json_encode([
            'isDuplicate' => true,
            'existing' => $existingList,
            'count' => count($existingFiles)
        ]);
    } else {
        // 重複なし
        echo json_encode([
            'isDuplicate' => false
        ]);
    }

} catch (Exception $e) {
    error_log("Duplicate check error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
