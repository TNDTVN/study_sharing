<?php

namespace App;

use PDO;
use PDOException;

class CategoryController
{
    private $pdo;
    private $itemsPerPage = 10;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function manage()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Kiểm tra quyền admin
        if (!isset($_SESSION['account_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            error_log("Access denied: Not an admin or session not set. Account ID: " . ($_SESSION['account_id'] ?? 'none') . ", Role: " . ($_SESSION['role'] ?? 'none'));
            header('Location: /study_sharing');
            exit;
        }

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
        $offset = ($page - 1) * $this->itemsPerPage;
        $categories = [];
        $totalPages = 1;
        $errorMessage = '';

        try {
            // Kiểm tra kết nối cơ sở dữ liệu
            if (!$this->pdo) {
                throw new PDOException("Kết nối cơ sở dữ liệu không hợp lệ");
            }

            $searchTerm = "%$keyword%";
            $query = "SELECT * FROM categories WHERE category_name LIKE :keyword OR description LIKE :keyword ORDER BY category_name LIMIT $offset, $this->itemsPerPage";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':keyword', $searchTerm, PDO::PARAM_STR);
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Debug: Ghi log dữ liệu lấy được
            error_log('Categories: ' . print_r($categories, true));

            // In ra view để kiểm tra (chỉ dùng tạm để debug, sau đó xóa đi)
            echo '<pre style="color:red;background:#fff;z-index:9999;position:relative;">';
            var_dump($categories);
            echo '</pre>';

            // Tính tổng số trang
            $countQuery = "SELECT COUNT(*) as total FROM categories WHERE category_name LIKE :keyword OR description LIKE :keyword";
            $countStmt = $this->pdo->prepare($countQuery);
            $countStmt->bindParam(':keyword', $searchTerm, PDO::PARAM_STR);
            $countStmt->execute();
            $totalCategories = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            $totalPages = ceil($totalCategories / $this->itemsPerPage);
        } catch (PDOException $e) {
            error_log("Manage categories error: " . $e->getMessage() . " at line " . $e->getLine());
            $errorMessage = 'Lỗi server khi tải danh mục: ' . $e->getMessage();
        }

        $title = 'Quản lý danh mục';
        // Truyền $pdo vào scope của view
        $pdo = $this->pdo;
        ob_start();
        require __DIR__ . '/../views/category/manage.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layouts/admin_layout.php';
        exit;
    }

    public function addCategory()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $category_name = $_POST['category_name'] ?? '';
            $description = $_POST['description'] ?? '';

            if (empty($category_name)) {
                $_SESSION['message'] = 'Tên danh mục là bắt buộc!';
                $_SESSION['message_type'] = 'danger';
                header('Location: /study_sharing/category/manage');
                exit;
            }

            try {
                $query = "SELECT * FROM categories WHERE category_name = :category_name";
                $stmt = $this->pdo->prepare($query);
                $stmt->bindParam(':category_name', $category_name, PDO::PARAM_STR);
                $stmt->execute();
                if ($stmt->fetch()) {
                    $_SESSION['message'] = 'Tên danh mục đã tồn tại!';
                    $_SESSION['message_type'] = 'danger';
                    header('Location: /study_sharing/category/manage');
                    exit;
                }

                $query = "INSERT INTO categories (category_name, description) VALUES (:category_name, :description)";
                $stmt = $this->pdo->prepare($query);
                $stmt->bindParam(':category_name', $category_name, PDO::PARAM_STR);
                $stmt->bindParam(':description', $description, PDO::PARAM_STR);
                $stmt->execute();

                $_SESSION['message'] = 'Thêm danh mục thành công!';
                $_SESSION['message_type'] = 'success';
                header('Location: /study_sharing/category/manage');
            } catch (PDOException $e) {
                error_log("Add category error: " . $e->getMessage());
                $_SESSION['message'] = 'Lỗi server: ' . $e->getMessage();
                $_SESSION['message_type'] = 'danger';
                header('Location: /study_sharing/category/manage');
            }
            exit;
        }
    }

    public function editCategory()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $category_id = $_POST['category_id'] ?? '';
            $category_name = $_POST['category_name'] ?? '';
            $description = $_POST['description'] ?? '';

            if (empty($category_id) || empty($category_name)) {
                $_SESSION['message'] = 'Vui lòng cung cấp ID và tên danh mục!';
                $_SESSION['message_type'] = 'danger';
                header('Location: /study_sharing/category/manage');
                exit;
            }

            try {
                $query = "SELECT * FROM categories WHERE category_name = :category_name AND category_id != :category_id";
                $stmt = $this->pdo->prepare($query);
                $stmt->bindParam(':category_name', $category_name, PDO::PARAM_STR);
                $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
                $stmt->execute();
                if ($stmt->fetch()) {
                    $_SESSION['message'] = 'Tên danh mục đã tồn tại!';
                    $_SESSION['message_type'] = 'danger';
                    header('Location: /study_sharing/category/manage');
                    exit;
                }

                $query = "UPDATE categories SET category_name = :category_name, description = :description WHERE category_id = :category_id";
                $stmt = $this->pdo->prepare($query);
                $stmt->bindParam(':category_name', $category_name, PDO::PARAM_STR);
                $stmt->bindParam(':description', $description, PDO::PARAM_STR);
                $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
                $stmt->execute();

                $_SESSION['message'] = 'Cập nhật danh mục thành công!';
                $_SESSION['message_type'] = 'success';
                header('Location: /study_sharing/category/manage');
            } catch (PDOException $e) {
                error_log("Edit category error: " . $e->getMessage());
                $_SESSION['message'] = 'Lỗi server: ' . $e->getMessage();
                $_SESSION['message_type'] = 'danger';
                header('Location: /study_sharing/category/manage');
            }
            exit;
        }
    }

    public function deleteCategory()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $category_id = $_POST['category_id'] ?? '';

            if (empty($category_id)) {
                $_SESSION['message'] = 'Vui lòng cung cấp ID danh mục!';
                $_SESSION['message_type'] = 'danger';
                header('Location: /study_sharing/category/manage');
                exit;
            }

            try {
                // Kiểm tra tài liệu liên quan
                $query = "SELECT COUNT(*) as count FROM documents WHERE category_id = :category_id";
                $stmt = $this->pdo->prepare($query);
                $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($result['count'] > 0) {
                    $_SESSION['message'] = 'Không thể xóa danh mục vì có tài liệu liên quan!';
                    $_SESSION['message_type'] = 'danger';
                    header('Location: /study_sharing/category/manage');
                    exit;
                }

                $query = "DELETE FROM categories WHERE category_id = :category_id";
                $stmt = $this->pdo->prepare($query);
                $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
                $stmt->execute();

                $_SESSION['message'] = 'Xóa danh mục thành công!';
                $_SESSION['message_type'] = 'success';
                header('Location: /study_sharing/category/manage');
            } catch (PDOException $e) {
                error_log("Delete category error: " . $e->getMessage());
                $_SESSION['message'] = 'Lỗi server: ' . $e->getMessage();
                $_SESSION['message_type'] = 'danger';
                header('Location: /study_sharing/category/manage');
            }
            exit;
        }
    }

    public function countCategories()
    {
        try {
            $query = "SELECT COUNT(*) as total FROM categories";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (PDOException $e) {
            error_log("Count categories error: " . $e->getMessage());
            return 0;
        }
    }
}
