<?php
/**
 * 管理者モードの切り替え
 */

// セッション開始
session_start();

// 管理者設定を読み込み
require_once 'config/admin.php';

// 現在の管理者モード状態を取得
$isAdminMode = isset($_SESSION['admin_mode']) && $_SESSION['admin_mode'];

// リダイレクト先を決定
$redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';

// 管理者モードをOFFにする場合（パスワード不要）
if ($isAdminMode) {
    $_SESSION['admin_mode'] = false;
    $_SESSION['admin_password_verified'] = false;
    header('Location: ' . $redirect);
    exit;
}

// 管理者モードをONにする場合（パスワード必要）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    if (verifyAdminPassword($password)) {
        $_SESSION['admin_mode'] = true;
        $_SESSION['admin_password_verified'] = true;
        header('Location: ' . $redirect);
        exit;
    } else {
        // パスワードが間違っている場合
        $_SESSION['admin_auth_error'] = true;
        header('Location: ' . $redirect);
        exit;
    }
} else {
    // POSTでない場合は単にリダイレクト
    header('Location: ' . $redirect);
    exit;
}
