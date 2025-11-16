<?php
/**
 * KidSnaps Growth Album - 位置情報名未取得ファイルのバッチ処理
 * ZIPインポート時にスキップした位置情報名（リバースジオコーディング）を取得
 *
 * 使用方法:
 * php process_pending_geocoding.php [--limit=100]
 *
 * オプション:
 * --limit=N  : 一度に処理するファイル数（デフォルト: 50）
 *
 * 注意:
 * - Nominatim APIのレート制限により、1リクエスト/秒で処理します
 * - 100件処理する場合、約100秒かかります
 */

// 実行時間を延長
set_time_limit(600); // 10分
ini_set('max_execution_time', '600');
ini_set('memory_limit', '512M');

// エラー表示設定
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 必要なファイルを読み込み
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/exif_helper.php';

// ログ出力
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[{$timestamp}] {$message}\n";
    error_log("[GEOCODING_BATCH] {$message}");
}

// コマンドライン引数を解析
$limit = 50; // デフォルト
foreach ($argv as $arg) {
    if (preg_match('/^--limit=(\d+)$/', $arg, $matches)) {
        $limit = (int)$matches[1];
    }
}

logMessage("=== 位置情報名未取得ファイルのバッチ処理開始 ===");
logMessage("処理上限: {$limit}件");
logMessage("レート制限: 1リクエスト/秒（Nominatim API）");

try {
    // データベース接続
    $pdo = getDbConnection();

    // 位置情報名未取得のファイルを検索
    // exif_latitude IS NOT NULL AND exif_longitude IS NOT NULL
    // AND (exif_location_name IS NULL OR exif_location_name = '')
    $sql = "SELECT id, filename, exif_latitude, exif_longitude
            FROM media_files
            WHERE exif_latitude IS NOT NULL
            AND exif_longitude IS NOT NULL
            AND (exif_location_name IS NULL OR exif_location_name = '')
            ORDER BY upload_date DESC
            LIMIT :limit";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $pendingFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalCount = count($pendingFiles);
    logMessage("位置情報名未取得のファイル: {$totalCount}件");

    if ($totalCount === 0) {
        logMessage("処理対象のファイルがありません。");
        exit(0);
    }

    logMessage("推定処理時間: 約{$totalCount}秒");

    $successCount = 0;
    $failedCount = 0;

    foreach ($pendingFiles as $index => $file) {
        $fileId = $file['id'];
        $fileName = $file['filename'];
        $latitude = $file['exif_latitude'];
        $longitude = $file['exif_longitude'];

        $progress = $index + 1;
        logMessage("[{$progress}/{$totalCount}] 処理中: {$fileName} (緯度: {$latitude}, 経度: {$longitude})");

        try {
            // レート制限を適用（1秒/リクエスト）
            applyRateLimitForGeocoding();

            // 位置情報名を取得
            $locationName = getLocationName($latitude, $longitude);

            if ($locationName) {
                logMessage("  位置情報名取得成功: {$locationName}");

                // データベースを更新
                $updateSql = "UPDATE media_files
                              SET exif_location_name = :location_name
                              WHERE id = :id";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([
                    ':location_name' => $locationName,
                    ':id' => $fileId
                ]);

                $successCount++;
                logMessage("  完了: {$fileName}");
            } else {
                logMessage("  警告: 位置情報名を取得できませんでした");
                $failedCount++;
            }
        } catch (Exception $e) {
            logMessage("  エラー: " . $e->getMessage());
            $failedCount++;

            // APIエラーの場合は少し待機してから継続
            if (strpos($e->getMessage(), 'HTTP') !== false) {
                logMessage("  APIエラー検出。5秒待機します...");
                sleep(5);
            }
        }
    }

    logMessage("=== バッチ処理完了 ===");
    logMessage("処理件数: {$totalCount}件");
    logMessage("成功: {$successCount}件");
    logMessage("失敗: {$failedCount}件");

} catch (Exception $e) {
    logMessage("致命的エラー: " . $e->getMessage());
    logMessage($e->getTraceAsString());
    exit(1);
}
?>
