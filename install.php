<?php
/**
 * KidSnaps Growth Album - インストールスクリプト
 * 初回セットアップ用
 */

// エラー表示を有効化
error_reporting(E_ALL);
ini_set('display_errors', 1);

$errors = [];
$success = [];

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KidSnaps Growth Album - インストール</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="bi bi-gear-fill me-2"></i>
                            KidSnaps Growth Album - インストール
                        </h3>
                    </div>
                    <div class="card-body">
                        <h4 class="mb-4">システム要件チェック</h4>

                        <?php
                        // PHP バージョンチェック
                        $phpVersion = phpversion();
                        $phpRequired = '7.4.0';
                        if (version_compare($phpVersion, $phpRequired, '>=')) {
                            echo '<div class="alert alert-success">
                                    <i class="bi bi-check-circle-fill me-2"></i>
                                    PHP バージョン: ' . $phpVersion . ' （要件: ' . $phpRequired . '+）
                                  </div>';
                            $success[] = 'PHP version OK';
                        } else {
                            echo '<div class="alert alert-danger">
                                    <i class="bi bi-x-circle-fill me-2"></i>
                                    PHP バージョン: ' . $phpVersion . ' （要件: ' . $phpRequired . '+が必要）
                                  </div>';
                            $errors[] = 'PHP version too old';
                        }

                        // PDO拡張チェック
                        if (extension_loaded('pdo') && extension_loaded('pdo_mysql')) {
                            echo '<div class="alert alert-success">
                                    <i class="bi bi-check-circle-fill me-2"></i>
                                    PDO MySQL 拡張: インストール済み
                                  </div>';
                            $success[] = 'PDO MySQL OK';
                        } else {
                            echo '<div class="alert alert-danger">
                                    <i class="bi bi-x-circle-fill me-2"></i>
                                    PDO MySQL 拡張: 未インストール
                                  </div>';
                            $errors[] = 'PDO MySQL not installed';
                        }

                        // fileinfo拡張チェック
                        if (extension_loaded('fileinfo')) {
                            echo '<div class="alert alert-success">
                                    <i class="bi bi-check-circle-fill me-2"></i>
                                    fileinfo 拡張: インストール済み
                                  </div>';
                            $success[] = 'fileinfo OK';
                        } else {
                            echo '<div class="alert alert-danger">
                                    <i class="bi bi-x-circle-fill me-2"></i>
                                    fileinfo 拡張: 未インストール
                                  </div>';
                            $errors[] = 'fileinfo not installed';
                        }

                        // GD拡張チェック
                        if (extension_loaded('gd')) {
                            echo '<div class="alert alert-success">
                                    <i class="bi bi-check-circle-fill me-2"></i>
                                    GD 拡張: インストール済み（画像処理）
                                  </div>';
                            $success[] = 'GD OK';
                        } else {
                            echo '<div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    GD 拡張: 未インストール（推奨）
                                  </div>';
                        }

                        // ディレクトリの書き込み権限チェック
                        $uploadDirs = [
                            'uploads/',
                            'uploads/images/',
                            'uploads/videos/'
                        ];

                        foreach ($uploadDirs as $dir) {
                            if (is_dir($dir) && is_writable($dir)) {
                                echo '<div class="alert alert-success">
                                        <i class="bi bi-check-circle-fill me-2"></i>
                                        ディレクトリ ' . $dir . ': 書き込み可能
                                      </div>';
                                $success[] = $dir . ' writable';
                            } else {
                                echo '<div class="alert alert-danger">
                                        <i class="bi bi-x-circle-fill me-2"></i>
                                        ディレクトリ ' . $dir . ': 書き込み不可
                                        <br><small>実行: chmod 755 ' . $dir . '</small>
                                      </div>';
                                $errors[] = $dir . ' not writable';
                            }
                        }

                        // .env_dbファイルチェック
                        if (file_exists('.env_db')) {
                            echo '<div class="alert alert-success">
                                    <i class="bi bi-check-circle-fill me-2"></i>
                                    .env_db ファイル: 存在します
                                  </div>';
                            $success[] = '.env_db exists';

                            // データベース接続テスト
                            try {
                                require_once 'config/database.php';
                                $pdo = getDbConnection();
                                echo '<div class="alert alert-success">
                                        <i class="bi bi-check-circle-fill me-2"></i>
                                        データベース接続: 成功
                                      </div>';
                                $success[] = 'DB connection OK';

                                // テーブル存在チェック
                                $stmt = $pdo->query("SHOW TABLES LIKE 'media_files'");
                                if ($stmt->rowCount() > 0) {
                                    echo '<div class="alert alert-success">
                                            <i class="bi bi-check-circle-fill me-2"></i>
                                            テーブル media_files: 存在します
                                          </div>';
                                    $success[] = 'Tables exist';
                                } else {
                                    echo '<div class="alert alert-warning">
                                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                            テーブル media_files: 未作成
                                            <br><small>sql/setup.sql を実行してください</small>
                                          </div>';
                                    $errors[] = 'Tables not created';
                                }
                            } catch (Exception $e) {
                                echo '<div class="alert alert-danger">
                                        <i class="bi bi-x-circle-fill me-2"></i>
                                        データベース接続: 失敗 - ' . htmlspecialchars($e->getMessage()) . '
                                      </div>';
                                $errors[] = 'DB connection failed';
                            }
                        } else {
                            echo '<div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    .env_db ファイル: 未作成
                                    <br><small>.env_db.example をコピーして .env_db を作成してください</small>
                                  </div>';
                            $errors[] = '.env_db not found';
                        }

                        // upload_max_filesize チェック
                        $uploadMaxFilesize = ini_get('upload_max_filesize');
                        $uploadMaxBytes = return_bytes($uploadMaxFilesize);
                        $requiredBytes = 50 * 1024 * 1024; // 50MB

                        if ($uploadMaxBytes >= $requiredBytes) {
                            echo '<div class="alert alert-success">
                                    <i class="bi bi-check-circle-fill me-2"></i>
                                    upload_max_filesize: ' . $uploadMaxFilesize . ' （推奨: 50M以上）
                                  </div>';
                            $success[] = 'upload_max_filesize OK';
                        } else {
                            echo '<div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    upload_max_filesize: ' . $uploadMaxFilesize . ' （推奨: 50M以上）
                                    <br><small>php.iniで設定を変更してください</small>
                                  </div>';
                        }

                        // post_max_size チェック
                        $postMaxSize = ini_get('post_max_size');
                        $postMaxBytes = return_bytes($postMaxSize);

                        if ($postMaxBytes >= $requiredBytes) {
                            echo '<div class="alert alert-success">
                                    <i class="bi bi-check-circle-fill me-2"></i>
                                    post_max_size: ' . $postMaxSize . ' （推奨: 50M以上）
                                  </div>';
                            $success[] = 'post_max_size OK';
                        } else {
                            echo '<div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    post_max_size: ' . $postMaxSize . ' （推奨: 50M以上）
                                    <br><small>php.iniで設定を変更してください</small>
                                  </div>';
                        }

                        // バイト変換関数
                        function return_bytes($val) {
                            $val = trim($val);
                            $last = strtolower($val[strlen($val)-1]);
                            $val = (int) $val;
                            switch($last) {
                                case 'g':
                                    $val *= 1024;
                                case 'm':
                                    $val *= 1024;
                                case 'k':
                                    $val *= 1024;
                            }
                            return $val;
                        }
                        ?>

                        <hr class="my-4">

                        <h4 class="mb-3">インストール結果</h4>
                        <?php if (empty($errors)): ?>
                            <div class="alert alert-success">
                                <h5 class="alert-heading">
                                    <i class="bi bi-check-circle-fill me-2"></i>
                                    インストール完了！
                                </h5>
                                <p>すべてのシステム要件を満たしています。</p>
                                <hr>
                                <a href="index.php" class="btn btn-success">
                                    <i class="bi bi-arrow-right-circle me-2"></i>
                                    アプリケーションを起動
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <h5 class="alert-heading">
                                    <i class="bi bi-x-circle-fill me-2"></i>
                                    インストール未完了
                                </h5>
                                <p><?php echo count($errors); ?> 件の問題を解決してください。</p>
                                <hr>
                                <button class="btn btn-warning" onclick="location.reload()">
                                    <i class="bi bi-arrow-clockwise me-2"></i>
                                    再チェック
                                </button>
                            </div>
                        <?php endif; ?>

                        <div class="mt-4">
                            <h5>次のステップ:</h5>
                            <ol>
                                <li>.env_db ファイルを作成（.env_db.example をコピー）してデータベース接続情報を設定</li>
                                <li>SQLスキーマを実行（sql/setup.sql）</li>
                                <li>ディレクトリのパーミッションを設定（uploads/以下）</li>
                                <li>PHP設定を確認・調整（upload_max_filesize, post_max_size）</li>
                                <li>アプリケーションにアクセス（index.php）</li>
                            </ol>
                        </div>

                        <div class="alert alert-info mt-4">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>セキュリティ注意:</strong>
                            インストール完了後、このinstall.phpファイルは削除または名前変更してください。
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
