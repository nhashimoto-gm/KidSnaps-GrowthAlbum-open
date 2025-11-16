/**
 * KidSnaps Growth Album - JavaScript機能
 */

// ===== 多言語対応 =====
const translations = {
    en: {
        'nav-gallery': 'Gallery',
        'nav-upload': 'Upload',
        'nav-refresh-exif': 'Refresh EXIF',
        'nav-admin-mode': 'Mode',
        'admin-mode-admin': 'Admin',
        'admin-mode-user': 'User',
        'admin-password-modal-title': 'Administrator Authentication',
        'admin-password-info': 'Please enter the administrator password to switch to admin mode.',
        'admin-password-label': 'Administrator Password',
        'admin-password-placeholder': 'Enter password',
        'admin-password-submit': 'Enable Admin Mode',
        'admin-password-error': 'Incorrect password.',
        'page-title': 'Media Gallery',
        'page-subtitle': 'View uploaded photos and videos',
        'media-count-format': '{count} items',
        'upload-button': 'Upload Media',
        'filter-label': 'Filter',
        'filter-all': 'All',
        'filter-image': 'Photos Only',
        'filter-video': 'Videos Only',
        'search-label': 'Search',
        'search-placeholder': 'Search by title, description, filename...',
        'no-media-title': 'No Media Files',
        'no-media-text': 'No photos or videos have been uploaded yet.',
        'first-upload': 'Upload Your First Media',
        'upload-modal-title': 'Media Upload',
        'file-select': 'Select File',
        'file-info': 'Supported formats: JPEG, PNG, GIF, HEIC, MP4, MOV, AVI (Max 500MB per file, multiple files allowed)',
        'title-label': 'Title',
        'title-placeholder': 'e.g., Family Trip 2024',
        'description-label': 'Description',
        'description-placeholder': 'Enter a description of this media...',
        'cancel': 'Cancel',
        'upload': 'Upload',
        'view-button': 'View',
        'image-badge': 'Image',
        'video-badge': 'Video',
        'modal-filename': 'Filename:',
        'modal-size': 'Size:',
        'modal-uploaded': 'Uploaded:',
        'modal-format': 'Format:',
        'exif-datetime': 'Taken:',
        'exif-location': 'Location:',
        'exif-camera': 'Camera:',
        'exif-details': 'EXIF Details',
        'modal-title': 'Title',
        'modal-description': 'Description',
        'footer-tagline': 'Capture precious memories and watch your children grow',
        'file-not-selected': 'Click to select files',
        'files-selected': '{count} file(s) selected',
        'selected-files-header': 'Selected Files: {count}',
        'file-size-error': 'File size is too large',
        'total-items': 'Total {count} items',
        'page-of-pages': 'Page {current} of {total}',
        'pagination-previous': 'Previous',
        'pagination-next': 'Next',
        'pagination-current': '(current page)',
        'pagination-info': '{start} - {end} of {total} items',
        'duplicate-warning-title': 'Duplicate Files Found',
        'duplicate-already-uploaded': 'is already uploaded',
        'duplicate-upload-date': 'Upload date',
        'duplicate-auto-excluded': 'Duplicate files will be automatically excluded.',
        'duplicate-reselect-hint': 'To upload anyway, please reselect the files.',
        'duplicate-checking': 'Checking for duplicates...',
        'duplicate-check-status': 'Checking {filename}... ({current}/{total})',
        'duplicate-count-suffix': '{count} instance(s)',
        'rotation-label': 'Rotation:',
        'rotate-left': 'Rotate Left',
        'rotate-right': 'Rotate Right',
        'save-rotation': 'Save Rotation',
        'rotation-changed': '(Changed)',
        'rotation-saved': 'Saved!',
        'rotation-save-error': 'Failed to save',
        'converting': 'Converting...',
        'uploading': 'Upload',
        'file-size-error-alert': 'Files over 500MB are included. Please reselect the files.',
        'please-select-file': 'Please select a file.',
        'file-size-over-100mb': 'The following files exceed 500MB:',
        'preparing-upload': 'Preparing upload...',
        'generating-thumbnail': 'Generating thumbnail...',
        'upload-error': 'An error occurred during upload: ',
        'delete-confirm': 'Are you sure you want to delete "{filename}"?\nThis action cannot be undone.',
        'heic-conversion-failed': 'Failed to convert {filename}. Will upload the original file.',
        'uploading-count': 'Uploading {current}/{total}...',
        'saving': 'Saving...',
        'media-load-error': 'Media load error: ',
        'upload-success': 'Media file uploaded successfully!',
        'upload-partial': 'Some files were uploaded.',
        'delete-success': 'Media file deleted.',
        'media-data-load-failed': 'Failed to load media data.',
        'page-title-gallery': 'Gallery',
        'sort-label': 'Sort by',
        'sort-upload-date-desc': 'Upload Date (Newest)',
        'sort-upload-date-asc': 'Upload Date (Oldest)',
        'sort-exif-datetime-desc': 'Taken Date (Newest)',
        'sort-exif-datetime-asc': 'Taken Date (Oldest)',
        'sort-location': 'Location Name',
        'sort-filename-asc': 'Filename (A-Z)',
        'sort-filename-desc': 'Filename (Z-A)',
        'refresh-exif-button': 'Refresh EXIF',
        'refresh-exif-modal-title': 'EXIF Data Refresh',
        'refresh-exif-description': 'Re-extract EXIF information (capture date, GPS location, camera info, etc.) from all existing media files and update the database.',
        'refresh-exif-warning': 'This process may take some time. Please do not close this window during processing.',
        'refresh-exif-progress': 'Processing...',
        'refresh-exif-total': 'Total',
        'refresh-exif-processed': 'Processed',
        'refresh-exif-updated': 'Updated',
        'refresh-exif-errors': 'Errors',
        'refresh-exif-complete': 'EXIF data refresh completed.',
        'refresh-exif-summary': 'Processing Results',
        'refresh-exif-files': 'file(s)',
        'refresh-exif-elapsed': 'Elapsed Time',
        'refresh-exif-seconds': 'seconds',
        'refresh-exif-start': 'Start Refresh',
        'refresh-exif-reload': 'Reload Page',
        'refresh-exif-cancel-confirm': 'Are you sure you want to cancel the EXIF refresh process?',
        'refresh-exif-cancelled': 'EXIF refresh has been cancelled',
        'refresh-exif-no-files': 'No files to process',
        'refresh-exif-error': 'An error occurred: ',
        'refresh-exif-cancelling': 'Cancelling...',
        'reading-exif-info': 'Reading EXIF information...',
        'not-set': 'Not set',
        'photo-date-save': 'Save',
        'edit-photo-date': 'Edit Photo Date',
        'saved-successfully': '✓ Saved',
        'update-photo-date-error': 'Failed to save photo date',
        'save-failed': 'Failed to save',
        'saving-in-progress': 'Saving...',
        'edit-metadata': 'Edit Metadata',
        'location-placeholder': 'e.g., Shibuya, Tokyo',
        'latitude': 'Latitude',
        'longitude': 'Longitude',
        'latitude-placeholder': 'e.g., 35.658581',
        'longitude-placeholder': 'e.g., 139.745438',
        'save': 'Save',
        'update-metadata-error': 'Failed to update metadata',
        'refresh-exif-already-running': 'EXIF refresh is already running',
        'refresh-exif-start-failed': 'Failed to start',
        'refresh-exif-file-list-failed': 'Failed to get file list',
        'duplicate-check-file-load-failed': 'Failed to load file',
        'duplicate-check-failed': 'Duplicate check failed',
        'write-exif-to-file': 'Write EXIF to File',
        'write-exif-confirm': 'Write the current metadata as EXIF data to the image file?\n\nThis will physically modify the image file.',
        'write-exif-success': 'EXIF data has been written to the file',
        'write-exif-error': 'Failed to write EXIF data to file',
        'write-exif-only-jpeg': 'Only JPEG files are supported',
        'write-exif-writing': 'Writing EXIF data...'
    },
    ja: {
        'nav-gallery': 'ギャラリー',
        'nav-upload': 'アップロード',
        'nav-refresh-exif': 'EXIF洗替',
        'nav-admin-mode': 'モード',
        'admin-mode-admin': '管理者',
        'admin-mode-user': 'ユーザー',
        'admin-password-modal-title': '管理者モード認証',
        'admin-password-info': '管理者モードに切り替えるにはパスワードを入力してください。',
        'admin-password-label': '管理者パスワード',
        'admin-password-placeholder': 'パスワードを入力',
        'admin-password-submit': '管理者モードON',
        'admin-password-error': 'パスワードが正しくありません。',
        'page-title': 'メディアギャラリー',
        'page-subtitle': 'アップロードした写真と動画を閲覧できます',
        'media-count-format': '{count} 件',
        'upload-button': 'メディアをアップロード',
        'filter-label': 'フィルター',
        'filter-all': 'すべて',
        'filter-image': '写真のみ',
        'filter-video': '動画のみ',
        'search-label': '検索',
        'search-placeholder': 'タイトル、説明、ファイル名で検索...',
        'no-media-title': 'メディアファイルがありません',
        'no-media-text': 'まだ写真や動画がアップロードされていません。',
        'first-upload': '最初のメディアをアップロード',
        'upload-modal-title': 'メディアアップロード',
        'file-select': 'ファイル選択',
        'file-info': '対応形式: JPEG, PNG, GIF, HEIC, MP4, MOV, AVI (各ファイル最大500MB、複数選択可)',
        'title-label': 'タイトル',
        'title-placeholder': '例: 家族旅行 2024',
        'description-label': '説明',
        'description-placeholder': 'このメディアについての説明を入力...',
        'cancel': 'キャンセル',
        'upload': 'アップロード',
        'view-button': '表示',
        'image-badge': '画像',
        'video-badge': '動画',
        'modal-filename': 'ファイル名:',
        'modal-size': 'サイズ:',
        'modal-uploaded': 'アップロード:',
        'modal-format': '形式:',
        'exif-datetime': '撮影日時:',
        'exif-location': '位置情報:',
        'exif-camera': 'カメラ:',
        'exif-details': 'EXIF情報',
        'modal-title': 'タイトル',
        'modal-description': '説明',
        'footer-tagline': '大切な思い出を記録し、成長を見守るアルバム',
        'file-not-selected': 'クリックしてファイルを選択',
        'files-selected': '{count} 個のファイルが選択されています',
        'selected-files-header': '選択されたファイル: {count}件',
        'file-size-error': 'ファイルサイズが大きすぎます',
        'total-items': '全{count}件',
        'page-of-pages': '{current} / {total} ページ',
        'pagination-previous': '前へ',
        'pagination-next': '次へ',
        'pagination-current': '(現在のページ)',
        'pagination-info': '{start} - {end} 件目 / 全 {total} 件',
        'duplicate-warning-title': '重複ファイルが見つかりました',
        'duplicate-already-uploaded': 'は既にアップロード済みです',
        'duplicate-upload-date': '登録日',
        'duplicate-auto-excluded': '重複ファイルは自動的に除外されます。',
        'duplicate-reselect-hint': 'それでもアップロードする場合は、ファイルを再選択してください。',
        'duplicate-checking': '重複チェック中...',
        'duplicate-check-status': '{filename} をチェック中... ({current}/{total})',
        'duplicate-count-suffix': '{count}個',
        'rotation-label': '回転:',
        'rotate-left': '左に回転',
        'rotate-right': '右に回転',
        'save-rotation': '回転を保存',
        'rotation-changed': '(変更済み)',
        'rotation-saved': '保存しました！',
        'rotation-save-error': '保存に失敗',
        'converting': '変換中...',
        'uploading': 'アップロード',
        'file-size-error-alert': '500MBを超えるファイルが含まれています。ファイルを選択し直してください。',
        'please-select-file': 'ファイルを選択してください。',
        'file-size-over-100mb': '以下のファイルは500MBを超えています:',
        'preparing-upload': 'アップロード準備中...',
        'generating-thumbnail': 'サムネイル生成中...',
        'upload-error': 'アップロード中にエラーが発生しました: ',
        'delete-confirm': '「{filename}」を削除してもよろしいですか？\nこの操作は取り消せません。',
        'heic-conversion-failed': '{filename} の変換に失敗しました。元のファイルをアップロードします。',
        'uploading-count': '{current}/{total}件アップロード中...',
        'saving': '保存中...',
        'media-load-error': 'メディアの読み込みエラー: ',
        'upload-success': 'メディアファイルが正常にアップロードされました！',
        'upload-partial': '一部のファイルがアップロードされました。',
        'delete-success': 'メディアファイルが削除されました。',
        'media-data-load-failed': 'メディアデータの読み込みに失敗しました。',
        'page-title-gallery': 'ギャラリー',
        'sort-label': '並び替え',
        'sort-upload-date-desc': 'アップロード日（新しい順）',
        'sort-upload-date-asc': 'アップロード日（古い順）',
        'sort-exif-datetime-desc': '撮影日（新しい順）',
        'sort-exif-datetime-asc': '撮影日（古い順）',
        'sort-location': '場所（地名順）',
        'sort-filename-asc': 'ファイル名（昇順）',
        'sort-filename-desc': 'ファイル名（降順）',
        'refresh-exif-button': 'EXIF洗替',
        'refresh-exif-modal-title': 'EXIF情報洗替',
        'refresh-exif-description': '既存の全メディアファイルのEXIF情報（撮影日時、GPS位置情報、カメラ情報など）を再抽出してデータベースを更新します。',
        'refresh-exif-warning': '処理には時間がかかる場合があります。処理中はこのウィンドウを閉じないでください。',
        'refresh-exif-progress': '処理中...',
        'refresh-exif-total': '全体',
        'refresh-exif-processed': '処理済',
        'refresh-exif-updated': '更新',
        'refresh-exif-errors': 'エラー',
        'refresh-exif-complete': 'EXIF情報の洗替が完了しました。',
        'refresh-exif-summary': '処理結果',
        'refresh-exif-files': '件',
        'refresh-exif-elapsed': '経過時間',
        'refresh-exif-seconds': '秒',
        'refresh-exif-start': '洗替開始',
        'refresh-exif-reload': 'ページを再読み込み',
        'refresh-exif-cancel-confirm': 'EXIF洗替処理を中止しますか？',
        'refresh-exif-cancelled': 'EXIF洗替がキャンセルされました',
        'refresh-exif-no-files': '処理対象のファイルがありません',
        'refresh-exif-error': 'エラーが発生しました: ',
        'refresh-exif-cancelling': 'キャンセル中...',
        'reading-exif-info': 'EXIF情報を読み取り中...',
        'not-set': '未設定',
        'photo-date-save': '保存',
        'edit-photo-date': '撮影日を編集',
        'saved-successfully': '✓ 保存しました',
        'update-photo-date-error': '撮影日の保存に失敗しました',
        'save-failed': '保存に失敗しました',
        'saving-in-progress': '保存中...',
        'edit-metadata': 'メタデータを編集',
        'location-placeholder': '例: 東京都渋谷区',
        'latitude': '緯度',
        'longitude': '経度',
        'latitude-placeholder': '例: 35.658581',
        'longitude-placeholder': '例: 139.745438',
        'save': '保存',
        'update-metadata-error': 'メタデータの更新に失敗しました',
        'refresh-exif-already-running': 'EXIF洗替は既に実行中です',
        'refresh-exif-start-failed': '開始に失敗しました',
        'refresh-exif-file-list-failed': 'ファイルリスト取得に失敗しました',
        'duplicate-check-file-load-failed': 'ファイルの読み込みに失敗しました',
        'duplicate-check-failed': '重複チェックに失敗しました',
        'write-exif-to-file': 'EXIFをファイルに書き込む',
        'write-exif-confirm': '現在のメタデータをEXIF情報として画像ファイルに書き込みますか？\n\n※画像ファイル自体を物理的に変更します。',
        'write-exif-success': 'EXIFデータをファイルに書き込みました',
        'write-exif-error': 'EXIFデータの書き込みに失敗しました',
        'write-exif-only-jpeg': 'JPEGファイルのみサポートされています',
        'write-exif-writing': 'EXIF書き込み中...'
    }
};

