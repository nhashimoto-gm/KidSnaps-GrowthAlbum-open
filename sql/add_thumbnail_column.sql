-- サムネイルパスカラムを追加するマイグレーション
-- 実行日: 2025-11-07

-- media_filesテーブルにthumbnail_pathカラムを追加
ALTER TABLE media_files
ADD COLUMN thumbnail_path VARCHAR(500) DEFAULT NULL COMMENT 'サムネイル画像パス（動画用）'
AFTER file_size;
