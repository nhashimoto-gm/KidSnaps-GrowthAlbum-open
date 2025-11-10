#!/usr/bin/env php
<?php
/**
 * サムネイル状態確認スクリプト
 * 動画のサムネイルの状態を確認します
 */

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

    echo Colors::$BOLD . Colors::$CYAN . "サムネイル状態確認" . Colors::$RESET . "\n";
    echo str_repeat('=', 80) . "\n\n";

    // 動画の総数
    $totalVideosSql = "SELECT COUNT(*) FROM media_files WHERE file_type = 'video'";
    $totalVideos = $pdo->query($totalVideosSql)->fetchColumn();

    echo "動画の総数: " . Colors::$CYAN . $totalVideos . "件" . Colors::$RESET . "\n\n";

    // サムネイルがNULLの動画
    $noThumbnailSql = "SELECT COUNT(*) FROM media_files WHERE file_type = 'video' AND (thumbnail_path IS NULL OR thumbnail_path = '')";
    $noThumbnailCount = $pdo->query($noThumbnailSql)->fetchColumn();

    // サムネイルパスがあるが、ファイルが存在しない動画
    $allVideosSql = "SELECT id, filename, file_path, thumbnail_path FROM media_files WHERE file_type = 'video' AND thumbnail_path IS NOT NULL AND thumbnail_path != ''";
    $videosWithThumbnail = $pdo->query($allVideosSql)->fetchAll();

    $missingThumbnailCount = 0;
    $missingThumbnailVideos = [];

    foreach ($videosWithThumbnail as $video) {
        if (!file_exists($video['thumbnail_path'])) {
            $missingThumbnailCount++;
            if (count($missingThumbnailVideos) < 10) {
                $missingThumbnailVideos[] = $video;
            }
        }
    }

    // サムネイルが正常な動画
    $validThumbnailCount = count($videosWithThumbnail) - $missingThumbnailCount;

    // 統計表示
    echo str_repeat('-', 80) . "\n";
    echo Colors::$BOLD . "サムネイル統計" . Colors::$RESET . "\n";
    echo str_repeat('-', 80) . "\n";

    echo sprintf("%-40s %s件\n",
        Colors::$GREEN . "✓ サムネイルが正常な動画" . Colors::$RESET,
        Colors::$GREEN . $validThumbnailCount . Colors::$RESET
    );

    echo sprintf("%-40s %s件\n",
        Colors::$YELLOW . "! サムネイルパスがNULLの動画" . Colors::$RESET,
        Colors::$YELLOW . $noThumbnailCount . Colors::$RESET
    );

    echo sprintf("%-40s %s件\n",
        Colors::$RED . "✗ サムネイルファイルが存在しない動画" . Colors::$RESET,
        Colors::$RED . $missingThumbnailCount . Colors::$RESET
    );

    echo str_repeat('-', 80) . "\n";

    $totalProblems = $noThumbnailCount + $missingThumbnailCount;
    echo "問題がある動画の総数: " . Colors::$RED . $totalProblems . "件" . Colors::$RESET . "\n\n";

    // サムネイルがNULLの動画の詳細（最初の10件）
    if ($noThumbnailCount > 0) {
        echo Colors::$YELLOW . "【サムネイルパスがNULLの動画】（最初の10件）" . Colors::$RESET . "\n";
        echo str_repeat('-', 80) . "\n";

        $noThumbnailVideosSql = "SELECT id, filename, file_path FROM media_files WHERE file_type = 'video' AND (thumbnail_path IS NULL OR thumbnail_path = '') ORDER BY id LIMIT 10";
        $noThumbnailVideos = $pdo->query($noThumbnailVideosSql)->fetchAll();

        foreach ($noThumbnailVideos as $video) {
            $fileExists = file_exists($video['file_path']) ? '✓' : '✗';
            echo sprintf("ID:%-5d %s %s\n",
                $video['id'],
                $fileExists,
                substr($video['filename'], 0, 60)
            );
        }
        echo "\n";
    }

    // サムネイルファイルが存在しない動画の詳細（最初の10件）
    if ($missingThumbnailCount > 0) {
        echo Colors::$RED . "【サムネイルファイルが存在しない動画】（最初の10件）" . Colors::$RESET . "\n";
        echo str_repeat('-', 80) . "\n";

        foreach ($missingThumbnailVideos as $video) {
            $videoExists = file_exists($video['file_path']) ? '✓' : '✗';
            echo sprintf("ID:%-5d %s %s\n",
                $video['id'],
                $videoExists,
                substr($video['filename'], 0, 60)
            );
            echo "  サムネイルパス: {$video['thumbnail_path']}\n";
        }
        echo "\n";
    }

    // 推奨される対処法
    if ($totalProblems > 0) {
        echo str_repeat('=', 80) . "\n";
        echo Colors::$BOLD . "推奨される対処法" . Colors::$RESET . "\n";
        echo str_repeat('=', 80) . "\n";

        echo "1. サムネイルがない動画のサムネイルを生成:\n";
        echo "   " . Colors::$CYAN . "php regenerate_thumbnails.php --missing" . Colors::$RESET . "\n\n";

        echo "2. すべての動画のサムネイルを強制的に再生成:\n";
        echo "   " . Colors::$CYAN . "php regenerate_thumbnails.php --all --force" . Colors::$RESET . "\n\n";

        echo "注意: ffmpegがインストールされている必要があります。\n";
    } else {
        echo Colors::$GREEN . Colors::$BOLD . "すべての動画のサムネイルは正常です！" . Colors::$RESET . "\n";
    }

} catch (Exception $e) {
    echo "\n" . Colors::$RED . Colors::$BOLD . "エラー: " . $e->getMessage() . Colors::$RESET . "\n";
    exit(1);
}
