<?php
/**
 * 画像サムネイル生成ヘルパー
 * 画像ファイルからサムネイルを生成
 */

// EXIF情報処理用のヘルパーを読み込み
require_once __DIR__ . '/exif_helper.php';

/**
 * 画像からサムネイルを生成
 * @param string $imagePath 元の画像ファイルパス
 * @param string $thumbnailPath 出力サムネイルパス
 * @param int $width サムネイルの幅（デフォルト: 400px）
 * @param int $quality JPEG品質（デフォルト: 85）
 * @return bool 成功したかどうか
 */
function generateImageThumbnail($imagePath, $thumbnailPath, $width = 400, $quality = 85) {
    // ファイルが存在するか確認
    if (!file_exists($imagePath)) {
        error_log("Image file not found: {$imagePath}");
        return false;
    }

    // MIMEタイプを取得
    $imageInfo = getimagesize($imagePath);
    if ($imageInfo === false) {
        error_log("Failed to get image info: {$imagePath}");
        return false;
    }

    $mimeType = $imageInfo['mime'];
    $sourceWidth = $imageInfo[0];
    $sourceHeight = $imageInfo[1];

    // サムネイル生成方法を選択
    if (class_exists('Imagick')) {
        return generateThumbnailWithImagick($imagePath, $thumbnailPath, $width, $quality);
    } else {
        return generateThumbnailWithGD($imagePath, $thumbnailPath, $mimeType, $sourceWidth, $sourceHeight, $width, $quality);
    }
}

/**
 * Imagickを使用してサムネイルを生成
 * @param string $imagePath 元の画像ファイルパス
 * @param string $thumbnailPath 出力サムネイルパス
 * @param int $width サムネイルの幅
 * @param int $quality JPEG品質
 * @return bool 成功したかどうか
 */
function generateThumbnailWithImagick($imagePath, $thumbnailPath, $width, $quality) {
    try {
        $imagick = new Imagick($imagePath);

        // EXIF情報に基づいて画像を自動回転
        $imagick->autoOrient();

        // アスペクト比を保ったままリサイズ
        $imagick->thumbnailImage($width, 0);

        // JPEG形式で保存
        $imagick->setImageFormat('jpeg');
        $imagick->setImageCompressionQuality($quality);

        $result = $imagick->writeImage($thumbnailPath);
        $imagick->clear();
        $imagick->destroy();

        if ($result && file_exists($thumbnailPath)) {
            error_log("Thumbnail generated using Imagick: {$thumbnailPath}");
            return true;
        }

        return false;
    } catch (Exception $e) {
        error_log("Imagick thumbnail generation error: " . $e->getMessage());
        return false;
    }
}

/**
 * GDライブラリを使用してサムネイルを生成
 * @param string $imagePath 元の画像ファイルパス
 * @param string $thumbnailPath 出力サムネイルパス
 * @param string $mimeType MIMEタイプ
 * @param int $sourceWidth 元の画像の幅
 * @param int $sourceHeight 元の画像の高さ
 * @param int $width サムネイルの幅
 * @param int $quality JPEG品質
 * @return bool 成功したかどうか
 */
function generateThumbnailWithGD($imagePath, $thumbnailPath, $mimeType, $sourceWidth, $sourceHeight, $width, $quality) {
    try {
        // 画像リソースを作成
        $sourceImage = null;
        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($imagePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($imagePath);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($imagePath);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $sourceImage = imagecreatefromwebp($imagePath);
                }
                break;
            default:
                error_log("Unsupported image type for GD: {$mimeType}");
                return false;
        }

        if ($sourceImage === false || $sourceImage === null) {
            error_log("Failed to create image resource from: {$imagePath}");
            return false;
        }

        // アスペクト比を保ったまま新しいサイズを計算
        $aspectRatio = $sourceHeight / $sourceWidth;
        $thumbnailWidth = $width;
        $thumbnailHeight = (int)($width * $aspectRatio);

        // サムネイル画像リソースを作成
        $thumbnailImage = imagecreatetruecolor($thumbnailWidth, $thumbnailHeight);
        if ($thumbnailImage === false) {
            imagedestroy($sourceImage);
            error_log("Failed to create thumbnail image resource");
            return false;
        }

        // PNG/GIFの透明度を保持
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($thumbnailImage, false);
            imagesavealpha($thumbnailImage, true);
            $transparent = imagecolorallocatealpha($thumbnailImage, 255, 255, 255, 127);
            imagefilledrectangle($thumbnailImage, 0, 0, $thumbnailWidth, $thumbnailHeight, $transparent);
        }

        // リサイズ
        $result = imagecopyresampled(
            $thumbnailImage,
            $sourceImage,
            0, 0, 0, 0,
            $thumbnailWidth,
            $thumbnailHeight,
            $sourceWidth,
            $sourceHeight
        );

        if (!$result) {
            imagedestroy($sourceImage);
            imagedestroy($thumbnailImage);
            error_log("Failed to resample image");
            return false;
        }

        // EXIF情報に基づいて回転（元画像のEXIF情報を使用）
        $rotation = getRotationFromExif($imagePath);
        if ($rotation !== 0) {
            // imagerotateは反時計回りなので角度を負にする
            $rotatedImage = imagerotate($thumbnailImage, -$rotation, 0);
            if ($rotatedImage !== false) {
                imagedestroy($thumbnailImage);
                $thumbnailImage = $rotatedImage;
                error_log("GD thumbnail rotated by {$rotation} degrees based on EXIF");
            }
        }

        // プログレッシブJPEGとして保存（段階的に表示されて体感速度向上）
        imageinterlace($thumbnailImage, 1);
        $saveResult = imagejpeg($thumbnailImage, $thumbnailPath, $quality);

        // メモリ解放
        imagedestroy($sourceImage);
        imagedestroy($thumbnailImage);

        if ($saveResult && file_exists($thumbnailPath)) {
            error_log("Thumbnail generated using GD: {$thumbnailPath}");
            return true;
        }

        return false;
    } catch (Exception $e) {
        error_log("GD thumbnail generation error: " . $e->getMessage());
        return false;
    }
}

