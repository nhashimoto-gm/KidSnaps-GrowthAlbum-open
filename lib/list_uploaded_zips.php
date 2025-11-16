<?php
/**
 * KidSnaps Growth Album - アップロード済みZIPファイル一覧取得
 * セッションに保存されているアップロード中のファイルと、
 * データベースに保存されているインポート履歴を返す
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

session_start();

try {
    require_once __DIR__ . '/../config/database.php';

    $uploadingFiles = [];  // アップロード中のファイル
    $importedFiles = [];   // インポート済みのファイル

    // 1. セッションからアップロード中のファイルを取得
    if (isset($_SESSION['chunked_files']) && is_array($_SESSION['chunked_files'])) {
        foreach ($_SESSION['chunked_files'] as $fileIdentifier => $fileInfo) {
            // ファイルが実際に存在するかチェック
            if (isset($fileInfo['path']) && file_exists($fileInfo['path'])) {
                $uploadingFiles[] = [
                    'fileIdentifier' => $fileIdentifier,
                    'fileName' => $fileInfo['name'] ?? 'Unknown',
                    'fileSize' => $fileInfo['size'] ?? 0,
                    'fileSizeFormatted' => formatBytes($fileInfo['size'] ?? 0),
                    'uploadedAt' => isset($fileInfo['uploaded_at']) ? $fileInfo['uploaded_at'] : date('Y-m-d H:i:s'),
                    'hasExtractDir' => isset($fileInfo['extract_dir']) && file_exists($fileInfo['extract_dir'])
                ];
            }
        }
    }

    // 2. データベースからインポート履歴を取得
    $pdo = getDbConnection();
    $sql = "SELECT
                h.id,
                h.zip_filename,
                h.total_files,
                h.imported_files,
                h.failed_files,
                h.status,
                h.import_started_at,
                h.import_completed_at,
                a.id as album_id,
                a.title as album_title,
                a.cover_image_path
            FROM zip_import_history h
            LEFT JOIN albums a ON h.album_id = a.id
            ORDER BY h.import_started_at DESC
            LIMIT 50";

    $stmt = $pdo->query($sql);
    $importHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($importHistory as $record) {
        $importedFiles[] = [
            'id' => $record['id'],
            'fileName' => $record['zip_filename'],
            'totalFiles' => $record['total_files'],
            'importedFiles' => $record['imported_files'],
            'failedFiles' => $record['failed_files'],
            'status' => $record['status'],
            'importStartedAt' => $record['import_started_at'],
            'importCompletedAt' => $record['import_completed_at'],
            'albumId' => $record['album_id'],
            'albumTitle' => $record['album_title'],
            'coverImagePath' => $record['cover_image_path']
        ];
    }

    echo json_encode([
        'success' => true,
        'uploading' => [
            'files' => $uploadingFiles,
            'count' => count($uploadingFiles)
        ],
        'imported' => [
            'files' => $importedFiles,
            'count' => count($importedFiles)
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * バイトサイズをフォーマット
 */
function formatBytes($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i) * 100) / 100 . ' ' . $sizes[$i];
}
?>
