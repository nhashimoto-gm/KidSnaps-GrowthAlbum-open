<?php
/**
 * データベース接続設定
 * Personal-Finance-Dashboardと同じMySQLデータベースを使用
 * .env_dbファイルから設定を読み込み
 */

// .env_dbファイルのパス
$envDbPath = __DIR__ . '/../.env_db';

// .env_dbファイルが存在しない場合のエラー処理
if (!file_exists($envDbPath)) {
    die("エラー: .env_dbファイルが見つかりません。.env_db.exampleをコピーして.env_dbを作成してください。");
}

// .env_dbファイルを読み込み
$envVars = parse_ini_file($envDbPath);

if ($envVars === false) {
    die("エラー: .env_dbファイルの読み込みに失敗しました。");
}

// データベース接続情報を定義
define('DB_HOST', $envVars['DB_HOST'] ?? 'localhost');
define('DB_NAME', $envVars['DB_NAME'] ?? 'personal_finance');
define('DB_USER', $envVars['DB_USER'] ?? 'root');
define('DB_PASS', $envVars['DB_PASS'] ?? '');
define('DB_CHARSET', 'utf8mb4');

/**
 * データベース接続を取得
 * @return PDO
 */
function getDbConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        die("データベース接続エラー: " . $e->getMessage());
    }
}

/**
 * SQLクエリの実行（準備済みステートメント）
 * @param PDO $pdo
 * @param string $sql
 * @param array $params
 * @return PDOStatement
 */
function executeQuery($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("クエリエラー: " . $e->getMessage());
        throw $e;
    }
}
?>
