#!/usr/bin/env php
<?php
/**
 * 最新のレコードを確認
 */

// CLIからの実行のみ許可
if (php_sapi_name() !== 'cli') {
    die("このスクリプトはコマンドラインからのみ実行できます。\n");
}

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getDbConnection();

    // 総件数
    $totalCount = $pdo->query("SELECT COUNT(*) FROM media_files")->fetchColumn();
    echo "============================================\n";
    echo "データベース総件数: {$totalCount}件\n";
    echo "============================================\n\n";

    // 最新20件を取得（IDが大きい順）
    $sql = "SELECT id, filename, file_type, upload_date
            FROM media_files
            ORDER BY id DESC
            LIMIT 20";
    $stmt = $pdo->query($sql);
    $files = $stmt->fetchAll();

    echo "最新20件（ID降順）:\n";
    echo "--------------------------------------------\n";
    printf("%-6s %-40s %-8s %s\n", "ID", "ファイル名", "タイプ", "登録日時");
    echo "--------------------------------------------\n";

    foreach ($files as $file) {
        printf("%-6d %-40s %-8s %s\n",
            $file['id'],
            substr($file['filename'], 0, 40),
            $file['file_type'],
            $file['upload_date']
        );
    }
    echo "--------------------------------------------\n\n";

    // ID範囲を確認
    $rangeSql = "SELECT MIN(id) as min_id, MAX(id) as max_id FROM media_files";
    $range = $pdo->query($rangeSql)->fetch();
    echo "ID範囲: {$range['min_id']} ～ {$range['max_id']}\n\n";

    // 今日登録されたレコード数を確認
    $todaySql = "SELECT COUNT(*) FROM media_files WHERE DATE(upload_date) = CURDATE()";
    $todayCount = $pdo->query($todaySql)->fetchColumn();
    echo "今日登録されたレコード: {$todayCount}件\n";

    if ($todayCount > 0) {
        $todayFilesSql = "SELECT id, filename, upload_date
                          FROM media_files
                          WHERE DATE(upload_date) = CURDATE()
                          ORDER BY id DESC
                          LIMIT 10";
        $todayFiles = $pdo->query($todayFilesSql)->fetchAll();

        echo "\n今日登録されたファイル（最新10件）:\n";
        echo "--------------------------------------------\n";
        foreach ($todayFiles as $file) {
            echo "ID:{$file['id']} - {$file['filename']} - {$file['upload_date']}\n";
        }
    }

} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
    exit(1);
}
