/**
 * KidSnaps Growth Album - ZIPアップロード処理
 */

// グローバル変数
let selectedZipFile = null;
let uploadAborted = false;
let fileIdentifier = null;
let previewCompleted = false;
let isExistingFile = false; // 既存ファイルかどうかのフラグ

// ページ読み込み時に既存ZIPファイルをチェック
document.addEventListener('DOMContentLoaded', async function() {
    await loadExistingZipFiles();

    // 人物フィルター入力欄の変更を監視
    document.getElementById('peopleFilter').addEventListener('input', function() {
        // フィルターが変更されたらプレビュー状態をリセット
        if (previewCompleted) {
            previewCompleted = false;
            document.getElementById('uploadBtn').disabled = true;
            document.getElementById('previewBtn').disabled = false;
        }
    });
});

// ファイル選択時の処理
document.getElementById('zipFile').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const fileLabel = document.getElementById('zipFileLabel');

    if (file) {
        selectedZipFile = file;
        isExistingFile = false; // 新規ファイルなのでフラグをリセット

        // プレビュー状態をリセット
        fileIdentifier = null;
        previewCompleted = false;
        document.getElementById('previewResult').classList.add('d-none');
        document.getElementById('uploadBtn').disabled = true;
        document.getElementById('previewBtn').disabled = false;

        // ファイル名を表示
        const fileName = file.name;
        const fileSize = formatFileSize(file.size);
        fileLabel.innerHTML = `<i class="bi bi-file-earmark-zip"></i> ${fileName} (${fileSize})`;

        // ZIPファイルかチェック
        if (!fileName.toLowerCase().endsWith('.zip')) {
            alert('ZIPファイルを選択してください。');
            e.target.value = '';
            selectedZipFile = null;
            fileLabel.innerHTML = '<i class="bi bi-cloud-upload"></i> クリックしてZIPファイルを選択';
            return;
        }

        // ファイルサイズチェック（5GB）
        const maxSize = 5 * 1024 * 1024 * 1024;
        if (file.size > maxSize) {
            alert('ZIPファイルサイズは5GB以下にしてください。');
            e.target.value = '';
            selectedZipFile = null;
            fileLabel.innerHTML = '<i class="bi bi-cloud-upload"></i> クリックしてZIPファイルを選択';
            return;
        }

        console.log('ZIPファイル選択:', fileName, fileSize);
    } else {
        selectedZipFile = null;
        fileLabel.innerHTML = '<i class="bi bi-cloud-upload"></i> クリックしてZIPファイルを選択';
    }
});

