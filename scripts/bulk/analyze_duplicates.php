#!/usr/bin/env php
<?php
/**
 * KidSnaps Growth Album - 重複データ分析スクリプト
 *
 * データベース内の重複パターンを詳しく分析します
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

try {
    $pdo = getDbConnection();

    echo Colors::$BOLD . Colors::$CYAN . "重複データ分析" . Colors::$RESET . "\n";
    echo str_repeat('=', 80) . "\n\n";

    // 総件数
    $totalCount = $pdo->query("SELECT COUNT(*) FROM media_files")->fetchColumn();
    echo "総件数: {$totalCount}件\n\n";

    // パターン1: ファイル名 + ファイルサイズでの重複
    echo Colors::$YELLOW . "【パターン1】ファイル名 + ファイルサイズでの重複" . Colors::$RESET . "\n";
    echo str_repeat('-', 80) . "\n";

    $sql1 = "SELECT filename, file_size, COUNT(*) as count
             FROM media_files
             GROUP BY filename, file_size
             HAVING count > 1
             ORDER BY count DESC
             LIMIT 10";
    $stmt1 = $pdo->query($sql1);
    $duplicates1 = $stmt1->fetchAll();

    if (empty($duplicates1)) {
        echo Colors::$GREEN . "重複なし" . Colors::$RESET . "\n\n";
    } else {
        foreach ($duplicates1 as $dup) {
            echo sprintf(
                "ファイル名: %-40s サイズ: %10s KB 件数: %s件\n",
                $dup['filename'],
                number_format($dup['file_size'] / 1024, 2),
                Colors::$RED . $dup['count'] . Colors::$RESET
            );

            // 詳細を表示
            $detailSql = "SELECT id, stored_filename, file_path, upload_date
                          FROM media_files
                          WHERE filename = :filename AND file_size = :filesize
                          ORDER BY upload_date DESC";
            $detailStmt = $pdo->prepare($detailSql);
            $detailStmt->execute([':filename' => $dup['filename'], ':filesize' => $dup['file_size']]);
            $details = $detailStmt->fetchAll();

            foreach ($details as $detail) {
                $fileExists = file_exists($detail['file_path']) ? '✓' : '✗';
                echo sprintf(
                    "  ID:%-4d %s stored: %-40s date: %s\n",
                    $detail['id'],
                    $fileExists,
                    substr($detail['stored_filename'], 0, 40),
                    $detail['upload_date']
                );
            }
            echo "\n";
        }

        $totalDup1 = $pdo->query(
            "SELECT SUM(cnt - 1) as total FROM (
                SELECT COUNT(*) as cnt
                FROM media_files
                GROUP BY filename, file_size
                HAVING cnt > 1
            ) as t"
        )->fetchColumn();
        echo "このパターンでの重複総数: " . Colors::$RED . $totalDup1 . "件" . Colors::$RESET . "\n\n";
    }

    // パターン2: EXIF撮影日時 + ファイルサイズでの重複
    echo Colors::$YELLOW . "【パターン2】EXIF撮影日時 + ファイルサイズでの重複（写真のみ）" . Colors::$RESET . "\n";
    echo str_repeat('-', 80) . "\n";

    $sql2 = "SELECT exif_datetime, file_size, COUNT(*) as count
             FROM media_files
             WHERE file_type = 'image' AND exif_datetime IS NOT NULL
             GROUP BY exif_datetime, file_size
             HAVING count > 1
             ORDER BY count DESC
             LIMIT 10";
    $stmt2 = $pdo->query($sql2);
    $duplicates2 = $stmt2->fetchAll();

    if (empty($duplicates2)) {
        echo Colors::$GREEN . "重複なし" . Colors::$RESET . "\n\n";
    } else {
        foreach ($duplicates2 as $dup) {
            echo sprintf(
                "撮影日時: %-20s サイズ: %10s KB 件数: %s件\n",
                $dup['exif_datetime'],
                number_format($dup['file_size'] / 1024, 2),
                Colors::$RED . $dup['count'] . Colors::$RESET
            );

            // 詳細を表示
            $detailSql = "SELECT id, filename, stored_filename, upload_date
                          FROM media_files
                          WHERE exif_datetime = :exif_datetime AND file_size = :filesize
                          ORDER BY upload_date DESC";
            $detailStmt = $pdo->prepare($detailSql);
            $detailStmt->execute([':exif_datetime' => $dup['exif_datetime'], ':filesize' => $dup['file_size']]);
            $details = $detailStmt->fetchAll();

            foreach ($details as $detail) {
                echo sprintf(
                    "  ID:%-4d filename: %-30s date: %s\n",
                    $detail['id'],
                    substr($detail['filename'], 0, 30),
                    $detail['upload_date']
                );
            }
            echo "\n";
        }

        $totalDup2 = $pdo->query(
            "SELECT SUM(cnt - 1) as total FROM (
                SELECT COUNT(*) as cnt
                FROM media_files
                WHERE file_type = 'image' AND exif_datetime IS NOT NULL
                GROUP BY exif_datetime, file_size
                HAVING cnt > 1
            ) as t"
        )->fetchColumn();
        echo "このパターンでの重複総数: " . Colors::$RED . ($totalDup2 ?? 0) . "件" . Colors::$RESET . "\n\n";
    }

    // パターン3: ファイルハッシュでの重複（実際のファイル内容が同じ）
    echo Colors::$YELLOW . "【パターン3】ファイル内容のハッシュ値での重複チェック（サンプル10件）" . Colors::$RESET . "\n";
    echo str_repeat('-', 80) . "\n";
    echo "ファイルのMD5ハッシュを計算中...\n";

    $allFiles = $pdo->query("SELECT id, file_path, filename, file_size FROM media_files ORDER BY id")->fetchAll();
    $hashMap = [];
    $processed = 0;
    $maxProcess = 100; // 処理するファイル数を制限

    foreach ($allFiles as $file) {
        if ($processed >= $maxProcess) break;

        if (file_exists($file['file_path'])) {
            $hash = md5_file($file['file_path']);
            if (!isset($hashMap[$hash])) {
                $hashMap[$hash] = [];
            }
            $hashMap[$hash][] = $file;
            $processed++;
        }
    }

    $hashDuplicates = array_filter($hashMap, function($files) {
        return count($files) > 1;
    });

    if (empty($hashDuplicates)) {
        echo Colors::$GREEN . "ハッシュ重複なし（サンプル{$maxProcess}件中）" . Colors::$RESET . "\n\n";
    } else {
        $displayCount = 0;
        foreach ($hashDuplicates as $hash => $files) {
            if ($displayCount >= 5) break;

            echo "ハッシュ: {$hash} - " . Colors::$RED . count($files) . "件" . Colors::$RESET . "\n";
            foreach ($files as $file) {
                echo sprintf("  ID:%-4d %-40s (%s KB)\n",
                    $file['id'],
                    substr($file['filename'], 0, 40),
                    number_format($file['file_size'] / 1024, 2)
                );
            }
            echo "\n";
            $displayCount++;
        }

        $totalHashDup = array_sum(array_map(function($files) {
            return count($files) - 1;
        }, $hashDuplicates));
        echo "ハッシュ重複総数（サンプル中）: " . Colors::$RED . $totalHashDup . "件" . Colors::$RESET . "\n\n";
    }

    // サマリー
    echo str_repeat('=', 80) . "\n";
    echo Colors::$BOLD . "推奨される削除方法" . Colors::$RESET . "\n";
    echo str_repeat('=', 80) . "\n";

    if (!empty($duplicates1)) {
        echo "1. " . Colors::$CYAN . "php remove_duplicates.php --method=filename" . Colors::$RESET . "\n";
        echo "   → ファイル名+サイズで重複削除（最も一般的）\n\n";
    }

    if (!empty($duplicates2)) {
        echo "2. " . Colors::$CYAN . "php remove_duplicates.php --method=exif" . Colors::$RESET . "\n";
        echo "   → EXIF撮影日時+サイズで重複削除（写真の場合より正確）\n\n";
    }

    if (!empty($hashDuplicates)) {
        echo "3. " . Colors::$CYAN . "php remove_duplicates.php --method=hash" . Colors::$RESET . "\n";
        echo "   → ファイル内容のハッシュで重複削除（最も確実だが時間がかかる）\n\n";
    }

} catch (Exception $e) {
    echo "\n" . Colors::$RED . Colors::$BOLD . "エラー: " . $e->getMessage() . Colors::$RESET . "\n";
    exit(1);
}
