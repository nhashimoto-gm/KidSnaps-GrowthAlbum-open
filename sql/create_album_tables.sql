-- KidSnaps Growth Album - アルバム機能用テーブル
-- 既存の media_files テーブルはそのまま利用

-- アルバムテーブル
CREATE TABLE IF NOT EXISTS albums (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL COMMENT 'アルバムタイトル',
    description TEXT DEFAULT NULL COMMENT 'アルバム説明',
    cover_media_id INT DEFAULT NULL COMMENT 'カバー画像のメディアID',
    media_count INT DEFAULT 0 COMMENT 'メディアファイル数',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at),
    INDEX idx_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='アルバム管理テーブル';

-- アルバムとメディアの関連テーブル
CREATE TABLE IF NOT EXISTS album_media_relations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    album_id INT NOT NULL COMMENT 'アルバムID',
    media_id INT NOT NULL COMMENT 'メディアファイルID',
    display_order INT DEFAULT 0 COMMENT '表示順序',
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '追加日時',
    FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE CASCADE,
    FOREIGN KEY (media_id) REFERENCES media_files(id) ON DELETE CASCADE,
    UNIQUE KEY unique_album_media (album_id, media_id),
    INDEX idx_album_id (album_id),
    INDEX idx_media_id (media_id),
    INDEX idx_display_order (album_id, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='アルバム-メディア関連テーブル';

-- ZIPインポート履歴テーブル（トラブルシューティング用）
CREATE TABLE IF NOT EXISTS zip_import_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    album_id INT DEFAULT NULL COMMENT '作成されたアルバムID',
    zip_filename VARCHAR(255) NOT NULL COMMENT 'ZIPファイル名',
    zip_size BIGINT DEFAULT NULL COMMENT 'ZIPファイルサイズ（バイト）',
    total_files INT DEFAULT 0 COMMENT 'ZIP内の総ファイル数',
    imported_files INT DEFAULT 0 COMMENT 'インポート成功ファイル数',
    failed_files INT DEFAULT 0 COMMENT 'インポート失敗ファイル数',
    status ENUM('processing', 'completed', 'failed', 'cancelled') DEFAULT 'processing' COMMENT 'インポートステータス',
    error_message TEXT DEFAULT NULL COMMENT 'エラーメッセージ',
    import_started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'インポート開始日時',
    import_completed_at TIMESTAMP NULL DEFAULT NULL COMMENT 'インポート完了日時',
    FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_started_at (import_started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ZIPインポート履歴テーブル';
