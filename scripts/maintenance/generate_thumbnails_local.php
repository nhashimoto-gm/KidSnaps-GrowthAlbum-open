#!/usr/bin/env php
<?php
/**
 * KidSnaps Growth Album - ローカル環境用サムネイル生成スクリプト
 *
 * ローカルPC（ffmpegがインストールされている環境）で実行し、
 * 動画ファイルからサムネイル画像を生成します。
 *
 * 使用方法:
 *   php generate_thumbnails_local.php <動画ディレクトリ> [オプション]
 *
 * オプション:
 *   --output=<出力ディレクトリ>  サムネイルの出力先（デフォルト: thumbnails/）
 *   --time=<秒数>               サムネイルを抽出する時間（デフォルト: 1秒）
 *   --width=<幅>                サムネイルの幅（デフォルト: 320px、高さは自動）
 *   --help                      ヘルプを表示
 *
 * 例:
 *   php generate_thumbnails_local.php /path/to/videos
 *   php generate_thumbnails_local.php /path/to/videos --output=./my_thumbnails
 *   php generate_thumbnails_local.php /path/to/videos --time=2 --width=640
 */

// エラー表示設定
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// CLIからの実行のみ許可
if (php_sapi_name() !== 'cli') {
    die("このスクリプトはコマンドラインからのみ実行できます。\n");
}

// カラー出力用のANSIカラーコード
class Colors {
    public static $RESET = "\033[0m";
    public static $RED = "\033[31m";
    public static $GREEN = "\033[32m";
    public static $YELLOW = "\033[33m";
    public static $BLUE = "\033[34m";
    public static $CYAN = "\033[36m";
    public static $BOLD = "\033[1m";
}

/**
 * ヘルプメッセージを表示
 */
function showHelp() {
    echo Colors::$BOLD . "KidSnaps Growth Album - ローカル環境用サムネイル生成" . Colors::$RESET . "\n\n";
    echo "使用方法:\n";
    echo "  php generate_thumbnails_local.php <動画ディレクトリ> [オプション]\n\n";
    echo "オプション:\n";
    echo "  --output=<出力ディレクトリ>  サムネイルの出力先（デフォルト: thumbnails/）\n";
    echo "  --time=<秒数>               サムネイルを抽出する時間（デフォルト: 1秒）\n";
    echo "  --width=<幅>                サムネイルの幅（デフォルト: 320px）\n";
    echo "  --help                      このヘルプを表示\n\n";
    echo "例:\n";
    echo "  php generate_thumbnails_local.php /path/to/videos\n";
    echo "  php generate_thumbnails_local.php /path/to/videos --output=./my_thumbnails\n";
    echo "  php generate_thumbnails_local.php /path/to/videos --time=2 --width=640\n\n";
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
 * 動画ファイルを再帰的にスキャン
 */
function scanVideoFiles($directory) {
    $supportedExtensions = ['mp4', 'mov', 'avi', 'mpeg', 'mpg'];
    $files = [];

    if (!is_dir($directory)) {
        echo Colors::$RED . "エラー: ディレクトリが見つかりません: {$directory}" . Colors::$RESET . "\n";
        return $files;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $extension = strtolower($file->getExtension());
            if (in_array($extension, $supportedExtensions)) {
                $files[] = $file->getPathname();
            }
        }
    }

    return $files;
}

/**
 * ffprobeで動画の長さを取得
 */
function getVideoDuration($videoPath, $ffprobePath) {
    if (empty($ffprobePath)) {
        return null;
    }

    $command = sprintf(
        '%s -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s 2>&1',
        escapeshellarg($ffprobePath),
        escapeshellarg($videoPath)
    );

    exec($command, $output, $returnCode);

    if ($returnCode === 0 && !empty($output[0])) {
        return (float)$output[0];
    }

    return null;
}

/**
 * ffmpegで動画からサムネイルを生成
 */
