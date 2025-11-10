#!/usr/bin/env php
<?php
/**
 * KidSnaps Growth Album - サムネイル関連付けスクリプト
 *
 * bulk_import.phpでインポートした動画データに、
 * generate_thumbnails_local.phpで生成したサムネイルを関連付けます。
 *
 * 使用方法:
 *   php link_thumbnails.php <サムネイルディレクトリ> [オプション]
 *
 * オプション:
 *   --mapping=<CSVファイル>  マッピングファイルのパス（デフォルト: <サムネイルディレクトリ>/thumbnail_mapping.csv）
 *   --dry-run                実際には更新せず、対象ファイルのリストのみ表示
 *   --help                   ヘルプを表示
 *
 * 例:
 *   php link_thumbnails.php ./thumbnails
 *   php link_thumbnails.php ./thumbnails --dry-run
 *   php link_thumbnails.php ./thumbnails --mapping=./custom_mapping.csv
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
    echo Colors::$BOLD . "KidSnaps Growth Album - サムネイル関連付けスクリプト" . Colors::$RESET . "\n\n";
    echo "使用方法:\n";
    echo "  php link_thumbnails.php <サムネイルディレクトリ> [オプション]\n\n";
    echo "オプション:\n";
    echo "  --mapping=<CSVファイル>  マッピングファイルのパス\n";
    echo "                           （デフォルト: <サムネイルディレクトリ>/thumbnail_mapping.csv）\n";
    echo "  --dry-run                実際には更新せず、対象ファイルのリストのみ表示\n";
    echo "  --help                   このヘルプを表示\n\n";
    echo "例:\n";
    echo "  php link_thumbnails.php ./thumbnails\n";
    echo "  php link_thumbnails.php ./thumbnails --dry-run\n";
    echo "  php link_thumbnails.php ./thumbnails --mapping=./custom_mapping.csv\n\n";
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
 * マッピングCSVファイルを読み込み
 */
function loadThumbnailMapping($csvPath) {
    if (!file_exists($csvPath)) {
        echo Colors::$RED . "エラー: マッピングファイルが見つかりません: {$csvPath}" . Colors::$RESET . "\n";
        return [];
    }

    $mapping = [];
    $handle = fopen($csvPath, 'r');

    // ヘッダー行をスキップ
    $header = fgetcsv($handle);

    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) >= 3) {
            $videoFilename = $row[0];
            $thumbnailFilename = $row[1];
            $thumbnailPath = $row[2];

            $mapping[$videoFilename] = [
                'thumbnail_filename' => $thumbnailFilename,
                'thumbnail_path' => $thumbnailPath
            ];
        }
    }

    fclose($handle);
    return $mapping;
}

/**
 * データベースから動画ファイルを取得
 */