// プレビューボタン
document.getElementById('previewBtn').addEventListener('click', async function(e) {
    e.preventDefault(); // フォーム送信を防止
    e.stopPropagation(); // イベント伝播を停止

    // 既存ファイルの場合と新規アップロードの場合で処理を分岐
    if (!isExistingFile && !selectedZipFile) {
        alert('ZIPファイルを選択してください。');
        return;
    }

    if (isExistingFile && !fileIdentifier) {
        alert('アップロード済みのZIPファイルを選択してください。');
        return;
    }

    const previewBtn = document.getElementById('previewBtn');
    const uploadBtn = document.getElementById('uploadBtn');
    const peopleFilter = document.getElementById('peopleFilter').value.trim();

    // ボタンを無効化
    previewBtn.disabled = true;
    uploadBtn.disabled = true;

    // 進捗表示
    const progressContainer = document.getElementById('uploadProgress');
    const progressBar = progressContainer.querySelector('.progress-bar');
    const progressText = document.getElementById('progressText');

    progressContainer.classList.remove('d-none');
    progressBar.style.width = '0%';
    progressBar.textContent = '0%';
    progressText.classList.remove('d-none');

    try {
        // 既存ファイルの場合はアップロードをスキップ
        if (!isExistingFile) {
            console.log('ZIPプレビュー開始:', selectedZipFile.name);
            progressText.textContent = 'ZIPファイルをアップロード中...';

            // チャンク分割アップロード
            fileIdentifier = await uploadZipFileInChunks(
                selectedZipFile,
                (progress) => {
                    const uploadProgress = Math.floor(progress * 50);
                    progressBar.style.width = uploadProgress + '%';
                    progressBar.textContent = uploadProgress + '%';
                }
            );

            if (uploadAborted) {
                throw new Error('アップロードがキャンセルされました。');
            }

            console.log('ZIPアップロード完了。プレビュー生成中...', 'fileIdentifier:', fileIdentifier);
        } else {
            console.log('既存ZIPファイルのプレビュー開始', 'fileIdentifier:', fileIdentifier);
            progressBar.style.width = '10%';
            progressBar.textContent = '10%';
        }

        progressText.textContent = 'ZIPファイルを展開してプレビュー生成中...';
        progressBar.style.width = '50%';
        progressBar.textContent = '50%';

        // プレビュー処理を呼び出し
        const formData = new FormData();
        formData.append('fileIdentifier', fileIdentifier);
        formData.append('peopleFilter', peopleFilter);

        console.log('プレビューAPI呼び出し開始...');
        const previewResponse = await fetch('lib/zip_preview.php', {
            method: 'POST',
            body: formData
        });

        console.log('プレビューAPIレスポンス受信:', previewResponse.status, previewResponse.statusText);

        // レスポンスのテキストを取得してログ出力
        const responseText = await previewResponse.text();
        console.log('プレビューAPIレスポンステキスト:', responseText.substring(0, 500));

        // JSONとしてパース
        let previewResult;
        try {
            previewResult = JSON.parse(responseText);
        } catch (jsonError) {
            console.error('JSONパースエラー:', jsonError);
            console.error('レスポンステキスト:', responseText);
            throw new Error('プレビューAPIのレスポンスが不正です: ' + responseText.substring(0, 100));
        }

        if (!previewResult.success) {
            throw new Error(previewResult.error || 'プレビュー生成に失敗しました。');
        }

        console.log('プレビュー生成完了:', previewResult);

        // プレビュー結果を表示
        displayPreviewResult(previewResult);
        previewCompleted = true;

        // 完了表示
        progressBar.style.width = '100%';
        progressBar.textContent = '100%';
        progressBar.classList.remove('progress-bar-animated');
        progressBar.classList.add('bg-success');
        progressText.textContent = 'プレビュー完了！';

        // インポートボタンとプレビューボタンを有効化
        uploadBtn.disabled = false;
        previewBtn.disabled = false;

        // 2秒後に進捗バーを非表示
        setTimeout(() => {
            progressContainer.classList.add('d-none');
            progressBar.classList.remove('bg-success');
            progressBar.classList.add('progress-bar-animated');
            progressText.classList.add('d-none');
        }, 2000);

    } catch (error) {
        console.error('プレビューエラー:', error);
        console.error('エラースタック:', error.stack);

        progressBar.classList.remove('progress-bar-animated');
        progressBar.classList.add('bg-danger');
        progressText.textContent = 'エラー: ' + error.message;
        progressText.classList.remove('d-none');

        previewBtn.disabled = false;

        // エラーメッセージをアラート表示
        alert('プレビュー生成エラー:\n' + error.message + '\n\nブラウザのコンソール(F12)で詳細を確認してください。');

        // エラーメッセージを長めに表示
        setTimeout(() => {
            progressContainer.classList.add('d-none');
            progressBar.classList.remove('bg-danger');
            progressBar.classList.add('progress-bar-animated');
            progressText.classList.add('d-none');
        }, 10000);
    }
});

