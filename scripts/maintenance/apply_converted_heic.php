#!/usr/bin/env php
<?php
/**
 * Windows PCで変換したHEIC画像をサーバーに適用するスクリプト
 *
 * 【前提条件】
 * 1. Windows PCで convert_heic_windows.ps1 を実行済み
 * 2. 変換されたJPEG/WebPファイルをサーバーの uploads/images/ にアップロード済み
 * 3. conversion_mapping.csv をサーバーにアップロード済み
 *
 * 【使用方法】
 * php apply_converted_heic.php --csv=/path/to/conversion_mapping.csv [--delete-heic] [--dry-run]
 *
 * 【オプション】
 * --csv            : 変換マッピングCSVファイルのパス（必須）
 * --delete-heic    : 元のHEICファイルを削除する
 * --generate-thumbnails : サムネイルを再生成する
 * --dry-run        : 実際の変更を行わず、プレビューのみ
 * --help           : ヘルプを表示
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/image_thumbnail_helper.php';

// 実行時間とメモリ制限を設定
set_time_limit(0);  // 無制限
ini_set('memory_limit', '512M');  // メモリ上限を512MBに

// コマンドライン引数の解析
$options = getopt('', ['csv:', 'delete-heic', 'generate-thumbnails', 'dry-run', 'help']);

if (isset($options['help'])) {
    echo "使用方法: php apply_converted_heic.php --csv=/path/to/conversion_mapping.csv [オプション]\n\n";
    echo "オプション:\n";
    echo "  --csv=PATH              変換マッピングCSVファイルのパス（必須）\n";
    echo "  --delete-heic           元のHEICファイルを削除する\n";
    echo "  --generate-thumbnails   サムネイルを再生成する\n";
    echo "  --dry-run               実際の変更を行わず、プレビューのみ\n";
    echo "  --help                  このヘルプを表示\n\n";
    echo "例:\n";
    echo "  php apply_converted_heic.php --csv=uploads/images/conversion_mapping.csv --delete-heic\n";
    echo "  php apply_converted_heic.php --csv=uploads/images/conversion_mapping.csv --dry-run\n";
    exit(0);
}

$csvPath = $options['csv'] ?? null;
$deleteHeic = isset($options['delete-heic']);
$generateThumbnails = isset($options['generate-thumbnails']);
$dryRun = isset($options['dry-run']);

if (!$csvPath) {
    echo "エラー: --csv パラメータは必須です。\n";
    echo "使用方法: php apply_converted_heic.php --csv=/path/to/conversion_mapping.csv\n";
    exit(1);
}

// 実行ディレクトリを取得
$baseDir = dirname(dirname(__DIR__));

// 相対パスを絶対パスに変換
if (!file_exists($csvPath)) {
    $csvPath = $baseDir . '/' . $csvPath;
}

if (!file_exists($csvPath)) {
    echo "エラー: CSVファイルが見つかりません: {$csvPath}\n";
    exit(1);
}

echo "=== HEIC変換ファイル適用スクリプト ===\n\n";
echo "CSVファイル: {$csvPath}\n";
echo "元のHEIC削除: " . ($deleteHeic ? 'はい' : 'いいえ') . "\n";
echo "サムネイル再生成: " . ($generateThumbnails ? 'はい' : 'いいえ') . "\n";
echo "ドライラン: " . ($dryRun ? 'はい（変更なし）' : 'いいえ') . "\n";
echo "\n";

// データベース接続
try {
    $db = getDbConnection();
} catch (Exception $e) {
    echo "エラー: データベース接続に失敗しました: " . $e->getMessage() . "\n";
    exit(1);
}

// CSVファイルを読み込み
$csvData = [];
$handle = fopen($csvPath, 'r');
if ($handle === false) {
    echo "エラー: CSVファイルを開けませんでした: {$csvPath}\n";
    exit(1);
}

// ヘッダー行をスキップ
$header = fgetcsv($handle);
if ($header === false || $header[0] !== 'original_filename') {
    echo "エラー: 無効なCSVフォーマットです。\n";
    fclose($handle);
    exit(1);
}

// データ行を読み込み
while (($row = fgetcsv($handle)) !== false) {
    if (count($row) >= 5) {
        $csvData[] = [
            'original_filename' => $row[0],
            'original_path' => $row[1],
            'jpeg_path' => $row[2],
            'webp_path' => $row[3],
            'status' => $row[4]
        ];
    }
}
fclose($handle);

if (empty($csvData)) {
    echo "CSVファイルにデータがありません。\n";
    exit(0);
}

echo "変換マッピング読み込み: " . count($csvData) . "件\n\n";

// 統計
$updatedCount = 0;
$thumbnailCount = 0;
$thumbnailWebpCount = 0;
$thumbnailSkippedCount = 0;  // サムネイル生成済みスキップ数
$deletedCount = 0;
$errorCount = 0;
$skippedCount = 0;

// 各エントリを処理
foreach ($csvData as $index => $entry) {
    $num = $index + 1;
    $totalFiles = count($csvData);

    echo "[{$num}/{$totalFiles}] {$entry['original_filename']}\n";

    // ステータスがsuccessでない場合はスキップ
    if ($entry['status'] !== 'success') {
        echo "  スキップ: ステータス = {$entry['status']}\n\n";
        $skippedCount++;
        continue;
    }

    // データベースで該当ファイルを検索
    $originalFilename = $entry['original_filename'];

    // 変換後のファイル名も取得（既に変換済みの場合に使用）
    $convertedJpegFilename = !empty($entry['jpeg_path']) ? basename($entry['jpeg_path']) : null;
    $convertedWebpFilename = !empty($entry['webp_path']) ? basename($entry['webp_path']) : null;

    // まずHEICファイル名で検索
    $sql = "SELECT * FROM media_files
            WHERE (stored_filename = :filename1 OR filename = :filename2)
            AND file_type = 'image'
            LIMIT 1";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':filename1' => $originalFilename,
        ':filename2' => $originalFilename
    ]);

    $media = $stmt->fetch(PDO::FETCH_ASSOC);

    // HEICで見つからない場合、変換後のファイル名でも検索
    if (!$media && $convertedJpegFilename) {
        echo "  HEICファイル名で見つからないため、JPEGファイル名で検索中...\n";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':filename1' => $convertedJpegFilename,
            ':filename2' => $convertedJpegFilename
        ]);
        $media = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // JPEGでも見つからない場合、WebPでも検索
    if (!$media && $convertedWebpFilename) {
        echo "  JPEGファイル名でも見つからないため、WebPファイル名で検索中...\n";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':filename1' => $convertedWebpFilename,
            ':filename2' => $convertedWebpFilename
        ]);
        $media = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$media) {
        echo "  警告: データベースにレコードが見つかりません\n";
        echo "    検索したファイル名: {$originalFilename}";
        if ($convertedJpegFilename) echo ", {$convertedJpegFilename}";
        if ($convertedWebpFilename) echo ", {$convertedWebpFilename}";
        echo "\n\n";
        $errorCount++;
        continue;
    }

    echo "  DB ID: {$media['id']}\n";

    // 変換後のファイルパスを決定（JPEGを優先）
    $newFilePath = null;
    $newFileRelativePath = null;  // ブラウザアクセス用の相対パス
    $newMimeType = null;

    if (!empty($entry['jpeg_path'])) {
        $newFileRelativePath = $entry['jpeg_path'];  // 相対パス（例: uploads/images/xxx.jpg）
        $newFilePath = $baseDir . '/' . $newFileRelativePath;  // 絶対パス（ファイル存在確認用）
        $newMimeType = 'image/jpeg';
        echo "  変換形式: JPEG\n";
    } elseif (!empty($entry['webp_path'])) {
        $newFileRelativePath = $entry['webp_path'];  // 相対パス
        $newFilePath = $baseDir . '/' . $newFileRelativePath;  // 絶対パス
        $newMimeType = 'image/webp';
        echo "  変換形式: WebP\n";
    }

    if (!$newFilePath) {
        echo "  エラー: 変換ファイルパスが見つかりません\n\n";
        $errorCount++;
        continue;
    }

    // ファイルが実際に存在するか確認
    if (!file_exists($newFilePath)) {
        echo "  エラー: 変換ファイルが見つかりません: {$newFilePath}\n\n";
        $errorCount++;
        continue;
    }

    echo "  変換ファイル: " . basename($newFilePath) . "\n";

    // 既に変換済みかどうかを確認（相対パスで比較）
    $alreadyConverted = ($media['file_path'] === $newFileRelativePath &&
                         $media['stored_filename'] === basename($newFileRelativePath) &&
                         $media['mime_type'] === $newMimeType);

    if ($alreadyConverted) {
        echo "  データベース: 既に変換済み（更新スキップ）\n";
        $updatedCount++; // 成功としてカウント
    } elseif (!$dryRun) {
        // データベースを更新（相対パスを保存）
        $updateSql = "UPDATE media_files
                      SET file_path = :file_path,
                          stored_filename = :stored_filename,
                          mime_type = :mime_type
                      WHERE id = :id";

        $updateStmt = $db->prepare($updateSql);
        $updateResult = $updateStmt->execute([
            ':file_path' => $newFileRelativePath,  // 相対パスを保存
            ':stored_filename' => basename($newFileRelativePath),
            ':mime_type' => $newMimeType,
            ':id' => $media['id']
        ]);

        if ($updateResult) {
            echo "  データベース: 更新成功\n";
            $updatedCount++;
        } else {
            echo "  エラー: データベース更新失敗\n";
            $errorCount++;
            continue;
        }
    } else {
        echo "  [ドライラン] データベースを更新します\n";
        $updatedCount++;
    }

    // 元のHEICファイルを削除
    if ($deleteHeic) {
        // 既に変換済みの場合はDBのfile_pathがJPEGになっているため、CSVのoriginal_pathを使用
        $heicPath = !empty($entry['original_path']) ? $baseDir . '/' . $entry['original_path'] : $media['file_path'];

        if (file_exists($heicPath) && preg_match('/\.(heic|heif)$/i', $heicPath)) {
            if (!$dryRun) {
                if (unlink($heicPath)) {
                    echo "  HEICファイル: 削除成功\n";
                    $deletedCount++;
                } else {
                    echo "  警告: HEICファイル削除失敗: {$heicPath}\n";
                }
            } else {
                echo "  [ドライラン] HEICファイルを削除します: " . basename($heicPath) . "\n";
                $deletedCount++;
            }
        } else {
            if (!file_exists($heicPath)) {
                echo "  HEICファイル: 既に削除済み\n";
            } else {
                echo "  HEICファイル: HEICファイルではないためスキップ\n";
            }
        }
    }

    // サムネイルを再生成
    if ($generateThumbnails) {
        $thumbnailDir = $baseDir . '/uploads/thumbnails/';
        if (!is_dir($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
        }

        $thumbnailFilename = pathinfo(basename($newFilePath), PATHINFO_FILENAME) . '_thumb.jpg';
        $thumbnailPath = $thumbnailDir . $thumbnailFilename;
        $thumbnailRelativePath = 'uploads/thumbnails/' . $thumbnailFilename;

        // WebPファイル名
        $webpFilename = pathinfo(basename($newFilePath), PATHINFO_FILENAME) . '_thumb.webp';
        $webpPath = $thumbnailDir . $webpFilename;
        $webpRelativePath = 'uploads/thumbnails/' . $webpFilename;

        // 既にサムネイルが存在するかチェック
        $jpegExists = file_exists($thumbnailPath);
        $webpExists = file_exists($webpPath);

        if ($jpegExists && $webpExists) {
            echo "  サムネイル: 既に生成済み（スキップ）\n";
            $thumbnailSkippedCount++;
        } else {
            echo "  サムネイル: 生成中...\n";

            if (!$dryRun) {
                // 最適化されたサムネイルを生成（JPEG + WebP）
                $result = generateOptimizedThumbnail($newFilePath, $thumbnailPath, 320, 85, true);

                if ($result['success']) {
                    echo "  サムネイル: JPEG生成成功\n";

                    // WebPも生成されたかチェック
                    $webpGenerated = !empty($result['webp']) && file_exists($result['webp']);
                    if ($webpGenerated) {
                        echo "  サムネイル: WebP生成成功\n";
                        $thumbnailWebpCount++;
                    }

                    // データベースを更新（相対パスで保存）
                    $updateSql = "UPDATE media_files
                                  SET thumbnail_path = :thumbnail_path" .
                                  ($webpGenerated ? ", thumbnail_webp_path = :thumbnail_webp_path" : "") .
                                  " WHERE id = :id";

                    $updateStmt = $db->prepare($updateSql);
                    $params = [
                        ':thumbnail_path' => $thumbnailRelativePath,
                        ':id' => $media['id']
                    ];

                    if ($webpGenerated) {
                        $params[':thumbnail_webp_path'] = $webpRelativePath;
                    }

                    $updateStmt->execute($params);

                    $thumbnailCount++;
                } else {
                    echo "  警告: サムネイル生成失敗\n";
                }
            } else {
                echo "  [ドライラン] サムネイルを生成します: {$thumbnailFilename}\n";
                echo "  [ドライラン] WebPサムネイルを生成します: {$webpFilename}\n";
                $thumbnailCount++;
                $thumbnailWebpCount++;
            }
        }
    }

    echo "\n";

    // 定期的にメモリを解放（50件ごと）
    if ($num % 50 === 0) {
        gc_collect_cycles();
        $memoryUsage = round(memory_get_usage(true) / 1024 / 1024, 2);
        echo "--- メモリ使用量: {$memoryUsage}MB (処理: {$num}/{$totalFiles}件) ---\n\n";
    }
}

// 結果サマリ
echo "=== 処理完了 ===\n";
if ($dryRun) {
    echo "*** ドライランモード（変更なし） ***\n";
}
echo "データベース更新: {$updatedCount}件\n";
if ($deleteHeic) {
    echo "HEICファイル削除: {$deletedCount}件\n";
}
if ($generateThumbnails) {
    echo "サムネイル生成（JPEG）: {$thumbnailCount}件\n";
    echo "サムネイル生成（WebP）: {$thumbnailWebpCount}件\n";
    echo "サムネイル生成済みスキップ: {$thumbnailSkippedCount}件\n";
}
echo "スキップ: {$skippedCount}件\n";
echo "エラー: {$errorCount}件\n";

if ($dryRun) {
    echo "\n実際に適用するには、--dry-run オプションを外して再実行してください。\n";
}

exit(0);
