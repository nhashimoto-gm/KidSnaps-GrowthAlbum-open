#!/usr/bin/env php
<?php
/**
 * KidSnaps Growth Album - サムネイル再生成スクリプト
 *
 * 動画のサムネイルを再生成します
 * - サムネイルが存在しない動画
 * - サムネイルファイルが実際に存在しない動画
 * - すべての動画（--forceオプション）
 *
 * 使用方法:
 *   php regenerate_thumbnails.php [オプション]
 *
 * オプション:
 *   --all      すべての動画のサムネイルを再生成
 *   --missing  サムネイルが存在しない動画のみ（デフォルト）
 *   --force    既存のサムネイルも強制的に再生成
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
    echo Colors::$BOLD . "KidSnaps Growth Album - サムネイル再生成スクリプト" . Colors::$RESET . "\n\n";
    echo "使用方法:\n";
    echo "  php regenerate_thumbnails.php [オプション]\n\n";
    echo "オプション:\n";
    echo "  --all      すべての動画のサムネイルを対象\n";
    echo "  --missing  サムネイルが存在しない動画のみ（デフォルト）\n";
    echo "  --force    既存のサムネイルも強制的に再生成\n";
    echo "  --help     このヘルプを表示\n\n";
    echo "例:\n";
    echo "  php regenerate_thumbnails.php --missing\n";
    echo "  php regenerate_thumbnails.php --all --force\n\n";
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

/**
 * 動画からサムネイルを生成
 */
function generateVideoThumbnail($videoPath, $thumbnailPath, $ffmpegPath) {

    // サムネイルディレクトリを作成
    $thumbnailDir = dirname($thumbnailPath);
    if (!is_dir($thumbnailDir)) {
        mkdir($thumbnailDir, 0755, true);
    }

    // ffmpegで1秒地点からサムネイルを抽出
    $command = sprintf(
        '%s -i %s -ss 00:00:01.000 -vframes 1 -vf "scale=320:-1" %s 2>&1',
        escapeshellarg($ffmpegPath),
        escapeshellarg($videoPath),
        escapeshellarg($thumbnailPath)
    );

    exec($command, $output, $returnCode);

    if ($returnCode === 0 && file_exists($thumbnailPath)) {
        return ['success' => true, 'path' => $thumbnailPath];
    } else {
        $errorMsg = implode("\n", $output);
        return ['success' => false, 'error' => $errorMsg];
    }
}

