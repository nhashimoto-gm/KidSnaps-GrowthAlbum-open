# パフォーマンス改善 クイックスタートガイド

**所要時間: 30分** | **難易度: 初級**

このガイドでは、最も効果が高く、簡単に実装できる施策を厳選して紹介します。

---

## 🎯 このガイドで実現すること

- ✅ ページ表示速度を **30-40%向上**
- ✅ データ転送量を **60-81%削減**
- ✅ 検索速度を **70-90%向上**

**所要時間:** 合計30分（各施策10分程度）

---

## 📋 事前準備

### 必要なもの

- ✅ Node.js がインストールされたPC（開発用）
- ✅ サーバーへのFTPまたはSSHアクセス
- ✅ phpMyAdmin または MySQLコマンドラインアクセス

### Node.jsのインストール確認

```bash
# ターミナルまたはコマンドプロンプトで実行
node --version
npm --version

# バージョンが表示されればOK
# v14.x.x 以上
# 9.x.x 以上
```

**未インストールの場合:**
- Windows/Mac: https://nodejs.org/ からダウンロード
- Linux: `sudo apt install nodejs npm` (Ubuntu/Debian)

---

## 🚀 施策1: JavaScript/CSSのminify化（10分）

### 効果: ページ表示速度 30-40%向上

### 手順

#### 1. ローカルでpackage.jsonを作成

プロジェクトフォルダで以下を実行:

```bash
cd /path/to/KidSnaps-GrowthAlbum

# package.json作成（コピー＆ペースト）
cat > package.json << 'EOF'
{
  "name": "kidsnaps-growth-album",
  "version": "1.0.0",
  "scripts": {
    "minify:all": "terser assets/js/script.js -c -m -o assets/js/script.min.js && csso assets/css/style.css -o assets/css/style.min.css"
  },
  "devDependencies": {
    "terser": "^5.19.0",
    "csso-cli": "^4.0.2"
  }
}
EOF

# 依存パッケージインストール（初回のみ）
npm install
```

#### 2. minify実行

```bash
npm run minify:all

# 実行結果の確認
ls -lh assets/js/script.min.js
ls -lh assets/css/style.min.css
```

**期待される結果:**
- `script.js` 108KB → `script.min.js` 約40KB (63%削減) ✅
- `style.css` 21KB → `style.min.css` 約15KB (30%削減) ✅

#### 3. includes/header.php を修正

**変更前:**
```php
<link href="assets/css/style.css" rel="stylesheet">
<script src="assets/js/script.js"></script>
```

**変更後:**
```php
<?php
// minifyファイルがあれば使用、なければ元ファイル
$cssFile = file_exists('assets/css/style.min.css') ? 'style.min.css' : 'style.css';
$jsFile = file_exists('assets/js/script.min.js') ? 'script.min.js' : 'script.js';
?>
<link href="assets/css/<?php echo $cssFile; ?>" rel="stylesheet">
<script src="assets/js/<?php echo $jsFile; ?>" defer></script>
```

#### 4. サーバーにアップロード

**FTPでアップロード:**
```
✓ assets/js/script.min.js
✓ assets/css/style.min.css
✓ includes/header.php
```

**または Git経由:**
```bash
git add assets/js/script.min.js assets/css/style.min.css includes/header.php
git commit -m "Add: Minified CSS/JS for performance"
git push origin main
```

#### 5. 動作確認

1. ブラウザでサイトを開く
2. F12でデベロッパーツールを開く
3. Networkタブで `script.min.js` と `style.min.css` が読み込まれていることを確認 ✅

---

## 🚀 施策2: データベースインデックス追加（10分）

### 効果: ページ表示速度 10-20%向上、検索速度 70-90%向上

### 手順

#### 1. SQLファイルを作成（ローカル）

```bash
# プロジェクトフォルダで実行
cat > migrations/202511_performance_indexes.sql << 'EOF'
-- パフォーマンス改善: インデックス追加

-- 1. フィルタ + ソート用の複合インデックス
ALTER TABLE media_files
ADD INDEX idx_type_upload_date (file_type, upload_date),
ADD INDEX idx_type_exif_datetime (file_type, exif_datetime);

-- 2. 全文検索インデックス（検索高速化）
ALTER TABLE media_files
ADD FULLTEXT INDEX idx_fulltext_search (title, description, filename);

-- 確認
SHOW INDEX FROM media_files;
EOF
```

#### 2. phpMyAdminで実行

1. ロリポップ管理画面 → データベース → phpMyAdmin を開く
2. 対象のデータベースを選択
3. 「SQL」タブをクリック
4. 上記SQLをコピー＆ペースト
5. 「実行」をクリック ✅

**実行結果:** `3 rows affected` などと表示されればOK

#### 3. index.phpを修正（検索部分のみ）

**ファイル:** `index.php` 58-64行目付近