function getVideoFiles($pdo) {
    $sql = "SELECT id, filename, stored_filename, file_path, thumbnail_path
            FROM media_files
            WHERE file_type = 'video'
            ORDER BY id";

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * サムネイルをコピーして関連付け
 */
function linkThumbnail($pdo, $videoFile, $thumbnailInfo, $dryRun = false) {
    $sourceThumbnailPath = $thumbnailInfo['thumbnail_path'];

    // サムネイルファイルが存在するか確認
    if (!file_exists($sourceThumbnailPath)) {
        return [
            'status' => 'error',
            'message' => "サムネイルファイルが見つかりません: {$sourceThumbnailPath}"
        ];
    }

    // 既にサムネイルが設定されている場合
    if (!empty($videoFile['thumbnail_path']) && file_exists($videoFile['thumbnail_path'])) {
        return [
            'status' => 'skipped',
            'message' => '既にサムネイルが設定されています'
        ];
    }

    // 保存先ディレクトリ
    $thumbnailDir = 'uploads/thumbnails/';
    if (!is_dir($thumbnailDir)) {
        if (!$dryRun) {
            mkdir($thumbnailDir, 0755, true);
        }
    }

    // 保存ファイル名を生成（ユニーク）
    $extension = pathinfo($sourceThumbnailPath, PATHINFO_EXTENSION);
    $storedThumbnailFilename = date('YmdHis') . '_' . uniqid() . '_thumb.' . $extension;
    $destThumbnailPath = $thumbnailDir . $storedThumbnailFilename;

    if ($dryRun) {
        return [
            'status' => 'success',
            'message' => "コピー予定: {$sourceThumbnailPath} -> {$destThumbnailPath}",
            'dest_path' => $destThumbnailPath
        ];
    }

    // サムネイルをコピー
    if (!copy($sourceThumbnailPath, $destThumbnailPath)) {
        return [
            'status' => 'error',
            'message' => 'サムネイルのコピーに失敗'
        ];
    }

    // データベースを更新
    try {
        $sql = "UPDATE media_files SET thumbnail_path = :thumbnail_path WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':thumbnail_path' => $destThumbnailPath,
            ':id' => $videoFile['id']
        ]);

        return [
            'status' => 'success',
            'message' => 'サムネイルを関連付けました',
            'dest_path' => $destThumbnailPath
        ];
    } catch (Exception $e) {
        // コピーしたファイルを削除
        if (file_exists($destThumbnailPath)) {
            unlink($destThumbnailPath);
        }

        return [
            'status' => 'error',
            'message' => 'データベースの更新に失敗: ' . $e->getMessage()
        ];
    }
}

