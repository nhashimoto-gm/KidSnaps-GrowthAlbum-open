#!/usr/bin/env php
<?php
/**
 * KidSnaps Growth Album - サムネイル一括登録スクリプト
 *
 * generate_thumbnails_local.phpで生成したサムネイル画像を
 * サーバーにアップロードし、データベースを更新します。
 *
 * 使用方法:
 *   1. ローカルでgenerate_thumbnails_local.phpを実行
 *   2. 生成されたthumbnails/ディレクトリをサーバーにアップロード
 *   3. このスクリプトを実行
 *
 *   php update_thumbnails.php <サムネイルディレクトリ> [オプション]
 *
 * オプション:
 *   --dry-run    実際には更新せず、対象ファイルのリストのみ表示
 *   --help       ヘルプを表示
 *
 * 例:
 *   php update_thumbnails.php ./thumbnails
 *   php update_thumbnails.php ./thumbnails --dry-run
 */

// エラー表示設定
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// CLIからの実行のみ許可
if (php_sapi_name() !== 'cli') {
    die("このスクリプトはコマンドラインからのみ実行できます。\n");
}

require_once __DIR__ . '/config/database.php';

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
    echo Colors::$BOLD . "KidSnaps Growth Album - サムネイル一括登録" . Colors::$RESET . "\n\n";
    echo "使用方法:\n";
    echo "  php update_thumbnails.php <サムネイルディレクトリ> [オプション]\n\n";
    echo "オプション:\n";
    echo "  --dry-run    実際には更新せず、対象ファイルのリストのみ表示\n";
    echo "  --help       このヘルプを表示\n\n";
    echo "ワークフロー:\n";
    echo "  1. ローカルでgenerate_thumbnails_local.phpを実行\n";
    echo "  2. 生成されたthumbnails/ディレクトリをサーバーにアップロード\n";
    echo "  3. このスクリプトを実行してデータベースを更新\n\n";
    echo "例:\n";
    echo "  php update_thumbnails.php ./thumbnails\n";
    echo "  php update_thumbnails.php ./thumbnails --dry-run\n\n";
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
 * サムネイルファイルをスキャン
 */
function scanThumbnailFiles($directory) {
    $files = [];

    if (!is_dir($directory)) {
        echo Colors::$RED . "エラー: ディレクトリが見つかりません: {$directory}" . Colors::$RESET . "\n";
        return $files;
    }

    $items = scandir($directory);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $filePath = $directory . '/' . $item;
        if (is_file($filePath)) {
            $extension = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                $files[] = [
                    'path' => $filePath,
                    'filename' => $item
                ];
            }
        }
    }

    return $files;
}

/**
 * マッピングファイルを読み込み
 */
function loadMappingFile($mappingFilePath) {
    $mapping = [];

    if (!file_exists($mappingFilePath)) {
        return $mapping;
    }

    $handle = fopen($mappingFilePath, 'r');
    $header = fgetcsv($handle); // ヘッダー行をスキップ

    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) >= 2) {
            $mapping[$row[1]] = $row[0]; // thumbnail_filename => video_filename
        }
    }

    fclose($handle);
    return $mapping;
}

/**
 * 動画ファイル名からデータベースのレコードを検索
 */
function findVideoRecord($pdo, $videoFilename) {
    $sql = "SELECT id, filename, stored_filename, thumbnail_path FROM media_files
            WHERE file_type = 'video' AND filename = :filename";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':filename' => $videoFilename]);
    return $stmt->fetch();
}

/**
 * サムネイルのファイル名から動画レコードを推測して検索
 */
function findVideoRecordByThumbnail($pdo, $thumbnailFilename) {
    // サムネイルのファイル名から _thumb を除去して元の動画名を推測
    $videoBasename = preg_replace('/_thumb\.(jpg|jpeg|png)$/i', '', $thumbnailFilename);

    // 拡張子のパターンを試す
    $extensions = ['mp4', 'mov', 'avi', 'mpeg', 'mpg', 'MP4', 'MOV', 'AVI', 'MPEG', 'MPG'];

    foreach ($extensions as $ext) {
        $videoFilename = $videoBasename . '.' . $ext;
        $record = findVideoRecord($pdo, $videoFilename);
        if ($record) {
            return $record;
        }
    }

    return null;
}

/**
 * データベースのサムネイルパスを更新
 */
function updateThumbnailPath($pdo, $mediaId, $thumbnailPath) {
    $sql = "UPDATE media_files SET thumbnail_path = :thumbnail_path WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        ':thumbnail_path' => $thumbnailPath,
        ':id' => $mediaId
    ]);
}

