/**
 * KidSnaps Growth Album - JavaScript機能
 */

// ===== 多言語対応 =====
const translations = {
    en: {
        'nav-gallery': 'Gallery',
        'nav-upload': 'Upload',
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
        'file-info': 'Supported formats: JPEG, PNG, GIF, HEIC, MP4, MOV, AVI (Max 50MB per file, multiple files allowed)',
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
        'file-size-error': 'File size is too large'
    },
    ja: {
        'nav-gallery': 'ギャラリー',
        'nav-upload': 'アップロード',
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
        'file-info': '対応形式: JPEG, PNG, GIF, HEIC, MP4, MOV, AVI (各ファイル最大50MB、複数選択可)',
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
        'file-size-error': 'ファイルサイズが大きすぎます'
    }
};

// 現在の言語とテーマ
let currentLanguage = localStorage.getItem('kidsnaps-language') || 'en'; // デフォルトEN
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
 * 言語を適用
 */
function applyLanguage() {
    const lang = translations[currentLanguage];

    // data-i18n属性を持つすべての要素を更新
    document.querySelectorAll('[data-i18n]').forEach(element => {
        const key = element.getAttribute('data-i18n');
        if (key === 'media-count') {
            // メディア件数の特殊処理
            const count = element.textContent.match(/\d+/);
            if (count && lang['media-count-format']) {
                element.textContent = lang['media-count-format'].replace('{count}', count[0]);
            }
        } else if (lang[key]) {
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

// DOM読み込み完了後に実行
document.addEventListener('DOMContentLoaded', function() {
    console.log('KidSnaps Growth Album initialized');

    // 言語とテーマを適用
    applyLanguage();
    applyTheme();

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
                uploadBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>変換中...';
            }

            // HEIC/HEIFファイルをJPEGに変換（クライアント側）
            files = await convertHeicFilesToJpeg(files);

            // アップロードボタンを再有効化
            if (uploadBtn) {
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '<i class="bi bi-cloud-upload"></i> <span data-i18n="upload">アップロード</span>';
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
                const maxSize = 50 * 1024 * 1024; // 50MB

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
                    alert('50MBを超えるファイルが含まれています。ファイルを選択し直してください。');
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

    // フォーム送信時の処理（複数ファイル対応）
    uploadForm.addEventListener('submit', function(e) {
        const fileInput = document.getElementById('mediaFile');

        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            e.preventDefault();
            alert('ファイルを選択してください。');
            return;
        }

        // 全ファイルのサイズチェック
        const files = Array.from(fileInput.files);
        const maxSize = 50 * 1024 * 1024; // 50MB
        const oversizedFiles = files.filter(f => f.size > maxSize);

        if (oversizedFiles.length > 0) {
            e.preventDefault();
            alert(`以下のファイルは50MBを超えています:\n${oversizedFiles.map(f => f.name).join('\n')}`);
            return;
        }

        // アップロードボタンを無効化
        if (uploadBtn) {
            uploadBtn.disabled = true;
            uploadBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>${files.length}件アップロード中...`;
        }

        // プログレスバーを表示
        if (uploadProgress) {
            uploadProgress.classList.remove('d-none');
            simulateProgress();
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
        const video = document.createElement('video');
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        video.preload = 'metadata';
        video.muted = true;
        video.playsInline = true;

        video.onloadeddata = function() {
            video.currentTime = Math.min(1, video.duration / 2);
        };

        video.onseeked = function() {
            const maxWidth = 800;
            const scale = Math.min(maxWidth / video.videoWidth, 1);
            canvas.width = video.videoWidth * scale;
            canvas.height = video.videoHeight * scale;

            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            canvas.toBlob(function(blob) {
                URL.revokeObjectURL(video.src);
                if (blob) {
                    resolve(blob);
                } else {
                    reject(new Error('サムネイル生成に失敗しました'));
                }
            }, 'image/jpeg', 0.85);
        };

        video.onerror = function() {
            URL.revokeObjectURL(video.src);
            reject(new Error('動画の読み込みに失敗しました'));
        };

        video.src = URL.createObjectURL(videoFile);
    });
}

/**
 * 疑似的なプログレスバー進捗表示
 */
function simulateProgress() {
    const progressBar = document.querySelector('#uploadProgress .progress-bar');
    if (!progressBar) return;

    let progress = 0;
    const interval = setInterval(() => {
        progress += Math.random() * 15;
        if (progress > 90) {
            progress = 90;
            clearInterval(interval);
        }
        progressBar.style.width = progress + '%';
        progressBar.textContent = Math.round(progress) + '%';
    }, 200);
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
        const media = JSON.parse(mediaJson);
        viewMedia(media);
    } catch (error) {
        console.error('Failed to parse media data:', error);
        alert('メディアデータの読み込みに失敗しました。');
    }
}

/**
 * メディアを表示（モーダル）
 * @param {Object} media - メディア情報オブジェクト
 */
function viewMedia(media) {
    const modal = new bootstrap.Modal(document.getElementById('viewModal'));
    const modalTitle = document.getElementById('viewModalLabel');
    const modalBody = document.getElementById('viewModalBody');
    const modalInfo = document.getElementById('viewModalInfo');
    const rotationControls = document.getElementById('rotationControls');

    // 現在のメディア情報を保存
    currentMedia = media;
    currentRotation = media.rotation || 0;

    // タイトル設定（地名があれば地名、なければファイル名）
    if (media.exif_location_name) {
        modalTitle.textContent = media.exif_location_name;
    } else if (media.title && media.title.trim() !== '') {
        modalTitle.textContent = media.title;
    } else {
        // タイトルも地名もない場合はファイル名を表示
        modalTitle.textContent = media.filename;
    }

    // メディアコンテンツの表示
    let mediaHTML = '';
    if (media.file_type === 'image') {
        mediaHTML = `
            <img src="${escapeHtml(media.file_path)}"
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

    // タイトル表示（ファイル名と異なる場合）
    if (media.title && media.title !== media.filename) {
        infoHTML += `
            <div class="col-12">
                <h6 class="mb-2"><i class="bi bi-tag-fill"></i> ${lang['modal-title'] || 'タイトル'}</h6>
                <p class="mb-0">${escapeHtml(media.title)}</p>
            </div>
        `;
    }

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
                <h6 class="mb-2"><i class="bi bi-card-text"></i> ${lang['modal-description'] || '説明'}</h6>
                <p class="mb-0">${escapeHtml(descriptionText)}</p>
            </div>
        `;
    }

    // EXIF詳細情報セクション
    const hasExifData = media.exif_datetime || (media.exif_latitude && media.exif_longitude) || media.exif_camera_make || media.exif_camera_model;

    if (hasExifData) {
        infoHTML += `<div class="col-12"><hr class="my-2"></div>`;
        infoHTML += `<div class="col-12"><h6 class="mb-2"><i class="bi bi-info-circle"></i> ${lang['exif-details'] || 'EXIF情報'}</h6></div>`;

        // EXIF撮影日時の表示
        if (media.exif_datetime) {
            infoHTML += `
                <div class="col-md-6">
                    <small><strong><i class="bi bi-camera-fill"></i> ${lang['exif-datetime'] || '撮影日時'}:</strong></small><br>
                    <small>${formatDate(media.exif_datetime)}</small>
                </div>
            `;
        }

        // EXIF位置情報の表示
        if (media.exif_latitude && media.exif_longitude) {
            // 緯度・経度を数値に変換（文字列として保存されている場合があるため）
            const lat = parseFloat(media.exif_latitude);
            const lng = parseFloat(media.exif_longitude);

            const mapLink = `https://www.google.com/maps?q=${lat},${lng}`;
            let locationDisplay = '';

            // 位置情報名がある場合は表示
            if (media.exif_location_name) {
                locationDisplay = `${escapeHtml(media.exif_location_name)}<br>`;
            }

            locationDisplay += `
                <a href="${mapLink}" target="_blank" rel="noopener noreferrer" class="text-decoration-none">
                    📍 ${lat.toFixed(6)}, ${lng.toFixed(6)}
                    <i class="bi bi-box-arrow-up-right small"></i>
                </a>
            `;

            infoHTML += `
                <div class="col-md-6">
                    <small><strong><i class="bi bi-geo-alt-fill"></i> ${lang['exif-location'] || '位置情報'}:</strong></small><br>
                    <small>${locationDisplay}</small>
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
                <i class="bi bi-file-earmark"></i> ${lang['modal-filename']} ${escapeHtml(media.filename)}
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
 * @param {number} mediaId - メディアID
 * @param {string} filename - ファイル名
 */
function deleteMedia(mediaId, filename) {
    if (!confirm(`「${filename}」を削除してもよろしいですか？\nこの操作は取り消せません。`)) {
        return;
    }

    // 削除フォームを作成して送信
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'delete.php';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'media_id';
    input.value = mediaId;

    form.appendChild(input);
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
                alert(`${file.name} の変換に失敗しました。元のファイルをアップロードします。`);
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