function generateThumbnail($videoPath, $outputPath, $ffmpegPath, $ffprobePath, $time = 1, $width = 320) {
    // ffmpegパスの確認
    if (empty($ffmpegPath)) {
        return ['success' => false, 'error' => 'ffmpegが見つかりません'];
    }

    // 出力ディレクトリを作成
    $outputDir = dirname($outputPath);
    if (!is_dir($outputDir)) {
        if (!mkdir($outputDir, 0755, true)) {
            return ['success' => false, 'error' => 'ディレクトリ作成に失敗'];
        }
    }

    // 動画の長さを取得して、適切なシーク位置を決定
    $duration = getVideoDuration($videoPath, $ffprobePath);
    $seekTime = 0; // デフォルトは0秒（最初のフレーム）

    if ($duration !== null && $duration > 0) {
        // 動画の長さが指定時間より長い場合は指定時間を使用、
        // それ以外は動画の長さの半分の位置を使用
        if ($duration > $time) {
            $seekTime = $time;
        } else {
            $seekTime = $duration / 2;
        }
    }

    // ffmpegコマンドを実行
    // -ss を -i の前に配置: 高速シーク
    // -pix_fmt yuvj420p: HEVC (Main 10) などの10bitビデオからJPEG互換フォーマットに変換
    $command = sprintf(
        '%s -ss %s -i %s -vframes 1 -vf "scale=%d:-1" -pix_fmt yuvj420p %s 2>&1',
        escapeshellarg($ffmpegPath),
        escapeshellarg(sprintf('%.3f', $seekTime)),
        escapeshellarg($videoPath),
        (int)$width,
        escapeshellarg($outputPath)
    );

    exec($command, $output, $returnCode);

    if ($returnCode === 0 && file_exists($outputPath)) {
        return ['success' => true, 'path' => $outputPath];
    } else {
        return ['success' => false, 'error' => implode("\n", $output)];
    }
}

