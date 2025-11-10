#!/usr/bin/env php
<?php
/**
 * データベース登録テストスクリプト
 * 実際にINSERTが動作するかテストします
 */

// CLIからの実行のみ許可
if (php_sapi_name() !== 'cli') {
    die("このスクリプトはコマンドラインからのみ実行できます。\n");
}

require_once __DIR__ . '/../../config/database.php';

echo "=== データベース登録テスト ===\n\n";

try {
    $pdo = getDbConnection();
    echo "✓ データベース接続成功\n\n";

    // トランザクション開始
    $pdo->beginTransaction();
    echo "トランザクション開始\n";

    // テストデータを挿入
    $sql = "INSERT INTO media_files (
                filename, stored_filename, file_path, file_type, mime_type, file_size, file_hash,
                thumbnail_path, rotation, title, description, upload_date
            ) VALUES (
                :filename, :stored_filename, :file_path, :file_type, :mime_type, :file_size, :file_hash,
                :thumbnail_path, :rotation, :title, :description, NOW()
            )";

    $testData = [
        ':filename' => 'TEST_' . date('YmdHis') . '.jpg',
        ':stored_filename' => 'TEST_stored_' . date('YmdHis') . '.jpg',
        ':file_path' => 'uploads/images/TEST_' . date('YmdHis') . '.jpg',
        ':file_type' => 'image',
        ':mime_type' => 'image/jpeg',
        ':file_size' => 12345,
        ':file_hash' => md5('test_' . time()),
        ':thumbnail_path' => null,
        ':rotation' => 0,
        ':title' => 'データベーステスト',
        ':description' => 'これはテストデータです'
    ];

    echo "テストデータ挿入中...\n";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($testData);
    $insertId = $pdo->lastInsertId();

    echo "✓ INSERT成功 - ID: {$insertId}\n";

    // 挿入したデータを確認
    $checkSql = "SELECT * FROM media_files WHERE id = :id";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([':id' => $insertId]);
    $insertedData = $checkStmt->fetch();

    if ($insertedData) {
        echo "✓ データ確認成功\n";
        echo "  - ID: {$insertedData['id']}\n";
        echo "  - ファイル名: {$insertedData['filename']}\n";
        echo "  - 登録日時: {$insertedData['upload_date']}\n";
    } else {
        echo "✗ データが見つかりません（トランザクション内）\n";
    }

    // ロールバック（テストデータなので削除）
    $pdo->rollBack();
    echo "\nトランザクションをロールバック（テストデータを削除）\n";

    // ロールバック後に確認
    $checkStmt->execute([':id' => $insertId]);
    $afterRollback = $checkStmt->fetch();

    if (!$afterRollback) {
        echo "✓ ロールバック成功（テストデータが削除されました）\n";
    } else {
        echo "✗ ロールバック失敗（データが残っています）\n";
    }

    echo "\n=== テスト完了 ===\n";
    echo "データベースへの登録は正常に動作します。\n\n";

    echo "次に、bulk_import.phpが実際にコミットしているか確認してください。\n";
    echo "bulk_import.phpにはトランザクション処理が明示的にないため、自動コミットのはずです。\n\n";

    // 最新のレコードを確認
    echo "=== 最新5件のレコード ===\n";
    $latestSql = "SELECT id, filename, upload_date FROM media_files ORDER BY id DESC LIMIT 5";
    $latestRecords = $pdo->query($latestSql)->fetchAll();

    foreach ($latestRecords as $record) {
        echo "ID:{$record['id']} - {$record['filename']} - {$record['upload_date']}\n";
    }

} catch (Exception $e) {
    echo "\n✗ エラー: " . $e->getMessage() . "\n";
    echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
