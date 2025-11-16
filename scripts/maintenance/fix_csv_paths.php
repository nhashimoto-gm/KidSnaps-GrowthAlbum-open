#!/usr/bin/env php
<?php
/**
 * conversion_mapping.csv のパスを修正するスクリプト
 *
 * 使用方法:
 * php fix_csv_paths.php --csv=/path/to/conversion_mapping.csv [--dry-run]
 */

$options = getopt('', ['csv:', 'dry-run', 'help']);

if (isset($options['help']) || !isset($options['csv'])) {
    echo "使用方法: php fix_csv_paths.php --csv=/path/to/conversion_mapping.csv [--dry-run]\n";
    echo "\n";
    echo "オプション:\n";
    echo "  --csv=PATH    修正するCSVファイルのパス（必須）\n";
    echo "  --dry-run     実際の変更を行わず、プレビューのみ\n";
    echo "  --help        このヘルプを表示\n";
    exit(0);
}

$csvPath = $options['csv'];
$dryRun = isset($options['dry-run']);

// CSVファイルの存在確認
if (!file_exists($csvPath)) {
    echo "エラー: CSVファイルが見つかりません: {$csvPath}\n";
    exit(1);
}

echo "=== CSVパス修正スクリプト ===\n\n";
echo "CSVファイル: {$csvPath}\n";
echo "ドライラン: " . ($dryRun ? 'はい（変更なし）' : 'いいえ') . "\n\n";

// CSVファイルを読み込み
$handle = fopen($csvPath, 'r');
if ($handle === false) {
    echo "エラー: CSVファイルを開けませんでした\n";
    exit(1);
}

// 新しいデータを格納する配列
$newLines = [];

// ヘッダー行を読み込み
$header = fgetcsv($handle);
if ($header === false) {
    echo "エラー: CSVファイルが空です\n";
    fclose($handle);
    exit(1);
}

// ヘッダー行をそのまま保持
$newLines[] = implode(',', $header);

$lineCount = 0;
$fixedCount = 0;

// データ行を読み込み・修正
while (($row = fgetcsv($handle)) !== false) {
    if (count($row) < 5) {
        continue; // 不正な行はスキップ
    }

    $lineCount++;

    $originalFilename = $row[0];
    $originalPath = $row[1];
    $jpegPath = $row[2];
    $webpPath = $row[3];
    $status = $row[4];

    // パスを修正（uploads/images/ を先頭に追加）
    $newOriginalPath = $originalPath;
    $newJpegPath = $jpegPath;
    $newWebpPath = $webpPath;

    // uploads/images/ が含まれていない場合のみ追加
    if (!empty($originalPath) && strpos($originalPath, 'uploads/images/') !== 0) {
        $newOriginalPath = 'uploads/images/' . ltrim($originalPath, './');
    }

    if (!empty($jpegPath) && strpos($jpegPath, 'uploads/images/') !== 0) {
        $newJpegPath = 'uploads/images/' . ltrim($jpegPath, './');
    }

    if (!empty($webpPath) && strpos($webpPath, 'uploads/images/') !== 0) {
        $newWebpPath = 'uploads/images/' . ltrim($webpPath, './');
    }

    // 変更があったかチェック
    if ($originalPath !== $newOriginalPath || $jpegPath !== $newJpegPath || $webpPath !== $newWebpPath) {
        $fixedCount++;

        if ($lineCount <= 5 || $dryRun) {
            echo "行 {$lineCount}: {$originalFilename}\n";
            echo "  元: {$originalPath} → JPEG: {$jpegPath}\n";
            echo "  新: {$newOriginalPath} → JPEG: {$newJpegPath}\n\n";
        }
    }

    // 新しい行を作成（CSVエスケープ処理）
    $newRow = [
        $originalFilename,
        $newOriginalPath,
        $newJpegPath,
        $newWebpPath,
        $status
    ];

    // CSVフォーマットで保存（引用符で囲む）
    $newLines[] = '"' . implode('","', $newRow) . '"';
}

fclose($handle);

echo "総行数: {$lineCount}件\n";
echo "修正対象: {$fixedCount}件\n\n";

if (!$dryRun) {
    // バックアップを作成
    $backupPath = $csvPath . '.backup.' . date('YmdHis');
    if (copy($csvPath, $backupPath)) {
        echo "バックアップ作成: {$backupPath}\n";
    } else {
        echo "警告: バックアップの作成に失敗しました\n";
    }

    // 新しいCSVファイルを書き込み
    $result = file_put_contents($csvPath, implode("\n", $newLines) . "\n");

    if ($result !== false) {
        echo "CSVファイルを更新しました: {$csvPath}\n";
        echo "\n次のステップ:\n";
        echo "php scripts/maintenance/apply_converted_heic.php --csv={$csvPath} --dry-run\n";
    } else {
        echo "エラー: CSVファイルの書き込みに失敗しました\n";
        exit(1);
    }
} else {
    echo "=== ドライランモード ===\n";
    echo "実際に修正するには、--dry-run オプションを外して再実行してください。\n";
}

exit(0);
