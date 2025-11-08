<?php
/**
 * HEIC画像変換ヘルパー
 * HEICフォーマットをJPEGに変換
 */

/**
 * HEICファイルをJPEGに変換
 * @param string $heicPath HEICファイルのパス
 * @param string $jpegPath 出力JPEGファイルのパス
 * @return bool 成功したかどうか
 */
function convertHeicToJpeg($heicPath, $jpegPath) {
    // 1. FFI + libheif を試行（最も効率的）
    if (extension_loaded('ffi') && extension_loaded('gd')) {
        $result = convertHeicWithLibheif($heicPath, $jpegPath);
        if ($result) {
            return true;
        }
    }

    // 2. ImageMagickが利用可能かチェック
    if (class_exists('Imagick')) {
        $result = convertHeicWithImagick($heicPath, $jpegPath);
        if ($result) {
            return true;
        }
    }

    // 3. コマンドラインツールを使用
    if (isCommandAvailable('magick') || isCommandAvailable('convert')) {
        $result = convertHeicWithCommand($heicPath, $jpegPath);
        if ($result) {
            return true;
        }
    }

    // 4. heif-convertコマンドを試行
    if (isCommandAvailable('heif-convert')) {
        $result = convertHeicWithHeifConvert($heicPath, $jpegPath);
        if ($result) {
            return true;
        }
    }

    error_log('HEIC conversion not available: No conversion method found');
    return false;
}

/**
 * Imagick拡張を使用してHEICをJPEGに変換
 * @param string $heicPath HEICファイルのパス
 * @param string $jpegPath 出力JPEGファイルのパス
 * @return bool 成功したかどうか
 */
function convertHeicWithImagick($heicPath, $jpegPath) {
    try {
        $imagick = new Imagick();
        $imagick->readImage($heicPath);
        $imagick->setImageFormat('jpeg');
        $imagick->setImageCompressionQuality(90);
        $result = $imagick->writeImage($jpegPath);
        $imagick->clear();
        $imagick->destroy();

        if ($result && file_exists($jpegPath)) {
            error_log("HEIC converted to JPEG using Imagick: {$jpegPath}");
            return true;
        }

        return false;
    } catch (Exception $e) {
        error_log("Imagick HEIC conversion error: " . $e->getMessage());
        return false;
    }
}

/**
 * コマンドラインツールを使用してHEICをJPEGに変換
 * @param string $heicPath HEICファイルのパス
 * @param string $jpegPath 出力JPEGファイルのパス
 * @return bool 成功したかどうか
 */
function convertHeicWithCommand($heicPath, $jpegPath) {
    $command = '';

    if (isCommandAvailable('magick')) {
        // ImageMagick 7+
        $command = sprintf(
            'magick %s -quality 90 %s 2>&1',
            escapeshellarg($heicPath),
            escapeshellarg($jpegPath)
        );
    } elseif (isCommandAvailable('convert')) {
        // ImageMagick 6
        $command = sprintf(
            'convert %s -quality 90 %s 2>&1',
            escapeshellarg($heicPath),
            escapeshellarg($jpegPath)
        );
    }

    if (empty($command)) {
        return false;
    }

    exec($command, $output, $returnCode);

    if ($returnCode === 0 && file_exists($jpegPath)) {
        error_log("HEIC converted to JPEG using command: {$jpegPath}");
        return true;
    }

    error_log("Command HEIC conversion failed: " . implode("\n", $output));
    return false;
}

/**
 * libheifライブラリを使用してHEICをJPEGに変換（Python経由）
 * @param string $heicPath HEICファイルのパス
 * @param string $jpegPath 出力JPEGファイルのパス
 * @return bool 成功したかどうか
 */
function convertHeicWithLibheif($heicPath, $jpegPath) {
    try {
        // Pythonスクリプトのパス
        $scriptPath = __DIR__ . '/heic_to_jpeg.py';

        if (!file_exists($scriptPath)) {
            error_log("Python converter script not found: {$scriptPath}");
            return false;
        }

        // Python3が利用可能かチェック
        if (!isCommandAvailable('python3')) {
            error_log("Python3 not available");
            return false;
        }

        // Pythonスクリプトを実行
        $command = sprintf(
            'python3 %s %s %s 90 2>&1',
            escapeshellarg($scriptPath),
            escapeshellarg($heicPath),
            escapeshellarg($jpegPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode === 0 && file_exists($jpegPath)) {
            error_log("HEIC converted to JPEG using Python/libheif: {$jpegPath}");
            return true;
        }

        error_log("Python conversion failed: " . implode("\n", $output));
        return false;
    } catch (Exception $e) {
        error_log("libheif conversion error: " . $e->getMessage());
        return false;
    }
}

/**
 * heif-convertコマンドを使用してHEICをJPEGに変換
 * @param string $heicPath HEICファイルのパス
 * @param string $jpegPath 出力JPEGファイルのパス
 * @return bool 成功したかどうか
 */
function convertHeicWithHeifConvert($heicPath, $jpegPath) {
    $command = sprintf(
        'heif-convert -q 90 %s %s 2>&1',
        escapeshellarg($heicPath),
        escapeshellarg($jpegPath)
    );

    exec($command, $output, $returnCode);

    if ($returnCode === 0 && file_exists($jpegPath)) {
        error_log("HEIC converted to JPEG using heif-convert: {$jpegPath}");
        return true;
    }

    error_log("heif-convert failed: " . implode("\n", $output));
    return false;
}

/**
 * コマンドが利用可能かチェック
 * @param string $command コマンド名
 * @return bool 利用可能かどうか
 */
function isCommandAvailable($command) {
    $output = [];
    $returnCode = 0;

    // Windowsの場合
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        exec("where {$command} 2>nul", $output, $returnCode);
    } else {
        // Unix系
        exec("which {$command} 2>/dev/null", $output, $returnCode);
    }

    return $returnCode === 0 && !empty($output);
}

/**
 * ファイルがHEICフォーマットかチェック
 * @param string $filePath ファイルパス
 * @param string $mimeType MIMEタイプ
 * @return bool HEICフォーマットかどうか
 */
function isHeicFile($filePath, $mimeType = null) {
    // 拡張子チェック
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    if (in_array($extension, ['heic', 'heif'])) {
        return true;
    }

    // MIMEタイプチェック
    if ($mimeType && in_array($mimeType, ['image/heic', 'image/heif', 'image/heic-sequence', 'image/heif-sequence'])) {
        return true;
    }

    // ファイルシグネチャチェック（より正確）
    if (file_exists($filePath)) {
        $handle = fopen($filePath, 'rb');
        if ($handle) {
            fseek($handle, 4, SEEK_SET);
            $ftyp = fread($handle, 8);
            fclose($handle);

            // HEICファイルは 'ftypheic', 'ftypheif', 'ftypmif1' などを含む
            if (strpos($ftyp, 'heic') !== false || strpos($ftyp, 'heif') !== false || strpos($ftyp, 'mif1') !== false) {
                return true;
            }
        }
    }

    return false;
}

/**
 * HEIC変換が利用可能かチェック
 * @return bool 利用可能かどうか
 */
function isHeicConversionAvailable() {
    if (class_exists('Imagick')) {
        return true;
    }

    if (isCommandAvailable('magick') || isCommandAvailable('convert')) {
        return true;
    }

    return false;
}
?>
