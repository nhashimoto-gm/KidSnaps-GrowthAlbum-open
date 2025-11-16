    </main>

    <!-- 管理者パスワード入力モーダル -->
    <div class="modal fade" id="adminPasswordModal" tabindex="-1" aria-labelledby="adminPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="adminPasswordModalLabel">
                        <i class="bi bi-shield-lock-fill"></i> <span data-i18n="admin-password-modal-title">Administrator Authentication</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="toggle_admin_mode.php" method="POST" id="adminPasswordForm">
                    <div class="modal-body">
                        <?php if (isset($_SESSION['admin_auth_error']) && $_SESSION['admin_auth_error']): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <span data-i18n="admin-password-error">Incorrect password.</span>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['admin_auth_error']); endif; ?>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <span data-i18n="admin-password-info">Please enter the administrator password to switch to admin mode.</span>
                        </div>

                        <div class="mb-3">
                            <label for="adminPassword" class="form-label fw-bold">
                                <span data-i18n="admin-password-label">Administrator Password</span> <span class="text-danger">*</span>
                            </label>
                            <input type="password" class="form-control" id="adminPassword" name="password"
                                   placeholder="Enter password" data-i18n-placeholder="admin-password-placeholder" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> <span data-i18n="cancel">Cancel</span>
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-shield-fill-check"></i> <span data-i18n="admin-password-submit">Enable Admin Mode</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- フッター -->
    <footer class="bg-dark text-white text-center py-4 mt-5">
        <div class="container">
            <p class="mb-2">
                <i class="bi bi-camera-fill"></i>
                KidSnaps Growth Album &copy; <?php echo date('Y'); ?>
            </p>
            <p class="small text-muted mb-0" data-i18n="footer-tagline">
                Capture precious memories and watch your children grow
            </p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- SparkMD5 Library for file hashing -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/spark-md5/3.0.2/spark-md5.min.js"></script>

    <!-- PHP変数をJavaScriptに渡す -->
    <script>
        // 管理者モードの状態をJavaScriptに渡す
        const isAdmin = <?php echo isset($isAdminMode) && $isAdminMode ? 'true' : 'false'; ?>;
    </script>

    <!-- Custom JavaScript - script.js is loaded in header.php -->
    <script src="assets/js/duplicate-checker.js"></script>
    <script src="assets/js/refresh-exif.js"></script>
</body>
</html>
