<?php
/**
 * Google Photos メタデータヘルパー
 * Google PhotosからエクスポートされたJSONメタデータを解析
 */

/**
 * Google Photos JSONメタデータを解析
 *
 * @param string $jsonFilePath JSONファイルパス
 * @return array|null 解析結果、失敗時はnull
 */
function parseGooglePhotosMetadata($jsonFilePath) {
    if (!file_exists($jsonFilePath) || !is_readable($jsonFilePath)) {
        return null;
    }

    $jsonContent = file_get_contents($jsonFilePath);
    if ($jsonContent === false) {
        return null;
    }

    $metadata = json_decode($jsonContent, true);
    if ($metadata === null) {
        return null;
    }

    return $metadata;
}

/**
 * Google Photosメタデータから撮影日時を取得
 *
 * @param array $metadata Google Photosメタデータ
 * @return string|null 撮影日時（YYYY-MM-DD HH:MM:SS形式）
 */
function getPhotoTakenTimeFromMetadata($metadata) {
    if (!isset($metadata['photoTakenTime']['timestamp'])) {
        return null;
    }

    $timestamp = $metadata['photoTakenTime']['timestamp'];
    return date('Y-m-d H:i:s', $timestamp);
}

/**
 * Google Photosメタデータから位置情報を取得
 *
 * @param array $metadata Google Photosメタデータ
 * @return array|null ['latitude' => float, 'longitude' => float, 'altitude' => float|null]
 */
function getGeoDataFromMetadata($metadata) {
    // geoDataまたはgeoDataExifから取得（優先順位: geoDataExif > geoData）
    $geoData = null;

    if (isset($metadata['geoDataExif']) &&
        isset($metadata['geoDataExif']['latitude']) &&
        isset($metadata['geoDataExif']['longitude'])) {
        $geoData = $metadata['geoDataExif'];
    } elseif (isset($metadata['geoData']) &&
              isset($metadata['geoData']['latitude']) &&
              isset($metadata['geoData']['longitude'])) {
        $geoData = $metadata['geoData'];
    }

    if ($geoData === null) {
        return null;
    }

    return [
        'latitude' => (float)$geoData['latitude'],
        'longitude' => (float)$geoData['longitude'],
        'altitude' => isset($geoData['altitude']) ? (float)$geoData['altitude'] : null
    ];
}

/**
 * Google Photosメタデータから人物情報を取得
 *
 * @param array $metadata Google Photosメタデータ
 * @return array 人物名の配列
 */
function getPeopleFromMetadata($metadata) {
    if (!isset($metadata['people']) || !is_array($metadata['people'])) {
        return [];
    }

    $people = [];
    foreach ($metadata['people'] as $person) {
        if (isset($person['name']) && !empty($person['name'])) {
            $people[] = $person['name'];
        }
    }

    return $people;
}

/**
 * 特定の人物名でフィルタリング
 *
 * @param array $people 人物名の配列
 * @param array $targetNames フィルタリングする人物名の配列
 * @return bool 指定された人物が含まれている場合true
 */
function filterPeopleByNames($people, $targetNames) {
    if (empty($targetNames)) {
        return true; // フィルタなしの場合は全て許可
    }

    foreach ($people as $personName) {
        if (in_array($personName, $targetNames)) {
            return true;
        }
    }

    return false;
}

/**
 * Google Photosメタデータからタイトルを取得
 *
 * @param array $metadata Google Photosメタデータ
 * @return string|null タイトル（ファイル名）
 */
function getTitleFromMetadata($metadata) {
    return isset($metadata['title']) ? $metadata['title'] : null;
}

/**
 * Google Photosメタデータから説明を取得
 *
 * @param array $metadata Google Photosメタデータ
 * @return string|null 説明
 */
function getDescriptionFromMetadata($metadata) {
    if (!isset($metadata['description']) || empty($metadata['description'])) {
        return null;
    }

    return $metadata['description'];
}

/**
 * Google Photosメタデータからカメラ情報を取得
 *
 * @param array $metadata Google Photosメタデータ
 * @return array|null ['make' => string|null, 'model' => string|null]
 */
