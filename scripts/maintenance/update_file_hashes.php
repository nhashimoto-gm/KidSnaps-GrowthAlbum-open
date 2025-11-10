#!/usr/bin/env php
<?php
/**
 * KidSnaps Growth Album - ファイルハッシュ更新スクリプト
 *
 * 既存のメディアファイルのMD5ハッシュを計算してデータベースに保存します
 *
 * 使用方法:
 *   php update_file_hashes.php [オプション]
 *
 * オプション:
 *   --force    既にハッシュが設定されているファイルも再計算
 *   --help     ヘルプを表示
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
    echo Colors::$BOLD . "KidSnaps Growth Album - ファイルハッシュ更新スクリプト" . Colors::$RESET . "\n\n";
    echo "使用方法:\n";
    echo "  php update_file_hashes.php [オプション]\n\n";
    echo "オプション:\n";
    echo "  --force    既にハッシュが設定されているファイルも再計算\n";
    echo "  --help     このヘルプを表示\n\n";
}

/**
 * 進捗バーを表示
 */
function showProgress($current, $total, $message = '') {
    $barWidth = 50;
    $progress = $total > 0 ? ($current / $total) : 0;
    $bar = str_repeat('=', (int)($barWidth * $progress));
    $space = str_repeat(' ', $barWidth - strlen($bar));
    $percent = number_format($progress * 100, 1);

    echo "\r" . Colors::$CYAN . "[{$bar}{$space}]" . Colors::$RESET . " {$percent}% ({$current}/{$total}) {$message}";

    if ($current >= $total) {
        echo "\n";
    }
}

try {
    // コマンドライン引数を解析
    $force = false;
    $help = false;

    for ($i = 1; $i < $argc; $i++) {
        $arg = $argv[$i];
        if ($arg === '--force') {
            $force = true;
        } elseif ($arg === '--help') {
            $help = true;
        }
    }

    // ヘルプ表示
    if ($help) {
        showHelp();
        exit(0);
    }

    echo Colors::$BOLD . Colors::$CYAN . "KidSnaps Growth Album - ファイルハッシュ更新" . Colors::$RESET . "\n";
    echo str_repeat('=', 60) . "\n\n";

    // データベース接続
    $pdo = getDbConnection();

    // file_hashカラムが存在するか確認
    $checkColumnSql = "SHOW COLUMNS FROM media_files LIKE 'file_hash'";
    $columnExists = $pdo->query($checkColumnSql)->rowCount() > 0;

    if (!$columnExists) {
        echo Colors::$RED . "エラー: file_hashカラムが存在しません。" . Colors::$RESET . "\n";
        echo "以下のSQLを実行してください:\n";
        echo Colors::$YELLOW . "ALTER TABLE media_files ADD COLUMN file_hash VARCHAR(32) NULL AFTER file_size, ADD INDEX idx_file_hash (file_hash);" . Colors::$RESET . "\n";
        exit(1);
    }

    // 処理対象のファイルを取得
    $sql = "SELECT id, file_path, filename FROM media_files";
    if (!$force) {
        $sql .= " WHERE file_hash IS NULL";
    }
    $sql .= " ORDER BY id";

    $files = $pdo->query($sql)->fetchAll();
    $totalFiles = count($files);

    if ($totalFiles === 0) {
        echo Colors::$GREEN . "更新対象のファイルはありません。" . Colors::$RESET . "\n";
        if (!$force) {
            echo Colors::$YELLOW . "すべてのファイルを再計算する場合は --force オプションを使用してください。" . Colors::$RESET . "\n";
        }
        exit(0);
    }

    echo "更新対象ファイル: " . Colors::$CYAN . $totalFiles . "件" . Colors::$RESET . "\n";
    if ($force) {
        echo Colors::$YELLOW . "強制モード: 既存のハッシュも再計算します" . Colors::$RESET . "\n";
    }
    echo "\n";

    echo Colors::$YELLOW . "ハッシュ計算を開始します..." . Colors::$RESET . "\n\n";

    $successCount = 0;
    $errorCount = 0;
    $missingCount = 0;

    foreach ($files as $index => $file) {
        $current = $index + 1;
        showProgress($current, $totalFiles, substr($file['filename'], 0, 30));

        if (!file_exists($file['file_path'])) {
            $missingCount++;
            continue;
        }

        try {
            $hash = md5_file($file['file_path']);

            $updateSql = "UPDATE media_files SET file_hash = :hash WHERE id = :id";
            $stmt = $pdo->prepare($updateSql);
            $stmt->execute([':hash' => $hash, ':id' => $file['id']]);

            $successCount++;
        } catch (Exception $e) {
            $errorCount++;
            error_log("ハッシュ計算エラー (ID: {$file['id']}): " . $e->getMessage());
        }
    }

    // 結果サマリーを表示
    echo "\n" . str_repeat('=', 60) . "\n";
    echo Colors::$BOLD . "更新結果" . Colors::$RESET . "\n";
    echo str_repeat('=', 60) . "\n";
    echo Colors::$GREEN . "成功:           " . $successCount . "件" . Colors::$RESET . "\n";
    echo Colors::$YELLOW . "ファイル不在:   " . $missingCount . "件" . Colors::$RESET . "\n";
    echo Colors::$RED . "エラー:         " . $errorCount . "件" . Colors::$RESET . "\n";
    echo str_repeat('=', 60) . "\n";

    echo "\n" . Colors::$GREEN . Colors::$BOLD . "処理が完了しました！" . Colors::$RESET . "\n";

} catch (Exception $e) {
    echo "\n" . Colors::$RED . Colors::$BOLD . "致命的なエラー: " . $e->getMessage() . Colors::$RESET . "\n";
    error_log("Update file hashes error: " . $e->getMessage());
    exit(1);
}
