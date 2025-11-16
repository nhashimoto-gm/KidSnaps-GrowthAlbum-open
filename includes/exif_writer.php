<?php
/**
 * EXIF Writer Helper - 画像ファイルにEXIF情報を書き込む
 * PEL (PHP Exif Library) を使用
 *
 * Note: このファイルは composer.json の autoload で自動読み込みされるため、
 * vendor/autoload.php は既に読み込まれています。
 */

use lsolesen\pel\PelJpeg;
use lsolesen\pel\PelTiff;
use lsolesen\pel\PelIfd;
use lsolesen\pel\PelTag;
use lsolesen\pel\PelEntryAscii;
use lsolesen\pel\PelEntryTime;
use lsolesen\pel\PelEntryRational;
use lsolesen\pel\PelEntryShort;
use lsolesen\pel\PelEntrySRational;

/**
 * 画像ファイルにEXIF情報を書き込む
 *
 * @param string $filePath 画像ファイルのパス
 * @param array $exifData 書き込むEXIF情報
 *   - datetime: string|null 撮影日時 (MySQL DATETIME format: "YYYY-MM-DD HH:MM:SS")
 *   - latitude: float|null GPS緯度
 *   - longitude: float|null GPS経度
 *   - camera_make: string|null カメラメーカー
 *   - camera_model: string|null カメラモデル
 *   - orientation: int|null 画像の向き (1-8)
 * @return bool 成功したかどうか
 * @throws Exception EXIF書き込みに失敗した場合
 */
function writeExifToFile($filePath, $exifData) {
    // ファイルが存在するかチェック
    if (!file_exists($filePath)) {
        throw new Exception("File not found: {$filePath}");
    }

    // JPEGファイルかチェック
    $imageInfo = @getimagesize($filePath);
    if (!$imageInfo || $imageInfo[2] !== IMAGETYPE_JPEG) {
        throw new Exception("Only JPEG files are supported for EXIF writing");
    }

    try {
        // JPEGファイルを読み込み
        $data = file_get_contents($filePath);
        $jpeg = new PelJpeg();
        $jpeg->load($data);

        // 既存のEXIFデータを取得、なければ新規作成
        $exif = $jpeg->getExif();
        if ($exif === null) {
            $exif = new PelTiff();
            $jpeg->setExif($exif);
        }

        // IFD0 (メインイメージの情報) を取得または作成
        $ifd0 = $exif->getIfd();
        if ($ifd0 === null) {
            $ifd0 = new PelIfd(PelIfd::IFD0);
            $exif->setIfd($ifd0);
        }

        // EXIF IFD (詳細なEXIF情報) を取得または作成
        $exifIfd = $ifd0->getSubIfd(PelIfd::EXIF);
        if ($exifIfd === null) {
            $exifIfd = new PelIfd(PelIfd::EXIF);
            $ifd0->addSubIfd($exifIfd);
        }

        // GPS IFD (GPS情報) を取得または作成
        $gpsIfd = $ifd0->getSubIfd(PelIfd::GPS);
        if ($gpsIfd === null) {
            $gpsIfd = new PelIfd(PelIfd::GPS);
            $ifd0->addSubIfd($gpsIfd);
        }

        // カメラメーカーの書き込み
        if (isset($exifData['camera_make']) && !empty($exifData['camera_make'])) {
            $ifd0->addEntry(new PelEntryAscii(PelTag::MAKE, $exifData['camera_make']));
        }

        // カメラモデルの書き込み
        if (isset($exifData['camera_model']) && !empty($exifData['camera_model'])) {
            $ifd0->addEntry(new PelEntryAscii(PelTag::MODEL, $exifData['camera_model']));
        }

        // 画像の向きの書き込み
        if (isset($exifData['orientation']) && $exifData['orientation'] >= 1 && $exifData['orientation'] <= 8) {
            $ifd0->addEntry(new PelEntryShort(PelTag::ORIENTATION, $exifData['orientation']));
        }

        // 撮影日時の書き込み
        if (isset($exifData['datetime']) && !empty($exifData['datetime'])) {
            // MySQL DATETIME format ("YYYY-MM-DD HH:MM:SS") を EXIF format ("YYYY:MM:DD HH:MM:SS") に変換
            $exifDatetime = convertMySQLDatetimeToExif($exifData['datetime']);

            if ($exifDatetime) {
                // DateTimeOriginal (オリジナル撮影日時)
                $exifIfd->addEntry(new PelEntryTime(PelTag::DATE_TIME_ORIGINAL, $exifDatetime));

                // DateTimeDigitized (デジタル化日時)
                $exifIfd->addEntry(new PelEntryTime(PelTag::DATE_TIME_DIGITIZED, $exifDatetime));

                // DateTime (ファイル変更日時)
                $ifd0->addEntry(new PelEntryTime(PelTag::DATE_TIME, $exifDatetime));
            }
        }

        // GPS情報の書き込み
        if (isset($exifData['latitude']) && isset($exifData['longitude']) &&
            !empty($exifData['latitude']) && !empty($exifData['longitude'])) {

            // 緯度の書き込み
            $latitudeData = convertDecimalToGPS($exifData['latitude']);
            $gpsIfd->addEntry(new PelEntryRational(
                PelTag::GPS_LATITUDE,
                [$latitudeData['degrees'], 1],
                [$latitudeData['minutes'], 1],
                [$latitudeData['seconds'], 100]
            ));

            // 緯度の方向 (N/S)
            $latRef = $exifData['latitude'] >= 0 ? 'N' : 'S';
            $gpsIfd->addEntry(new PelEntryAscii(PelTag::GPS_LATITUDE_REF, $latRef));

            // 経度の書き込み
            $longitudeData = convertDecimalToGPS($exifData['longitude']);
            $gpsIfd->addEntry(new PelEntryRational(
                PelTag::GPS_LONGITUDE,
                [$longitudeData['degrees'], 1],
                [$longitudeData['minutes'], 1],
                [$longitudeData['seconds'], 100]
            ));

            // 経度の方向 (E/W)
            $lonRef = $exifData['longitude'] >= 0 ? 'E' : 'W';
            $gpsIfd->addEntry(new PelEntryAscii(PelTag::GPS_LONGITUDE_REF, $lonRef));
        }

        // 変更をファイルに保存
        // オリジナルファイルのバックアップを作成
        $backupPath = $filePath . '.backup';
        if (!copy($filePath, $backupPath)) {
            throw new Exception("Failed to create backup file");
        }

        try {
            // 新しいEXIFデータを含むJPEGファイルを保存
            file_put_contents($filePath, $jpeg->getBytes());

            // バックアップファイルを削除
            @unlink($backupPath);

            return true;
        } catch (Exception $e) {
            // エラーが発生した場合、バックアップから復元
            if (file_exists($backupPath)) {
                copy($backupPath, $filePath);
                @unlink($backupPath);
            }
            throw $e;
        }

    } catch (Exception $e) {
        error_log("EXIF write error: " . $e->getMessage());
        throw new Exception("Failed to write EXIF data: " . $e->getMessage());
    }
}

