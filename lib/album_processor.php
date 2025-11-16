<?php
/**
 * KidSnaps Growth Album - アルバム処理クラス
 * アルバムの作成、更新、削除などの操作を管理
 */

require_once __DIR__ . '/../config/database.php';

class AlbumProcessor {
    private $pdo;

    public function __construct() {
        $this->pdo = getDbConnection();
    }

    /**
     * 新しいアルバムを作成
     * @param string $title アルバムタイトル
     * @param string|null $description アルバム説明
     * @return int 作成されたアルバムID
     */
    public function createAlbum($title, $description = null) {
        $sql = "INSERT INTO albums (title, description, media_count) VALUES (?, ?, 0)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$title, $description]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * アルバムにメディアファイルを追加
     * @param int $albumId アルバムID
     * @param int $mediaId メディアファイルID
     * @param int $displayOrder 表示順序
     * @return bool 成功した場合true
     */
    public function addMediaToAlbum($albumId, $mediaId, $displayOrder = 0) {
        try {
            // 既に追加されていないかチェック
            $checkSql = "SELECT id FROM album_media_relations WHERE album_id = ? AND media_id = ?";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$albumId, $mediaId]);

            if ($checkStmt->fetch()) {
                // 既に存在する場合はスキップ
                return true;
            }

            // 関連を追加
            $sql = "INSERT INTO album_media_relations (album_id, media_id, display_order) VALUES (?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$albumId, $mediaId, $displayOrder]);

            // メディア数を更新
            $this->updateMediaCount($albumId);

            return true;
        } catch (PDOException $e) {
            error_log("メディア追加エラー: " . $e->getMessage());
            return false;
        }
    }

    /**
     * アルバムのメディア数を更新
     * @param int $albumId アルバムID
     */
    public function updateMediaCount($albumId) {
        $sql = "UPDATE albums SET media_count = (
            SELECT COUNT(*) FROM album_media_relations WHERE album_id = ?
        ) WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$albumId, $albumId]);
    }

    /**
     * アルバムのカバー画像を設定
     * @param int $albumId アルバムID
     * @param int|null $mediaId メディアID（nullの場合は最初の画像を自動選択）
     */
    public function setCoverImage($albumId, $mediaId = null) {
        if ($mediaId === null) {
            // 最初の画像を自動選択
            $sql = "SELECT m.id
                    FROM media_files m
                    INNER JOIN album_media_relations r ON m.id = r.media_id
                    WHERE r.album_id = ? AND m.file_type = 'image'
                    ORDER BY r.display_order ASC, r.added_at ASC
                    LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$albumId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                $mediaId = $result['id'];
            } else {
                // 画像がない場合は動画でも可
                $sql = "SELECT m.id
                        FROM media_files m
                        INNER JOIN album_media_relations r ON m.id = r.media_id
                        WHERE r.album_id = ?
                        ORDER BY r.display_order ASC, r.added_at ASC
                        LIMIT 1";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$albumId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($result) {
                    $mediaId = $result['id'];
                }
            }
        }

        if ($mediaId) {
            $sql = "UPDATE albums SET cover_media_id = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$mediaId, $albumId]);
        }
    }

    /**
     * アルバム情報を取得
     * @param int $albumId アルバムID
     * @return array|null アルバム情報
     */
    public function getAlbum($albumId) {
        $sql = "SELECT a.*,
                       m.thumbnail_path as cover_thumbnail,
                       m.thumbnail_webp_path as cover_thumbnail_webp,
                       m.file_path as cover_file_path
                FROM albums a
                LEFT JOIN media_files m ON a.cover_media_id = m.id
                WHERE a.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$albumId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 全アルバムを取得
     * @param string $orderBy ソート順 (created_at_desc, created_at_asc, updated_at_desc, title_asc)
     * @param int $limit 取得件数制限
     * @param int $offset オフセット
     * @return array アルバムリスト
     */
    public function getAllAlbums($orderBy = 'created_at_desc', $limit = null, $offset = 0) {
        $allowedOrders = [
            'created_at_desc' => 'a.created_at DESC',
            'created_at_asc' => 'a.created_at ASC',
            'updated_at_desc' => 'a.updated_at DESC',
            'title_asc' => 'a.title ASC'
        ];

        $order = isset($allowedOrders[$orderBy]) ? $allowedOrders[$orderBy] : $allowedOrders['created_at_desc'];

        $sql = "SELECT a.*,
                       m.thumbnail_path as cover_thumbnail,
                       m.thumbnail_webp_path as cover_thumbnail_webp,
                       m.file_path as cover_file_path,
                       m.file_type as cover_file_type
                FROM albums a
                LEFT JOIN media_files m ON a.cover_media_id = m.id
                ORDER BY {$order}";

        if ($limit !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        } else {
            $stmt = $this->pdo->prepare($sql);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * アルバムのメディアを取得
     * @param int $albumId アルバムID
     * @param string $orderBy ソート順
     * @return array メディアリスト
     */
    public function getAlbumMedia($albumId, $orderBy = 'display_order') {
        $allowedOrders = [
            'display_order' => 'r.display_order ASC, r.added_at ASC',
            'added_at_desc' => 'r.added_at DESC',
            'added_at_asc' => 'r.added_at ASC',
            'filename' => 'm.filename ASC',
            'exif_datetime_desc' => 'm.exif_datetime DESC, r.added_at DESC',
            'exif_datetime_asc' => 'm.exif_datetime ASC, r.added_at ASC'
        ];

        $order = isset($allowedOrders[$orderBy]) ? $allowedOrders[$orderBy] : $allowedOrders['display_order'];

        $sql = "SELECT m.*, r.display_order, r.added_at as added_to_album_at
                FROM media_files m
                INNER JOIN album_media_relations r ON m.id = r.media_id
                WHERE r.album_id = ?
                ORDER BY {$order}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$albumId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * アルバムを削除
     * @param int $albumId アルバムID
     * @return bool 成功した場合true
     */
    public function deleteAlbum($albumId) {
        try {
            // CASCADE設定により関連レコードも自動削除される
            $sql = "DELETE FROM albums WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$albumId]);

            return true;
        } catch (PDOException $e) {
            error_log("アルバム削除エラー: " . $e->getMessage());
            return false;
        }
    }

    /**
     * アルバムからメディアを削除
     * @param int $albumId アルバムID
     * @param int $mediaId メディアID
     * @return bool 成功した場合true
     */
    public function removeMediaFromAlbum($albumId, $mediaId) {
        try {
            $sql = "DELETE FROM album_media_relations WHERE album_id = ? AND media_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$albumId, $mediaId]);

            // メディア数を更新
            $this->updateMediaCount($albumId);

            // カバー画像が削除されたメディアの場合、新しいカバーを設定
            $album = $this->getAlbum($albumId);
            if ($album && $album['cover_media_id'] == $mediaId) {
                $this->setCoverImage($albumId);
            }

            return true;
        } catch (PDOException $e) {
            error_log("メディア削除エラー: " . $e->getMessage());
            return false;
        }
    }

    /**
     * アルバム情報を更新
     * @param int $albumId アルバムID
     * @param array $data 更新データ (title, description)
     * @return bool 成功した場合true
     */
    public function updateAlbum($albumId, $data) {
        try {
            $updates = [];
            $params = [];

            if (isset($data['title'])) {
                $updates[] = 'title = ?';
                $params[] = $data['title'];
            }

            if (isset($data['description'])) {
                $updates[] = 'description = ?';
                $params[] = $data['description'];
            }

            if (empty($updates)) {
                return false;
            }

            $params[] = $albumId;
            $sql = "UPDATE albums SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return true;
        } catch (PDOException $e) {
            error_log("アルバム更新エラー: " . $e->getMessage());
            return false;
        }
    }

    /**
     * アルバム総数を取得
     * @return int アルバム数
     */
    public function getAlbumCount() {
        $sql = "SELECT COUNT(*) FROM albums";
        $stmt = $this->pdo->query($sql);
        return (int)$stmt->fetchColumn();
    }
}
?>
