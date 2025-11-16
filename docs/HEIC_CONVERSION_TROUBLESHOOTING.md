# HEIC変換トラブルシューティングガイド

Windows PCでHEIC画像を変換する際のトラブルシューティングガイドです。

## 目次

1. [白ブランク画像になる問題](#白ブランク画像になる問題)
2. [推奨される変換方法の比較](#推奨される変換方法の比較)
3. [各ツールのインストール方法](#各ツールのインストール方法)
4. [その他のトラブルシューティング](#その他のトラブルシューティング)

---

## 白ブランク画像になる問題

### 症状

- PowerShellスクリプト（FFmpeg版）で変換したJPEGが真っ白になる
- ファイルサイズは正常だが、中身が空白

### 原因

FFmpegのビルドがHEIC/HEIFコーデックに完全対応していないことが原因です。特に以下のケースで発生します：

- FFmpeg Essentialsビルドを使用している
- 古いバージョンのFFmpegを使用している
- HEVC/HEVCデコーダーが無効化されているビルド

### 解決方法

以下の3つの代替方法を推奨順に紹介します。

---

## 推奨される変換方法の比較

| 方法 | 難易度 | 信頼性 | 速度 | メモリ | 備考 |
|-----|--------|--------|------|--------|------|
| **Python版（推奨）** | ⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐ | 最も確実、EXIF保持 |
| **ImageMagick版** | ⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐ | 安定動作、実績多数 |
| **FFmpeg Full版** | ⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | 正しいビルドなら高速 |
| **GUIツール** | ⭐ | ⭐⭐⭐⭐ | ⭐⭐ | ⭐⭐⭐ | 手動作業が必要 |

---

## 方法1: Python版スクリプト（最推奨）

### なぜPython版が最適か？

- ✅ **確実性**: pillow-heifライブラリがApple公式のlibheifを使用
- ✅ **EXIF保持**: 撮影日時、GPS等のメタデータを完全保持
- ✅ **透明度対応**: RGBA形式も適切に処理
- ✅ **クロスプラットフォーム**: Windows/Mac/Linux対応
- ✅ **無料**: 全てオープンソース

### インストール手順

#### ステップ1: Pythonのインストール

1. [Python公式サイト](https://www.python.org/downloads/) にアクセス
2. **Download Python 3.12** をクリック
3. インストーラーを実行
4. **重要**: "Add Python to PATH" にチェック
5. "Install Now" をクリック

#### ステップ2: 必要なライブラリのインストール

PowerShellまたはコマンドプロンプトを開き、以下を実行：

```bash
pip install pillow pillow-heif
```

#### ステップ3: スクリプトの実行

```bash
# JPEG変換（品質90%）
python scripts/maintenance/convert_heic_python.py --source "C:\KidSnaps\uploads\images" --format jpeg

# WebP変換（品質85%）
python scripts/maintenance/convert_heic_python.py --source "C:\KidSnaps\uploads\images" --format webp --quality 85

# JPEG + WebP 両方
python scripts/maintenance/convert_heic_python.py --source "C:\KidSnaps\uploads\images" --format both
```

### 使用例

```bash
# 現在のディレクトリのuploadフォルダを変換
python scripts/maintenance/convert_heic_python.py --source ".\uploads\images" --format jpeg

# 高品質で変換（95%）
python scripts/maintenance/convert_heic_python.py --source "C:\KidSnaps\uploads\images" --format jpeg --quality 95

# WebP形式でファイルサイズ削減
python scripts/maintenance/convert_heic_python.py --source "C:\KidSnaps\uploads\images" --format webp --quality 80

# マルチスレッド処理（8スレッド）
python scripts/maintenance/convert_heic_python.py --source "C:\KidSnaps\uploads\images" --format both --threads 8

# カスタムプレフィックス指定
python scripts/maintenance/convert_heic_python.py --source "C:\KidSnaps\uploads\images" --format jpeg --prefix "uploads/images/"
```

### オプション一覧

| オプション | 説明 | デフォルト |
|-----------|------|-----------|
| `--source`, `-s` | 変換対象ディレクトリ（必須） | - |
| `--format`, `-f` | 出力形式（jpeg/webp/both） | jpeg |
| `--quality`, `-q` | 変換品質（1-100） | 90 |
| `--threads`, `-t` | 並列スレッド数 | CPU数の最大4 |
| `--prefix`, `-p` | CSVパスプレフィックス | uploads/images/ |

### 実行結果の例

```
=== HEIC画像変換スクリプト (Python版) ===
対象ディレクトリ: C:\KidSnaps\uploads\images
出力形式: jpeg
品質: 90

変換対象ファイル: 15件

[1/15] 処理中: 2024\photo1.heic
  JPEG: 変換中...
  JPEG: 成功 (3.45 MB)

[2/15] 処理中: 2024\photo2.heic
  JPEG: 変換中...
  JPEG: 成功 (4.12 MB)

...

変換マッピングファイル保存: C:\KidSnaps\uploads\images\conversion_mapping.csv

=== 変換完了 ===
変換成功: 15 ファイル
スキップ: 0 ファイル
エラー: 0 ファイル
```

---

## 方法2: ImageMagick版スクリプト

### インストール手順

#### ステップ1: ImageMagickのインストール

1. [ImageMagick公式ダウンロードページ](https://imagemagick.org/script/download.php#windows) にアクセス
2. **ImageMagick-7.x.x-Q16-HDRI-x64-dll.exe** をダウンロード
3. インストーラーを実行
4. **重要**: "Add application directory to your system path" にチェック
5. インストール完了

#### ステップ2: インストール確認

コマンドプロンプトで確認：

```bash
magick -version
```

正常にバージョン情報が表示されればOK。

#### ステップ3: スクリプトの実行

```bash
convert_heic_imagemagick.bat "C:\KidSnaps\uploads\images"
```

### メリット・デメリット

**メリット:**
- 安定した実績
- 大量のファイルにも対応
- 画像処理のプロフェッショナルツール

**デメリット:**
- インストールサイズが大きい（約100MB）
- 一部のHEIC variant（Live Photosなど）で問題が出る場合あり

---

## 方法3: FFmpeg Full版を使用

### 問題の原因

FFmpeg EssentialsビルドにはHEVCデコーダーが含まれていません。

### 解決策: FFmpeg Fullビルドを使用

#### ステップ1: FFmpeg Fullビルドのダウンロード

1. [gyan.dev FFmpeg Builds](https://www.gyan.dev/ffmpeg/builds/) にアクセス
2. **ffmpeg-release-full.7z** をダウンロード（Essentialsではなく **Full** を選択）
3. 7-Zipで解凍（[7-Zip公式](https://www.7-zip.org/)）
4. `C:\ffmpeg\` に配置

#### ステップ2: PowerShellスクリプトでFullビルドを使用

```powershell
.\scripts\maintenance\convert_heic_windows.ps1 `
    -SourceDir "C:\KidSnaps\uploads\images" `
    -OutputFormat "jpeg" `
    -FFmpegPath "C:\ffmpeg\bin\ffmpeg.exe"
```

#### ステップ3: 変換確認

変換後、画像ビューアーで正しく表示されるか確認してください。

### それでも白ブランクになる場合

FFmpegではなく、**Python版またはImageMagick版**の使用を強く推奨します。

---

## 方法4: GUIツール（手動変換）

少数のファイルなら、GUIツールも選択肢です。

### CopyTrans HEIC for Windows（無料）

1. [CopyTrans HEIC](https://www.copytrans.net/copytransheic/) をダウンロード
2. インストール
3. HEICファイルを右クリック → "Convert to JPEG with CopyTrans"

**メリット:** 簡単、確実
**デメリット:** 大量ファイルには不向き（手動作業）

### iMazing HEIC Converter（無料）

1. [iMazing HEIC Converter](https://imazing.com/heic) をダウンロード
2. ドラッグ&ドロップで変換

**メリット:** バッチ変換対応、EXIF保持
**デメリット:** conversion_mapping.csv は手動作成

---

## 各ツールのインストール方法まとめ

### Python + pillow-heif（推奨）

```bash
# 1. Pythonインストール（公式サイトから）
# 2. ライブラリインストール
pip install pillow pillow-heif

# 3. 確認
python --version
python -c "import pillow_heif; print('OK')"
```

### ImageMagick

```bash
# 1. ImageMagickインストール（公式サイトから）
# 2. 確認
magick -version
```

### FFmpeg Full

```bash
# 1. FFmpeg Fullビルドダウンロード（gyan.dev）
# 2. C:\ffmpeg\ に解凍
# 3. 確認
C:\ffmpeg\bin\ffmpeg.exe -version
```

---

## その他のトラブルシューティング

### 問題: "pip: command not found"

**原因:** PythonのPATHが通っていない

**解決策:**

```bash
# Pythonを再インストール（"Add to PATH"にチェック）
# または、手動でPATHに追加:
# C:\Users\YourName\AppData\Local\Programs\Python\Python312\
# C:\Users\YourName\AppData\Local\Programs\Python\Python312\Scripts\
```

### 問題: "pillow-heif installation failed"

**原因:** Visual C++ Redistributableが不足

**解決策:**

1. [Microsoft Visual C++ Redistributable](https://aka.ms/vs/17/release/vc_redist.x64.exe) をダウンロード
2. インストール
3. 再度 `pip install pillow-heif` を実行

### 問題: 変換が非常に遅い

**原因:** 高解像度画像、または大量ファイル

**解決策:**

1. **品質を下げる**: `--quality 85` など
2. **WebP形式を使用**: より高速
3. **並列処理**: 複数のウィンドウで実行（フォルダごと）

```bash
# 例: 年ごとに分割して並列実行
python convert_heic_python.py --source ".\uploads\images\2023" --format jpeg
python convert_heic_python.py --source ".\uploads\images\2024" --format jpeg
```

### 問題: メモリ不足エラー

**原因:** 大量の高解像度画像

**解決策:**

1. **分割処理**: サブフォルダごとに実行
2. **不要なアプリケーションを閉じる**
3. **WebP形式を使用**: メモリ使用量が少ない

### 問題: EXIF情報が消える

**原因:** 変換ツールがEXIFに対応していない

**解決策:**

- ✅ **Python版を使用**: EXIF保持機能あり
- ✅ **ImageMagick版**: EXIF保持
- ❌ **一部のFFmpegビルド**: EXIF非対応

確認方法（Python版なら保持されます）:

```bash
# 変換前
exiftool photo.heic | grep DateTime

# 変換後
exiftool photo.jpg | grep DateTime
# 同じ日時が表示されればOK
```

---

## 推奨フローチャート

```
変換したいHEIC画像がある
    ↓
Pythonをインストールできる？
    ↓ はい
【Python版】を使用（最推奨）
    ・確実性が高い
    ・EXIF完全保持
    ・大量ファイル対応
    ↓
変換成功
    ↓
サーバーにアップロード


    ↓ いいえ
ImageMagickをインストールできる？
    ↓ はい
【ImageMagick版】を使用
    ・実績豊富
    ・安定動作
    ↓
変換成功
    ↓
サーバーにアップロード


    ↓ いいえ
ファイル数が少ない（<50件）？
    ↓ はい
【GUIツール】を使用
    ・CopyTrans HEIC
    ・iMazing HEIC Converter
    ↓
手動でconversion_mapping.csv作成
    ↓
サーバーにアップロード


    ↓ いいえ
FFmpeg Fullビルドを試す
    ↓
それでも白ブランク？
    ↓
【Python版】の使用を強く推奨
```

---

## まとめ

### 白ブランク問題の解決策

1. **最推奨: Python版スクリプト**
   - `pip install pillow pillow-heif`
   - `python convert_heic_python.py --source "path" --format jpeg`

2. **次点: ImageMagick版**
   - ImageMagickインストール
   - `convert_heic_imagemagick.bat "path"`

3. **FFmpeg Full版**
   - Fullビルドを使用（Essentialsは非推奨）

4. **GUIツール**
   - 少数ファイルなら手軽

## 関連ドキュメント

- **[HEIC_CONVERSION_WORKFLOW.md](./HEIC_CONVERSION_WORKFLOW.md)** - HEIC変換ワークフロー（完全手順）
- **[CLAUDE.md](../CLAUDE.md)** - AI開発ガイド（技術仕様）
- **[README.md](../README.md)** - プロジェクト概要
- **[LOLIPOP_SETUP.md](./LOLIPOP_SETUP.md)** - レンタルサーバーセットアップ

### サポート

問題が解決しない場合：
- GitHub Issues: バグ報告・機能要望
- 上記の関連ドキュメントを参照してください

---

**バージョン:** 1.0
**最終更新:** 2025-01-15