// 現在の言語とテーマ
// currentLanguage is already declared in header.php inline script
let currentTheme = localStorage.getItem('kidsnaps-theme') || 'dark'; // デフォルトダークモード

/**
 * 言語を切り替え
 */
function toggleLanguage() {
    currentLanguage = currentLanguage === 'en' ? 'ja' : 'en';
    localStorage.setItem('kidsnaps-language', currentLanguage);
    applyLanguage();
}

/**
 * 翻訳を取得（ヘルパー関数）
 * @param {string} key 翻訳キー
 * @returns {string} 翻訳されたテキスト
 */
function t(key) {
    return translations[currentLanguage] && translations[currentLanguage][key]
        ? translations[currentLanguage][key]
        : key;
}

/**
 * 言語を適用
 */
function applyLanguage() {
    const lang = translations[currentLanguage];

    // data-i18n属性を持つすべての要素を更新
    document.querySelectorAll('[data-i18n]').forEach(element => {
        const key = element.getAttribute('data-i18n');

        // ページネーション: total-items (data-count)
        if (key === 'total-items' && element.dataset.count) {
            element.textContent = lang[key].replace('{count}', element.dataset.count);
        }
        // ページネーション: page-of-pages (data-current, data-total)
        else if (key === 'page-of-pages' && element.dataset.current && element.dataset.total) {
            element.textContent = lang[key]
                .replace('{current}', element.dataset.current)
                .replace('{total}', element.dataset.total);
        }
        // ページネーション: pagination-info (data-start, data-end, data-total)
        else if (key === 'pagination-info' && element.dataset.start && element.dataset.end && element.dataset.total) {
            element.textContent = lang[key]
                .replace('{start}', element.dataset.start)
                .replace('{end}', element.dataset.end)
                .replace('{total}', element.dataset.total);
        }
        // メディア件数の特殊処理（既存）
        else if (key === 'media-count') {
            const count = element.textContent.match(/\d+/);
            if (count && lang['media-count-format']) {
                element.textContent = lang['media-count-format'].replace('{count}', count[0]);
            }
        }
        // 通常のテキスト置換
        else if (lang[key]) {
            element.textContent = lang[key];
        }
    });

    // title属性も更新
    document.querySelectorAll('[data-i18n-title]').forEach(element => {
        const key = element.getAttribute('data-i18n-title');
        if (lang[key]) {
            element.setAttribute('title', lang[key]);
        }
    });

    // placeholder属性も更新
    document.querySelectorAll('[data-i18n-placeholder]').forEach(element => {
        const key = element.getAttribute('data-i18n-placeholder');
        if (lang[key]) {
            element.setAttribute('placeholder', lang[key]);
        }
    });

    // 言語切り替えボタンのテキストを更新
    const langToggleText = document.getElementById('langToggleText');
    if (langToggleText) {
        langToggleText.textContent = currentLanguage === 'en' ? 'JP' : 'EN';
    }

    // ページタイトルを更新（ブラウザタブのタイトル）
    const currentPageTitle = document.querySelector('[data-page-title]');
    if (currentPageTitle) {
        const pageTitleKey = currentPageTitle.getAttribute('data-page-title');
        if (lang[pageTitleKey]) {
            document.title = `${lang[pageTitleKey]} - KidSnaps Growth Album`;
        }
    }

    // ファイル入力ラベルの更新
    const fileInputLabel = document.getElementById('fileInputLabel');
    const mediaFileInput = document.getElementById('mediaFile');
    if (fileInputLabel && mediaFileInput) {
        const files = mediaFileInput.files;
        if (files.length === 0) {
            fileInputLabel.innerHTML = `<i class="bi bi-cloud-upload"></i><span>${lang['file-not-selected']}</span>`;
        } else if (files.length === 1) {
            // ファイル名はそのまま（既にHTMLで設定済み）
        } else {
            fileInputLabel.innerHTML = `<i class="bi bi-check-circle-fill"></i><span>${lang['files-selected'].replace('{count}', files.length)}</span>`;
        }
    }

    console.log('Language changed to:', currentLanguage);
}

/**
 * テーマを切り替え
 */
function toggleTheme() {
    currentTheme = currentTheme === 'light' ? 'dark' : 'light';
    localStorage.setItem('kidsnaps-theme', currentTheme);
    applyTheme();
}

/**
 * テーマを適用
 */
function applyTheme() {
    document.documentElement.setAttribute('data-theme', currentTheme);

    // テーマアイコンを更新
    const themeIcon = document.getElementById('themeIcon');
    if (themeIcon) {
        if (currentTheme === 'dark') {
            themeIcon.className = 'bi bi-sun-fill';
        } else {
            themeIcon.className = 'bi bi-moon-fill';
        }
    }

    console.log('Theme changed to:', currentTheme);
}

/**
 * URLパラメータからsuccessおよびerrorパラメータを削除してメッセージの再表示を防ぐ
 */
function cleanupSuccessParameter() {
    const urlParams = new URLSearchParams(window.location.search);
    let needsUpdate = false;

    // successパラメータが存在する場合、URLから削除
    if (urlParams.has('success')) {
        urlParams.delete('success');
        needsUpdate = true;
    }

    // errorパラメータが存在する場合、URLから削除
    if (urlParams.has('error')) {
        urlParams.delete('error');
        needsUpdate = true;
    }

    // パラメータを削除した場合のみURLを更新
    if (needsUpdate) {
        // 新しいURLを構築
        const newUrl = urlParams.toString()
            ? window.location.pathname + '?' + urlParams.toString()
            : window.location.pathname;

        // ブラウザの履歴を更新（ページをリロードせずに）
        window.history.replaceState({}, '', newUrl);
    }
}

// DOM読み込み完了後に実行
document.addEventListener('DOMContentLoaded', function() {
    console.log('KidSnaps Growth Album initialized');

    // 言語とテーマを適用
    applyLanguage();
    applyTheme();

    // URLパラメータからsuccessを削除（メッセージが表示された後）
    cleanupSuccessParameter();

    // アップロードフォームの処理
    initUploadForm();

    // メディアビューアーの初期化
    initMediaViewer();

    // ツールチップの初期化（Bootstrap）
    initTooltips();
});

/**
 * アップロードフォームの初期化
 */
