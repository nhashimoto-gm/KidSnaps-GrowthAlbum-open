-- EXIF情報を保存するカラムを追加

ALTER TABLE media_files
ADD COLUMN exif_datetime DATETIME DEFAULT NULL COMMENT 'EXIF撮影日時',
ADD COLUMN exif_latitude DECIMAL(10, 8) DEFAULT NULL COMMENT 'EXIF緯度',
ADD COLUMN exif_longitude DECIMAL(11, 8) DEFAULT NULL COMMENT 'EXIF経度',
ADD COLUMN exif_location_name VARCHAR(255) DEFAULT NULL COMMENT 'EXIF位置情報（住所など）',
ADD COLUMN exif_camera_make VARCHAR(100) DEFAULT NULL COMMENT 'EXIFカメラメーカー',
ADD COLUMN exif_camera_model VARCHAR(100) DEFAULT NULL COMMENT 'EXIFカメラモデル',
ADD COLUMN exif_orientation INT DEFAULT 1 COMMENT 'EXIF画像の向き（1-8）',
ADD INDEX idx_exif_datetime (exif_datetime);
