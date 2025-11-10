# KidSnaps Growth Album

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-blue.svg)

子供の成長記録を残すためのウェブアルバムアプリケーション。写真・動画のアップロード、EXIF情報の自動抽出、位置情報の表示、多言語対応（日本語・英語）、ダークモード対応などの機能を備えています。

## ✨ 主な機能

- 📸 **写真・動画アップロード** - JPEG, PNG, GIF, HEIC, MP4, MOV, AVIに対応
- 🌍 **EXIF情報自動抽出** - 撮影日時、GPS位置情報、カメラ情報を自動取得
- 🔍 **重複ファイル検出** - ファイルハッシュによる重複チェックで無駄なストレージ使用を防止
- 🌐 **多言語対応** - 日本語・英語に対応
- 🌙 **ダークモード** - 目に優しいダークテーマ
- 📱 **レスポンシブデザイン** - モバイル・タブレット・デスクトップに対応
- 🎬 **動画サムネイル自動生成** - ffmpegによる動画サムネイル生成
- 📄 **ページネーション** - 1ページ12件のカード表示

## 📁 ディレクトリ構造

```
/
├── index.php                   # メインページ（ギャラリー）
├── upload.php                  # アップロード処理
├── delete.php                  # 削除処理
├── rotate.php                  # 画像回転処理
├── install.php                 # インストールスクリプト
│
├── api/                        # API エンドポイント
│   └── check_duplicate.php     # 重複チェックAPI
│
├── assets/                     # 静的リソース
│   ├── css/                    # スタイルシート
│   │   └── style.css           # メインCSS
│   └── js/                     # JavaScript
│       ├── script.js           # メインスクリプト
│       └── duplicate-checker.js # クライアント側重複チェック
│
├── config/                     # 設定ファイル
│   └── database.php            # データベース接続設定
│
├── docs/                       # ドキュメント
│   ├── README_en.md            # 英語版README
│   ├── MIGRATION_GUIDE.md      # マイグレーションガイド
│   ├── LOLIPOP_SETUP.md        # ロリポップ設置ガイド
│   └── DUPLICATE_CHECK_SETUP.md # 重複チェック設定ガイド
│
├── includes/                   # ヘルパーファイル
│   ├── header.php              # ヘッダーコンポーネント
│   ├── footer.php              # フッターコンポーネント
│   ├── exif_helper.php         # EXIF情報抽出
│   ├── heic_converter.php      # HEIC変換ヘルパー
│   ├── image_thumbnail_helper.php # 画像サムネイル生成
│   ├── video_metadata_helper.php  # 動画メタデータ抽出
│   └── getid3/                 # GetID3ライブラリ
│
├── lib/                        # サーバー側処理ライブラリ
│   ├── chunk_upload.php        # チャンク分割アップロード
│   ├── finalize_upload.php     # アップロード最終処理
│   └── debug_logs.php          # デバッグログビューア
│
├── logs/                       # ログファイル（.gitignore対象）
│   └── .gitkeep
│
├── migrations/                 # データベースマイグレーション
│   ├── add_file_hash_column.sql
│   └── ...
│
├── scripts/                    # CLIスクリプト
│   ├── bulk/                   # バルク処理
│   │   ├── bulk_import.php     # 一括インポート
│   │   ├── analyze_duplicates.php # 重複分析
│   │   ├── remove_duplicates_v1_deprecated.php  # 重複削除v1（非推奨）
│   │   └── remove_duplicates_v2.php # 重複削除v2（推奨）
│   ├── check/                  # チェック・診断
│   │   ├── check_db.php        # DB接続確認
│   │   ├── check_schema.php    # スキーマ確認
│   │   ├── check_thumbnails.php # サムネイル確認
│   │   ├── check_latest_records.php # 最新レコード確認
│   │   └── check_paths.php     # パス確認
│   │
│   ├── maintenance/            # メンテナンス
│   │   ├── regenerate_thumbnails.php # サムネイル再生成
│   │   ├── update_file_hashes.php    # ハッシュ値更新
│   │   ├── update_thumbnails.php     # サムネイル更新
│   │   ├── generate_thumbnails_local.php # ローカルサムネイル生成
│   │   ├── link_thumbnails.php       # サムネイルリンク
│   │   ├── migrate_exif.php          # EXIFマイグレーション
│   │   └── convert_existing_heic.php # HEIC変換
│   │
│   └── test/                   # テストスクリプト
│       ├── test_db_insert.php  # DB挿入テスト
│       └── test_index.php      # インデックステスト
│
├── sql/                        # SQLスクリプト
│   └── schema.sql              # データベーススキーマ
│
└── uploads/                    # アップロードファイル（.gitignore対象）
    ├── images/                 # 画像ファイル
    ├── videos/                 # 動画ファイル
    ├── thumbnails/             # サムネイル
    └── temp/                   # 一時ファイル
```