function initUploadForm() {
    const uploadForm = document.getElementById('uploadForm');
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadProgress = document.getElementById('uploadProgress');
    const mediaFileInput = document.getElementById('mediaFile');
    const fileListContainer = document.getElementById('fileList');

    if (!uploadForm) return;

    // ファイル選択時のプレビュー（複数ファイル対応）
    if (mediaFileInput) {
        mediaFileInput.addEventListener('change', async function(e) {
            let files = Array.from(e.target.files);
            const fileInputLabel = document.getElementById('fileInputLabel');
            const uploadBtn = document.getElementById('uploadBtn');
            const lang = translations[currentLanguage];

            if (files.length === 0) {
                if (fileListContainer) {
                    fileListContainer.innerHTML = '';
                }
                // ラベルをリセット
                if (fileInputLabel) {
                    fileInputLabel.innerHTML = `<i class="bi bi-cloud-upload"></i><span data-i18n="file-not-selected">${lang['file-not-selected']}</span>`;
                    fileInputLabel.classList.remove('file-selected');
                }
                // タイトルをクリア
                const titleInput = document.getElementById('title');
                if (titleInput && titleInput.dataset.autoGenerated) {
                    titleInput.value = '';
                    delete titleInput.dataset.autoGenerated;
                }
                return;
            }

            // アップロードボタンを無効化（HEIC変換中）
            if (uploadBtn) {
                uploadBtn.disabled = true;
                uploadBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>${lang['converting']}`;
            }

            // HEIC/HEIFファイルをJPEGに変換（クライアント側）
            files = await convertHeicFilesToJpeg(files);

            // アップロードボタンを再有効化
            if (uploadBtn) {
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = `<i class="bi bi-cloud-upload"></i> <span data-i18n="upload">${lang['upload']}</span>`;
                applyLanguage(); // 言語設定を再適用
            }

            // ファイルラベルを更新
            if (fileInputLabel) {
                if (files.length === 1) {
                    fileInputLabel.innerHTML = `<i class="bi bi-check-circle-fill"></i><span>${escapeHtml(files[0].name)}</span>`;
                } else {
                    fileInputLabel.innerHTML = `<i class="bi bi-check-circle-fill"></i><span>${lang['files-selected'].replace('{count}', files.length)}</span>`;
                }
                fileInputLabel.classList.add('file-selected');
            }

            // ファイルリストを表示
            if (fileListContainer) {
                let listHTML = '<div class="file-list-display py-2 mb-0">';
                listHTML += `<strong><i class="bi bi-files"></i> ${lang['selected-files-header'].replace('{count}', files.length)}</strong><ul class="mb-0 mt-2 small">`;

                let hasError = false;
                const maxSize = 500 * 1024 * 1024; // 500MB

                for (const file of files) {
                    const sizeStr = formatFileSize(file.size);
                    const isOverSize = file.size > maxSize;

                    if (isOverSize) {
                        hasError = true;
                        listHTML += `<li class="text-danger"><strong>${escapeHtml(file.name)}</strong> (${sizeStr}) - ${lang['file-size-error']}</li>`;
                    } else {
                        listHTML += `<li>${escapeHtml(file.name)} (${sizeStr})</li>`;
                    }
                }

                listHTML += '</ul></div>';
                fileListContainer.innerHTML = listHTML;

                if (hasError) {
                    alert(lang['file-size-error-alert']);
                    mediaFileInput.value = '';
                    fileListContainer.innerHTML = '';
                    return;
                }
            }

            // EXIF情報を読み取ってタイトルを仮生成（最初のファイルのみ）
            if (files.length > 0 && files[0].type.startsWith('image/')) {
                try {
                    await generateTitleFromExif(files[0]);
                } catch (error) {
                    console.error('EXIF情報の読み取りエラー:', error);
                }
            }

            // 動画ファイルのサムネイル生成（複数対応）
            console.log(`${files.length}個のファイルが選択されました。`);
            const videoFiles = files.filter(f => f.type.startsWith('video/'));

            if (videoFiles.length > 0) {
                console.log(`${videoFiles.length}個の動画ファイルのサムネイルを生成中...`);
                try {
                    await generateMultipleVideoThumbnails(videoFiles);
                } catch (error) {
                    console.error('サムネイル生成エラー:', error);
                }
            }
        });
    }

    // フォーム送信時の処理（チャンク分割アップロード対応）
    uploadForm.addEventListener('submit', async function(e) {
        e.preventDefault(); // 常にデフォルト動作を防止

        const fileInput = document.getElementById('mediaFile');
        const titleInput = document.getElementById('title');
        const descriptionInput = document.getElementById('description');

        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            const lang = translations[currentLanguage];
            alert(lang['please-select-file']);
            return;
        }

        // 全ファイルのサイズチェック（500MBに上限を引き上げ）
        const lang = translations[currentLanguage];
        const files = Array.from(fileInput.files);
        const maxSize = 500 * 1024 * 1024; // 500MB
        const oversizedFiles = files.filter(f => f.size > maxSize);

        if (oversizedFiles.length > 0) {
            alert(`${lang['file-size-over-100mb']}\n${oversizedFiles.map(f => f.name).join('\n')}`);
            return;
        }

        // アップロードボタンを無効化
        if (uploadBtn) {
            uploadBtn.disabled = true;
            uploadBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>${lang['preparing-upload']}`;
        }

        // プログレスバーを表示
        if (uploadProgress) {
            uploadProgress.classList.remove('d-none');
            const progressBar = uploadProgress.querySelector('.progress-bar');
            if (progressBar) {
                progressBar.style.width = '0%';
                progressBar.textContent = '0%';
            }
        }

        try {
            // 動画ファイルのサムネイルを生成
            const thumbnailMap = new Map();
            const videoFiles = files.filter(f => f.type.startsWith('video/'));

            if (videoFiles.length > 0) {
                if (uploadBtn) {
                    uploadBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>${lang['generating-thumbnail']}`;
                }

                console.log(`[アップロード] ${videoFiles.length}個の動画のサムネイルを生成します`);

                for (const videoFile of videoFiles) {
                    try {
                        const blob = await generateSingleVideoThumbnail(videoFile);
                        // BlobをBase64に変換
                        const reader = new FileReader();
                        const base64Data = await new Promise((resolve, reject) => {
                            reader.onloadend = () => resolve(reader.result);
                            reader.onerror = reject;
                            reader.readAsDataURL(blob);
                        });
                        thumbnailMap.set(videoFile.name, base64Data);
                        console.log(`[アップロード] サムネイル生成成功: ${videoFile.name}`);
                    } catch (error) {
                        console.warn(`[アップロード] サムネイル生成失敗 (${videoFile.name}):`, error);
                        console.warn(`[アップロード] サムネイルなしで続行します: ${videoFile.name}`);
                        // サムネイル生成に失敗してもアップロードは続行
                        // thumbnailMapには追加しない（サーバー側でサムネイルなしとして処理）
                    }
                }

                console.log(`[アップロード] サムネイル生成完了: ${thumbnailMap.size}/${videoFiles.length}個成功`);
            }

            // 画像ファイルのEXIF情報を抽出
            const exifMap = new Map();
            const imageFiles = files.filter(f => f.type.startsWith('image/'));

            if (imageFiles.length > 0) {
                if (uploadBtn) {
                    uploadBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>${lang['reading-exif-info']}`;
                }

                console.log(`[アップロード] ${imageFiles.length}個の画像のEXIF情報を抽出します`);

                for (const imageFile of imageFiles) {
                    try {
                        const exifData = await readExifFromFile(imageFile);
                        if (exifData) {
                            // GPS座標を10進数に変換
                            let latitude = null;
                            let longitude = null;
                            if (exifData.GPSLatitude && exifData.GPSLongitude) {
                                latitude = convertDMSToDD(exifData.GPSLatitude, exifData.GPSLatitudeRef);
                                longitude = convertDMSToDD(exifData.GPSLongitude, exifData.GPSLongitudeRef);
                            }

                            // EXIF情報をマップに保存
                            exifMap.set(imageFile.name, {
                                datetime: exifData.DateTimeOriginal || exifData.DateTime || exifData.DateTimeDigitized || null,
                                latitude: latitude,
                                longitude: longitude,
                                camera_make: exifData.Make || null,
                                camera_model: exifData.Model || null,
                                orientation: exifData.Orientation || 1
                            });

                            console.log(`[アップロード] EXIF抽出成功: ${imageFile.name}`);
                        }
                    } catch (error) {
                        console.warn(`[アップロード] EXIF抽出失敗 (${imageFile.name}):`, error);
                        // EXIF抽出に失敗してもアップロードは続行
                    }
                }

                console.log(`[アップロード] EXIF抽出完了: ${exifMap.size}/${imageFiles.length}個成功`);
            }

            // 新しいアップロード処理を実行
            const title = titleInput ? titleInput.value : '';
            const description = descriptionInput ? descriptionInput.value : '';

            const results = await uploadMultipleFiles(files, title, description, thumbnailMap, exifMap);

            // 結果に応じてリダイレクト
            if (results.errors.length === 0) {
                // 全て成功
                window.location.href = 'index.php?success=upload';
            } else if (results.success.length > 0) {
                // 一部成功
                sessionStorage.setItem('uploadErrors', JSON.stringify(results.errors));
                window.location.href = 'index.php?success=partial';
            } else {
                // 全て失敗
                sessionStorage.setItem('uploadErrors', JSON.stringify(results.errors));
                window.location.href = 'index.php?error=upload';
            }

        } catch (error) {
            console.error('アップロードエラー:', error);
            alert(lang['upload-error'] + error.message);

            // ボタンとプログレスバーを元に戻す
            if (uploadBtn) {
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = `<i class="bi bi-cloud-upload me-2"></i>${lang['uploading']}`;
            }
            if (uploadProgress) {
                uploadProgress.classList.add('d-none');
            }
        }
    });
}

/**
 * EXIF情報を読み取ってタイトルを仮生成
 * @param {File} imageFile - 画像ファイル
 * @returns {Promise}
 */
async function generateTitleFromExif(imageFile) {
    const titleInput = document.getElementById('title');
    if (!titleInput) return;

    // ユーザーが既にタイトルを入力している場合は上書きしない
    if (titleInput.value && !titleInput.dataset.autoGenerated) {
        return;
    }

    try {
        // EXIF.jsライブラリを使用してEXIF情報を読み取り
        const exifData = await readExifFromFile(imageFile);

        if (!exifData) {
            return;
        }

        // 撮影日時とGPS情報からタイトルを生成
        let suggestedTitle = '';

        // 撮影日時があれば日付を使用
        if (exifData.DateTime || exifData.DateTimeOriginal) {
            const dateTimeStr = exifData.DateTimeOriginal || exifData.DateTime;
            const date = parseDateTimeFromExif(dateTimeStr);

            if (date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                suggestedTitle = `${year}/${month}/${day}`;
            }
        }

        // GPS情報があれば追加
        if (exifData.GPSLatitude && exifData.GPSLongitude) {
            const lat = convertDMSToDD(exifData.GPSLatitude, exifData.GPSLatitudeRef);
            const lon = convertDMSToDD(exifData.GPSLongitude, exifData.GPSLongitudeRef);

            if (lat && lon) {
                if (suggestedTitle) {
                    suggestedTitle += ' - ';
                }
                suggestedTitle += `📍 ${lat.toFixed(4)}, ${lon.toFixed(4)}`;
            }
        }

        // タイトルが生成できた場合は入力欄に設定
        if (suggestedTitle) {
            titleInput.value = suggestedTitle;
            titleInput.dataset.autoGenerated = 'true';
            titleInput.classList.add('auto-generated-title');

            // ユーザーが編集したら自動生成フラグを削除
            titleInput.addEventListener('input', function onInput() {
                delete titleInput.dataset.autoGenerated;
                titleInput.classList.remove('auto-generated-title');
                titleInput.removeEventListener('input', onInput);
            }, { once: true });
        }

    } catch (error) {
        console.error('EXIF読み取りエラー:', error);
    }

    // アップロードモーダルのフォーカス管理（aria-hidden警告を防ぐ）
    const uploadModal = document.getElementById('uploadModal');
    if (uploadModal) {
        uploadModal.addEventListener('hide.bs.modal', function() {
            // フォーカスされている要素からフォーカスを外す
            if (document.activeElement && uploadModal.contains(document.activeElement)) {
                document.activeElement.blur();
            }
        });
    }
}

/**
 * ファイルからEXIF情報を読み取り（EXIF.js使用）
 * @param {File} file - 画像ファイル
 * @returns {Promise<Object|null>} EXIF情報
 */
function readExifFromFile(file) {
    return new Promise((resolve, reject) => {
        // EXIF.jsライブラリが利用可能かチェック
        if (typeof EXIF === 'undefined') {
            console.warn('EXIF.js library not loaded');
            resolve(null);
            return;
        }

        const reader = new FileReader();

        reader.onload = function(e) {
            try {
                const img = new Image();

                img.onload = function() {
                    try {
                        // EXIF.jsを使用してEXIF情報を取得
                        EXIF.getData(img, function() {
                            const allTags = EXIF.getAllTags(this);

                            if (!allTags || Object.keys(allTags).length === 0) {
                                resolve(null);
                                return;
                            }

                            // 必要な情報を抽出
                            const exifData = {
                                DateTime: allTags.DateTime,
                                DateTimeOriginal: allTags.DateTimeOriginal,
                                DateTimeDigitized: allTags.DateTimeDigitized,
                                GPSLatitude: allTags.GPSLatitude,
                                GPSLatitudeRef: allTags.GPSLatitudeRef,
                                GPSLongitude: allTags.GPSLongitude,
                                GPSLongitudeRef: allTags.GPSLongitudeRef,
                                Make: allTags.Make,
                                Model: allTags.Model,
                                Orientation: allTags.Orientation
                            };

                            console.log('EXIF data extracted:', exifData);
                            resolve(exifData);
                        });
                    } catch (error) {
                        console.error('EXIF parsing error:', error);
                        resolve(null);
                    }
                };

                img.onerror = function() {
                    console.error('Failed to load image');
                    resolve(null);
                };

                img.src = e.target.result;

            } catch (error) {
                reject(error);
            }
        };

        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}

/**
 * EXIF日時文字列を解析
 * @param {string} dateTimeStr - EXIF日時 (例: "2024:01:15 14:30:45")
 * @returns {Date|null}
 */
function parseDateTimeFromExif(dateTimeStr) {
    if (!dateTimeStr) return null;

    // "YYYY:MM:DD HH:MM:SS" 形式を解析
    const parts = dateTimeStr.split(' ');
    if (parts.length !== 2) return null;

    const dateParts = parts[0].split(':');
    const timeParts = parts[1].split(':');

    if (dateParts.length !== 3 || timeParts.length !== 3) return null;

    return new Date(
        parseInt(dateParts[0]),
        parseInt(dateParts[1]) - 1,
        parseInt(dateParts[2]),
        parseInt(timeParts[0]),
        parseInt(timeParts[1]),
        parseInt(timeParts[2])
    );
}

/**
 * DMS（度分秒）形式をDD（10進数）形式に変換
 * @param {Array} dms - [度, 分, 秒]
 * @param {string} ref - 方位 ("N", "S", "E", "W")
 * @returns {number|null}
 */
function convertDMSToDD(dms, ref) {
    if (!dms || dms.length < 3) return null;

    const degrees = dms[0];
    const minutes = dms[1];
    const seconds = dms[2];

    let dd = degrees + minutes / 60 + seconds / 3600;

    if (ref === 'S' || ref === 'W') {
        dd = -dd;
    }

    return dd;
}

/**
 * 動画からサムネイルを生成
 * @param {File} videoFile - 動画ファイル
 * @returns {Promise} サムネイル生成完了Promise
 */
function generateVideoThumbnail(videoFile) {
    return new Promise((resolve, reject) => {
        const video = document.createElement('video');
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        video.preload = 'metadata';
        video.muted = true;
        video.playsInline = true;

        video.onloadeddata = function() {
            // 1秒後のフレームを取得
            video.currentTime = Math.min(1, video.duration / 2);
        };

        video.onseeked = function() {
            // サムネイルサイズを設定（最大幅800px）
            const maxWidth = 800;
            const scale = Math.min(maxWidth / video.videoWidth, 1);
            canvas.width = video.videoWidth * scale;
            canvas.height = video.videoHeight * scale;

            // キャンバスに動画フレームを描画
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            // Blobに変換
            canvas.toBlob(function(blob) {
                if (blob) {
                    // 生成されたサムネイルをフォームに保存
                    const thumbnailFile = new File([blob], 'thumbnail.jpg', { type: 'image/jpeg' });

                    // フォームにhidden inputとして保存
                    const thumbnailInput = document.getElementById('videoThumbnail');
                    if (thumbnailInput) {
                        // DataTransferを使ってFileオブジェクトを保存
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(thumbnailFile);
                        thumbnailInput.files = dataTransfer.files;
                    }

                    console.log('サムネイル生成完了:', formatFileSize(blob.size));
                    resolve(blob);
                } else {
                    reject(new Error('サムネイル生成に失敗しました'));
                }

                // クリーンアップ
                URL.revokeObjectURL(video.src);
            }, 'image/jpeg', 0.85);
        };

        video.onerror = function() {
            reject(new Error('動画の読み込みに失敗しました'));
            URL.revokeObjectURL(video.src);
        };

        // 動画をロード
        video.src = URL.createObjectURL(videoFile);
    });
}

/**
 * 複数の動画からサムネイルを生成
 * @param {File[]} videoFiles - 動画ファイルの配列
 * @returns {Promise} すべてのサムネイル生成完了Promise
 */
async function generateMultipleVideoThumbnails(videoFiles) {
    const thumbnailBlobs = [];

    for (const videoFile of videoFiles) {
        try {
            const blob = await generateSingleVideoThumbnail(videoFile);
            thumbnailBlobs.push(blob);
        } catch (error) {
            console.error(`サムネイル生成エラー (${videoFile.name}):`, error);
            // エラーの場合はnullを追加（サムネイルなしで続行）
            thumbnailBlobs.push(null);
        }
    }

    // すべてのサムネイルをhidden inputに設定
    const thumbnailInput = document.getElementById('videoThumbnail');
    if (thumbnailInput) {
        const dataTransfer = new DataTransfer();
        thumbnailBlobs.forEach((blob, index) => {
            if (blob) {
                const thumbnailFile = new File([blob], `thumbnail_${index}.jpg`, { type: 'image/jpeg' });
                dataTransfer.items.add(thumbnailFile);
            }
        });
        thumbnailInput.files = dataTransfer.files;
    }

    console.log(`${thumbnailBlobs.filter(b => b !== null).length}/${videoFiles.length} 個のサムネイル生成完了`);
}

/**
 * 単一の動画からサムネイルを生成（複数処理用）
 * @param {File} videoFile - 動画ファイル
 * @returns {Promise<Blob>} サムネイルBlob
 */
function generateSingleVideoThumbnail(videoFile) {
    return new Promise((resolve, reject) => {
        console.log(`[サムネイル生成] 開始: ${videoFile.name}`);

        const video = document.createElement('video');
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        // タイムアウト処理（30秒）
        const timeout = setTimeout(() => {
            console.error(`[サムネイル生成] タイムアウト: ${videoFile.name}`);
            URL.revokeObjectURL(video.src);
            reject(new Error('サムネイル生成がタイムアウトしました（30秒）'));
        }, 30000);

        const cleanup = () => {
            clearTimeout(timeout);
            URL.revokeObjectURL(video.src);
        };

        video.preload = 'metadata';
        video.muted = true;
        video.playsInline = true;
        video.crossOrigin = 'anonymous'; // CORS対策

        video.onloadedmetadata = function() {
            console.log(`[サムネイル生成] メタデータ読み込み完了: ${videoFile.name}, duration: ${video.duration}s`);
            try {
                // 動画の長さが不明な場合はスキップ
                if (!video.duration || video.duration === Infinity || isNaN(video.duration)) {
                    console.warn(`[サムネイル生成] duration が不正: ${video.duration}`);
                    cleanup();
                    reject(new Error('動画の長さを取得できませんでした'));
                    return;
                }
                video.currentTime = Math.min(1, video.duration / 2);
            } catch (error) {
                console.error(`[サムネイル生成] currentTime設定エラー:`, error);
                cleanup();
                reject(error);
            }
        };

        video.onseeked = function() {
            console.log(`[サムネイル生成] シーク完了: ${videoFile.name}`);
            try {
                const maxWidth = 800;
                const scale = Math.min(maxWidth / video.videoWidth, 1);
                canvas.width = video.videoWidth * scale;
                canvas.height = video.videoHeight * scale;

                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                console.log(`[サムネイル生成] キャンバス描画完了: ${canvas.width}x${canvas.height}`);

                canvas.toBlob(function(blob) {
                    cleanup();
                    if (blob) {
                        console.log(`[サムネイル生成] 成功: ${videoFile.name}, size: ${blob.size} bytes`);
                        resolve(blob);
                    } else {
                        console.error(`[サムネイル生成] Blob生成失敗`);
                        reject(new Error('サムネイル生成に失敗しました'));
                    }
                }, 'image/jpeg', 0.85);
            } catch (error) {
                console.error(`[サムネイル生成] 描画エラー:`, error);
                cleanup();
                reject(error);
            }
        };

        video.onerror = function(e) {
            console.error(`[サムネイル生成] 動画読み込みエラー: ${videoFile.name}`, e);
            cleanup();
            reject(new Error('動画の読み込みに失敗しました'));
        };

        try {
            video.src = URL.createObjectURL(videoFile);
            console.log(`[サムネイル生成] 動画読み込み開始: ${videoFile.name}`);
        } catch (error) {
            console.error(`[サムネイル生成] createObjectURL エラー:`, error);
            cleanup();
            reject(error);
        }
    });
}

/**
 * メディアビューアーの初期化
 */
function initMediaViewer() {
    // メディアビューアーモーダルが閉じられた時の処理
    const viewModal = document.getElementById('viewModal');
    if (viewModal) {
        // モーダルが閉じる前にフォーカスを解除（aria-hidden警告を防ぐ）
        viewModal.addEventListener('hide.bs.modal', function() {
            // フォーカスされている要素からフォーカスを外す
            if (document.activeElement && viewModal.contains(document.activeElement)) {
                document.activeElement.blur();
            }
        });

        // モーダルが完全に閉じた後の処理
        viewModal.addEventListener('hidden.bs.modal', function() {
            // 動画を停止
            const videos = viewModal.querySelectorAll('video');
            videos.forEach(video => {
                video.pause();
                video.currentTime = 0;
            });

            // HEIC変換で作成されたBlob URLをクリーンアップ（メモリリーク防止）
            if (window.heicBlobUrls && window.heicBlobUrls.length > 0) {
                window.heicBlobUrls.forEach(url => {
                    URL.revokeObjectURL(url);
                });
                window.heicBlobUrls = [];
                console.log('HEIC Blob URLsをクリーンアップしました');
            }
        });
    }
}

// グローバル変数: 現在表示中のメディア情報
let currentMedia = null;
let currentRotation = 0;

/**
 * HTML要素からメディアデータを取得して表示
 * @param {HTMLElement} element - data-media属性を持つ要素
 */
function viewMediaFromElement(element) {
    try {
        const mediaJson = element.getAttribute('data-media');
        if (!mediaJson) {
            console.error('data-media attribute not found');
            return;
        }
        console.log('Attempting to parse media JSON:', mediaJson.substring(0, 100) + '...');
        const media = JSON.parse(mediaJson);
        viewMedia(media);
    } catch (error) {
        console.error('Failed to parse media data:', error);
        console.error('Raw JSON string:', element.getAttribute('data-media'));
        const lang = translations[currentLanguage];
        alert(lang['media-data-load-failed']);
    }
}

/**
 * メディアを表示（モーダル）
 * @param {Object} media - メディア情報オブジェクト
 */
async function viewMedia(media) {
    const modal = new bootstrap.Modal(document.getElementById('viewModal'));
    const modalTitle = document.getElementById('viewModalLabel');
    const modalBody = document.getElementById('viewModalBody');
    const modalInfo = document.getElementById('viewModalInfo');
    const rotationControls = document.getElementById('rotationControls');

    // 現在のメディア情報を保存
    currentMedia = media;
    currentRotation = media.rotation || 0;

    // タイトル設定（優先順位: 地名 → 撮影日 → ファイル名）
    if (media.exif_location_name) {
        // 地名がある場合は地名を表示
        modalTitle.textContent = media.exif_location_name;
    } else if (media.exif_datetime) {
        // 地名がなく撮影日がある場合は撮影日を表示
        modalTitle.textContent = formatDate(media.exif_datetime);
    } else {
        // 地名も撮影日もない場合はファイル名を表示（長い場合は切り詰める）
        modalTitle.textContent = truncateFilename(media.filename);
    }

    // メディアコンテンツの表示
    let mediaHTML = '';
    if (media.file_type === 'image') {
        // HEICファイルの場合、クライアント側で変換
        const isHeic = media.file_path.toLowerCase().match(/\.(heic|heif)$/);
        let imageSrc = escapeHtml(media.file_path);

        if (isHeic) {
            // ローディング表示
            modalBody.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">変換中...</span>
                    </div>
                    <p class="mt-3">HEIC画像を変換中...</p>
                </div>
            `;

            try {
                // HEICファイルを取得
                console.log('HEIC画像を変換開始:', media.file_path);
                const response = await fetch(media.file_path);
                if (!response.ok) {
                    throw new Error('画像の読み込みに失敗しました');
                }
                const blob = await response.blob();

                // heic2anyで変換
                const convertedBlob = await heic2any({
                    blob: blob,
                    toType: 'image/jpeg',
                    quality: 0.9
                });

                // Blob URLを作成
                const blobUrl = URL.createObjectURL(convertedBlob);
                imageSrc = blobUrl;

                // クリーンアップ用にBlob URLを保存
                if (!window.heicBlobUrls) {
                    window.heicBlobUrls = [];
                }
                window.heicBlobUrls.push(blobUrl);

                console.log('HEIC画像変換完了:', blobUrl);
            } catch (error) {
                console.error('HEIC変換エラー:', error);
                // 変換失敗時は元のパスを使用（Safariなどでは表示可能）
                imageSrc = escapeHtml(media.file_path);
            }
        }

        mediaHTML = `
            <img src="${imageSrc}"
                 alt="${escapeHtml(media.title || media.filename)}"
                 class="img-fluid rounded rotatable-media rotate-${currentRotation}"
                 id="currentMediaElement">
        `;
    } else if (media.file_type === 'video') {
        // サムネイルがある場合はposter属性に設定
        const posterAttr = media.thumbnail_path ? `poster="${escapeHtml(media.thumbnail_path)}"` : '';

        // .movファイル（QuickTime）の場合は、type属性を省略してブラウザに自動判定させる
        // これにより、より多くのブラウザで再生可能になります
        const sourceTag = (media.mime_type === 'video/quicktime' || media.file_path.toLowerCase().endsWith('.mov'))
            ? `<source src="${escapeHtml(media.file_path)}">`
            : `<source src="${escapeHtml(media.file_path)}" type="${escapeHtml(media.mime_type)}">`;

        mediaHTML = `
            <video class="w-100 rounded rotatable-media rotate-${currentRotation}"
                   preload="metadata" playsinline ${posterAttr} id="currentMediaElement">
                ${sourceTag}
                <p>お使いのブラウザはこの動画形式をサポートしていません。</p>
                <p><a href="${escapeHtml(media.file_path)}" download>動画をダウンロード</a></p>
            </video>
        `;
    }
    modalBody.innerHTML = mediaHTML;

    // 回転コントロールの初期化
    const saveBtn = document.getElementById('saveRotationBtn');
    const statusEl = document.getElementById('rotationStatus');
    if (saveBtn) {
        saveBtn.style.display = 'none';
    }
    if (statusEl) {
        statusEl.textContent = '';
        statusEl.className = 'text-muted small ms-2';
    }

    // 動画の場合、進行状況トラッキングを設定
    if (media.file_type === 'video') {
        const videoElement = document.getElementById('currentMediaElement');
        if (videoElement) {
            setupVideoProgressTracking(videoElement);
        }
    } else {
        // 画像の場合は進行状況インジケーターを非表示
        const progressIndicator = document.getElementById('videoProgressIndicator');
        if (progressIndicator) {
            progressIndicator.style.display = 'none';
        }
    }

    // メディア情報の表示
    const lang = translations[currentLanguage];
    let infoHTML = '<div class="row g-3">';

    // タイトル表示
    const titleDisplay = media.title || `<span class="text-muted">${lang['not-set'] || '未設定'}</span>`;
    infoHTML += `
        <div class="col-12">
            <h6 class="mb-2"><i class="bi bi-tag-fill"></i> ${lang['modal-title']}</h6>
            <div id="metadataTitleDisplay">
                <p class="mb-0">${escapeHtml(media.title || '')}</p>
            </div>
        </div>
    `;

    // 説明文またはEXIF情報から自動生成した説明
    let descriptionText = '';
    if (media.description) {
        descriptionText = media.description;
    } else {
        // EXIF情報から説明文を自動生成（地名のみ表示）
        if (media.exif_location_name) {
            descriptionText = `📍 ${media.exif_location_name}`;
        }
    }

    if (descriptionText) {
        infoHTML += `
            <div class="col-12">
                <h6 class="mb-2"><i class="bi bi-card-text"></i> ${lang['modal-description']}</h6>
                <p class="mb-0">${escapeHtml(descriptionText)}</p>
            </div>
        `;
    }

    // EXIF詳細情報セクション
    const hasExifData = media.exif_datetime || (media.exif_latitude && media.exif_longitude) || media.exif_camera_make || media.exif_camera_model;

    // 管理者モードの場合、またはEXIFデータがある場合にセクションを表示
    if (hasExifData || isAdmin) {
        infoHTML += `<div class="col-12"><hr class="my-2"></div>`;
        infoHTML += `
            <div class="col-12">
                <div class="d-flex align-items-center">
                    <h6 class="mb-2"><i class="bi bi-info-circle"></i> ${lang['exif-details']}</h6>
                    ${isAdmin ? `<button type="button" class="btn btn-sm btn-outline-primary ms-3 mb-2" onclick="editMetadata()">
                        <i class="bi bi-pencil-square"></i> ${lang['edit-metadata'] || 'メタデータを編集'}
                    </button>` : ''}
                </div>
            </div>
        `;

        // EXIF撮影日時の表示
        if (media.exif_datetime || isAdmin) {
            const displayDateHTML = media.exif_datetime ? formatDate(media.exif_datetime) : `<span class="text-muted">${lang['not-set']}</span>`;

            infoHTML += `
                <div class="col-md-6">
                    <small><strong><i class="bi bi-camera-fill"></i> ${lang['exif-datetime']}:</strong></small>
                    <br>
                    <div id="metadataDateDisplay">
                        <small>${displayDateHTML}</small>
                    </div>
                </div>
            `;
        }

        // EXIF位置情報の表示
        if ((media.exif_latitude && media.exif_longitude) || media.exif_location_name || isAdmin) {
            let locationDisplay = '';

            if (media.exif_latitude && media.exif_longitude) {
                // 緯度・経度を数値に変換（文字列として保存されている場合があるため）
                const lat = parseFloat(media.exif_latitude);
                const lng = parseFloat(media.exif_longitude);
                const mapLink = `https://www.google.com/maps?q=${lat},${lng}`;

                // 位置情報名がある場合は表示
                if (media.exif_location_name) {
                    locationDisplay = `<span x-apple-data-detectors="false" data-phone-skip="true">${escapeHtml(media.exif_location_name)}</span><br>`;
                }

                locationDisplay += `
                    <a href="${mapLink}" target="_blank" rel="noopener noreferrer" class="text-decoration-none" x-apple-data-detectors="false" data-phone-skip="true">
                        <span x-apple-data-detectors="false" data-phone-skip="true">📍 ${lat.toFixed(6)}, ${lng.toFixed(6)}</span>
                        <i class="bi bi-box-arrow-up-right small"></i>
                    </a>
                `;
            } else if (media.exif_location_name) {
                locationDisplay = `<span x-apple-data-detectors="false" data-phone-skip="true">${escapeHtml(media.exif_location_name)}</span>`;
            } else {
                locationDisplay = `<span class="text-muted">${lang['not-set']}</span>`;
            }

            infoHTML += `
                <div class="col-md-6">
                    <small><strong><i class="bi bi-geo-alt-fill"></i> ${lang['exif-location']}:</strong></small><br>
                    <div id="metadataLocationDisplay" x-apple-data-detectors="false" data-phone-skip="true">
                        <small>${locationDisplay}</small>
                    </div>
                </div>
            `;
        }

        // カメラ情報の表示
        if (media.exif_camera_make || media.exif_camera_model) {
            let cameraInfo = '';
            if (media.exif_camera_make) cameraInfo += escapeHtml(media.exif_camera_make);
            if (media.exif_camera_model) {
                if (cameraInfo) cameraInfo += ' ';
                cameraInfo += escapeHtml(media.exif_camera_model);
            }
            infoHTML += `
                <div class="col-md-6">
                    <small><strong><i class="bi bi-camera2"></i> ${lang['exif-camera'] || 'カメラ'}:</strong></small><br>
                    <small>${cameraInfo}</small>
                </div>
            `;
        }
    }

    infoHTML += `
        <div class="col-md-6">
            <small class="text-muted">
                <i class="bi bi-file-earmark"></i> ${lang['modal-filename']} <span title="${escapeHtml(media.filename)}">${escapeHtml(truncateFilename(media.filename))}</span>
            </small>
        </div>
        <div class="col-md-6">
            <small class="text-muted">
                <i class="bi bi-hdd"></i> ${lang['modal-size']} ${formatFileSize(media.file_size)}
            </small>
        </div>
        <div class="col-md-6">
            <small class="text-muted">
                <i class="bi bi-calendar3"></i> ${lang['modal-uploaded']} ${formatDate(media.upload_date)}
            </small>
        </div>
        <div class="col-md-6">
            <small class="text-muted">
                <i class="bi bi-file-code"></i> ${lang['modal-format']} ${escapeHtml(media.mime_type)}
            </small>
        </div>
    `;
    // メタデータ編集フォーム（管理者のみ）
    if (isAdmin) {
        infoHTML += `
            <div class="col-12" id="metadataEditForm" style="display: none;">
                <div class="card border-primary">
                    <div class="card-body">
                        <h6 class="card-title mb-3">
                            <i class="bi bi-pencil-square"></i> ${lang['edit-metadata'] || 'メタデータを編集'}
                        </h6>

                        <!-- タイトル -->
                        <div class="mb-3">
                            <label for="metadataTitleInput" class="form-label small fw-bold">
                                <i class="bi bi-tag-fill"></i> ${lang['modal-title']}
                            </label>
                            <input type="text" class="form-control" id="metadataTitleInput"
                                   placeholder="${lang['title-placeholder'] || '例: 家族旅行 2024'}">
                        </div>

                        <!-- 撮影日 -->
                        <div class="mb-3">
                            <label for="metadataDateInput" class="form-label small fw-bold">
                                <i class="bi bi-camera-fill"></i> ${lang['exif-datetime']}
                            </label>
                            <input type="datetime-local" class="form-control" id="metadataDateInput">
                        </div>

                        <!-- ロケーション名 -->
                        <div class="mb-3">
                            <label for="metadataLocationNameInput" class="form-label small fw-bold">
                                <i class="bi bi-geo-alt-fill"></i> ${lang['exif-location'] || '位置情報'}
                            </label>
                            <input type="text" class="form-control" id="metadataLocationNameInput"
                                   placeholder="${lang['location-placeholder'] || '例: 東京都渋谷区'}">
                        </div>

                        <!-- GPS座標 -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="metadataLatitudeInput" class="form-label small fw-bold">
                                    ${lang['latitude'] || '緯度'}
                                </label>
                                <input type="number" class="form-control" id="metadataLatitudeInput"
                                       step="0.000001" min="-90" max="90"
                                       placeholder="${lang['latitude-placeholder'] || '例: 35.658581'}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="metadataLongitudeInput" class="form-label small fw-bold">
                                    ${lang['longitude'] || '経度'}
                                </label>
                                <input type="number" class="form-control" id="metadataLongitudeInput"
                                       step="0.000001" min="-180" max="180"
                                       placeholder="${lang['longitude-placeholder'] || '例: 139.745438'}">
                            </div>
                        </div>

                        <!-- ボタン -->
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-primary" id="saveMetadataBtn" onclick="saveMetadata()">
                                <i class="bi bi-check-circle"></i> ${lang['save'] || '保存'}
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="cancelEditMetadata()">
                                <i class="bi bi-x-circle"></i> ${lang['cancel']}
                            </button>
                            ${media.mime_type === 'image/jpeg' ? `
                            <button type="button" class="btn btn-info" id="writeExifBtn" onclick="writeExifToFile()">
                                <i class="bi bi-file-earmark-code"></i> ${lang['write-exif-to-file'] || 'EXIFをファイルに書き込む'}
                            </button>
                            ` : ''}
                        </div>
                        <span id="metadataStatus" class="small ms-2"></span>
                        ${media.mime_type !== 'image/jpeg' ? `
                        <div class="alert alert-info mt-2 mb-0" role="alert">
                            <small><i class="bi bi-info-circle"></i> ${lang['write-exif-only-jpeg'] || 'JPEGファイルのみEXIF書き込みがサポートされています'}</small>
                        </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }

    infoHTML += '</div>';
    modalInfo.innerHTML = infoHTML;

    // モーダルを表示
    modal.show();
}

/**
 * 動画の進行状況トラッキングを設定
 * @param {HTMLVideoElement} videoElement - 動画要素
 */
function setupVideoProgressTracking(videoElement) {
    const progressIndicator = document.getElementById('videoProgressIndicator');
    const progressBar = document.getElementById('videoProgressBar');
    const progressPercent = document.getElementById('videoProgressPercent');
    const currentTimeSpan = document.getElementById('videoCurrentTime');
    const durationSpan = document.getElementById('videoDuration');
    const playPauseIcon = document.getElementById('playPauseIcon');
    const muteIcon = document.getElementById('muteIcon');

    if (!progressIndicator || !videoElement) return;

    // 進行状況インジケーターを表示
    progressIndicator.style.display = 'block';

    // 時間をフォーマット (秒 -> MM:SS)
    function formatTime(seconds) {
        if (isNaN(seconds) || seconds < 0) return '0:00';
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }

    // 進行状況を更新
    function updateProgress() {
        const currentTime = videoElement.currentTime;
        const duration = videoElement.duration;

        if (duration > 0) {
            const percent = (currentTime / duration) * 100;
            progressBar.style.width = `${percent}%`;
            progressPercent.textContent = Math.round(percent);
            currentTimeSpan.textContent = formatTime(currentTime);
            durationSpan.textContent = formatTime(duration);
        }
    }

    // 再生/一時停止アイコンを更新
    function updatePlayPauseIcon() {
        if (videoElement.paused) {
            playPauseIcon.className = 'bi bi-play-fill';
        } else {
            playPauseIcon.className = 'bi bi-pause-fill';
        }
    }

    // ミュートアイコンを更新
    function updateMuteIcon() {
        if (videoElement.muted) {
            muteIcon.className = 'bi bi-volume-mute-fill';
        } else {
            muteIcon.className = 'bi bi-volume-up-fill';
        }
    }

    // メタデータ読み込み時（初回）
    videoElement.addEventListener('loadedmetadata', () => {
        durationSpan.textContent = formatTime(videoElement.duration);
        updateProgress();
        updatePlayPauseIcon();
        updateMuteIcon();
    });

    // 時間更新時
    videoElement.addEventListener('timeupdate', updateProgress);

    // シーク時
    videoElement.addEventListener('seeked', updateProgress);

    // 再生/一時停止時
    videoElement.addEventListener('play', updatePlayPauseIcon);
    videoElement.addEventListener('pause', updatePlayPauseIcon);

    // ボリューム変更時
    videoElement.addEventListener('volumechange', updateMuteIcon);

    // 再生終了時
    videoElement.addEventListener('ended', () => {
        progressBar.style.width = '100%';
        progressPercent.textContent = '100';
        updatePlayPauseIcon();
    });

    // 初期値設定
    if (videoElement.readyState >= 1) {
        durationSpan.textContent = formatTime(videoElement.duration);
        updateProgress();
    }
    updatePlayPauseIcon();
    updateMuteIcon();
}

/**
 * 再生/一時停止を切り替え
 */
function togglePlayPause() {
    const videoElement = document.getElementById('currentMediaElement');
    if (!videoElement || videoElement.tagName !== 'VIDEO') return;

    if (videoElement.paused) {
        videoElement.play();
    } else {
        videoElement.pause();
    }
}

/**
 * ミュートを切り替え
 */
function toggleMute() {
    const videoElement = document.getElementById('currentMediaElement');
    if (!videoElement || videoElement.tagName !== 'VIDEO') return;

    videoElement.muted = !videoElement.muted;
}

/**
 * プログレスバーをクリックしてシーク
 * @param {MouseEvent} event - クリックイベント
 */
function seekVideo(event) {
    const videoElement = document.getElementById('currentMediaElement');
    const progressContainer = document.getElementById('videoProgressContainer');

    if (!videoElement || !progressContainer || videoElement.tagName !== 'VIDEO') return;

    const rect = progressContainer.getBoundingClientRect();
    const clickX = event.clientX - rect.left;
    const width = rect.width;
    const percentage = clickX / width;
    const newTime = percentage * videoElement.duration;

    if (!isNaN(newTime)) {
        videoElement.currentTime = newTime;
    }
}

/**
 * 動画の進行状況表示を手動で更新
 * @param {HTMLVideoElement} videoElement - 動画要素
 */
function updateVideoProgressDisplay(videoElement) {
    const progressBar = document.getElementById('videoProgressBar');
    const progressPercent = document.getElementById('videoProgressPercent');
    const currentTimeSpan = document.getElementById('videoCurrentTime');
    const durationSpan = document.getElementById('videoDuration');

    if (!progressBar || !videoElement) return;

    function formatTime(seconds) {
        if (isNaN(seconds) || seconds < 0) return '0:00';
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }

    const currentTime = videoElement.currentTime;
    const duration = videoElement.duration;

    if (duration > 0) {
        const percent = (currentTime / duration) * 100;
        progressBar.style.width = `${percent}%`;
        progressPercent.textContent = Math.round(percent);
        currentTimeSpan.textContent = formatTime(currentTime);
        durationSpan.textContent = formatTime(duration);
    }
}


/**
 * メディアを削除
 * @param {Event|number} eventOrMediaId - イベントオブジェクトまたはメディアID
 * @param {string|number} filenameOrMediaId - ファイル名またはメディアID（第1引数がイベントの場合）
 * @param {string} filename - ファイル名（第1引数がイベントの場合）
 */
function deleteMedia(eventOrMediaId, filenameOrMediaId, filename) {
    // 引数の処理：eventオブジェクトが渡された場合と、従来の呼び出し方の両方に対応
    let event = null;
    let mediaId, targetFilename;

    if (eventOrMediaId && typeof eventOrMediaId === 'object' && eventOrMediaId.type) {
        // 新しい呼び出し方：deleteMedia(event, mediaId, filename)
        event = eventOrMediaId;
        mediaId = filenameOrMediaId;
        targetFilename = filename;

        // iOS Safari対策：イベント伝播を確実に止める
        if (event.preventDefault) event.preventDefault();
        if (event.stopPropagation) event.stopPropagation();
        if (event.stopImmediatePropagation) event.stopImmediatePropagation();
    } else {
        // 従来の呼び出し方：deleteMedia(mediaId, filename)
        mediaId = eventOrMediaId;
        targetFilename = filenameOrMediaId;
    }

    const lang = translations[currentLanguage];
    if (!confirm(lang['delete-confirm'].replace('{filename}', targetFilename))) {
        return false;
    }

    // 現在のページ番号を取得
    const urlParams = new URLSearchParams(window.location.search);
    const currentPage = urlParams.get('page') || '1';
    const filterType = urlParams.get('filter') || 'all';
    const searchQuery = urlParams.get('search') || '';
    const sortBy = urlParams.get('sort') || 'upload_date_desc';

    // 削除フォームを作成して送信
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'delete.php';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'media_id';
    input.value = mediaId;
    form.appendChild(input);

    // 現在のページ番号を追加
    const pageInput = document.createElement('input');
    pageInput.type = 'hidden';
    pageInput.name = 'current_page';
    pageInput.value = currentPage;
    form.appendChild(pageInput);

    // フィルター設定を追加
    const filterInput = document.createElement('input');
    filterInput.type = 'hidden';
    filterInput.name = 'filter';
    filterInput.value = filterType;
    form.appendChild(filterInput);

    // 検索クエリを追加
    if (searchQuery) {
        const searchInput = document.createElement('input');
        searchInput.type = 'hidden';
        searchInput.name = 'search';
        searchInput.value = searchQuery;
        form.appendChild(searchInput);
    }

    // ソート設定を追加
    if (sortBy) {
        const sortInput = document.createElement('input');
        sortInput.type = 'hidden';
        sortInput.name = 'sort';
        sortInput.value = sortBy;
        form.appendChild(sortInput);
    }

    document.body.appendChild(form);
    form.submit();
}

/**
 * ファイルサイズをフォーマット
 * @param {number} bytes - バイト数
 * @returns {string} フォーマットされたサイズ
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';

    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

/**
 * 日付をフォーマット
 * @param {string} dateString - 日付文字列
 * @returns {string} フォーマットされた日付
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return `${year}/${month}/${day} ${hours}:${minutes}`;
}

/**
 * HTMLエスケープ
 * @param {string} text - エスケープするテキスト
 * @returns {string} エスケープされたテキスト
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

/**
 * ファイル名を適度な長さに切り詰める
 * @param {string} filename - ファイル名
 * @param {number} maxLength - 最大文字数（デフォルト: 50）
 * @returns {string} 切り詰められたファイル名
 */
function truncateFilename(filename, maxLength = 50) {
    if (!filename || filename.length <= maxLength) {
        return filename;
    }

    // 拡張子を取得
    const lastDotIndex = filename.lastIndexOf('.');
    const hasExtension = lastDotIndex > 0 && lastDotIndex < filename.length - 1;

    if (hasExtension) {
        const name = filename.substring(0, lastDotIndex);
        const extension = filename.substring(lastDotIndex); // ドットを含む

        // 拡張子を考慮した最大文字数
        const maxNameLength = maxLength - extension.length - 3; // 3は "..." の長さ

        if (maxNameLength > 10) {
            // 前半と後半に分けて表示
            const frontLength = Math.ceil(maxNameLength * 0.6);
            const backLength = Math.floor(maxNameLength * 0.4);

            return name.substring(0, frontLength) + '...' + name.substring(name.length - backLength) + extension;
        }
    }

    // 拡張子がない場合、または拡張子が長すぎる場合
    return filename.substring(0, maxLength - 3) + '...';
}

/**
 * ツールチップの初期化
 */
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * HEIC/HEIFファイルをJPEGに変換（クライアント側）
 * @param {File[]} files - ファイルの配列
 * @returns {Promise<File[]>} 変換後のファイル配列
 */
async function convertHeicFilesToJpeg(files) {
    const convertedFiles = [];

    // heic2anyライブラリの可用性チェック
    if (typeof heic2any === 'undefined') {
        return files; // ライブラリがない場合は元のファイルをそのまま返す
    }

    for (const file of files) {
        const fileName = file.name.toLowerCase();
        const isHeic = fileName.endsWith('.heic') || fileName.endsWith('.heif');

        if (isHeic) {
            try {
                // HEIC変換前に元ファイルからEXIF情報を読み取る
                let originalExifData = null;
                try {
                    // ArrayBufferとして読み込んでEXIF情報を抽出
                    const arrayBuffer = await file.arrayBuffer();
                    const dataView = new DataView(arrayBuffer);

                    // piexifjsでEXIF情報を読み取り（可能であれば）
                    if (typeof piexif !== 'undefined') {
                        try {
                            // HEICファイルからは直接読めないため、後でサーバー側で処理
                            originalExifData = null;
                        } catch (e) {
                            // HEIC形式ではpiexifが使えないため無視
                        }
                    }
                } catch (exifError) {
                    // EXIF読み取り失敗は無視して変換を続行
                }

                // heic2anyライブラリで変換
                const convertedBlob = await heic2any({
                    blob: file,
                    toType: 'image/jpeg',
                    quality: 0.9
                });

                // BlobをFileオブジェクトに変換
                const jpegFileName = file.name.replace(/\.(heic|heif)$/i, '.jpg');
                const jpegFile = new File(
                    [convertedBlob],
                    jpegFileName,
                    { type: 'image/jpeg', lastModified: file.lastModified }
                );

                convertedFiles.push(jpegFile);
            } catch (error) {
                const lang = translations[currentLanguage];
                alert(lang['heic-conversion-failed'].replace('{filename}', file.name));
                convertedFiles.push(file); // 変換失敗時は元のファイルを使用
            }
        } else {
            // HEIC以外のファイルはそのまま
            convertedFiles.push(file);
        }
    }

    // 変換後のファイルでFileListを再作成
    // DataTransferを使用してFileListを生成
    const dataTransfer = new DataTransfer();
    convertedFiles.forEach(file => dataTransfer.items.add(file));

    // 元のinput要素のfilesを更新
    const mediaFileInput = document.getElementById('mediaFile');
    if (mediaFileInput) {
        mediaFileInput.files = dataTransfer.files;
    }

    return convertedFiles;
}

/**
 * チャンク分割アップロード（大きなファイル対応）
 * @param {File} file - アップロードするファイル
 * @param {string} fileIdentifier - ファイルの一意識別子
 * @param {Function} onProgress - 進捗コールバック
 * @param {number} maxRetries - 最大リトライ回数
 * @returns {Promise<boolean>} アップロード成功/失敗
 */
async function uploadFileInChunks(file, fileIdentifier, onProgress, maxRetries = 3) {
    const chunkSize = 1024 * 1024; // 1MB per chunk
    const totalChunks = Math.ceil(file.size / chunkSize);
    let uploadedBytes = 0;

    for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
        const start = chunkIndex * chunkSize;
        const end = Math.min(start + chunkSize, file.size);
        const chunk = file.slice(start, end);

        // リトライロジック
        let success = false;
        let retryCount = 0;

        while (!success && retryCount < maxRetries) {
            try {
                const formData = new FormData();
                formData.append('chunk', chunk);
                formData.append('chunkIndex', chunkIndex);
                formData.append('totalChunks', totalChunks);
                formData.append('fileName', file.name);
                formData.append('fileIdentifier', fileIdentifier);

                const response = await fetch('lib/chunk_upload.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.error || 'チャンクアップロード失敗');
                }

                // 進捗を更新
                uploadedBytes = end;
                if (onProgress) {
                    onProgress(uploadedBytes, file.size);
                }

                success = true;

            } catch (error) {
                retryCount++;
                console.error(`チャンク ${chunkIndex} のアップロード失敗 (試行 ${retryCount}/${maxRetries}):`, error);

                if (retryCount >= maxRetries) {
                    throw new Error(`チャンク ${chunkIndex} のアップロードに失敗しました: ${error.message}`);
                }

                // エクスポネンシャルバックオフ
                const delay = Math.min(1000 * Math.pow(2, retryCount - 1), 10000);
                await new Promise(resolve => setTimeout(resolve, delay));
            }
        }
    }

    return true;
}

/**
 * ファイルの最終処理（データベース登録）
 * @param {string} fileIdentifier - ファイルの一意識別子
 * @param {string} title - タイトル
 * @param {string} description - 説明
 * @param {string} thumbnailData - サムネイルのBase64データ
 * @param {Object} exifData - EXIF情報
 * @returns {Promise<Object>} レスポンス
 */
async function finalizeFileUpload(fileIdentifier, title, description, thumbnailData, exifData) {
    const formData = new FormData();
    formData.append('fileIdentifier', fileIdentifier);
    formData.append('title', title || '');
    formData.append('description', description || '');
    if (thumbnailData) {
        formData.append('thumbnailData', thumbnailData);
    }

    // EXIF情報を追加
    if (exifData) {
        formData.append('exifData', JSON.stringify(exifData));
    }

    const response = await fetch('lib/finalize_upload.php', {
        method: 'POST',
        body: formData
    });

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }

    return await response.json();
}

