/**
 * EXIF洗替機能 - JavaScript
 * 既存メディアファイルのEXIF情報を再抽出してデータベースを更新
 */

let isRefreshingExif = false;
let refreshExifAborted = false;

/**
 * EXIF洗替を開始
 */
async function startRefreshExif() {
    if (isRefreshingExif) {
        console.warn(t('refresh-exif-already-running'));
        return;
    }

    isRefreshingExif = true;
    refreshExifAborted = false;

    // UI更新
    document.getElementById('refreshExifStart').style.display = 'none';
    document.getElementById('refreshExifProgress').style.display = 'block';
    document.getElementById('refreshExifStartBtn').style.display = 'none';

    // キャンセルボタンは有効のまま（処理中でもキャンセルできるように）
    // ただし、data-bs-dismiss属性を削除（処理中はモーダルを閉じないように）
    const cancelBtn = document.getElementById('refreshExifCancelBtn');
    cancelBtn.removeAttribute('data-bs-dismiss');

    // 閉じるボタンは無効化
    document.getElementById('refreshExifCloseBtn').disabled = true;

    try {
        // ステップ1: 開始（総件数を取得）
        const startResponse = await fetch('api/refresh_exif.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=start'
        });

        if (!startResponse.ok) {
            throw new Error(t('refresh-exif-start-failed'));
        }

        const startData = await startResponse.json();

        if (!startData.success) {
            throw new Error(startData.error || t('refresh-exif-start-failed'));
        }

        const totalFiles = startData.total;
        document.getElementById('refreshExifTotal').textContent = totalFiles;

        if (totalFiles === 0) {
            alert(t('refresh-exif-no-files'));
            resetRefreshExifUI();
            return;
        }

        // ステップ2: ファイルリストを取得
        const listResponse = await fetch('api/refresh_exif.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=list'
        });

        if (!listResponse.ok) {
            throw new Error(t('refresh-exif-file-list-failed'));
        }

        const listData = await listResponse.json();

        if (!listData.success) {
            throw new Error(listData.error || t('refresh-exif-file-list-failed'));
        }

        const files = listData.files;
        let totalProcessed = 0;
        let totalUpdated = 0;
        let totalErrors = 0;
        const errorMessages = [];

        // ステップ3: 各ファイルを処理
        for (const fileInfo of files) {
            if (refreshExifAborted) break;

            totalProcessed++;

            try {
                let latitude = null;
                let longitude = null;

                if (fileInfo.file_type === 'image') {
                    // 画像ファイルの処理
                    // ファイルをBlobとして取得
                    const fileBlob = await fetchFileAsBlob(fileInfo.file_path);

                    if (!fileBlob) {
                        totalErrors++;
                        errorMessages.push(`ファイル取得失敗: ${fileInfo.filename}`);
                        continue;
                    }

                    // EXIF情報を抽出（JavaScript側で）
                    const exifData = await readExifFromFileBlob(fileBlob);

                    // GPS座標を10進数に変換
                    if (exifData && exifData.GPSLatitude && exifData.GPSLongitude) {
                        latitude = convertDMSToDD(exifData.GPSLatitude, exifData.GPSLatitudeRef);
                        longitude = convertDMSToDD(exifData.GPSLongitude, exifData.GPSLongitudeRef);
                    }

                    // 撮影日時を取得
                    let datetime = null;
                    if (exifData) {
                        datetime = exifData.DateTimeOriginal || exifData.DateTime || exifData.DateTimeDigitized;
                    }

                    // サーバーに送信してデータベースを更新
                    const updateResponse = await fetch('api/refresh_exif.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'update',
                            file_id: fileInfo.id,
                            exif_data: {
                                datetime: datetime,
                                latitude: latitude,
                                longitude: longitude,
                                camera_make: exifData ? exifData.Make : null,
                                camera_model: exifData ? exifData.Model : null,
                                orientation: exifData ? exifData.Orientation : 1
                            }
                        })
                    });

                    if (!updateResponse.ok) {
                        throw new Error('更新リクエストが失敗しました');
                    }

                    const updateData = await updateResponse.json();

                    if (updateData.success) {
                        totalUpdated++;
                    } else {
                        totalErrors++;
                        errorMessages.push(`更新失敗: ${fileInfo.filename}`);
                    }

                } else if (fileInfo.file_type === 'video') {
                    // 動画ファイルの処理（サーバー側でメタデータ抽出）
                    const updateResponse = await fetch('api/refresh_exif.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'update_video',
                            file_id: fileInfo.id,
                            file_path: fileInfo.file_path
                        })
                    });

                    if (!updateResponse.ok) {
                        throw new Error('更新リクエストが失敗しました');
                    }

                    const updateData = await updateResponse.json();

                    if (updateData.success) {
                        totalUpdated++;
                        // サーバーから返されたGPS情報を使用
                        latitude = updateData.latitude;
                        longitude = updateData.longitude;
                    } else {
                        totalErrors++;
                        errorMessages.push(`更新失敗: ${fileInfo.filename}`);
                    }
                }

                // 位置情報がある場合はリバースジオコーディング
                if (latitude !== null && longitude !== null) {
                    try {
                        const locationName = await reverseGeocode(latitude, longitude);
                        if (locationName) {
                            await updateLocationName(fileInfo.id, locationName);
                        }
                    } catch (geoError) {
                        console.warn('リバースジオコーディング失敗:', geoError);
                    }
                    // レート制限対策
                    await sleep(1000);
                }

            } catch (error) {
                totalErrors++;
                errorMessages.push(`${fileInfo.filename}: ${error.message}`);
                console.error('ファイル処理エラー:', error);
            }

            // UI更新
            updateRefreshExifProgress(totalProcessed, totalUpdated, totalErrors, totalFiles, errorMessages);

            // 短い待機時間
            await sleep(100);
        }

        if (refreshExifAborted) {
            alert(t('refresh-exif-cancelled'));
            resetRefreshExifUI();

            // モーダルを閉じる
            const modal = bootstrap.Modal.getInstance(document.getElementById('refreshExifModal'));
            if (modal) {
                modal.hide();
            }
            return;
        }

        // ステップ4: 完了処理
        const completeResponse = await fetch('api/refresh_exif.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=complete'
        });

        if (!completeResponse.ok) {
            throw new Error('完了処理が失敗しました');
        }

        const completeData = await completeResponse.json();

        // 完了画面を表示
        showRefreshExifComplete(completeData.finalStatus || {
            total: totalFiles,
            processed: totalProcessed,
            updated: totalUpdated,
            errors: totalErrors,
            start_time: Date.now() / 1000 - 10
        });

    } catch (error) {
        console.error('EXIF洗替エラー:', error);
        alert(t('refresh-exif-error') + error.message);
        resetRefreshExifUI();
    } finally {
        isRefreshingExif = false;
    }
}