/**
 * MySQL DATETIME形式をEXIF形式に変換
 * @param string $mysqlDatetime MySQL DATETIME形式 ("YYYY-MM-DD HH:MM:SS")
 * @return int|null Unixタイムスタンプ
 */
function convertMySQLDatetimeToExif($mysqlDatetime) {
    if (empty($mysqlDatetime)) {
        return null;
    }

    $timestamp = strtotime($mysqlDatetime);
    if ($timestamp === false) {
        return null;
    }

    return $timestamp;
}

/**
 * 10進数の座標を度分秒形式に変換
 * @param float $decimal 10進数の座標
 * @return array ['degrees' => int, 'minutes' => int, 'seconds' => int]
 */
function convertDecimalToGPS($decimal) {
    $decimal = abs($decimal);

    $degrees = floor($decimal);
    $minutesDecimal = ($decimal - $degrees) * 60;
    $minutes = floor($minutesDecimal);
    $seconds = round(($minutesDecimal - $minutes) * 60 * 100); // 小数点以下2桁まで (x100)

    return [
        'degrees' => (int)$degrees,
        'minutes' => (int)$minutes,
        'seconds' => (int)$seconds
    ];
}

/**
 * 画像ファイルにEXIFデータがあるかチェック
 * @param string $filePath 画像ファイルのパス
 * @return bool EXIFデータが存在する場合true
 */
function hasExifData($filePath) {
    if (!file_exists($filePath)) {
        return false;
    }

    // exif拡張が利用可能かチェック
    if (!function_exists('exif_read_data')) {
        return false;
    }

    $exif = @exif_read_data($filePath, 0, true);

    // EXIF情報が存在し、かつ空でないかチェック
    return $exif !== false && !empty($exif) && (
        isset($exif['EXIF']) ||
        isset($exif['IFD0']) ||
        isset($exif['GPS'])
    );
}

/**
 * サムネイルを再生成（EXIF書き込み後）
 * @param string $originalPath オリジナル画像のパス
 * @param string $thumbnailPath サムネイル画像のパス
 * @return bool 成功したかどうか
 */
function regenerateThumbnailAfterExifWrite($originalPath, $thumbnailPath) {
    require_once __DIR__ . '/image_thumbnail_helper.php';

    try {
        // サムネイルを再生成
        return generateImageThumbnail($originalPath, $thumbnailPath, 400, 85);
    } catch (Exception $e) {
        error_log("Thumbnail regeneration error: " . $e->getMessage());
        return false;
    }
}
