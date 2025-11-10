<?php
/**
 * KidSnaps Growth Album - チャンク分割アップロード処理
 * 大きなファイルを安定してアップロードするための機能
 */

// エラー表示設定（環境変数 DEBUG_MODE=1 で有効化）
if (getenv('DEBUG_MODE') === '1') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
}

header('Content-Type: application/json');

// POSTリクエストの確認
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

session_start();

// デバッグログ関数
function debugLog($message, $data = null) {
    $logDir = __DIR__ . '/../uploads/temp';
    if (!file_exists($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/upload_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    if ($data !== null) {
        $logMessage .= ': ' . print_r($data, true);
    }
    $logMessage .= "\n";
    @file_put_contents($logFile, $logMessage, FILE_APPEND);
}

try {
    debugLog('チャンクアップロード開始', [
        'POST' => $_POST,
        'FILES' => isset($_FILES['chunk']) ? $_FILES['chunk'] : 'なし',
        'USER_AGENT' => $_SERVER['HTTP_USER_AGENT'] ?? '不明'
    ]);

    // チャンクアップロードのパラメータを取得
    $chunkIndex = isset($_POST['chunkIndex']) ? (int)$_POST['chunkIndex'] : 0;
    $totalChunks = isset($_POST['totalChunks']) ? (int)$_POST['totalChunks'] : 1;
    $fileName = isset($_POST['fileName']) ? $_POST['fileName'] : '';
    $fileIdentifier = isset($_POST['fileIdentifier']) ? $_POST['fileIdentifier'] : '';

    debugLog('パラメータ取得', [
        'chunkIndex' => $chunkIndex,
        'totalChunks' => $totalChunks,
        'fileName' => $fileName,
        'fileIdentifier' => $fileIdentifier
    ]);

    if (empty($fileName) || empty($fileIdentifier)) {
        throw new Exception('ファイル名または識別子が不正です。');
    }

    // ファイル名のサニタイズ
    $fileName = basename($fileName);

    // チャンク保存用の一時ディレクトリ（Lolipop対応: プロジェクト内に配置）
    $tempDir = __DIR__ . '/../uploads/temp/chunked_uploads';
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0755, true);

        // セキュリティ対策: 一時ディレクトリへのアクセス禁止
        $htaccessPath = __DIR__ . '/../uploads/temp/.htaccess';
        if (!file_exists($htaccessPath)) {
            file_put_contents($htaccessPath, "Require all denied\n");
        }
    }

    // このアップロード用の一時ディレクトリ
    $uploadTempDir = $tempDir . '/' . $fileIdentifier;
    if (!file_exists($uploadTempDir)) {
        mkdir($uploadTempDir, 0755, true);
    }

    // チャンクファイルが送信されているか確認
    if (!isset($_FILES['chunk']) || $_FILES['chunk']['error'] !== UPLOAD_ERR_OK) {
        $errorCode = isset($_FILES['chunk']) ? $_FILES['chunk']['error'] : 'ファイル未送信';
        debugLog('チャンクファイルのアップロードエラー', [
            'error_code' => $errorCode,
            'error_message' => isset($_FILES['chunk']) ? 'エラーコード: ' . $errorCode : 'FILESが存在しません'
        ]);
        throw new Exception('チャンクファイルのアップロードに失敗しました。エラーコード: ' . $errorCode);
    }

    debugLog('チャンク受信成功', [
        'size' => $_FILES['chunk']['size'],
        'tmp_name' => $_FILES['chunk']['tmp_name']
    ]);

    // チャンクを一時ディレクトリに保存
    $chunkPath = $uploadTempDir . '/chunk_' . $chunkIndex;
    if (!move_uploaded_file($_FILES['chunk']['tmp_name'], $chunkPath)) {
        debugLog('チャンク保存失敗', [
            'chunkPath' => $chunkPath,
            'tmp_name' => $_FILES['chunk']['tmp_name']
        ]);
        throw new Exception('チャンクの保存に失敗しました。');
    }

    debugLog('チャンク保存成功', ['chunkPath' => $chunkPath, 'index' => $chunkIndex]);

    // 全てのチャンクが揃ったかチェック
    $uploadedChunks = [];
    for ($i = 0; $i < $totalChunks; $i++) {
        if (file_exists($uploadTempDir . '/chunk_' . $i)) {
            $uploadedChunks[] = $i;
        }
    }

    $isComplete = count($uploadedChunks) === $totalChunks;

    // 全チャンクが揃った場合、ファイルを結合
    if ($isComplete) {
        // 結合後のファイルパス
        $finalTempPath = $uploadTempDir . '/' . $fileName;
        $finalFile = fopen($finalTempPath, 'wb');

        if (!$finalFile) {
            throw new Exception('結合ファイルの作成に失敗しました。');
        }

        // チャンクを順番に結合
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = $uploadTempDir . '/chunk_' . $i;
            $chunk = fopen($chunkPath, 'rb');

            if (!$chunk) {
                fclose($finalFile);
                throw new Exception('チャンク' . $i . 'の読み込みに失敗しました。');
            }

            // チャンクを結合ファイルに書き込み
            while (!feof($chunk)) {
                fwrite($finalFile, fread($chunk, 8192));
            }

            fclose($chunk);
            unlink($chunkPath); // チャンク削除
        }

        fclose($finalFile);

        // ファイル情報を取得
        $fileSize = filesize($finalTempPath);
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $finalTempPath);
        finfo_close($finfo);

        // セッションに結合ファイル情報を保存（後続の処理で使用）
        if (!isset($_SESSION['chunked_files'])) {
            $_SESSION['chunked_files'] = [];
        }

        $_SESSION['chunked_files'][$fileIdentifier] = [
            'path' => $finalTempPath,
            'name' => $fileName,
            'size' => $fileSize,
            'mime_type' => $mimeType,
            'temp_dir' => $uploadTempDir
        ];

        echo json_encode([
            'success' => true,
            'complete' => true,
            'chunkIndex' => $chunkIndex,
            'totalChunks' => $totalChunks,
            'fileIdentifier' => $fileIdentifier,
            'message' => 'ファイルのアップロードが完了しました。'
        ]);
    } else {
        // まだ全チャンクが揃っていない
        echo json_encode([
            'success' => true,
            'complete' => false,
            'chunkIndex' => $chunkIndex,
            'totalChunks' => $totalChunks,
            'uploadedChunks' => count($uploadedChunks),
            'message' => 'チャンク' . ($chunkIndex + 1) . '/' . $totalChunks . 'を受信しました。'
        ]);
    }

} catch (Exception $e) {
    debugLog('チャンクアップロード例外エラー', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    error_log('チャンクアップロードエラー: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