/**
 * 進捗状況を更新
 */
function updateRefreshExifProgress(processed, updated, errors, total, errorMessages) {
    const progress = total > 0 ? (processed / total) * 100 : 0;
    const progressPercent = Math.round(progress);

    // プログレスバー更新
    const progressBar = document.getElementById('refreshExifProgressBar');
    progressBar.style.width = progressPercent + '%';
    progressBar.textContent = progressPercent + '%';
    document.getElementById('refreshExifProgressPercent').textContent = progressPercent + '%';

    // 数値更新
    document.getElementById('refreshExifProcessed').textContent = processed;
    document.getElementById('refreshExifUpdated').textContent = updated;

    // エラー表示
    if (errors > 0) {
        document.getElementById('refreshExifErrorContainer').style.display = 'block';
        document.getElementById('refreshExifErrorCount').textContent = errors;

        if (errorMessages && errorMessages.length > 0) {
            const errorHtml = errorMessages.slice(0, 5).map(msg =>
                `<div class="mb-1">${escapeHtml(msg)}</div>`
            ).join('');
            document.getElementById('refreshExifErrorMessages').innerHTML = errorHtml;
        }
    }
}

/**
 * 完了画面を表示
 */
function showRefreshExifComplete(finalStatus) {
    // 進捗画面を非表示
    document.getElementById('refreshExifProgress').style.display = 'none';

    // 完了画面を表示
    document.getElementById('refreshExifComplete').style.display = 'block';
    document.getElementById('refreshExifFinalTotal').textContent = finalStatus.total || 0;
    document.getElementById('refreshExifFinalProcessed').textContent = finalStatus.processed || 0;
    document.getElementById('refreshExifFinalUpdated').textContent = finalStatus.updated || 0;
    document.getElementById('refreshExifFinalErrors').textContent = finalStatus.errors || 0;

    const elapsedTime = finalStatus.start_time ? (Date.now() / 1000 - finalStatus.start_time) : 0;
    document.getElementById('refreshExifElapsedTime').textContent = Math.round(elapsedTime);

    // ボタン更新
    const cancelBtn = document.getElementById('refreshExifCancelBtn');
    cancelBtn.style.display = 'none';
    cancelBtn.setAttribute('data-bs-dismiss', 'modal'); // data-bs-dismiss属性を復元
    document.getElementById('refreshExifReloadBtn').style.display = 'inline-block';
    document.getElementById('refreshExifCloseBtn').disabled = false;
}

/**
 * UI状態をリセット
 */
