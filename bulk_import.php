#!/usr/bin/env php
<?php
/**
 * KidSnaps Growth Album - 一括メディアインポートスクリプト
 *
 * 指定ディレクトリから画像・動画ファイルを再帰的に検索し、
 * データベースに一括登録するCLIスクリプト
 *
 * 使用方法:
 *   php bulk_import.php <対象ディレクトリ> [オプション]
 *
 * オプション:
 *   --dry-run         実際には登録せず、対象ファイルのリストのみ表示
 *   --skip-duplicates 既に登録済みのファイルをスキップ（デフォルト: 有効）
 *   --title=<タイトル>  全ファイルに共通のタイトルを設定
 *   --help            ヘルプを表示
 *
 * 例:
 *   php bulk_import.php /path/to/photos
 *   php bulk_import.php /path/to/photos --dry-run
 *   php bulk_import.php /path/to/photos --title="夏休みの思い出"
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
require_once __DIR__ . '/includes/exif_helper.php';
require_once __DIR__ . '/includes/heic_converter.php';
require_once __DIR__ . '/includes/video_metadata_helper.php';

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
    echo Colors::$BOLD . "KidSnaps Growth Album - 一括メディアインポートスクリプト" . Colors::$RESET . "\n\n";
    echo "使用方法:\n";
    echo "  php bulk_import.php <対象ディレクトリ> [オプション]\n\n";
    echo "オプション:\n";
    echo "  --dry-run           実際には登録せず、対象ファイルのリストのみ表示\n";
    echo "  --skip-duplicates   既に登録済みのファイルをスキップ（デフォルト: 有効）\n";
    echo "  --title=<タイトル>  全ファイルに共通のタイトルを設定\n";
    echo "  --help              このヘルプを表示\n\n";
    echo "例:\n";
    echo "  php bulk_import.php /path/to/photos\n";
    echo "  php bulk_import.php /path/to/photos --dry-run\n";
    echo "  php bulk_import.php /path/to/photos --title=\"夏休みの思い出\"\n\n";
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
 * サポートされているファイル拡張子
 */
$supportedImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif'];
$supportedVideoExtensions = ['mp4', 'mov', 'avi', 'mpeg', 'mpg'];
$supportedExtensions = array_merge($supportedImageExtensions, $supportedVideoExtensions);

/**
 * ディレクトリを再帰的にスキャンしてメディアファイルを取得
 */
function scanMediaFiles($directory, $supportedExtensions) {
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
 * ファイルが既にデータベースに登録されているかチェック
 */
function isFileRegistered($pdo, $filename, $filesize) {
    $sql = "SELECT COUNT(*) FROM media_files WHERE filename = :filename AND file_size = :file_size";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':filename' => $filename, ':file_size' => $filesize]);
    return $stmt->fetchColumn() > 0;
}

/**
 * 動画からサムネイルを生成
 */
function generateVideoThumbnail($videoPath, $thumbnailPath) {
    // ffmpegがインストールされているか確認（クロスプラットフォーム対応）
    $isWindows = (PHP_OS_FAMILY === 'Windows');
    $ffmpegCheckCommand = $isWindows ? 'where ffmpeg 2>nul' : 'which ffmpeg 2>/dev/null';
    $ffmpegPath = trim(shell_exec($ffmpegCheckCommand));

    if (empty($ffmpegPath)) {
        error_log('ffmpegがインストールされていないため、サムネイル生成をスキップします。');
        return false;
    }

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
        return true;
    } else {
        error_log("サムネイル生成に失敗: {$videoPath}");
        return false;
    }
}

/**
 * メディアファイルをインポート
 */
