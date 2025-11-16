<?php
/**
 * 動画サムネイル生成ヘルパー
 * ffmpegを使用して動画ファイルからサムネイルを生成
 */

/**
 * ffmpegのパスを取得
 * @return string|null ffmpegのパス、見つからない場合はnull
 */
function getFfmpegPath() {
    static $cachedPath = null;

    if ($cachedPath !== null) {
        return $cachedPath;
    }

    $isWindows = (PHP_OS_FAMILY === 'Windows');

    // 1. プロジェクトルートからの相対パスで確認
    $projectRoot = realpath(__DIR__ . '/..');
    $localFfmpegPaths = [
        $projectRoot . '/ffmpeg/ffmpeg.exe',  // Windows
        $projectRoot . '/ffmpeg/ffmpeg'        // Linux/Mac
    ];

    foreach ($localFfmpegPaths as $localPath) {
        if (file_exists($localPath)) {
            // 実行権限を確認・付与
            if (!$isWindows && !is_executable($localPath)) {
                @chmod($localPath, 0755);
            }
            $cachedPath = $localPath;
            return $cachedPath;
        }
    }

    // 2. システムPATHから探す
    $ffmpegCheckCommand = $isWindows ? 'where ffmpeg 2>nul' : 'which ffmpeg 2>/dev/null';
    $ffmpegPath = trim(shell_exec($ffmpegCheckCommand));

    if (!empty($ffmpegPath) && file_exists($ffmpegPath)) {
        $cachedPath = $ffmpegPath;
        return $cachedPath;
    }

    return null;
}

/**
 * 動画からサムネイルを生成（JPEG）
 * @param string $videoPath 動画ファイルのパス
 * @param string $thumbnailPath 出力サムネイルパス
 * @param int $width サムネイルの幅（デフォルト: 400px）
 * @param int $quality JPEG品質（デフォルト: 85）
 * @param float $timePosition サムネイルを取得する時間位置（秒、デフォルト: 1.0）
 * @return bool 成功したかどうか
 */
function generateVideoThumbnail($videoPath, $thumbnailPath, $width = 400, $quality = 85, $timePosition = 1.0) {
    // ファイルが存在するか確認
    if (!file_exists($videoPath)) {
        error_log("Video file not found: {$videoPath}");
        return false;
    }

    // ffmpegのパスを取得
    $ffmpegPath = getFfmpegPath();
    if (!$ffmpegPath) {
        error_log("ffmpeg not found. Please install ffmpeg or place it in the ffmpeg/ directory.");
        return false;
    }

    // サムネイルディレクトリを作成
    $thumbnailDir = dirname($thumbnailPath);
    if (!is_dir($thumbnailDir)) {
        if (!mkdir($thumbnailDir, 0755, true)) {
            error_log("Failed to create thumbnail directory: {$thumbnailDir}");
            return false;
        }
    }

    // 複数の時間位置を試行（破損したフレームを回避）
    $timePositions = [$timePosition, 0.5, 2.0, 3.0, 0.1];

    foreach ($timePositions as $pos) {
        // 時間位置をフォーマット
        $hours = floor($pos / 3600);
        $minutes = floor(($pos % 3600) / 60);
        $seconds = $pos % 60;
        $timeString = sprintf('%02d:%02d:%06.3f', $hours, $minutes, $seconds);

        // ffmpegコマンドを構築
        // -err_detect ignore_err: エラーを無視して続行
        // -skip_frame nokey: キーフレームのみ使用（破損対策）
        // -threads 1: スレッド数を制限（メモリ削減）
        // -ss: 開始時間（-iの前に配置して高速化）
        // -i: 入力ファイル
        // -vframes 1: 1フレームのみ
        // -vf scale: リサイズ（アスペクト比維持）
        // -q:v: JPEG品質（2-31、小さいほど高品質）
        $jpegQuality = (int)(31 - ($quality / 100 * 29)); // 85% -> 約3

        $command = sprintf(
            '%s -err_detect ignore_err -skip_frame nokey -threads 1 -ss %s -i %s -vframes 1 -vf "scale=%d:-1" -q:v %d -y %s 2>&1',
            escapeshellarg($ffmpegPath),
            escapeshellarg($timeString),
            escapeshellarg($videoPath),
            (int)$width,
            $jpegQuality,
            escapeshellarg($thumbnailPath)
        );

        // コマンド実行
        exec($command, $output, $returnCode);

        // 結果確認
        if ($returnCode === 0 && file_exists($thumbnailPath) && filesize($thumbnailPath) > 0) {
            error_log("Video thumbnail generated at {$pos}s: {$thumbnailPath}");
            return true;
        }

        // エラー時は次の時間位置を試行
        error_log("Failed to generate thumbnail at {$pos}s, trying next position...");

        // 一時的に作成された不完全なファイルを削除
        if (file_exists($thumbnailPath)) {
            @unlink($thumbnailPath);
        }
    }

    // すべての時間位置で失敗した場合
    $errorMsg = "Failed to generate video thumbnail after trying multiple time positions";
    if (!empty($output)) {
        // 最後のエラーメッセージのみログに記録（冗長な出力を避ける）
        $lastError = array_slice($output, -10); // 最後の10行のみ
        $errorMsg .= ":\n" . implode("\n", $lastError);
    }
    error_log($errorMsg);
    return false;
}