// メイン処理
try {
    // コマンドライン引数を解析
    $options = [
        'mapping' => null,
        'dryRun' => false,
        'help' => false
    ];

    $thumbnailDir = null;

    for ($i = 1; $i < $argc; $i++) {
        $arg = $argv[$i];

        if ($arg === '--help') {
            $options['help'] = true;
        } elseif ($arg === '--dry-run') {
            $options['dryRun'] = true;
        } elseif (strpos($arg, '--mapping=') === 0) {
            $options['mapping'] = substr($arg, 10);
        } elseif (!$thumbnailDir && $arg[0] !== '-') {
            $thumbnailDir = $arg;
        }
    }

    // ヘルプ表示
    if ($options['help']) {
        showHelp();
        exit(0);
    }

    // サムネイルディレクトリの確認
    if (!$thumbnailDir) {
        echo Colors::$RED . "エラー: サムネイルディレクトリを指定してください。" . Colors::$RESET . "\n";
        showHelp();
        exit(1);
    }

    if (!is_dir($thumbnailDir)) {
        echo Colors::$RED . "エラー: 指定されたディレクトリが見つかりません: {$thumbnailDir}" . Colors::$RESET . "\n";
        exit(1);
    }

    // マッピングファイルのパス
    $mappingFile = $options['mapping'] ?? ($thumbnailDir . '/thumbnail_mapping.csv');

    echo Colors::$BOLD . Colors::$CYAN . "KidSnaps Growth Album - サムネイル関連付け" . Colors::$RESET . "\n";
    echo str_repeat('=', 60) . "\n\n";

    if ($options['dryRun']) {
        echo Colors::$YELLOW . "ドライランモード: 実際には更新しません" . Colors::$RESET . "\n\n";
    }

    // マッピングファイルを読み込み
    echo Colors::$YELLOW . "マッピングファイルを読み込み中: {$mappingFile}" . Colors::$RESET . "\n";
    $thumbnailMapping = loadThumbnailMapping($mappingFile);

    $totalMappings = count($thumbnailMapping);

    if ($totalMappings === 0) {
        echo Colors::$YELLOW . "マッピング情報が見つかりませんでした。" . Colors::$RESET . "\n";
        exit(0);
    }

    echo Colors::$GREEN . "マッピング情報: {$totalMappings}件" . Colors::$RESET . "\n\n";

    // データベース接続
    $pdo = getDbConnection();

    // 動画ファイルを取得
    echo Colors::$YELLOW . "データベースから動画ファイルを取得中..." . Colors::$RESET . "\n";
    $videoFiles = getVideoFiles($pdo);

    $totalVideos = count($videoFiles);

    if ($totalVideos === 0) {
        echo Colors::$YELLOW . "データベースに動画ファイルが見つかりませんでした。" . Colors::$RESET . "\n";
        exit(0);
    }

    echo Colors::$GREEN . "動画ファイル: {$totalVideos}件" . Colors::$RESET . "\n\n";

    // サムネイルを関連付け
    echo Colors::$YELLOW . "サムネイルの関連付けを開始します..." . Colors::$RESET . "\n\n";

    $results = [
        'success' => 0,
        'skipped' => 0,
        'not_found' => 0,
        'error' => 0
    ];

    $messages = [];

    foreach ($videoFiles as $index => $videoFile) {
        $current = $index + 1;
        $videoFilename = $videoFile['filename'];
        showProgress($current, $totalVideos, $videoFilename);

        // マッピング情報を検索
        if (!isset($thumbnailMapping[$videoFilename])) {
            $results['not_found']++;
            $messages[] = [
                'type' => 'not_found',
                'message' => "{$videoFilename}: マッピング情報が見つかりません"
            ];
            continue;
        }

        // サムネイルを関連付け
        $thumbnailInfo = $thumbnailMapping[$videoFilename];
        $result = linkThumbnail($pdo, $videoFile, $thumbnailInfo, $options['dryRun']);

        if ($result['status'] === 'success') {
            $results['success']++;
            if ($options['dryRun']) {
                $messages[] = [
                    'type' => 'success',
                    'message' => "{$videoFilename}: {$result['message']}"
                ];
            }
        } elseif ($result['status'] === 'skipped') {
            $results['skipped']++;
        } else {
            $results['error']++;
            $messages[] = [
                'type' => 'error',
                'message' => "{$videoFilename}: {$result['message']}"
            ];
        }
    }

    // 結果サマリーを表示
    echo "\n" . str_repeat('=', 60) . "\n";
    echo Colors::$BOLD . "関連付け結果" . Colors::$RESET . "\n";
    echo str_repeat('=', 60) . "\n";
    echo Colors::$GREEN . "成功:             " . $results['success'] . "件" . Colors::$RESET . "\n";
    echo Colors::$YELLOW . "スキップ:         " . $results['skipped'] . "件" . Colors::$RESET . "\n";
    echo Colors::$YELLOW . "マッピング未検出: " . $results['not_found'] . "件" . Colors::$RESET . "\n";
    echo Colors::$RED . "エラー:           " . $results['error'] . "件" . Colors::$RESET . "\n";
    echo str_repeat('=', 60) . "\n";

    // 詳細メッセージを表示（最大10件）
    if (!empty($messages)) {
        echo "\n" . Colors::$BOLD . "詳細:" . Colors::$RESET . "\n";
        $displayMessages = array_slice($messages, 0, 10);
        foreach ($displayMessages as $msg) {
            $color = $msg['type'] === 'error' ? Colors::$RED :
                    ($msg['type'] === 'not_found' ? Colors::$YELLOW : Colors::$CYAN);
            echo $color . "  - {$msg['message']}" . Colors::$RESET . "\n";
        }
        if (count($messages) > 10) {
            echo Colors::$YELLOW . "  ... 他" . (count($messages) - 10) . "件のメッセージ" . Colors::$RESET . "\n";
        }
    }

    if ($options['dryRun']) {
        echo "\n" . Colors::$CYAN . "ドライランモードで実行しました。実際には更新されていません。" . Colors::$RESET . "\n";
        echo Colors::$CYAN . "問題がなければ、--dry-run オプションを外して再実行してください。" . Colors::$RESET . "\n";
    } else {
        echo "\n" . Colors::$GREEN . Colors::$BOLD . "処理が完了しました！" . Colors::$RESET . "\n";
    }

} catch (Exception $e) {
    echo "\n" . Colors::$RED . Colors::$BOLD . "致命的なエラー: " . $e->getMessage() . Colors::$RESET . "\n";
    error_log("Link thumbnails error: " . $e->getMessage());
    exit(1);
}