function resetRefreshExifUI() {
    isRefreshingExif = false;
    refreshExifAborted = false;

    // 各セクションを初期状態に
    document.getElementById('refreshExifStart').style.display = 'block';
    document.getElementById('refreshExifProgress').style.display = 'none';
    document.getElementById('refreshExifComplete').style.display = 'none';
    document.getElementById('refreshExifErrorContainer').style.display = 'none';

    // ボタンを初期状態に
    document.getElementById('refreshExifStartBtn').style.display = 'inline-block';
    const cancelBtn = document.getElementById('refreshExifCancelBtn');
    cancelBtn.style.display = 'inline-block';
    cancelBtn.disabled = false; // キャンセルボタンを再度有効化
    cancelBtn.setAttribute('data-bs-dismiss', 'modal'); // data-bs-dismiss属性を再度追加
    cancelBtn.innerHTML = '<i class="bi bi-x-circle"></i> <span data-i18n="cancel">キャンセル</span>'; // テキストを元に戻す
    document.getElementById('refreshExifReloadBtn').style.display = 'none';
    document.getElementById('refreshExifCloseBtn').disabled = false;

    // プログレスバーをリセット
    const progressBar = document.getElementById('refreshExifProgressBar');
    progressBar.style.width = '0%';
    progressBar.textContent = '0%';
    document.getElementById('refreshExifProgressPercent').textContent = '0%';

    // 数値をリセット
    document.getElementById('refreshExifTotal').textContent = '0';
    document.getElementById('refreshExifProcessed').textContent = '0';
    document.getElementById('refreshExifUpdated').textContent = '0';
}

/**
 * モーダルが閉じられたときのイベント
 */
document.addEventListener('DOMContentLoaded', function() {
    const refreshExifModal = document.getElementById('refreshExifModal');
    if (refreshExifModal) {
        refreshExifModal.addEventListener('hidden.bs.modal', function() {
            // 処理中でなければUIをリセット
            if (!isRefreshingExif) {
                resetRefreshExifUI();
            }
        });

        // キャンセルボタンのイベント
        document.getElementById('refreshExifCancelBtn').addEventListener('click', function(event) {
            // 処理中の場合のみ、キャンセル確認を表示
            if (isRefreshingExif) {
                // キャンセル確認
                if (confirm(t('refresh-exif-cancel-confirm'))) {
                    refreshExifAborted = true;
                    // キャンセルボタンを無効化（重複クリック防止）
                    this.disabled = true;
                    this.innerHTML = '<i class="bi bi-hourglass-split"></i> <span data-i18n="refresh-exif-cancelling">キャンセル中...</span>';
                }
            }
            // 処理中でない場合は、data-bs-dismissでモーダルが閉じる（属性が復元されているため）
        });
    }
});

/**
 * ユーティリティ: sleep関数
 */
function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * ユーティリティ: HTMLエスケープ
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

/**
 * サーバーからファイルをBlobとして取得
 */
async function fetchFileAsBlob(filePath) {
    try {
        const response = await fetch(filePath);
        if (!response.ok) {
            throw new Error('ファイル取得失敗');
        }
        return await response.blob();
    } catch (error) {
        console.error('fetchFileAsBlob error:', error);
        return null;
    }
}

/**
 * BlobからEXIF情報を読み取り（EXIF.js使用）
 */
function readExifFromFileBlob(blob) {
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
        reader.readAsDataURL(blob);
    });
}

/**
 * DMS（度分秒）形式をDD（10進数）形式に変換
 * script.jsの同名関数と同じ実装
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
 * リバースジオコーディング（緯度経度から住所を取得）
 */
async function reverseGeocode(latitude, longitude) {
    try {
        const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&zoom=18&addressdetails=1&accept-language=ja`;

        const response = await fetch(url, {
            headers: {
                'User-Agent': 'KidSnaps-GrowthAlbum/1.0 (Family Photo Album)'
            }
        });

        if (!response.ok) {
            return null;
        }

        const data = await response.json();

        if (!data || !data.address) {
            return null;
        }

        // 住所情報を組み立て
        const address = data.address;
        const locationParts = [];

        // 日本の住所フォーマット
        if (address.country === '日本') {
            if (address.state) locationParts.push(address.state);
            if (address.city) locationParts.push(address.city);
            else if (address.town) locationParts.push(address.town);
            else if (address.village) locationParts.push(address.village);
            if (address.suburb) locationParts.push(address.suburb);
        } else {
            // 海外の住所フォーマット
            if (address.city) locationParts.push(address.city);
            else if (address.town) locationParts.push(address.town);
            if (address.state) locationParts.push(address.state);
            if (address.country) locationParts.push(address.country);
        }

        if (locationParts.length === 0 && data.display_name) {
            return data.display_name.substring(0, 100);
        }

        return locationParts.join(', ');
    } catch (error) {
        console.error('Reverse geocoding error:', error);
        return null;
    }
}

/**
 * 位置情報名をデータベースに更新
 */
async function updateLocationName(fileId, locationName) {
    try {
        const response = await fetch('api/refresh_exif.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'update_location',
                file_id: fileId,
                location_name: locationName
            })
        });

        if (!response.ok) {
            throw new Error('位置情報更新失敗');
        }

        const data = await response.json();
        return data.success;
    } catch (error) {
        console.error('updateLocationName error:', error);
        return false;
    }
}
