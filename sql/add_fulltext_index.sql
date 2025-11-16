-- 全文検索インデックスを追加
-- title, description, filename に対する全文検索を高速化

ALTER TABLE media_files
ADD FULLTEXT INDEX idx_fulltext_search (title, description, filename);