function getCameraInfoFromMetadata($metadata) {
    // Google Photosのメタデータにはカメラメーカー・モデル情報が直接含まれないことが多い
    // googlePhotosOrigin から推測可能な場合もある
    $cameraInfo = [
        'make' => null,
        'model' => null
    ];

    if (isset($metadata['googlePhotosOrigin']['mobileUpload']['deviceType'])) {
        $deviceType = $metadata['googlePhotosOrigin']['mobileUpload']['deviceType'];

        // デバイスタイプからメーカーを推測
        if (strpos($deviceType, 'IOS') !== false || strpos($deviceType, 'IPHONE') !== false) {
            $cameraInfo['make'] = 'Apple';
        } elseif (strpos($deviceType, 'ANDROID') !== false) {
            $cameraInfo['make'] = 'Android';
        }
    }

    return $cameraInfo;
}

/**
 * ファイル名に対応するJSONメタデータファイルを検索
 *
 * Google Photosのエクスポートでは、メディアファイルと同じディレクトリに
 * 同名のJSONファイル（拡張子が.json）が配置される
 *
 * 例:
 *   - IMG_2565.MOV -> IMG_2565.MOV.json
 *   - IMG_2565.MOV -> IMG_2565.json
 *   - 00[1].jpg -> 00[1].jpg.supplemental-metadata.json
 *
 * @param string $mediaFilePath メディアファイルのパス
 * @param string $extractDir ZIP展開ディレクトリ
 * @return string|null JSONファイルパス、見つからない場合はnull
 */
function findGooglePhotosJsonForMedia($mediaFilePath, $extractDir) {
    $baseName = basename($mediaFilePath);
    $dirName = dirname($mediaFilePath);

    // パターン1: ファイル名.拡張子.json（例: IMG_2565.MOV.json）
    $jsonPath1 = $dirName . '/' . $baseName . '.json';
    if (file_exists($jsonPath1)) {
        error_log("JSON検出: パターン1（{$baseName}.json）");
        return $jsonPath1;
    }

    // パターン2: ファイル名.拡張子.supplemental-metadata.json（例: 00[1].jpg.supplemental-metadata.json）
    $jsonPath2 = $dirName . '/' . $baseName . '.supplemental-metadata.json';
    if (file_exists($jsonPath2)) {
        error_log("JSON検出: パターン2（{$baseName}.supplemental-metadata.json）");
        return $jsonPath2;
    }

    // パターン3: ファイル名.json（例: IMG_2565.json）
    $baseNameWithoutExt = pathinfo($baseName, PATHINFO_FILENAME);
    $jsonPath3 = $dirName . '/' . $baseNameWithoutExt . '.json';
    if (file_exists($jsonPath3)) {
        error_log("JSON検出: パターン3（{$baseNameWithoutExt}.json）");
        return $jsonPath3;
    }

    // パターン4: 再帰的に検索（異なるディレクトリ構造の場合）
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($extractDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $jsonBaseName = $file->getFilename();

            // ファイル名.拡張子.jsonパターン
            if ($jsonBaseName === $baseName . '.json') {
                error_log("JSON検出: パターン4-1（再帰検索: {$baseName}.json）");
                return $file->getPathname();
            }

            // ファイル名.拡張子.supplemental-metadata.jsonパターン
            if ($jsonBaseName === $baseName . '.supplemental-metadata.json') {
                error_log("JSON検出: パターン4-2（再帰検索: {$baseName}.supplemental-metadata.json）");
                return $file->getPathname();
            }

            // ファイル名.jsonパターン
            if ($jsonBaseName === $baseNameWithoutExt . '.json') {
                error_log("JSON検出: パターン4-3（再帰検索: {$baseNameWithoutExt}.json）");
                return $file->getPathname();
            }
        }
    }

    return null;
}

/**
 * ZIP内の全てのGoogle Photos JSONメタデータをマッピング
 *
 * @param string $extractDir ZIP展開ディレクトリ
 * @return array ['ファイル名' => 'JSONパス']のマップ
 */
