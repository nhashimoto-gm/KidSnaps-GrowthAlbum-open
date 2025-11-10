<?php
/**
 * KidSnaps Growth Album - メインギャラリーページ
 * 写真と動画を表示
 */

require_once 'config/database.php';

// セッション開始（アップロードメッセージ用）
session_start();

$pageTitle = 'ギャラリー';
$pageTitleKey = 'page-title-gallery'; // 多言語化キー

// データベースからメディアファイルを取得
try {
    $pdo = getDbConnection();

    // ページネーション設定
    $itemsPerPage = 12;
    $currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($currentPage - 1) * $itemsPerPage;

    // フィルター処理
    $filterType = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

    // ソート処理
    $sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'upload_date_desc';

    // 許可されたソートオプション
    $allowedSorts = [
        'upload_date_desc' => 'upload_date DESC',
        'upload_date_asc' => 'upload_date ASC',
        'exif_datetime_desc' => 'exif_datetime DESC, upload_date DESC',
        'exif_datetime_asc' => 'exif_datetime ASC, upload_date ASC',
        'location' => 'exif_location_name ASC, upload_date DESC',
        'filename_asc' => 'filename ASC',
        'filename_desc' => 'filename DESC'
    ];

    // ソート順を決定（不正な値の場合はデフォルト）
    $orderBy = isset($allowedSorts[$sortBy]) ? $allowedSorts[$sortBy] : $allowedSorts['upload_date_desc'];

    // WHERE条件の構築
    $whereClause = "WHERE 1=1";
    $params = [];

    if ($filterType === 'image') {
        $whereClause .= " AND file_type = 'image'";
    } elseif ($filterType === 'video') {
        $whereClause .= " AND file_type = 'video'";
    }

    if (!empty($searchQuery)) {
        $whereClause .= " AND (title LIKE :search1 OR description LIKE :search2 OR filename LIKE :search3)";
        $searchPattern = '%' . $searchQuery . '%';
        $params[':search1'] = $searchPattern;
        $params[':search2'] = $searchPattern;
        $params[':search3'] = $searchPattern;
    }

    // 総件数を取得
    $countSql = "SELECT COUNT(*) FROM media_files " . $whereClause;
    $countStmt = executeQuery($pdo, $countSql, $params);
    $totalItems = $countStmt->fetchColumn();
    $totalPages = ceil($totalItems / $itemsPerPage);

    // データを取得（ページネーション付き）
    $sql = "SELECT * FROM media_files " . $whereClause . " ORDER BY " . $orderBy . " LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);

    // パラメータをバインド
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $mediaFiles = $stmt->fetchAll();
} catch (Exception $e) {
    $error = '<span data-i18n="media-load-error">メディアの読み込みエラー: </span>' . htmlspecialchars($e->getMessage());
    $mediaFiles = [];
    $totalItems = 0;
    $totalPages = 0;
    $currentPage = 1;
}

include 'includes/header.php';
?>

