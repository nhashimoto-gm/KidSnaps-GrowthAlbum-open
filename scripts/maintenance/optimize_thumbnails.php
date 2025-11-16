#!/usr/bin/env php
<?php
/**
 * KidSnaps Growth Album - サムネイル最適化スクリプト
 *
 * 既存のサムネイルを最適化します
 * - プログレッシブJPEGに変換
 * - 適切なサイズにリサイズ（400px幅）
 * - オプションでWebP版も生成
 *
 * 使用方法:
 *   php scripts/maintenance/optimize_thumbnails.php [オプション]
 *
 * オプション:
 *   --all        すべてのサムネイルを最適化
 *   --webp       WebP版も生成
 *   --dry-run    実際には変更せず、処理内容のみ表示
 *   --help       ヘルプを表示
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
require_once __DIR__ . '/../../includes/image_thumbnail_helper.php';

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
    echo Colors::$BOLD . "KidSnaps Growth Album - サムネイル最適化スクリプト" . Colors::$RESET . "\n\n";
    echo "使用方法:\n";
    echo "  php scripts/maintenance/optimize_thumbnails.php [オプション]\n\n";
    echo "オプション:\n";
    echo "  --all      すべてのサムネイルを最適化（デフォルト）\n";
    echo "  --webp     WebP版も生成（ファイルサイズ25-35%削減）\n";
    echo "  --dry-run  実際には変更せず、処理内容のみ表示\n";
    echo "  --help     このヘルプを表示\n\n";
    echo "例:\n";
    echo "  php scripts/maintenance/optimize_thumbnails.php --all\n";
    echo "  php scripts/maintenance/optimize_thumbnails.php --all --webp\n";
    echo "  php scripts/maintenance/optimize_thumbnails.php --dry-run\n\n";
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
 * ファイルサイズをフォーマット
 */
function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}

