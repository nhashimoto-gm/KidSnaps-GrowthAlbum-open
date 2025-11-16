#!/usr/bin/env php
<?php
/**
 * HEIC関連のデータベースレコードを確認
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getDbConnection();

    echo "=== HEIC関連レコード確認 ===\n\n";

    // HEICファイル名を含むレコードを検索
    $sql = "SELECT id, filename, stored_filename, file_path, mime_type
            FROM media_files
            WHERE filename LIKE '%.heic'
               OR filename LIKE '%.HEIC'
               OR filename LIKE '%.heif'
               OR filename LIKE '%.HEIF'
               OR stored_filename LIKE '%.heic'
               OR stored_filename LIKE '%.HEIC'
               OR stored_filename LIKE '%.heif'
               OR stored_filename LIKE '%.HEIF'
               OR file_path LIKE '%heic%'
               OR file_path LIKE '%HEIC%'
               OR file_path LIKE '%heif%'
               OR file_path LIKE '%HEIF%'
            ORDER BY id DESC
            LIMIT 20";

    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($results)) {
        echo "HEIC関連のレコードが見つかりませんでした。\n";
        exit(0);
    }

    echo "見つかったレコード数: " . count($results) . "\n\n";

    foreach ($results as $row) {
        echo str_repeat('-', 80) . "\n";
        echo "ID: {$row['id']}\n";
        echo "元のファイル名 (filename): {$row['filename']}\n";
        echo "保存ファイル名 (stored_filename): {$row['stored_filename']}\n";
        echo "ファイルパス (file_path): {$row['file_path']}\n";
        echo "MIMEタイプ (mime_type): {$row['mime_type']}\n";

        // ファイルの存在確認
        $baseDir = __DIR__ . '/../..';
        $fullPath = $row['file_path'];

        // 相対パスの場合は絶対パスに変換
        if (!file_exists($fullPath)) {
            $fullPath = $baseDir . '/' . $row['file_path'];
        }

        if (file_exists($fullPath)) {
            echo "ファイル存在: ✓ YES\n";
            echo "実際のファイルサイズ: " . filesize($fullPath) . " bytes\n";

            // ファイルの実際のMIMEタイプを確認
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $actualMimeType = finfo_file($finfo, $fullPath);
            finfo_close($finfo);
            echo "実際のMIMEタイプ: {$actualMimeType}\n";

            // HEICファイルか判定
            if (preg_match('/\.(heic|heif)$/i', $fullPath)) {
                echo "状態: ⚠ まだHEICファイル（変換されていない）\n";
            } elseif (preg_match('/\.(jpg|jpeg)$/i', $fullPath)) {
                echo "状態: ✓ JPEGに変換済み\n";
            } elseif (preg_match('/\.webp$/i', $fullPath)) {
                echo "状態: ✓ WebPに変換済み\n";
            }
        } else {
            echo "ファイル存在: ✗ NO (パス: {$fullPath})\n";
        }

        echo "\n";
    }

    echo str_repeat('=', 80) . "\n\n";

    // 統計情報
    echo "=== 統計情報 ===\n";

    $statsStmt = $pdo->query("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN mime_type = 'image/heic' OR mime_type = 'image/heif' THEN 1 ELSE 0 END) as heic_mime,
            SUM(CASE WHEN mime_type = 'image/jpeg' THEN 1 ELSE 0 END) as jpeg_mime,
            SUM(CASE WHEN mime_type = 'image/webp' THEN 1 ELSE 0 END) as webp_mime,
            SUM(CASE WHEN file_path LIKE '%.heic' OR file_path LIKE '%.HEIC' OR file_path LIKE '%.heif' OR file_path LIKE '%.HEIF' THEN 1 ELSE 0 END) as heic_extension
        FROM media_files
        WHERE filename LIKE '%.heic'
           OR filename LIKE '%.HEIC'
           OR filename LIKE '%.heif'
           OR filename LIKE '%.HEIF'
           OR stored_filename LIKE '%.heic'
           OR stored_filename LIKE '%.HEIC'
           OR stored_filename LIKE '%.heif'
           OR stored_filename LIKE '%.HEIF'
           OR file_path LIKE '%heic%'
           OR file_path LIKE '%HEIC%'
           OR file_path LIKE '%heif%'
           OR file_path LIKE '%HEIF%'
    ");

    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    echo "HEIC関連レコード総数: {$stats['total']}\n";
    echo "MIMEタイプが image/heic または image/heif: {$stats['heic_mime']}\n";
    echo "MIMEタイプが image/jpeg: {$stats['jpeg_mime']}\n";
    echo "MIMEタイプが image/webp: {$stats['webp_mime']}\n";
    echo "file_pathの拡張子が .heic/.heif: {$stats['heic_extension']}\n";

    echo "\n";

    // 変換状況の判定
    if ($stats['heic_extension'] > 0) {
        echo "⚠ 警告: file_pathがまだHEIC拡張子のレコードがあります。\n";
        echo "   これらは apply_converted_heic.php で変換されていません。\n";
    } elseif ($stats['jpeg_mime'] > 0 || $stats['webp_mime'] > 0) {
        echo "✓ file_pathは変換済みファイルを指していますが、\n";
        echo "  filenameやstored_filenameにまだHEIC名が残っている可能性があります。\n";
    }

} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
    exit(1);
}