<div class="container">
    <!-- ヘッダーセクション -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h1 class="display-5 fw-bold text-primary mb-2">
                        <i class="bi bi-images"></i> <span data-i18n="page-title">メディアギャラリー</span>
                    </h1>
                    <p class="text-muted">
                        <span data-i18n="page-subtitle">アップロードした写真と動画を閲覧できます</span>
                        <span class="badge bg-secondary">
                            <span data-i18n="total-items" data-count="<?php echo $totalItems ?? 0; ?>">全<?php echo $totalItems ?? 0; ?>件</span>
                        </span>
                        <?php if (isset($totalPages) && $totalPages > 1): ?>
                        <span class="badge bg-info">
                            <span data-i18n="page-of-pages" data-current="<?php echo $currentPage; ?>" data-total="<?php echo $totalPages; ?>">
                                <?php echo $currentPage; ?> / <?php echo $totalPages; ?> ページ
                            </span>
                        </span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="mt-3 mt-md-0">
                    <button class="btn btn-secondary btn-lg shadow me-2" data-bs-toggle="modal" data-bs-target="#refreshExifModal">
                        <i class="bi bi-arrow-repeat me-2"></i><span data-i18n="refresh-exif-button">EXIF洗替</span>
                    </button>
                    <button class="btn btn-primary btn-lg shadow" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <i class="bi bi-cloud-upload-fill me-2"></i><span data-i18n="upload-button">メディアをアップロード</span>
                    </button>
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

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php
            if ($_GET['success'] == 'upload') {
                if (isset($_SESSION['upload_success'])) {
                    echo htmlspecialchars($_SESSION['upload_success']);
                    unset($_SESSION['upload_success']);
                } else {
                    echo '<span data-i18n="upload-success">メディアファイルが正常にアップロードされました！</span>';
                }
            } elseif ($_GET['success'] == 'partial') {
                if (isset($_SESSION['upload_partial'])) {
                    echo htmlspecialchars($_SESSION['upload_partial']);
                    unset($_SESSION['upload_partial']);
                } else {
                    echo '<span data-i18n="upload-partial">一部のファイルがアップロードされました。</span>';
                }
            } elseif ($_GET['success'] == 'delete') {
                echo '<span data-i18n="delete-success">メディアファイルが削除されました。</span>';
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error']) && isset($_SESSION['upload_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php
            echo htmlspecialchars($_SESSION['upload_error']);
            unset($_SESSION['upload_error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- フィルターとサーチバー -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="GET" action="index.php" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">
                                <i class="bi bi-funnel"></i> <span data-i18n="filter-label">フィルター</span>
                            </label>
                            <select name="filter" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?php echo $filterType === 'all' ? 'selected' : ''; ?> data-i18n="filter-all">すべて</option>
                                <option value="image" <?php echo $filterType === 'image' ? 'selected' : ''; ?> data-i18n="filter-image">写真のみ</option>
                                <option value="video" <?php echo $filterType === 'video' ? 'selected' : ''; ?> data-i18n="filter-video">動画のみ</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">
                                <i class="bi bi-sort-down"></i> <span data-i18n="sort-label">Sort by</span>
                            </label>
                            <select name="sort" class="form-select" onchange="this.form.submit()">
                                <option value="upload_date_desc" <?php echo $sortBy === 'upload_date_desc' ? 'selected' : ''; ?> data-i18n="sort-upload-date-desc">Upload Date (Newest)</option>
                                <option value="upload_date_asc" <?php echo $sortBy === 'upload_date_asc' ? 'selected' : ''; ?> data-i18n="sort-upload-date-asc">Upload Date (Oldest)</option>
                                <option value="exif_datetime_desc" <?php echo $sortBy === 'exif_datetime_desc' ? 'selected' : ''; ?> data-i18n="sort-exif-datetime-desc">Taken Date (Newest)</option>
                                <option value="exif_datetime_asc" <?php echo $sortBy === 'exif_datetime_asc' ? 'selected' : ''; ?> data-i18n="sort-exif-datetime-asc">Taken Date (Oldest)</option>
                                <option value="location" <?php echo $sortBy === 'location' ? 'selected' : ''; ?> data-i18n="sort-location">Location Name</option>
                                <option value="filename_asc" <?php echo $sortBy === 'filename_asc' ? 'selected' : ''; ?> data-i18n="sort-filename-asc">Filename (A-Z)</option>
                                <option value="filename_desc" <?php echo $sortBy === 'filename_desc' ? 'selected' : ''; ?> data-i18n="sort-filename-desc">Filename (Z-A)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">
                                <i class="bi bi-search"></i> <span data-i18n="search-label">検索</span>
                            </label>
                            <div class="input-group">
                                <input type="text" name="search" class="form-control"
                                       placeholder="タイトル、説明、ファイル名で検索..."
                                       data-i18n-placeholder="search-placeholder"
                                       value="<?php echo htmlspecialchars($searchQuery); ?>">
                                <button class="btn btn-outline-primary" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- メディアグリッド -->
    <?php if (empty($mediaFiles)): ?>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info text-center py-5">
                    <i class="bi bi-info-circle display-1 d-block mb-3"></i>
                    <h4 data-i18n="no-media-title">メディアファイルがありません</h4>
                    <p class="mb-3" data-i18n="no-media-text">まだ写真や動画がアップロードされていません。</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <i class="bi bi-cloud-upload me-2"></i><span data-i18n="first-upload">最初のメディアをアップロード</span>
                    </button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4" id="mediaGrid">
            <?php foreach ($mediaFiles as $media):
                $rotation = $media['rotation'] ?? 0;
                $rotateClass = "rotate-{$rotation}";
            ?>
                <div class="col-sm-6 col-md-4 col-lg-3 media-item" data-type="<?php echo $media['file_type']; ?>" data-media-id="<?php echo $media['id']; ?>">
                    <div class="card h-100 shadow-sm media-card" style="cursor: pointer;"
                         data-media='<?php echo htmlspecialchars(json_encode($media), ENT_QUOTES, 'UTF-8'); ?>'
                         onclick="viewMediaFromElement(this)">
                        <div class="card-img-wrapper position-relative">
                            <?php if ($media['file_type'] === 'image'): ?>
                                <?php
                                // サムネイルがあればサムネイルを表示、なければ元画像を表示
                                $imageSrc = (!empty($media['thumbnail_path']) && file_exists($media['thumbnail_path']))
                                    ? $media['thumbnail_path']
                                    : $media['file_path'];
                                ?>
                                <img src="<?php echo htmlspecialchars($imageSrc); ?>"
                                     class="card-img-top media-thumbnail <?php echo $rotateClass; ?>"
                                     alt="<?php echo htmlspecialchars($media['title'] ?? $media['filename']); ?>"
                                     loading="lazy">
                                <div class="media-type-badge badge bg-info position-absolute top-0 start-0 m-2">
                                    <i class="bi bi-image"></i> <span data-i18n="image-badge">画像</span>
                                </div>
                            <?php else: ?>
                                <div class="video-thumbnail-wrapper">
                                    <?php if (!empty($media['thumbnail_path']) && file_exists($media['thumbnail_path'])): ?>
                                        <!-- サムネイル画像が存在する場合 -->
                                        <img src="<?php echo htmlspecialchars($media['thumbnail_path']); ?>"
                                             class="card-img-top media-thumbnail <?php echo $rotateClass; ?>"
                                             alt="<?php echo htmlspecialchars($media['title'] ?? $media['filename']); ?>"
                                             loading="lazy">
                                    <?php else: ?>
                                        <!-- サムネイルがない場合はvideoタグでプレビュー -->
                                        <video class="card-img-top media-thumbnail <?php echo $rotateClass; ?>" preload="metadata" muted playsinline>
                                            <?php if ($media['mime_type'] === 'video/quicktime' || strtolower(pathinfo($media['file_path'], PATHINFO_EXTENSION)) === 'mov'): ?>
                                                <!-- .movファイルの場合はtype属性を省略 -->
                                                <source src="<?php echo htmlspecialchars($media['file_path']); ?>#t=0.5">
                                            <?php else: ?>
                                                <source src="<?php echo htmlspecialchars($media['file_path']); ?>#t=0.5"
                                                        type="<?php echo htmlspecialchars($media['mime_type']); ?>">
                                            <?php endif; ?>
                                        </video>
                                    <?php endif; ?>
                                    <div class="video-play-overlay">
                                        <i class="bi bi-play-circle-fill"></i>
                                    </div>
                                </div>
                                <div class="media-type-badge badge bg-danger position-absolute top-0 start-0 m-2">
                                    <i class="bi bi-camera-video"></i> <span data-i18n="video-badge">動画</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="card-body">
                            <h6 class="card-title text-truncate fw-bold">
                                <?php echo htmlspecialchars($media['title'] ?? $media['filename']); ?>
                            </h6>
                            <?php if ($media['description']): ?>
                                <p class="card-text small text-muted text-truncate">
                                    <?php echo htmlspecialchars($media['description']); ?>
                                </p>
                            <?php endif; ?>
                            <p class="card-text">
                                <small class="text-muted">
                                    <i class="bi bi-calendar3"></i>
                                    <?php echo date('Y/m/d H:i', strtotime($media['upload_date'])); ?>
                                </small>
                            </p>
                        </div>

                        <div class="card-footer bg-transparent" onclick="event.stopPropagation();">
                            <div class="btn-group w-100" role="group">
                                <a href="<?php echo htmlspecialchars($media['file_path']); ?>"
                                   class="btn btn-sm btn-outline-success" download
                                   onclick="event.stopPropagation();">
                                    <i class="bi bi-download"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                        onclick="event.stopPropagation(); deleteMedia(<?php echo $media['id']; ?>, '<?php echo htmlspecialchars($media['filename']); ?>')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ページネーション -->
        <?php if (isset($totalPages) && $totalPages > 1): ?>
        <div class="row mt-4">
            <div class="col-12">
                <nav aria-label="Pagination">
                    <ul class="pagination justify-content-center">
                        <!-- 前へボタン -->
                        <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage - 1])); ?>" data-i18n-aria="pagination-previous">
                                <span aria-hidden="true">&laquo;</span>
                                <span class="visually-hidden" data-i18n="pagination-previous">前へ</span>
                            </a>
                        </li>

                        <?php
                        // ページ番号の表示範囲を計算
                        $range = 2; // 現在のページの前後に表示するページ数
                        $startPage = max(1, $currentPage - $range);
                        $endPage = min($totalPages, $currentPage + $range);

                        // 最初のページ
                        if ($startPage > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '">1</a></li>';
                            if ($startPage > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }

                        // ページ番号
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                    <?php echo $i; ?>
                                    <?php if ($i == $currentPage): ?>
                                    <span class="visually-hidden" data-i18n="pagination-current">(現在のページ)</span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php
                        // 最後のページ
                        if ($endPage < $totalPages) {
                            if ($endPage < $totalPages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => $totalPages])) . '">' . $totalPages . '</a></li>';
                        }
                        ?>

                        <!-- 次へボタン -->
                        <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage + 1])); ?>" data-i18n-aria="pagination-next">
                                <span aria-hidden="true">&raquo;</span>
                                <span class="visually-hidden" data-i18n="pagination-next">次へ</span>
                            </a>
                        </li>
                    </ul>
                </nav>

                <!-- ページ情報 -->
                <p class="text-center text-muted">
                    <span data-i18n="pagination-info"
                          data-start="<?php echo $offset + 1; ?>"
                          data-end="<?php echo min($offset + $itemsPerPage, $totalItems); ?>"
                          data-total="<?php echo $totalItems; ?>">
                        <?php
                        $startItem = $offset + 1;
                        $endItem = min($offset + $itemsPerPage, $totalItems);
                        echo "{$startItem} - {$endItem} 件目 / 全 {$totalItems} 件";
                        ?>
                    </span>
                </p>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- アップロードモーダル -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="uploadModalLabel">
                    <i class="bi bi-cloud-upload"></i> <span data-i18n="upload-modal-title">メディアアップロード</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="upload.php" method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="mediaFile" class="form-label fw-bold">
                            <span data-i18n="file-select">ファイル選択</span> <span class="text-danger">*</span>
                        </label>
                        <div class="custom-file-input-wrapper">
                            <input type="file" class="form-control custom-file-input" id="mediaFile" name="mediaFile[]"
                                   accept="image/*,.heic,.heif,video/*" multiple required>
                            <label class="custom-file-label" for="mediaFile" id="fileInputLabel">
                                <i class="bi bi-cloud-upload"></i>
                                <span data-i18n="file-not-selected">クリックしてファイルを選択</span>
                            </label>
                        </div>
                        <!-- 動画サムネイル用のhidden input（複数対応） -->
                        <input type="file" id="videoThumbnail" name="videoThumbnail[]" style="display: none;" multiple>
                        <div class="form-text">
                            <i class="bi bi-info-circle"></i>
                            <span data-i18n="file-info">対応形式: JPEG, PNG, GIF, HEIC, MP4, MOV, AVI (各ファイル最大100MB、複数選択可)</span>
                        </div>
                        <div id="fileList" class="mt-2"></div>
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label fw-bold" data-i18n="title-label">タイトル</label>
                        <input type="text" class="form-control" id="title" name="title"
                               placeholder="例: 家族旅行 2024"
                               data-i18n-placeholder="title-placeholder">
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-bold" data-i18n="description-label">説明</label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                                  placeholder="このメディアについての説明を入力..."
                                  data-i18n-placeholder="description-placeholder"></textarea>
                    </div>

                    <div id="uploadProgress" class="progress d-none" style="height: 25px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                             style="width: 0%">0%</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> <span data-i18n="cancel">キャンセル</span>
                    </button>
                    <button type="submit" class="btn btn-primary" id="uploadBtn">
                        <i class="bi bi-cloud-upload"></i> <span data-i18n="upload">アップロード</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- メディアビューアーモーダル -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center" id="viewModalBody">
                <!-- メディアコンテンツがここに動的に挿入されます -->
            </div>
            <div class="modal-footer flex-column align-items-start">
                <!-- 回転コントロール -->
                <div id="rotationControls" class="w-100 mb-2 pb-2 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <label class="text-muted small mb-0">
                            <i class="bi bi-arrow-clockwise"></i> <span data-i18n="rotation-label">回転:</span>
                        </label>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="rotateMedia(-90)" title="反時計回りに90度回転">
                            <i class="bi bi-arrow-counterclockwise"></i> <span data-i18n="rotate-left">左に回転</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="rotateMedia(90)" title="時計回りに90度回転">
                            <i class="bi bi-arrow-clockwise"></i> <span data-i18n="rotate-right">右に回転</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" id="saveRotationBtn" onclick="saveRotation()" style="display: none;">
                            <i class="bi bi-check-circle"></i> <span data-i18n="save-rotation">回転を保存</span>
                        </button>
                        <span id="rotationStatus" class="text-muted small ms-2"></span>
                    </div>
                </div>

                <!-- 動画の進行状況インジケーター -->
                <div id="videoProgressIndicator" class="w-100 mb-2" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-primary" id="videoPlayPauseBtn" onclick="togglePlayPause()">
                                <i class="bi bi-play-fill" id="playPauseIcon"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="videoMuteBtn" onclick="toggleMute()">
                                <i class="bi bi-volume-up-fill" id="muteIcon"></i>
                            </button>
                            <small class="text-muted ms-2">
                                <span id="videoCurrentTime">0:00</span> / <span id="videoDuration">0:00</span>
                            </small>
                        </div>
                        <small class="text-muted">
                            <span id="videoProgressPercent">0</span>%
                        </small>
                    </div>
                    <div class="progress" style="height: 10px; cursor: pointer;" id="videoProgressContainer" onclick="seekVideo(event)">
                        <div id="videoProgressBar" class="progress-bar bg-primary" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
                <div id="viewModalInfo" class="text-start w-100"></div>
            </div>
        </div>
    </div>