/**
 * 複数ファイルのアップロード処理（ファイルサイズ按分での進捗表示）
 * @param {FileList} files - アップロードするファイルリスト
 * @param {string} title - タイトル
 * @param {string} description - 説明
 * @param {Map} thumbnailMap - ファイル名とサムネイルのマップ
 * @param {Map} exifMap - ファイル名とEXIF情報のマップ
 * @returns {Promise<Object>} アップロード結果
 */
async function uploadMultipleFiles(files, title, description, thumbnailMap, exifMap) {
    const progressBar = document.querySelector('#uploadProgress .progress-bar');
    const uploadBtn = document.getElementById('uploadBtn');

    // 全ファイルの合計サイズを計算
    const totalSize = Array.from(files).reduce((sum, file) => sum + file.size, 0);
    let totalUploadedBytes = 0;

    const results = {
        success: [],
        errors: []
    };

    // 各ファイルをアップロード
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const fileIdentifier = `${Date.now()}_${i}_${Math.random().toString(36).substr(2, 9)}`;

        try {
            const lang = translations[currentLanguage];
            // アップロードボタンのテキストを更新
            if (uploadBtn) {
                uploadBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>${lang['uploading-count'].replace('{current}', i + 1).replace('{total}', files.length)}`;
            }

            // このファイルのアップロード開始前のバイト数を記録
            const fileStartBytes = totalUploadedBytes;

            // チャンク分割アップロード
            await uploadFileInChunks(file, fileIdentifier, (uploadedBytes, fileSize) => {
                // このファイルの進捗を全体の進捗に反映
                const currentFileProgress = fileStartBytes + uploadedBytes;
                const overallProgress = Math.min(100, Math.round((currentFileProgress / totalSize) * 100));

                if (progressBar) {
                    progressBar.style.width = overallProgress + '%';
                    progressBar.textContent = overallProgress + '%';
                }
            });

            // このファイルのアップロードが完了したので、全体のアップロード済みバイト数を更新
            totalUploadedBytes += file.size;

            // サムネイルデータを取得
            const thumbnailData = thumbnailMap.get(file.name) || null;

            // EXIF情報を取得
            const exifData = exifMap ? exifMap.get(file.name) || null : null;

            // 最終処理（データベース登録）
            const result = await finalizeFileUpload(fileIdentifier, title, description, thumbnailData, exifData);

            if (result.success) {
                results.success.push(file.name);
            } else {
                results.errors.push(`${file.name}: ${result.error}`);
            }

        } catch (error) {
            console.error(`ファイル ${file.name} のアップロード失敗:`, error);
            results.errors.push(`${file.name}: ${error.message}`);

            // エラーでもこのファイルのサイズ分は進捗に加算（スキップ）
            totalUploadedBytes += file.size;

            // エラー時のみ進捗を更新（成功時はchunk callbackで既に更新済み）
            const overallProgress = Math.min(100, Math.round((totalUploadedBytes / totalSize) * 100));
            if (progressBar) {
                progressBar.style.width = overallProgress + '%';
                progressBar.textContent = overallProgress + '%';
            }
        }
    }

    return results;
}

/**
 * アラートの自動非表示
 */
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        setTimeout(() => {
            bsAlert.close();
        }, 5000); // 5秒後に自動的に閉じる
    });
}, 500);

/**
 * メディアを回転（一時的）
 * @param {number} degrees - 回転角度（90 or -90）
 */
function rotateMedia(degrees) {
    if (!currentMedia) return;

    const lang = translations[currentLanguage];
    const mediaElement = document.getElementById('currentMediaElement');
    const saveBtn = document.getElementById('saveRotationBtn');
    const statusEl = document.getElementById('rotationStatus');

    if (!mediaElement || !saveBtn || !statusEl) return;

    // 現在の回転角度を更新
    currentRotation = (currentRotation + degrees + 360) % 360;

    // CSSクラスを更新
    mediaElement.className = mediaElement.className.replace(/rotate-\d+/, `rotate-${currentRotation}`);

    // 保存ボタンを表示
    saveBtn.style.display = 'inline-block';
    statusEl.textContent = lang['rotation-changed'];
    statusEl.classList.add('text-warning');
}

/**
 * 回転設定を保存
 */
async function saveRotation() {
    if (!currentMedia) return;

    const lang = translations[currentLanguage];
    const saveBtn = document.getElementById('saveRotationBtn');
    const statusEl = document.getElementById('rotationStatus');

    if (!saveBtn || !statusEl) return;

    // ボタンを無効化
    saveBtn.disabled = true;
    saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>${lang['saving']}`;

    try {
        const response = await fetch('api/update_rotation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                media_id: currentMedia.id,
                rotation: currentRotation
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();

        if (result.success) {
            // 成功メッセージ
            statusEl.textContent = lang['rotation-saved'];
            statusEl.classList.remove('text-warning');
            statusEl.classList.add('text-success');

            // currentMediaのrotation値を更新
            currentMedia.rotation = currentRotation;

            console.log('回転設定を保存しました:', result);

            // 1秒後にページをリロードしてキャッシュをクリア
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            throw new Error(result.error || lang['save-failed']);
        }

    } catch (error) {
        console.error('回転設定の保存に失敗:', error);
        statusEl.textContent = lang['rotation-save-error'];
        statusEl.classList.remove('text-warning');
        statusEl.classList.add('text-danger');

        // エラーメッセージを3秒後にクリア
        setTimeout(() => {
            statusEl.textContent = lang['rotation-changed'];
            statusEl.classList.remove('text-danger');
            statusEl.classList.add('text-warning');
        }, 3000);
    } finally {
        // ボタンを再有効化
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="bi bi-check-circle"></i> <span data-i18n="save-rotation">' + lang['save-rotation'] + '</span>';
    }
}

// ===== フィルター・ソート設定の永続化 =====

/**
 * フィルター・ソート設定をlocalStorageに保存
 * @param {string} filter - フィルター値
 * @param {string} sort - ソート値
 * @param {string} search - 検索クエリ
 */
function saveFilterSortSettings(filter, sort, search) {
    try {
        localStorage.setItem('kidsnaps-filter', filter || 'all');
        localStorage.setItem('kidsnaps-sort', sort || 'upload_date_desc');
        localStorage.setItem('kidsnaps-search', search || '');
    } catch (e) {
        console.warn('localStorage保存に失敗しました:', e);
    }
}

/**
 * localStorageからフィルター・ソート設定を読み込み
 * @returns {Object} 保存された設定値
 */
function loadFilterSortSettings() {
    try {
        return {
            filter: localStorage.getItem('kidsnaps-filter') || 'all',
            sort: localStorage.getItem('kidsnaps-sort') || 'upload_date_desc',
            search: localStorage.getItem('kidsnaps-search') || ''
        };
    } catch (e) {
        console.warn('localStorage読み込みに失敗しました:', e);
        return {
            filter: 'all',
            sort: 'upload_date_desc',
            search: ''
        };
    }
}

/**
 * ページロード時にフィルター・ソート設定を復元
 */
function restoreFilterSortSettings() {
    // index.phpページ以外では実行しない
    if (!window.location.pathname.endsWith('index.php') &&
        !window.location.pathname.endsWith('/')) {
        return;
    }

    // URLパラメータを取得
    const urlParams = new URLSearchParams(window.location.search);
    const hasFilter = urlParams.has('filter');
    const hasSort = urlParams.has('sort');
    const hasSearch = urlParams.has('search');

    // URLパラメータがある場合は、それをlocalStorageに保存
    if (hasFilter || hasSort || hasSearch) {
        const filter = urlParams.get('filter') || 'all';
        const sort = urlParams.get('sort') || 'upload_date_desc';
        const search = urlParams.get('search') || '';
        saveFilterSortSettings(filter, sort, search);
        return;
    }

    // URLパラメータがない場合、localStorageから復元
    const saved = loadFilterSortSettings();

    // デフォルト値と異なる場合のみURLを更新
    if (saved.filter !== 'all' || saved.sort !== 'upload_date_desc' || saved.search !== '') {
        const newParams = new URLSearchParams(window.location.search);

        if (saved.filter !== 'all') {
            newParams.set('filter', saved.filter);
        }
        if (saved.sort !== 'upload_date_desc') {
            newParams.set('sort', saved.sort);
        }
        if (saved.search !== '') {
            newParams.set('search', saved.search);
        }

        // ページ番号は保持しない（初期表示時は1ページ目）
        newParams.delete('page');

        // URLを更新してページをリロード
        const newUrl = window.location.pathname + '?' + newParams.toString();
        if (newUrl !== window.location.pathname + window.location.search) {
            window.location.href = newUrl;
        }
    }
}

/**
 * フォーム送信時に設定を保存
 */
function setupFilterSortFormListeners() {
    // index.phpページ以外では実行しない
    if (!window.location.pathname.endsWith('index.php') &&
        !window.location.pathname.endsWith('/')) {
        return;
    }

    // フィルター・ソートフォームを取得
    const form = document.querySelector('form[action="index.php"]');
    if (!form) return;

    const filterSelect = form.querySelector('select[name="filter"]');
    const sortSelect = form.querySelector('select[name="sort"]');
    const searchInput = form.querySelector('input[name="search"]');

    // フォーム送信前に設定を保存
    form.addEventListener('submit', function(e) {
        const filter = filterSelect ? filterSelect.value : 'all';
        const sort = sortSelect ? sortSelect.value : 'upload_date_desc';
        const search = searchInput ? searchInput.value : '';
        saveFilterSortSettings(filter, sort, search);
    });

    // セレクトボックス変更時にも保存（即座にフォームが送信されるため）
    if (filterSelect) {
        filterSelect.addEventListener('change', function() {
            const filter = filterSelect.value;
            const sort = sortSelect ? sortSelect.value : 'upload_date_desc';
            const search = searchInput ? searchInput.value : '';
            saveFilterSortSettings(filter, sort, search);
        });
    }

    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const filter = filterSelect ? filterSelect.value : 'all';
            const sort = sortSelect.value;
            const search = searchInput ? searchInput.value : '';
            saveFilterSortSettings(filter, sort, search);
        });
    }
}

