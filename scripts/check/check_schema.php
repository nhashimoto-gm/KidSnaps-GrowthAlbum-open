#!/usr/bin/env php
<?php
/**
 * データベーススキーマ確認スクリプト
 */

// CLIからの実行のみ許可
if (php_sapi_name() !== 'cli') {
    die("このスクリプトはコマンドラインからのみ実行できます。\n");
}

require_once __DIR__ . '/../../config/database.php';

echo "=== media_files テーブルスキーマ確認 ===\n\n";

try {
    $pdo = getDbConnection();

    // カラム情報を取得
    $sql = "SHOW COLUMNS FROM media_files";
    $columns = $pdo->query($sql)->fetchAll();

    echo "カラム一覧:\n";
    echo str_repeat('-', 80) . "\n";
    printf("%-25s %-20s %-10s %-10s\n", "カラム名", "型", "NULL", "デフォルト");
    echo str_repeat('-', 80) . "\n";

    $hasFileHash = false;
    foreach ($columns as $column) {
        printf("%-25s %-20s %-10s %-10s\n",
            $column['Field'],
            $column['Type'],
            $column['Null'],
            $column['Default'] ?? 'なし'
        );

        if ($column['Field'] === 'file_hash') {
            $hasFileHash = true;
        }
    }
    echo str_repeat('-', 80) . "\n\n";

    // file_hashカラムの確認
    if ($hasFileHash) {
        echo "✓ file_hashカラムは存在します\n\n";

        // インデックスを確認
        $indexSql = "SHOW INDEX FROM media_files WHERE Column_name = 'file_hash'";
        $indexes = $pdo->query($indexSql)->fetchAll();

        if (!empty($indexes)) {
            echo "✓ file_hashカラムにインデックスが設定されています\n";
        } else {
            echo "✗ file_hashカラムにインデックスが設定されていません\n";
            echo "  以下のSQLでインデックスを作成できます:\n";
            echo "  ALTER TABLE media_files ADD INDEX idx_file_hash (file_hash);\n";
        }
    } else {
        echo "✗ file_hashカラムが存在しません！\n\n";
        echo "以下のSQLを実行してください:\n";
        echo "ALTER TABLE media_files ADD COLUMN file_hash VARCHAR(32) NULL AFTER file_size;\n";
        echo "ALTER TABLE media_files ADD INDEX idx_file_hash (file_hash);\n";
    }

} catch (Exception $e) {
    echo "\nエラー: " . $e->getMessage() . "\n";
    exit(1);
}
