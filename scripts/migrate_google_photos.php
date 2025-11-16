<?php
/**
 * Google Photosメタデータ用マイグレーション実行スクリプト
 */

require_once __DIR__ . '/../config/database.php';

echo "=== Google Photosメタデータ用マイグレーション開始 ===\n";

try {
    $pdo = getDbConnection();

    // マイグレーションSQLを読み込み
    $sqlFile = __DIR__ . '/../sql/add_google_photos_metadata.sql';

    if (!file_exists($sqlFile)) {
        throw new Exception("マイグレーションファイルが見つかりません: {$sqlFile}");
    }

    $sql = file_get_contents($sqlFile);

    if ($sql === false) {
        throw new Exception("マイグレーションファイルの読み込みに失敗しました。");
    }

    // SQLを実行
    echo "マイグレーションSQLを実行中...\n";

    // セミコロンで分割して各SQL文を個別に実行
    $statements = array_filter(array_map('trim', explode(';', $sql)), function($stmt) {
        return !empty($stmt) && strpos($stmt, '--') !== 0;
    });

    foreach ($statements as $statement) {
        if (empty($statement)) continue;

        echo "実行: " . substr($statement, 0, 100) . "...\n";
        $pdo->exec($statement);
    }

    echo "\n=== マイグレーション完了 ===\n";
    echo "Google Photosメタデータ用のカラムを追加しました。\n";

} catch (Exception $e) {
    echo "\n=== マイグレーションエラー ===\n";
    echo "エラー: " . $e->getMessage() . "\n";
    exit(1);
}
?>
