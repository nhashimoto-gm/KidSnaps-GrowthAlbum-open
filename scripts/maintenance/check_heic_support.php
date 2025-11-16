<?php
/**
 * HEIC変換サポート確認スクリプト（PHP版）
 * ブラウザまたはCLIから実行可能
 *
 * 使用方法:
 * CLI: php check_heic_support.php
 * Web: http://your-domain/scripts/maintenance/check_heic_support.php
 */

// CLI実行時の設定
if (php_sapi_name() === 'cli') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    // Web実行時はHTMLで出力
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>HEIC変換サポート確認</title>";
    echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}";
    echo ".section{background:white;padding:15px;margin:10px 0;border-radius:5px;}";
    echo ".ok{color:green;}.ng{color:red;}.title{font-size:18px;font-weight:bold;margin-bottom:10px;}";
    echo "pre{background:#f0f0f0;padding:10px;overflow-x:auto;}</style></head><body>";
}

function output($text, $isHtml = false) {
    if (php_sapi_name() === 'cli') {
        echo $text . "\n";
    } else {
        if ($isHtml) {
            echo $text;
        } else {
            echo nl2br(htmlspecialchars($text)) . "\n";
        }
    }
}

function sectionStart($title) {
    if (php_sapi_name() !== 'cli') {
        echo "<div class='section'><div class='title'>{$title}</div>";
    } else {
        output("===========================================");
        output($title);
        output("===========================================");
    }
}

function sectionEnd() {
    if (php_sapi_name() !== 'cli') {
        echo "</div>";
    }
    output("");
}

output("HEIC変換サポート確認スクリプト");
output(str_repeat("=", 50));
output("");

// 1. FFmpegの確認
sectionStart("1. FFmpeg確認");

// ローカルのffmpegをチェック
$localFfmpeg = __DIR__ . '/../../ffmpeg/ffmpeg';
$ffmpegCmd = null;

if (file_exists($localFfmpeg)) {
    output("✓ ローカルFFmpeg検出: {$localFfmpeg}");

    if (is_executable($localFfmpeg)) {
        output("  実行権限: あり");
        $ffmpegCmd = $localFfmpeg;
    } else {
        output("  実行権限: なし（chmod +x が必要）");
        // 試しに実行してみる
        $ffmpegCmd = $localFfmpeg;
    }

    exec("{$ffmpegCmd} -version 2>&1", $ffmpegVersion, $versionReturn);
    if ($versionReturn === 0) {
        output("  バージョン: " . ($ffmpegVersion[0] ?? 'N/A'));

        exec("{$ffmpegCmd} -codecs 2>&1 | grep hevc", $hevcSupport);
        if (!empty($hevcSupport)) {
            output("  ✓ HEVCコーデックサポート: あり");
            foreach ($hevcSupport as $line) {
                output("    " . trim($line));
            }
        } else {
            output("  ✗ HEVCコーデックサポート: なし");
        }

        exec("{$ffmpegCmd} -formats 2>&1 | grep heif", $heifSupport);
        if (!empty($heifSupport)) {
            output("  ✓ HEIFフォーマットサポート: あり");
            foreach ($heifSupport as $line) {
                output("    " . trim($line));
            }
        } else {
            output("  ✗ HEIFフォーマットサポート: なし");
        }
    } else {
        output("  ✗ 実行エラー: " . implode("\n  ", $ffmpegVersion));
    }
} else {
    output("✗ ローカルFFmpegなし: {$localFfmpeg}");
}

// システムのffmpegをチェック
output("");
exec('which ffmpeg 2>&1', $systemFfmpegPath, $ffmpegReturn);
if ($ffmpegReturn === 0 && !empty($systemFfmpegPath)) {
    output("✓ システムFFmpegインストール済み: " . $systemFfmpegPath[0]);

    exec('ffmpeg -version 2>&1', $ffmpegVersion);
    output("  バージョン: " . ($ffmpegVersion[0] ?? 'N/A'));

    exec('ffmpeg -codecs 2>&1 | grep hevc', $hevcSupport);
    if (!empty($hevcSupport)) {
        output("  ✓ HEVCコーデックサポート: あり");
        output("    " . implode("\n    ", $hevcSupport));
    } else {
        output("  ✗ HEVCコーデックサポート: なし");
    }

    exec('ffmpeg -formats 2>&1 | grep heif', $heifSupport);
    if (!empty($heifSupport)) {
        output("  ✓ HEIFフォーマットサポート: あり");
        output("    " . implode("\n    ", $heifSupport));
    } else {
        output("  ✗ HEIFフォーマットサポート: なし");
    }
} else {
    output("✗ システムFFmpegがインストールされていません");
}
sectionEnd();

