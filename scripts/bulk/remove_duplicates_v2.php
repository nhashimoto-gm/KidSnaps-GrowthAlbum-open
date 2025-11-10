#!/usr/bin/env php
<?php
/**
 * KidSnaps Growth Album - 重複データ削除スクリプト v2
 *
 * 複数の方法で重複を検出・削除します
 *
 * 使用方法:
 *   php remove_duplicates_v2.php [オプション]
 *
 * オプション:
 *   --method=<方法>   重複検出方法を指定
 *                     filename: ファイル名+サイズ（デフォルト）
 *                     exif: EXIF撮影日時+サイズ
 *                     hash: ファイル内容のMD5ハッシュ
 *   --dry-run         実際には削除せず、削除対象のリストのみ表示
 *   --help            ヘルプを表示
 */

// エラー表示設定
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// CLIからの実行のみ許可
if (php_sapi_name() !== 'cli') {
    die("このスクリプトはコマンドラインからのみ実行できます。\n");
}

require_once __DIR__ . '/../../config/database.php';

// カラー出力用のANSIカラーコード
class Colors {
    public static $RESET = "\033[0m";
    public static $RED = "\033[31m";
    public static $GREEN = "\033[32m";
    public static $YELLOW = "\033[33m";
    public static $BLUE = "\033[34m";
    public static $MAGENTA = "\033[35m";
    public static $CYAN = "\033[36m";
    public static $WHITE = "\033[37m";
    public static $BOLD = "\033[1m";
}

/**
 * ヘルプメッセージを表示
 */
function showHelp() {
    echo Colors::$BOLD . "KidSnaps Growth Album - 重複データ削除スクリプト v2" . Colors::$RESET . "\n\n";
    echo "使用方法:\n";
    echo "  php remove_duplicates_v2.php [オプション]\n\n";
    echo "オプション:\n";
    echo "  --method=<方法>   重複検出方法を指定\n";
    echo "                    filename: ファイル名+サイズ（デフォルト）\n";
    echo "                    exif: EXIF撮影日時+サイズ（写真のみ）\n";
    echo "                    hash: ファイル内容のMD5ハッシュ（最も正確）\n";
    echo "  --dry-run         実際には削除せず、削除対象のリストのみ表示\n";
    echo "  --help            このヘルプを表示\n\n";
    echo "例:\n";
    echo "  php remove_duplicates_v2.php --method=filename --dry-run\n";
    echo "  php remove_duplicates_v2.php --method=hash\n\n";
}

/**
 * 方法1: ファイル名+サイズで重複を検出
 */
function findDuplicatesByFilename($pdo) {
    $sql = "SELECT filename, file_size, COUNT(*) as count
            FROM media_files
            GROUP BY filename, file_size
            HAVING count > 1
            ORDER BY count DESC";
    return $pdo->query($sql)->fetchAll();
}

/**
 * 方法2: EXIF撮影日時+サイズで重複を検出
 */
function findDuplicatesByExif($pdo) {
    $sql = "SELECT exif_datetime, file_size, COUNT(*) as count
            FROM media_files
            WHERE file_type = 'image' AND exif_datetime IS NOT NULL
            GROUP BY exif_datetime, file_size
            HAVING count > 1
            ORDER BY count DESC";
    return $pdo->query($sql)->fetchAll();
}

/**
 * 方法3: ファイルハッシュで重複を検出
 */
function findDuplicatesByHash($pdo) {
    echo Colors::$YELLOW . "ファイルのハッシュを計算中..." . Colors::$RESET . "\n";

    $allFiles = $pdo->query("SELECT id, file_path, filename, file_size, upload_date FROM media_files ORDER BY id")->fetchAll();
    $hashMap = [];
    $total = count($allFiles);
    $processed = 0;

    foreach ($allFiles as $file) {
        $processed++;
        if ($processed % 10 == 0 || $processed == $total) {
            echo "\r進捗: {$processed}/{$total} (" . round($processed/$total*100, 1) . "%)";
        }

        if (file_exists($file['file_path'])) {
            $hash = md5_file($file['file_path']);
            if (!isset($hashMap[$hash])) {
                $hashMap[$hash] = [];
            }
            $hashMap[$hash][] = $file;
        }
    }
    echo "\n";

    // 重複のみを抽出
    $duplicates = [];
    foreach ($hashMap as $hash => $files) {
        if (count($files) > 1) {
            $duplicates[] = [
                'hash' => $hash,
                'count' => count($files),
                'files' => $files
            ];
        }
    }

    return $duplicates;
}