// アップロードフォーム送信
document.getElementById('zipUploadForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    // 既存ファイルの場合と新規アップロードの場合で処理を分岐
    if (!isExistingFile && !selectedZipFile) {
        alert('ZIPファイルを選択してください。');
        return;
    }

    if (!fileIdentifier) {
        alert('ファイルが選択されていません。');
        return;
    }

    if (!previewCompleted) {
        alert('まずプレビューを実行してください。');
        return;
    }

    const albumTitle = document.getElementById('albumTitle').value.trim();
    const albumDescription = document.getElementById('albumDescription').value.trim();
    const peopleFilter = document.getElementById('peopleFilter').value.trim();

    // アップロードボタンを無効化
    const uploadBtn = document.getElementById('uploadBtn');
    const previewBtn = document.getElementById('previewBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    uploadBtn.disabled = true;
    previewBtn.disabled = true;
    uploadAborted = false;

    // 進捗表示
    const progressContainer = document.getElementById('uploadProgress');
    const progressBar = progressContainer.querySelector('.progress-bar');
    const progressText = document.getElementById('progressText');
    const currentFileText = document.getElementById('currentFileText');

    progressContainer.classList.remove('d-none');
    progressBar.style.width = '0%';
    progressBar.textContent = '0%';
    progressText.textContent = 'インポート処理を開始中...';
    progressText.classList.remove('d-none');
    currentFileText.textContent = '';
    currentFileText.classList.remove('d-none');

    try {
        // ファイル名を取得（既存ファイルの場合はfileIdentifierから生成）
        const zipFileName = selectedZipFile ? selectedZipFile.name : fileIdentifier;
        console.log('ZIPインポート開始:', zipFileName);

        // ZIPインポート処理を呼び出し
        const formData = new FormData();
        formData.append('fileIdentifier', fileIdentifier);
        formData.append('albumTitle', albumTitle);
        formData.append('albumDescription', albumDescription);
        formData.append('peopleFilter', peopleFilter);

        // 進捗ポーリング用のタイマー
        let progressCheckInterval = null;

        // インポートAPIを非同期で呼び出し
        const importPromise = fetch('lib/zip_import.php', {
            method: 'POST',
            body: formData
        });

        // インポート処理と並行して進捗をチェック（将来のバックグラウンド処理対応）
        // 現在は同期処理なので効果は限定的ですが、将来的には有用
        progressCheckInterval = setInterval(async () => {
            try {
                // 注: historyIdは現在取得できないため、将来の実装用のプレースホルダー
                // バックグラウンド処理実装時にhistoryIdを使用して進捗を取得
                console.log('進捗チェック中...（現在は同期処理のため更新なし）');
            } catch (progressError) {
                console.error('進捗チェックエラー:', progressError);
            }
        }, 2000); // 2秒ごと

        const importResponse = await importPromise;

        // 進捗チェックを停止
        if (progressCheckInterval) {
            clearInterval(progressCheckInterval);
            progressCheckInterval = null;
        }

        // レスポンステキストを取得してログ出力
        const importResponseText = await importResponse.text();
        console.log('インポートAPIレスポンステキスト:', importResponseText.substring(0, 500));

        // JSONパース
        let importResult;
        try {
            importResult = JSON.parse(importResponseText);
        } catch (jsonError) {
            console.error('JSONパースエラー:', jsonError);
            console.error('レスポンステキスト:', importResponseText);
            throw new Error('インポートAPIのレスポンスが不正です: ' + importResponseText.substring(0, 100));
        }

        if (!importResult.success) {
            throw new Error(importResult.error || 'ZIPインポートに失敗しました。');
        }

        console.log('ZIPインポート完了:', importResult);

        // 完了表示
        progressBar.style.width = '100%';
        progressBar.textContent = '100%';
        progressBar.classList.remove('progress-bar-animated');
        progressBar.classList.add('bg-success');
        progressText.textContent = `インポート完了！ ${importResult.importedCount}件のメディアを追加しました。`;

        if (importResult.failedCount > 0) {
            currentFileText.textContent = `※ ${importResult.failedCount}件のファイルをスキップしました。`;
        }

        // 3秒後にアルバム詳細ページへリダイレクト
        setTimeout(() => {
            window.location.href = `album_detail.php?id=${importResult.albumId}`;
        }, 3000);

    } catch (error) {
        console.error('アップロードエラー:', error);

        // 進捗チェックを停止（エラー時）
        if (typeof progressCheckInterval !== 'undefined' && progressCheckInterval) {
            clearInterval(progressCheckInterval);
            progressCheckInterval = null;
        }

        progressBar.classList.remove('progress-bar-animated');
        progressBar.classList.add('bg-danger');
        progressText.textContent = 'エラー: ' + error.message;

        uploadBtn.disabled = false;

        setTimeout(() => {
            progressContainer.classList.add('d-none');
            progressBar.classList.remove('bg-danger');
            progressBar.classList.add('progress-bar-animated');
        }, 5000);
    }
});

