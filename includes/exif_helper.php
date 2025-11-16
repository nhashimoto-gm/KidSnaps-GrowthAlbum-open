<?php
/**
 * EXIF Helper - 画像のEXIF情報から回転角度を取得
 */

/**
 * 画像ファイルのEXIF Orientationから回転角度を取得
 * @param string $filePath 画像ファイルのパス
 * @return int 回転角度 (0, 90, 180, 270)
 */
function getRotationFromExif($filePath) {
    // exif拡張が利用可能かチェック
    if (!function_exists('exif_read_data')) {
        error_log('EXIF functions not available');
        return 0;
    }

    // ファイルが存在するかチェック
    if (!file_exists($filePath)) {
        error_log("File not found: {$filePath}");
        return 0;
    }

    // EXIF情報を読み取り
    $exif = @exif_read_data($filePath);

    if (!$exif || !isset($exif['Orientation'])) {
        return 0; // EXIF情報がない、またはOrientation情報がない
    }

    // Orientation値から回転角度を決定
    // EXIF Orientation values:
    // 1 = 0 degrees: the correct orientation, no adjustment is required.
    // 2 = 0 degrees, mirrored: image has been flipped back-to-front.
    // 3 = 180 degrees: image is upside down.
    // 4 = 180 degrees, mirrored: image is upside down and flipped back-to-front.
    // 5 = 90 degrees: image is on its side.
    // 6 = 90 degrees, mirrored: image is on its side and flipped back-to-front.
    // 7 = 270 degrees: image is on its far side.
    // 8 = 270 degrees, mirrored: image is on its far side and flipped back-to-front.

    switch ($exif['Orientation']) {
        case 3:
            return 180;
        case 6:
            return 90;
        case 8:
            return 270;
        default:
            return 0;
    }
}

/**
 * EXIF Orientationに基づいて画像を物理的に回転
 * （オプション：サーバー側で画像を自動回転させる場合）
 * @param string $filePath 画像ファイルのパス
 * @return bool 成功したかどうか
 */
function autoRotateImageByExif($filePath) {
    // GD拡張が利用可能かチェック
    if (!function_exists('imagecreatefromjpeg')) {
        error_log('GD functions not available');
        return false;
    }

    $rotation = getRotationFromExif($filePath);

    if ($rotation === 0) {
        return true; // 回転不要
    }

    // 画像の読み込み
    $imageInfo = getimagesize($filePath);
    if (!$imageInfo) {
        return false;
    }

    $mimeType = $imageInfo['mime'];
    $image = null;

    switch ($mimeType) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($filePath);
            break;
        case 'image/png':
            $image = imagecreatefrompng($filePath);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($filePath);
            break;
        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) {
                $image = imagecreatefromwebp($filePath);
            }
            break;
        default:
            return false;
    }

    if (!$image) {
        return false;
    }

    // 画像を回転（反時計回りに回転するので、角度を反転）
    $rotatedImage = imagerotate($image, -$rotation, 0);

    if (!$rotatedImage) {
        imagedestroy($image);
        return false;
    }

    // 回転した画像を保存
    $success = false;
    switch ($mimeType) {
        case 'image/jpeg':
            $success = imagejpeg($rotatedImage, $filePath, 90);
            break;
        case 'image/png':
            $success = imagepng($rotatedImage, $filePath, 9);
            break;
        case 'image/gif':
            $success = imagegif($rotatedImage, $filePath);
            break;
        case 'image/webp':
            if (function_exists('imagewebp')) {
                $success = imagewebp($rotatedImage, $filePath, 90);
            }
            break;
    }

    // メモリ解放
    imagedestroy($image);
    imagedestroy($rotatedImage);

    return $success;
}

/**
 * 画像ファイルから詳細なEXIF情報を取得
 * @param string $filePath 画像ファイルのパス
 * @return array EXIF情報の連想配列
 */
