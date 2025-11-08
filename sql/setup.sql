-- KidSnaps Growth Album データベース設定
-- Personal-Finance-Dashboardと同じデータベースを使用

-- メディアテーブル（写真・動画の情報を保存）
CREATE TABLE IF NOT EXISTS media_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL COMMENT '元のファイル名',
    stored_filename VARCHAR(255) NOT NULL COMMENT '保存時のファイル名（ユニーク）',
    file_path VARCHAR(500) NOT NULL COMMENT 'ファイルパス',
    file_type ENUM('image', 'video') NOT NULL COMMENT 'ファイルタイプ',
    mime_type VARCHAR(100) NOT NULL COMMENT 'MIMEタイプ',
    file_size BIGINT NOT NULL COMMENT 'ファイルサイズ（バイト）',
    thumbnail_path VARCHAR(500) DEFAULT NULL COMMENT 'サムネイル画像パス（動画用）',
    title VARCHAR(255) DEFAULT NULL COMMENT 'タイトル',
    description TEXT DEFAULT NULL COMMENT '説明',
    upload_date DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'アップロード日時',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_file_type (file_type),
    INDEX idx_upload_date (upload_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='メディアファイル管理テーブル';

-- タグテーブル（将来の拡張用）
CREATE TABLE IF NOT EXISTS media_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tag_name VARCHAR(50) NOT NULL UNIQUE COMMENT 'タグ名',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='タグマスター';

-- メディアとタグの関連テーブル
CREATE TABLE IF NOT EXISTS media_tag_relations (
    media_id INT NOT NULL,
    tag_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (media_id, tag_id),
    FOREIGN KEY (media_id) REFERENCES media_files(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES media_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='メディアタグ関連テーブル';

-- サンプルデータ挿入用（オプション）
-- INSERT INTO media_tags (tag_name) VALUES
-- ('家族'), ('旅行'), ('イベント'), ('日常'), ('記念日');
