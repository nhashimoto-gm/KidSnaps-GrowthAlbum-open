<?php
/**
 * KidSnaps Growth Album - 一時ファイルクリーンアップスクリプト
 * 古い一時ディレクトリとファイルを削除
 *
 * 使用方法: php lib/cleanup_temp.php
 * または: cronジョブで定期実行
 */

// 一時ディレクトリのパス
$tempDir = __DIR__ . '/../uploads/temp/chunked_uploads';

// クリーンアップ対象の経過時間（秒）- デフォルト24時間
$maxAge = 24 * 60 * 60;

/**
 * 一時ディレクトリを再帰的に削除する関数
 */
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }

    if (!is_dir($dir)) {
        return unlink($dir);
    }

    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }

        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }

    return rmdir($dir);
}

/**
 * 古い一時ディレクトリをクリーンアップ
 */
function cleanupOldDirectories($baseDir, $maxAge) {
    if (!file_exists($baseDir)) {
        echo "一時ディレクトリが存在しません: $baseDir\n";
        return 0;
    }

    $deletedCount = 0;
    $currentTime = time();

    $dirs = scandir($baseDir);
    foreach ($dirs as $dir) {
        if ($dir == '.' || $dir == '..') {
            continue;
        }

        $dirPath = $baseDir . DIRECTORY_SEPARATOR . $dir;

        if (!is_dir($dirPath)) {
            continue;
        }

        // ディレクトリの最終更新時刻を取得
        $lastModified = filemtime($dirPath);
        $age = $currentTime - $lastModified;

        // 指定時間より古い場合は削除
        if ($age > $maxAge) {
            if (deleteDirectory($dirPath)) {
                echo "削除: $dirPath (経過時間: " . round($age / 3600, 1) . "時間)\n";
                $deletedCount++;
            } else {
                echo "削除失敗: $dirPath\n";
            }
        }
    }

    return $deletedCount;
}

// コマンドライン実行の場合のみ実行
if (php_sapi_name() === 'cli' || !isset($_SERVER['HTTP_HOST'])) {
    echo "=== 一時ファイルクリーンアップ開始 ===\n";
    echo "対象ディレクトリ: $tempDir\n";
    echo "保持期間: " . ($maxAge / 3600) . "時間\n\n";

    $deletedCount = cleanupOldDirectories($tempDir, $maxAge);

    echo "\n=== クリーンアップ完了 ===\n";
    echo "削除したディレクトリ数: $deletedCount\n";
} else {
    // Web経由でのアクセスは禁止
    http_response_code(403);
    die('Direct access is not allowed.');
}
?>