function getExifData($filePath) {
    $exifData = [
        'datetime' => null,
        'latitude' => null,
        'longitude' => null,
        'location_name' => null,
        'camera_make' => null,
        'camera_model' => null,
        'orientation' => 1
    ];

    // exif拡張が利用可能かチェック
    if (!function_exists('exif_read_data')) {
        error_log('EXIF functions not available');
        return $exifData;
    }

    // ファイルが存在するかチェック
    if (!file_exists($filePath)) {
        error_log("File not found: {$filePath}");
        return $exifData;
    }

    // EXIF情報を読み取り
    $exif = @exif_read_data($filePath, 0, true);

    if (!$exif) {
        return $exifData;
    }

    // 撮影日時の取得
    if (isset($exif['EXIF']['DateTimeOriginal'])) {
        $exifData['datetime'] = convertExifDatetime($exif['EXIF']['DateTimeOriginal']);
    } elseif (isset($exif['IFD0']['DateTime'])) {
        $exifData['datetime'] = convertExifDatetime($exif['IFD0']['DateTime']);
    }

    // GPS情報の取得
    if (isset($exif['GPS'])) {
        $gps = $exif['GPS'];

        if (isset($gps['GPSLatitude']) && isset($gps['GPSLatitudeRef']) &&
            isset($gps['GPSLongitude']) && isset($gps['GPSLongitudeRef'])) {

            $exifData['latitude'] = convertGPSCoordinate($gps['GPSLatitude'], $gps['GPSLatitudeRef']);
            $exifData['longitude'] = convertGPSCoordinate($gps['GPSLongitude'], $gps['GPSLongitudeRef']);
        }
    }

    // カメラメーカーとモデル
    if (isset($exif['IFD0']['Make'])) {
        $exifData['camera_make'] = trim($exif['IFD0']['Make']);
    }
    if (isset($exif['IFD0']['Model'])) {
        $exifData['camera_model'] = trim($exif['IFD0']['Model']);
    }

    // 画像の向き
    if (isset($exif['IFD0']['Orientation'])) {
        $exifData['orientation'] = $exif['IFD0']['Orientation'];
    }

    return $exifData;
}

/**
 * EXIF日時フォーマットをMySQLフォーマットに変換
 * @param string $exifDatetime EXIF日時 (例: "2024:01:15 14:30:45")
 * @return string|null MySQL DATETIME形式 (例: "2024-01-15 14:30:45")
 */
function convertExifDatetime($exifDatetime) {
    if (empty($exifDatetime)) {
        return null;
    }

    // EXIF形式: "YYYY:MM:DD HH:MM:SS" を MySQL形式: "YYYY-MM-DD HH:MM:SS" に変換
    // 最初の2つのコロンのみをハイフンに置換（日付部分のみ）
    $parts = explode(' ', $exifDatetime, 2);
    if (count($parts) === 2) {
        $datePart = str_replace(':', '-', $parts[0]);
        $datetime = $datePart . ' ' . $parts[1];
    } else {
        $datetime = str_replace(':', '-', $exifDatetime);
    }

    // 日時として有効かチェック
    $timestamp = strtotime($datetime);
    if ($timestamp === false) {
        return null;
    }

    return date('Y-m-d H:i:s', $timestamp);
}

/**
 * GPS座標を10進数形式に変換
 * @param array $coordinate GPS座標配列 (例: ["40/1", "26/1", "4620/100"])
 * @param string $hemisphere 方位 ("N", "S", "E", "W")
 * @return float|null 10進数の座標
 */
function convertGPSCoordinate($coordinate, $hemisphere) {
    if (!is_array($coordinate) || count($coordinate) < 3) {
        return null;
    }

    // 度、分、秒を取得
    $degrees = evaluateFraction($coordinate[0]);
    $minutes = evaluateFraction($coordinate[1]);
    $seconds = evaluateFraction($coordinate[2]);

    // 10進数に変換
    $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

    // 南半球または西半球の場合は負の値にする
    if ($hemisphere === 'S' || $hemisphere === 'W') {
        $decimal = -$decimal;
    }

    return $decimal;
}

