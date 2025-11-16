<?php
/**
 * KidSnaps Growth Album - アルバム詳細ページ
 * アルバム内のメディアファイルを表示
 */

require_once 'config/database.php';
require_once 'lib/album_processor.php';

session_start();

// アルバムIDを取得
$albumId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($albumId <= 0) {
    header('Location: albums.php');
    exit;
}

// アルバムプロセッサー初期化
$albumProcessor = new AlbumProcessor();

try {
    // アルバム情報を取得
    $album = $albumProcessor->getAlbum($albumId);

    if (!$album) {
        throw new Exception('アルバムが見つかりません。');
    }

    // ソート処理
    $sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'display_order';

    // アルバムのメディアを取得
    $mediaFiles = $albumProcessor->getAlbumMedia($albumId, $sortBy);

} catch (Exception $e) {
    $error = 'エラー: ' . htmlspecialchars($e->getMessage());
    header('Location: albums.php');
    exit;
}

$pageTitle = $album['title'];
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
        .media-card {
            transition: transform 0.2s;
            cursor: pointer;
        }

        .media-card:hover {
            transform: scale(1.05);
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
        }

        .media-thumbnail {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .album-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }

        body {
            background-color: #f8f9fa;
        }

        .video-play-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 3rem;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
            pointer-events: none;
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
                        <a class="nav-link" href="albums.php">
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

    <!-- アルバムヘッダー -->
    <div class="album-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h1 class="display-4 fw-bold mb-2">
                        <i class="bi bi-folder-fill"></i> <?php echo htmlspecialchars($album['title']); ?>
                    </h1>
                    <?php if ($album['description']): ?>
                    <p class="lead mb-3"><?php echo nl2br(htmlspecialchars($album['description'])); ?></p>
                    <?php endif; ?>
                    <div class="d-flex gap-3">
                        <span class="badge bg-light text-dark">
                            <i class="bi bi-images"></i> <?php echo count($mediaFiles); ?>件
                        </span>
                        <span class="badge bg-light text-dark">
                            <i class="bi bi-calendar3"></i> <?php echo date('Y年m月d日', strtotime($album['created_at'])); ?>
                        </span>
                    </div>
                </div>
                <div>
                    <a href="albums.php" class="btn btn-light">
                        <i class="bi bi-arrow-left"></i> 戻る
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- ソートバー -->
        <?php if (count($mediaFiles) > 0): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form method="GET" action="album_detail.php" class="row g-3">
                            <input type="hidden" name="id" value="<?php echo $albumId; ?>">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <label class="form-label fw-bold mb-0 me-2 text-nowrap">
                                        <i class="bi bi-sort-down"></i> 並び順
                                    </label>
                                    <select name="sort" class="form-select" onchange="this.form.submit()">
                                        <option value="display_order" <?php echo $sortBy === 'display_order' ? 'selected' : ''; ?>>表示順</option>
                                        <option value="added_at_desc" <?php echo $sortBy === 'added_at_desc' ? 'selected' : ''; ?>>追加日（新しい順）</option>
                                        <option value="added_at_asc" <?php echo $sortBy === 'added_at_asc' ? 'selected' : ''; ?>>追加日（古い順）</option>
                                        <option value="filename" <?php echo $sortBy === 'filename' ? 'selected' : ''; ?>>ファイル名</option>
                                        <option value="exif_datetime_desc" <?php echo $sortBy === 'exif_datetime_desc' ? 'selected' : ''; ?>>撮影日時（新しい順）</option>
                                        <option value="exif_datetime_asc" <?php echo $sortBy === 'exif_datetime_asc' ? 'selected' : ''; ?>>撮影日時（古い順）</option>
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- メディアグリッド -->
        <?php if (empty($mediaFiles)): ?>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info text-center py-5">
                    <i class="bi bi-info-circle display-1 d-block mb-3"></i>
                    <h4>メディアファイルがありません</h4>
                    <p>このアルバムにはまだメディアファイルが登録されていません。</p>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="row g-4 mb-5">
            <?php foreach ($mediaFiles as $media):
                $rotation = $media['rotation'] ?? 0;
                $rotateClass = "rotate-{$rotation}";
            ?>
            <div class="col-sm-6 col-md-4 col-lg-3">
                <div class="card h-100 shadow-sm media-card" onclick="viewMedia(<?php echo $media['id']; ?>)">
                    <div class="position-relative">
                        <?php if ($media['file_type'] === 'image'): ?>
                            <?php
                            $imageSrc = (!empty($media['thumbnail_path']) && file_exists($media['thumbnail_path']))
                                ? $media['thumbnail_path']
                                : $media['file_path'];

                            $imageWebPSrc = null;
                            if (!empty($media['thumbnail_webp_path']) && file_exists($media['thumbnail_webp_path'])) {
                                $imageWebPSrc = $media['thumbnail_webp_path'];
                            }
                            ?>
                            <?php if ($imageWebPSrc): ?>
                            <picture>
                                <source srcset="<?php echo htmlspecialchars($imageWebPSrc); ?>" type="image/webp">
                                <img src="<?php echo htmlspecialchars($imageSrc); ?>" class="card-img-top media-thumbnail" alt="<?php echo htmlspecialchars($media['title'] ?? $media['filename']); ?>" loading="lazy">
                            </picture>
                            <?php else: ?>
                            <img src="<?php echo htmlspecialchars($imageSrc); ?>" class="card-img-top media-thumbnail" alt="<?php echo htmlspecialchars($media['title'] ?? $media['filename']); ?>" loading="lazy">
                            <?php endif; ?>
                            <div class="position-absolute top-0 start-0 m-2">
                                <span class="badge bg-info"><i class="bi bi-image"></i> 画像</span>
                            </div>
                        <?php else: ?>
                            <?php if (!empty($media['thumbnail_path']) && file_exists($media['thumbnail_path'])): ?>
                            <img src="<?php echo htmlspecialchars($media['thumbnail_path']); ?>" class="card-img-top media-thumbnail" alt="<?php echo htmlspecialchars($media['title'] ?? $media['filename']); ?>" loading="lazy">
                            <?php else: ?>
                            <video class="card-img-top media-thumbnail" preload="metadata" muted playsinline>
                                <source src="<?php echo htmlspecialchars($media['file_path']); ?>#t=0.5">
                            </video>
                            <?php endif; ?>
                            <div class="video-play-overlay">
                                <i class="bi bi-play-circle-fill"></i>
                            </div>
                            <div class="position-absolute top-0 start-0 m-2">
                                <span class="badge bg-danger"><i class="bi bi-camera-video"></i> 動画</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-body">
                        <h6 class="card-title text-truncate">
                            <?php echo htmlspecialchars($media['title'] ?? $media['filename']); ?>
                        </h6>
                        <small class="text-muted">
                            <i class="bi bi-calendar3"></i>
                            <?php echo date('Y/m/d H:i', strtotime($media['upload_date'])); ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
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

    <script>
        function viewMedia(mediaId) {
            // 既存のギャラリービューアーがあれば連携
            // ここでは簡易的にメディア詳細ページへリダイレクト
            window.open('index.php#media-' + mediaId, '_blank');
        }
    </script>
</body>
</html>
