<?php

namespace App;

use PDO;
use PDOException;

class TagController
{
    private $pdo;
    private $itemsPerPage = 10;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function searchTagsWithDocuments()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Kiểm tra quyền admin
        if (!isset($_SESSION['account_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header('Location: /study_sharing');
            exit;
        }

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
        $offset = ($page - 1) * $this->itemsPerPage;
        $tags = [];
        $totalPages = 1;

        try {
            if (!$this->pdo) {
                throw new PDOException("Kết nối cơ sở dữ liệu không hợp lệ");
            }

            // Truy vấn chính
            $query = "
                SELECT DISTINCT t.*
                FROM tags t
                LEFT JOIN document_tags dt ON t.tag_id = dt.tag_id
                LEFT JOIN documents d ON dt.document_id = d.document_id
                WHERE 1=1
            ";
            $params = [];

            // Tìm theo tên và mô tả
            if (!empty($keyword)) {
                $query .= " AND (
                    t.tag_name LIKE :keyword1 OR 
                    COALESCE(t.description, '') LIKE :keyword2
                )";
                $params[':keyword1'] = '%' . $keyword . '%';
                $params[':keyword2'] = '%' . $keyword . '%';
            }

            $query .= " ORDER BY t.tag_name LIMIT :offset, :itemsPerPage";

            $stmt = $this->pdo->prepare($query);
            if (!empty($keyword)) {
                $stmt->bindValue(':keyword1', $params[':keyword1'], PDO::PARAM_STR);
                $stmt->bindValue(':keyword2', $params[':keyword2'], PDO::PARAM_STR);
            }
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':itemsPerPage', $this->itemsPerPage, PDO::PARAM_INT);
            $stmt->execute();
            $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Truy vấn đếm tổng số thẻ
            $countQuery = "
                SELECT COUNT(DISTINCT t.tag_id) as total
                FROM tags t
                LEFT JOIN document_tags dt ON t.tag_id = dt.tag_id
                LEFT JOIN documents d ON dt.document_id = d.document_id
                WHERE 1=1
            ";

            if (!empty($keyword)) {
                $countQuery .= " AND (
                    t.tag_name LIKE :keyword1 OR 
                    COALESCE(t.description, '') LIKE :keyword2
                )";
            }

            $countStmt = $this->pdo->prepare($countQuery);
            if (!empty($keyword)) {
                $countStmt->bindValue(':keyword1', $params[':keyword1'], PDO::PARAM_STR);
                $countStmt->bindValue(':keyword2', $params[':keyword2'], PDO::PARAM_STR);
            }
            $countStmt->execute();
            $totalTags = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            $totalPages = ceil($totalTags / $this->itemsPerPage);

