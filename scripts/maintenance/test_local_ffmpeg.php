<?php
/**
 * ローカルFFmpeg簡易テストスクリプト
 * プロジェクト内のffmpegが正しく動作し、HEIC変換に対応しているか確認
 *
 * 使用方法:
 * CLI: php test_local_ffmpeg.php
 * Web: http://your-domain/scripts/maintenance/test_local_ffmpeg.php
 */

// Web実行時の設定
if (php_sapi_name() !== 'cli') {
    echo "<pre>";
}

echo "==========================================\n";
echo "ローカルFFmpeg HEIC変換サポート確認\n";
echo "==========================================\n\n";

// ローカルffmpegのパス
$localFfmpeg = __DIR__ . '/../../ffmpeg/ffmpeg';

echo "1. ローカルFFmpegの存在確認\n";
echo "----------------------------\n";
echo "パス: {$localFfmpeg}\n";

if (!file_exists($localFfmpeg)) {
    echo "✗ エラー: ファイルが存在しません\n";
    exit(1);
}

echo "✓ ファイル存在: あり\n";
echo "ファイルサイズ: " . number_format(filesize($localFfmpeg)) . " bytes\n";

// 実行権限の確認
if (is_executable($localFfmpeg)) {
    echo "✓ 実行権限: あり\n\n";
} else {
    echo "⚠ 実行権限: なし\n";
    echo "  実行権限を付与するには: chmod +x {$localFfmpeg}\n\n";
}

// バージョン確認
echo "2. FFmpegバージョン確認\n";
echo "----------------------------\n";
exec("{$localFfmpeg} -version 2>&1", $versionOutput, $versionReturn);

if ($versionReturn === 0) {
    echo "✓ 実行成功\n";
    echo $versionOutput[0] . "\n\n";
} else {
    echo "✗ 実行失敗 (終了コード: {$versionReturn})\n";
    echo implode("\n", array_slice($versionOutput, 0, 5)) . "\n\n";
    exit(1);
}

// HEVCコーデックサポート確認
echo "3. HEVCコーデックサポート確認\n";
echo "----------------------------\n";
exec("{$localFfmpeg} -codecs 2>&1 | grep hevc", $hevcOutput, $hevcReturn);

if (!empty($hevcOutput)) {
    echo "✓ HEVCコーデック: サポート済\n";
    foreach ($hevcOutput as $line) {
        echo "  " . trim($line) . "\n";
    }
    echo "\n";
} else {
    echo "✗ HEVCコーデック: 未サポート\n";
    echo "  HEIC変換には HEVCコーデック が必要です\n\n";
}

// HEIFフォーマットサポート確認
echo "4. HEIFフォーマットサポート確認\n";
echo "----------------------------\n";
exec("{$localFfmpeg} -formats 2>&1 | grep heif", $heifOutput, $heifReturn);

if (!empty($heifOutput)) {
    echo "✓ HEIFフォーマット: サポート済\n";
    foreach ($heifOutput as $line) {
        echo "  " . trim($line) . "\n";
    }
    echo "\n";
} else {
    echo "⚠ HEIFフォーマット: 未検出\n";
    echo "  注意: HEICはHEVCコーデックでデコード可能なため、必須ではありません\n\n";
}

// heic_converter.php統合テスト
echo "5. heic_converter.php統合テスト\n";
echo "----------------------------\n";
$heicConverterPath = __DIR__ . '/../../includes/heic_converter.php';

if (file_exists($heicConverterPath)) {
    require_once $heicConverterPath;
    echo "✓ heic_converter.php読み込み成功\n";

    if (function_exists('getFFmpegPath')) {
        $ffmpegPath = getFFmpegPath();
        echo "✓ getFFmpegPath() 実行結果:\n";
        echo "  パス: " . ($ffmpegPath ?? 'null') . "\n";

        if ($ffmpegPath === $localFfmpeg) {
            echo "  ✓ ローカルFFmpegが優先的に選択されています\n";
        } elseif ($ffmpegPath === 'ffmpeg') {
            echo "  ⚠ システムFFmpegが選択されています（ローカルFFmpegが利用できない）\n";
        } else {
            echo "  ✗ FFmpegが利用できません\n";
        }
    } else {
        echo "✗ getFFmpegPath() 関数が見つかりません\n";
    }
} else {
    echo "✗ heic_converter.phpが見つかりません\n";
}

echo "\n";

// 結論
echo "==========================================\n";
echo "結論\n";
echo "==========================================\n";

if ($versionReturn === 0 && !empty($hevcOutput)) {
    echo "✓ ローカルFFmpegはHEIC変換に対応しています\n";
    echo "  HEIC変換が正常に動作するはずです\n";
} elseif ($versionReturn === 0 && empty($hevcOutput)) {
    echo "⚠ ローカルFFmpegは動作しますが、HEVCコーデックがありません\n";
    echo "  HEIC変換は動作しない可能性があります\n";
} else {
    echo "✗ ローカルFFmpegが正しく動作していません\n";
    echo "  HEIC変換は動作しません\n";
}

echo "\n";

if (php_sapi_name() !== 'cli') {
    echo "</pre>";
}
?>
