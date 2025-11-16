-- Add rotation field to media_files table
-- This field stores the rotation angle in degrees (0, 90, 180, 270)

ALTER TABLE media_files
ADD COLUMN rotation INT DEFAULT 0 COMMENT '回転角度（0, 90, 180, 270度）'
AFTER thumbnail_path;