function importMediaFile($pdo, $filePath, $options) {
    try {
        // ファイル情報を取得
        $filename = basename($filePath);
        $filesize = filesize($filePath);

        // 重複チェック
        if ($options['skipDuplicates'] && isFileRegistered($pdo, $filename, $filesize)) {
            return ['status' => 'skipped', 'message' => '既に登録済み'];
        }

        // MIMEタイプを取得
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        // HEICファイルチェック
        $isHeic = isHeicFile($filePath, $mimeType);

        // ファイルタイプを判定
        $imageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/heic', 'image/heif', 'application/octet-stream'];
        $videoTypes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/mpeg'];

        if (in_array($mimeType, $imageTypes) || $isHeic) {
            $fileType = 'image';
        } elseif (in_array($mimeType, $videoTypes)) {
            $fileType = 'video';
        } else {
            return ['status' => 'error', 'message' => "サポートされていない形式: {$mimeType}"];
        }

        // 保存先ディレクトリ
        $uploadDir = ($fileType === 'image') ? 'uploads/images/' : 'uploads/videos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // 安全なファイル名を生成
        $pathInfo = pathinfo($filename);
        $extension = strtolower($pathInfo['extension']);
        $storedFilename = date('YmdHis') . '_' . uniqid() . '.' . $extension;
        $destPath = $uploadDir . $storedFilename;

        // ファイルをコピー
        if (!copy($filePath, $destPath)) {
            return ['status' => 'error', 'message' => 'ファイルのコピーに失敗'];
        }

        // HEIC変換
        if ($isHeic) {
            $jpegPath = preg_replace('/\.(heic|heif)$/i', '.jpg', $destPath);
            if (convertHeicToJpeg($destPath, $jpegPath)) {
                unlink($destPath);
                $destPath = $jpegPath;
                $storedFilename = basename($jpegPath);
                $mimeType = 'image/jpeg';
                $extension = 'jpg';
            }
        }

        // サムネイル生成（動画の場合）
        $thumbnailPath = null;
        if ($fileType === 'video') {
            $thumbnailDir = 'uploads/thumbnails/';
            $thumbnailFilename = date('YmdHis') . '_' . uniqid() . '_thumb.jpg';
            $thumbnailPath = $thumbnailDir . $thumbnailFilename;

            if (!generateVideoThumbnail($destPath, $thumbnailPath)) {
                $thumbnailPath = null;
            }
        }

        // EXIF/メタデータ取得
        $autoRotation = 0;
        $exifData = [
            'datetime' => null,
            'latitude' => null,
            'longitude' => null,
            'location_name' => null,
            'camera_make' => null,
            'camera_model' => null,
            'orientation' => 1
        ];

        if ($fileType === 'image') {
            $autoRotation = getRotationFromExif($destPath);
            $exifData = getExifData($destPath);

            if ($exifData['latitude'] && $exifData['longitude']) {
                applyRateLimitForGeocoding();
                $exifData['location_name'] = getLocationName($exifData['latitude'], $exifData['longitude']);
            }
        } elseif ($fileType === 'video') {
            $videoMetadata = getVideoMetadata($destPath);

            if ($videoMetadata['datetime']) {
                $exifData['datetime'] = $videoMetadata['datetime'];
            }
            if ($videoMetadata['latitude'] && $videoMetadata['longitude']) {
                $exifData['latitude'] = $videoMetadata['latitude'];
                $exifData['longitude'] = $videoMetadata['longitude'];
                applyRateLimitForGeocoding();
                $exifData['location_name'] = getLocationName($exifData['latitude'], $exifData['longitude']);
            }
            if ($videoMetadata['camera_make']) {
                $exifData['camera_make'] = $videoMetadata['camera_make'];
            }
            if ($videoMetadata['camera_model']) {
                $exifData['camera_model'] = $videoMetadata['camera_model'];
            }
        }

        // タイトルと説明を設定
        $title = $options['title'] ?? null;
        $description = null;

        // 位置情報がある場合は自動的に説明を設定
        if (empty($description) && !empty($exifData['location_name'])) {
            $description = $exifData['location_name'];
        }

        // データベースに登録
        $sql = "INSERT INTO media_files (
                    filename, stored_filename, file_path, file_type, mime_type, file_size,
                    thumbnail_path, rotation, title, description, upload_date,
                    exif_datetime, exif_latitude, exif_longitude, exif_location_name,
                    exif_camera_make, exif_camera_model, exif_orientation
                ) VALUES (
                    :filename, :stored_filename, :file_path, :file_type, :mime_type, :file_size,
                    :thumbnail_path, :rotation, :title, :description, NOW(),
                    :exif_datetime, :exif_latitude, :exif_longitude, :exif_location_name,
                    :exif_camera_make, :exif_camera_model, :exif_orientation
                )";

        $params = [
            ':filename' => $filename,
            ':stored_filename' => $storedFilename,
            ':file_path' => $destPath,
            ':file_type' => $fileType,
            ':mime_type' => $mimeType,
            ':file_size' => $filesize,
            ':thumbnail_path' => $thumbnailPath,
            ':rotation' => $autoRotation,
            ':title' => $title,
            ':description' => $description,
            ':exif_datetime' => $exifData['datetime'],
            ':exif_latitude' => $exifData['latitude'],
            ':exif_longitude' => $exifData['longitude'],
            ':exif_location_name' => $exifData['location_name'],
            ':exif_camera_make' => $exifData['camera_make'],
            ':exif_camera_model' => $exifData['camera_model'],
            ':exif_orientation' => $exifData['orientation']
        ];

        executeQuery($pdo, $sql, $params);

        return ['status' => 'success', 'message' => '登録完了'];

    } catch (Exception $e) {
        error_log("インポートエラー ({$filePath}): " . $e->getMessage());
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

// メイン処理
try {
    // コマンドライン引数を解析
    $options = [
        'dryRun' => false,
        'skipDuplicates' => true,
        'title' => null,
        'help' => false
    ];

    $targetDir = null;

    for ($i = 1; $i < $argc; $i++) {
        $arg = $argv[$i];

        if ($arg === '--help') {
            $options['help'] = true;
        } elseif ($arg === '--dry-run') {
            $options['dryRun'] = true;
        } elseif ($arg === '--skip-duplicates') {
            $options['skipDuplicates'] = true;
        } elseif (strpos($arg, '--title=') === 0) {
            $options['title'] = substr($arg, 8);
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
        echo Colors::$RED . "エラー: 対象ディレクトリを指定してください。" . Colors::$RESET . "\n";
        showHelp();
        exit(1);
    }

    if (!is_dir($targetDir)) {
        echo Colors::$RED . "エラー: 指定されたディレクトリが見つかりません: {$targetDir}" . Colors::$RESET . "\n";
        exit(1);
    }

    echo Colors::$BOLD . Colors::$CYAN . "KidSnaps Growth Album - 一括メディアインポート" . Colors::$RESET . "\n";
    echo str_repeat('=', 60) . "\n\n";

    // 対象ファイルをスキャン
    echo Colors::$YELLOW . "対象ディレクトリをスキャン中: {$targetDir}" . Colors::$RESET . "\n";
    $mediaFiles = scanMediaFiles($targetDir, $supportedExtensions);

    $totalFiles = count($mediaFiles);

    if ($totalFiles === 0) {
        echo Colors::$YELLOW . "メディアファイルが見つかりませんでした。" . Colors::$RESET . "\n";
        exit(0);
    }

    echo Colors::$GREEN . "見つかったファイル: {$totalFiles}件" . Colors::$RESET . "\n\n";

    // ドライラン時はファイルリストを表示して終了
    if ($options['dryRun']) {
        echo Colors::$YELLOW . "ドライランモード: 以下のファイルが登録対象です" . Colors::$RESET . "\n";
        echo str_repeat('-', 60) . "\n";
        foreach ($mediaFiles as $index => $file) {
            echo sprintf("%4d. %s\n", $index + 1, $file);
        }
        echo str_repeat('-', 60) . "\n";
        echo Colors::$CYAN . "合計: {$totalFiles}ファイル" . Colors::$RESET . "\n";
        exit(0);
    }

    // データベース接続
    $pdo = getDbConnection();

    // インポート処理開始
    echo Colors::$YELLOW . "インポート処理を開始します..." . Colors::$RESET . "\n\n";

    $results = [
        'success' => 0,
        'skipped' => 0,
        'error' => 0
    ];

    $errorMessages = [];

    foreach ($mediaFiles as $index => $filePath) {
        $current = $index + 1;
        showProgress($current, $totalFiles, basename($filePath));

        $result = importMediaFile($pdo, $filePath, $options);

        if ($result['status'] === 'success') {
            $results['success']++;
        } elseif ($result['status'] === 'skipped') {
            $results['skipped']++;
        } else {
            $results['error']++;
            $errorMessages[] = basename($filePath) . ': ' . $result['message'];
        }
    }

    // 結果サマリーを表示
    echo "\n" . str_repeat('=', 60) . "\n";
    echo Colors::$BOLD . "インポート結果" . Colors::$RESET . "\n";
    echo str_repeat('=', 60) . "\n";
    echo Colors::$GREEN . "成功:     " . $results['success'] . "件" . Colors::$RESET . "\n";
    echo Colors::$YELLOW . "スキップ: " . $results['skipped'] . "件" . Colors::$RESET . "\n";
    echo Colors::$RED . "エラー:   " . $results['error'] . "件" . Colors::$RESET . "\n";
    echo str_repeat('=', 60) . "\n";

    // エラーメッセージを表示
    if (!empty($errorMessages)) {
        echo "\n" . Colors::$RED . Colors::$BOLD . "エラー詳細:" . Colors::$RESET . "\n";
        foreach ($errorMessages as $msg) {
            echo Colors::$RED . "  - {$msg}" . Colors::$RESET . "\n";
        }
    }

    echo "\n" . Colors::$GREEN . Colors::$BOLD . "処理が完了しました！" . Colors::$RESET . "\n";

} catch (Exception $e) {
    echo "\n" . Colors::$RED . Colors::$BOLD . "致命的なエラー: " . $e->getMessage() . Colors::$RESET . "\n";
    error_log("Bulk import error: " . $e->getMessage());
    exit(1);
}
