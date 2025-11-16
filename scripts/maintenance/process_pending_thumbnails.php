<?php
/**
 * KidSnaps Growth Album - サムネイル未生成ファイルのバッチ処理
 * ZIPインポート時にHEIC変換失敗などでサムネイルが生成できなかったファイルを処理
 *
 * 使用方法:
 * php process_pending_thumbnails.php [--limit=100]
 *
 * オプション:
 * --limit=N  : 一度に処理するファイル数（デフォルト: 50）
 */

// 実行時間を延長
set_time_limit(600); // 10分
ini_set('max_execution_time', '600');
ini_set('memory_limit', '1024M');

// エラー表示設定
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 必要なファイルを読み込み
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/heic_converter.php';
require_once __DIR__ . '/../../includes/image_thumbnail_helper.php';

// ログ出力
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[{$timestamp}] {$message}\n";
    error_log("[THUMBNAIL_BATCH] {$message}");
}

// コマンドライン引数を解析
$limit = 50; // デフォルト
foreach ($argv as $arg) {
    if (preg_match('/^--limit=(\d+)$/', $arg, $matches)) {
        $limit = (int)$matches[1];
    }
}

logMessage("=== サムネイル未生成ファイルのバッチ処理開始 ===");
logMessage("処理上限: {$limit}件");

try {
    // データベース接続
    $pdo = getDbConnection();

    // サムネイル未生成のファイルを検索
    // file_type='image' かつ thumbnail_path IS NULL
    $sql = "SELECT id, filename, stored_filename, file_path, file_type, mime_type
            FROM media_files
            WHERE file_type = 'image'
            AND thumbnail_path IS NULL
            ORDER BY upload_date DESC
            LIMIT :limit";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $pendingFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalCount = count($pendingFiles);
    logMessage("サムネイル未生成のファイル: {$totalCount}件");

    if ($totalCount === 0) {
        logMessage("処理対象のファイルがありません。");
        exit(0);
    }

    $successCount = 0;
    $failedCount = 0;

    foreach ($pendingFiles as $file) {
        $fileId = $file['id'];
        $fileName = $file['filename'];
        $filePath = __DIR__ . '/../../' . $file['file_path'];

        logMessage("処理中: {$fileName} (ID: {$fileId})");

        // ファイルが存在するか確認
        if (!file_exists($filePath)) {
            logMessage("  エラー: ファイルが見つかりません: {$filePath}");
            $failedCount++;
            continue;
        }

        // HEICファイルの場合は変換を試みる
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $isHeic = in_array($extension, ['heic', 'heif']);

        if ($isHeic) {
            logMessage("  HEICファイル検出: 変換を試行します");
            $jpegPath = preg_replace('/\.(heic|heif)$/i', '.jpg', $filePath);

            if (convertHeicToJpeg($filePath, $jpegPath)) {
                logMessage("  HEIC変換成功: {$jpegPath}");

                // データベースを更新（ファイルパスをJPEGに変更）
                @unlink($filePath);
                $filePath = $jpegPath;
                $newFileName = basename($jpegPath);
                $newRelativePath = 'uploads/images/' . $newFileName;

                $updateSql = "UPDATE media_files
                              SET filename = :filename,
                                  stored_filename = :stored_filename,
                                  file_path = :file_path,
                                  mime_type = 'image/jpeg'
                              WHERE id = :id";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([
                    ':filename' => $newFileName,
                    ':stored_filename' => $newFileName,
                    ':file_path' => $newRelativePath,
                    ':id' => $fileId
                ]);

                logMessage("  データベース更新: ファイルパスをJPEGに変更");
            } else {
                logMessage("  HEIC変換失敗: サムネイルなしで登録（フロントエンドで変換）");

                // HEIC変換失敗時は、サムネイルをnullのまま登録
                // フロントエンド（モーダル/ギャラリー）でheic2anyを使用して表示
                // 元のHEICファイルのパスを thumbnail_path に設定（代替表示用）

                $thumbnailPath = $file['file_path']; // 元のHEICファイルをサムネイルとして使用

                $updateSql = "UPDATE media_files
                              SET thumbnail_path = :thumbnail_path
                              WHERE id = :id";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([
                    ':thumbnail_path' => $thumbnailPath,
                    ':id' => $fileId
                ]);

                $successCount++;
                logMessage("  完了: HEICファイルのまま登録（クライアント側で変換）");
                continue; // サムネイル生成はスキップ
            }
        }

        // サムネイル生成
        $thumbnailDir = __DIR__ . '/../../uploads/thumbnails';
        if (!file_exists($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
        }

        $thumbnailFileName = 'thumb_' . pathinfo(basename($filePath), PATHINFO_FILENAME) . '.jpg';
        $thumbnailFullPath = $thumbnailDir . '/' . $thumbnailFileName;

        try {
            if (generateImageThumbnail($filePath, $thumbnailFullPath, 400, 85)) {
                $thumbnailPath = 'uploads/thumbnails/' . $thumbnailFileName;
                logMessage("  サムネイル生成成功: {$thumbnailPath}");

                // WebP版サムネイル
                $thumbnailWebPFileName = 'thumb_' . pathinfo(basename($filePath), PATHINFO_FILENAME) . '.webp';
                $thumbnailWebPFullPath = $thumbnailDir . '/' . $thumbnailWebPFileName;
                $thumbnailWebPPath = null;

                if (generateWebPThumbnail($filePath, $thumbnailWebPFullPath, 400, 85)) {
                    $thumbnailWebPPath = 'uploads/thumbnails/' . $thumbnailWebPFileName;
                    logMessage("  WebPサムネイル生成成功: {$thumbnailWebPPath}");
                }

                // データベースを更新
                $updateSql = "UPDATE media_files
                              SET thumbnail_path = :thumbnail_path,
                                  thumbnail_webp_path = :thumbnail_webp_path
                              WHERE id = :id";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([
                    ':thumbnail_path' => $thumbnailPath,
                    ':thumbnail_webp_path' => $thumbnailWebPPath,
                    ':id' => $fileId
                ]);

                $successCount++;
                logMessage("  完了: {$fileName}");
            } else {
                logMessage("  エラー: サムネイル生成に失敗しました");
                $failedCount++;
            }
        } catch (Exception $e) {
            logMessage("  エラー: " . $e->getMessage());
            $failedCount++;
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
