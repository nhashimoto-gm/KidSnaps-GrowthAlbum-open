-- WebPサムネイルパスカラムを追加
-- 実行: mysql -u root -p kidsnaps_growth_album < sql/add_webp_thumbnail.sql

ALTER TABLE media_files
ADD COLUMN thumbnail_webp_path VARCHAR(500) DEFAULT NULL COMMENT 'WebPサムネイル画像パス' AFTER thumbnail_path;

-- インデックスを追加（WebP対応メディアの検索を高速化）
CREATE INDEX idx_webp_thumbnail ON media_files(thumbnail_webp_path);