/**
 * 分数形式の文字列を評価
 * @param string $fraction 分数 (例: "40/1" または "4620/100")
 * @return float 評価された値
 */
function evaluateFraction($fraction) {
    if (strpos($fraction, '/') === false) {
        return (float)$fraction;
    }

    $parts = explode('/', $fraction);
    if (count($parts) !== 2 || $parts[1] == 0) {
        return 0;
    }

    return (float)$parts[0] / (float)$parts[1];
}

/**
 * 緯度経度から位置情報名を取得（リバースジオコーディング）
 * OpenStreetMap Nominatimを使用（無料、利用規約に注意）
 * @param float $latitude 緯度
 * @param float $longitude 経度
 * @return string|null 位置情報名
 */
function getLocationName($latitude, $longitude) {
    if (empty($latitude) || empty($longitude)) {
        return null;
    }

    try {
        // OpenStreetMap Nominatim API
        // 利用規約: https://operations.osmfoundation.org/policies/nominatim/
        // - User-Agentを設定すること
        // - 1リクエスト/秒以下に制限すること
        // - 商用利用の場合は独自サーバーの構築を検討すること

        $url = sprintf(
            'https://nominatim.openstreetmap.org/reverse?format=json&lat=%s&lon=%s&zoom=18&addressdetails=1&accept-language=ja',
            $latitude,
            $longitude
        );

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: KidSnaps-GrowthAlbum/1.0 (Family Photo Album)',
                    'Accept: application/json'
                ],
                'timeout' => 5
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            error_log('Nominatim API request failed');
            return null;
        }

        $data = json_decode($response, true);

        if (!$data || !isset($data['address'])) {
            return null;
        }

        // 住所情報を組み立て（日本語の場合）
        $address = $data['address'];
        $locationParts = [];

        // 日本の住所フォーマット
        if (isset($address['country']) && $address['country'] === '日本') {
            // 都道府県
            if (isset($address['state'])) {
                $locationParts[] = $address['state'];
            }
            // 市区町村
            if (isset($address['city'])) {
                $locationParts[] = $address['city'];
            } elseif (isset($address['town'])) {
                $locationParts[] = $address['town'];
            } elseif (isset($address['village'])) {
                $locationParts[] = $address['village'];
            }
            // 地区
            if (isset($address['suburb'])) {
                $locationParts[] = $address['suburb'];
            }
        } else {
            // 海外の住所フォーマット
            if (isset($address['city'])) {
                $locationParts[] = $address['city'];
            } elseif (isset($address['town'])) {
                $locationParts[] = $address['town'];
            }
            if (isset($address['state'])) {
                $locationParts[] = $address['state'];
            }
            if (isset($address['country'])) {
                $locationParts[] = $address['country'];
            }
        }

        if (empty($locationParts)) {
            // display_nameをフォールバックとして使用
            return isset($data['display_name']) ? mb_substr($data['display_name'], 0, 100) : null;
        }

        return implode(', ', $locationParts);

    } catch (Exception $e) {
        error_log('Reverse geocoding error: ' . $e->getMessage());
        return null;
    }
}

/**
 * リバースジオコーディングのレート制限を管理
 * 1秒に1リクエスト以下に制限
 */
function applyRateLimitForGeocoding() {
    $lastRequestFile = sys_get_temp_dir() . '/kidsnaps_geocoding_last_request.txt';

    if (file_exists($lastRequestFile)) {
        $lastRequestTime = (float)file_get_contents($lastRequestFile);
        $timeSinceLastRequest = microtime(true) - $lastRequestTime;

        if ($timeSinceLastRequest < 1.0) {
            // 1秒未満の場合は待機
            usleep((int)((1.0 - $timeSinceLastRequest) * 1000000));
        }
    }

    // 現在の時刻を記録
    file_put_contents($lastRequestFile, microtime(true));
}