// メイン処理
try {
    // コマンドライン引数を解析
    $options = [
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

    echo Colors::$BOLD . Colors::$CYAN . "KidSnaps - サムネイル一括登録" . Colors::$RESET . "\n";
    echo str_repeat('=', 60) . "\n\n";

    // サムネイルファイルをスキャン
    echo Colors::$YELLOW . "サムネイルファイルをスキャン中: {$thumbnailDir}" . Colors::$RESET . "\n";
    $thumbnailFiles = scanThumbnailFiles($thumbnailDir);

    $totalFiles = count($thumbnailFiles);

    if ($totalFiles === 0) {
        echo Colors::$YELLOW . "サムネイルファイルが見つかりませんでした。" . Colors::$RESET . "\n";
        exit(0);
    }

    echo Colors::$GREEN . "見つかったサムネイル: {$totalFiles}件" . Colors::$RESET . "\n\n";

    // マッピングファイルを読み込み
    $mappingFilePath = rtrim($thumbnailDir, '/') . '/thumbnail_mapping.csv';
    $mapping = loadMappingFile($mappingFilePath);

    if (!empty($mapping)) {
        echo Colors::$GREEN . "マッピングファイルを読み込みました: " . count($mapping) . "件" . Colors::$RESET . "\n\n";
    } else {
        echo Colors::$YELLOW . "マッピングファイルが見つかりません。ファイル名から推測します。" . Colors::$RESET . "\n\n";
    }

    // データベース接続
    $pdo = getDbConnection();

    // サムネイル保存先ディレクトリ
    $uploadThumbnailDir = 'uploads/thumbnails/';
    if (!is_dir($uploadThumbnailDir)) {
        if (!mkdir($uploadThumbnailDir, 0755, true)) {
            echo Colors::$RED . "エラー: サムネイルディレクトリの作成に失敗しました。" . Colors::$RESET . "\n";
            exit(1);
        }
    }

    // ドライランモード
    if ($options['dryRun']) {
        echo Colors::$YELLOW . "ドライランモード: 以下のサムネイルが登録対象です" . Colors::$RESET . "\n";
        echo str_repeat('-', 60) . "\n";

        foreach ($thumbnailFiles as $index => $thumbnail) {
            $thumbnailFilename = $thumbnail['filename'];

            // マッピングファイルから動画名を取得、なければ推測
            $videoFilename = $mapping[$thumbnailFilename] ?? preg_replace('/_thumb\.(jpg|jpeg|png)$/i', '.mp4', $thumbnailFilename);

            $record = findVideoRecord($pdo, $videoFilename);
            if (!$record) {
                $record = findVideoRecordByThumbnail($pdo, $thumbnailFilename);
            }

            if ($record) {
                echo sprintf("%4d. %s => 動画ID: %d (%s)\n",
                    $index + 1,
                    $thumbnailFilename,
                    $record['id'],
                    $record['filename']
                );
            } else {
                echo sprintf("%4d. %s => " . Colors::$RED . "対応する動画が見つかりません" . Colors::$RESET . "\n",
                    $index + 1,
                    $thumbnailFilename
                );
            }
        }

        echo str_repeat('-', 60) . "\n";
        exit(0);
    }

    // サムネイル登録処理
    echo Colors::$YELLOW . "サムネイル登録を開始します..." . Colors::$RESET . "\n\n";

    $results = [
        'success' => 0,
        'notfound' => 0,
        'error' => 0
    ];

    $messages = [];

    foreach ($thumbnailFiles as $index => $thumbnail) {
        $current = $index + 1;
        $thumbnailFilename = $thumbnail['filename'];
        $thumbnailPath = $thumbnail['path'];

        showProgress($current, $totalFiles, $thumbnailFilename);

        try {
            // マッピングファイルから動画名を取得、なければ推測
            $videoFilename = $mapping[$thumbnailFilename] ?? null;

            $record = null;
            if ($videoFilename) {
                $record = findVideoRecord($pdo, $videoFilename);
            }

            // マッピングで見つからない場合、ファイル名から推測
            if (!$record) {
                $record = findVideoRecordByThumbnail($pdo, $thumbnailFilename);
            }

            if (!$record) {
                $results['notfound']++;
                $messages[] = "{$thumbnailFilename}: 対応する動画が見つかりません";
                continue;
            }

            // サムネイルをコピー
            $destFilename = date('YmdHis') . '_' . uniqid() . '_thumb.jpg';
            $destPath = $uploadThumbnailDir . $destFilename;

            if (!copy($thumbnailPath, $destPath)) {
                $results['error']++;
                $messages[] = "{$thumbnailFilename}: ファイルのコピーに失敗";
                continue;
            }

            // データベースを更新
            if (updateThumbnailPath($pdo, $record['id'], $destPath)) {
                $results['success']++;
            } else {
                $results['error']++;
                $messages[] = "{$thumbnailFilename}: データベース更新に失敗";

                // 失敗時はコピーしたファイルを削除
                if (file_exists($destPath)) {
                    unlink($destPath);
                }
            }

        } catch (Exception $e) {
            $results['error']++;
            $messages[] = "{$thumbnailFilename}: " . $e->getMessage();
        }
    }

    // 結果サマリーを表示
    echo "\n" . str_repeat('=', 60) . "\n";
    echo Colors::$BOLD . "サムネイル登録結果" . Colors::$RESET . "\n";
    echo str_repeat('=', 60) . "\n";
    echo Colors::$GREEN . "成功:       " . $results['success'] . "件" . Colors::$RESET . "\n";
    echo Colors::$YELLOW . "動画未発見: " . $results['notfound'] . "件" . Colors::$RESET . "\n";
    echo Colors::$RED . "エラー:     " . $results['error'] . "件" . Colors::$RESET . "\n";
    echo str_repeat('=', 60) . "\n";

    // メッセージを表示
    if (!empty($messages)) {
        echo "\n" . Colors::$YELLOW . Colors::$BOLD . "詳細:" . Colors::$RESET . "\n";
        foreach (array_slice($messages, 0, 20) as $msg) {
            echo Colors::$YELLOW . "  - {$msg}" . Colors::$RESET . "\n";
        }
        if (count($messages) > 20) {
            echo Colors::$YELLOW . "  ... 他" . (count($messages) - 20) . "件" . Colors::$RESET . "\n";
        }
    }

    echo "\n" . Colors::$GREEN . Colors::$BOLD . "処理が完了しました！" . Colors::$RESET . "\n";

} catch (Exception $e) {
    echo "\n" . Colors::$RED . Colors::$BOLD . "致命的なエラー: " . $e->getMessage() . Colors::$RESET . "\n";
    error_log("Thumbnail update error: " . $e->getMessage());
    exit(1);
}
