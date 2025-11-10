<?php
/**
 * 動画メタデータ取得ヘルパー
 * getID3ライブラリを使用して動画ファイルからメタデータを抽出
 */

require_once __DIR__ . '/getid3/getid3.php';

/**
 * 動画ファイルからメタデータを取得
 * @param string $filePath 動画ファイルのパス
 * @return array メタデータ配列
 */
function getVideoMetadata($filePath) {
    $metadata = [
        'datetime' => null,
        'latitude' => null,
        'longitude' => null,
        'location_name' => null,
        'camera_make' => null,
        'camera_model' => null,
        'software' => null,
        'focal_length' => null,
        'location_accuracy' => null,
        'duration' => null,
        'width' => null,
        'height' => null
    ];

    try {
        // getID3インスタンスを作成
        $getID3 = new getID3();
        $getID3->option_md5_data = false;
        $getID3->option_md5_data_source = false;
        $getID3->encoding = 'UTF-8';

        // ファイル情報を解析
        $fileInfo = $getID3->analyze($filePath);

        // エラーチェック
        if (isset($fileInfo['error'])) {
            error_log('getID3 error: ' . implode(', ', $fileInfo['error']));
            return $metadata;
        }

        // QuickTime/MOVファイルの場合
        if (isset($fileInfo['quicktime'])) {
            // 作成日時
            if (isset($fileInfo['quicktime']['time']['create_time'])) {
                $metadata['datetime'] = date('Y-m-d H:i:s', $fileInfo['quicktime']['time']['create_time']);
            }

            // GPS情報（QuickTimeのメタデータから）
            if (isset($fileInfo['quicktime']['gps_latitude']) && isset($fileInfo['quicktime']['gps_longitude'])) {
                $metadata['latitude'] = $fileInfo['quicktime']['gps_latitude'];
                $metadata['longitude'] = $fileInfo['quicktime']['gps_longitude'];
            }

            // カメラ情報
            if (isset($fileInfo['quicktime']['camera']['make'])) {
                $metadata['camera_make'] = $fileInfo['quicktime']['camera']['make'];
            }
            if (isset($fileInfo['quicktime']['camera']['model'])) {
                $metadata['camera_model'] = $fileInfo['quicktime']['camera']['model'];
            }

            // Apple QuickTimeメタデータの取得（com.apple.quicktime.*）
            // getID3は "com.apple.quicktime." プレフィックスを削除して保存
            if (isset($fileInfo['quicktime']['comments'])) {
                $comments = $fileInfo['quicktime']['comments'];

                // メーカー（com.apple.quicktime.make）
                if (isset($comments['make'][0]) && empty($metadata['camera_make'])) {
                    $metadata['camera_make'] = $comments['make'][0];
                }

                // 機種（com.apple.quicktime.model）
                if (isset($comments['model'][0]) && empty($metadata['camera_model'])) {
                    $metadata['camera_model'] = $comments['model'][0];
                }

                // 作成日時（com.apple.quicktime.creationdate）
                if (isset($comments['creationdate'][0]) && empty($metadata['datetime'])) {
                    // ISO 8601形式をMySQLフォーマットに変換
                    $creationDate = $comments['creationdate'][0];
                    $timestamp = strtotime($creationDate);
                    if ($timestamp !== false) {
                        $metadata['datetime'] = date('Y-m-d H:i:s', $timestamp);
                    }
                }

                // ISO6709形式の位置情報（com.apple.quicktime.location.ISO6709）
                if (isset($comments['location.ISO6709'][0])) {
                    $iso6709 = $comments['location.ISO6709'][0];
                    $parsed = parseISO6709($iso6709);
                    if ($parsed) {
                        $metadata['latitude'] = $parsed['latitude'];
                        $metadata['longitude'] = $parsed['longitude'];
                    }
                }

                // GPS座標（別の形式）
                if (isset($comments['gps_latitude'][0]) && empty($metadata['latitude'])) {
                    $metadata['latitude'] = floatval($comments['gps_latitude'][0]);
                }
                if (isset($comments['gps_longitude'][0]) && empty($metadata['longitude'])) {
                    $metadata['longitude'] = floatval($comments['gps_longitude'][0]);
                }

                // 位置精度（com.apple.quicktime.location.accuracy.horizontal）
                if (isset($comments['location.accuracy.horizontal'][0])) {
                    $metadata['location_accuracy'] = floatval($comments['location.accuracy.horizontal'][0]);
                }

                // 35mm換算焦点距離（com.apple.quicktime.camera.focal_length.35mm_equivalent）
                if (isset($comments['camera.focal_length.35mm_equivalent'][0])) {
                    $metadata['focal_length'] = floatval($comments['camera.focal_length.35mm_equivalent'][0]);
                }

                // ソフトウェア情報（com.apple.quicktime.software）
                if (isset($comments['software'][0])) {
                    $metadata['software'] = $comments['software'][0];
                }
            }
        }

        // 動画情報
        if (isset($fileInfo['playtime_seconds'])) {
            $metadata['duration'] = $fileInfo['playtime_seconds'];
        }

        if (isset($fileInfo['video']['resolution_x'])) {
            $metadata['width'] = $fileInfo['video']['resolution_x'];
        }

        if (isset($fileInfo['video']['resolution_y'])) {
            $metadata['height'] = $fileInfo['video']['resolution_y'];
        }

        // 日時が取得できなかった場合、ファイルの変更日時を使用
        if (!$metadata['datetime']) {
            $fileModTime = filemtime($filePath);
            if ($fileModTime) {
                $metadata['datetime'] = date('Y-m-d H:i:s', $fileModTime);
            }
        }

        // デバッグ用：取得したメタデータをログに出力
        error_log('Video metadata extracted: ' . json_encode($metadata));

    } catch (Exception $e) {
        error_log('Video metadata extraction error: ' . $e->getMessage());
    }

    return $metadata;
}

/**
 * ISO 6709形式の位置情報をパース
 * 例: "+35.6586+139.7454+035.247/" または "+35.6586+139.7454/"
 * @param string $iso6709 ISO 6709形式の文字列
 * @return array|null ['latitude' => float, 'longitude' => float, 'altitude' => float|null]
 */
function parseISO6709($iso6709) {
    if (empty($iso6709)) {
        return null;
    }

    // ISO 6709形式: ±DD.DDDD±DDD.DDDD±AAA.AAA/
    // 緯度（-90〜+90）、経度（-180〜+180）、高度（オプション）
    $pattern = '/^([+-]\d+\.?\d*)([+-]\d+\.?\d*)([+-]\d+\.?\d*)?\/$/';

    if (preg_match($pattern, $iso6709, $matches)) {
        $result = [
            'latitude' => floatval($matches[1]),
            'longitude' => floatval($matches[2]),
            'altitude' => isset($matches[3]) ? floatval($matches[3]) : null
        ];
        return $result;
    }

    return null;
}
?>