// メイン処理
try {
    // コマンドライン引数を解析
    $all = false;
    $missing = true;
    $force = false;
    $help = false;

    for ($i = 1; $i < $argc; $i++) {
        $arg = $argv[$i];
        if ($arg === '--all') {
            $all = true;
            $missing = false;
        } elseif ($arg === '--missing') {
            $missing = true;
            $all = false;
        } elseif ($arg === '--force') {
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

    echo Colors::$BOLD . Colors::$CYAN . "KidSnaps Growth Album - サムネイル再生成" . Colors::$RESET . "\n";
    echo str_repeat('=', 60) . "\n\n";

    // ffmpegの確認（ローカル優先）
    $isWindows = (PHP_OS_FAMILY === 'Windows');
    $ffmpegPath = null;

    // 1. ローカルのffmpegディレクトリを確認
    // Windows用(.exe)とLinux/Mac用(拡張子なし)の両方をチェック
    $localFfmpegPaths = [
        __DIR__ . '/../../ffmpeg/ffmpeg.exe',  // Windows
        __DIR__ . '/../../ffmpeg/ffmpeg'        // Linux/Mac
    ];

    foreach ($localFfmpegPaths as $localPath) {
        if (file_exists($localPath)) {
            $ffmpegPath = $localPath;
            echo Colors::$GREEN . "✓ ローカルのffmpegを使用: {$ffmpegPath}" . Colors::$RESET . "\n\n";
            break;
        }
    }

    if (!$ffmpegPath) {
        // 2. システムPATHから探す
        $ffmpegCheckCommand = $isWindows ? 'where ffmpeg 2>nul' : 'which ffmpeg 2>/dev/null';
        $ffmpegPath = trim(shell_exec($ffmpegCheckCommand));

        if (empty($ffmpegPath)) {
            echo Colors::$RED . "エラー: ffmpegが見つかりません。" . Colors::$RESET . "\n\n";
            echo "以下のいずれかの方法でffmpegを用意してください:\n\n";
            echo "【方法1】ローカルに配置（推奨）\n";
            echo "  1. ffmpegをダウンロード: https://ffmpeg.org/download.html\n";
            echo "  2. 解凍して ./ffmpeg/ ディレクトリに配置\n";
            echo "     " . Colors::$YELLOW . __DIR__ . "/ffmpeg/ffmpeg" . ($isWindows ? '.exe' : '') . Colors::$RESET . "\n\n";
            echo "【方法2】システムにインストール\n";
            if ($isWindows) {
                echo "  - PATHに追加\n";
                echo "  - または: choco install ffmpeg\n";
            } else {
                echo "  - sudo apt install ffmpeg  (Ubuntu/Debian)\n";
                echo "  - brew install ffmpeg      (macOS)\n";
            }
            exit(1);
        } else {
            echo Colors::$GREEN . "✓ システムのffmpegを使用: {$ffmpegPath}" . Colors::$RESET . "\n\n";
        }
    }

    // データベース接続
    $pdo = getDbConnection();

    // 対象の動画を取得
    $sql = "SELECT id, filename, file_path, thumbnail_path, stored_filename
            FROM media_files
            WHERE file_type = 'video'";

    if ($missing && !$all) {
        // サムネイルが存在しないもののみ
        $sql .= " AND (thumbnail_path IS NULL OR thumbnail_path = '')";
    }

    $sql .= " ORDER BY id";

    $videos = $pdo->query($sql)->fetchAll();
    $totalVideos = count($videos);

    if ($totalVideos === 0) {
        echo Colors::$YELLOW . "対象の動画が見つかりませんでした。" . Colors::$RESET . "\n";
        exit(0);
    }

    echo "対象動画: " . Colors::$CYAN . $totalVideos . "件" . Colors::$RESET . "\n";
    if ($force) {
        echo Colors::$YELLOW . "強制モード: 既存のサムネイルも再生成します" . Colors::$RESET . "\n";
    }
    echo "\n";

    echo Colors::$YELLOW . "サムネイル生成を開始します..." . Colors::$RESET . "\n\n";

    $successCount = 0;
    $skipCount = 0;
    $errorCount = 0;
    $errorMessages = [];

    foreach ($videos as $index => $video) {
        $current = $index + 1;
        showProgress($current, $totalVideos, substr($video['filename'], 0, 30));

        // 動画ファイルが存在するか確認
        if (!file_exists($video['file_path'])) {
            $errorCount++;
            $errorMessages[] = "ID:{$video['id']} - 動画ファイルが見つかりません: {$video['file_path']}";
            continue;
        }

        // 既存のサムネイルをチェック
        $hasThumbnail = !empty($video['thumbnail_path']) && file_exists($video['thumbnail_path']);

        if ($hasThumbnail && !$force) {
            $skipCount++;
            continue;
        }

        // サムネイルパスを生成
        $thumbnailDir = 'uploads/thumbnails/';
        if (!is_dir($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
        }

        $pathInfo = pathinfo($video['stored_filename']);
        $thumbnailFilename = $pathInfo['filename'] . '_thumb.jpg';
        $thumbnailPath = $thumbnailDir . $thumbnailFilename;

        // サムネイルを生成
        $result = generateVideoThumbnail($video['file_path'], $thumbnailPath, $ffmpegPath);

        if ($result['success']) {
            // データベースを更新
            $updateSql = "UPDATE media_files SET thumbnail_path = :thumbnail_path WHERE id = :id";
            $stmt = $pdo->prepare($updateSql);
            $stmt->execute([
                ':thumbnail_path' => $thumbnailPath,
                ':id' => $video['id']
            ]);

            $successCount++;
        } else {
            $errorCount++;
            $errorMessages[] = "ID:{$video['id']} - {$video['filename']}: {$result['error']}";
        }
    }

    // 結果サマリーを表示
    echo "\n" . str_repeat('=', 60) . "\n";
    echo Colors::$BOLD . "サムネイル生成結果" . Colors::$RESET . "\n";
    echo str_repeat('=', 60) . "\n";
    echo Colors::$GREEN . "成功:     " . $successCount . "件" . Colors::$RESET . "\n";
    echo Colors::$YELLOW . "スキップ: " . $skipCount . "件" . Colors::$RESET . "\n";
    echo Colors::$RED . "エラー:   " . $errorCount . "件" . Colors::$RESET . "\n";
    echo str_repeat('=', 60) . "\n";

    // エラーメッセージを表示
    if (!empty($errorMessages) && count($errorMessages) <= 20) {
        echo "\n" . Colors::$RED . Colors::$BOLD . "エラー詳細:" . Colors::$RESET . "\n";
        foreach ($errorMessages as $msg) {
            echo Colors::$RED . "  - {$msg}" . Colors::$RESET . "\n";
        }
    } elseif (!empty($errorMessages)) {
        echo "\n" . Colors::$RED . Colors::$BOLD . "エラー詳細（最初の20件）:" . Colors::$RESET . "\n";
        for ($i = 0; $i < 20; $i++) {
            echo Colors::$RED . "  - {$errorMessages[$i]}" . Colors::$RESET . "\n";
        }
        echo Colors::$YELLOW . "  ... 他 " . (count($errorMessages) - 20) . " 件" . Colors::$RESET . "\n";
    }

    echo "\n" . Colors::$GREEN . Colors::$BOLD . "処理が完了しました！" . Colors::$RESET . "\n";

} catch (Exception $e) {
    echo "\n" . Colors::$RED . Colors::$BOLD . "致命的なエラー: " . $e->getMessage() . Colors::$RESET . "\n";
    error_log("Regenerate thumbnails error: " . $e->getMessage());
    exit(1);
}