// ページロード時に設定を復元
document.addEventListener('DOMContentLoaded', function() {
    restoreFilterSortSettings();
    setupFilterSortFormListeners();
});

/**
 * 撮影日編集機能
 */

// 撮影日編集UIを表示
function editPhotoDate() {
    if (!currentMedia) return;

    const displayElement = document.getElementById('photoDateDisplay');
    const editElement = document.getElementById('photoDateEdit');

    if (!displayElement || !editElement) return;

    // 現在の撮影日時を入力フォームにセット
    const dateInput = document.getElementById('photoDateInput');
    if (currentMedia.exif_datetime) {
        // "YYYY-MM-DD HH:MM:SS" -> "YYYY-MM-DDTHH:MM"
        const datetime = currentMedia.exif_datetime.replace(' ', 'T').substring(0, 16);
        dateInput.value = datetime;
    } else {
        dateInput.value = '';
    }

    // 表示と編集を切り替え
    displayElement.style.display = 'none';
    editElement.style.display = 'block';
    dateInput.focus();
}

// 撮影日編集をキャンセル
function cancelEditPhotoDate() {
    const displayElement = document.getElementById('photoDateDisplay');
    const editElement = document.getElementById('photoDateEdit');

    if (!displayElement || !editElement) return;

    displayElement.style.display = 'block';
    editElement.style.display = 'none';
}