/**
 * ファイル名+サイズでの重複レコードを取得
 */
function getRecordsByFilename($pdo, $filename, $filesize) {
    $sql = "SELECT id, filename, file_path, thumbnail_path, upload_date, file_type
            FROM media_files
            WHERE filename = :filename AND file_size = :filesize
            ORDER BY upload_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':filename' => $filename, ':filesize' => $filesize]);
    return $stmt->fetchAll();
}

/**
 * EXIF+サイズでの重複レコードを取得
 */
function getRecordsByExif($pdo, $exifDatetime, $filesize) {
    $sql = "SELECT id, filename, file_path, thumbnail_path, upload_date, file_type
            FROM media_files
            WHERE exif_datetime = :exif_datetime AND file_size = :filesize
            ORDER BY upload_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':exif_datetime' => $exifDatetime, ':filesize' => $filesize]);
    return $stmt->fetchAll();
}

/**
 * レコードを削除（ファイルも削除）
 */
function deleteRecord($pdo, $record, $dryRun = false) {
    $id = $record['id'];
    $filePath = $record['file_path'];
    $thumbnailPath = $record['thumbnail_path'];

    if ($dryRun) {
        echo "  [DRY RUN] ID:{$id} を削除予定\n";
        if (file_exists($filePath)) {
            echo "    - ファイル削除予定: {$filePath}\n";
        }
        if (!empty($thumbnailPath) && file_exists($thumbnailPath)) {
            echo "    - サムネイル削除予定: {$thumbnailPath}\n";
        }
        return true;
    }

    try {
        // データベースから削除
        $sql = "DELETE FROM media_files WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        // ファイルを削除
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // サムネイルを削除
        if (!empty($thumbnailPath) && file_exists($thumbnailPath)) {
            unlink($thumbnailPath);
        }

        return true;
    } catch (Exception $e) {
        error_log("削除エラー (ID:{$id}): " . $e->getMessage());
        return false;
    }
}

