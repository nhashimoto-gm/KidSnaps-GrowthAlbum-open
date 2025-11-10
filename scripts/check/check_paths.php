#!/usr/bin/env php
<?php
/**
 * ファイルパス確認スクリプト
 * データベースに登録されているファイルパスとファイルの実在を確認
 */

// CLIからの実行のみ許可
if (php_sapi_name() !== 'cli') {
    die("このスクリプトはコマンドラインからのみ実行できます。\n");
}

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getDbConnection();

    // 最新の5件を詳細表示
    $sql = "SELECT id, filename, file_type, file_path, thumbnail_path, stored_filename
            FROM media_files
            ORDER BY upload_date DESC
            LIMIT 5";
    $stmt = $pdo->query($sql);
    $files = $stmt->fetchAll();

    echo "============================================\n";
    echo "ファイルパス詳細確認（最新5件）\n";
    echo "============================================\n\n";

    foreach ($files as $file) {
        echo "ID: {$file['id']}\n";
        echo "ファイル名: {$file['filename']}\n";
        echo "保存ファイル名: {$file['stored_filename']}\n";
        echo "ファイルパス: {$file['file_path']}\n";
        echo "サムネイルパス: {$file['thumbnail_path']}\n";

        // ファイルの実在確認
        if (file_exists($file['file_path'])) {
            echo "✓ ファイル存在: YES\n";
        } else {
            echo "✗ ファイル存在: NO\n";
        }

        // サムネイルの実在確認
        if (!empty($file['thumbnail_path'])) {
            if (file_exists($file['thumbnail_path'])) {
                echo "✓ サムネイル存在: YES\n";
            } else {
                echo "✗ サムネイル存在: NO\n";
            }
        }

        echo "--------------------------------------------\n\n";
    }

    // ファイルパスの形式をチェック
    echo "ファイルパス形式の統計:\n";
    echo "============================================\n";

    $pathCheckSql = "SELECT
        COUNT(*) as total,
        SUM(CASE WHEN file_path LIKE 'uploads/%' THEN 1 ELSE 0 END) as relative_paths,
        SUM(CASE WHEN file_path LIKE 'C:%' OR file_path LIKE 'c:%' THEN 1 ELSE 0 END) as absolute_paths
        FROM media_files";
    $pathStats = $pdo->query($pathCheckSql)->fetch();

    echo "総件数: {$pathStats['total']}\n";
    echo "相対パス (uploads/...): {$pathStats['relative_paths']}件\n";
    echo "絶対パス (C:\\...): {$pathStats['absolute_paths']}件\n";

} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
    exit(1);
}
