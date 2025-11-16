<?php
/**
 * KidSnaps Growth Album - HEICサムネイル修正スクリプト
 * thumbnail_pathがNULLのHEICファイルに対して、file_pathをthumbnail_pathにコピー
 *
 * 使用方法:
 * php fix_heic_thumbnails.php
 */

set_time_limit(300);
ini_set('max_execution_time', '300');
ini_set('memory_limit', '512M');

// エラー表示設定
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 必要なファイルを読み込み
require_once __DIR__ . '/../../config/database.php';

// ログ出力
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[{$timestamp}] {$message}\n";
    error_log("[FIX_HEIC] {$message}");
}

logMessage("=== HEICサムネイル修正スクリプト開始 ===");

try {
    // データベース接続
    $pdo = getDbConnection();

    // thumbnail_pathがNULLのHEIC画像ファイルを検索
    $sql = "SELECT id, filename, file_path, mime_type
            FROM media_files
            WHERE file_type = 'image'
            AND thumbnail_path IS NULL
            AND (filename LIKE '%.heic' OR filename LIKE '%.HEIC' OR filename LIKE '%.heif' OR filename LIKE '%.HEIF'
                 OR mime_type IN ('image/heic', 'image/heif', 'application/octet-stream'))
            ORDER BY upload_date DESC";

    $stmt = $pdo->query($sql);
    $heicFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalCount = count($heicFiles);
    logMessage("対象HEICファイル: {$totalCount}件");

    if ($totalCount === 0) {
        logMessage("修正対象のファイルがありません。");
        exit(0);
    }

    $successCount = 0;
    $failedCount = 0;

    foreach ($heicFiles as $file) {
        $fileId = $file['id'];
        $fileName = $file['filename'];
        $filePath = $file['file_path'];

        logMessage("処理中: {$fileName} (ID: {$fileId})");

        // file_pathをthumbnail_pathにコピー
        $updateSql = "UPDATE media_files
                      SET thumbnail_path = :thumbnail_path
                      WHERE id = :id";
        $updateStmt = $pdo->prepare($updateSql);

        try {
            $updateStmt->execute([
                ':thumbnail_path' => $filePath,
                ':id' => $fileId
            ]);

            $successCount++;
            logMessage("  完了: thumbnail_path = {$filePath}");
        } catch (Exception $e) {
            logMessage("  エラー: " . $e->getMessage());
            $failedCount++;
        }
    }

    logMessage("=== 修正完了 ===");
    logMessage("対象件数: {$totalCount}件");
    logMessage("成功: {$successCount}件");
    logMessage("失敗: {$failedCount}件");
    logMessage("");
    logMessage("注意: これらのHEICファイルは、ブラウザ側でheic2anyを使用して表示されます。");

} catch (Exception $e) {
    logMessage("致命的エラー: " . $e->getMessage());
    logMessage($e->getTraceAsString());
    exit(1);
}
?>