// 2. ImageMagickの確認
sectionStart("2. ImageMagick確認");
exec('which convert 2>&1', $convertPath, $convertReturn);
if ($convertReturn === 0 && !empty($convertPath)) {
    output("✓ ImageMagickインストール済み: " . $convertPath[0]);

    exec('convert -version 2>&1', $convertVersion);
    output("バージョン: " . ($convertVersion[0] ?? 'N/A'));

    exec('convert -list configure 2>&1 | grep DELEGATES', $delegates);
    if (!empty($delegates)) {
        output("デリゲート設定:");
        output("  " . implode("\n  ", $delegates));
    }

    exec('convert -list format 2>&1 | grep -i heic', $heicFormat);
    if (!empty($heicFormat)) {
        output("✓ HEICフォーマットサポート: あり");
        output("  " . implode("\n  ", $heicFormat));
    } else {
        output("✗ HEICフォーマットサポート: なし");
    }
} else {
    output("✗ ImageMagickがインストールされていません");
}
sectionEnd();

// 3. heif-convertの確認
sectionStart("3. heif-convert確認");
exec('which heif-convert 2>&1', $heifConvertPath, $heifConvertReturn);
if ($heifConvertReturn === 0 && !empty($heifConvertPath)) {
    output("✓ heif-convertインストール済み: " . $heifConvertPath[0]);
    exec('heif-convert --version 2>&1', $heifConvertVersion);
    if (!empty($heifConvertVersion)) {
        output("バージョン: " . implode("\n", $heifConvertVersion));
    }
} else {
    output("✗ heif-convertがインストールされていません");
}
sectionEnd();

// 4. PHP Imagick拡張の確認
sectionStart("4. PHP Imagick拡張確認");
if (extension_loaded('imagick')) {
    output("✓ PHP Imagick拡張インストール済み");

    $imagick = new Imagick();
    $version = $imagick->getVersion();
    output("バージョン: " . ($version['versionString'] ?? 'N/A'));

    $formats = $imagick->queryFormats();
    if (in_array('HEIC', $formats)) {
        output("✓ HEIC形式サポート: あり");
    } else {
        output("✗ HEIC形式サポート: なし");
    }

    // サポートされている形式の一部を表示
    $relevantFormats = array_filter($formats, function($format) {
        return in_array($format, ['HEIC', 'HEIF', 'JPEG', 'JPG', 'PNG', 'WEBP']);
    });
    output("サポート形式（抜粋）: " . implode(', ', $relevantFormats));
} else {
    output("✗ PHP Imagick拡張がインストールされていません");
}
sectionEnd();

// 5. PHP FFI拡張とlibheifの確認
sectionStart("5. PHP FFI拡張とlibheif確認");
if (extension_loaded('ffi')) {
    output("✓ PHP FFI拡張インストール済み");

    $libheifPaths = [
        '/usr/lib/x86_64-linux-gnu/libheif.so',
        '/usr/local/lib/libheif.so',
        '/usr/lib/libheif.so',
        '/opt/local/lib/libheif.dylib', // macOS
    ];

    $libheifFound = false;
    foreach ($libheifPaths as $path) {
        if (file_exists($path)) {
            output("✓ libheifライブラリ検出: {$path}");
            $libheifFound = true;
            break;
        }
    }

    if (!$libheifFound) {
        output("✗ libheifライブラリが見つかりません");
        output("  検索パス: " . implode(', ', $libheifPaths));
    }
} else {
    output("✗ PHP FFI拡張がインストールされていません");
}
sectionEnd();

// 6. 実際のHEIC変換テスト（heic_converter.phpを使用）
sectionStart("6. HEIC変換テスト");
$heicConverterPath = __DIR__ . '/../../includes/heic_converter.php';
if (file_exists($heicConverterPath)) {
    require_once $heicConverterPath;
    output("✓ heic_converter.php読み込み成功");

    // FFmpegパスの確認
    if (function_exists('getFFmpegPath')) {
        $ffmpegPath = getFFmpegPath();
        if ($ffmpegPath !== null) {
            output("✓ getFFmpegPath(): {$ffmpegPath}");
        } else {
            output("✗ getFFmpegPath(): FFmpeg利用不可");
        }
    }

    // 利用可能な変換メソッドをチェック
    $methods = [
        'FFmpeg' => 'convertHeicWithFFmpeg',
        'FFI+libheif' => 'convertHeicWithLibheif',
        'Imagick' => 'convertHeicWithImagick',
        'ImageMagick' => 'convertHeicWithCommand',
        'heif-convert' => 'convertHeicWithHeifConvert'
    ];

    output("利用可能な変換メソッド:");
    foreach ($methods as $name => $function) {
        if (function_exists($function)) {
            output("  ✓ {$name}");
        } else {
            output("  ✗ {$name} (関数未定義)");
        }
    }
} else {
    output("✗ heic_converter.phpが見つかりません: {$heicConverterPath}");
}
sectionEnd();

// 7. 推奨事項
sectionStart("推奨事項");
output("HEIC変換を有効にするには、以下のいずれかが必要です:");
output("1. FFmpeg + libx265 (HEVCコーデック)");
output("2. ImageMagick + libheif delegate");
output("3. PHP Imagick拡張 + libheif");
output("4. heif-convert (libheif-examples パッケージ)");
output("");
output("現在の対策:");
output("- サーバー側で変換できない場合は、クライアント側（ブラウザ）でheic2anyを使用して変換");
output("- process_pending_thumbnails.phpは変換失敗時に元のHEICファイルをthumbnail_pathに設定");
sectionEnd();

if (php_sapi_name() !== 'cli') {
    echo "</body></html>";
}
?>
