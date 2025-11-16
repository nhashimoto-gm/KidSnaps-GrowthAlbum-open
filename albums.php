<?php
/**
 * KidSnaps Growth Album - アルバム一覧ページ
 * 作成されたアルバムを表示
 */

require_once 'config/database.php';
require_once 'lib/album_processor.php';

session_start();

$pageTitle = 'Albums';

// アルバムプロセッサー初期化
$albumProcessor = new AlbumProcessor();

// ページネーション設定
$itemsPerPage = 12;
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// ソート処理
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'created_at_desc';

try {
    // アルバム総数を取得
    $totalItems = $albumProcessor->getAlbumCount();
    $totalPages = ceil($totalItems / $itemsPerPage);

    // アルバム一覧を取得
    $albums = $albumProcessor->getAllAlbums($sortBy, $itemsPerPage, $offset);
} catch (Exception $e) {
    $error = 'アルバム読み込みエラー: ' . htmlspecialchars($e->getMessage());
    $albums = [];
    $totalItems = 0;
    $totalPages = 0;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - KidSnaps Growth Album</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        .album-card {
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            height: 100%;
        }

        .album-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
        }

        .album-cover {
            width: 100%;
            height: 250px;
            object-fit: cover;
            background-color: #f0f0f0;
        }

        .album-cover-placeholder {
            width: 100%;
            height: 250px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #e9ecef;
            color: #6c757d;
            font-size: 3rem;
        }

        body {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-camera"></i> KidSnaps Growth Album
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-images"></i> ギャラリー
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="albums.php">
                            <i class="bi bi-folder"></i> アルバム
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="album_upload.php">
                            <i class="bi bi-cloud-upload"></i> ZIPアップロード
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- ヘッダーセクション -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h1 class="display-5 fw-bold text-primary mb-2">
                            <i class="bi bi-folder-fill"></i> アルバム
                            <span class="badge bg-secondary"><?php echo $totalItems; ?>件</span>
                        </h1>
                        <p class="text-muted">ZIPファイルからインポートしたアルバムを管理</p>
                    </div>
                    <div>
                        <a href="album_upload.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-cloud-upload"></i> ZIPアップロード
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- ソートバー -->
        <?php if ($totalItems > 0): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form method="GET" action="albums.php" class="row g-3">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <label class="form-label fw-bold mb-0 me-2 text-nowrap">
                                        <i class="bi bi-sort-down"></i> 並び順
                                    </label>
                                    <select name="sort" class="form-select" onchange="this.form.submit()">
                                        <option value="created_at_desc" <?php echo $sortBy === 'created_at_desc' ? 'selected' : ''; ?>>作成日（新しい順）</option>
                                        <option value="created_at_asc" <?php echo $sortBy === 'created_at_asc' ? 'selected' : ''; ?>>作成日（古い順）</option>
                                        <option value="updated_at_desc" <?php echo $sortBy === 'updated_at_desc' ? 'selected' : ''; ?>>更新日（新しい順）</option>
                                        <option value="title_asc" <?php echo $sortBy === 'title_asc' ? 'selected' : ''; ?>>タイトル（A-Z）</option>
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- アルバムグリッド -->
        <?php if (empty($albums)): ?>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info text-center py-5">
                    <i class="bi bi-folder-x display-1 d-block mb-3"></i>
                    <h4>アルバムがありません</h4>
                    <p class="mb-3">まだアルバムが作成されていません。</p>
                    <a href="album_upload.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-cloud-upload"></i> 最初のアルバムを作成
                    </a>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($albums as $album): ?>
            <div class="col-sm-6 col-md-4 col-lg-3">
                <div class="card album-card shadow-sm" onclick="location.href='album_detail.php?id=<?php echo $album['id']; ?>'">
                    <?php if ($album['cover_thumbnail']): ?>
                        <?php if ($album['cover_thumbnail_webp']): ?>
                        <picture>
                            <source srcset="<?php echo htmlspecialchars($album['cover_thumbnail_webp']); ?>" type="image/webp">
                            <img src="<?php echo htmlspecialchars($album['cover_thumbnail']); ?>" class="card-img-top album-cover" alt="<?php echo htmlspecialchars($album['title']); ?>" loading="lazy">
                        </picture>
                        <?php else: ?>
                        <img src="<?php echo htmlspecialchars($album['cover_thumbnail']); ?>" class="card-img-top album-cover" alt="<?php echo htmlspecialchars($album['title']); ?>" loading="lazy">
                        <?php endif; ?>
                    <?php else: ?>
                    <div class="album-cover-placeholder">
                        <i class="bi bi-folder"></i>
                    </div>
                    <?php endif; ?>

                    <div class="card-body">
                        <h5 class="card-title text-truncate fw-bold">
                            <?php echo htmlspecialchars($album['title']); ?>
                        </h5>
                        <?php if ($album['description']): ?>
                        <p class="card-text small text-muted text-truncate">
                            <?php echo htmlspecialchars($album['description']); ?>
                        </p>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="bi bi-images"></i> <?php echo $album['media_count']; ?>件
                            </small>
                            <small class="text-muted">
                                <i class="bi bi-calendar3"></i> <?php echo date('Y/m/d', strtotime($album['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ページネーション -->
        <?php if ($totalPages > 1): ?>
        <div class="row mt-4">
            <div class="col-12">
                <nav aria-label="Pagination">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage - 1])); ?>">
                                <span aria-hidden="true">&laquo;</span> 前へ
                            </a>
                        </li>

                        <?php
                        $range = 2;
                        $startPage = max(1, $currentPage - $range);
                        $endPage = min($totalPages, $currentPage + $range);

                        if ($startPage > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '">1</a></li>';
                            if ($startPage > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }

                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                        <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>

                        <?php
                        if ($endPage < $totalPages) {
                            if ($endPage < $totalPages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => $totalPages])) . '">' . $totalPages . '</a></li>';
                        }
                        ?>

                        <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage + 1])); ?>">
                                次へ <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <footer class="bg-light py-4 mt-5">
        <div class="container text-center">
            <p class="text-muted mb-0">
                <i class="bi bi-heart-fill text-danger"></i> KidSnaps Growth Album - 大切な思い出を記録し、成長を見守るアルバム
            </p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