</div>

<!-- EXIF洗替モーダル -->
<div class="modal fade" id="refreshExifModal" tabindex="-1" aria-labelledby="refreshExifModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title" id="refreshExifModalLabel">
                    <i class="bi bi-arrow-repeat"></i> <span data-i18n="refresh-exif-modal-title">EXIF情報洗替</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" id="refreshExifCloseBtn"></button>
            </div>
            <div class="modal-body">
                <div id="refreshExifStart">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <span data-i18n="refresh-exif-description">
                            既存の全メディアファイルのEXIF情報（撮影日時、GPS位置情報、カメラ情報など）を再抽出してデータベースを更新します。
                        </span>
                    </div>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <span data-i18n="refresh-exif-warning">
                            処理には時間がかかる場合があります。処理中はこのウィンドウを閉じないでください。
                        </span>
                    </div>
                </div>

                <div id="refreshExifProgress" style="display: none;">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted" data-i18n="refresh-exif-progress">処理中...</span>
                            <span class="fw-bold" id="refreshExifProgressPercent">0%</span>
                        </div>
                        <div class="progress" style="height: 25px;">
                            <div id="refreshExifProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-secondary"
                                 role="progressbar" style="width: 0%">0%</div>
                        </div>
                    </div>

                    <div class="row text-center mb-3">
                        <div class="col-4">
                            <div class="card">
                                <div class="card-body p-2">
                                    <div class="text-muted small" data-i18n="refresh-exif-total">全体</div>
                                    <div class="fs-5 fw-bold" id="refreshExifTotal">0</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card">
                                <div class="card-body p-2">
                                    <div class="text-muted small" data-i18n="refresh-exif-processed">処理済</div>
                                    <div class="fs-5 fw-bold text-primary" id="refreshExifProcessed">0</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card">
                                <div class="card-body p-2">
                                    <div class="text-muted small" data-i18n="refresh-exif-updated">更新</div>
                                    <div class="fs-5 fw-bold text-success" id="refreshExifUpdated">0</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="refreshExifErrorContainer" style="display: none;">
                        <div class="alert alert-danger">
                            <div class="fw-bold mb-2">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <span data-i18n="refresh-exif-errors">エラー:</span> <span id="refreshExifErrorCount">0</span>
                            </div>
                            <div id="refreshExifErrorMessages" class="small"></div>
                        </div>
                    </div>
                </div>

                <div id="refreshExifComplete" style="display: none;">
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i>
                        <span data-i18n="refresh-exif-complete">EXIF情報の洗替が完了しました。</span>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title" data-i18n="refresh-exif-summary">処理結果</h6>
                            <ul class="list-unstyled mb-0">
                                <li><strong data-i18n="refresh-exif-total">全体:</strong> <span id="refreshExifFinalTotal">0</span> <span data-i18n="refresh-exif-files">件</span></li>
                                <li><strong data-i18n="refresh-exif-processed">処理済:</strong> <span id="refreshExifFinalProcessed">0</span> <span data-i18n="refresh-exif-files">件</span></li>
                                <li><strong data-i18n="refresh-exif-updated">更新:</strong> <span id="refreshExifFinalUpdated">0</span> <span data-i18n="refresh-exif-files">件</span></li>
                                <li><strong data-i18n="refresh-exif-errors">エラー:</strong> <span id="refreshExifFinalErrors">0</span> <span data-i18n="refresh-exif-files">件</span></li>
                                <li><strong data-i18n="refresh-exif-elapsed">経過時間:</strong> <span id="refreshExifElapsedTime">0</span> <span data-i18n="refresh-exif-seconds">秒</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="refreshExifCancelBtn">
                    <i class="bi bi-x-circle"></i> <span data-i18n="cancel">キャンセル</span>
                </button>
                <button type="button" class="btn btn-primary" id="refreshExifStartBtn" onclick="startRefreshExif()">
                    <i class="bi bi-play-circle"></i> <span data-i18n="refresh-exif-start">洗替開始</span>
                </button>
                <button type="button" class="btn btn-success" id="refreshExifReloadBtn" style="display: none;" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise"></i> <span data-i18n="refresh-exif-reload">ページを再読み込み</span>
                </button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
