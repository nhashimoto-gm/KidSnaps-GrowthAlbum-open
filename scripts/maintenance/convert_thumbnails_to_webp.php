#!/usr/bin/env php
<?php
/**
 * 既存のサムネイルをWebP形式に変換するスクリプト
 *
 * 画像と動画のサムネイルをWebP形式に変換します。
 * 動画でサムネイルが存在しない場合は、ffmpegを使用してサムネイル（JPEG + WebP）を生成します。
 *
 * 使い方:
 *   php convert_thumbnails_to_webp.php [options]
 *
 * オプション:
 *   --all         : すべてのサムネイルを変換（既存のWebPも再生成）
 *   --missing     : WebP版がないサムネイルのみ変換（デフォルト）
 *   --dry-run     : 実際には変換せず、処理内容のみ表示
 *   --quality=85  : WebP品質を指定（デフォルト: 85）
 *
 * 注意:
 *   - 動画のサムネイル生成には ffmpeg が必要です
 *   - ffmpegは ./ffmpeg/ffmpeg に配置するか、システムにインストールしてください
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
require_once $projectRoot . '/includes/image_thumbnail_helper.php';
require_once $projectRoot . '/includes/video_thumbnail_helper.php';

// コマンドライン引数の解析
$options = getopt('', ['all', 'missing', 'dry-run', 'quality::']);

$convertAll = isset($options['all']);
$missingOnly = isset($options['missing']) || !$convertAll;
$dryRun = isset($options['dry-run']);
$quality = isset($options['quality']) ? (int)$options['quality'] : 85;

// WebP対応チェック
if (!isWebPSupported()) {
    echo "エラー: WebPサポートが有効になっていません。\n";
    echo "GDライブラリにWebPサポートを追加してください。\n";
    exit(1);
}

echo "===========================================\n";
echo "サムネイルWebP変換ツール\n";
echo "===========================================\n";
echo "モード: " . ($convertAll ? "すべて変換" : "未変換のみ") . "\n";
echo "実行モード: " . ($dryRun ? "ドライラン（変更なし）" : "実行") . "\n";
echo "WebP品質: {$quality}\n";
echo "===========================================\n\n";

try {
    // データベース接続
    $pdo = getDbConnection();

    // 画像・動画メディア取得
    // サムネイルがないものも対象に含める（動画の場合は生成を試みる）
    $sql = "SELECT id, file_path, file_type, thumbnail_path, thumbnail_webp_path, filename, stored_filename
            FROM media_files";

    if ($missingOnly) {
        // WebPがないもの、またはサムネイル自体がないもの
        $sql .= " WHERE (thumbnail_webp_path IS NULL OR thumbnail_webp_path = '')";
    }

    $sql .= " ORDER BY id ASC";

    $stmt = $pdo->query($sql);
    $mediaFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = count($mediaFiles);

    if ($total === 0) {
        echo "変換対象のサムネイルがありません。\n";
        exit(0);
    }

    echo "変換対象: {$total}件のサムネイル\n\n";

    $processed = 0;
    $converted = 0;
    $skipped = 0;
    $errors = 0;

    foreach ($mediaFiles as $media) {
        $processed++;
        $id = $media['id'];
        $thumbnailPath = $media['thumbnail_path'];
        $filename = $media['filename'];
        $fileType = $media['file_type'];

        // 進捗表示
        $percent = round(($processed / $total) * 100);
        $typeLabel = $fileType === 'image' ? '画像' : '動画';
        echo sprintf("[%d/%d] (%d%%) [%s] ", $processed, $total, $percent, $typeLabel);

        // サムネイルが存在しない場合の処理
        if (empty($thumbnailPath)) {
            // 動画の場合はffmpegで生成を試みる
            if ($fileType === 'video' && isVideoThumbnailGenerationAvailable()) {
                echo "サムネイル生成中: {$filename} ... ";

                if ($dryRun) {
                    echo "[ドライラン] 生成予定\n";
                    $skipped++;
                    continue;
                }

                // サムネイルパスを生成
                $thumbnailDir = $projectRoot . '/uploads/thumbnails';
                if (!is_dir($thumbnailDir)) {
                    mkdir($thumbnailDir, 0755, true);
                }

                $pathInfo = pathinfo($media['stored_filename']);
                $thumbnailFilename = 'thumb_' . $pathInfo['filename'] . '.jpg';
                $thumbnailFullPath = $thumbnailDir . '/' . $thumbnailFilename;
                $thumbnailPath = 'uploads/thumbnails/' . $thumbnailFilename;

                // 動画ファイルの絶対パス
                $videoFullPath = $projectRoot . '/' . $media['file_path'];

                // サムネイル生成
                $result = generateOptimizedVideoThumbnail($videoFullPath, $thumbnailFullPath, 400, $quality, true);

                if (!$result['success']) {
                    echo "失敗: サムネイル生成エラー (動画が破損している可能性があります)\n";
                    echo "      ヒント: 動画を再エンコードするか、手動でサムネイルを追加してください\n";
                    $errors++;
                    continue;
                }

                // WebPパス
                $webpPathInfo = pathinfo($thumbnailPath);
                $webpRelativePath = $webpPathInfo['dirname'] . '/' . $webpPathInfo['filename'] . '.webp';

                // データベース更新
                $updateSql = "UPDATE media_files
                              SET thumbnail_path = :thumbnail_path,
                                  thumbnail_webp_path = :thumbnail_webp_path
                              WHERE id = :id";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([
                    ':thumbnail_path' => $thumbnailPath,
                    ':thumbnail_webp_path' => $result['webp'] ? $webpRelativePath : null,
                    ':id' => $id
                ]);

                echo "成功 (新規生成)\n";
                $converted++;
                continue;
            } else {
                echo "スキップ: サムネイルが存在しません - {$filename}\n";
                $skipped++;
                continue;
            }
        }

        // 相対パスを絶対パスに変換
        $thumbnailFullPath = $projectRoot . '/' . $thumbnailPath;

        // サムネイルファイルが存在するか確認
        if (!file_exists($thumbnailFullPath)) {
            echo "スキップ: サムネイルファイルが存在しません - {$filename} (パス: {$thumbnailFullPath})\n";
            $skipped++;
            continue;
        }

        // WebPファイル名を生成（絶対パス）
        $pathInfo = pathinfo($thumbnailFullPath);
        $webpFilename = $pathInfo['filename'] . '.webp';
        $webpFullPath = $pathInfo['dirname'] . '/' . $webpFilename;

        // 相対パスも生成（データベース保存用）
        $webpPathInfo = pathinfo($thumbnailPath);
        $webpRelativePath = $webpPathInfo['dirname'] . '/' . $webpPathInfo['filename'] . '.webp';

        // 既にWebPが存在する場合
        if (file_exists($webpFullPath) && !$convertAll) {
            echo "スキップ: WebP版が既に存在します - {$filename}\n";
            $skipped++;
            continue;
        }

        echo "変換中: {$filename} ... ";

        if ($dryRun) {
            echo "[ドライラン] 変換予定\n";
            $converted++;
            continue;
        }

        // WebP変換を実行
        try {
            // 動画の場合はサムネイル自体から変換、画像の場合は元ファイルから変換
            // 動画の場合は既存のJPEGサムネイルから、画像の場合は元ファイルから
            if ($fileType === 'video') {
                // 動画：既存のJPEGサムネイルから変換
                $sourceFullPath = $thumbnailFullPath;
            } else {
                // 画像：元の画像ファイルから変換
                $sourceFullPath = $projectRoot . '/' . $media['file_path'];
            }

            // ソースファイルの存在確認
            if (!file_exists($sourceFullPath)) {
                echo "失敗: ソースファイルが見つかりません ({$sourceFullPath})\n";
                $errors++;
                continue;
            }

            $success = generateWebPThumbnail($sourceFullPath, $webpFullPath, 400, $quality);

            if ($success && file_exists($webpFullPath)) {
                // ファイルサイズを比較
                $jpegSize = filesize($thumbnailFullPath);
                $webpSize = filesize($webpFullPath);
                $reduction = round((($jpegSize - $webpSize) / $jpegSize) * 100, 1);

                echo "成功 (JPEG: " . formatBytes($jpegSize) . " → WebP: " . formatBytes($webpSize) . ", {$reduction}% 削減)\n";

                // データベースを更新（相対パスで保存）
                $updateSql = "UPDATE media_files SET thumbnail_webp_path = :webp_path WHERE id = :id";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([
                    ':webp_path' => $webpRelativePath,
                    ':id' => $id
                ]);

                $converted++;
            } else {
                echo "失敗: WebP生成エラー\n";
                $errors++;
            }
        } catch (Exception $e) {
            echo "失敗: " . $e->getMessage() . "\n";
            $errors++;
        }
    }

    echo "\n===========================================\n";
    echo "変換完了\n";
    echo "===========================================\n";
    echo "処理件数: {$processed}件\n";
    echo "変換成功: {$converted}件\n";
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
