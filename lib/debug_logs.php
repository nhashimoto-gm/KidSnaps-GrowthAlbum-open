<?php
/**
 * KidSnaps Growth Album - デバッグログ確認ページ
 * アップロードのトラブルシューティング用
 */

// ⚠️ セキュリティ警告: 本番環境では必ず無効化または削除してください
// このファイルは開発・デバッグ専用です

// 環境変数でのアクセス制御
if (getenv('DEBUG_MODE') !== '1') {
    http_response_code(403);
    die('このページは現在無効化されています。アクセスするにはDEBUG_MODE=1を設定してください。');
}

// 基本認証（セキュリティのため）
// .env_db ファイルで DEBUG_PASSWORD を設定してください
$valid_password = getenv('DEBUG_PASSWORD') ?: 'debug2024'; // デフォルト値
$entered_password = isset($_GET['pass']) ? $_GET['pass'] : '';

if ($entered_password !== $valid_password) {
    http_response_code(403);
    die('アクセスが拒否されました。正しいパスワードを入力してください。<br>使い方: debug_logs.php?pass=パスワード');
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>デバッグログ - KidSnaps Growth Album</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #4ec9b0;
            border-bottom: 2px solid #4ec9b0;
            padding-bottom: 10px;
        }
        .log-section {
            background: #252526;
            border: 1px solid #3e3e42;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .log-section h2 {
            color: #569cd6;
            margin-top: 0;
        }
        pre {
            background: #1e1e1e;
            border: 1px solid #3e3e42;
            border-radius: 3px;
            padding: 15px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 500px;
            overflow-y: auto;
        }
        .error {
            color: #f48771;
        }
        .success {
            color: #4ec9b0;
        }
        .warning {
            color: #dcdcaa;
        }
        .info {
            color: #9cdcfe;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007acc;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            margin: 5px;
        }
        .btn:hover {
            background: #005a9e;
        }
        .btn-danger {
            background: #d16969;
        }
        .btn-danger:hover {
            background: #a84444;
        }
        .empty {
            color: #808080;
            font-style: italic;
        }
        .timestamp {
            color: #858585;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 デバッグログ確認</h1>

        <div style="margin-bottom: 20px;">
            <a href="?pass=<?php echo htmlspecialchars($entered_password); ?>" class="btn">🔄 更新</a>
            <a href="?pass=<?php echo htmlspecialchars($entered_password); ?>&clear=upload" class="btn btn-danger" onclick="return confirm('アップロードログを削除しますか？')">🗑️ アップロードログ削除</a>
            <a href="?pass=<?php echo htmlspecialchars($entered_password); ?>&clear=php" class="btn btn-danger" onclick="return confirm('PHPエラーログを削除しますか？')">🗑️ PHPエラーログ削除</a>
        </div>

        <?php
        // ログ削除処理
        if (isset($_GET['clear'])) {
            if ($_GET['clear'] === 'upload') {
                $uploadLog = __DIR__ . '/../uploads/temp/upload_debug.log';
                if (file_exists($uploadLog)) {
                    unlink($uploadLog);
                    echo '<div class="log-section"><p class="success">✅ アップロードログを削除しました。</p></div>';
                }
            } elseif ($_GET['clear'] === 'php') {
                $phpLog = __DIR__ . '/../uploads/temp/php_error.log';
                if (file_exists($phpLog)) {
                    unlink($phpLog);
                    echo '<div class="log-section"><p class="success">✅ PHPエラーログを削除しました。</p></div>';
                }
            }
        }

        // アップロードデバッグログ
        $uploadLog = __DIR__ . '/uploads/temp/upload_debug.log';
        echo '<div class="log-section">';
        echo '<h2>📤 アップロードデバッグログ</h2>';
        if (file_exists($uploadLog)) {
            $content = file_get_contents($uploadLog);
            if (!empty($content)) {
                // ログを色分け
                $content = htmlspecialchars($content);
                $content = preg_replace('/\[([^\]]+)\]/', '<span class="timestamp">[$1]</span>', $content);
                $content = preg_replace('/(エラー|失敗|Exception|Error)/u', '<span class="error">$1</span>', $content);
                $content = preg_replace('/(成功|完了|SUCCESS)/u', '<span class="success">$1</span>', $content);
                $content = preg_replace('/(開始|処理中|WARNING)/u', '<span class="warning">$1</span>', $content);
                echo '<pre>' . $content . '</pre>';

                // ファイルサイズ表示
                $filesize = filesize($uploadLog);
                echo '<p class="info">ファイルサイズ: ' . number_format($filesize) . ' バイト (' . date('Y-m-d H:i:s', filemtime($uploadLog)) . ' 更新)</p>';
            } else {
                echo '<p class="empty">ログファイルは空です。</p>';
            }
        } else {
            echo '<p class="empty">ログファイルが見つかりません。</p>';
        }
        echo '</div>';

        // PHPエラーログ
        $phpLog = __DIR__ . '/uploads/temp/php_error.log';
        echo '<div class="log-section">';
        echo '<h2>⚠️ PHPエラーログ</h2>';
        if (file_exists($phpLog)) {
            $content = file_get_contents($phpLog);
            if (!empty($content)) {
                $content = htmlspecialchars($content);
                $content = preg_replace('/\[([^\]]+)\]/', '<span class="timestamp">[$1]</span>', $content);
                $content = preg_replace('/(Error|Fatal|Warning|Notice)/i', '<span class="error">$1</span>', $content);
                echo '<pre>' . $content . '</pre>';

                $filesize = filesize($phpLog);
                echo '<p class="info">ファイルサイズ: ' . number_format($filesize) . ' バイト (' . date('Y-m-d H:i:s', filemtime($phpLog)) . ' 更新)</p>';
            } else {
                echo '<p class="empty">ログファイルは空です。</p>';
            }
        } else {
            echo '<p class="empty">ログファイルが見つかりません。</p>';
        }
        echo '</div>';

        // システム情報
        echo '<div class="log-section">';
        echo '<h2>🖥️ システム情報</h2>';
        echo '<pre>';
        echo 'PHPバージョン: ' . phpversion() . "\n";
        echo 'Webサーバー: ' . $_SERVER['SERVER_SOFTWARE'] ?? '不明' . "\n";
        echo 'upload_max_filesize: ' . ini_get('upload_max_filesize') . "\n";
        echo 'post_max_size: ' . ini_get('post_max_size') . "\n";
        echo 'max_execution_time: ' . ini_get('max_execution_time') . "秒\n";
        echo 'max_input_time: ' . ini_get('max_input_time') . "秒\n";
        echo 'memory_limit: ' . ini_get('memory_limit') . "\n";
        echo 'タイムゾーン: ' . date_default_timezone_get() . "\n";
        echo '現在時刻: ' . date('Y-m-d H:i:s') . "\n";

        // 一時ディレクトリの確認
        $tempDir = __DIR__ . '/../uploads/temp';
        echo "\nuploads/temp/ ディレクトリ:\n";
        if (is_dir($tempDir)) {
            echo "  存在: はい\n";
            echo "  書き込み可能: " . (is_writable($tempDir) ? 'はい' : 'いいえ') . "\n";

            // チャンクディレクトリの確認
            $chunkDir = $tempDir . '/chunked_uploads';
            if (is_dir($chunkDir)) {
                $files = scandir($chunkDir);
                $fileCount = count($files) - 2; // . と .. を除く
                echo "  チャンクファイル数: " . $fileCount . "\n";
            }
        } else {
            echo "  存在: いいえ\n";
        }
        echo '</pre>';
        echo '</div>';

        // セッション情報
        echo '<div class="log-section">';
        echo '<h2>🔐 セッション情報</h2>';
        echo '<pre>';
        session_start();
        if (isset($_SESSION['chunked_files']) && !empty($_SESSION['chunked_files'])) {
            echo "アップロード中のファイル:\n";
            foreach ($_SESSION['chunked_files'] as $id => $info) {
                echo "  ID: $id\n";
                echo "    ファイル名: " . ($info['name'] ?? '不明') . "\n";
                echo "    サイズ: " . number_format($info['size'] ?? 0) . " バイト\n";
                echo "    パス: " . ($info['path'] ?? '不明') . "\n\n";
            }
        } else {
            echo "アップロード中のファイルはありません。\n";
        }
        echo '</pre>';
        echo '</div>';
        ?>

        <div style="margin-top: 30px; padding: 20px; background: #252526; border-radius: 5px;">
            <h3>📝 使い方</h3>
            <ul>
                <li>このページはアップロードの問題を診断するためのものです</li>
                <li>iPhoneから動画をアップロードする際、このページを別タブで開いて「更新」ボタンを押してください</li>
                <li>ログにエラーが表示されている場合、その内容を確認してください</li>
                <li><strong>セキュリティ上の注意:</strong> 本番環境では必ずパスワードを変更し、使用後はこのファイルを削除してください</li>
            </ul>
        </div>
    </div>
</body>
</html>
