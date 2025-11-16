#!/usr/bin/env php
<?php
/**
 * 既存のHEICファイルをJPGに変換し、サムネイルを生成するバッチスクリプト
 *
 * 使用方法:
 * php convert_existing_heic.php
 *
 * または実行権限を付与して:
 * chmod +x convert_existing_heic.php
 * ./convert_existing_heic.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/heic_converter.php';
require_once __DIR__ . '/../../includes/image_thumbnail_helper.php';

echo "=== HEIC画像変換・サムネイル生成バッチ処理 ===\n\n";

// データベース接続
try {
    $db = getDbConnection();
} catch (Exception $e) {
    echo "エラー: データベース接続に失敗しました: " . $e->getMessage() . "\n";
    exit(1);
}

// HEICファイルを検索
$sql = "SELECT * FROM media_files WHERE file_type = 'image' AND (mime_type LIKE '%heic%' OR mime_type LIKE '%heif%' OR file_path LIKE '%.heic' OR file_path LIKE '%.heif')";
$stmt = $db->query($sql);
$heicFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($heicFiles)) {
    echo "変換が必要なHEICファイルは見つかりませんでした。\n";
    exit(0);
}

echo "変換対象のHEICファイル: " . count($heicFiles) . "件\n\n";

$convertedCount = 0;
$thumbnailCount = 0;
$errorCount = 0;

foreach ($heicFiles as $media) {
    echo "処理中: {$media['filename']} (ID: {$media['id']})\n";

    $heicPath = $media['file_path'];

    // ファイルが存在するか確認
    if (!file_exists($heicPath)) {
        echo "  エラー: ファイルが見つかりません: {$heicPath}\n";
        $errorCount++;
        continue;
    }

    // JPGファイルパスを生成
    $jpgPath = preg_replace('/\.(heic|heif)$/i', '.jpg', $heicPath);

    // 既にJPGに変換済みかチェック
    if (file_exists($jpgPath)) {
        echo "  スキップ: 既にJPGファイルが存在します: {$jpgPath}\n";

        // データベースを更新（HEICからJPGへ）
        $updateSql = "UPDATE media_files SET file_path = :jpg_path, stored_filename = :stored_filename, mime_type = 'image/jpeg' WHERE id = :id";
        $updateStmt = $db->prepare($updateSql);
        $updateStmt->execute([
            ':jpg_path' => $jpgPath,
            ':stored_filename' => basename($jpgPath),
            ':id' => $media['id']
        ]);

        // HEICファイルを削除
        if (file_exists($heicPath)) {
            unlink($heicPath);
            echo "  HEICファイルを削除しました\n";
        }
    } else {
        // HEICからJPGへ変換
        echo "  変換中: HEIC → JPG...\n";
        $conversionSuccess = convertHeicToJpeg($heicPath, $jpgPath);

        if ($conversionSuccess && file_exists($jpgPath)) {
            echo "  変換成功: {$jpgPath}\n";
            $convertedCount++;

            // データベースを更新
            $updateSql = "UPDATE media_files SET file_path = :jpg_path, stored_filename = :stored_filename, mime_type = 'image/jpeg' WHERE id = :id";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->execute([
                ':jpg_path' => $jpgPath,
                ':stored_filename' => basename($jpgPath),
                ':id' => $media['id']
            ]);

            // HEICファイルを削除
            if (file_exists($heicPath)) {
                unlink($heicPath);
                echo "  HEICファイルを削除しました\n";
            }
        } else {
            echo "  エラー: 変換に失敗しました\n";
            $errorCount++;
            continue;
        }
    }

    // サムネイルが既に存在するかチェック
    if (!empty($media['thumbnail_path']) && file_exists($media['thumbnail_path'])) {
        echo "  サムネイル: 既に存在します\n";
    } else {
        // サムネイルを生成
        echo "  サムネイル生成中...\n";

        $thumbnailDir = 'uploads/thumbnails/';
        if (!is_dir($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
        }

        $thumbnailFilename = pathinfo(basename($jpgPath), PATHINFO_FILENAME) . '_thumb.jpg';
        $thumbnailPath = $thumbnailDir . $thumbnailFilename;

        $thumbnailSuccess = generateImageThumbnail($jpgPath, $thumbnailPath, 320, 85);

        if ($thumbnailSuccess && file_exists($thumbnailPath)) {
            echo "  サムネイル生成成功: {$thumbnailPath}\n";
            $thumbnailCount++;

            // データベースを更新
            $updateSql = "UPDATE media_files SET thumbnail_path = :thumbnail_path WHERE id = :id";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->execute([
                ':thumbnail_path' => $thumbnailPath,
                ':id' => $media['id']
            ]);
        } else {
            echo "  警告: サムネイル生成に失敗しました\n";
        }
    }

    echo "\n";
}

echo "=== 処理完了 ===\n";
echo "変換成功: {$convertedCount}件\n";
echo "サムネイル生成: {$thumbnailCount}件\n";
echo "エラー: {$errorCount}件\n";

exit(0);
?>
