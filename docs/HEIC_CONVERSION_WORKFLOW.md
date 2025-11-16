# HEIC画像変換ワークフロー（Windows PC → レンタルサーバー）

このガイドでは、Windows PCでHEIC画像をJPEG/WebPに変換し、レンタルサーバーに適用する手順を説明します。

## 目次

1. [概要](#概要)
2. [前提条件](#前提条件)
3. [ステップ1: Windows PCでの変換](#ステップ1-windows-pcでの変換)
4. [ステップ2: ファイルのアップロード](#ステップ2-ファイルのアップロード)
5. [ステップ3: サーバーでの適用](#ステップ3-サーバーでの適用)
6. [トラブルシューティング](#トラブルシューティング)

---

## 概要

### なぜこの方法が必要か？

- レンタルサーバー上でHEIC変換が困難な場合（FFmpeg未対応など）
- ローカルPCで一括変換したほうが高速・安定
- サーバーリソースを節約したい場合

### ワークフロー図

```
┌─────────────────────────────────────────────────────────────────┐
│ ステップ1: Windows PCでの変換                                   │
│                                                                 │
│  uploads/images/*.heic                                          │
│         ↓                                                       │
│  [convert_heic_windows.ps1]                                     │
│         ↓                                                       │
│  uploads/images/*.jpg (+ conversion_mapping.csv)                │
└─────────────────────────────────────────────────────────────────┘
                        ↓ FTP/SFTPアップロード
┌─────────────────────────────────────────────────────────────────┐
│ ステップ2: レンタルサーバーへアップロード                       │
│                                                                 │
│  - *.jpg ファイルを uploads/images/ にアップロード              │
│  - conversion_mapping.csv もアップロード                        │
└─────────────────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────────────────┐
│ ステップ3: サーバーでの適用                                     │
│                                                                 │
│  [apply_converted_heic.php]                                     │
│         ↓                                                       │
│  - データベース更新（file_path, mime_type）                     │
│  - 元のHEICファイル削除（オプション）                           │
│  - サムネイル再生成（オプション）                               │
└─────────────────────────────────────────────────────────────────┘
```

---

## 前提条件

### 変換方法の選択

**重要:** FFmpegで白ブランク画像になる場合は、以下の代替方法を使用してください。

| 方法 | 推奨度 | インストール難易度 | 信頼性 | 備考 |
|-----|--------|-------------------|--------|------|
| **Python版** | ⭐⭐⭐⭐⭐ | やや易 | 非常に高い | **最推奨**: EXIF保持、確実 |
| **ImageMagick版** | ⭐⭐⭐⭐ | 易 | 高い | 実績豊富、安定 |
| **FFmpeg版** | ⭐⭐⭐ | 易 | 中（ビルド依存） | Full版なら動作 |

**白ブランク問題が発生した場合:**
- まず **Python版** を試してください（最も確実）
- 詳細は `docs/HEIC_CONVERSION_TROUBLESHOOTING.md` を参照

### Windows PC側（共通）

1. **サーバーからダウンロードした uploads/images ディレクトリ**
   - FTPクライアント（FileZilla、WinSCP等）でダウンロード
   - 例: `C:\KidSnaps\uploads\images\`

### 方法別の前提条件

#### Python版（最推奨）

1. **Python 3.8以降**
   - ダウンロード: https://www.python.org/downloads/
   - **重要**: インストール時に "Add Python to PATH" にチェック

2. **必要なライブラリ**
   ```bash
   pip install pillow pillow-heif
   ```

#### ImageMagick版

1. **ImageMagick for Windows**
   - ダウンロード: https://imagemagick.org/script/download.php#windows
   - **重要**: インストール時に "Add to system path" にチェック

#### FFmpeg版（PowerShell）

1. **PowerShell 5.0以降**
   - Windows 10/11にはデフォルトでインストール済み
   - 確認方法: `$PSVersionTable.PSVersion`

2. **FFmpeg（Full版推奨）**
   - ダウンロード: https://www.gyan.dev/ffmpeg/builds/
   - **ffmpeg-release-full.7z** を選択（Essentialsは非推奨）
   - 解凍して任意のフォルダに配置（例: `C:\ffmpeg\`）

### サーバー側

1. **PHP 7.4以降**
2. **SSH/ターミナルアクセス** (PHPスクリプト実行のため)
3. **データベースアクセス権限**

---

## ステップ1: Windows PCでの変換

### 1.1 uploads/images ディレクトリのダウンロード

FTPクライアントを使用して、サーバーの `uploads/images/` ディレクトリをローカルにダウンロードします。

例: `C:\KidSnaps\uploads\images\`

### 1.2 変換方法を選択

以下のいずれかの方法で変換します。**白ブランク問題が発生する場合はPython版を使用してください。**

---

### 方法A: Python版（最推奨）

**最も確実で、EXIF情報も完全に保持されます。**

#### スクリプトの準備

プロジェクトの `scripts/maintenance/convert_heic_python.py` をWindows PCにコピーします。

#### 実行方法

コマンドプロンプトまたはPowerShellで実行：

```bash
# JPEG変換（品質90%）
python scripts/maintenance/convert_heic_python.py --source "C:\KidSnaps\uploads\images" --format jpeg

# WebP変換（品質85%）
python scripts/maintenance/convert_heic_python.py --source "C:\KidSnaps\uploads\images" --format webp --quality 85

# JPEG + WebP 両方変換
python scripts/maintenance/convert_heic_python.py --source "C:\KidSnaps\uploads\images" --format both
```

#### オプション

| オプション | 説明 | デフォルト |
|-----------|------|-----------|
| `--source`, `-s` | 変換対象ディレクトリ（必須） | - |
| `--format`, `-f` | 出力形式（jpeg/webp/both） | jpeg |
| `--quality`, `-q` | 変換品質（1-100） | 90 |
| `--threads`, `-t` | 並列スレッド数 | CPU数の最大4 |
| `--prefix`, `-p` | CSVファイルパスのプレフィックス | uploads/images/ |

---

### 方法B: ImageMagick版

**実績豊富で安定した動作が期待できます。**

#### スクリプトの準備

プロジェクトの `scripts/maintenance/convert_heic_imagemagick.bat` をWindows PCにコピーします。

#### 実行方法

コマンドプロンプトで実行：

```bash
convert_heic_imagemagick.bat "C:\KidSnaps\uploads\images"
```

品質設定を変更したい場合は、バッチファイル内の `set "QUALITY=90"` を編集してください。

---

### 方法C: FFmpeg版（PowerShell）

**FFmpeg Full版を使用する場合に有効です。白ブランクになる場合は他の方法を使用してください。**

#### スクリプトの準備

プロジェクトの `scripts/maintenance/convert_heic_windows.ps1` をWindows PCにコピーします。

#### 実行方法

PowerShellを起動し、以下のコマンドを実行します。

```powershell
cd C:\path\to\scripts

.\convert_heic_windows.ps1 -SourceDir "C:\KidSnaps\uploads\images" -OutputFormat "jpeg"
```

#### オプション付き実行例

```powershell
# JPEG変換（品質90%）
.\convert_heic_windows.ps1 -SourceDir "C:\KidSnaps\uploads\images" -OutputFormat "jpeg" -Quality 90

# WebP変換（品質85%）
.\convert_heic_windows.ps1 -SourceDir "C:\KidSnaps\uploads\images" -OutputFormat "webp" -Quality 85

# JPEG + WebP 両方変換
.\convert_heic_windows.ps1 -SourceDir "C:\KidSnaps\uploads\images" -OutputFormat "both"

# FFmpegパスを手動指定
.\convert_heic_windows.ps1 `
    -SourceDir "C:\KidSnaps\uploads\images" `
    -OutputFormat "jpeg" `
    -FFmpegPath "C:\ffmpeg\bin\ffmpeg.exe"

# パスプレフィックスを指定（カスタマイズ）
.\convert_heic_windows.ps1 `
    -SourceDir "C:\KidSnaps\uploads\images" `
    -OutputFormat "both" `
    -PathPrefix "uploads/images/"
```

#### オプション

| オプション | 説明 | デフォルト |
|-----------|------|-----------|
| `-SourceDir` | 変換対象ディレクトリ（必須） | - |
| `-OutputFormat` | 出力形式（jpeg/webp/both） | jpeg |
| `-Quality` | 変換品質（1-100） | 90 |
| `-FFmpegPath` | FFmpegの実行ファイルパス | ffmpeg |
| `-PathPrefix` | CSVファイルパスのプレフィックス | uploads/images/ |

### 1.4 実行結果の確認

スクリプトが完了すると、以下が生成されます：

```
C:\KidSnaps\uploads\images\
├── photo1.heic
├── photo1.jpg          ← 新規作成
├── photo2.heic
├── photo2.jpg          ← 新規作成
└── conversion_mapping.csv  ← 変換マッピングファイル
```

#### conversion_mapping.csv の内容例

```csv
original_filename,original_path,jpeg_path,webp_path,status
photo1.heic,"uploads/images/subfolder/photo1.heic","uploads/images/subfolder/photo1.jpg","",success
photo2.heic,"uploads/images/photo2.heic","uploads/images/photo2.jpg","uploads/images/photo2.webp",success
```

**注意:**
- すべてのパスには `uploads/images/` プレフィックスが含まれます（デフォルト設定）
- パス区切りは `/` （スラッシュ）に統一されます
- Windows形式の `\` は自動的に `/` に変換されます

---

## ステップ2: ファイルのアップロード

### 2.1 変換したファイルをサーバーにアップロード

FTPクライアントを使用して、以下をサーバーにアップロードします：

1. **変換された画像ファイル（*.jpg または *.webp）**
   - アップロード先: サーバーの `uploads/images/`
   - **重要**: ディレクトリ構造を保持してアップロード

2. **conversion_mapping.csv**
   - アップロード先: サーバーの `uploads/images/` または任意の場所

### 2.2 FTPアップロード例（FileZilla）

1. FileZillaでサーバーに接続
2. ローカル側: `C:\KidSnaps\uploads\images\`
3. リモート側: `/home/user/uploads/images/`
4. 変換された `.jpg` ファイルと `conversion_mapping.csv` を選択してアップロード
5. ディレクトリ構造を保持するため、フォルダごとアップロード推奨

---

## ステップ3: サーバーでの適用

### 3.1 SSHでサーバーに接続

```bash
ssh user@your-server.com
cd /path/to/KidSnaps-GrowthAlbum
```

### 3.2 ドライランで確認（推奨）

実際の変更を行う前に、プレビューモードで確認します。

```bash
php scripts/maintenance/apply_converted_heic.php \
    --csv=uploads/images/conversion_mapping.csv \
    --dry-run
```

**出力例:**

```
=== HEIC変換ファイル適用スクリプト ===

CSVファイル: uploads/images/conversion_mapping.csv
元のHEIC削除: いいえ
サムネイル再生成: いいえ
ドライラン: はい（変更なし）

変換マッピング読み込み: 25件

[1/25] photo1.heic
  DB ID: 123
  変換形式: JPEG
  変換ファイル: photo1.jpg
  [ドライラン] データベースを更新します

...

=== 処理完了 ===
*** ドライランモード（変更なし） ***
データベース更新: 25件
スキップ: 0件
エラー: 0件

実際に適用するには、--dry-run オプションを外して再実行してください。
```

### 3.3 実際に適用

ドライランで問題がなければ、実際に適用します。

```bash
php scripts/maintenance/apply_converted_heic.php \
    --csv=uploads/images/conversion_mapping.csv \
    --delete-heic
```

**オプション説明:**

| オプション | 説明 |
|-----------|------|
| `--csv=PATH` | 変換マッピングCSVファイルのパス（必須） |
| `--delete-heic` | 元のHEICファイルを削除する |
| `--generate-thumbnails` | サムネイルを再生成する |
| `--dry-run` | 実際の変更を行わず、プレビューのみ |

### 3.4 サムネイルも再生成する場合

```bash
php scripts/maintenance/apply_converted_heic.php \
    --csv=uploads/images/conversion_mapping.csv \
    --delete-heic \
    --generate-thumbnails
```

---

## トラブルシューティング

### Windows側

#### 問題: "FFmpegが見つかりません"

**原因:** FFmpegがインストールされていない、またはPATHが通っていない

**解決策:**

1. FFmpegをダウンロード・インストール
2. `-FFmpegPath` オプションで明示的にパス指定

```powershell
.\convert_heic_windows.ps1 `
    -SourceDir "C:\KidSnaps\uploads\images" `
    -OutputFormat "jpeg" `
    -FFmpegPath "C:\ffmpeg\bin\ffmpeg.exe"
```

#### 問題: "スクリプトの実行が無効になっています"

**原因:** PowerShellの実行ポリシー制限

**解決策:** 実行ポリシーを一時的に変更

```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope Process
```

#### 問題: 変換が失敗する

**原因:** ファイルが破損している、またはHEIC形式でない

**解決策:**

1. ファイルを別のツールで開けるか確認
2. スクリプト出力のエラーメッセージを確認

### サーバー側

#### 問題: "CSVファイルが見つかりません"

**原因:** パス指定が間違っている

**解決策:** 絶対パスまたは正しい相対パスを指定

```bash
# 絶対パス
php scripts/maintenance/apply_converted_heic.php \
    --csv=/home/user/KidSnaps/uploads/images/conversion_mapping.csv

# 相対パス（プロジェクトルートから）
php scripts/maintenance/apply_converted_heic.php \
    --csv=uploads/images/conversion_mapping.csv
```

#### 問題: "データベースにレコードが見つかりません"

**原因:** ファイル名がデータベースと一致しない

**解決策:**

1. データベースの `media_files` テーブルを確認

```sql
SELECT id, filename, stored_filename, file_path
FROM media_files
WHERE file_type = 'image'
AND (mime_type LIKE '%heic%' OR file_path LIKE '%.heic')
LIMIT 10;
```

2. CSVファイルの `original_filename` とDBの `stored_filename` または `filename` が一致するか確認

#### 問題: "変換ファイルが見つかりません"

**原因1:** FTPアップロードが正しく行われていない

**解決策:**

1. サーバー上でファイルの存在を確認

```bash
ls -la uploads/images/*.jpg
```

2. ディレクトリ構造が正しいか確認
3. ファイルパーミッションを確認（644推奨）

```bash
chmod 644 uploads/images/*.jpg
```

**原因2:** CSVファイルのパスに `uploads/images/` プレフィックスが含まれていない

**解決策:** CSVパス修正スクリプトを使用

```bash
# CSVパスを修正（ドライラン）
php scripts/maintenance/fix_csv_paths.php \
    --csv=uploads/images/conversion_mapping.csv \
    --dry-run

# 実際に修正
php scripts/maintenance/fix_csv_paths.php \
    --csv=uploads/images/conversion_mapping.csv
```

このスクリプトは、古いバージョンのスクリプトで生成されたCSVに `uploads/images/` プレフィックスを自動追加します。

---

## ベストプラクティス

### 1. バックアップを取る

作業前に必ずバックアップを取りましょう。

```bash
# データベースバックアップ
mysqldump -u user -p kidsnaps > backup_$(date +%Y%m%d).sql

# ファイルバックアップ
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz uploads/
```

### 2. 段階的に実行

大量のファイルがある場合は、小分けにして実行することを推奨します。

```powershell
# サブフォルダごとに変換
.\convert_heic_windows.ps1 -SourceDir "C:\KidSnaps\uploads\images\2024\01"
.\convert_heic_windows.ps1 -SourceDir "C:\KidSnaps\uploads\images\2024\02"
```

### 3. 必ず --dry-run で確認

サーバー側スクリプトは必ず `--dry-run` で確認してから実行しましょう。

### 4. 変換品質の調整

用途に応じて品質を調整します。

- **高品質保存**: `-Quality 95`
- **標準**: `-Quality 90` (デフォルト)
- **Web表示用**: `-Quality 85`
- **ファイルサイズ優先**: `-Quality 80`

### 5. WebP形式の活用

ファイルサイズを大幅に削減したい場合は、WebP形式も検討してください。

```powershell
.\convert_heic_windows.ps1 -SourceDir "C:\KidSnaps\uploads\images" -OutputFormat "webp" -Quality 85
```

---

## FAQ

### Q1: 元のHEICファイルはいつ削除すべきですか？

A: サーバー側スクリプトで `--delete-heic` オプションを使用すると自動削除されます。ただし、以下を確認してから削除することを推奨します：

1. 変換が正常に完了している
2. 画像が正しく表示される
3. バックアップを取っている

### Q2: JPEGとWebPはどちらが良いですか？

A: 用途によります。

- **JPEG**: 互換性が高い、ブラウザサポート100%
- **WebP**: ファイルサイズが小さい（25-35%削減）、最新ブラウザ対応

両方生成する場合は `-OutputFormat "both"` を使用してください。

### Q3: サムネイルは必ず再生成すべきですか？

A: 以下の場合は再生成を推奨します：

- 既存のサムネイルがHEICから生成されている
- サムネイルが壊れている・表示されない
- より高品質なサムネイルが必要

再生成しない場合は、`--generate-thumbnails` オプションを省略してください。

### Q4: CSVファイルを手動で編集できますか？

A: 可能ですが、以下に注意してください：

- UTF-8エンコーディングで保存
- カンマ区切りCSV形式を保持
- ヘッダー行を削除しない
- パスは相対パスまたは絶対パス

### Q5: 大量のファイル（1000件以上）を処理できますか？

A: 可能ですが、以下を推奨します：

1. 段階的に処理（100-200件ずつ）
2. サーバー側のPHPタイムアウト設定を延長

```bash
php -d max_execution_time=600 scripts/maintenance/apply_converted_heic.php ...
```

3. ログを確認しながら進める

---

## まとめ

このワークフローを使用することで、レンタルサーバーでHEIC変換が困難な場合でも、Windows PCで効率的に変換し、サーバーに適用できます。

**推奨手順:**

1. ✅ バックアップを取る
2. ✅ Windows PCでHEIC→JPEG変換
3. ✅ ファイルをサーバーにアップロード
4. ✅ `--dry-run` で確認
5. ✅ 実際に適用
6. ✅ ブラウザで動作確認

問題が発生した場合は、トラブルシューティングセクションを参照してください。

---

## 関連ドキュメント

- **[HEIC_CONVERSION_TROUBLESHOOTING.md](./HEIC_CONVERSION_TROUBLESHOOTING.md)** - HEIC変換トラブルシューティング
- **[LOLIPOP_SETUP.md](./LOLIPOP_SETUP.md)** - レンタルサーバーセットアップガイド
- **[CLAUDE.md](../CLAUDE.md)** - AI開発ガイド（技術仕様）
- **[README.md](../README.md)** - プロジェクト概要
- **Scripts:**
  - `scripts/maintenance/convert_heic_windows.ps1` - Windows変換スクリプト
  - `scripts/maintenance/convert_heic_python.py` - Python変換スクリプト（推奨）
  - `scripts/maintenance/apply_converted_heic.php` - サーバー適用スクリプト
  - `scripts/maintenance/convert_existing_heic.php` - サーバー側直接変換

**バージョン:** 1.0
**最終更新:** 2025-01-15
