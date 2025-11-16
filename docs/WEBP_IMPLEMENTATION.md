# WebP完全実装ガイド

## 概要

KidSnaps-GrowthAlbumに完全なWebP対応を実装しました。WebP形式は、JPEG/PNGと比較して約25-35%のファイルサイズ削減を実現し、ページ読み込み速度を向上させます。

## 実装内容

### 1. データベーススキーマの更新

新しいカラム `thumbnail_webp_path` を `media_files` テーブルに追加しました。

```bash
# データベースマイグレーションを実行
mysql -u root -p kidsnaps_growth_album < sql/add_webp_thumbnail.sql
```

### 2. 自動WebP生成

#### 2.1 新規アップロード時

- `upload.php` - 通常のアップロード処理
- `lib/finalize_upload.php` - チャンク分割アップロードの最終処理

新しく画像または動画をアップロードすると、自動的に以下が生成されます：
- **画像の場合**:
  - JPEGサムネイル（320px幅、品質85%）
  - WebPサムネイル（320px幅、品質85%）
- **動画の場合**:
  - JPEGサムネイル（ブラウザで生成されたサムネイル）
  - WebPサムネイル（JPEGから自動変換）

両方のファイルがデータベースに記録されます。

### 3. フロントエンド表示

`index.php` では、`<picture>` タグを使用してWebPをフォールバック付きで表示します：

```html
<picture>
    <source srcset="uploads/thumbnails/thumb_xxx.webp" type="image/webp">
    <img src="uploads/thumbnails/thumb_xxx.jpg" alt="..." loading="lazy">
</picture>
```

ブラウザがWebPに対応している場合はWebP版を、対応していない場合はJPEG版を表示します。

### 4. 既存サムネイルの変換

既存のサムネイルをWebP形式に変換するバッチスクリプトを用意しました。

#### 使用方法

```bash
# すべてのサムネイルを変換
php scripts/maintenance/convert_thumbnails_to_webp.php --all

# WebP版がないサムネイルのみ変換（デフォルト）
php scripts/maintenance/convert_thumbnails_to_webp.php --missing

# 実際には変換せず、処理内容のみ表示（ドライラン）
php scripts/maintenance/convert_thumbnails_to_webp.php --dry-run

# WebP品質を指定（デフォルト: 85）
php scripts/maintenance/convert_thumbnails_to_webp.php --quality=90
```

#### 実行例

```bash
$ php scripts/maintenance/convert_thumbnails_to_webp.php --missing
===========================================
サムネイルWebP変換ツール
===========================================
モード: 未変換のみ
実行モード: 実行
WebP品質: 85
===========================================

変換対象: 150件のサムネイル

[1/150] (1%) [画像] 変換中: IMG_0001.jpg ... 成功 (JPEG: 42.5 KB → WebP: 28.3 KB, 33.4% 削減)
[2/150] (1%) [画像] 変換中: IMG_0002.jpg ... 成功 (JPEG: 38.2 KB → WebP: 26.1 KB, 31.7% 削減)
[3/150] (2%) [動画] 変換中: VID_0001.mp4 ... 成功 (JPEG: 35.8 KB → WebP: 24.2 KB, 32.4% 削減)
...
[150/150] (100%) [画像] 変換中: IMG_0150.jpg ... 成功 (JPEG: 45.1 KB → WebP: 29.8 KB, 33.9% 削減)

===========================================
変換完了
===========================================
処理件数: 150件
変換成功: 150件
スキップ: 0件
エラー: 0件
===========================================
```

**注意**: このスクリプトは画像と動画の両方のサムネイルを変換します。

## パフォーマンス向上

### ファイルサイズ削減

- **平均削減率**: 25-35%
- **例**: 40 KBのJPEGサムネイル → 28 KBのWebPサムネイル（30%削減）

### ページ読み込み速度

- サムネイル一覧ページ（12枚表示）の場合：
  - JPEG: 12枚 × 40 KB = 480 KB
  - WebP: 12枚 × 28 KB = 336 KB
  - **削減**: 144 KB（30%）

モバイルネットワークでは、この削減により体感的な読み込み速度が大幅に向上します。

## 技術詳細

### WebP生成関数

`includes/image_thumbnail_helper.php` に実装されています：

- `generateWebPThumbnail()` - WebP形式のサムネイルを生成
- `isWebPSupported()` - WebPサポートを確認

### 対応ブラウザ

WebPは以下のブラウザで対応されています：

- Chrome 23+
- Firefox 65+
- Edge 18+
- Safari 14+ (macOS 11+, iOS 14+)
- Opera 12.1+

古いブラウザでは自動的にJPEG版が表示されます（フォールバック）。

## トラブルシューティング

### WebPが生成されない

**問題**: WebPサムネイルが生成されない

**解決策**:
1. PHPのGDライブラリにWebPサポートがあるか確認：
   ```bash
   php -r "var_dump(function_exists('imagewebp'));"
   ```
   → `bool(true)` が表示されればOK

2. GDライブラリを再インストール（Ubuntu/Debian）：
   ```bash
   sudo apt-get install php-gd libwebp-dev
   sudo service apache2 restart  # または php-fpm restart
   ```

### データベースエラー

**問題**: `Unknown column 'thumbnail_webp_path'` エラー

**解決策**: データベースマイグレーションを実行してください：
```bash
mysql -u root -p kidsnaps_growth_album < sql/add_webp_thumbnail.sql
```

### 既存の画像がWebPで表示されない

**問題**: 新規アップロード画像はWebPで表示されるが、既存画像がJPEGのまま

**解決策**: バッチ変換スクリプトを実行してください：
```bash
php scripts/maintenance/convert_thumbnails_to_webp.php --all
```

## まとめ

WebP完全対応により、以下の利点が得られます：

1. **ファイルサイズ削減**: 平均30%のサイズ削減
2. **読み込み速度向上**: 特にモバイルネットワークで効果的
3. **自動フォールバック**: 非対応ブラウザでも問題なく動作
4. **既存画像の変換**: バッチスクリプトで簡単に変換可能

これにより、ユーザーエクスペリエンスが大幅に向上します。