// 撮影日を保存
function savePhotoDate() {
    if (!currentMedia) return;

    const lang = translations[currentLanguage];
    const dateInput = document.getElementById('photoDateInput');
    const saveBtn = document.getElementById('savePhotoDateBtn');
    const statusElement = document.getElementById('photoDateStatus');

    const newDateTime = dateInput.value; // "YYYY-MM-DDTHH:MM" format

    // 保存ボタンを無効化
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.innerHTML = `<i class="bi bi-hourglass-split"></i> ${lang['saving-in-progress']}`;
    }

    // APIリクエスト
    fetch('api/update_photo_date.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            media_id: currentMedia.id,
            exif_datetime: newDateTime
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 成功メッセージ
            if (statusElement) {
                statusElement.textContent = lang['saved-successfully'];
                statusElement.className = 'text-success small ms-2';
                setTimeout(() => {
                    statusElement.textContent = '';
                }, 3000);
            }

            // currentMediaを更新
            currentMedia.exif_datetime = data.exif_datetime;

            // 表示を更新
            const displayElement = document.getElementById('photoDateDisplay');
            if (displayElement) {
                if (data.exif_datetime) {
                    displayElement.innerHTML = `<small>${formatDate(data.exif_datetime)}</small>`;
                } else {
                    displayElement.innerHTML = `<small class="text-muted">${lang['not-set']}</small>`;
                }
            }

            // 編集UIを閉じる
            cancelEditPhotoDate();

            // ページをリロードして一覧の表示も更新
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            throw new Error(data.error || lang['save-failed']);
        }
    })
    .catch(error => {
        console.error('撮影日保存エラー:', error);
        if (statusElement) {
            statusElement.textContent = '✗ ' + error.message;
            statusElement.className = 'text-danger small ms-2';
        }
        alert(lang['update-photo-date-error'] + ': ' + error.message);
    })
    .finally(() => {
        // 保存ボタンを再度有効化
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = `<i class="bi bi-check-circle"></i> ${lang['photo-date-save']}`;
        }
    });
}