// メイン処理
try {
    // コマンドライン引数を解析
    $method = 'filename';
    $dryRun = false;
    $help = false;

    for ($i = 1; $i < $argc; $i++) {
        $arg = $argv[$i];
        if ($arg === '--dry-run') {
            $dryRun = true;
        } elseif ($arg === '--help') {
            $help = true;
        } elseif (strpos($arg, '--method=') === 0) {
            $method = substr($arg, 9);
        }
    }

    // ヘルプ表示
    if ($help) {
        showHelp();
        exit(0);
    }

    // 方法の検証
    if (!in_array($method, ['filename', 'exif', 'hash'])) {
        echo Colors::$RED . "エラー: 無効な方法: {$method}" . Colors::$RESET . "\n";
        echo "使用可能な方法: filename, exif, hash\n";
        exit(1);
    }

    echo Colors::$BOLD . Colors::$CYAN . "KidSnaps Growth Album - 重複データ削除 v2" . Colors::$RESET . "\n";
    echo str_repeat('=', 60) . "\n\n";

    echo "検出方法: " . Colors::$CYAN . $method . Colors::$RESET . "\n";
    if ($dryRun) {
        echo Colors::$YELLOW . "【ドライランモード】実際の削除は行いません" . Colors::$RESET . "\n";
    }
    echo "\n";

    // データベース接続
    $pdo = getDbConnection();

    // 総件数を取得
    $totalSql = "SELECT COUNT(*) FROM media_files";
    $totalCount = $pdo->query($totalSql)->fetchColumn();
    echo "データベース総件数: " . Colors::$CYAN . $totalCount . "件" . Colors::$RESET . "\n\n";

    // 重複を検出
    echo Colors::$YELLOW . "重複を検出中..." . Colors::$RESET . "\n";

    $duplicates = [];
    if ($method === 'filename') {
        $duplicates = findDuplicatesByFilename($pdo);
    } elseif ($method === 'exif') {
        $duplicates = findDuplicatesByExif($pdo);
    } elseif ($method === 'hash') {
        $duplicates = findDuplicatesByHash($pdo);
    }

    if (empty($duplicates)) {
        echo Colors::$GREEN . "重複データは見つかりませんでした。" . Colors::$RESET . "\n";
        exit(0);
    }

    $duplicateGroupCount = count($duplicates);
    echo Colors::$YELLOW . "重複グループ: {$duplicateGroupCount}件" . Colors::$RESET . "\n\n";

    // ログファイルを作成
    $logFile = __DIR__ . '/remove_duplicates_log_' . date('YmdHis') . '.txt';
    $logHandle = fopen($logFile, 'w');
    fwrite($logHandle, "=== Remove Duplicates Log v2 ===\n");
    fwrite($logHandle, "実行時刻: " . date('Y-m-d H:i:s') . "\n");
    fwrite($logHandle, "検出方法: {$method}\n");
    fwrite($logHandle, "モード: " . ($dryRun ? "DRY RUN" : "実行") . "\n");
    fwrite($logHandle, str_repeat('=', 80) . "\n\n");

    // 削除対象を収集（まだ削除しない）
    $deleteTargets = [];
    $totalDuplicates = 0;

    // 重複の詳細を表示（削除はしない）
    echo str_repeat('-', 60) . "\n";
    echo Colors::$YELLOW . "削除対象の確認:" . Colors::$RESET . "\n\n";

    if ($method === 'hash') {
        // ハッシュ方式の場合
        foreach ($duplicates as $dup) {
            $hash = $dup['hash'];
            $files = $dup['files'];
            $duplicateCount = count($files) - 1;
            $totalDuplicates += $duplicateCount;

            // 最新のレコードを残す
            usort($files, function($a, $b) {
                return strtotime($b['upload_date']) - strtotime($a['upload_date']);
            });

            $keepRecord = array_shift($files);

            if ($dryRun || count($deleteTargets) < 10) {
                echo Colors::$CYAN . "ハッシュ: " . substr($hash, 0, 16) . "..." . Colors::$RESET . "\n";
                echo "  " . Colors::$GREEN . "✓ 保持: ID:{$keepRecord['id']} {$keepRecord['filename']}" . Colors::$RESET . "\n";
                foreach ($files as $file) {
                    echo "  " . Colors::$RED . "✗ 削除: ID:{$file['id']} {$file['filename']}" . Colors::$RESET . "\n";
                }
                echo "\n";
            }

            // 削除対象に追加
            foreach ($files as $file) {
                $deleteTargets[] = ['record' => $file, 'key' => "Hash: {$hash}", 'keep' => $keepRecord];
            }
        }
    } else {
        // ファイル名またはEXIF方式の場合
        foreach ($duplicates as $dup) {
            $duplicateCount = $dup['count'] - 1;
            $totalDuplicates += $duplicateCount;

            if ($method === 'filename') {
                $key = $dup['filename'] . " (" . number_format($dup['file_size'] / 1024, 2) . " KB)";
                $records = getRecordsByFilename($pdo, $dup['filename'], $dup['file_size']);
            } else {
                $key = $dup['exif_datetime'] . " (" . number_format($dup['file_size'] / 1024, 2) . " KB)";
                $records = getRecordsByExif($pdo, $dup['exif_datetime'], $dup['file_size']);
            }

            $keepRecord = array_shift($records);

            if ($dryRun || count($deleteTargets) < 20) {
                echo Colors::$CYAN . "{$key}" . Colors::$RESET . "\n";
                echo "  " . Colors::$GREEN . "✓ 保持: ID:{$keepRecord['id']}" . Colors::$RESET . "\n";
                foreach ($records as $record) {
                    echo "  " . Colors::$RED . "✗ 削除: ID:{$record['id']}" . Colors::$RESET . "\n";
                }
                echo "\n";
            }

            // 削除対象に追加
            foreach ($records as $record) {
                $deleteTargets[] = ['record' => $record, 'key' => $key, 'keep' => $keepRecord];
            }
        }
    }

    if (count($deleteTargets) > 20 && !$dryRun) {
        echo Colors::$YELLOW . "... 他 " . (count($deleteTargets) - 20) . " 件\n\n" . Colors::$RESET;
    }

    echo str_repeat('-', 60) . "\n";
    echo "削除対象レコード数: " . Colors::$RED . $totalDuplicates . "件" . Colors::$RESET . "\n";
    echo "削除後の件数: " . Colors::$GREEN . ($totalCount - $totalDuplicates) . "件" . Colors::$RESET . "\n\n";

    // 確認プロンプト（ドライランモードでない場合）
    if (!$dryRun && $totalDuplicates > 0) {
        echo Colors::$YELLOW . "本当に削除しますか？ (yes/no): " . Colors::$RESET;
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);

        if (strtolower($line) !== 'yes') {
            echo Colors::$CYAN . "削除をキャンセルしました。" . Colors::$RESET . "\n";
            fclose($logHandle);
            unlink($logFile);
            exit(0);
        }
        echo "\n";
    }

    // ドライランの場合はここで終了
    if ($dryRun) {
        fwrite($logHandle, "\n[DRY RUN] 実際の削除は行いませんでした\n");
        fwrite($logHandle, "削除対象: {$totalDuplicates}件\n");
        fclose($logHandle);
        echo Colors::$GREEN . "\nドライラン完了。削除は実行されていません。" . Colors::$RESET . "\n";
        echo Colors::$CYAN . "詳細ログ: {$logFile}" . Colors::$RESET . "\n";
        exit(0);
    }

    // 実際の削除処理を実行
    echo Colors::$YELLOW . "削除処理を開始します..." . Colors::$RESET . "\n\n";

    $deletedCount = 0;
    $failedCount = 0;
    $deletedIds = [];

    foreach ($deleteTargets as $target) {
        $record = $target['record'];
        $key = $target['key'];
        $keep = $target['keep'];

        fwrite($logHandle, "\n{$key}\n");
        fwrite($logHandle, "  保持: ID:{$keep['id']}\n");

        if (deleteRecord($pdo, $record, false)) {
            $deletedCount++;
            $deletedIds[] = $record['id'];
            fwrite($logHandle, "  削除: ID:{$record['id']}\n");
        } else {
            $failedCount++;
            fwrite($logHandle, "  削除失敗: ID:{$record['id']}\n");
        }
    }

    echo "\n" . Colors::$GREEN . "削除処理が完了しました。" . Colors::$RESET . "\n";

    // ログファイルに結果サマリーを書き込む
    fwrite($logHandle, "\n" . str_repeat('=', 80) . "\n");
    fwrite($logHandle, "=== 削除結果 ===\n");
    fwrite($logHandle, "削除成功: {$deletedCount}件\n");
    fwrite($logHandle, "削除失敗: {$failedCount}件\n");
    if (!empty($deletedIds)) {
        fwrite($logHandle, "\n削除されたID一覧:\n");
        fwrite($logHandle, implode(', ', $deletedIds) . "\n");
    }
    fwrite($logHandle, "\n終了時刻: " . date('Y-m-d H:i:s') . "\n");
    fclose($logHandle);

    // 結果サマリーを表示
    echo "\n" . str_repeat('=', 60) . "\n";
    echo Colors::$BOLD . "削除結果" . Colors::$RESET . "\n";
    echo str_repeat('=', 60) . "\n";
    echo Colors::$GREEN . "削除成功: " . $deletedCount . "件" . Colors::$RESET . "\n";
    if ($failedCount > 0) {
        echo Colors::$RED . "削除失敗: " . $failedCount . "件" . Colors::$RESET . "\n";
    }
    echo str_repeat('=', 60) . "\n";

    // 削除後の総件数を取得
    $newTotalCount = $pdo->query($totalSql)->fetchColumn();
    echo "\n削除後のデータベース総件数: " . Colors::$CYAN . $newTotalCount . "件" . Colors::$RESET . "\n";

    echo "\n" . Colors::$CYAN . "詳細ログ: {$logFile}" . Colors::$RESET . "\n";
    echo "\n" . Colors::$GREEN . Colors::$BOLD . "処理が完了しました！" . Colors::$RESET . "\n";

} catch (Exception $e) {
    echo "\n" . Colors::$RED . Colors::$BOLD . "致命的なエラー: " . $e->getMessage() . Colors::$RESET . "\n";
    error_log("Remove duplicates error: " . $e->getMessage());
    exit(1);
}
