/**
 * 重複チェック機能
 * ファイル選択時にMD5ハッシュを計算し、サーバーに送信して重複をチェックします
 */

// SparkMD5ライブラリ（軽量なMD5実装）をCDNから読み込みます
// CDN: https://cdnjs.cloudflare.com/ajax/libs/spark-md5/3.0.2/spark-md5.min.js

/**
 * ファイルのMD5ハッシュを計算
 * @param {File} file - 計算対象のファイル
 * @param {Function} progressCallback - 進捗コールバック (0-100)
 * @returns {Promise<string>} MD5ハッシュ値
 */
async function calculateFileMD5(file, progressCallback) {
    return new Promise((resolve, reject) => {
        const chunkSize = 2097152; // 2MB
        const chunks = Math.ceil(file.size / chunkSize);
        let currentChunk = 0;
        const spark = new SparkMD5.ArrayBuffer();
        const fileReader = new FileReader();

        fileReader.onload = function(e) {
            spark.append(e.target.result);
            currentChunk++;

            if (progressCallback) {
                const progress = Math.round((currentChunk / chunks) * 100);
                progressCallback(progress);
            }

            if (currentChunk < chunks) {
                loadNext();
            } else {
                resolve(spark.end());
            }
        };

        fileReader.onerror = function() {
            reject(new Error('ファイルの読み込みに失敗しました'));
        };

        function loadNext() {
            const start = currentChunk * chunkSize;
            const end = Math.min(start + chunkSize, file.size);
            fileReader.readAsArrayBuffer(file.slice(start, end));
        }

        loadNext();
    });
}

/**
 * サーバーに重複チェックをリクエスト
 * @param {string} hash - MD5ハッシュ値
 * @param {string} filename - ファイル名
 * @param {number} filesize - ファイルサイズ
 * @returns {Promise<Object>} チェック結果
 */
async function checkDuplicate(hash, filename, filesize) {
    const response = await fetch('api/check_duplicate.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            hash: hash,
            filename: filename,
            filesize: filesize
        })
    });

    if (!response.ok) {
        throw new Error('重複チェックに失敗しました');
    }

    return await response.json();
}

/**
 * 複数のファイルの重複をチェック
 * @param {FileList} files - チェック対象のファイルリスト
 * @param {Function} progressCallback - 進捗コールバック
 * @returns {Promise<Array>} チェック結果の配列
 */
async function checkMultipleFiles(files, progressCallback) {
    const results = [];
    const totalFiles = files.length;

    for (let i = 0; i < totalFiles; i++) {
        const file = files[i];

        if (progressCallback) {
            progressCallback({
                current: i + 1,
                total: totalFiles,
                filename: file.name,
                status: 'calculating'
            });
        }

        try {
            // MD5ハッシュを計算
            const hash = await calculateFileMD5(file, (progress) => {
                if (progressCallback) {
                    progressCallback({
                        current: i + 1,
                        total: totalFiles,
                        filename: file.name,
                        status: 'calculating',
                        progress: progress
                    });
                }
            });

            if (progressCallback) {
                progressCallback({
                    current: i + 1,
                    total: totalFiles,
                    filename: file.name,
                    status: 'checking'
                });
            }

            // サーバーに重複チェック
            const result = await checkDuplicate(hash, file.name, file.size);

            results.push({
                file: file,
                hash: hash,
                isDuplicate: result.isDuplicate,
                existing: result.existing || null,
                count: result.count || 0
            });

        } catch (error) {
            console.error(`ファイル ${file.name} の処理に失敗:`, error);
            results.push({
                file: file,
                hash: null,
                isDuplicate: false,
                error: error.message
            });
        }
    }

    return results;
}

/**
 * 重複ファイルの警告を表示
 * @param {Array} duplicates - 重複ファイルの配列
 */