**変更前:**
```php
if (!empty($searchQuery)) {
    $whereClause .= " AND (title LIKE :search1 OR description LIKE :search2 OR filename LIKE :search3)";
    $searchPattern = '%' . $searchQuery . '%';
    $params[':search1'] = $searchPattern;
    $params[':search2'] = $searchPattern;
    $params[':search3'] = $searchPattern;
}
```

**変更後:**
```php
if (!empty($searchQuery)) {
    // 全文検索インデックスを使用（高速化）
    if (mb_strlen($searchQuery) >= 3) {
        // 3文字以上: 全文検索（高速）
        $whereClause .= " AND MATCH(title, description, filename) AGAINST(:search IN NATURAL LANGUAGE MODE)";
        $params[':search'] = $searchQuery;
    } else {
        // 2文字以下: 従来のLIKE検索
        $whereClause .= " AND (title LIKE :search1 OR description LIKE :search2 OR filename LIKE :search3)";
        $searchPattern = '%' . $searchQuery . '%';
        $params[':search1'] = $searchPattern;
        $params[':search2'] = $searchPattern;
        $params[':search3'] = $searchPattern;
    }
}
```

#### 4. サーバーにアップロード

```bash
# FTPまたはGit経由でindex.phpをアップロード
git add index.php migrations/202511_performance_indexes.sql
git commit -m "Add: Database indexes for better performance"
git push origin main
```

#### 5. 動作確認

1. サイトの検索機能を使ってみる
2. 検索結果が正しく表示されることを確認 ✅
3. 体感で速くなっていることを確認 ✅

---

## 🎉 完了！

おめでとうございます！パフォーマンス改善が完了しました。

### 📊 効果測定

#### Google PageSpeed Insights でテスト

1. https://pagespeed.web.dev/ を開く
2. サイトのURLを入力
3. 「分析」をクリック

**改善前後の比較:**
- Performance Score: 60-70点 → **80-90点** 🎯
- First Contentful Paint: 2.0s → **1.0s**
- Largest Contentful Paint: 3.5s → **1.8s**
- Total Blocking Time: 300ms → **100ms**

---

## 🔍 トラブルシューティング

### Q1. minify後にJavaScriptエラーが出る

**解決策:**
```php
// includes/header.php を一時的に元に戻す
<script src="assets/js/script.js" defer></script>
```

その後、エラー内容を確認して修正。

### Q2. 全文検索で結果が出ない

**原因:** 3文字未満のキーワード

**解決策:**
- 3文字以上で検索してください
- または、上記のコード（2文字以下はLIKE検索）を実装

### Q3. データベースでエラーが出る

**エラー例:** `Duplicate key name 'idx_type_upload_date'`

**解決策:**
```sql
-- 既存のインデックスを削除してから再実行
DROP INDEX idx_type_upload_date ON media_files;
DROP INDEX idx_type_exif_datetime ON media_files;
DROP INDEX idx_fulltext_search ON media_files;

-- 再度実行
ALTER TABLE media_files ADD INDEX idx_type_upload_date (file_type, upload_date);
-- 以下同様
```

---

## 📈 次のステップ（オプション）

さらにパフォーマンスを向上させたい場合:

1. **WebP対応** (画像サイズ25-35%削減)
   → 詳細: `docs/PERFORMANCE_IMPROVEMENT_GUIDE.md` の施策4を参照

2. **レスポンシブ画像** (モバイル転送量50-70%削減)
   → 詳細: 評価レポート参照

3. **CDN導入** (全世界で30-50%高速化)
   → Cloudflare無料プランがおすすめ

---

## 📚 関連ドキュメント

- **[PERFORMANCE_IMPROVEMENT_GUIDE.md](./PERFORMANCE_IMPROVEMENT_GUIDE.md)** - 全施策の詳細手順（上級者向け）
- **[PERFORMANCE_EVALUATION.md](../PERFORMANCE_EVALUATION.md)** - パフォーマンス評価レポート
- **[WEBP_IMPLEMENTATION.md](./WEBP_IMPLEMENTATION.md)** - WebP実装ガイド
- **[CLAUDE.md](../CLAUDE.md)** - AI開発ガイド（技術仕様）
- **[README.md](../README.md)** - プロジェクト概要
- **[LOLIPOP_SETUP.md](./LOLIPOP_SETUP.md)** - レンタルサーバーセットアップ

### 外部ツール

- [Google PageSpeed Insights](https://pagespeed.web.dev/) - パフォーマンス測定ツール

---

**質問や問題が発生した場合:**
- GitHub Issues: https://github.com/nhashimoto-gm/KidSnaps-GrowthAlbum/issues
- 詳細ガイドを参照: `docs/PERFORMANCE_IMPROVEMENT_GUIDE.md`

---

**最終更新**: 2025-11-12
