<?php
/**
 * KidSnaps Growth Album - ZIPアルバムアップロードページ
 * ZIPファイルをアップロードしてアルバムを作成
 */

session_start();
$pageTitle = 'ZIP Album Upload';
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
        .custom-file-input-wrapper {
            position: relative;
            margin-bottom: 1rem;
        }

        .custom-file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
            z-index: 2;
        }

        .custom-file-label {
            display: block;
            padding: 3rem 1rem;
            border: 2px dashed #dee2e6;
            border-radius: 0.375rem;
            text-align: center;
            background-color: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .custom-file-label:hover {
            border-color: #0d6efd;
            background-color: #e7f1ff;
        }

        .custom-file-label i {
            font-size: 2rem;
            display: block;
            margin-bottom: 0.5rem;
            color: #6c757d;
        }

        body {
            background-color: #f8f9fa;
        }

        .upload-container {
            max-width: 800px;
            margin: 2rem auto;
        }

        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
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
                        <a class="nav-link active" href="album_upload.php">
                            <i class="bi bi-cloud-upload"></i> ZIPアップロード
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container upload-container">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-file-earmark-zip"></i> ZIPアーカイブからアルバムを作成
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-4">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>ZIPアルバムアップロード機能</strong><br>
                            画像・動画が入ったZIPファイルをアップロードすると、自動的にアルバムが作成されます。<br>
                            <small>
                                ・対応形式: JPEG, PNG, GIF, HEIC, MP4, MOV, AVI<br>
                                ・ZIPファイルサイズ: 最大5GB<br>
                                ・各ファイルサイズ: 最大500MB<br>
                                ・分割アップロード対応（大きなファイルでも安心）
                            </small>
                        </div>

                        <!-- アップロード済みZIPファイル選択 -->
                        <div id="existingZipsSection" class="mb-4 d-none">
                            <div class="alert alert-success">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="bi bi-check-circle me-2"></i>
                                        <strong>アップロード済みのZIPファイルがあります</strong>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-success" id="toggleExistingZips">
                                        表示 <i class="bi bi-chevron-down"></i>
                                    </button>
                                </div>
                                <div id="existingZipsList" class="mt-3 d-none">
                                    <div class="list-group" id="existingZipsListGroup">
                                        <!-- ここに既存ZIPファイルが表示される -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form id="zipUploadForm">
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    ZIPファイル <span class="text-danger">*</span>
                                </label>
                                <div class="custom-file-input-wrapper">
                                    <input type="file" class="form-control custom-file-input" id="zipFile" accept=".zip">
                                    <label class="custom-file-label" for="zipFile" id="zipFileLabel">
                                        <i class="bi bi-cloud-upload"></i>
                                        クリックしてZIPファイルを選択
                                    </label>
                                </div>
                                <div class="form-text">
                                    <i class="bi bi-info-circle"></i> 新しいZIPファイルをアップロードする場合は、上記からファイルを選択してください
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="albumTitle" class="form-label fw-bold">アルバムタイトル</label>
                                <input type="text" class="form-control" id="albumTitle" placeholder="例: 家族旅行 2024">
                                <div class="form-text">
                                    <i class="bi bi-info-circle"></i> 未入力の場合はZIPファイル名が使用されます
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="albumDescription" class="form-label fw-bold">アルバム説明</label>
                                <textarea class="form-control" id="albumDescription" rows="3" placeholder="このアルバムについての説明を入力..."></textarea>
                            </div>

                            <div class="mb-4">
                                <label for="peopleFilter" class="form-label fw-bold">
                                    <i class="bi bi-people"></i> 人物フィルタ（Google Photos用）
                                </label>
                                <input type="text" class="form-control" id="peopleFilter" placeholder="例: 山田太郎, 山田花子">
                                <div class="form-text">
                                    <i class="bi bi-info-circle"></i> Google Photosからエクスポートしたデータの場合、指定した人物が写っている写真のみをインポートします。複数指定する場合はカンマ区切りで入力してください。未入力の場合は全てインポートされます。
                                </div>
                            </div>

                            <!-- プレビュー結果表示 -->
                            <div id="previewResult" class="d-none mb-4">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0">
                                            <i class="bi bi-eye"></i> プレビュー結果
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="previewSummary" class="mb-3"></div>

                                        <!-- タブナビゲーション -->
                                        <ul class="nav nav-tabs mb-3" id="previewTabs" role="tablist">
                                            <li class="nav-item" role="presentation" id="peopleTabItem">
                                                <button class="nav-link active" id="people-tab" data-bs-toggle="tab" data-bs-target="#people-panel" type="button">
                                                    <i class="bi bi-people"></i> 人物一覧 <span id="peopleBadge" class="badge bg-primary ms-1">0</span>
                                                </button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link" id="matched-tab" data-bs-toggle="tab" data-bs-target="#matched-panel" type="button">
                                                    インポート対象 <span id="matchedBadge" class="badge bg-success ms-1">0</span>
                                                </button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link" id="filtered-tab" data-bs-toggle="tab" data-bs-target="#filtered-panel" type="button">
                                                    除外されるファイル <span id="filteredBadge" class="badge bg-warning text-dark ms-1">0</span>
                                                </button>
                                            </li>
                                            <li class="nav-item" role="presentation" id="noMetadataTabItem" style="display: none;">
                                                <button class="nav-link" id="no-metadata-tab" data-bs-toggle="tab" data-bs-target="#no-metadata-panel" type="button">
                                                    メタデータなし <span id="noMetadataBadge" class="badge bg-secondary ms-1">0</span>
                                                </button>
                                            </li>
                                        </ul>

                                        <!-- タブコンテンツ -->
                                        <div class="tab-content" id="previewTabContent">
                                            <div class="tab-pane fade show active" id="people-panel" role="tabpanel">
                                                <div id="peopleList" class="list-group" style="max-height: 400px; overflow-y: auto;"></div>
                                            </div>
                                            <div class="tab-pane fade" id="matched-panel" role="tabpanel">
                                                <div id="matchedFilesList" class="list-group" style="max-height: 400px; overflow-y: auto;"></div>
                                            </div>
                                            <div class="tab-pane fade" id="filtered-panel" role="tabpanel">
                                                <div id="filteredFilesList" class="list-group" style="max-height: 400px; overflow-y: auto;"></div>
                                            </div>
                                            <div class="tab-pane fade" id="no-metadata-panel" role="tabpanel">
                                                <div id="noMetadataFilesList" class="list-group" style="max-height: 400px; overflow-y: auto;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="uploadProgress" class="progress d-none mb-4" style="height: 30px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%">0%</div>
                            </div>

                            <div id="progressText" class="text-center mb-2 d-none"></div>
                            <div id="currentFileText" class="text-center text-muted small mb-3 d-none"></div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="button" class="btn btn-secondary" id="cancelBtn" onclick="location.href='albums.php'">
                                    <i class="bi bi-x-circle"></i> キャンセル
                                </button>
                                <button type="button" class="btn btn-info btn-lg" id="previewBtn">
                                    <i class="bi bi-eye"></i> プレビュー
                                </button>
                                <button type="submit" class="btn btn-primary btn-lg" id="uploadBtn" disabled>
                                    <i class="bi bi-cloud-upload"></i> インポート実行
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-question-circle"></i> ヘルプ
                        </h5>
                        <div class="accordion" id="helpAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#help1">
                                        ZIPファイルの作り方
                                    </button>
                                </h2>
                                <div id="help1" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                    <div class="accordion-body">
                                        <strong>Windows:</strong> フォルダを右クリック → 「送る」→「圧縮(zip形式)フォルダー」<br>
                                        <strong>Mac:</strong> フォルダを右クリック → 「"フォルダ名"を圧縮」
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#help2">
                                        大きなZIPファイルでも大丈夫ですか？
                                    </button>
                                </h2>
                                <div id="help2" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                    <div class="accordion-body">
                                        はい、最大5GBまで対応しています。分割アップロード技術により、大きなファイルでも安定してアップロードできます。
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#help3">
                                        処理時間はどのくらいかかりますか？
                                    </button>
                                </h2>
                                <div id="help3" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                    <div class="accordion-body">
                                        ファイル数とサイズによりますが、100枚程度の写真で数分程度です。処理中は画面を閉じないでください。
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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

    <!-- ZIPアップロードスクリプト -->
    <script src="assets/js/zip-upload.js"></script>
</body>
</html>