/**
 * 動画から最適化されたサムネイルを生成（JPEG + WebP）
 * @param string $videoPath 動画ファイルのパス
 * @param string $thumbnailPath 出力JPEGサムネイルパス
 * @param int $width サムネイルの幅（デフォルト: 400px）
 * @param int $quality 品質（デフォルト: 85）
 * @param bool $generateWebP WebP版も生成するか（デフォルト: true）
 * @return array 生成されたファイルパス ['jpeg' => path, 'webp' => path or null, 'success' => bool]
 */
function generateOptimizedVideoThumbnail($videoPath, $thumbnailPath, $width = 400, $quality = 85, $generateWebP = true) {
    $result = [
        'jpeg' => null,
        'webp' => null,
        'success' => false
    ];

    // JPEGサムネイルを生成
    if (generateVideoThumbnail($videoPath, $thumbnailPath, $width, $quality)) {
        $result['jpeg'] = $thumbnailPath;
        $result['success'] = true;
    } else {
        return $result;
    }

    // WebP版も生成（オプション）
    if ($generateWebP && $result['success']) {
        // image_thumbnail_helper.phpのWebP生成関数を使用
        require_once __DIR__ . '/image_thumbnail_helper.php';

        $webpPath = preg_replace('/\.(jpg|jpeg)$/i', '.webp', $thumbnailPath);
        if (generateWebPThumbnail($thumbnailPath, $webpPath, $width, $quality)) {
            $result['webp'] = $webpPath;
        }
    }

    return $result;
}

/**
 * 動画の長さを取得（秒）
 * @param string $videoPath 動画ファイルのパス
 * @return float|null 動画の長さ（秒）、失敗時はnull
 */
function getVideoDuration($videoPath) {
    if (!file_exists($videoPath)) {
        return null;
    }

    $ffmpegPath = getFfmpegPath();
    if (!$ffmpegPath) {
        return null;
    }

    // ffprobeがあればそれを使用、なければffmpegで取得
    $ffprobePath = str_replace('ffmpeg', 'ffprobe', $ffmpegPath);

    if (file_exists($ffprobePath)) {
        $command = sprintf(
            '%s -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s 2>&1',
            escapeshellarg($ffprobePath),
            escapeshellarg($videoPath)
        );
    } else {
        // ffmpegで取得（出力から解析）
        $command = sprintf(
            '%s -i %s 2>&1 | grep Duration',
            escapeshellarg($ffmpegPath),
            escapeshellarg($videoPath)
        );
    }

    $output = shell_exec($command);

    if ($output && preg_match('/(\d+):(\d+):(\d+\.\d+)/', $output, $matches)) {
        $hours = (int)$matches[1];
        $minutes = (int)$matches[2];
        $seconds = (float)$matches[3];
        return $hours * 3600 + $minutes * 60 + $seconds;
    } elseif ($output && is_numeric(trim($output))) {
        return (float)trim($output);
    }

    return null;
}

/**
 * ffmpegが利用可能かチェック
 * @return bool 利用可能かどうか
 */
function isVideoThumbnailGenerationAvailable() {
    return getFfmpegPath() !== null;
}
?>
