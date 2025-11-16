<?php
// セッション開始（必要に応じて）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSS/JSファイルの自動切り替え（minify版優先）
$cssFile = file_exists('assets/css/style.min.css') ? 'style.min.css' : 'style.css';
$jsFile = file_exists('assets/js/script.min.js') ? 'script.min.js' : 'script.js';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>KidSnaps Growth Album</title>
    <?php if (isset($pageTitleKey)): ?>
    <meta name="page-title-key" content="<?php echo htmlspecialchars($pageTitleKey); ?>" data-page-title="<?php echo htmlspecialchars($pageTitleKey); ?>">
    <?php endif; ?>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Custom CSS (本番環境ではminify版を使用) -->
    <link rel="stylesheet" href="assets/css/<?php echo $cssFile; ?>?v=1.0.0">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">

    <!-- EXIF.js Library for reading EXIF data from images -->
    <script src="https://cdn.jsdelivr.net/npm/exif-js"></script>

    <!-- piexifjs Library for reading/writing EXIF data -->
    <script src="https://cdn.jsdelivr.net/npm/piexifjs@1.0.6/piexif.min.js"></script>

    <!-- heic2any Library for converting HEIC to JPEG on client-side -->
    <script src="https://cdn.jsdelivr.net/npm/heic2any/dist/heic2any.min.js"></script>

    <!-- Inline script to prevent language flash by applying language before DOM renders -->
    <script>
        // Initialize language from localStorage immediately
        const storedLanguage = localStorage.getItem('kidsnaps-language') || 'en';

        // Set HTML lang attribute dynamically
        document.documentElement.lang = storedLanguage;

        // Set default language variable for script.js to use
        if (typeof currentLanguage === 'undefined') {
            var currentLanguage = storedLanguage;
        }
    </script>

    <!-- Custom JavaScript - Load in head with defer to execute after DOM is parsed (本番環境ではminify版を使用) -->
    <script src="assets/js/<?php echo $jsFile; ?>?v=1.0.0" defer></script>
</head>
<body>
    <!-- ナビゲーションバー -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="bi bi-camera-fill me-2"></i>
                <span class="fw-bold">KidSnaps Growth Album</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>"
                           href="index.php">
                            <i class="bi bi-images me-1"></i><span data-i18n="nav-gallery">Gallery</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="bi bi-cloud-upload me-1"></i><span data-i18n="nav-upload">Upload</span>
                        </a>
                    </li>
                    <?php
                    // 管理者モードチェック
                    $isAdminMode = isset($_SESSION['admin_mode']) && $_SESSION['admin_mode'];
                    ?>
                    <?php if ($isAdminMode): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#refreshExifModal">
                            <i class="bi bi-arrow-repeat me-1"></i><span data-i18n="nav-refresh-exif">Refresh EXIF</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <?php if ($isAdminMode): ?>
                        <a class="nav-link" href="toggle_admin_mode.php" title="Switch to User Mode">
                            <i class="bi bi-shield-fill-check me-1"></i>
                            <span data-i18n="admin-mode-admin">Admin</span>
                        </a>
                        <?php else: ?>
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#adminPasswordModal" title="Switch to Admin Mode">
                            <i class="bi bi-shield-lock me-1"></i>
                            <span data-i18n="admin-mode-user">User</span>
                        </a>
                        <?php endif; ?>
                    </li>
                    <li class="nav-item ms-2">
                        <button class="lang-toggle-btn" onclick="toggleLanguage()" id="langToggleBtn">
                            <i class="bi bi-translate me-1"></i><span id="langToggleText">EN</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="theme-toggle-btn" onclick="toggleTheme()" id="themeToggleBtn">
                            <i class="bi bi-moon-fill" id="themeIcon"></i>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- メインコンテンツ -->
    <main class="container-fluid py-4">
