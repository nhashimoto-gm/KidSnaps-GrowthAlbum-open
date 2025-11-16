<?php
/**
 * index.php の動作確認用テストスクリプト
 * ブラウザでアクセスして、実際にデータが取得できているか確認
 */

// エラー表示を有効化
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';

echo "<h1>KidSnaps データベース取得テスト</h1>";
echo "<hr>";

try {
    $pdo = getDbConnection();
    echo "<p style='color: green;'>✓ データベース接続成功</p>";

    // 総件数を取得
    $countSql = "SELECT COUNT(*) as total FROM media_files";
    $countStmt = $pdo->query($countSql);
    $total = $countStmt->fetchColumn();

    echo "<p><strong>総件数:</strong> {$total}件</p>";

    // index.phpと同じクエリを実行
    $sql = "SELECT * FROM media_files WHERE 1=1 ORDER BY upload_date DESC";
    $stmt = executeQuery($pdo, $sql, []);
    $mediaFiles = $stmt->fetchAll();

    echo "<p><strong>取得件数:</strong> " . count($mediaFiles) . "件</p>";
    echo "<hr>";

    if (empty($mediaFiles)) {
        echo "<p style='color: red;'>メディアファイルが取得できませんでした。</p>";
    } else {
        echo "<h2>最新10件の詳細:</h2>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th><th>ファイル名</th><th>タイプ</th><th>ファイルパス</th><th>ファイル存在</th><th>登録日</th>";
        echo "</tr>";

        $count = 0;
        foreach ($mediaFiles as $media) {
            if ($count >= 10) break;

            $fileExists = file_exists($media['file_path']) ? '✓ YES' : '✗ NO';
            $fileExistsColor = file_exists($media['file_path']) ? 'green' : 'red';

            echo "<tr>";
            echo "<td>{$media['id']}</td>";
            echo "<td>{$media['filename']}</td>";
            echo "<td>{$media['file_type']}</td>";
            echo "<td><small>{$media['file_path']}</small></td>";
            echo "<td style='color: {$fileExistsColor};'>{$fileExists}</td>";
            echo "<td>" . date('Y/m/d H:i', strtotime($media['upload_date'])) . "</td>";
            echo "</tr>";

            $count++;
        }
        echo "</table>";

        // ファイルパスの形式を確認
        echo "<hr>";
        echo "<h2>ファイルパス形式の確認:</h2>";
        $samplePath = $mediaFiles[0]['file_path'];
        echo "<p><strong>サンプルパス:</strong> {$samplePath}</p>";
        echo "<p><strong>パス形式:</strong> ";
        if (strpos($samplePath, 'uploads/') === 0) {
            echo "相対パス（正しい）</p>";
        } elseif (strpos($samplePath, 'C:') === 0 || strpos($samplePath, 'c:') === 0) {
            echo "<span style='color: red;'>絶対パス（ブラウザからアクセス不可）</span></p>";
            echo "<p style='background-color: #fff3cd; padding: 10px; border-left: 4px solid #ffc107;'>";
            echo "⚠ <strong>問題検出:</strong> ファイルパスが絶対パス（C:\\...）になっています。<br>";
            echo "ブラウザからはこのパスにアクセスできません。<br>";
            echo "パスを相対パス（uploads/...）に変更する必要があります。";
            echo "</p>";
        } else {
            echo "不明な形式</p>";
        }
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>✗ エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr>";
echo "<p><a href='index.php'>→ 通常のindex.phpに戻る</a></p>";
?>
