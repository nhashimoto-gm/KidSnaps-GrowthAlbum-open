#!/usr/bin/env php
<?php
/**
 * 既存動画のサムネイル生成スクリプト
 *
 * サムネイルが存在しない動画から、ffmpegを使ってサムネイル（JPEG + WebP）を生成します。
 *
 * 使い方:
 *   php generate_video_thumbnails.php [options]
 *
 * オプション:
 *   --all         : すべての動画を処理（既存のサムネイルも再生成）
 *   --missing     : サムネイルがない動画のみ処理（デフォルト）
 *   --dry-run     : 実際には生成せず、処理内容のみ表示
 *   --quality=85  : 品質を指定（デフォルト: 85）
 *   --width=400   : サムネイル幅を指定（デフォルト: 400）
 */

// エラー表示を有効化
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// CLIからの実行のみ許可
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

// プロジェクトルートのパスを取得
$projectRoot = realpath(__DIR__ . '/../..');
if ($projectRoot === false) {
    die("エラー: プロジェクトルートが見つかりません。\n");
}

// プロジェクトルートに移動
chdir($projectRoot);

// 必要なファイルを読み込み
require_once $projectRoot . '/config/database.php';
require_once $projectRoot . '/includes/video_thumbnail_helper.php';

// コマンドライン引数の解析
$options = getopt('', ['all', 'missing', 'dry-run', 'quality::', 'width::']);

$processAll = isset($options['all']);
$missingOnly = isset($options['missing']) || !$processAll;
$dryRun = isset($options['dry-run']);
$quality = isset($options['quality']) ? (int)$options['quality'] : 85;
$width = isset($options['width']) ? (int)$options['width'] : 400;

// ffmpegの確認
if (!isVideoThumbnailGenerationAvailable()) {
    echo "エラー: ffmpegが見つかりません。\n";
    echo "ffmpegをインストールするか、プロジェクトの ffmpeg/ ディレクトリに配置してください。\n";
    echo "\n";
    echo "インストール方法:\n";
    echo "  Ubuntu/Debian: sudo apt install ffmpeg\n";
    echo "  macOS:         brew install ffmpeg\n";
    echo "  または https://ffmpeg.org/download.html からダウンロード\n";
    exit(1);
}

$ffmpegPath = getFfmpegPath();

echo "===========================================\n";
echo "動画サムネイル生成ツール\n";
echo "===========================================\n";
echo "モード: " . ($processAll ? "すべて処理" : "未生成のみ") . "\n";
echo "実行モード: " . ($dryRun ? "ドライラン（変更なし）" : "実行") . "\n";
echo "品質: {$quality}\n";
echo "幅: {$width}px\n";
echo "ffmpeg: {$ffmpegPath}\n";
echo "===========================================\n\n";

try {
    // データベース接続
    $pdo = getDbConnection();

    // 動画メディア取得
    $sql = "SELECT id, file_path, file_type, thumbnail_path, thumbnail_webp_path, filename, stored_filename
            FROM media_files
            WHERE file_type = 'video'";

    if ($missingOnly) {
        $sql .= " AND (thumbnail_path IS NULL OR thumbnail_path = '')";
    }

    $sql .= " ORDER BY id ASC";

    $stmt = $pdo->query($sql);
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = count($videos);

    if ($total === 0) {
        echo "処理対象の動画がありません。\n";
        exit(0);
    }

    echo "処理対象: {$total}件の動画\n\n";

    $processed = 0;
    $generated = 0;
    $skipped = 0;
    $errors = 0;

    foreach ($videos as $video) {
        $processed++;
        $id = $video['id'];
        $videoPath = $video['file_path'];
        $filename = $video['filename'];
        $storedFilename = $video['stored_filename'];

        // 進捗表示
        $percent = round(($processed / $total) * 100);
        echo sprintf("[%d/%d] (%d%%) [動画] ", $processed, $total, $percent);

        // 動画ファイルの絶対パス
        $videoFullPath = $projectRoot . '/' . $videoPath;

        // 動画ファイルが存在するか確認
        if (!file_exists($videoFullPath)) {
            echo "スキップ: 動画ファイルが存在しません - {$filename}\n";
            $skipped++;
            continue;
        }

        // サムネイルパスを生成
        $thumbnailDir = $projectRoot . '/uploads/thumbnails';
        if (!is_dir($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
        }

        $pathInfo = pathinfo($storedFilename);
        $thumbnailFilename = 'thumb_' . $pathInfo['filename'] . '.jpg';
        $thumbnailFullPath = $thumbnailDir . '/' . $thumbnailFilename;
        $thumbnailRelativePath = 'uploads/thumbnails/' . $thumbnailFilename;

        // WebPパスも生成
        $thumbnailWebPFilename = 'thumb_' . $pathInfo['filename'] . '.webp';
        $thumbnailWebPFullPath = $thumbnailDir . '/' . $thumbnailWebPFilename;
        $thumbnailWebPRelativePath = 'uploads/thumbnails/' . $thumbnailWebPFilename;

        // 既にサムネイルが存在する場合
        if (file_exists($thumbnailFullPath) && !$processAll) {
            echo "スキップ: サムネイルが既に存在します - {$filename}\n";
            $skipped++;
            continue;
        }

        echo "生成中: {$filename} ... ";

        if ($dryRun) {
            echo "[ドライラン] 生成予定\n";
            $generated++;
            continue;
        }

        // サムネイルを生成
        try {
            $result = generateOptimizedVideoThumbnail($videoFullPath, $thumbnailFullPath, $width, $quality, true);

            if ($result['success']) {
                // ファイルサイズを取得
                $jpegSize = filesize($thumbnailFullPath);
                $webpSize = $result['webp'] && file_exists($thumbnailWebPFullPath) ? filesize($thumbnailWebPFullPath) : 0;

                if ($webpSize > 0) {
                    $reduction = round((($jpegSize - $webpSize) / $jpegSize) * 100, 1);
                    echo "成功 (JPEG: " . formatBytes($jpegSize) . ", WebP: " . formatBytes($webpSize) . ", {$reduction}% 削減)\n";
                } else {
                    echo "成功 (JPEG: " . formatBytes($jpegSize) . ", WebP生成失敗)\n";
                }

                // データベースを更新
                $updateSql = "UPDATE media_files
                              SET thumbnail_path = :thumbnail_path,
                                  thumbnail_webp_path = :thumbnail_webp_path
                              WHERE id = :id";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([
                    ':thumbnail_path' => $thumbnailRelativePath,
                    ':thumbnail_webp_path' => $result['webp'] ? $thumbnailWebPRelativePath : null,
                    ':id' => $id
                ]);

                $generated++;
            } else {
                echo "失敗: サムネイル生成エラー\n";
                $errors++;
            }
        } catch (Exception $e) {
            echo "失敗: " . $e->getMessage() . "\n";
            $errors++;
        }
    }

    echo "\n===========================================\n";
    echo "生成完了\n";
    echo "===========================================\n";
    echo "処理件数: {$processed}件\n";
    echo "生成成功: {$generated}件\n";
    echo "スキップ: {$skipped}件\n";
    echo "エラー: {$errors}件\n";
    echo "===========================================\n";

} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * バイト数を人間が読みやすい形式にフォーマット
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>
