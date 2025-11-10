-- ファイルハッシュカラムを追加（重複チェック用）

ALTER TABLE media_files
ADD COLUMN file_hash VARCHAR(32) DEFAULT NULL COMMENT 'MD5ハッシュ値（重複チェック用）',
ADD INDEX idx_file_hash (file_hash);
