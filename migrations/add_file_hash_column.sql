-- ファイルハッシュカラムを追加
-- 重複チェックの高速化のため、MD5ハッシュを保存します

ALTER TABLE media_files
ADD COLUMN file_hash VARCHAR(32) NULL AFTER file_size,
ADD INDEX idx_file_hash (file_hash);

-- 既存のレコードについては、別途update_file_hashes.phpで更新します
