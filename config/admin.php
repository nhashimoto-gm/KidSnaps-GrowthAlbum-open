<?php
/**
 * 管理者モード設定
 *
 * .env_dbファイルから管理者パスワードを読み込みます。
 * .env_dbファイルにADMIN_PASSWORDを設定してください。
 */

/**
 * 管理者パスワードを取得
 * @return string 管理者パスワード
 */
function getAdminPassword() {
    // .env_dbファイルのパス
    $envDbPath = __DIR__ . '/../.env_db';

    // .env_dbファイルが存在しない場合のエラー処理
    if (!file_exists($envDbPath)) {
        error_log("エラー: .env_dbファイルが見つかりません。");
        return null;
    }

    // .env_dbファイルを読み込み
    $envVars = parse_ini_file($envDbPath);

    if ($envVars === false) {
        error_log("エラー: .env_dbファイルの読み込みに失敗しました。");
        return null;
    }

    // 管理者パスワードを取得（デフォルト: admin123）
    return $envVars['ADMIN_PASSWORD'] ?? 'admin123';
}

/**
 * パスワードを検証
 * @param string $password 入力されたパスワード
 * @return bool 検証結果
 */
function verifyAdminPassword($password) {
    $adminPassword = getAdminPassword();

    if ($adminPassword === null) {
        return false;
    }

    return $password === $adminPassword;
}
