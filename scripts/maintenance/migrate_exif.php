<?php
/**
 * EXIF情報カラム追加マイグレーション
 * このファイルを一度だけ実行してください
 */

require_once 'config/database.php';

try {
    $pdo = getDbConnection();

    echo "=== EXIF情報カラム追加マイグレーション ===\n\n";

    // 既存のカラムをチェック
    $stmt = $pdo->query("SHOW COLUMNS FROM media_files LIKE 'exif_datetime'");
    $columnExists = $stmt->fetch();

    if ($columnExists) {
        echo "✓ EXIF情報カラムは既に存在します。マイグレーション不要です。\n";
        exit(0);
    }

    echo "EXIF情報カラムを追加中...\n";

    // マイグレーションSQLを実行
    $sql = "ALTER TABLE media_files
            ADD COLUMN exif_datetime DATETIME DEFAULT NULL COMMENT 'EXIF撮影日時',
            ADD COLUMN exif_latitude DECIMAL(10, 8) DEFAULT NULL COMMENT 'EXIF緯度',
            ADD COLUMN exif_longitude DECIMAL(11, 8) DEFAULT NULL COMMENT 'EXIF経度',
            ADD COLUMN exif_location_name VARCHAR(255) DEFAULT NULL COMMENT 'EXIF位置情報（住所など）',
            ADD COLUMN exif_camera_make VARCHAR(100) DEFAULT NULL COMMENT 'EXIFカメラメーカー',
            ADD COLUMN exif_camera_model VARCHAR(100) DEFAULT NULL COMMENT 'EXIFカメラモデル',
            ADD COLUMN exif_orientation INT DEFAULT 1 COMMENT 'EXIF画像の向き（1-8）',
            ADD INDEX idx_exif_datetime (exif_datetime)";

    $pdo->exec($sql);

    echo "✓ EXIF情報カラムを追加しました。\n\n";
    echo "=== マイグレーション完了 ===\n";
    echo "このファイル (migrate_exif.php) は削除できます。\n";

} catch (PDOException $e) {
    echo "✗ エラー: " . $e->getMessage() . "\n";
    echo "\nエラーが発生しました。データベース接続を確認してください。\n";
    exit(1);
}
?>