/**
 * WebP形式のサムネイルを生成（オプション）
 * @param string $imagePath 元の画像ファイルパス
 * @param string $webpPath 出力WebPパス
 * @param int $width サムネイルの幅（デフォルト: 400px）
 * @param int $quality WebP品質（デフォルト: 85）
 * @return bool 成功したかどうか
 */
function generateWebPThumbnail($imagePath, $webpPath, $width = 400, $quality = 85) {
    // WebP対応チェック
    if (!function_exists('imagewebp')) {
        error_log("WebP support not available");
        return false;
    }

    // ファイルが存在するか確認
    if (!file_exists($imagePath)) {
        error_log("Image file not found: {$imagePath}");
        return false;
    }

    // 画像情報を取得
    $imageInfo = getimagesize($imagePath);
    if ($imageInfo === false) {
        error_log("Failed to get image info: {$imagePath}");
        return false;
    }

    $mimeType = $imageInfo['mime'];
    $sourceWidth = $imageInfo[0];
    $sourceHeight = $imageInfo[1];

    try {
        // 画像リソースを作成
        $sourceImage = null;
        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($imagePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($imagePath);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($imagePath);
                break;
            case 'image/webp':
                $sourceImage = imagecreatefromwebp($imagePath);
                break;
            default:
                error_log("Unsupported image type for WebP: {$mimeType}");
                return false;
        }

        if ($sourceImage === false || $sourceImage === null) {
            error_log("Failed to create image resource from: {$imagePath}");
            return false;
        }

        // アスペクト比を保ったまま新しいサイズを計算
        $aspectRatio = $sourceHeight / $sourceWidth;
        $thumbnailWidth = min($width, $sourceWidth); // 元画像より大きくしない
        $thumbnailHeight = (int)($thumbnailWidth * $aspectRatio);

        // サムネイル画像リソースを作成
        $thumbnailImage = imagecreatetruecolor($thumbnailWidth, $thumbnailHeight);
        if ($thumbnailImage === false) {
            imagedestroy($sourceImage);
            error_log("Failed to create thumbnail image resource");
            return false;
        }

        // PNG/GIFの透明度を保持
        imagealphablending($thumbnailImage, false);
        imagesavealpha($thumbnailImage, true);

        // リサイズ
        $result = imagecopyresampled(
            $thumbnailImage,
            $sourceImage,
            0, 0, 0, 0,
            $thumbnailWidth,
            $thumbnailHeight,
            $sourceWidth,
            $sourceHeight
        );

        if (!$result) {
            imagedestroy($sourceImage);
            imagedestroy($thumbnailImage);
            error_log("Failed to resample image");
            return false;
        }

        // EXIF情報に基づいて回転（元画像のEXIF情報を使用）
        $rotation = getRotationFromExif($imagePath);
        if ($rotation !== 0) {
            // imagerotateは反時計回りなので角度を負にする
            $rotatedImage = imagerotate($thumbnailImage, -$rotation, 0);
            if ($rotatedImage !== false) {
                imagedestroy($thumbnailImage);
                $thumbnailImage = $rotatedImage;
                error_log("WebP thumbnail rotated by {$rotation} degrees based on EXIF");
            }
        }

        // WebP形式で保存
        $saveResult = imagewebp($thumbnailImage, $webpPath, $quality);

        // メモリ解放
        imagedestroy($sourceImage);
        imagedestroy($thumbnailImage);

        if ($saveResult && file_exists($webpPath)) {
            error_log("WebP thumbnail generated: {$webpPath}");
            return true;
        }

        return false;
    } catch (Exception $e) {
        error_log("WebP thumbnail generation error: " . $e->getMessage());
        return false;
    }
}

/**
 * 最適化されたサムネイルを生成（JPEG + WebP）
 * @param string $imagePath 元の画像ファイルパス
 * @param string $thumbnailPath 出力JPEGサムネイルパス
 * @param int $width サムネイルの幅（デフォルト: 400px）
 * @param int $quality 品質（デフォルト: 85）
 * @param bool $generateWebP WebP版も生成するか（デフォルト: false）
 * @return array 生成されたファイルパス ['jpeg' => path, 'webp' => path or null]
 */
function generateOptimizedThumbnail($imagePath, $thumbnailPath, $width = 400, $quality = 85, $generateWebP = false) {
    $result = [
        'jpeg' => null,
        'webp' => null,
        'success' => false
    ];

    // JPEGサムネイルを生成
    if (generateImageThumbnail($imagePath, $thumbnailPath, $width, $quality)) {
        $result['jpeg'] = $thumbnailPath;
        $result['success'] = true;
    }

    // WebP版も生成（オプション）
    if ($generateWebP && $result['success']) {
        $webpPath = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $thumbnailPath);
        if (generateWebPThumbnail($imagePath, $webpPath, $width, $quality)) {
            $result['webp'] = $webpPath;
        }
    }

    return $result;
}

/**
 * サムネイル生成が利用可能かチェック
 * @return bool 利用可能かどうか
 */
function isThumbnailGenerationAvailable() {
    // Imagick または GD が利用可能ならOK
    if (class_exists('Imagick')) {
        return true;
    }

    if (function_exists('imagecreatefromjpeg')) {
        return true;
    }

    return false;
}

/**
 * WebP対応チェック
 * @return bool WebPが利用可能かどうか
 */
function isWebPSupported() {
    return function_exists('imagewebp');
}
?>
