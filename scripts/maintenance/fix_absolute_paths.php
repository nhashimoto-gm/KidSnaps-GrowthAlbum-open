#!/usr/bin/env php
<?php
/**
 * データベース内の絶対パスを相対パスに修正するスクリプト
 *
 * 【使用方法】
 * php fix_absolute_paths.php [--dry-run]
 *
 * 【オプション】
 * --dry-run : 実際の変更を行わず、プレビューのみ
 */

require_once __DIR__ . '/../../config/database.php';

// コマンドライン引数の解析
$options = getopt('', ['dry-run', 'help']);
$dryRun = isset($options['dry-run']);

if (isset($options['help'])) {
    echo "使用方法: php fix_absolute_paths.php [--dry-run]\n\n";
    echo "オプション:\n";
    echo "  --dry-run    実際の変更を行わず、プレビューのみ\n";
    echo "  --help       このヘルプを表示\n";
    exit(0);
}

echo "=== データベース内の絶対パス修正スクリプト ===\n\n";
echo "ドライラン: " . ($dryRun ? 'はい（変更なし）' : 'いいえ') . "\n\n";

try {
    $db = getDbConnection();
} catch (Exception $e) {
    echo "エラー: データベース接続に失敗しました: " . $e->getMessage() . "\n";
    exit(1);
}

// プロジェクトのベースディレクトリを取得
$baseDir = dirname(dirname(__DIR__));

echo "ベースディレクトリ: {$baseDir}\n\n";

// 絶対パスを含むレコードを検索
$sql = "SELECT id, file_path, thumbnail_path, thumbnail_webp_path, stored_filename, filename
        FROM media_files
        WHERE file_path LIKE :base_dir
           OR thumbnail_path LIKE :base_dir2
           OR thumbnail_webp_path LIKE :base_dir3";

$stmt = $db->prepare($sql);
$stmt->execute([
    ':base_dir' => $baseDir . '%',
    ':base_dir2' => $baseDir . '%',
    ':base_dir3' => $baseDir . '%'
]);

$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($records)) {
    echo "絶対パスを含むレコードは見つかりませんでした。\n";
    exit(0);
}

echo "絶対パスを含むレコード数: " . count($records) . "\n\n";

$updatedCount = 0;
$errorCount = 0;

foreach ($records as $record) {
    $id = $record['id'];
    $needsUpdate = false;
    $updates = [];

    echo "[ID: {$id}] {$record['filename']}\n";

    // file_path の処理
    if (strpos($record['file_path'], $baseDir) === 0) {
        $relativePath = str_replace($baseDir . '/', '', $record['file_path']);
        echo "  file_path:\n";
        echo "    変更前: {$record['file_path']}\n";
        echo "    変更後: {$relativePath}\n";
        $updates['file_path'] = $relativePath;
        $needsUpdate = true;
    }

    // thumbnail_path の処理
    if (!empty($record['thumbnail_path']) && strpos($record['thumbnail_path'], $baseDir) === 0) {
        $relativePath = str_replace($baseDir . '/', '', $record['thumbnail_path']);
        echo "  thumbnail_path:\n";
        echo "    変更前: {$record['thumbnail_path']}\n";
        echo "    変更後: {$relativePath}\n";
        $updates['thumbnail_path'] = $relativePath;
        $needsUpdate = true;
    }

    // thumbnail_webp_path の処理
    if (!empty($record['thumbnail_webp_path']) && strpos($record['thumbnail_webp_path'], $baseDir) === 0) {
        $relativePath = str_replace($baseDir . '/', '', $record['thumbnail_webp_path']);
        echo "  thumbnail_webp_path:\n";
        echo "    変更前: {$record['thumbnail_webp_path']}\n";
        echo "    変更後: {$relativePath}\n";
        $updates['thumbnail_webp_path'] = $relativePath;
        $needsUpdate = true;
    }

    if ($needsUpdate) {
        if (!$dryRun) {
            // データベースを更新
            $setClauses = [];
            $params = [':id' => $id];

            foreach ($updates as $column => $value) {
                $setClauses[] = "{$column} = :{$column}";
                $params[":{$column}"] = $value;
            }

            $updateSql = "UPDATE media_files SET " . implode(', ', $setClauses) . " WHERE id = :id";
            $updateStmt = $db->prepare($updateSql);

            try {
                $updateResult = $updateStmt->execute($params);
                if ($updateResult) {
                    echo "  ✓ 更新成功\n";
                    $updatedCount++;
                } else {
                    echo "  ✗ 更新失敗\n";
                    $errorCount++;
                }
            } catch (Exception $e) {
                echo "  ✗ エラー: " . $e->getMessage() . "\n";
                $errorCount++;
            }
        } else {
            echo "  [ドライラン] 更新をスキップ\n";
            $updatedCount++;
        }
    }

    echo "\n";
}

// 結果サマリ
echo "=== 処理完了 ===\n";
if ($dryRun) {
    echo "*** ドライランモード（変更なし） ***\n";
}
echo "更新されたレコード: {$updatedCount}件\n";
echo "エラー: {$errorCount}件\n";

if ($dryRun) {
    echo "\n実際に適用するには、--dry-run オプションを外して再実行してください。\n";
}

exit(0);
