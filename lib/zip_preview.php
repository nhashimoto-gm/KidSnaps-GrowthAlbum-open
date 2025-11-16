<?php
/**
 * KidSnaps Growth Album - ZIPファイルプレビュー処理
 * ZIPアーカイブの内容とフィルター結果をインポート前に確認
 */

// 実行時間を延長
set_time_limit(300); // 5分
ini_set('max_execution_time', '300');
ini_set('memory_limit', '1024M');

// エラー表示設定
if (getenv('DEBUG_MODE') === '1') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
}

// ログファイル設定
$logFile = __DIR__ . '/../uploads/temp/zip_preview.log';
if (!file_exists(dirname($logFile))) {
    @mkdir(dirname($logFile), 0755, true);
}
ini_set('error_log', $logFile);

function logDebug($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] ZIP_PREVIEW: {$message}\n";
    @file_put_contents($logFile, $logMessage, FILE_APPEND);
    error_log($message);
}

// Fatal Error時もJSONレスポンスを返すように設定
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // ヘッダーがまだ送信されていない場合のみ設定
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(500);
        }
        // JSONレスポンスを出力
        echo json_encode([
            'success' => false,
            'error' => 'サーバーエラーが発生しました: ' . $error['message'],
            'debug' => [
                'file' => basename($error['file']),
                'line' => $error['line']
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
});

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

session_start();

try {
    logDebug('=== ZIPプレビュー開始 ===');
    logDebug('POST data: ' . print_r($_POST, true));
    logDebug('Session ID: ' . session_id());
    logDebug('Session data available: ' . (isset($_SESSION['chunked_files']) ? 'Yes' : 'No'));

    require_once __DIR__ . '/../includes/google_photos_metadata_helper.php';

    $fileIdentifier = isset($_POST['fileIdentifier']) ? $_POST['fileIdentifier'] : '';
    $peopleFilter = isset($_POST['peopleFilter']) ? trim($_POST['peopleFilter']) : '';

    $targetPeople = null;
    if (!empty($peopleFilter)) {
        $targetPeople = array_map('trim', explode(',', $peopleFilter));
    }

    logDebug('ファイル識別子: ' . $fileIdentifier);
    logDebug('Peopleフィルタ: ' . ($targetPeople ? implode(', ', $targetPeople) : 'なし'));

    if (empty($fileIdentifier)) {
        logDebug('エラー: ファイル識別子が空');
        throw new Exception('ファイル識別子が指定されていません。');
    }

    // セッションからファイル情報を取得
    if (!isset($_SESSION['chunked_files'][$fileIdentifier])) {
        logDebug('エラー: セッションにファイル情報なし');
        logDebug('利用可能なファイル識別子: ' . print_r(array_keys($_SESSION['chunked_files'] ?? []), true));
        throw new Exception('アップロードされたファイルが見つかりません。セッション: ' . session_id());
    }

    $fileInfo = $_SESSION['chunked_files'][$fileIdentifier];
    $zipPath = $fileInfo['path'];
    $zipFileName = $fileInfo['name'];
    $zipSize = $fileInfo['size'];

    logDebug('ZIPファイル: ' . $zipPath);
    logDebug('ZIPサイズ: ' . $zipSize . ' bytes');

    // ZIPファイルかチェック
    if (!preg_match('/\.zip$/i', $zipFileName)) {
        throw new Exception('ZIPファイルのみがサポートされています。');
    }

    // ZIPファイルサイズ制限（5GB）
    $maxZipSize = 5 * 1024 * 1024 * 1024;
    if ($zipSize > $maxZipSize) {
        throw new Exception('ZIPファイルサイズは5GB以下にしてください。');
    }

    // ZIPファイル展開用ディレクトリ（セッションに保存して後で使用）
    $extractDir = __DIR__ . '/../uploads/temp/extract_' . $fileIdentifier;
    if (!file_exists($extractDir)) {
        if (!mkdir($extractDir, 0755, true)) {
            throw new Exception('展開ディレクトリの作成に失敗しました。');
        }
    }

    logDebug('展開ディレクトリ: ' . $extractDir);

    // ZIPを開く
    $zip = new ZipArchive();
    $zipOpenResult = $zip->open($zipPath);

    if ($zipOpenResult !== true) {
        throw new Exception('ZIPファイルを開けません。エラーコード: ' . $zipOpenResult);
    }

    // ZIPボム対策: 展開後のサイズをチェック
    $totalUncompressedSize = 0;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        $totalUncompressedSize += $stat['size'];
    }

    $maxUncompressedSize = 20 * 1024 * 1024 * 1024; // 20GB
    if ($totalUncompressedSize > $maxUncompressedSize) {
        $zip->close();
        throw new Exception('ZIP展開後のサイズが大きすぎます（最大20GB）。');
    }

    logDebug('ZIP内ファイル数: ' . $zip->numFiles);
    logDebug('展開後サイズ: ' . round($totalUncompressedSize / 1024 / 1024, 2) . ' MB');

    // ZIP展開
    if (!$zip->extractTo($extractDir)) {
        $zip->close();
        throw new Exception('ZIPファイルの展開に失敗しました。');
    }

    $zip->close();
    logDebug('ZIP展開完了');

    // 展開ディレクトリをセッションに保存（後でインポート時に使用）
    $_SESSION['chunked_files'][$fileIdentifier]['extract_dir'] = $extractDir;

    // 対応するメディアファイル拡張子
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif', 'mp4', 'mov', 'avi', 'mpeg'];

    // 展開したファイルを再帰的に取得
    $mediaFiles = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($extractDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $extension = strtolower($file->getExtension());
            if (in_array($extension, $allowedExtensions)) {
                // __MACOSXなどのシステムファイルを除外
                $filePath = $file->getPathname();
                if (strpos($filePath, '__MACOSX') === false && strpos($filePath, '.DS_Store') === false) {
                    $mediaFiles[] = $filePath;
                }
            }
        }
    }

    logDebug('メディアファイル数: ' . count($mediaFiles));

    // 各メディアファイルを分析
    $matchedFiles = [];
    $filteredFiles = [];
    $filesWithoutMetadata = [];
    $peopleStats = []; // 人物統計情報

    foreach ($mediaFiles as $filePath) {
        $fileName = basename($filePath);
        $fileSize = filesize($filePath);

        // ファイルサイズチェック（500MB）
        if ($fileSize > 500 * 1024 * 1024) {
            continue; // サイズ超過のファイルはスキップ
        }

        // Google Photosメタデータを取得
        $googlePhotosData = getMediaInfoWithGooglePhotosMetadata($filePath, $extractDir, $targetPeople);

        $fileInfo = [
            'filename' => $fileName,
            'size' => $fileSize,
            'size_formatted' => formatFileSize($fileSize),
            'has_metadata' => $googlePhotosData['has_json_metadata'],
            'people' => $googlePhotosData['people'],
            'datetime' => $googlePhotosData['datetime'],
            'has_location' => !empty($googlePhotosData['latitude']) && !empty($googlePhotosData['longitude'])
        ];

        // 人物統計をカウント
        if (!empty($googlePhotosData['people'])) {
            foreach ($googlePhotosData['people'] as $person) {
                if (!isset($peopleStats[$person])) {
                    $peopleStats[$person] = [
                        'name' => $person,
                        'count' => 0,
                        'files' => []
                    ];
                }
                $peopleStats[$person]['count']++;
                $peopleStats[$person]['files'][] = $fileName;
            }
        }

        if ($googlePhotosData && $googlePhotosData['filtered_out']) {
            // フィルターで除外されたファイル
            $filteredFiles[] = $fileInfo;
        } else {
            // フィルターに一致したファイル（またはフィルターなし）
            if (!$googlePhotosData['has_json_metadata'] && $targetPeople !== null && !empty($targetPeople)) {
                // メタデータなし、かつフィルター指定ありの場合は除外扱い
                $filesWithoutMetadata[] = $fileInfo;
            } else {
                $matchedFiles[] = $fileInfo;
            }
        }
    }

    // 人物統計を件数の多い順にソート
    usort($peopleStats, function($a, $b) {
        return $b['count'] - $a['count'];
    });

    logDebug('マッチしたファイル: ' . count($matchedFiles));
    logDebug('フィルターで除外: ' . count($filteredFiles));
    logDebug('メタデータなし: ' . count($filesWithoutMetadata));
    logDebug('検出された人物: ' . count($peopleStats));

    // 結果を返す
    echo json_encode([
        'success' => true,
        'total_files' => count($mediaFiles),
        'matched_files' => $matchedFiles,
        'filtered_files' => $filteredFiles,
        'files_without_metadata' => $filesWithoutMetadata,
        'matched_count' => count($matchedFiles),
        'filtered_count' => count($filteredFiles),
        'no_metadata_count' => count($filesWithoutMetadata),
        'has_people_filter' => !empty($targetPeople),
        'people_filter' => $targetPeople,
        'people_stats' => array_values($peopleStats), // 人物統計情報
        'people_count' => count($peopleStats) // 検出された人物数
    ]);

    logDebug('=== ZIPプレビュー完了 ===');

} catch (Exception $e) {
    // エラー時のクリーンアップ（展開ディレクトリは削除しない）
    logDebug('===== ZIPプレビューエラー =====');
    logDebug('エラーメッセージ: ' . $e->getMessage());
    logDebug('ファイル: ' . $e->getFile() . ':' . $e->getLine());
    logDebug('スタックトレース: ' . $e->getTraceAsString());
    logDebug('=====================================');

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'session_id' => session_id()
        ]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * ファイルサイズをフォーマット
 */
function formatFileSize($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i) * 100) / 100 . ' ' . $sizes[$i];
}
?>