/**
 * ZIPファイルをチャンク分割してアップロード
 */
async function uploadZipFileInChunks(file, progressCallback) {
    const chunkSize = 5 * 1024 * 1024; // 5MB
    const totalChunks = Math.ceil(file.size / chunkSize);
    const fileIdentifier = generateFileIdentifier(file);

    console.log(`チャンク数: ${totalChunks}, 識別子: ${fileIdentifier}`);

    for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
        if (uploadAborted) {
            throw new Error('アップロードがキャンセルされました。');
        }

        const start = chunkIndex * chunkSize;
        const end = Math.min(start + chunkSize, file.size);
        const chunk = file.slice(start, end);

        const formData = new FormData();
        formData.append('chunk', chunk);
        formData.append('chunkIndex', chunkIndex);
        formData.append('totalChunks', totalChunks);
        formData.append('fileName', file.name);
        formData.append('fileIdentifier', fileIdentifier);

        console.log(`チャンク ${chunkIndex + 1}/${totalChunks} アップロード中...`);

        const response = await fetch('lib/chunk_upload.php', {
            method: 'POST',
            body: formData
        });

        // レスポンステキストを取得してログ出力
        const responseText = await response.text();
        console.log(`チャンク ${chunkIndex + 1} APIレスポンステキスト:`, responseText.substring(0, 200));

        // JSONパース
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (jsonError) {
            console.error('チャンクアップロードJSONパースエラー:', jsonError);
            console.error('レスポンステキスト:', responseText);
            throw new Error('チャンクアップロードAPIのレスポンスが不正です: ' + responseText.substring(0, 100));
        }

        if (!result.success) {
            throw new Error(result.error || 'チャンクアップロードに失敗しました。');
        }

        // 進捗通知
        const progress = (chunkIndex + 1) / totalChunks;
        progressCallback(progress);

        console.log(`チャンク ${chunkIndex + 1}/${totalChunks} 完了`);
    }

    console.log('全チャンクのアップロード完了');
    return fileIdentifier;
}

/**
 * ファイル識別子を生成
 */
function generateFileIdentifier(file) {
    return 'zip_' + Date.now() + '_' + Math.random().toString(36).substring(2, 15);
}

/**
 * ファイルサイズをフォーマット
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

/**
 * キャンセルボタン
 */
document.getElementById('cancelBtn').addEventListener('click', function() {
    if (confirm('アップロードをキャンセルしますか？')) {
        uploadAborted = true;
        location.reload();
    }
});

/**
 * プレビュー結果を表示
 */
