-- QuickTime動画のAppleメタデータを保存するカラムを追加

ALTER TABLE media_files
ADD COLUMN exif_software VARCHAR(100) DEFAULT NULL COMMENT 'ソフトウェア情報（com.apple.quicktime.software）',
ADD COLUMN exif_focal_length DECIMAL(5, 2) DEFAULT NULL COMMENT '35mm換算焦点距離（com.apple.quicktime.camera.focal_length.35mm_equivalent）',
ADD COLUMN exif_location_accuracy DECIMAL(8, 2) DEFAULT NULL COMMENT '位置精度（メートル単位、com.apple.quicktime.location.accuracy.horizontal）';
