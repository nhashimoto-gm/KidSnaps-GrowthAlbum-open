<?php
/**
 * KidSnaps Growth Album - メディア削除処理
 */

require_once 'config/database.php';

session_start();

// POSTリクエストの確認
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

try {
    // メディアID取得
    if (!isset($_POST['media_id']) || !is_numeric($_POST['media_id'])) {
        throw new Exception('無効なメディアIDです。');
    }

    $mediaId = (int)$_POST['media_id'];

    // データベース接続
    $pdo = getDbConnection();

    // メディア情報取得
    $sql = "SELECT * FROM media_files WHERE id = :id";
    $stmt = executeQuery($pdo, $sql, [':id' => $mediaId]);
    $media = $stmt->fetch();

    if (!$media) {
        throw new Exception('指定されたメディアが見つかりません。');
    }

    // ファイル削除
    if (file_exists($media['file_path'])) {
        if (!unlink($media['file_path'])) {
            throw new Exception('ファイルの削除に失敗しました。');
        }
    }

    // データベースから削除
    $sql = "DELETE FROM media_files WHERE id = :id";
    executeQuery($pdo, $sql, [':id' => $mediaId]);

    // リダイレクト先のパラメータを構築
    $redirectParams = ['success' => 'delete'];

    // 現在のページ番号を保持
    if (isset($_POST['current_page']) && is_numeric($_POST['current_page'])) {
        $redirectParams['page'] = (int)$_POST['current_page'];
    }

    // フィルター設定を保持
    if (isset($_POST['filter']) && !empty($_POST['filter'])) {
        $redirectParams['filter'] = $_POST['filter'];
    }

    // 検索クエリを保持
    if (isset($_POST['search']) && !empty($_POST['search'])) {
        $redirectParams['search'] = $_POST['search'];
    }

    // ソート設定を保持
    if (isset($_POST['sort']) && !empty($_POST['sort'])) {
        $redirectParams['sort'] = $_POST['sort'];
    }

    // 成功メッセージ
    $_SESSION['delete_success'] = 'メディアが削除されました。';
    header('Location: index.php?' . http_build_query($redirectParams));
    exit;

} catch (Exception $e) {
    // リダイレクト先のパラメータを構築（エラー時）
    $redirectParams = ['error' => 'delete'];

    // 現在のページ番号を保持
    if (isset($_POST['current_page']) && is_numeric($_POST['current_page'])) {
        $redirectParams['page'] = (int)$_POST['current_page'];
    }

    // フィルター設定を保持
    if (isset($_POST['filter']) && !empty($_POST['filter'])) {
        $redirectParams['filter'] = $_POST['filter'];
    }

    // 検索クエリを保持
    if (isset($_POST['search']) && !empty($_POST['search'])) {
        $redirectParams['search'] = $_POST['search'];
    }

    // ソート設定を保持
    if (isset($_POST['sort']) && !empty($_POST['sort'])) {
        $redirectParams['sort'] = $_POST['sort'];
    }

    // エラーメッセージ
    $_SESSION['delete_error'] = $e->getMessage();
    header('Location: index.php?' . http_build_query($redirectParams));
    exit;
}
?>