// メイン処理
try {
    // コマンドライン引数を解析
    $options = [
        'output' => 'thumbnails',
        'time' => 1,
        'width' => 320,
        'help' => false
    ];

    $targetDir = null;

    for ($i = 1; $i < $argc; $i++) {
        $arg = $argv[$i];

        if ($arg === '--help') {
            $options['help'] = true;
        } elseif (strpos($arg, '--output=') === 0) {
            $options['output'] = substr($arg, 9);
        } elseif (strpos($arg, '--time=') === 0) {
            $options['time'] = (int)substr($arg, 7);
        } elseif (strpos($arg, '--width=') === 0) {
            $options['width'] = (int)substr($arg, 8);
        } elseif (!$targetDir && $arg[0] !== '-') {
            $targetDir = $arg;
        }
    }

    // ヘルプ表示
    if ($options['help']) {
        showHelp();
        exit(0);
    }

    // 対象ディレクトリの確認
    if (!$targetDir) {
        echo Colors::$RED . "エラー: 動画ディレクトリを指定してください。" . Colors::$RESET . "\n";
        showHelp();
        exit(1);
    }

    if (!is_dir($targetDir)) {
        echo Colors::$RED . "エラー: 指定されたディレクトリが見つかりません: {$targetDir}" . Colors::$RESET . "\n";
        exit(1);
    }

    echo Colors::$BOLD . Colors::$CYAN . "KidSnaps - ローカル環境用サムネイル生成" . Colors::$RESET . "\n";
    echo str_repeat('=', 60) . "\n\n";

    // ffmpegの確認（クロスプラットフォーム対応）
    $isWindows = (PHP_OS_FAMILY === 'Windows');
    $ffmpegCheckCommand = $isWindows ? 'where ffmpeg 2>nul' : 'which ffmpeg 2>/dev/null';
    $ffmpegPath = trim(shell_exec($ffmpegCheckCommand));

    if (empty($ffmpegPath)) {
        echo Colors::$RED . "エラー: ffmpegがインストールされていません。" . Colors::$RESET . "\n";
        echo "ffmpegをインストールしてから再実行してください。\n\n";
        echo "インストール方法:\n";
        echo "  macOS:   brew install ffmpeg\n";
        echo "  Ubuntu:  sudo apt-get install ffmpeg\n";
        echo "  Windows: winget install ffmpeg (または https://ffmpeg.org/download.html)\n";
        exit(1);
    }

    echo Colors::$GREEN . "ffmpegが見つかりました: {$ffmpegPath}" . Colors::$RESET . "\n";

    // ffprobeの確認（動画の長さを取得するために使用）
    $ffprobeCheckCommand = $isWindows ? 'where ffprobe 2>nul' : 'which ffprobe 2>/dev/null';
    $ffprobePath = trim(shell_exec($ffprobeCheckCommand));

    if (empty($ffprobePath)) {
        echo Colors::$YELLOW . "警告: ffprobeが見つかりません。動画の長さを自動検出できません。" . Colors::$RESET . "\n";
    } else {
        echo Colors::$GREEN . "ffprobeが見つかりました: {$ffprobePath}" . Colors::$RESET . "\n";
    }

    echo "\n";

    // 動画ファイルをスキャン
    echo Colors::$YELLOW . "動画ファイルをスキャン中: {$targetDir}" . Colors::$RESET . "\n";
    $videoFiles = scanVideoFiles($targetDir);

    $totalFiles = count($videoFiles);

    if ($totalFiles === 0) {
        echo Colors::$YELLOW . "動画ファイルが見つかりませんでした。" . Colors::$RESET . "\n";
        exit(0);
    }

    echo Colors::$GREEN . "見つかった動画: {$totalFiles}件" . Colors::$RESET . "\n\n";

    // 出力ディレクトリを作成
    if (!is_dir($options['output'])) {
        if (!mkdir($options['output'], 0755, true)) {
            echo Colors::$RED . "エラー: 出力ディレクトリの作成に失敗しました。" . Colors::$RESET . "\n";
            exit(1);
        }
    }

    echo Colors::$YELLOW . "サムネイル生成を開始します..." . Colors::$RESET . "\n";
    echo "設定: 時間={$options['time']}秒, 幅={$options['width']}px\n";
    echo "出力先: {$options['output']}/\n\n";

    $results = [
        'success' => 0,
        'error' => 0
    ];

    $errorMessages = [];

    // マッピングファイルを作成（後でサーバーにアップロードする際に使用）
    $mappingFile = $options['output'] . '/thumbnail_mapping.csv';
    $mappingHandle = fopen($mappingFile, 'w');
    fputcsv($mappingHandle, ['video_filename', 'thumbnail_filename', 'thumbnail_path']);

    foreach ($videoFiles as $index => $videoPath) {
        $current = $index + 1;
        $videoFilename = basename($videoPath);
        showProgress($current, $totalFiles, $videoFilename);

        // サムネイルのファイル名を生成
        $pathInfo = pathinfo($videoFilename);
        $thumbnailFilename = $pathInfo['filename'] . '_thumb.jpg';
        $thumbnailPath = $options['output'] . '/' . $thumbnailFilename;

        // 既にサムネイルが存在する場合はスキップ
        if (file_exists($thumbnailPath)) {
            $results['success']++;
            fputcsv($mappingHandle, [$videoFilename, $thumbnailFilename, $thumbnailPath]);
            continue;
        }

        // サムネイルを生成
        $result = generateThumbnail($videoPath, $thumbnailPath, $ffmpegPath, $ffprobePath, $options['time'], $options['width']);

        if ($result['success']) {
            $results['success']++;
            fputcsv($mappingHandle, [$videoFilename, $thumbnailFilename, $thumbnailPath]);
        } else {
            $results['error']++;
            $errorMessages[] = "{$videoFilename}: {$result['error']}";
        }
    }

    fclose($mappingHandle);

    // 結果サマリーを表示
    echo "\n" . str_repeat('=', 60) . "\n";
    echo Colors::$BOLD . "サムネイル生成結果" . Colors::$RESET . "\n";
    echo str_repeat('=', 60) . "\n";
    echo Colors::$GREEN . "成功:   " . $results['success'] . "件" . Colors::$RESET . "\n";
    echo Colors::$RED . "エラー: " . $results['error'] . "件" . Colors::$RESET . "\n";
    echo str_repeat('=', 60) . "\n";

    // エラーメッセージを表示
    if (!empty($errorMessages)) {
        echo "\n" . Colors::$RED . Colors::$BOLD . "エラー詳細:" . Colors::$RESET . "\n";
        foreach (array_slice($errorMessages, 0, 10) as $msg) {
            echo Colors::$RED . "  - {$msg}" . Colors::$RESET . "\n";
        }
        if (count($errorMessages) > 10) {
            echo Colors::$YELLOW . "  ... 他" . (count($errorMessages) - 10) . "件のエラー" . Colors::$RESET . "\n";
        }
    }

    echo "\n" . Colors::$GREEN . Colors::$BOLD . "次のステップ:" . Colors::$RESET . "\n";
    echo "1. 生成されたサムネイルを確認: {$options['output']}/\n";
    echo "2. サムネイルをサーバーにアップロード\n";
    echo "3. サーバーで update_thumbnails.php を実行してデータベースを更新\n\n";
    echo Colors::$CYAN . "マッピングファイル: {$mappingFile}" . Colors::$RESET . "\n";

} catch (Exception $e) {
    echo "\n" . Colors::$RED . Colors::$BOLD . "致命的なエラー: " . $e->getMessage() . Colors::$RESET . "\n";
    error_log("Thumbnail generation error: " . $e->getMessage());
    exit(1);
}