function showDuplicateWarning(duplicates) {
    if (duplicates.length === 0) return;

    // 翻訳を取得
    const lang = translations[currentLanguage];

    // 各重複ファイルのリストを作成
    const duplicateListHTML = duplicates.map(dup => {
        // 複数の既存ファイルを表示
        const existingFilesHTML = dup.existing && Array.isArray(dup.existing)
            ? dup.existing.map(ex => {
                const uploadDate = new Date(ex.upload_date).toLocaleDateString(currentLanguage === 'ja' ? 'ja-JP' : 'en-US');
                return `<div class="ms-3 small text-muted">→ ${ex.filename} (${lang['duplicate-upload-date']}: ${uploadDate})</div>`;
            }).join('')
            : '';

        const countText = dup.count > 0 ? ` <span class="badge bg-secondary">${lang['duplicate-count-suffix'].replace('{count}', dup.count)}</span>` : '';

        return `
            <li class="mb-2">
                <strong>${escapeHtml(dup.file.name)}</strong> ${lang['duplicate-already-uploaded']}${countText}
                ${existingFilesHTML}
            </li>
        `;
    }).join('');

    const warningDiv = document.createElement('div');
    warningDiv.className = 'alert alert-warning alert-dismissible fade show';
    warningDiv.innerHTML = `
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>${lang['duplicate-warning-title']}</strong>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <ul class="mt-2 mb-0">
            ${duplicateListHTML}
        </ul>
        <p class="mt-2 mb-0">
            <small>
                ${lang['duplicate-auto-excluded']}<br>
                ${lang['duplicate-reselect-hint']}
            </small>
        </p>
    `;

    // モーダルのボディに挿入
    const modalBody = document.querySelector('#uploadModal .modal-body');
    const existingWarning = modalBody.querySelector('.alert-warning');
    if (existingWarning) {
        existingWarning.remove();
    }
    modalBody.insertBefore(warningDiv, modalBody.firstChild);
}

/**
 * ファイル選択時の重複チェック処理を初期化
 */
function initDuplicateChecker() {
    const fileInput = document.getElementById('mediaFile');
    if (!fileInput) return;

    // SparkMD5ライブラリが読み込まれているかチェック
    if (typeof SparkMD5 === 'undefined') {
        console.warn('SparkMD5ライブラリが読み込まれていません。重複チェック機能は無効です。');
        return;
    }

    let checkInProgress = false;

    fileInput.addEventListener('change', async function(e) {
        const files = Array.from(e.target.files);
        if (files.length === 0 || checkInProgress) return;

        checkInProgress = true;

        // 翻訳を取得
        const lang = translations[currentLanguage];

        // 進捗表示用の要素を作成
        const progressDiv = document.createElement('div');
        progressDiv.id = 'duplicateCheckProgress';
        progressDiv.className = 'alert alert-info mt-3';
        progressDiv.innerHTML = `
            <div class="d-flex align-items-center">
                <div class="spinner-border spinner-border-sm me-2" role="status">
                    <span class="visually-hidden">${lang['duplicate-checking']}</span>
                </div>
                <div class="flex-grow-1">
                    <div id="duplicateCheckStatus">${lang['duplicate-checking']}</div>
                    <div class="progress mt-2" style="height: 5px;">
                        <div id="duplicateCheckProgressBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        `;

        const fileList = document.getElementById('fileList');
        fileList.appendChild(progressDiv);

        try {
            // 重複チェックを実行
            const results = await checkMultipleFiles(files, (status) => {
                const statusEl = document.getElementById('duplicateCheckStatus');
                const progressBar = document.getElementById('duplicateCheckProgressBar');

                if (statusEl) {
                    const progress = Math.round((status.current / status.total) * 100);
                    const statusText = lang['duplicate-check-status']
                        .replace('{filename}', status.filename)
                        .replace('{current}', status.current)
                        .replace('{total}', status.total);
                    statusEl.textContent = statusText;
                    progressBar.style.width = `${progress}%`;
                }
            });

            // 重複ファイルを抽出
            const duplicates = results.filter(r => r.isDuplicate);

            if (duplicates.length > 0) {
                // 重複ファイルの警告を表示
                showDuplicateWarning(duplicates);

                // 重複していないファイルのみをフィルタリング
                const nonDuplicateFiles = results
                    .filter(r => !r.isDuplicate)
                    .map(r => r.file);

                // FileListを再作成（DataTransferを使用）
                const dataTransfer = new DataTransfer();
                nonDuplicateFiles.forEach(file => dataTransfer.items.add(file));
                fileInput.files = dataTransfer.files;

                // ファイルリスト表示を更新
                updateFileListDisplay();
            }

        } catch (error) {
            console.error('重複チェックエラー:', error);
        } finally {
            // 進捗表示を削除
            const progressEl = document.getElementById('duplicateCheckProgress');
            if (progressEl) {
                progressEl.remove();
            }
            checkInProgress = false;
        }
    });
}

// ページ読み込み時に初期化
document.addEventListener('DOMContentLoaded', function() {
    initDuplicateChecker();
});