/**
 * メタデータ編集機能
 */

// メタデータ編集UIを表示
function editMetadata() {
    if (!currentMedia) return;

    const editForm = document.getElementById('metadataEditForm');
    if (!editForm) return;

    // 現在の値を入力フォームにセット
    const titleInput = document.getElementById('metadataTitleInput');
    const dateInput = document.getElementById('metadataDateInput');
    const locationNameInput = document.getElementById('metadataLocationNameInput');
    const latitudeInput = document.getElementById('metadataLatitudeInput');
    const longitudeInput = document.getElementById('metadataLongitudeInput');

    if (titleInput) titleInput.value = currentMedia.title || '';

    if (dateInput) {
        if (currentMedia.exif_datetime) {
            // "YYYY-MM-DD HH:MM:SS" -> "YYYY-MM-DDTHH:MM"
            const datetime = currentMedia.exif_datetime.replace(' ', 'T').substring(0, 16);
            dateInput.value = datetime;
        } else {
            dateInput.value = '';
        }
    }

    if (locationNameInput) locationNameInput.value = currentMedia.exif_location_name || '';
    if (latitudeInput) latitudeInput.value = currentMedia.exif_latitude || '';
    if (longitudeInput) longitudeInput.value = currentMedia.exif_longitude || '';

    // フォームを表示
    editForm.style.display = 'block';
    editForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// メタデータ編集をキャンセル
function cancelEditMetadata() {
    const editForm = document.getElementById('metadataEditForm');
    if (!editForm) return;

    editForm.style.display = 'none';
}

// メタデータを保存
function saveMetadata() {
    if (!currentMedia) return;

    const lang = translations[currentLanguage];
    const titleInput = document.getElementById('metadataTitleInput');
    const dateInput = document.getElementById('metadataDateInput');
    const locationNameInput = document.getElementById('metadataLocationNameInput');
    const latitudeInput = document.getElementById('metadataLatitudeInput');
    const longitudeInput = document.getElementById('metadataLongitudeInput');
    const saveBtn = document.getElementById('saveMetadataBtn');
    const statusElement = document.getElementById('metadataStatus');

    // 入力値を取得
    const newTitle = titleInput ? titleInput.value : '';
    const newDateTime = dateInput ? dateInput.value : ''; // "YYYY-MM-DDTHH:MM" format
    const newLocationName = locationNameInput ? locationNameInput.value : '';
    const newLatitude = latitudeInput ? latitudeInput.value : '';
    const newLongitude = longitudeInput ? longitudeInput.value : '';

    // 保存ボタンを無効化
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.innerHTML = `<i class="bi bi-hourglass-split"></i> ${lang['saving-in-progress'] || '保存中...'}`;
    }

    // APIリクエスト用のデータを構築
    const requestData = {
        media_id: currentMedia.id,
        title: newTitle,
        exif_datetime: newDateTime,
        exif_location_name: newLocationName,
        exif_latitude: newLatitude,
        exif_longitude: newLongitude
    };

    // APIリクエスト
    fetch('api/update_metadata.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 成功メッセージ
            if (statusElement) {
                statusElement.textContent = lang['saved-successfully'] || '保存しました';
                statusElement.className = 'text-success small ms-2';
                setTimeout(() => {
                    statusElement.textContent = '';
                }, 3000);
            }

            // currentMediaを更新
            if (data.data) {
                currentMedia.title = data.data.title;
                currentMedia.exif_datetime = data.data.exif_datetime;
                currentMedia.exif_location_name = data.data.exif_location_name;
                currentMedia.exif_latitude = data.data.exif_latitude;
                currentMedia.exif_longitude = data.data.exif_longitude;
            }

            // 表示を更新
            updateMetadataDisplay();

            // 編集UIを閉じる
            cancelEditMetadata();

            // ページをリロードして一覧の表示も更新
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            throw new Error(data.error || lang['save-failed'] || '保存に失敗しました');
        }
    })
    .catch(error => {
        console.error('メタデータ保存エラー:', error);
        if (statusElement) {
            statusElement.textContent = '✗ ' + error.message;
            statusElement.className = 'text-danger small ms-2';
        }
        alert((lang['update-metadata-error'] || 'メタデータの更新に失敗しました') + ': ' + error.message);
    })
    .finally(() => {
        // 保存ボタンを再度有効化
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = `<i class="bi bi-check-circle"></i> ${lang['save'] || '保存'}`;
        }
    });
}