## 🚀 クイックスタート

### 1. 必要環境

- PHP 7.4以上
- MySQL 5.7以上
- ffmpeg（動画サムネイル生成に必要）
- Webサーバー（Apache / Nginx）

### 2. インストール

```bash
# リポジトリのクローン
git clone https://github.com/yourusername/KidSnaps-GrowthAlbum.git
cd KidSnaps-GrowthAlbum

# データベース設定ファイルの作成
cp .env_db.example .env_db

# .env_dbを編集してデータベース情報を設定
nano .env_db

# ブラウザでインストーラーにアクセス
# http://your-domain.com/install.php
```

### 3. ffmpegの設置（推奨）

動画サムネイル生成のために、ffmpegをローカルに配置することを推奨します：

```bash
# ffmpegをダウンロードして解凍
# https://ffmpeg.org/download.html

# ./ffmpeg/ ディレクトリに配置
mkdir -p ffmpeg
cp /path/to/ffmpeg ./ffmpeg/ffmpeg  # Linux/Mac
cp /path/to/ffmpeg.exe ./ffmpeg/ffmpeg.exe  # Windows
```

## 📖 使い方

### 基本操作

1. **写真・動画のアップロード**
   - トップページの「アップロード」ボタンをクリック
   - ファイルを選択（複数選択可）
   - タイトル・説明を入力（オプション）
   - 「アップロード」をクリック

2. **重複チェック**
   - ファイル選択時に自動的に重複チェックが実行されます
   - 重複ファイルがある場合は警告が表示されます

3. **検索・フィルタ**
   - トップページの検索バーでタイトル、説明、ファイル名を検索
   - フィルターで「すべて」「写真のみ」「動画のみ」を切り替え

4. **言語切り替え**
   - ヘッダーの「EN/JP」ボタンで日本語⇔英語を切り替え

5. **ダークモード切り替え**
   - ヘッダーの月/太陽アイコンでライト⇔ダークモードを切り替え

### CLIスクリプトの使用

#### バルクインポート

```bash
# 指定ディレクトリ内の全ファイルをインポート
php scripts/bulk/bulk_import.php /path/to/photos
```

#### 重複ファイルの削除

**推奨:** `remove_duplicates_v2.php` を使用してください（複数の検出方法をサポート）

```bash
# 重複ファイルを分析
php scripts/bulk/analyze_duplicates.php

# 方法1: ファイル名+サイズで重複削除（ドライラン）
php scripts/bulk/remove_duplicates_v2.php --method filename --dry-run

# 方法2: EXIF撮影日時+サイズで重複削除（写真のみ、ドライラン）
php scripts/bulk/remove_duplicates_v2.php --method exif --dry-run

# 方法3: ファイルハッシュで重複削除（最も正確、推奨、ドライラン）
php scripts/bulk/remove_duplicates_v2.php --method hash --dry-run

# 実際に削除（ハッシュ方式）
php scripts/bulk/remove_duplicates_v2.php --method hash
```

**注意:** `remove_duplicates.php`（v1）は非推奨です。`remove_duplicates_v2.php` を使用してください。

#### サムネイル再生成

```bash
# サムネイルが存在しない動画のみ
php scripts/maintenance/regenerate_thumbnails.php --missing

# すべての動画
php scripts/maintenance/regenerate_thumbnails.php --all

# 強制再生成
php scripts/maintenance/regenerate_thumbnails.php --all --force
```

#### ファイルハッシュの更新

```bash
# 既存ファイルのハッシュ値を計算・更新
php scripts/maintenance/update_file_hashes.php
```

#### サムネイル最適化

```bash
# すべてのサムネイルを最適化（プログレッシブJPEG化、サイズ最適化）
php scripts/maintenance/optimize_thumbnails.php --all

# WebP版も生成（25-35%ファイルサイズ削減）
php scripts/maintenance/optimize_thumbnails.php --all --webp

# ドライラン（実際には変更せず、処理内容のみ表示）
php scripts/maintenance/optimize_thumbnails.php --dry-run
```

#### データベース確認

```bash
# データベース接続確認
php scripts/check/check_db.php

# スキーマ確認
php scripts/check/check_schema.php

# 最新レコード確認
php scripts/check/check_latest_records.php
```

## 🔧 設定

### データベース設定

`.env_db` ファイルで以下を設定：

```
DB_HOST=localhost
DB_NAME=kidsnaps
DB_USER=your_username
DB_PASS=your_password
```

### アップロード制限

`php.ini` または `.user.ini` で以下を設定：

```ini
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
memory_limit = 256M
```