function displayPreviewResult(result) {
    const previewResult = document.getElementById('previewResult');
    const previewSummary = document.getElementById('previewSummary');
    const peopleList = document.getElementById('peopleList');
    const matchedFilesList = document.getElementById('matchedFilesList');
    const filteredFilesList = document.getElementById('filteredFilesList');
    const noMetadataFilesList = document.getElementById('noMetadataFilesList');
    const peopleBadge = document.getElementById('peopleBadge');
    const matchedBadge = document.getElementById('matchedBadge');
    const filteredBadge = document.getElementById('filteredBadge');
    const noMetadataBadge = document.getElementById('noMetadataBadge');
    const noMetadataTabItem = document.getElementById('noMetadataTabItem');
    const peopleTabItem = document.getElementById('peopleTabItem');

    // サマリーを表示
    let summaryHtml = '<div class="alert alert-info mb-0">';
    summaryHtml += '<h6><i class="bi bi-info-circle"></i> プレビュー結果</h6>';
    summaryHtml += `<p class="mb-1">全体: <strong>${result.total_files}件</strong>のメディアファイル</p>`;

    if (result.people_count > 0) {
        summaryHtml += `<p class="mb-1">検出された人物: <strong class="text-primary">${result.people_count}名</strong></p>`;
    }

    summaryHtml += `<p class="mb-1">インポート対象: <strong class="text-success">${result.matched_count}件</strong></p>`;

    if (result.has_people_filter) {
        summaryHtml += `<p class="mb-1">除外: <strong class="text-warning">${result.filtered_count}件</strong> (人物フィルタ: ${result.people_filter.join(', ')})</p>`;
        if (result.no_metadata_count > 0) {
            summaryHtml += `<p class="mb-0">メタデータなし: <strong class="text-secondary">${result.no_metadata_count}件</strong> (人物情報がないためフィルタ判定不可)</p>`;
        }
    }

    summaryHtml += '</div>';
    previewSummary.innerHTML = summaryHtml;

    // バッジを更新
    peopleBadge.textContent = result.people_count;
    matchedBadge.textContent = result.matched_count;
    filteredBadge.textContent = result.filtered_count;
    noMetadataBadge.textContent = result.no_metadata_count;

    // 人物タブの表示/非表示
    if (result.people_count > 0) {
        peopleTabItem.style.display = 'block';
    } else {
        peopleTabItem.style.display = 'none';
    }

    // メタデータなしタブの表示/非表示
    if (result.no_metadata_count > 0 && result.has_people_filter) {
        noMetadataTabItem.style.display = 'block';
    } else {
        noMetadataTabItem.style.display = 'none';
    }

    // 人物一覧を表示
    peopleList.innerHTML = '';
    if (result.people_stats && result.people_stats.length === 0) {
        peopleList.innerHTML = '<div class="alert alert-info mb-0">Google Photosの人物情報が見つかりませんでした。</div>';
    } else if (result.people_stats && result.people_stats.length > 0) {
        result.people_stats.forEach((person, index) => {
            const item = createPeopleListItem(person, index + 1);
            peopleList.appendChild(item);
        });
    }

    // インポート対象ファイルリストを表示
    matchedFilesList.innerHTML = '';
    if (result.matched_files.length === 0) {
        matchedFilesList.innerHTML = '<div class="alert alert-warning mb-0">インポート対象のファイルがありません。</div>';
    } else {
        result.matched_files.forEach((file, index) => {
            const item = createFileListItem(file, 'success', index + 1);
            matchedFilesList.appendChild(item);
        });
    }

    // 除外ファイルリストを表示
    filteredFilesList.innerHTML = '';
    if (result.filtered_files.length === 0) {
        filteredFilesList.innerHTML = '<div class="alert alert-info mb-0">除外されるファイルはありません。</div>';
    } else {
        result.filtered_files.forEach((file, index) => {
            const item = createFileListItem(file, 'warning', index + 1);
            filteredFilesList.appendChild(item);
        });
    }

    // メタデータなしファイルリストを表示
    noMetadataFilesList.innerHTML = '';
    if (result.files_without_metadata.length === 0) {
        noMetadataFilesList.innerHTML = '<div class="alert alert-info mb-0">該当するファイルはありません。</div>';
    } else {
        result.files_without_metadata.forEach((file, index) => {
            const item = createFileListItem(file, 'secondary', index + 1);
            noMetadataFilesList.appendChild(item);
        });
    }

    // プレビュー結果を表示
    previewResult.classList.remove('d-none');

    // プレビュー結果までスクロール
    previewResult.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

/**
 * 人物リストアイテムを作成
 */
function createPeopleListItem(person, index) {
    const div = document.createElement('div');
    div.className = 'list-group-item list-group-item-action';
    div.style.cursor = 'pointer';

    let html = '<div class="d-flex justify-content-between align-items-center">';
    html += '<div class="flex-grow-1">';
    html += `<div class="fw-bold"><i class="bi bi-person-circle"></i> ${escapeHtml(person.name)}</div>`;
    html += `<small class="text-muted">${person.count}枚の写真に写っています</small>`;
    html += '</div>';
    html += '<button class="btn btn-sm btn-outline-primary" type="button">';
    html += '<i class="bi bi-plus-circle"></i> フィルターに追加';
    html += '</button>';
    html += '</div>';

    // 写真ファイルリストを折りたたみ表示（最初の5件）
    if (person.files && person.files.length > 0) {
        const displayFiles = person.files.slice(0, 5);
        html += '<div class="mt-2 small text-muted">';
        html += '<i class="bi bi-images"></i> 含まれるファイル: ';
        html += displayFiles.map(f => escapeHtml(f)).join(', ');
        if (person.files.length > 5) {
            html += ` ... 他${person.files.length - 5}件`;
        }
        html += '</div>';
    }

    div.innerHTML = html;

    // フィルターに追加ボタンのイベントリスナー
    const addButton = div.querySelector('button');
    addButton.addEventListener('click', function(e) {
        e.stopPropagation();
        addPersonToFilter(person.name);
    });

    return div;
}

/**
 * 人物をフィルターに追加
 */
function addPersonToFilter(personName) {
    const peopleFilterInput = document.getElementById('peopleFilter');
    const currentValue = peopleFilterInput.value.trim();

    // 既に含まれているかチェック
    const currentPeople = currentValue ? currentValue.split(',').map(p => p.trim()) : [];
    if (currentPeople.includes(personName)) {
        alert(`"${personName}" は既にフィルターに含まれています。`);
        return;
    }

    // フィルターに追加
    const newValue = currentValue ? `${currentValue}, ${personName}` : personName;
    peopleFilterInput.value = newValue;

    // プレビュー状態をリセット
    previewCompleted = false;
    document.getElementById('uploadBtn').disabled = true;
    document.getElementById('previewBtn').disabled = false;

    // フィルター入力欄までスクロール
    peopleFilterInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
    peopleFilterInput.focus();

    // 一時的にハイライト
    peopleFilterInput.classList.add('border-primary');
    setTimeout(() => {
        peopleFilterInput.classList.remove('border-primary');
    }, 2000);

    alert(`"${personName}" をフィルターに追加しました。プレビューボタンを再度クリックして結果を確認してください。`);
}

/**
 * ファイルリストアイテムを作成
 */
function createFileListItem(file, type, index) {
    const div = document.createElement('div');
    div.className = 'list-group-item';

    let html = '<div class="d-flex justify-content-between align-items-start">';
    html += '<div class="flex-grow-1">';
    html += `<div class="fw-bold">${index}. ${escapeHtml(file.filename)}</div>`;
    html += `<small class="text-muted">サイズ: ${file.size_formatted}</small>`;

    if (file.has_metadata) {
        html += ' <span class="badge bg-info">メタデータあり</span>';
    }

    if (file.people && file.people.length > 0) {
        html += '<br><small><i class="bi bi-people"></i> 人物: ' + file.people.map(p => escapeHtml(p)).join(', ') + '</small>';
    }

    if (file.datetime) {
        html += `<br><small><i class="bi bi-calendar"></i> 撮影日時: ${file.datetime}</small>`;
    }

    if (file.has_location) {
        html += '<br><small><i class="bi bi-geo-alt"></i> 位置情報あり</small>';
    }

    html += '</div>';

    // タイプに応じたバッジ
    if (type === 'success') {
        html += '<span class="badge bg-success">インポート</span>';
    } else if (type === 'warning') {
        html += '<span class="badge bg-warning text-dark">除外</span>';
    } else if (type === 'secondary') {
        html += '<span class="badge bg-secondary">メタデータなし</span>';
    }

    html += '</div>';

    div.innerHTML = html;
    return div;
}

/**
 * HTMLエスケープ
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * 既存のアップロード済みZIPファイルを読み込む
 */
async function loadExistingZipFiles() {
    try {
        const response = await fetch('lib/list_uploaded_zips.php');
        const result = await response.json();

        if (!result.success) {
            console.error('既存ZIPファイルの取得に失敗:', result.error);
            return;
        }

        // アップロード中のファイル表示
        if (result.uploading && result.uploading.count > 0) {
            displayExistingZipFiles(result.uploading.files);
        }

        // インポート済みファイル表示
        if (result.imported && result.imported.count > 0) {
            displayImportedZipFiles(result.imported.files);
        }
    } catch (error) {
        console.error('既存ZIPファイルの読み込みエラー:', error);
    }
}

/**
 * 既存のZIPファイルを表示
 */
function displayExistingZipFiles(files) {
    const existingZipsSection = document.getElementById('existingZipsSection');
    const existingZipsListGroup = document.getElementById('existingZipsListGroup');
    const toggleButton = document.getElementById('toggleExistingZips');
    const existingZipsList = document.getElementById('existingZipsList');

    // ファイルがない場合は非表示
    if (files.length === 0) {
        existingZipsSection.classList.add('d-none');
        return;
    }

    // セクションを表示
    existingZipsSection.classList.remove('d-none');

    // リストをクリア
    existingZipsListGroup.innerHTML = '';

    // 各ファイルをリストに追加
    files.forEach((file, index) => {
        const item = document.createElement('div');
        item.className = 'list-group-item list-group-item-action';
        item.style.cursor = 'pointer';

        let html = '<div class="d-flex justify-content-between align-items-center">';
        html += '<div class="flex-grow-1">';
        html += `<div class="fw-bold"><i class="bi bi-file-earmark-zip"></i> ${escapeHtml(file.fileName)}</div>`;
        html += `<small class="text-muted">サイズ: ${file.fileSizeFormatted}`;
        if (file.uploadedAt) {
            html += ` | アップロード: ${file.uploadedAt}`;
        }
        if (file.hasExtractDir) {
            html += ' <span class="badge bg-info">展開済み</span>';
        }
        html += '</small>';
        html += '</div>';
        html += '<button class="btn btn-sm btn-primary" type="button">';
        html += '<i class="bi bi-eye"></i> このファイルをプレビュー';
        html += '</button>';
        html += '</div>';

        item.innerHTML = html;

        // クリックイベント
        const button = item.querySelector('button');
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            selectExistingZipFile(file);
        });

        existingZipsListGroup.appendChild(item);
    });

    // トグルボタンのイベント
    toggleButton.addEventListener('click', function() {
        existingZipsList.classList.toggle('d-none');
        const icon = this.querySelector('i');
        if (existingZipsList.classList.contains('d-none')) {
            icon.className = 'bi bi-chevron-down';
            this.innerHTML = '表示 <i class="bi bi-chevron-down"></i>';
        } else {
            icon.className = 'bi bi-chevron-up';
            this.innerHTML = '非表示 <i class="bi bi-chevron-up"></i>';
        }
    });
}