// EXIFデータをファイルに書き込む
function writeExifToFile() {
    if (!currentMedia) return;

    const lang = translations[currentLanguage];

    // JPEGファイルのみサポート
    if (currentMedia.mime_type !== 'image/jpeg') {
        alert(lang['write-exif-only-jpeg'] || 'JPEGファイルのみサポートされています');
        return;
    }

    // 確認ダイアログ
    if (!confirm(lang['write-exif-confirm'] || '現在のメタデータをEXIF情報として画像ファイルに書き込みますか？\n\n※画像ファイル自体を物理的に変更します。')) {
        return;
    }

    const writeExifBtn = document.getElementById('writeExifBtn');
    const statusElement = document.getElementById('metadataStatus');

    // ボタンを無効化
    if (writeExifBtn) {
        writeExifBtn.disabled = true;
        writeExifBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${lang['write-exif-writing'] || 'EXIF書き込み中...'}`;
    }

    // APIリクエスト
    fetch('api/write_exif_to_file.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            media_id: currentMedia.id
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 成功メッセージ
            if (statusElement) {
                statusElement.textContent = '✓ ' + (lang['write-exif-success'] || 'EXIFデータをファイルに書き込みました');
                statusElement.className = 'text-success small ms-2';
                setTimeout(() => {
                    statusElement.textContent = '';
                }, 5000);
            }

            alert(lang['write-exif-success'] || 'EXIFデータをファイルに書き込みました');

            // ページをリロード
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            throw new Error(data.error || lang['write-exif-error'] || 'EXIFデータの書き込みに失敗しました');
        }
    })
    .catch(error => {
        console.error('EXIF書き込みエラー:', error);
        if (statusElement) {
            statusElement.textContent = '✗ ' + error.message;
            statusElement.className = 'text-danger small ms-2';
        }
        alert((lang['write-exif-error'] || 'EXIFデータの書き込みに失敗しました') + ':\n' + error.message);
    })
    .finally(() => {
        // ボタンを再度有効化
        if (writeExifBtn) {
            writeExifBtn.disabled = false;
            writeExifBtn.innerHTML = `<i class="bi bi-file-earmark-code"></i> ${lang['write-exif-to-file'] || 'EXIFをファイルに書き込む'}`;
        }
    });
}

// メタデータ表示を更新
function updateMetadataDisplay() {
    const lang = translations[currentLanguage];

    // タイトル表示を更新
    const titleDisplay = document.getElementById('metadataTitleDisplay');
    if (titleDisplay) {
        titleDisplay.innerHTML = currentMedia.title ?
            `<p class="mb-0">${escapeHtml(currentMedia.title)}</p>` :
            '';
    }

    // 撮影日表示を更新
    const dateDisplay = document.getElementById('metadataDateDisplay');
    if (dateDisplay) {
        const displayDateHTML = currentMedia.exif_datetime ?
            formatDate(currentMedia.exif_datetime) :
            `<span class="text-muted">${lang['not-set']}</span>`;
        dateDisplay.innerHTML = `<small>${displayDateHTML}</small>`;
    }

    // ロケーション表示を更新
    const locationDisplay = document.getElementById('metadataLocationDisplay');
    if (locationDisplay) {
        let locationHTML = '';

        if (currentMedia.exif_latitude && currentMedia.exif_longitude) {
            const lat = parseFloat(currentMedia.exif_latitude);
            const lng = parseFloat(currentMedia.exif_longitude);
            const mapLink = `https://www.google.com/maps?q=${lat},${lng}`;

            if (currentMedia.exif_location_name) {
                locationHTML = `${escapeHtml(currentMedia.exif_location_name)}<br>`;
            }

            locationHTML += `
                <a href="${mapLink}" target="_blank" rel="noopener noreferrer" class="text-decoration-none">
                    📍 ${lat.toFixed(6)}, ${lng.toFixed(6)}
                    <i class="bi bi-box-arrow-up-right small"></i>
                </a>
            `;
        } else if (currentMedia.exif_location_name) {
            locationHTML = escapeHtml(currentMedia.exif_location_name);
        } else {
            locationHTML = `<span class="text-muted">${lang['not-set']}</span>`;
        }

        locationDisplay.innerHTML = `<small>${locationHTML}</small>`;
    }
}

/**
 * Brave browser fix: Delete button event listener
 * Use event delegation instead of inline onclick to avoid issues with Brave Shields
 * Support both click and touch events for mobile devices
 */
document.addEventListener('DOMContentLoaded', function() {
    // Handler function for delete button events
    function handleDeleteButtonEvent(event) {
        const deleteBtn = event.target.closest('.delete-media-btn');
        if (deleteBtn) {
            // Prevent event propagation to parent elements
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();

            // Get data attributes
            const mediaId = deleteBtn.getAttribute('data-media-id');
            const filename = deleteBtn.getAttribute('data-filename');

            // Call deleteMedia function
            if (mediaId && filename) {
                deleteMedia(event, mediaId, filename);
            }

            return false;
        }
    }

    // Event delegation for delete buttons - click event
    document.addEventListener('click', handleDeleteButtonEvent, true);

    // Event delegation for delete buttons - touch event for mobile
    document.addEventListener('touchend', handleDeleteButtonEvent, true);

    // HEIC thumbnail conversion: Convert HEIC thumbnails to JPEG for display
    convertHeicThumbnails();
});

/**
 * Convert HEIC thumbnails to JPEG using heic2any
 * This function finds all img elements with HEIC sources and converts them
 */
async function convertHeicThumbnails() {
    // Find all img elements with HEIC sources
    const thumbnails = document.querySelectorAll('img.media-thumbnail');

    for (const img of thumbnails) {
        const src = img.src || img.getAttribute('data-src') || '';

        // Check if source is HEIC/HEIF
        if (src.match(/\.(heic|heif)$/i)) {
            try {
                // Fetch the HEIC file
                const response = await fetch(src);
                const blob = await response.blob();

                // Convert to JPEG
                const convertedBlob = await heic2any({
                    blob: blob,
                    toType: 'image/jpeg',
                    quality: 0.8
                });

                // Create Blob URL
                const blobUrl = URL.createObjectURL(convertedBlob);

                // Replace image source
                img.src = blobUrl;

                // Store Blob URL for cleanup (optional)
                if (!window.heicThumbnailUrls) {
                    window.heicThumbnailUrls = [];
                }
                window.heicThumbnailUrls.push(blobUrl);

                console.log(`Converted HEIC thumbnail: ${src}`);
            } catch (error) {
                console.error(`Failed to convert HEIC thumbnail: ${src}`, error);
                // On error, try to display a placeholder or original image
                img.alt = 'HEIC image (conversion failed)';
            }
        }
    }
}
