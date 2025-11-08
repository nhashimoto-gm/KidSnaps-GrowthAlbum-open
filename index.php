<?php
/**
 * KidSnaps Growth Album - メインギャラリーページ
 * 写真と動画を表示
 */

require_once 'config/database.php';

// セッション開始（アップロードメッセージ用）
session_start();

$pageTitle = 'ギャラリー';

// データベースからメディアファイルを取得
try {
    $pdo = getDbConnection();

    // フィルター処理
    $filterType = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

    // SQL構築
    $sql = "SELECT * FROM media_files WHERE 1=1";
    $params = [];

    if ($filterType === 'image') {
        $sql .= " AND file_type = 'image'";
    } elseif ($filterType === 'video') {
        $sql .= " AND file_type = 'video'";
    }

    if (!empty($searchQuery)) {
        $sql .= " AND (title LIKE :search OR description LIKE :search OR filename LIKE :search)";
        $params[':search'] = '%' . $searchQuery . '%';
    }

    $sql .= " ORDER BY upload_date DESC";

    $stmt = executeQuery($pdo, $sql, $params);
    $mediaFiles = $stmt->fetchAll();
} catch (Exception $e) {
    $error = "メディアの読み込みエラー: " . $e->getMessage();
    $mediaFiles = [];
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
                            <span data-i18n="media-count"><?php echo count($mediaFiles); ?> 件</span>
                        </span>
                    </p>
                </div>
                <div class="mt-3 mt-md-0">
                    <button class="btn btn-primary btn-lg shadow" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <i class="bi bi-cloud-upload-fill me-2"></i><span data-i18n="upload-button">メディアをアップロード</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?>
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
                    echo 'メディアファイルが正常にアップロードされました！';
                }
            } elseif ($_GET['success'] == 'partial') {
                if (isset($_SESSION['upload_partial'])) {
                    echo htmlspecialchars($_SESSION['upload_partial']);
                    unset($_SESSION['upload_partial']);
                } else {
                    echo '一部のファイルがアップロードされました。';
                }
            } elseif ($_GET['success'] == 'delete') {
                echo 'メディアファイルが削除されました。';
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
                        <div class="col-md-6">
                            <label class="form-label fw-bold">
                                <i class="bi bi-funnel"></i> <span data-i18n="filter-label">フィルター</span>
                            </label>
                            <select name="filter" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?php echo $filterType === 'all' ? 'selected' : ''; ?> data-i18n="filter-all">すべて</option>
                                <option value="image" <?php echo $filterType === 'image' ? 'selected' : ''; ?> data-i18n="filter-image">写真のみ</option>
                                <option value="video" <?php echo $filterType === 'video' ? 'selected' : ''; ?> data-i18n="filter-video">動画のみ</option>
                            </select>
                        </div>
                        <div class="col-md-6">
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

                        <div class="card-footer bg-transparent">
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
                            <span data-i18n="file-info">対応形式: JPEG, PNG, GIF, HEIC, MP4, MOV, AVI (各ファイル最大50MB、複数選択可)</span>
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

<?php include 'includes/footer.php'; ?>
