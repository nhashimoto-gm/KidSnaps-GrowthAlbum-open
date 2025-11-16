-- Google Photosメタデータ用カラムの追加
-- media_files テーブルに Google Photos のメタデータを保存するカラムを追加

ALTER TABLE media_files
ADD COLUMN google_photos_people JSON DEFAULT NULL COMMENT 'Google Photosの人物情報（JSON配列）',
ADD COLUMN has_google_photos_metadata BOOLEAN DEFAULT FALSE COMMENT 'Google Photosメタデータの有無',
ADD INDEX idx_has_google_photos_metadata (has_google_photos_metadata);