### パフォーマンス最適化

アプリケーションには以下の高速化機能が実装されています：

#### 自動適用される最適化

1. **Lazy Loading（遅延読み込み）**
   - 画像・動画に`loading="lazy"`属性を自動付与
   - スクロール時に必要な要素のみ読み込み
   - 初期表示速度が60-80%向上

2. **ブラウザキャッシュ**
   - 画像・動画: 1年間キャッシュ（`immutable`フラグ）
   - CSS/JavaScript: 1ヶ月キャッシュ
   - 2回目以降のアクセスで90%以上高速化

3. **プログレッシブJPEG**
   - サムネイルは段階的に表示される形式で保存
   - 体感速度が大幅に向上

#### 手動で実行できる最適化

```bash
# 既存サムネイルの最適化
php scripts/maintenance/optimize_thumbnails.php --all

# WebP形式も生成（対応ブラウザで25-35%削減）
php scripts/maintenance/optimize_thumbnails.php --all --webp
```

#### 期待される効果

| 施策 | 初回読み込み | 2回目以降 | データ転送量 |
|------|-------------|----------|--------------|
| Lazy Loading | 60-80%削減 | - | 60-80%削減 |
| サムネイル最適化 | 30-50%削減 | - | 40-60%削減 |
| ブラウザキャッシュ | - | 90%以上削減 | 100%削減 |
| WebP対応 | 25-35%削減 | 25-35%削減 | 25-35%削減 |

## 🔒 セキュリティ

### セキュリティ機能
- アップロードディレクトリに `.htaccess` を配置してPHP実行を禁止
- ファイル名をハッシュ化して保存
- SQLインジェクション対策（PDOプリペアドステートメント使用）
- XSS対策（htmlspecialchars使用）
- デバッグモードの環境変数による制御（本番環境では自動無効化）
- デバッグログページのアクセス制限（環境変数とパスワード保護）

### 環境変数による設定

本番環境と開発環境を適切に分離するため、環境変数をサポートしています：

#### デバッグモード設定

```bash
# 開発環境: デバッグモードを有効化
export DEBUG_MODE=1

# 本番環境: 設定しない（デフォルトで無効）
# DEBUG_MODE を設定しない、または 0 に設定
```

デバッグモードが有効な場合：
- PHP エラーが画面に表示されます
- デバッグログページ（`lib/debug_logs.php`）にアクセス可能になります

#### デバッグパスワード設定

```bash
# .env_db ファイルに追加
DEBUG_PASSWORD=your_secure_password
```

デバッグログページへのアクセス:
```
http://your-domain.com/lib/debug_logs.php?pass=your_secure_password
```

**⚠️ 重要:** 本番環境では `DEBUG_MODE` を設定しないでください。設定した場合、セキュリティリスクが発生します。

## 📝 ライセンス

MITライセンス - 詳細は [LICENSE](LICENSE) を参照してください。

## 📋 変更履歴

### 最新のリファクタリング（2025-01-10）

#### セキュリティ改善
- ✅ デバッグモードを環境変数（`DEBUG_MODE`）で制御可能に変更
- ✅ 本番環境でのエラー表示を自動無効化
- ✅ デバッグログページ（`lib/debug_logs.php`）に環境変数とパスワードによる二重保護を追加

#### コード品質の向上
- ✅ 重複削除スクリプトv1を非推奨化（v2を推奨）
- ✅ エラーハンドリングの統一
- ✅ コードの整理とドキュメント改善

#### 開発環境の改善
- ✅ 環境変数による開発/本番環境の分離
- ✅ より安全なデバッグ機能の実装

### 既知の問題と今後の改善予定

- [ ] CSRF保護の実装
- [ ] セッション固定化対策の追加
- [ ] 統合テストスイートの追加
- [ ] クラスベース設計への段階的移行
- [ ] API レート制限の強化（Nominatim API）

## 🤝 コントリビューション

プルリクエストを歓迎します！大きな変更の場合は、まずissueを開いて変更内容を議論してください。

## 📧 サポート

問題が発生した場合は、[GitHub Issues](https://github.com/yourusername/KidSnaps-GrowthAlbum/issues) で報告してください。

## 🙏 謝辞

- [Bootstrap 5](https://getbootstrap.com/) - UIフレームワーク
- [EXIF.js](https://github.com/exif-js/exif-js) - EXIF情報抽出
- [heic2any](https://github.com/alexcorvi/heic2any) - HEIC変換
- [SparkMD5](https://github.com/satazor/js-spark-md5) - ファイルハッシュ計算
- [GetID3](https://www.getid3.org/) - メディアメタデータ抽出
- [ffmpeg](https://ffmpeg.org/) - 動画処理

---

Made with ❤️ for capturing precious moments