            if (empty($tags) && !empty($keyword)) {
                $_SESSION['message'] = 'Không tìm thấy thẻ nào khớp với từ khóa: ' . htmlspecialchars($keyword);
                $_SESSION['message_type'] = 'warning';
            }
        } catch (PDOException $e) {
            error_log("Search tags error: " . $e->getMessage());
            $_SESSION['message'] = 'Lỗi server khi tìm kiếm: ' . $e->getMessage();
            $_SESSION['message_type'] = 'danger';
        }

        $keyword = htmlspecialchars($keyword ?? '');
        $title = 'Tìm kiếm thẻ';
        $pdo = $this->pdo;

        ob_start();
        require __DIR__ . '/../views/tag/manage.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layouts/admin_layout.php';
        exit;
    }

    public function manage()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Debug: In thông tin session
        error_log('Session data: ' . print_r($_SESSION, true));

        // Kiểm tra quyền admin
        if (!isset($_SESSION['account_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            error_log("Access denied: Not an admin or session not set. Account ID: " . ($_SESSION['account_id'] ?? 'none') . ", Role: " . ($_SESSION['role'] ?? 'none'));
            header('Location: /study_sharing');
            exit;
        }

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
        $offset = ($page - 1) * $this->itemsPerPage;
        $tags = [];
        $totalPages = 1;
        $errorMessage = '';

        try {
            // Kiểm tra kết nối cơ sở dữ liệu
            if (!$this->pdo) {
                throw new PDOException("Kết nối cơ sở dữ liệu không hợp lệ");
            }

            // Truy vấn thẻ với tìm kiếm
            $query = "SELECT * FROM tags WHERE 1=1";
            $params = [];
            if (!empty($keyword)) {
                $query .= " AND (tag_name LIKE :keyword OR description LIKE :keyword)";
                $params[':keyword'] = '%' . $keyword . '%';
            }
            $query .= " ORDER BY tag_name LIMIT :offset, :itemsPerPage";

            $stmt = $this->pdo->prepare($query);
            if (!empty($keyword)) {
                $stmt->bindValue(':keyword', $params[':keyword'], PDO::PARAM_STR);
            }
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':itemsPerPage', $this->itemsPerPage, PDO::PARAM_INT);
            $stmt->execute();
            $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Debug: In số lượng và dữ liệu
            error_log('Number of tags fetched: ' . count($tags));
            error_log('Tags: ' . print_r($tags, true));

            // Tính tổng số trang với tìm kiếm
            $countQuery = "SELECT COUNT(*) as total FROM tags WHERE 1=1";
            $params = [];

            if (!empty($keyword)) {
                $countQuery .= " AND (tag_name LIKE :keyword OR description LIKE :keyword)";
                $params[':keyword'] = '%' . $keyword . '%';
            }

            $countStmt = $this->pdo->prepare($countQuery);

            if (!empty($keyword)) {
                $countStmt->bindValue(':keyword', $params[':keyword'], PDO::PARAM_STR);
            }

            $countStmt->execute();
            $totalTags = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            $totalPages = ceil($totalTags / $this->itemsPerPage);
        } catch (PDOException $e) {
            error_log("Manage tags error: " . $e->getMessage() . " at line " . $e->getLine());
            $errorMessage = 'Lỗi server khi tải thẻ: ' . $e->getMessage();
        }
        $keyword = $keyword;

        $title = 'Quản lý thẻ';
        $pdo = $this->pdo;
        ob_start();
        require __DIR__ . '/../views/tag/manage.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layouts/admin_layout.php';
        exit;
    }

    public function addTag()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Ghi log để debug
            error_log('POST data: ' . print_r($_POST, true));

            $tag_name = trim($_POST['tag_name'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if (empty($tag_name)) {
                $_SESSION['message'] = 'Tên thẻ là bắt buộc!';
                $_SESSION['message_type'] = 'danger';
                header('Location: /study_sharing/tag/manage');
                exit;
            }

            try {
                // Kiểm tra trùng tên
                $query = "SELECT * FROM tags WHERE tag_name = :tag_name";
                $stmt = $this->pdo->prepare($query);
                $stmt->bindParam(':tag_name', $tag_name, PDO::PARAM_STR);
                $stmt->execute();

                if ($stmt->fetch()) {
                    $_SESSION['message'] = 'Tên thẻ đã tồn tại!';
                    $_SESSION['message_type'] = 'danger';
                    header('Location: /study_sharing/tag/manage');
                    exit;
                }

                // Thêm thẻ
                $insertQuery = "INSERT INTO tags (tag_name, description) VALUES (:tag_name, :description)";
                $insertStmt = $this->pdo->prepare($insertQuery);
                $insertStmt->bindParam(':tag_name', $tag_name, PDO::PARAM_STR);
                $insertStmt->bindParam(':description', $description, PDO::PARAM_STR);
                $insertStmt->execute();

                // Kiểm tra xem có thực sự thêm thành công
                $affected = $insertStmt->rowCount();
                error_log("Rows affected: $affected");

                if ($affected > 0) {
                    $_SESSION['message'] = 'Thêm thẻ thành công!';
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = 'Không thể thêm thẻ. Hãy thử lại.';
                    $_SESSION['message_type'] = 'danger';
                }

                header('Location: /study_sharing/tag/manage');
            } catch (PDOException $e) {
                error_log("Add tag error: " . $e->getMessage());
                $_SESSION['message'] = 'Lỗi server: ' . $e->getMessage();
                $_SESSION['message_type'] = 'danger';
                header('Location: /study_sharing/tag/manage');
            }
            exit;
        }
    }

    public function editTag()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tag_id = $_POST['tag_id'] ?? '';
            $tag_name = $_POST['tag_name'] ?? '';
            $description = $_POST['description'] ?? '';

            if (empty($tag_id) || empty($tag_name)) {
                $_SESSION['message'] = 'Vui lòng cung cấp ID và tên thẻ!';
                $_SESSION['message_type'] = 'danger';
                header('Location: /study_sharing/tag/manage');
                exit;
            }

            try {
                $query = "SELECT * FROM tags WHERE tag_name = :tag_name AND tag_id != :tag_id";
                $stmt = $this->pdo->prepare($query);
                $stmt->bindParam(':tag_name', $tag_name, PDO::PARAM_STR);
                $stmt->bindParam(':tag_id', $tag_id, PDO::PARAM_INT);
                $stmt->execute();
                if ($stmt->fetch()) {
                    $_SESSION['message'] = 'Tên thẻ đã tồn tại!';
                    $_SESSION['message_type'] = 'danger';
                    header('Location: /study_sharing/tag/manage');
                    exit;
                }

                $query = "UPDATE tags SET tag_name = :tag_name, description = :description WHERE tag_id = :tag_id";
                $stmt = $this->pdo->prepare($query);
                $stmt->bindParam(':tag_name', $tag_name, PDO::PARAM_STR);
                $stmt->bindParam(':description', $description, PDO::PARAM_STR);
                $stmt->bindParam(':tag_id', $tag_id, PDO::PARAM_INT);
                $stmt->execute();

                $_SESSION['message'] = 'Cập nhật thẻ thành công!';
                $_SESSION['message_type'] = 'success';
                header('Location: /study_sharing/tag/manage');
            } catch (PDOException $e) {
                error_log("Edit tag error: " . $e->getMessage());
                $_SESSION['message'] = 'Lỗi server: ' . $e->getMessage();
                $_SESSION['message_type'] = 'danger';
                header('Location: /study_sharing/tag/manage');
            }
            exit;
        }
    }

    public function deleteTag()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Đặt header để trả về JSON
            header('Content-Type: application/json');

            // Kiểm tra session và quyền admin
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (!isset($_SESSION['account_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa thẻ!']);
                exit;
            }

            $tag_id = $_POST['tag_id'] ?? '';

            if (empty($tag_id)) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng cung cấp ID thẻ!']);
                exit;
            }

            try {
                // Kiểm tra tài liệu liên quan
                $query = "SELECT COUNT(*) as count FROM document_tags WHERE tag_id = :tag_id";
                $stmt = $this->pdo->prepare($query);
                $stmt->bindParam(':tag_id', $tag_id, PDO::PARAM_INT);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($result['count'] > 0) {
                    echo json_encode(['success' => false, 'message' => 'Không thể xóa thẻ vì có tài liệu liên quan!']);
                    exit;
                }

                // Xóa thẻ
                $query = "DELETE FROM tags WHERE tag_id = :tag_id";
                $stmt = $this->pdo->prepare($query);
                $stmt->bindParam(':tag_id', $tag_id, PDO::PARAM_INT);
                $stmt->execute();

                // Kiểm tra xem có xóa thành công không
                $affected = $stmt->rowCount();
                if ($affected > 0) {
                    echo json_encode(['success' => true, 'message' => 'Xóa thẻ thành công!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Không tìm thấy thẻ để xóa!']);
                }
            } catch (PDOException $e) {
                error_log("Delete tag error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
            }
            exit;
        }
    }

    public function countTags()
    {
        try {
            $query = "SELECT COUNT(*) as total FROM tags";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (PDOException $e) {
            error_log("Count tags error: " . $e->getMessage());
            return 0;
        }
    }
}