// メイン処理
try {
    // コマンドライン引数を解析
    $all = true;
    $generateWebP = false;
    $dryRun = false;
    $help = false;

    for ($i = 1; $i < $argc; $i++) {
        $arg = $argv[$i];
        if ($arg === '--all') {
            $all = true;
        } elseif ($arg === '--webp') {
            $generateWebP = true;
        } elseif ($arg === '--dry-run') {
            $dryRun = true;
        } elseif ($arg === '--help') {
            $help = true;
        }
    }

    // ヘルプ表示
    if ($help) {
        showHelp();
        exit(0);
    }

    echo Colors::$BOLD . Colors::$CYAN . "KidSnaps Growth Album - サムネイル最適化" . Colors::$RESET . "\n";
    echo str_repeat('=', 60) . "\n\n";

    if ($dryRun) {
        echo Colors::$YELLOW . "⚠️  ドライランモード: 実際には変更しません" . Colors::$RESET . "\n\n";
    }

    if ($generateWebP) {
        if (isWebPSupported()) {
            echo Colors::$GREEN . "✓ WebP生成: 有効" . Colors::$RESET . "\n";
        } else {
            echo Colors::$RED . "✗ WebP生成: サポートされていません" . Colors::$RESET . "\n";
            $generateWebP = false;
        }
    }

    echo "\n";

    // データベース接続
    $pdo = getDbConnection();

    // 対象の画像を取得（サムネイルが存在するもの）
    $sql = "SELECT id, filename, file_path, thumbnail_path
            FROM media_files
            WHERE file_type = 'image'
            AND thumbnail_path IS NOT NULL
            AND thumbnail_path != ''
            ORDER BY id";

    $images = $pdo->query($sql)->fetchAll();
    $totalImages = count($images);

    if ($totalImages === 0) {
        echo Colors::$YELLOW . "対象のサムネイルが見つかりませんでした。" . Colors::$RESET . "\n";
        exit(0);
    }

    echo "対象サムネイル: " . Colors::$CYAN . $totalImages . "件" . Colors::$RESET . "\n\n";
    echo Colors::$YELLOW . "サムネイル最適化を開始します..." . Colors::$RESET . "\n\n";

    $optimizedCount = 0;
    $webpCount = 0;
    $skipCount = 0;
    $errorCount = 0;
    $totalSizeBefore = 0;
    $totalSizeAfter = 0;

    foreach ($images as $index => $image) {
        $current = $index + 1;
        showProgress($current, $totalImages, substr($image['filename'], 0, 30));

        $thumbnailPath = $image['thumbnail_path'];

        // サムネイルファイルが存在するか確認
        if (!file_exists($thumbnailPath)) {
            $errorCount++;
            continue;
        }

        $sizeBefore = filesize($thumbnailPath);
        $totalSizeBefore += $sizeBefore;

        if (!$dryRun) {
            // 一時ファイルに最適化版を生成
            $tempPath = $thumbnailPath . '.tmp';

            // 元の画像からサムネイルを再生成
            $result = generateImageThumbnail($image['file_path'], $tempPath, 400, 85);

            if ($result && file_exists($tempPath)) {
                $sizeAfter = filesize($tempPath);

                // 最適化によってサイズが小さくなった場合のみ置き換え
                if ($sizeAfter <= $sizeBefore) {
                    // 元のファイルを置き換え
                    rename($tempPath, $thumbnailPath);
                    $optimizedCount++;
                    $totalSizeAfter += $sizeAfter;

                    // WebP版も生成
                    if ($generateWebP) {
                        $webpPath = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $thumbnailPath);
                        if (generateWebPThumbnail($image['file_path'], $webpPath, 400, 85)) {
                            $webpCount++;
                        }
                    }
                } else {
                    // サイズが大きくなった場合はスキップ
                    unlink($tempPath);
                    $skipCount++;
                    $totalSizeAfter += $sizeBefore;
                }
            } else {
                $errorCount++;
                $totalSizeAfter += $sizeBefore;
                if (file_exists($tempPath)) {
                    unlink($tempPath);
                }
            }
        } else {
            // ドライラン
            $skipCount++;
            $totalSizeAfter += $sizeBefore;
        }
    }

    // 結果サマリーを表示
    echo "\n" . str_repeat('=', 60) . "\n";
    echo Colors::$BOLD . "サムネイル最適化結果" . Colors::$RESET . "\n";
    echo str_repeat('=', 60) . "\n";
    echo Colors::$GREEN . "最適化:   " . $optimizedCount . "件" . Colors::$RESET . "\n";

    if ($generateWebP) {
        echo Colors::$GREEN . "WebP生成: " . $webpCount . "件" . Colors::$RESET . "\n";
    }

    echo Colors::$YELLOW . "スキップ: " . $skipCount . "件" . Colors::$RESET . "\n";
    echo Colors::$RED . "エラー:   " . $errorCount . "件" . Colors::$RESET . "\n";
    echo str_repeat('=', 60) . "\n";

    if (!$dryRun && $optimizedCount > 0) {
        $savedBytes = $totalSizeBefore - $totalSizeAfter;
        $savedPercent = ($totalSizeBefore > 0) ? ($savedBytes / $totalSizeBefore * 100) : 0;

        echo "\n" . Colors::$BOLD . "容量削減:" . Colors::$RESET . "\n";
        echo "  最適化前: " . formatFileSize($totalSizeBefore) . "\n";
        echo "  最適化後: " . formatFileSize($totalSizeAfter) . "\n";
        echo "  削減量:   " . Colors::$GREEN . formatFileSize($savedBytes) . " (" . number_format($savedPercent, 1) . "%削減)" . Colors::$RESET . "\n";
    }

    echo "\n" . Colors::$GREEN . Colors::$BOLD . "処理が完了しました！" . Colors::$RESET . "\n";

} catch (Exception $e) {
    echo "\n" . Colors::$RED . Colors::$BOLD . "致命的なエラー: " . $e->getMessage() . Colors::$RESET . "\n";
    error_log("Optimize thumbnails error: " . $e->getMessage());
    exit(1);
}
