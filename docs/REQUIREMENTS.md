# KidSnaps Growth Album - 要件定義書

**作成日**: 2025年1月
**バージョン**: 1.0
**対象システム**: KidSnaps Growth Album (子供の成長記録アルバムシステム)

---

## 目次

1. [プロジェクト概要](#1-プロジェクト概要)
2. [システム目的](#2-システム目的)
3. [機能要件](#3-機能要件)
4. [非機能要件](#4-非機能要件)
5. [技術仕様](#5-技術仕様)
6. [データベース設計](#6-データベース設計)
7. [システム構成](#7-システム構成)
8. [ディレクトリ構造](#8-ディレクトリ構造)
9. [セキュリティ要件](#9-セキュリティ要件)
10. [API仕様](#10-api仕様)
11. [インストール要件](#11-インストール要件)
12. [デプロイ手順](#12-デプロイ手順)
13. [開発環境構築](#13-開発環境構築)
14. [テスト要件](#14-テスト要件)
15. [今後の拡張計画](#15-今後の拡張計画)

---

## 1. プロジェクト概要

### 1.1 システム名
**KidSnaps Growth Album** - 子供の成長記録写真・動画アルバムシステム

### 1.2 システム概要
子供の写真や動画をアップロードし、撮影日時・場所・カメラ情報などのメタデータを自動抽出して管理する、セルフホスト型のWebアルバムアプリケーション。

### 1.3 主要特徴
- 画像・動画の一元管理
- EXIF/メタデータの自動抽出
- GPS情報から住所への逆ジオコーディング
- 重複ファイル検出機能
- HEIC形式のJPEG自動変換
- 多言語対応（日本語・英語）
- ダークモード・ライトモード切り替え
- レスポンシブデザイン

### 1.4 想定ユーザー
- 家族や個人利用者
- 子供の成長記録を保存したい保護者
- プライバシーを重視し、自己ホスティングを希望するユーザー

---

## 2. システム目的

### 2.1 ビジネス目標
1. 子供の成長記録を長期的に安全に保存
2. 撮影日時・場所を自動で整理し、思い出を振り返りやすくする
3. クラウドサービスに依存せず、自己管理可能な環境を提供

### 2.2 解決する課題
- スマートフォンのストレージ容量不足
- 複数デバイスに散らばった写真・動画の一元管理
- 撮影情報の自動整理の必要性
- プライバシー保護（第三者サービス利用の懸念）

---

## 3. 機能要件

### 3.1 メディアアップロード機能

#### 3.1.1 基本アップロード
- **対応フォーマット**:
  - 画像: JPEG, PNG, GIF, HEIC/HEIF
  - 動画: MP4, MOV, AVI
- **最大ファイルサイズ**: 500MB/ファイル
- **複数ファイル同時アップロード**: 対応
- **進行状況表示**: リアルタイム進捗バー
- **エラーハンドリング**: ファイルサイズ超過、形式不正、サーバーエラー時のメッセージ表示

#### 3.1.2 HEIC変換機能
- **クライアント側変換**: heic2anyライブラリによるブラウザ内変換
- **サーバー側変換**: PHP Imagick/GDによるフォールバック変換
- **変換品質**: 85% JPEG品質

#### 3.1.3 重複検出機能
- **検出方式**: MD5ハッシュ値による照合
- **検出タイミング**: アップロード前（クライアント側）
- **ユーザー通知**: 重複ファイルがある場合、警告を表示し除外
- **検出精度**: ファイル内容が完全一致する場合のみ

#### 3.1.4 サムネイル自動生成
- **画像サムネイル**:
  - サイズ: 幅320px（アスペクト比維持）
  - 品質: 85% JPEG
  - ライブラリ: PHP GD/Imagick
- **動画サムネイル**:
  - ffmpegで先頭フレーム抽出
  - サイズ: 320x180px
  - フォーマット: JPEG

### 3.2 メタデータ抽出機能

#### 3.2.1 EXIF情報抽出（画像）
- **撮影日時**: `exif_datetime` (EXIF DateTimeOriginal > DateTime)
- **GPS座標**: `exif_latitude`, `exif_longitude`
- **カメラ情報**: `exif_camera_make`, `exif_camera_model`
- **画像向き**: `exif_orientation` (1-8)
- **自動補正**: EXIF orientationに基づく自動回転

#### 3.2.2 動画メタデータ抽出
- **作成日時**: QuickTime metadata (`creation_time`)
- **GPS情報**: ISO 6709形式のGPS文字列パース
- **カメラ情報**: `make`, `model` タグ
- **ライブラリ**: GetID3

#### 3.2.3 逆ジオコーディング
- **API**: OpenStreetMap Nominatim API
- **レート制限**: 1秒/1リクエスト
- **取得情報**: 住所文字列（国、都道府県、市町村レベル）
- **キャッシュ**: データベースに保存

#### 3.2.4 EXIF一括更新機能
- **対象**: データベース内の全メディアファイル
- **実行方法**: 管理者モードから手動実行
- **処理内容**:
  - ファイルから再度EXIF読み取り
  - GPS情報の逆ジオコーディング
  - データベース更新
- **進捗表示**: リアルタイム進捗とログ表示

### 3.3 ギャラリー表示機能

#### 3.3.1 一覧表示
- **レイアウト**: グリッドカードレイアウト（レスポンシブ）
- **表示項目**:
  - サムネイル画像
  - タイトル（ファイル名）
  - 撮影日時
  - 撮影場所
  - ファイルタイプアイコン
- **ページネーション**: 12件/ページ

#### 3.3.2 フィルタリング機能
- **タイプフィルタ**: すべて / 画像のみ / 動画のみ
- **検索機能**: タイトル、説明、ファイル名での部分一致検索
- **ソート機能**:
  - アップロード日時（新しい順・古い順）
  - 撮影日時（新しい順・古い順）
  - 撮影場所（五十音順）
  - ファイル名（五十音順）

#### 3.3.3 詳細表示（モーダルビューア）
- **画像表示**: 元画像をモーダルで表示
- **動画再生**: HTML5 videoタグでの再生制御
- **メタデータ表示**:
  - ファイル名
  - ファイルサイズ
  - 撮影日時
  - 撮影場所
  - カメラ情報
  - GPS座標
  - MIMEタイプ
- **操作ボタン**:
  - 回転（左90°・右90°）
  - 削除（管理者モードのみ）
  - ダウンロード

#### 3.3.4 遅延読み込み
- **実装方式**: Lazy loading属性
- **対象**: サムネイル画像
- **効果**: 初期ページ読み込み速度向上

### 3.4 メディア操作機能

#### 3.4.1 回転機能
- **回転角度**: 90°単位（左・右）
- **適用方式**: CSSトランスフォーム + データベース保存
- **保存タイミング**: 「回転を保存」ボタンクリック時
- **対応ファイル**: 画像・動画両方
- **権限**: ユーザーモード・管理者モード両方で可能

#### 3.4.2 削除機能
- **権限**: 管理者モードのみ
- **確認ダイアログ**: 削除前に確認メッセージ表示
- **削除対象**:
  - データベースレコード
  - 元ファイル
  - サムネイルファイル
- **状態保持**: 削除後もフィルタ・検索・ページ状態を維持

#### 3.4.3 メタデータ編集機能
- **編集可能項目**:
  - 撮影日時
  - GPS座標
  - 撮影場所名
- **権限**: 管理者モード推奨
- **保存方式**: AJAX非同期更新

### 3.5 管理者モード

#### 3.5.1 認証機能
- **認証方式**: セッションベース
- **パスワード**: `.env_db` で設定（`ADMIN_PASSWORD`）
- **ログイン**: モーダルダイアログで入力
- **セッション継続**: ブラウザセッション中有効
- **ログアウト**: 明示的なログアウト機能（トグルで解除）

#### 3.5.2 管理者専用機能
- 削除ボタンの表示・実行
- EXIF一括更新の実行
- メタデータ編集

### 3.6 多言語対応機能

#### 3.6.1 対応言語
- 日本語（デフォルト）
- 英語

#### 3.6.2 実装方式
- **クライアント側切り替え**: JavaScriptによる動的テキスト置換
- **保存**: LocalStorageに言語設定を保存
- **対象UI要素**:
  - ナビゲーション
  - ボタンラベル
  - フォームラベル
  - メッセージ
  - モーダルダイアログ

### 3.7 テーマ機能

#### 3.7.1 テーマ種別
- ライトモード（デフォルト）
- ダークモード

#### 3.7.2 実装方式
- **CSSカスタムプロパティ**: `:root` での変数定義
- **切り替え**: JavaScriptでbody classを追加・削除
- **保存**: LocalStorageにテーマ設定を保存

#### 3.7.3 対応色
- 背景色
- テキスト色
- カード背景色
- ボーダー色
- ホバー色

### 3.8 CLI管理ツール

#### 3.8.1 一括インポート
- **スクリプト**: `scripts/bulk/bulk_import.php`
- **機能**: 指定ディレクトリから画像・動画を再帰的にインポート
- **オプション**:
  - `--dry-run`: 実行シミュレーション
  - `--title`: 一括タイトル設定
- **処理内容**:
  - 重複チェック（ファイル名+サイズ）
  - メタデータ抽出
  - サムネイル生成
  - データベース登録

#### 3.8.2 重複分析・削除
- **分析スクリプト**: `scripts/bulk/analyze_duplicates.php`
- **削除スクリプト**: `scripts/bulk/remove_duplicates_v2.php`
- **検出方式**:
  - ファイル名ベース
  - EXIFベース
  - ハッシュベース（推奨）
- **オプション**: `--dry-run`, `--method`

#### 3.8.3 サムネイル再生成
- **スクリプト**: `scripts/maintenance/regenerate_thumbnails.php`
- **モード**:
  - `--missing`: 欠損サムネイルのみ再生成
  - `--all`: 全サムネイル再生成
  - `--force`: 強制上書き

#### 3.8.4 ファイルハッシュ更新
- **スクリプト**: `scripts/maintenance/update_file_hashes.php`
- **機能**: 既存ファイルのMD5ハッシュを計算しDB更新

#### 3.8.5 データベース診断
- **スクリプト**:
  - `scripts/check/check_db.php`: 接続確認
  - `scripts/check/check_schema.php`: スキーマ検証
  - `scripts/check/check_thumbnails.php`: サムネイル整合性確認

---

## 4. 非機能要件

### 4.1 性能要件

#### 4.1.1 レスポンスタイム
- **ページ初期表示**: 3秒以内（ギャラリー12件表示）
- **ファイルアップロード**: 500MBファイルで10分以内
- **サムネイル生成**: 画像1枚あたり5秒以内
- **EXIF一括更新**: 1000ファイルで30分以内

#### 4.1.2 スループット
- **同時アップロード**: 最大10ファイル
- **並行ユーザー**: 10ユーザー（想定）

#### 4.1.3 最適化
- **ブラウザキャッシュ**: 画像・動画は1年間キャッシュ
- **Gzip圧縮**: HTML/CSS/JS
- **Progressive JPEG**: サムネイル形式
- **遅延読み込み**: サムネイル画像

### 4.2 可用性要件
- **稼働率**: 99%（個人利用想定）
- **バックアップ**: ユーザー責任（スクリプト提供予定）
- **復旧時間**: 1時間以内

### 4.3 拡張性要件
- **ファイル数**: 10,000ファイルまで対応
- **ストレージ**: 500GB想定
- **ユーザー数**: 現状は単一ユーザー（将来的に複数ユーザー対応予定）

### 4.4 保守性要件
- **ログ記録**: エラーログの記録（`logs/` ディレクトリ）
- **デバッグモード**: 開発環境用デバッグ機能
- **ドキュメント**: README、セットアップガイド、マイグレーションガイド完備

### 4.5 互換性要件

#### 4.5.1 ブラウザ対応
- **必須対応**:
  - Google Chrome 90+
  - Mozilla Firefox 88+
  - Safari 14+
  - Microsoft Edge 90+
- **部分対応**: IE11（機能制限あり）

#### 4.5.2 モバイル対応
- iOS Safari 14+
- Android Chrome 90+
- レスポンシブデザイン必須

#### 4.5.3 サーバー環境
- PHP 7.4+（推奨: 8.0+）
- MySQL 5.7+（推奨: 8.0+）
- Apache 2.4+ / LiteSpeed / Nginx

### 4.6 セキュリティ要件
（詳細は第9章を参照）

### 4.7 ユーザビリティ要件
- **学習時間**: 初回利用で10分以内に基本操作習得
- **言語**: 日本語・英語対応
- **アクセシビリティ**: 基本的なWCAG 2.1レベルA準拠

---

## 5. 技術仕様

### 5.1 技術スタック

#### 5.1.1 バックエンド
- **言語**: PHP 7.4+ (推奨: PHP 8.3)
- **データベース**: MySQL 5.7+ / MariaDB 10.3+
- **Webサーバー**: Apache 2.4+ (mod_rewrite必須) / LiteSpeed / Nginx

#### 5.1.2 必須PHP拡張
- `pdo`
- `pdo_mysql`
- `fileinfo`
- `gd` または `imagick`
- `exif`
- `mbstring`

#### 5.1.3 フロントエンド
- **フレームワーク**: Bootstrap 5.3.2
- **アイコン**: Bootstrap Icons 1.11.1
- **JavaScript**: Vanilla JS (ES6+)

#### 5.1.4 外部ライブラリ

**JavaScript:**
- `exif-js`: EXIF読み取り
- `piexifjs 1.0.6`: EXIF操作
- `heic2any`: HEIC変換
- `spark-md5 3.0.2`: MD5ハッシュ計算

**PHP:**
- `getid3`: 動画メタデータ抽出（同梱）

**システム:**
- `ffmpeg`: 動画サムネイル生成（バンドル版またはシステム版）

#### 5.1.5 外部API
- **Nominatim API** (OpenStreetMap): 逆ジオコーディング
  - URL: `https://nominatim.openstreetmap.org/reverse`
  - レート制限: 1req/秒

### 5.2 アーキテクチャ

#### 5.2.1 アーキテクチャパターン
- **構造**: 伝統的PHP (手続き型 + ヘルパー関数)
- **MVC風分離**:
  - View: `index.php`, `includes/*.php`
  - Controller: `upload.php`, `delete.php`, `rotate.php`
  - Model: `config/database.php`, ヘルパー関数

#### 5.2.2 ディレクトリ構成
（詳細は第8章を参照）

#### 5.2.3 データフロー

**アップロードフロー:**
```
[ブラウザ]
  -> ファイル選択
  -> クライアント側: MD5計算 + 重複チェック
  -> クライアント側: HEIC変換（該当時）
  -> クライアント側: 動画サムネイル抽出
  -> [upload.php]
  -> サーバー側: 形式検証
  -> サーバー側: ファイル保存
  -> サーバー側: EXIF/メタデータ抽出
  -> サーバー側: サムネイル生成
  -> サーバー側: 逆ジオコーディング
  -> [MySQL]
  -> [ブラウザ] リダイレクト
```

**ギャラリー表示フロー:**
```
[ブラウザ]
  -> [index.php]
  -> フィルタ・検索・ソートパラメータ取得
  -> [MySQL] SELECT with WHERE/ORDER BY/LIMIT
  -> レンダリング
  -> [ブラウザ] HTML表示
  -> JavaScript: 言語・テーマ適用
```

### 5.3 データベースアクセス

#### 5.3.1 接続方式
- **PDO (PHP Data Objects)**
- **文字コード**: UTF-8
- **タイムゾーン**: Asia/Tokyo（設定可能）

#### 5.3.2 接続情報
`.env_db` ファイルから読み込み:
```ini
DB_HOST=localhost
DB_NAME=kidsnaps
DB_USER=username
DB_PASS=password
```

#### 5.3.3 ヘルパー関数
**`config/database.php`:**
- `getDBConnection()`: PDOインスタンス取得
- `executeQuery($sql, $params)`: プリペアドステートメント実行
- `fetchAll($sql, $params)`: 全レコード取得
- `fetchOne($sql, $params)`: 単一レコード取得

### 5.4 ファイルストレージ

#### 5.4.1 ストレージ構造
```
uploads/
├── images/           # オリジナル画像
├── videos/           # オリジナル動画
├── thumbnails/       # サムネイル画像
└── temp/             # 一時ファイル（自動削除）
```

#### 5.4.2 ファイル命名規則
```
{timestamp}_{uniqid}_{originalname}
例: 20250111123045_65a1b2c3d4e5f_IMG_1234.jpg
```

#### 5.4.3 パーミッション
- ディレクトリ: `755`
- ファイル: `644`
- `.htaccess` で PHP実行を禁止

---

## 6. データベース設計

### 6.1 ER図概要

```
[media_files] 1---0..* [media_tag_relations] *---1 [media_tags]
```

### 6.2 テーブル定義

#### 6.2.1 media_files（メディアファイル）

| カラム名 | 型 | NULL | デフォルト | 説明 |
|---------|-----|------|----------|------|
| id | INT | NO | AUTO_INCREMENT | 主キー |
| filename | VARCHAR(255) | NO | - | 元ファイル名 |
| stored_filename | VARCHAR(255) | NO | - | 保存ファイル名 |
| file_path | VARCHAR(500) | NO | - | ファイルパス |
| file_type | ENUM('image','video') | NO | - | ファイルタイプ |
| mime_type | VARCHAR(100) | NO | - | MIMEタイプ |
| file_size | BIGINT | NO | - | ファイルサイズ（バイト）|
| file_hash | VARCHAR(32) | YES | NULL | MD5ハッシュ値 |
| thumbnail_path | VARCHAR(500) | YES | NULL | サムネイルパス |
| rotation | INT | YES | 0 | 回転角度（0/90/180/270）|
| title | VARCHAR(255) | YES | NULL | タイトル |
| description | TEXT | YES | NULL | 説明 |
| exif_datetime | DATETIME | YES | NULL | 撮影日時 |
| exif_latitude | DECIMAL(10,8) | YES | NULL | 緯度 |
| exif_longitude | DECIMAL(11,8) | YES | NULL | 経度 |
| exif_location_name | VARCHAR(255) | YES | NULL | 撮影場所名 |
| exif_camera_make | VARCHAR(100) | YES | NULL | カメラメーカー |
| exif_camera_model | VARCHAR(100) | YES | NULL | カメラモデル |
| exif_orientation | INT | YES | 1 | EXIF向き（1-8）|
| upload_date | DATETIME | YES | CURRENT_TIMESTAMP | アップロード日時 |
| created_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | 作成日時 |
| updated_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | 更新日時 |

**インデックス:**
- PRIMARY KEY: `id`
- INDEX: `idx_file_type` (`file_type`)
- INDEX: `idx_upload_date` (`upload_date`)
- INDEX: `idx_file_hash` (`file_hash`)
- INDEX: `idx_exif_datetime` (`exif_datetime`)

**CREATE文:**
```sql
CREATE TABLE media_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type ENUM('image', 'video') NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size BIGINT NOT NULL,
    file_hash VARCHAR(32) DEFAULT NULL,
    thumbnail_path VARCHAR(500) DEFAULT NULL,
    rotation INT DEFAULT 0,
    title VARCHAR(255) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    exif_datetime DATETIME DEFAULT NULL,
    exif_latitude DECIMAL(10,8) DEFAULT NULL,
    exif_longitude DECIMAL(11,8) DEFAULT NULL,
    exif_location_name VARCHAR(255) DEFAULT NULL,
    exif_camera_make VARCHAR(100) DEFAULT NULL,
    exif_camera_model VARCHAR(100) DEFAULT NULL,
    exif_orientation INT DEFAULT 1,
    upload_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_file_type (file_type),
    INDEX idx_upload_date (upload_date),
    INDEX idx_file_hash (file_hash),
    INDEX idx_exif_datetime (exif_datetime)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 6.2.2 media_tags（タグマスタ）※未実装

| カラム名 | 型 | NULL | デフォルト | 説明 |
|---------|-----|------|----------|------|
| id | INT | NO | AUTO_INCREMENT | 主キー |
| tag_name | VARCHAR(50) | NO | - | タグ名 |
| created_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | 作成日時 |

**CREATE文:**
```sql
CREATE TABLE media_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tag_name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 6.2.3 media_tag_relations（タグ関連）※未実装

| カラム名 | 型 | NULL | デフォルト | 説明 |
|---------|-----|------|----------|------|
| media_id | INT | NO | - | メディアID |
| tag_id | INT | NO | - | タグID |
| created_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | 作成日時 |

**CREATE文:**
```sql
CREATE TABLE media_tag_relations (
    media_id INT NOT NULL,
    tag_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (media_id, tag_id),
    FOREIGN KEY (media_id) REFERENCES media_files(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES media_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 6.3 データ移行

#### 6.3.1 初期セットアップ
`sql/setup.sql` を実行して基本テーブル作成

#### 6.3.2 マイグレーション
順次以下のSQLファイルを適用:
1. `sql/add_exif_data.sql` - EXIF関連カラム追加
2. `sql/add_file_hash.sql` - ファイルハッシュカラム追加
3. `sql/add_rotation.sql` - 回転カラム追加
4. `sql/add_thumbnail_column.sql` - サムネイルパスカラム追加

#### 6.3.3 インストールウィザード
`install.php` が自動的に:
- テーブル存在確認
- マイグレーション適用
- 初期データ投入（必要時）

---

## 7. システム構成

### 7.1 システム構成図

```
[クライアント（ブラウザ）]
        |
        | HTTPS
        |
[Webサーバー (Apache/LiteSpeed)]
        |
        +-- [PHP 7.4+]
        |      |
        |      +-- [アプリケーションロジック]
        |      |
        |      +-- [GD/Imagick] (画像処理)
        |      |
        |      +-- [GetID3] (動画メタデータ)
        |
        +-- [ffmpeg] (動画サムネイル)
        |
        +-- [ファイルシステム]
        |      |
        |      +-- uploads/images/
        |      +-- uploads/videos/
        |      +-- uploads/thumbnails/
        |
        +-- [MySQL 5.7+]
               |
               +-- [media_files テーブル]

[外部API]
   +-- Nominatim (OpenStreetMap) - 逆ジオコーディング
```

### 7.2 デプロイ環境

#### 7.2.1 推奨環境
- **ホスティング**: レンタルサーバー（ロリポップ等）またはVPS
- **OS**: Linux (Ubuntu 20.04+, CentOS 7+)
- **Webサーバー**: LiteSpeed / Apache 2.4+
- **PHP**: 8.0以上
- **MySQL**: 8.0以上
- **ディスク**: 最低50GB（画像・動画保存用）
- **メモリ**: 最低512MB（推奨: 1GB以上）

#### 7.2.2 最小動作環境
- PHP 7.4
- MySQL 5.7
- 256MB メモリ
- 10GB ディスク

### 7.3 ネットワーク構成

#### 7.3.1 ポート
- **HTTP**: 80番ポート（リダイレクト推奨）
- **HTTPS**: 443番ポート（推奨）

#### 7.3.2 SSL/TLS
- **証明書**: Let's Encrypt推奨
- **プロトコル**: TLS 1.2以上

#### 7.3.3 アクセス制限（推奨）
- **Basic認証**: Webサーバーレベルで設定
- **IP制限**: 必要に応じて `.htaccess` で設定

---

## 8. ディレクトリ構造

### 8.1 完全ディレクトリツリー

```
/home/user/KidSnaps-GrowthAlbum/
│
├── .git/                         # Gitリポジトリ
├── .gitignore                    # Git除外設定
├── .htaccess                     # Apache設定
├── .user.ini                     # PHP設定（CGI/FastCGI）
├── .env_db                       # 環境変数（DBパスワード等）
├── .env_db.example               # 環境変数テンプレート
│
├── index.php                     # メインギャラリーページ
├── upload.php                    # アップロード処理
├── delete.php                    # 削除処理
├── rotate.php                    # 回転処理
├── install.php                   # インストールウィザード
├── toggle_admin_mode.php         # 管理者モード切り替え
│
├── api/                          # REST API エンドポイント
│   ├── check_duplicate.php       # 重複チェックAPI
│   ├── refresh_exif.php          # EXIF一括更新API
│   ├── update_metadata.php       # メタデータ更新API
│   ├── update_photo_date.php     # 撮影日時更新API
│   └── update_rotation.php       # 回転更新API（非推奨）
│
├── assets/                       # 静的リソース
│   ├── css/
│   │   └── style.css             # メインスタイルシート
│   └── js/
│       ├── script.js             # メインJavaScript
│       ├── duplicate-checker.js  # 重複チェック機能
│       └── refresh-exif.js       # EXIF更新UI
│
├── config/                       # 設定ファイル
│   ├── database.php              # DB接続・ヘルパー関数
│   └── admin.php                 # 管理者認証
│
├── docs/                         # ドキュメント
│   ├── README.md                 # 日本語README
│   ├── README_en.md              # 英語README
│   ├── MIGRATION_GUIDE.md        # マイグレーションガイド
│   ├── LOLIPOP_SETUP.md          # ロリポップセットアップ
│   ├── DUPLICATE_CHECK_SETUP.md  # 重複チェックセットアップ
│   └── REQUIREMENTS.md           # 本要件定義書（新規）
│
├── ffmpeg/                       # ffmpegバイナリ（オプション）
│   ├── ffmpeg                    # ffmpeg実行ファイル
│   └── ffprobe                   # ffprobe実行ファイル
│
├── includes/                     # インクルードファイル
│   ├── header.php                # HTMLヘッダー
│   ├── footer.php                # HTMLフッター
│   ├── exif_helper.php           # EXIF抽出ヘルパー
│   ├── heic_converter.php        # HEIC変換ヘルパー
│   ├── image_thumbnail_helper.php # サムネイル生成ヘルパー
│   ├── video_metadata_helper.php # 動画メタデータヘルパー
│   └── getid3/                   # GetID3ライブラリ
│       └── (多数のファイル)
│
├── lib/                          # サーバーサイドライブラリ
│   ├── chunk_upload.php          # チャンクアップロード
│   ├── finalize_upload.php       # アップロード完了処理
│   ├── cleanup_temp.php          # 一時ファイル削除
│   └── debug_logs.php            # デバッグログ閲覧
│
├── logs/                         # ログファイル（.gitignore）
│   ├── error.log
│   └── debug.log
│
├── migrations/                   # DBマイグレーション
│   └── add_file_hash_column.sql
│
├── scripts/                      # CLIツール
│   ├── bulk/                     # バッチ処理
│   │   ├── bulk_import.php       # 一括インポート
│   │   ├── analyze_duplicates.php # 重複分析
│   │   └── remove_duplicates_v2.php # 重複削除
│   ├── check/                    # ヘルスチェック
│   │   ├── check_db.php          # DB接続確認
│   │   ├── check_schema.php      # スキーマ検証
│   │   └── check_thumbnails.php  # サムネイル検証
│   ├── maintenance/              # メンテナンス
│   │   ├── regenerate_thumbnails.php # サムネイル再生成
│   │   ├── update_file_hashes.php    # ハッシュ更新
│   │   └── convert_existing_heic.php # HEIC一括変換
│   └── test/                     # テストスクリプト
│       └── (各種テストファイル)
│
├── sql/                          # SQLスキーマ
│   ├── setup.sql                 # 初期セットアップ
│   ├── add_exif_data.sql         # EXIF追加マイグレーション
│   ├── add_file_hash.sql         # ハッシュ追加
│   ├── add_rotation.sql          # 回転追加
│   └── add_thumbnail_column.sql  # サムネイルパス追加
│
└── uploads/                      # アップロードファイル（.gitignore）
    ├── .htaccess                 # PHP実行禁止
    ├── images/                   # 画像ファイル
    ├── videos/                   # 動画ファイル
    ├── thumbnails/               # サムネイル画像
    └── temp/                     # 一時ファイル
```

### 8.2 主要ファイル説明

#### 8.2.1 ルートディレクトリ
- **index.php**: ギャラリーメインページ。フィルタ・検索・ソート・ページネーション機能を実装
- **upload.php**: ファイルアップロード処理。メタデータ抽出、サムネイル生成、DB保存
- **delete.php**: ファイル削除処理。管理者認証チェック
- **rotate.php**: 画像・動画回転処理
- **install.php**: 初回セットアップウィザード
- **.htaccess**: Apache設定。リライトルール、セキュリティ設定
- **.env_db**: データベース接続情報、管理者パスワード

#### 8.2.2 設定ファイル (config/)
- **database.php**: PDO接続、ヘルパー関数
- **admin.php**: 管理者モード認証ロジック

#### 8.2.3 ヘルパー (includes/)
- **header.php / footer.php**: 共通HTMLテンプレート
- **exif_helper.php**: EXIF抽出、GPS変換、逆ジオコーディング
- **video_metadata_helper.php**: GetID3による動画メタデータ抽出
- **image_thumbnail_helper.php**: GD/Imagickサムネイル生成
- **heic_converter.php**: サーバー側HEIC変換

#### 8.2.4 フロントエンド (assets/)
- **css/style.css**: カスタムCSS、テーマ変数
- **js/script.js**: メインロジック（約1000行）
- **js/duplicate-checker.js**: MD5計算、重複API呼び出し
- **js/refresh-exif.js**: EXIF更新UI

---

## 9. セキュリティ要件

### 9.1 認証・認可

#### 9.1.1 管理者認証
- **方式**: セッションベース
- **パスワード**: 平文保存（`.env_db`）
  - **TODO**: bcryptでハッシュ化必須
- **セッション管理**:
  - セッションID再生成（login時）
  - httponly フラグ設定
  - secure フラグ（HTTPS時）

#### 9.1.2 Basic認証（推奨）
- Webサーバーレベルで実装
- `.htaccess` または Nginx設定
- 全ページへのアクセス制限

### 9.2 ファイルアップロードセキュリティ

#### 9.2.1 ファイル検証
- **MIMEタイプチェック**: `$_FILES['type']` と `finfo_file()` の両方
- **拡張子ホワイトリスト**: jpg, jpeg, png, gif, heic, heif, mp4, mov, avi
- **ファイルサイズ制限**: 500MB
- **ファイル名サニタイズ**: 特殊文字除去、ユニークID付与

#### 9.2.2 アップロードディレクトリ保護
- **uploads/.htaccess**:
  ```apache
  # PHP実行禁止
  php_flag engine off
  AddType text/plain .php .php3 .php4 .php5 .phtml

  # .htaccess自体の閲覧禁止
  <Files .htaccess>
  Order allow,deny
  Deny from all
  </Files>
  ```

### 9.3 SQLインジェクション対策

#### 9.3.1 プリペアドステートメント
すべてのDB操作でPDOプリペアドステートメント使用:
```php
$stmt = $pdo->prepare("SELECT * FROM media_files WHERE id = ?");
$stmt->execute([$id]);
```

#### 9.3.2 パラメータバインディング
```php
$stmt = $pdo->prepare("INSERT INTO media_files (filename, file_size) VALUES (:filename, :size)");
$stmt->execute([
    ':filename' => $filename,
    ':size' => $filesize
]);
```

### 9.4 XSS対策

#### 9.4.1 出力エスケープ
すべてのユーザー入力を表示時にエスケープ:
```php
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');
```

#### 9.4.2 Content-Security-Policy（推奨）
`.htaccess` に追加:
```apache
Header set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';"
```

### 9.5 CSRF対策

#### 9.5.1 現状
- **未実装**（TODO項目）

#### 9.5.2 実装予定
- CSRFトークン生成・検証
- 全フォームにトークン埋め込み
- トークンの有効期限管理

### 9.6 ディレクトリトラバーサル対策

#### 9.6.1 パス検証
```php
$realPath = realpath($uploadDir . '/' . $filename);
if (strpos($realPath, $uploadDir) !== 0) {
    die('Invalid file path');
}
```

#### 9.6.2 ホワイトリスト検証
アップロードパスを固定ディレクトリに制限

### 9.7 セッション管理

#### 9.7.1 セッション設定
```php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // HTTPS時
ini_set('session.use_strict_mode', 1);
session_start();
```

#### 9.7.2 セッション固定攻撃対策
ログイン時にセッションID再生成:
```php
session_regenerate_id(true);
```

### 9.8 機密情報保護

#### 9.8.1 .htaccessによるアクセス制限
```apache
# 隠しファイル・設定ファイル保護
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

# 設定ファイル保護
<FilesMatch "\.(env|ini|sql|log)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

#### 9.8.2 .gitignore設定
```
.env_db
logs/
uploads/
```

### 9.9 エラーハンドリング

#### 9.9.1 本番環境設定
```php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');
```

#### 9.9.2 カスタムエラーページ
`.htaccess`:
```apache
ErrorDocument 404 /404.html
ErrorDocument 500 /500.html
```

### 9.10 レート制限

#### 9.10.1 外部API
- Nominatim: 1秒/1リクエスト（コード内実装済み）

#### 9.10.2 アップロード（未実装）
- セッション単位でアップロード回数制限
- IP単位で制限（将来実装予定）

### 9.11 その他セキュリティ対策

#### 9.11.1 HTTPヘッダー
`.htaccess` 推奨設定:
```apache
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
Header set Referrer-Policy "strict-origin-when-cross-origin"
```

#### 9.11.2 PHP設定
`.user.ini`:
```ini
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off
```

---

## 10. API仕様

### 10.1 API共通仕様

#### 10.1.1 エンドポイント形式
- **ベースURL**: `https://yourdomain.com/api/`
- **形式**: REST風
- **リクエスト**: POST（JSON）
- **レスポンス**: JSON

#### 10.1.2 共通レスポンスフォーマット
```json
{
    "success": true|false,
    "message": "処理結果メッセージ",
    "data": {}
}
```

#### 10.1.3 エラーレスポンス
```json
{
    "success": false,
    "error": "エラーメッセージ",
    "code": "ERROR_CODE"
}
```

### 10.2 API詳細

#### 10.2.1 重複チェックAPI

**エンドポイント**: `POST /api/check_duplicate.php`

**説明**: アップロード前にファイルの重複をチェック

**リクエスト**:
```json
{
    "hash": "d41d8cd98f00b204e9800998ecf8427e",
    "filename": "IMG_1234.jpg",
    "filesize": 2048000
}
```

**レスポンス**:
```json
{
    "isDuplicate": true,
    "count": 2,
    "existing": [
        {
            "id": 123,
            "filename": "IMG_1234.jpg",
            "upload_date": "2025-01-01 12:00:00",
            "file_path": "uploads/images/20250101120000_abc123_IMG_1234.jpg"
        }
    ]
}
```

**認証**: 不要

---

#### 10.2.2 EXIF一括更新API

**エンドポイント**: `POST /api/refresh_exif.php`

**説明**: 全メディアファイルのEXIFデータを再抽出・更新

**リクエスト**:
```json
{}
```

**レスポンス（ストリーミング）**:
```json
{"status": "processing", "current": 1, "total": 100, "filename": "IMG_0001.jpg"}
{"status": "processing", "current": 2, "total": 100, "filename": "IMG_0002.jpg"}
...
{"status": "complete", "updated": 98, "failed": 2, "total": 100}
```

**認証**: 管理者モード必須

---

#### 10.2.3 メタデータ更新API

**エンドポイント**: `POST /api/update_metadata.php`

**説明**: メディアファイルのGPS・場所情報を更新

**リクエスト**:
```json
{
    "media_id": 123,
    "latitude": 35.6812,
    "longitude": 139.7671,
    "location_name": "東京都千代田区"
}
```

**レスポンス**:
```json
{
    "success": true,
    "message": "メタデータを更新しました"
}
```

**認証**: 管理者モード推奨

---

#### 10.2.4 撮影日時更新API

**エンドポイント**: `POST /api/update_photo_date.php`

**説明**: メディアファイルの撮影日時を更新

**リクエスト**:
```json
{
    "media_id": 123,
    "exif_datetime": "2025-01-01 12:00:00"
}
```

**レスポンス**:
```json
{
    "success": true,
    "message": "撮影日時を更新しました"
}
```

**認証**: 管理者モード推奨

---

#### 10.2.5 回転更新API（非推奨）

**エンドポイント**: `POST /api/update_rotation.php`

**説明**: 画像・動画の回転角度を更新（現在は `/rotate.php` を推奨）

**リクエスト**:
```json
{
    "media_id": 123,
    "rotation": 90
}
```

**レスポンス**:
```json
{
    "success": true,
    "message": "回転を保存しました"
}
```

**認証**: 不要（ユーザーモードでも可）

---

## 11. インストール要件

### 11.1 サーバー要件

#### 11.1.1 必須要件
- **PHP**: 7.4以上
- **MySQL**: 5.7以上
- **Webサーバー**: Apache 2.4+ (mod_rewrite) / LiteSpeed / Nginx
- **ディスク空き容量**: 最低10GB（画像・動画保存用）
- **PHPメモリ**: 256MB以上

#### 11.1.2 必須PHP拡張
```
- pdo
- pdo_mysql
- fileinfo
- gd または imagick
- exif
- mbstring
- json
```

#### 11.1.3 オプション
- **ffmpeg**: 動画サムネイル生成用（推奨）
- **imagick**: 高品質サムネイル生成用

### 11.2 PHP設定要件

#### 11.2.1 .user.ini / php.ini 設定
```ini
upload_max_filesize = 512M
post_max_size = 512M
max_execution_time = 600
max_input_time = 600
memory_limit = 256M
file_uploads = On
display_errors = Off
log_errors = On
error_log = /path/to/logs/error.log
```

### 11.3 ディレクトリパーミッション

```bash
chmod 755 uploads/ logs/
chmod 755 uploads/images uploads/videos uploads/thumbnails uploads/temp
chmod 644 .env_db
chmod 644 .htaccess
```

### 11.4 データベース設定

#### 11.4.1 文字コード
- **デフォルト文字セット**: `utf8mb4`
- **照合順序**: `utf8mb4_unicode_ci`

#### 11.4.2 データベース作成
```sql
CREATE DATABASE kidsnaps DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'kidsnaps_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON kidsnaps.* TO 'kidsnaps_user'@'localhost';
FLUSH PRIVILEGES;
```

---

## 12. デプロイ手順

### 12.1 初回インストール手順

#### ステップ1: ファイル配置
```bash
# リポジトリをクローン
git clone https://github.com/your-repo/KidSnaps-GrowthAlbum.git
cd KidSnaps-GrowthAlbum

# 本番環境にアップロード（FTP/SFTP/Git経由）
```

#### ステップ2: 環境変数設定
```bash
# .env_db.example をコピー
cp .env_db.example .env_db

# エディタで編集
nano .env_db
```

`.env_db` 内容:
```ini
DB_HOST=localhost
DB_NAME=kidsnaps
DB_USER=your_db_user
DB_PASS=your_secure_password
ADMIN_PASSWORD=your_admin_password
```

#### ステップ3: データベース作成
```bash
mysql -u root -p
```

```sql
CREATE DATABASE kidsnaps DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'kidsnaps_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON kidsnaps.* TO 'kidsnaps_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### ステップ4: ディレクトリ作成・パーミッション設定
```bash
mkdir -p uploads/images uploads/videos uploads/thumbnails uploads/temp logs
chmod 755 uploads logs
chmod 755 uploads/images uploads/videos uploads/thumbnails uploads/temp
```

uploads/.htaccess 作成:
```apache
php_flag engine off
AddType text/plain .php
<Files .htaccess>
    Order allow,deny
    Deny from all
</Files>
```

#### ステップ5: インストールウィザード実行
ブラウザで以下にアクセス:
```
https://yourdomain.com/install.php
```

1. システムチェック実行
2. DB接続確認
3. テーブル作成
4. マイグレーション適用
5. 完了メッセージ表示

#### ステップ6: install.php 削除またはリネーム
```bash
mv install.php install.php.bak
```

#### ステップ7: Basic認証設定（推奨）
`.htaccess` に追加:
```apache
AuthType Basic
AuthName "KidSnaps Album"
AuthUserFile /path/to/.htpasswd
Require valid-user
```

.htpasswd 作成:
```bash
htpasswd -c .htpasswd username
```

### 12.2 アップデート手順

#### ステップ1: バックアップ
```bash
# データベースバックアップ
mysqldump -u kidsnaps_user -p kidsnaps > backup_$(date +%Y%m%d).sql

# ファイルバックアップ
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz uploads/
```

#### ステップ2: 最新コード取得
```bash
git pull origin main
```

#### ステップ3: マイグレーション実行
```bash
mysql -u kidsnaps_user -p kidsnaps < migrations/new_migration.sql
```

または install.php 再実行（テーブル存在確認あり）

#### ステップ4: キャッシュクリア
```bash
# 必要に応じてブラウザキャッシュクリア
# Ctrl+Shift+R (Chrome/Firefox)
```

### 12.3 ロリポップ専用手順

詳細は `docs/LOLIPOP_SETUP.md` 参照

#### 要点:
1. LiteSpeed環境用に `.user.ini` で設定
2. `php_value` ではなく `ini_set()` 使用
3. ffmpegバイナリを `/ffmpeg/` に配置
4. パーミッション: ファイルマネージャーで設定

---

## 13. 開発環境構築

### 13.1 ローカル開発環境

#### 13.1.1 必要ツール
- PHP 7.4+ (CLI)
- MySQL 5.7+ または MariaDB
- Composer（オプション）
- Node.js + npm（オプション、フロントエンド開発時）
- Git

#### 13.1.2 XAMPP/MAMP でのセットアップ

**XAMPPの場合 (Windows/Linux):**
```bash
# リポジトリクローン
cd /opt/lampp/htdocs/
git clone https://github.com/your-repo/KidSnaps-GrowthAlbum.git

# .env_db設定
cd KidSnaps-GrowthAlbum
cp .env_db.example .env_db
nano .env_db

# MySQL起動
sudo /opt/lampp/lampp start

# データベース作成
mysql -u root
CREATE DATABASE kidsnaps DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# ブラウザでアクセス
http://localhost/KidSnaps-GrowthAlbum/install.php
```

**MAMPの場合 (Mac):**
```bash
cd /Applications/MAMP/htdocs/
git clone https://github.com/your-repo/KidSnaps-GrowthAlbum.git
cd KidSnaps-GrowthAlbum
cp .env_db.example .env_db
# .env_db編集後
http://localhost:8888/KidSnaps-GrowthAlbum/install.php
```

#### 13.1.3 PHP Built-in Server での起動
```bash
# ディレクトリ移動
cd KidSnaps-GrowthAlbum

# MySQL起動（別途）
mysql.server start

# PHP組み込みサーバー起動
php -S localhost:8000

# ブラウザでアクセス
http://localhost:8000/install.php
```

### 13.2 デバッグモード

#### 13.2.1 有効化
`.env_db` に追加:
```ini
DEBUG_MODE=1
DEBUG_PASSWORD=your_debug_password
```

#### 13.2.2 デバッグログ閲覧
```
http://localhost/lib/debug_logs.php
```
パスワード入力後、`logs/debug.log` の内容を表示

#### 13.2.3 エラー表示
開発環境では `php.ini` で:
```ini
display_errors = On
error_reporting = E_ALL
```

### 13.3 テストデータ投入

#### 13.3.1 手動アップロード
ブラウザからテスト画像をアップロード

#### 13.3.2 一括インポート
```bash
php scripts/bulk/bulk_import.php /path/to/test/photos --dry-run
php scripts/bulk/bulk_import.php /path/to/test/photos
```

### 13.4 開発用ツール

#### 13.4.1 データベース診断
```bash
php scripts/check/check_db.php
php scripts/check/check_schema.php
```

#### 13.4.2 サムネイル確認
```bash
php scripts/check/check_thumbnails.php
```

#### 13.4.3 ログ監視
```bash
tail -f logs/error.log
tail -f logs/debug.log
```

### 13.5 コーディング規約

#### 13.5.1 PHP
- **標準**: PSR-12準拠推奨
- **インデント**: スペース4つ
- **命名規則**:
  - 関数: `snake_case`
  - 変数: `$snake_case`
  - 定数: `UPPER_CASE`

#### 13.5.2 JavaScript
- **標準**: ES6+
- **インデント**: スペース4つ
- **命名規則**:
  - 関数: `camelCase`
  - 変数: `camelCase`
  - 定数: `UPPER_CASE`

#### 13.5.3 CSS
- **標準**: BEM推奨
- **インデント**: スペース4つ

#### 13.5.4 SQL
- **キーワード**: 大文字
- **インデント**: スペース4つ

---

## 14. テスト要件

### 14.1 単体テスト

#### 14.1.1 対象
- ヘルパー関数（exif_helper.php, video_metadata_helper.php等）
- データベースヘルパー関数

#### 14.1.2 フレームワーク
- PHPUnit（将来導入予定）

#### 14.1.3 カバレッジ目標
- 70%以上

### 14.2 統合テスト

#### 14.2.1 シナリオ
- ファイルアップロード → メタデータ抽出 → DB保存
- EXIF一括更新
- 重複検出
- 削除処理

#### 14.2.2 実装方法
- Selenium（将来導入予定）
- 手動テスト（現状）

### 14.3 E2Eテスト

#### 14.3.1 テストケース
1. **アップロードフロー**:
   - JPEG画像アップロード
   - HEIC画像アップロード（変換確認）
   - MP4動画アップロード
   - 重複ファイルアップロード（警告確認）

2. **ギャラリー表示フロー**:
   - 一覧表示
   - フィルタ適用
   - 検索実行
   - ソート変更
   - ページネーション

3. **管理者フロー**:
   - 管理者ログイン
   - EXIF一括更新
   - ファイル削除

4. **メタデータ編集フロー**:
   - 撮影日時変更
   - GPS座標変更
   - 場所名変更

### 14.4 性能テスト

#### 14.4.1 負荷テスト
- **ツール**: Apache Bench, JMeter
- **シナリオ**: 同時10ユーザーでアップロード
- **目標**: エラー率5%以下

#### 14.4.2 ストレステスト
- **ファイル数**: 10,000ファイル登録時のギャラリー表示速度
- **目標**: 3秒以内

### 14.5 セキュリティテスト

#### 14.5.1 脆弱性診断
- SQLインジェクション
- XSS
- CSRF
- ファイルアップロード脆弱性
- ディレクトリトラバーサル

#### 14.5.2 ツール
- OWASP ZAP
- Burp Suite

### 14.6 ブラウザ互換性テスト

#### 14.6.1 対象ブラウザ
- Chrome 最新版
- Firefox 最新版
- Safari 最新版
- Edge 最新版
- iOS Safari（iPhone/iPad）
- Android Chrome

#### 14.6.2 テスト項目
- レイアウト崩れ
- JavaScript動作
- ファイルアップロード
- HEIC変換

---

## 15. 今後の拡張計画

### 15.1 短期目標（3ヶ月以内）

#### 15.1.1 セキュリティ強化
- [ ] CSRF対策実装
- [ ] 管理者パスワードのbcryptハッシュ化
- [ ] セッション固定攻撃対策
- [ ] Content-Security-Policy設定

#### 15.1.2 機能改善
- [ ] タグ機能実装（UI実装）
- [ ] 日付範囲フィルタ
- [ ] お気に入り機能
- [ ] コメント機能（メディアごと）

#### 15.1.3 パフォーマンス
- [ ] WebP形式サムネイル対応
- [ ] 遅延読み込み最適化
- [ ] データベースインデックス最適化

### 15.2 中期目標（6ヶ月以内）

#### 15.2.1 ユーザー管理
- [ ] マルチユーザー対応
- [ ] ユーザー登録・ログイン機能
- [ ] ロールベースアクセス制御（管理者・閲覧者）

#### 15.2.2 アルバム機能
- [ ] アルバム作成機能
- [ ] メディアのアルバム振り分け
- [ ] アルバム共有（URL生成）

#### 15.2.3 UI/UX改善
- [ ] カレンダービュー
- [ ] スライドショーモード
- [ ] GPSマップビュー（Google Maps / OpenStreetMap）

### 15.3 長期目標（1年以内）

#### 15.3.1 AI機能
- [ ] 顔認識・人物タグ付け
- [ ] 自動カテゴリ分類（風景・食事・人物等）
- [ ] 類似画像検出

#### 15.3.2 クラウド連携
- [ ] Google Drive / Dropbox バックアップ
- [ ] Google Photos インポート
- [ ] 自動バックアップスケジュール

#### 15.3.3 画像編集
- [ ] トリミング
- [ ] フィルタ適用
- [ ] 明るさ・コントラスト調整
- [ ] 赤目補正

#### 15.3.4 モバイルアプリ
- [ ] iOSアプリ（Swift）
- [ ] Androidアプリ（Kotlin）
- [ ] 自動アップロード機能

### 15.4 技術的改善

#### 15.4.1 リファクタリング
- [ ] OOP化（クラスベース設計）
- [ ] MVCフレームワーク導入（Laravel / Symfony検討）
- [ ] フロントエンド: React / Vue.js 導入検討

#### 15.4.2 テスト
- [ ] PHPUnit導入
- [ ] Selenium E2Eテスト
- [ ] CI/CDパイプライン（GitHub Actions）

#### 15.4.3 インフラ
- [ ] Docker対応
- [ ] Kubernetes対応（大規模運用時）
- [ ] CDN統合（Cloudflare）

---

## 付録A: 用語集

| 用語 | 説明 |
|------|------|
| EXIF | Exchangeable Image File Format。画像ファイルに埋め込まれるメタデータ形式 |
| HEIC | High Efficiency Image Container。Apple製品で使用される画像形式 |
| 逆ジオコーディング | GPS座標から住所を取得する処理 |
| MD5ハッシュ | ファイルの一意性を検証するためのハッシュ値 |
| ffmpeg | 動画・音声処理用のオープンソースソフトウェア |
| GetID3 | PHPメディアファイルメタデータ抽出ライブラリ |
| PDO | PHP Data Objects。データベース抽象化レイヤー |
| XSS | Cross-Site Scripting。Webアプリケーション脆弱性の一種 |
| CSRF | Cross-Site Request Forgery。不正なリクエスト送信攻撃 |
| SQLインジェクション | データベースへの不正なSQL文実行攻撃 |

---

## 付録B: 参考資料

### B.1 公式ドキュメント
- PHP公式マニュアル: https://www.php.net/manual/ja/
- MySQL公式ドキュメント: https://dev.mysql.com/doc/
- Bootstrap 5: https://getbootstrap.com/docs/5.3/
- GetID3: https://github.com/JamesHeinrich/getID3

### B.2 API仕様
- Nominatim API: https://nominatim.org/release-docs/latest/api/Overview/

### B.3 セキュリティガイドライン
- OWASP Top 10: https://owasp.org/www-project-top-ten/
- PHP Security Best Practices: https://www.php.net/manual/ja/security.php

---

## 付録C: 変更履歴

| バージョン | 日付 | 変更内容 | 作成者 |
|-----------|------|---------|-------|
| 1.0 | 2025-01-11 | 初版作成 | Claude Code |

---

## 付録D: 連絡先

- **プロジェクトリポジトリ**: https://github.com/nhashimoto-gm/KidSnaps-GrowthAlbum
- **Issues**: https://github.com/nhashimoto-gm/KidSnaps-GrowthAlbum/issues
- **開発者**: nhashimoto-gm

---

**以上、要件定義書**