/**
 * インポート済みZIPファイルを表示
 */
function displayImportedZipFiles(files) {
    const existingZipsSection = document.getElementById('existingZipsSection');
    const existingZipsListGroup = document.getElementById('existingZipsListGroup');

    // ファイルがない場合はスキップ
    if (files.length === 0) {
        return;
    }

    // セクションを表示
    existingZipsSection.classList.remove('d-none');

    // 「インポート履歴」見出しを追加
    const historyHeader = document.createElement('div');
    historyHeader.className = 'list-group-item bg-light';
    historyHeader.innerHTML = '<h6 class="mb-0"><i class="bi bi-clock-history"></i> インポート履歴</h6>';
    existingZipsListGroup.appendChild(historyHeader);

    // 各履歴をリストに追加
    files.forEach((file, index) => {
        const item = document.createElement('div');
        item.className = 'list-group-item';

        let statusBadge = '';
        if (file.status === 'completed') {
            statusBadge = '<span class="badge bg-success">完了</span>';
        } else if (file.status === 'failed') {
            statusBadge = '<span class="badge bg-danger">失敗</span>';
        } else {
            statusBadge = '<span class="badge bg-warning">処理中</span>';
        }

        let html = '<div class="d-flex justify-content-between align-items-start">';
        html += '<div class="flex-grow-1">';
        html += `<div class="fw-bold"><i class="bi bi-file-earmark-zip"></i> ${escapeHtml(file.fileName)} ${statusBadge}</div>`;
        html += `<small class="text-muted">`;
        html += `インポート件数: ${file.importedFiles}件`;
        if (file.failedFiles > 0) {
            html += ` | 失敗: ${file.failedFiles}件`;
        }
        if (file.importCompletedAt) {
            const date = new Date(file.importCompletedAt);
            html += ` | 完了: ${date.toLocaleString('ja-JP')}`;
        }
        html += `</small>`;

        // アルバム情報があれば表示
        if (file.albumId && file.albumTitle) {
            html += `<div class="mt-2">`;
            html += `<a href="album_detail.php?id=${file.albumId}" class="btn btn-sm btn-outline-primary">`;
            html += `<i class="bi bi-images"></i> ${escapeHtml(file.albumTitle)} を開く`;
            html += `</a>`;
            html += `</div>`;
        }

        html += '</div>';
        html += '</div>';

        item.innerHTML = html;
        existingZipsListGroup.appendChild(item);
    });
}

