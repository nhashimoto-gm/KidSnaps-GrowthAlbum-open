# ZIPアルバムインポート機能 - 使用方法

## 概要

KidSnaps Growth Albumに、ZIPファイルから画像・動画をまとめてインポートし、アルバムとして管理する機能を追加しました。

## 主な機能

- ✅ **ZIPファイル分割アップロード**: 大きなZIPファイル（最大12GB）も安定してアップロード
- ✅ **自動展開とメディア抽出**: ZIPを自動展開し、対応するメディアファイルを検出
- ✅ **アルバム自動作成**: インポートしたメディアをアルバムとして整理
- ✅ **既存機能との完全互換**: 既存のギャラリー機能に影響なし
- ✅ **重複チェック**: 既にアップロード済みのファイルは自動的にスキップ
- ✅ **EXIF情報抽出**: 撮影日時、GPS情報などを自動抽出
- ✅ **サムネイル自動生成**: WebP形式も含めて自動生成

## セットアップ

### 1. データベーステーブルの作成

以下のSQLファイルを実行して、アルバム機能用のテーブルを作成してください：

```bash
mysql -u [username] -p [database] < sql/create_album_tables.sql
```

または、phpMyAdminなどから `sql/create_album_tables.sql` の内容を実行してください。

作成されるテーブル：
- `albums` - アルバム情報
- `album_media_relations` - アルバムとメディアの関連
- `zip_import_history` - ZIPインポート履歴

### 2. ファイルパーミッションの確認

以下のディレクトリに書き込み権限があることを確認してください：

```bash
chmod 755 uploads/temp
chmod 755 uploads/images
chmod 755 uploads/videos
chmod 755 uploads/thumbnails
```

### 3. PHP設定の確認（推奨）

`php.ini` で以下の設定を確認してください：

```ini
upload_max_filesize = 500M
post_max_size = 500M
max_execution_time = 600
memory_limit = 1024M
```

## 使用方法

### ZIPファイルの準備

1. 画像・動画ファイルをフォルダに入れる
2. フォルダを右クリックして「圧縮」を選択
   - **Windows**: 「送る」→「圧縮(zip形式)フォルダー」
   - **Mac**: 「"フォルダ名"を圧縮」

### アルバムのインポート

1. ブラウザで `album_upload.php` にアクセス
   - URL例: `http://your-domain.com/album_upload.php`

2. ZIPファイルを選択

3. アルバムタイトルと説明を入力（オプション）
   - 未入力の場合、ZIPファイル名がタイトルになります

4. 「アップロード開始」ボタンをクリック

5. 進捗バーが表示され、処理が完了すると自動的にアルバム詳細ページへ移動します

### アルバムの閲覧

1. ブラウザで `albums.php` にアクセス
   - URL例: `http://your-domain.com/albums.php`

2. アルバム一覧から目的のアルバムをクリック

3. アルバム内のメディアファイルが表示されます

## ファイル構成

### 新規追加ファイル（既存ファイルへの変更なし）

```
/KidSnaps-GrowthAlbum/
├── albums.php                          # アルバム一覧ページ
├── album_detail.php                    # アルバム詳細ページ
├── album_upload.php                    # ZIPアップロード専用ページ
├── lib/
│   ├── zip_import.php                  # ZIP展開・インポート処理
│   ├── album_processor.php             # アルバム作成・管理処理
│   └── zip_import_progress.php         # 進捗確認API
├── assets/
│   └── js/
│       └── zip-upload.js               # ZIPアップロードJavaScript
├── sql/
│   └── create_album_tables.sql         # アルバムテーブル作成SQL
└── ALBUM_FEATURE_README.md             # この説明書
```

### 既存ファイルの利用

以下の既存ファイルを**変更せずに**利用しています：

- `lib/chunk_upload.php` - チャンク分割アップロード
- `config/database.php` - データベース接続
- `includes/heic_converter.php` - HEIC変換
- `includes/image_thumbnail_helper.php` - サムネイル生成
- `includes/video_metadata_helper.php` - 動画メタデータ抽出

## 対応ファイル形式

### 画像
- JPEG (.jpg, .jpeg)
- PNG (.png)
- GIF (.gif)
- WebP (.webp)
- HEIC/HEIF (.heic, .heif) ※自動的にJPEGに変換

### 動画
- MP4 (.mp4)
- QuickTime (.mov)
- AVI (.avi)
- MPEG (.mpeg)

## 制限事項

- ZIPファイルサイズ: 最大12GB
- 各メディアファイル: 最大500MB
- ZIP展開後の総サイズ: 最大20GB（ZIPボム対策）
- 処理時間: 最大10分（タイムアウト設定）

## トラブルシューティング

### ZIPアップロードが途中で止まる

- サーバーのPHP設定を確認してください（`upload_max_filesize`, `post_max_size`）
- ネットワーク接続が安定しているか確認してください

### メディアファイルが一部インポートされない

- ファイル形式が対応しているか確認してください
- ファイルサイズが500MB以下か確認してください
- 重複ファイルは自動的にスキップされます

### データベースエラーが発生する

- `sql/create_album_tables.sql` が正しく実行されているか確認してください
- データベース接続情報が正しいか確認してください（`config/database.php`）

### ログの確認

エラーが発生した場合、以下のログファイルを確認してください：

```
uploads/temp/zip_import.log
uploads/temp/upload_debug.log
```

## セキュリティ対策

本機能には以下のセキュリティ対策が実装されています：

- ✅ ZIPボム対策（展開サイズ制限）
- ✅ パストラバーサル対策（ファイル名サニタイズ）
- ✅ 実行ファイル除外（.php, .exe, .shなど）
- ✅ ファイルタイプ検証（MIMEタイプ + 拡張子チェック）
- ✅ 重複チェック（MD5ハッシュ）
- ✅ アップロードディレクトリの保護（.htaccess）

## 今後の拡張案

- [ ] 進捗表示のリアルタイム更新（WebSocket）
- [ ] アルバム編集機能（タイトル、説明、カバー画像の変更）
- [ ] アルバムからメディアを削除する機能
- [ ] アルバムの共有機能
- [ ] アルバムのエクスポート（ZIP出力）

## サポート

問題が発生した場合は、GitHubのIssueに報告してください。

---

**注意**: この機能は既存のギャラリー機能（`index.php`）に影響を与えません。独立した機能として動作します。