function mapAllGooglePhotosJson($extractDir) {
    $jsonMap = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($extractDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && strtolower($file->getExtension()) === 'json') {
            $jsonPath = $file->getPathname();
            $jsonFileName = $file->getFilename();

            // パターン1: .supplemental-metadata.jsonを除去してメディアファイル名を推測
            if (preg_match('/^(.+)\.supplemental-metadata\.json$/i', $jsonFileName, $matches)) {
                $mediaFileName = $matches[1];
                $jsonMap[$mediaFileName] = $jsonPath;
            }
            // パターン2: .jsonを除去してメディアファイル名を推測
            elseif (preg_match('/^(.+)\.json$/i', $jsonFileName, $matches)) {
                $mediaFileName = $matches[1];
                $jsonMap[$mediaFileName] = $jsonPath;
            }
        }
    }

    return $jsonMap;
}

/**
 * Google Photosメタデータを統合したメディア情報を取得
 *
 * @param string $mediaFilePath メディアファイルパス
 * @param string $extractDir ZIP展開ディレクトリ
 * @param array|null $targetPeople フィルタリングする人物名の配列（nullの場合はフィルタなし）
 * @return array|null メディア情報配列、フィルタで除外された場合はnull
 */
function getMediaInfoWithGooglePhotosMetadata($mediaFilePath, $extractDir, $targetPeople = null) {
    $mediaInfo = [
        'has_json_metadata' => false,
        'datetime' => null,
        'latitude' => null,
        'longitude' => null,
        'altitude' => null,
        'people' => [],
        'description' => null,
        'camera_make' => null,
        'camera_model' => null,
        'filtered_out' => false
    ];

    // JSONメタデータを検索
    $jsonPath = findGooglePhotosJsonForMedia($mediaFilePath, $extractDir);
    if ($jsonPath === null) {
        return $mediaInfo; // JSONなし
    }

    // JSONメタデータを解析
    $metadata = parseGooglePhotosMetadata($jsonPath);
    if ($metadata === null) {
        return $mediaInfo; // JSON解析失敗
    }

    $mediaInfo['has_json_metadata'] = true;

    // 撮影日時
    $mediaInfo['datetime'] = getPhotoTakenTimeFromMetadata($metadata);

    // 位置情報
    $geoData = getGeoDataFromMetadata($metadata);
    if ($geoData !== null) {
        $mediaInfo['latitude'] = $geoData['latitude'];
        $mediaInfo['longitude'] = $geoData['longitude'];
        $mediaInfo['altitude'] = $geoData['altitude'];
    }

    // 人物情報
    $people = getPeopleFromMetadata($metadata);
    $mediaInfo['people'] = $people;

    // 人物フィルタリング
    if ($targetPeople !== null && !empty($targetPeople)) {
        if (!filterPeopleByNames($people, $targetPeople)) {
            $mediaInfo['filtered_out'] = true;
            return $mediaInfo; // フィルタで除外
        }
    }

    // 説明
    $mediaInfo['description'] = getDescriptionFromMetadata($metadata);

    // カメラ情報
    $cameraInfo = getCameraInfoFromMetadata($metadata);
    $mediaInfo['camera_make'] = $cameraInfo['make'];
    $mediaInfo['camera_model'] = $cameraInfo['model'];

    return $mediaInfo;
}

/**
 * EXIFデータとGoogle Photosメタデータを統合
 *
 * @param array $exifData EXIFデータ
 * @param array $googlePhotosData Google Photosメタデータ
 * @return array 統合されたメタデータ（Google Photosデータを優先）
 */
function mergeExifAndGooglePhotosMetadata($exifData, $googlePhotosData) {
    $merged = [
        'datetime' => $googlePhotosData['datetime'] ?? $exifData['datetime'] ?? null,
        'latitude' => $googlePhotosData['latitude'] ?? $exifData['latitude'] ?? null,
        'longitude' => $googlePhotosData['longitude'] ?? $exifData['longitude'] ?? null,
        'altitude' => $googlePhotosData['altitude'] ?? $exifData['altitude'] ?? null,
        'camera_make' => $googlePhotosData['camera_make'] ?? $exifData['camera_make'] ?? null,
        'camera_model' => $googlePhotosData['camera_model'] ?? $exifData['camera_model'] ?? null,
        'description' => $googlePhotosData['description'] ?? $exifData['description'] ?? null,
        'people' => $googlePhotosData['people'] ?? [],
        'has_exif' => !empty($exifData),
        'has_google_photos_metadata' => $googlePhotosData['has_json_metadata'] ?? false
    ];

    return $merged;
}
?>
