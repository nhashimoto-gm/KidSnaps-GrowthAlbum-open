# KidSnaps Growth Album

![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue?logo=php)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange?logo=mysql)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3.2-purple?logo=bootstrap)
![License](https://img.shields.io/badge/License-MIT-green)
![GetID3](https://img.shields.io/badge/GetID3-Enabled-brightgreen)
![HEIC Support](https://img.shields.io/badge/HEIC-Supported-blue)

お子様の成長を記録する写真・動画アルバムアプリケーション

## 目次

- [概要](#概要)
- [クイックスタート](#クイックスタート)
- [主な機能](#主な機能)
- [技術スタック](#技術スタック)
- [ディレクトリ構造](#ディレクトリ構造)
- [セットアップ手順](#セットアップ手順)
  - **[📘 Lolipopレンタルサーバー向けセットアップガイド](LOLIPOP_SETUP.md)**
- [使い方](#使い方)
- [CLIスクリプト](#cliスクリプト)
- [セキュリティ機能](#セキュリティ機能)
- [データベーススキーマ](#データベーススキーマ)
- [カスタマイズ](#カスタマイズ)
- [トラブルシューティング](#トラブルシューティング)
- [ロードマップ](#ロードマップ)
- [ライセンス](#ライセンス)
- [サポート](#サポート)
- [貢献](#貢献)

## 概要

KidSnaps Growth Albumは、大切な家族の思い出を安全に保存・管理するためのPHPベースのWebアプリケーションです。写真や動画を簡単にアップロードし、美しいギャラリー形式で閲覧できます。

## クイックスタート

```bash
# 1. リポジトリをクローン
git clone https://github.com/yourusername/KidSnaps-GrowthAlbum.git
cd KidSnaps-GrowthAlbum

# 2. データベース設定ファイルを作成
cp .env_db.example .env_db
nano .env_db  # データベース接続情報を編集

# 3. データベーステーブルを作成
mysql -u root -p personal_finance < sql/setup.sql

# 4. アップロードディレクトリのパーミッション設定
chmod 755 uploads/ uploads/images/ uploads/videos/ uploads/thumbnails/

# 5. Webサーバーを起動（開発環境の場合）
php -S localhost:8000

# 6. ブラウザでアクセス
# http://localhost:8000
```

これで、すぐに写真や動画のアップロードを開始できます！

## 主な機能

### コア機能
- **メディアアップロード**: 写真（JPEG, PNG, GIF, HEIC）と動画（MP4, MOV, AVI）のアップロード
- **HEIC自動変換**: Apple製品のHEIC形式を自動的にJPGに変換
- **ギャラリー表示**: グリッド形式での美しいメディア表示
- **フィルタリング**: 画像・動画の種類別フィルター機能
- **検索機能**: タイトル、説明、ファイル名での検索
- **レスポンシブデザイン**: Bootstrap 5を使用したモバイルフレンドリーなUI

### メディア処理
- **画像回転**: 画像の向きを90度単位で調整可能
- **サムネイル自動生成**: 動画アップロード時に自動でサムネイルを生成
- **EXIF情報抽出**: 撮影日時、カメラ情報、位置情報などを自動抽出
- **動画メタデータ**: GetID3ライブラリを使用した動画情報の取得

### 一括処理ツール
- **一括インポート**: ディレクトリから複数ファイルを一括でデータベースに登録
- **既存HEIC変換**: 既にアップロード済みのHEIC画像を一括変換
- **サムネイル生成ツール**: ローカル環境での動画サムネイル一括生成
- **EXIF移行ツール**: 既存メディアへのEXIF情報の一括適用

### セキュリティ
- **ファイルバリデーション**: MIMEタイプによる厳格なファイル検証
- **SQLインジェクション対策**: PDOプリペアドステートメント使用
- **XSS対策**: HTMLエスケープ処理実装
- **ディレクトリトラバーサル対策**: ファイル名のサニタイズ

## 技術スタック

- **バックエンド**: PHP 7.4+
- **データベース**: MySQL 5.7+ / MariaDB 10.3+
- **フロントエンド**:
  - Bootstrap 5.3.2
  - Bootstrap Icons
  - Vanilla JavaScript
- **メディア処理ライブラリ**:
  - GetID3 (動画・音声メタデータ抽出)
  - GD / ImageMagick (画像処理)
  - FFmpeg (動画サムネイル生成、オプション)
- **HEIC変換**:
  - ImageMagick
  - heic-to-jpg (コマンドラインツール)
  - FFmpeg (フォールバック)
- **アーキテクチャ**: MVC風の構造

## ディレクトリ構造

```
KidSnaps-GrowthAlbum/
├── assets/
│   ├── css/
│   │   └── style.css                    # カスタムスタイルシート
│   └── js/
│       └── script.js                     # JavaScriptファイル
├── config/
│   └── database.php                      # データベース接続設定
├── includes/
│   ├── header.php                        # 共通ヘッダー
│   ├── footer.php                        # 共通フッター
│   ├── exif_helper.php                   # EXIF情報抽出ヘルパー
│   ├── video_metadata_helper.php         # 動画メタデータヘルパー
│   ├── image_thumbnail_helper.php        # 画像サムネイル生成ヘルパー
│   ├── heic_converter.php                # HEIC変換ヘルパー
│   └── getid3/                           # GetID3ライブラリ
├── sql/
│   └── setup.sql                         # データベーススキーマ
├── uploads/
│   ├── images/                           # 画像ファイル保存先
│   ├── videos/                           # 動画ファイル保存先
│   └── thumbnails/                       # サムネイル保存先
├── .env_db                               # データベース接続情報（要作成）
├── .env_db.example                       # データベース設定サンプル
├── .htaccess                             # Apache設定
├── index.php                             # メインページ（ギャラリー）
├── upload.php                            # アップロード処理
├── delete.php                            # 削除処理
├── rotate.php                            # 画像回転処理
├── install.php                           # インストールチェックツール
├── bulk_import.php                       # 一括インポートスクリプト（CLI）
├── generate_thumbnails_local.php         # ローカルサムネイル生成（CLI）
├── link_thumbnails.php                   # サムネイル関連付け（CLI）
├── convert_existing_heic.php             # 既存HEIC変換（CLI）
├── migrate_exif.php                      # EXIF移行スクリプト（CLI）
├── update_thumbnails.php                 # サムネイル更新（CLI）
├── MIGRATION_GUIDE.md                    # 移行ガイド
├── README.md                             # このファイル
└── LICENSE                               # ライセンス
```

## セットアップ手順

### 1. 環境要件

- PHP 7.4以上
- MySQL 5.7以上 または MariaDB 10.3以上
- Apache/Nginx Webサーバー
- 必須PHP拡張:
  - PDO
  - pdo_mysql
  - fileinfo
  - gd または imagick（画像処理）
  - exif（EXIF情報取得）
  - mbstring（マルチバイト文字列処理）
- オプション（推奨）:
  - ImageMagick（HEIC変換、画像処理）
  - FFmpeg（動画サムネイル生成）
  - heic-to-jpg（HEIC変換のフォールバック）

### 2. データベースセットアップ

Personal-Finance-Dashboardと同じMySQLデータベースを使用します。

```bash
# MySQLにログイン
mysql -u root -p

# データベースを選択
USE personal_finance;

# テーブルを作成
SOURCE sql/setup.sql;
```

### 3. データベース接続設定

`.env_db.example` をコピーして `.env_db` を作成し、データベース接続情報を設定します：

```bash
# .env_db.exampleをコピー
cp .env_db.example .env_db

# .env_dbを編集
nano .env_db
```

`.env_db` ファイルの内容：

```ini
DB_HOST=localhost
DB_NAME=personal_finance
DB_USER=your_username
DB_PASS=your_password
```

**注意**: `.env_db` ファイルはGitで管理されません（.gitignoreに含まれています）。Personal-Finance-Dashboardと同じデータベース接続情報を使用してください。

### 4. ディレクトリパーミッション設定

アップロードディレクトリに書き込み権限を付与します：

```bash
chmod 755 uploads/
chmod 755 uploads/images/
chmod 755 uploads/videos/
```

### 5. PHP設定（php.ini）

大きなファイルをアップロードするために、以下の設定を確認・変更してください：

```ini
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
memory_limit = 256M
```

### 6. Webサーバー設定

#### Apache の場合

`.htaccess` ファイルを作成（ルートディレクトリ）：

```apache
# セキュリティ設定
Options -Indexes

# URLリライト（オプション）
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
</IfModule>

# ファイルアップロード設定
php_value upload_max_filesize 50M
php_value post_max_size 50M
```

#### Nginx の場合

nginx.conf に以下を追加：

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/KidSnaps-GrowthAlbum;
    index index.php;

    client_max_body_size 50M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location /uploads/ {
        location ~ \.php$ {
            deny all;
        }
    }
}
```

### 7. アクセス

ブラウザで以下にアクセス：

```
http://localhost/KidSnaps-GrowthAlbum/
```

または

```
http://your-domain.com/
```

## 使い方

### メディアのアップロード

1. 「メディアをアップロード」ボタンをクリック
2. ファイルを選択（画像または動画）
3. タイトルと説明を入力（オプション）
4. 「アップロード」ボタンをクリック

### メディアの閲覧

- ギャラリーからサムネイルをクリック
- 「表示」ボタンでモーダルビューアーを開く
- 動画は自動的にコントロール付きで表示されます

### フィルタリングと検索

- フィルタードロップダウンで「すべて」「写真のみ」「動画のみ」を選択
- 検索ボックスでタイトル、説明、ファイル名を検索

### メディアの削除

1. 削除したいメディアの「削除」ボタンをクリック
2. 確認ダイアログで「OK」をクリック

## CLIスクリプト

### 一括メディアインポート（bulk_import.php）

指定ディレクトリから画像・動画ファイルを再帰的に検索し、データベースに一括登録します。

```bash
# 基本的な使用方法
php bulk_import.php /path/to/photos

# ドライラン（実際には登録せず確認のみ）
php bulk_import.php /path/to/photos --dry-run

# タイトルを指定
php bulk_import.php /path/to/photos --title="夏休みの思い出"
```

**主な機能:**
- 画像・動画ファイルの再帰的スキャン
- EXIF情報の自動抽出（撮影日時、位置情報、カメラ情報）
- 動画メタデータの取得
- 重複ファイルのスキップ
- 動画サムネイルの自動生成（ffmpegが利用可能な場合）

### ローカル環境でのサムネイル生成（generate_thumbnails_local.php）

ローカルPC（ffmpegがインストールされている環境）で動画からサムネイルを生成します。

```bash
# 基本的な使用方法
php generate_thumbnails_local.php /path/to/videos

# 出力先を指定
php generate_thumbnails_local.php /path/to/videos --output=./my_thumbnails

# サムネイル抽出時間と幅を指定
php generate_thumbnails_local.php /path/to/videos --time=2 --width=640
```

**生成されるファイル:**
- サムネイル画像（JPG形式）
- `thumbnail_mapping.csv`（動画とサムネイルの対応表）

### サムネイル関連付け（link_thumbnails.php）

`bulk_import.php`でインポートした動画データに、`generate_thumbnails_local.php`で生成したサムネイルを関連付けます。

```bash
# 基本的な使用方法
php link_thumbnails.php ./thumbnails

# ドライラン（実際には更新せず確認のみ）
php link_thumbnails.php ./thumbnails --dry-run

# カスタムマッピングファイルを使用
php link_thumbnails.php ./thumbnails --mapping=./custom_mapping.csv
```

**使用手順:**
1. ローカル環境で`generate_thumbnails_local.php`を実行してサムネイルを生成
2. 生成されたサムネイルとCSVファイルをサーバーにアップロード
3. サーバーで`link_thumbnails.php`を実行してデータベースを更新

**注意事項:**
- `bulk_import.php`でインポートした動画ファイルの`filename`と、マッピングCSVの`video_filename`が一致する必要があります
- サムネイルファイルは`uploads/thumbnails/`ディレクトリに保存されます
- 既にサムネイルが設定されている動画はスキップされます

### 既存HEIC画像の一括変換（convert_existing_heic.php）

データベースに登録済みのHEIC画像を一括でJPGに変換します。

```bash
# 基本的な使用方法
php convert_existing_heic.php

# ドライラン（実際には変換せず確認のみ）
php convert_existing_heic.php --dry-run
```

**主な機能:**
- データベース内のHEIC画像を検索
- ImageMagick、heic-to-jpg、FFmpegを使った複数のフォールバック変換
- 変換後のファイルパス・MIME タイプの自動更新
- 元のHEICファイルは保持

### サムネイルの一括更新（update_thumbnails.php）

既存の動画メディアに対してサムネイルを一括生成します。

```bash
# 基本的な使用方法
php update_thumbnails.php

# 特定の動画IDのみ処理
php update_thumbnails.php --id=123
```

**主な機能:**
- サムネイルが未設定の動画を自動検出
- FFmpegを使用してサムネイルを生成
- データベースへのサムネイルパス自動登録

### EXIF情報の移行（migrate_exif.php）

既存メディアファイルからEXIF情報を抽出してデータベースに追加します。

```bash
# 基本的な使用方法
php migrate_exif.php
```

**主な機能:**
- 既存画像・動画のEXIF/メタデータを抽出
- データベーススキーマの自動更新
- 撮影日時、GPS情報、カメラ情報の取得

## セキュリティ機能

- **ファイルタイプ検証**: MIMEタイプによる厳格なファイル検証
- **ファイルサイズ制限**: 最大50MBまで
- **SQLインジェクション対策**: PDOプリペアドステートメント使用
- **XSS対策**: HTMLエスケープ処理実装
- **ディレクトリトラバーサル対策**: ファイル名のサニタイズ
- **直接実行防止**: アップロードディレクトリでのPHP実行を制限

### ⚠️ ベーシック認証の設定（強く推奨）

本アプリケーションには現在ユーザー認証機能が実装されていません。**公開サーバーにデプロイする場合は、必ずベーシック認証を設定してください。**

#### Apache での設定

ルートディレクトリの `.htaccess` に以下を追加：

```apache
# ベーシック認証
AuthType Basic
AuthName "KidSnaps Growth Album - Private Area"
AuthUserFile /path/to/.htpasswd
Require valid-user
```

`.htpasswd` ファイルの作成：

```bash
# htpasswdコマンドでパスワードファイルを作成
htpasswd -c /path/to/.htpasswd username

# 追加ユーザーの作成（-cオプションなし）
htpasswd /path/to/.htpasswd another_user
```

#### Nginx での設定

`nginx.conf` または該当のサーバーブロックに以下を追加：

```nginx
location / {
    auth_basic "KidSnaps Growth Album - Private Area";
    auth_basic_user_file /path/to/.htpasswd;

    try_files $uri $uri/ /index.php?$query_string;
}
```

`.htpasswd` ファイルの作成：

```bash
# openssl コマンドを使用（推奨）
echo "username:$(openssl passwd -apr1)" > /path/to/.htpasswd

# または htpasswd コマンドを使用
htpasswd -c /path/to/.htpasswd username
```

#### ロリポップレンタルサーバーでの設定

詳細は **[📘 Lolipopレンタルサーバー向けセットアップガイド](LOLIPOP_SETUP.md)** を参照してください。

ロリポップの場合、管理画面から簡単に設定できます：

1. ロリポップ管理画面にログイン
2. 「セキュリティ」→「アクセス制限」を選択
3. 対象ディレクトリ（KidSnaps-GrowthAlbumのルート）を指定
4. ユーザー名とパスワードを設定

または、`.htaccess` を手動で設置：

```apache
AuthType Basic
AuthName "Private Album"
AuthUserFile /home/users/2/lolipop.jp-xxxx/.htpasswd
Require valid-user
```

**重要:** `.htpasswd` のパスは絶対パスで指定してください。相対パスでは動作しません。

## データベーススキーマ

### media_files テーブル

| カラム名 | データ型 | 説明 |
|---------|---------|------|
| id | INT | 主キー（自動増分） |
| filename | VARCHAR(255) | 元のファイル名 |
| stored_filename | VARCHAR(255) | 保存時のファイル名 |
| file_path | VARCHAR(500) | ファイルパス |
| file_type | ENUM | ファイルタイプ（image/video） |
| mime_type | VARCHAR(100) | MIMEタイプ |
| file_size | BIGINT | ファイルサイズ（バイト） |
| thumbnail_path | VARCHAR(500) | サムネイル画像パス（動画用） |
| title | VARCHAR(255) | タイトル |
| description | TEXT | 説明 |
| upload_date | DATETIME | アップロード日時 |
| **exif_datetime** | DATETIME | EXIF撮影日時 ⭐ |
| **exif_latitude** | DECIMAL(10,8) | EXIF緯度情報 ⭐ |
| **exif_longitude** | DECIMAL(11,8) | EXIF経度情報 ⭐ |
| **exif_location_name** | VARCHAR(255) | EXIF位置情報（住所など） ⭐ |
| **exif_camera_make** | VARCHAR(100) | EXIFカメラメーカー ⭐ |
| **exif_camera_model** | VARCHAR(100) | EXIFカメラモデル ⭐ |
| **exif_orientation** | INT | EXIF画像の向き（1-8） ⭐ |
| created_at | TIMESTAMP | 作成日時 |
| updated_at | TIMESTAMP | 更新日時 |

⭐ のカラムは、`migrate_exif.php`を実行することで追加されます。

### media_tags テーブル

タグ機能のためのテーブル（スキーマ定義済み、UI未実装）

| カラム名 | データ型 | 説明 |
|---------|---------|------|
| id | INT | 主キー（自動増分） |
| tag_name | VARCHAR(50) | タグ名（ユニーク） |
| created_at | TIMESTAMP | 作成日時 |

### media_tag_relations テーブル

メディアとタグの関連付けテーブル

| カラム名 | データ型 | 説明 |
|---------|---------|------|
| media_id | INT | メディアID（外部キー） |
| tag_id | INT | タグID（外部キー） |
| created_at | TIMESTAMP | 作成日時 |

## カスタマイズ

### アップロード可能なファイルタイプの変更

`upload.php` の以下の部分を編集：

```php
$allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$allowedVideoTypes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/mpeg'];
```

### ファイルサイズ制限の変更

`upload.php` の以下の部分を編集：

```php
$maxFileSize = 50 * 1024 * 1024; // 50MB in bytes
```

### テーマカラーの変更

`assets/css/style.css` の以下の部分を編集：

```css
:root {
    --primary-color: #0d6efd;
    --secondary-color: #6c757d;
    /* ... */
}
```

## トラブルシューティング

### アップロードが失敗する場合

1. `uploads/` ディレクトリの書き込み権限を確認
2. PHP設定（upload_max_filesize, post_max_size）を確認
3. Webサーバーのエラーログを確認

### データベース接続エラー

1. `config/database.php` の接続情報を確認
2. MySQLサーバーが起動しているか確認
3. データベースとテーブルが正しく作成されているか確認

### 画像・動画が表示されない

1. ファイルパスが正しいか確認
2. ブラウザの開発者ツールでネットワークエラーを確認
3. Webサーバーの静的ファイル配信設定を確認

## ロードマップ

### 実装済み機能 ✅

- [x] 画像・動画のアップロード（JPEG, PNG, GIF, HEIC, MP4, MOV, AVI対応）
- [x] ギャラリー表示とページネーション
- [x] 検索・フィルタリング機能
- [x] 多言語対応（日本語・英語）
- [x] ダークモード対応
- [x] HEIC形式の自動変換
- [x] 画像回転機能
- [x] EXIF情報の自動抽出（撮影日時、位置情報、カメラ情報）
- [x] 動画メタデータの抽出（GetID3）
- [x] 動画サムネイル自動生成
- [x] 一括インポート機能
- [x] 重複ファイル検出機能
- [x] サムネイル最適化・WebP対応
- [x] Lazy Loading（遅延読み込み）
- [x] ブラウザキャッシュ最適化
- [x] セキュリティ対策（SQLインジェクション、XSS対策）
- [x] 撮影日編集機能

### 短期目標（v2.0） - 1-2ヶ月

- [ ] **ユーザー認証・マルチユーザー対応**
  - ログイン・ログアウト機能
  - ユーザーごとのアルバム管理
  - 権限管理（閲覧のみ、編集可能など）
  - **現在はベーシック認証で対応（推奨）**
- [ ] **タグ機能の実装**
  - メディアへの複数タグ付与
  - タグによるフィルタリング
  - タグクラウド表示
- [ ] **日付フィルタリング強化**
  - 日付範囲での絞り込み
  - 年・月単位での表示切り替え
  - カレンダービュー

### 中期目標（v3.0） - 3-6ヶ月

- [ ] **スライドショー機能**
  - 自動再生機能
  - トランジション効果
  - フルスクリーンモード
- [ ] **共有機能**
  - アルバムの外部共有リンク生成
  - パスワード保護
  - 有効期限設定
- [ ] **お気に入り・評価機能**
  - メディアへの星評価
  - お気に入りフォルダ
  - ベストショット自動選択
- [ ] **GPS位置情報マップ表示**
  - 撮影場所の地図表示
  - 位置情報によるフィルタリング

### 長期目標（v4.0以降） - 6ヶ月以上

- [ ] **AIによる自動分類**
  - 顔認識による人物タグ付け
  - シーン自動認識
  - 類似写真のグループ化
- [ ] **メディア編集機能**
  - トリミング、フィルター適用
  - 明るさ・コントラスト調整
  - テキスト・スタンプ追加
- [ ] **バックアップ・エクスポート機能**
  - クラウドストレージ連携（Dropbox、Google Drive）
  - アルバムのZIPエクスポート
  - 自動バックアップスケジュール
- [ ] **コメント・思い出機能**
  - メディアへのコメント追加
  - タイムライン形式での思い出表示
  - コメントの共有

### 技術的改善（継続的）

- [ ] **パフォーマンス最適化**
  - データベースクエリの最適化
  - CDN対応
  - より高度なキャッシュ機構
- [ ] **レスポンシブデザインの向上**
  - タッチジェスチャーのサポート強化
  - Progressive Web App (PWA) 対応
- [ ] **テストカバレッジの向上**
  - ユニットテストの追加
  - E2Eテストの実装
  - CI/CDパイプラインの構築
- [ ] **セキュリティ強化**
  - CSRF保護の実装
  - セッション固定化対策
  - レート制限の実装

## ライセンス

このプロジェクトのライセンスについては、LICENSEファイルを参照してください。

## サポート

問題や質問がある場合は、GitHubのIssuesセクションで報告してください。

## 貢献

プルリクエストを歓迎します！大きな変更の場合は、まずIssueを開いて変更内容を議論してください。

## 作成者

KidSnaps Growth Album development team

---

**注意**: このアプリケーションは教育・個人使用を目的としています。本番環境で使用する場合は、追加のセキュリティ対策（HTTPS、CSRF保護、入力検証の強化など）を実装してください。