/**
 * 既存のZIPファイルを選択
 */
function selectExistingZipFile(file) {
    console.log('既存ZIPファイルを選択:', file.fileName);

    // グローバル変数に設定
    fileIdentifier = file.fileIdentifier;
    isExistingFile = true;
    selectedZipFile = null; // 新規アップロードではないのでnull

    // ファイル選択表示を更新
    const fileLabel = document.getElementById('zipFileLabel');
    fileLabel.innerHTML = `<i class="bi bi-file-earmark-zip text-success"></i> ${escapeHtml(file.fileName)} (${file.fileSizeFormatted}) <span class="badge bg-success">アップロード済み</span>`;

    // プレビューボタンを有効化
    document.getElementById('previewBtn').disabled = false;
    document.getElementById('uploadBtn').disabled = true;

    // プレビュー結果をリセット
    previewCompleted = false;
    document.getElementById('previewResult').classList.add('d-none');

    // アルバムタイトルにファイル名を設定（拡張子を除く）
    const albumTitle = document.getElementById('albumTitle');
    if (!albumTitle.value) {
        const fileNameWithoutExt = file.fileName.replace(/\.zip$/i, '');
        albumTitle.value = fileNameWithoutExt;
    }

    // 既存ZIPリストを折りたたむ
    const existingZipsList = document.getElementById('existingZipsList');
    existingZipsList.classList.add('d-none');
    const toggleButton = document.getElementById('toggleExistingZips');
    toggleButton.innerHTML = '表示 <i class="bi bi-chevron-down"></i>';

    // スクロール
    fileLabel.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
