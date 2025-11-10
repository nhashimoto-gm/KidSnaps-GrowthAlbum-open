#!/usr/bin/env php
<?php
/**
 * データベース登録確認スクリプト
 * media_filesテーブルの内容を表示
 */

// CLIからの実行のみ許可
if (php_sapi_name() !== 'cli') {
    die("このスクリプトはコマンドラインからのみ実行できます。\n");
}

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getDbConnection();

    // 総件数を取得
    $countSql = "SELECT COUNT(*) as total FROM media_files";
    $countStmt = $pdo->query($countSql);
    $total = $countStmt->fetchColumn();

    echo "============================================\n";
    echo "media_files テーブルの登録状況\n";
    echo "============================================\n";
    echo "総件数: {$total}件\n\n";

    if ($total > 0) {
        // 最新の10件を取得
        $sql = "SELECT id, filename, file_type, file_size, upload_date
                FROM media_files
                ORDER BY upload_date DESC
                LIMIT 10";
        $stmt = $pdo->query($sql);
        $files = $stmt->fetchAll();

        echo "最新10件:\n";
        echo "--------------------------------------------\n";
        printf("%-5s %-40s %-8s %-12s %s\n", "ID", "ファイル名", "タイプ", "サイズ(KB)", "登録日時");
        echo "--------------------------------------------\n";

        foreach ($files as $file) {
            $sizeKB = round($file['file_size'] / 1024, 2);
            $uploadDate = date('Y/m/d H:i', strtotime($file['upload_date']));
            printf("%-5d %-40s %-8s %-12s %s\n",
                $file['id'],
                substr($file['filename'], 0, 40),
                $file['file_type'],
                $sizeKB,
                $uploadDate
            );
        }
        echo "--------------------------------------------\n";
    } else {
        echo "データベースにメディアファイルが登録されていません。\n";
    }

} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
    exit(1);
}
