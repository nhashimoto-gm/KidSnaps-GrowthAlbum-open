# ファイルハッシュによる重複チェック機能 - セットアップガイド

## 概要

この機能は、ファイルのMD5ハッシュを使用して重複を検出します。
アップロード時点で既存ファイルとの重複をチェックし、警告を表示します。

## セットアップ手順

### 1. データベースにfile_hashカラムを追加

以下のSQLを実行してください：

```sql
ALTER TABLE media_files
ADD COLUMN file_hash VARCHAR(32) NULL AFTER file_size,
ADD INDEX idx_file_hash (file_hash);
```

または、提供されているマイグレーションファイルを使用：

```bash
mysql -u your_user -p your_database < migrations/add_file_hash_column.sql
```

### 2. 既存ファイルのハッシュを計算

既にアップロード済みのファイルのハッシュを計算します：

```bash
php update_file_hashes.php
```

進捗バーが表示され、すべてのファイルのハッシュが計算されます。

オプション：
- `--force`: 既にハッシュが設定されているファイルも再計算

### 3. 動作確認

1. ブラウザで `index.php` にアクセス
2. 「メディアをアップロード」をクリック
3. 既にアップロード済みのファイルを選択
4. 自動的に重複チェックが行われ、警告が表示されます
5. 重複ファイルは自動的に除外されます

## 機能詳細

### クライアント側（JavaScript）

**ファイル**: `js/duplicate-checker.js`

- ファイル選択時に自動的にMD5ハッシュを計算
- 計算したハッシュをサーバーに送信して重複チェック
- 重複が見つかった場合、警告を表示し自動的に除外
- 進捗バーで計算状況を表示

**使用ライブラリ**: SparkMD5 (CDNから読み込み)

### サーバー側（PHP）

#### 1. 重複チェックAPI

**ファイル**: `api/check_duplicate.php`

- エンドポイント: `POST /api/check_duplicate.php`
- リクエスト:
  ```json
  {
    "hash": "md5ハッシュ値",
    "filename": "ファイル名（オプション）",
    "filesize": ファイルサイズ（オプション）
  }
  ```
- レスポンス:
  ```json
  {
    "isDuplicate": true|false,
    "existing": {
      "id": 123,
      "filename": "既存ファイル名",
      "file_type": "image",
      "upload_date": "2025-01-08 12:00:00"
    },
    "message": "メッセージ"
  }
  ```

#### 2. アップロード処理

**ファイル**: `upload.php`, `bulk_import.php`

- ファイル保存後、自動的にMD5ハッシュを計算
- データベースに `file_hash` として保存
- 次回以降の重複チェックに使用

## パフォーマンス

### ハッシュ計算速度

- 小さいファイル（< 10MB）: 即座
- 中サイズファイル（10-100MB）: 1-3秒
- 大きいファイル（> 100MB）: 3-10秒

### データベースクエリ

- `file_hash` カラムにインデックスを作成済み
- 重複チェックは高速（< 10ms）

## 既存データの移行

### 全ファイルのハッシュを更新

```bash
php update_file_hashes.php
```

### 特定のファイルのみ更新（file_hashがNULLのもの）

```bash
php update_file_hashes.php
```
（デフォルトでNULLのみ処理します）

### 全ファイルを強制再計算

```bash
php update_file_hashes.php --force
```

## 重複削除

### 方法1: ファイル名 + サイズ（従来の方法）

```bash
php remove_duplicates_v2.php --method=filename --dry-run
php remove_duplicates_v2.php --method=filename
```

### 方法2: ファイルハッシュ（最も正確）

```bash
php remove_duplicates_v2.php --method=hash --dry-run
php remove_duplicates_v2.php --method=hash
```

ハッシュ方式は以下の利点があります：
- ファイル名が変わっていても検出可能
- リネーム後の重複も検出可能
- ファイル内容が完全に一致するもののみ削除

## トラブルシューティング

### 1. 重複チェックが動作しない

**原因**: SparkMD5ライブラリが読み込まれていない

**確認方法**: ブラウザのコンソールで
```javascript
typeof SparkMD5
```

**解決方法**: `includes/footer.php` にSparkMD5のCDNが追加されているか確認

### 2. 「file_hashカラムが存在しません」エラー

**原因**: データベースマイグレーションが実行されていない

**解決方法**:
```sql
ALTER TABLE media_files ADD COLUMN file_hash VARCHAR(32) NULL AFTER file_size;
```

### 3. 既存ファイルの重複が検出されない

**原因**: 既存ファイルのハッシュが計算されていない

**解決方法**:
```bash
php update_file_hashes.php
```

### 4. ハッシュ計算が遅い

**原因**: ファイルサイズが大きい

**対処方法**:
- 進捗バーで進行状況を確認
- サーバー側でバックグラウンド処理も検討可能

## セキュリティ考慮事項

1. **MD5の使用について**
   - セキュリティ用途ではなく、重複検出のみに使用
   - SHA-256等への変更も可能

2. **APIアクセス制限**
   - 現在は認証なし（内部使用のため）
   - 必要に応じてセッション認証を追加可能

## 今後の拡張案

1. **段階的ハッシュ計算**
   - 大きいファイルの場合、部分ハッシュで事前チェック

2. **キャッシュ機能**
   - 計算済みハッシュをブラウザにキャッシュ

3. **重複ファイルの自動マージ**
   - 同じファイルの場合、メタデータを統合

4. **より高度な重複検出**
   - 画像の視覚的類似度チェック
   - 動画のフレーム比較

## 関連ファイル

- `migrations/add_file_hash_column.sql` - データベーススキーマ変更
- `update_file_hashes.php` - 既存ファイルのハッシュ更新
- `api/check_duplicate.php` - 重複チェックAPI
- `js/duplicate-checker.js` - クライアント側重複チェック
- `upload.php` - アップロード処理（ハッシュ計算含む）
- `bulk_import.php` - 一括インポート（ハッシュ計算含む）
- `remove_duplicates_v2.php` - 重複削除スクリプト
- `analyze_duplicates.php` - 重複分析スクリプト
