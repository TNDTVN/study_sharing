<?php

namespace App;

use PDO;
use PDOException;

class AdminCourseController
{
    private $pdo;
    private $courseModel;
    private $itemsPerPage = 5;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->courseModel = new Course($pdo);
    }

    public function manage()
    {
        // Khởi động session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Kiểm tra quyền admin
        if (!isset($_SESSION['account_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header('Location: /study_sharing');
            exit;
        }

        try {
            $pdo = $this->pdo;

            // Lấy thông tin người dùng hiện tại
            $userModel = new User($pdo);
            $user = $userModel->getUserById($_SESSION['account_id']);

            // Lấy các tham số lọc từ URL
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
            $category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
            $status = isset($_GET['status']) && in_array(trim($_GET['status']), ['open', 'in_progress', 'closed']) ? trim($_GET['status']) : '';
            $offset = ($page - 1) * $this->itemsPerPage;

            // Lấy danh sách khóa học với bộ lọc
            $query = "SELECT c.*, a.username, 
                    (SELECT COUNT(*) FROM course_members cm WHERE cm.course_id = c.course_id) as member_count
                  FROM courses c
                  LEFT JOIN accounts a ON c.creator_id = a.account_id
                  WHERE 1=1";
            $params = [];

            if (!empty($keyword)) {
                $query .= " AND (c.course_name LIKE :keyword1 OR c.description LIKE :keyword2)";
                $params[':keyword1'] = "%$keyword%";
                $params[':keyword2'] = "%$keyword%";
            }

            if ($category_id > 0) {
                $query .= " AND EXISTS (
                SELECT 1 FROM documents d 
                WHERE d.course_id = c.course_id 
                AND d.category_id = :category_id
            )";
                $params[':category_id'] = $category_id;
            }

            if ($status !== '') {
                $query .= " AND c.status = :status";
                $params[':status'] = $status;
            }

            $query .= " ORDER BY c.created_at DESC LIMIT :offset, :itemsPerPage";
            $stmt = $pdo->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':itemsPerPage', $this->itemsPerPage, PDO::PARAM_INT);
            $stmt->execute();
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Lấy danh mục, tài khoản (phục vụ form lọc)
            $categoryStmt = $pdo->prepare("SELECT * FROM categories ORDER BY category_name");
            $categoryStmt->execute();
            $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

            $accountStmt = $pdo->prepare("SELECT account_id, username FROM accounts ORDER BY username");
            $accountStmt->execute();
            $accounts = $accountStmt->fetchAll(PDO::FETCH_ASSOC);

            // Đếm tổng số khóa học (cho phân trang)
            $countQuery = "SELECT COUNT(*) as total FROM courses WHERE 1=1";
            $countParams = [];

            if (!empty($keyword)) {
                $countQuery .= " AND (course_name LIKE :keyword1 OR description LIKE :keyword2)";
                $countParams[':keyword1'] = "%$keyword%";
                $countParams[':keyword2'] = "%$keyword%";
            }

            if ($category_id > 0) {
                $countQuery .= " AND EXISTS (
                SELECT 1 FROM documents d 
                WHERE d.course_id = courses.course_id 
                AND d.category_id = :category_id
            )";
                $countParams[':category_id'] = $category_id;
            }

            if ($status !== '') {
                $countQuery .= " AND status = :status";
                $countParams[':status'] = $status;
            }

            $countStmt = $pdo->prepare($countQuery);
            foreach ($countParams as $key => $value) {
                $countStmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $countStmt->execute();
            $totalCourses = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            $totalPages = ceil($totalCourses / $this->itemsPerPage);

            // Tạo nội dung và hiển thị layout
            $title = 'Quản lý khóa học';
            ob_start();
            require __DIR__ . '/../views/course/manage.php';
            $content = ob_get_clean();
            require __DIR__ . '/../views/layouts/admin_layout.php';
        } catch (PDOException $e) {
            error_log("Manage courses error: " . $e->getMessage());
            $_SESSION['message'] = 'Lỗi server khi tải khóa học: ' . $e->getMessage();
            $_SESSION['message_type'] = 'danger';
            header('Location: /study_sharing');
            exit;
        }
    }


    public function admin_add()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ!']);
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['account_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thêm khóa học!']);
            exit;
        }

        $course_name = trim($_POST['course_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $creator_id = (int)($_POST['creator_id'] ?? 0);
        $max_members = (int)($_POST['max_members'] ?? 50);
        $learn_link = trim($_POST['learn_link'] ?? '');
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $status = in_array($_POST['status'] ?? '', ['open', 'in_progress', 'closed']) ? $_POST['status'] : 'open';

        if (empty($course_name) || $creator_id <= 0 || $max_members <= 0) {
            echo json_encode(['success' => false, 'message' => 'Tên khóa học, người tạo và số lượng thành viên tối đa là bắt buộc!']);
            exit;
        }

        try {
            $result = $this->courseModel->createCourse($course_name, $description, $creator_id);
            if ($result) {
                $course_id = $this->pdo->lastInsertId();
                $updateQuery = "UPDATE courses 
                            SET max_members = :max_members, learn_link = :learn_link, 
                                start_date = :start_date, end_date = :end_date, status = :status 
                            WHERE course_id = :course_id";
                $updateStmt = $this->pdo->prepare($updateQuery);
                $updateStmt->bindValue(':max_members', $max_members, PDO::PARAM_INT);
                $updateStmt->bindValue(':learn_link', $learn_link ?: null, $learn_link ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $updateStmt->bindValue(':start_date', $start_date, $start_date ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $updateStmt->bindValue(':end_date', $end_date, $end_date ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $updateStmt->bindValue(':status', $status, PDO::PARAM_STR);
                $updateStmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
                $updateStmt->execute();

                echo json_encode(['success' => true, 'message' => 'Thêm khóa học thành công!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Thêm khóa học thất bại!']);
            }
        } catch (PDOException $e) {
            error_log("Add course error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
        }
        exit;
    }

    public function admin_edit()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            if (!isset($_SESSION['account_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
                $_SESSION['message'] = 'Bạn không có quyền chỉnh sửa khóa học!';
                $_SESSION['message_type'] = 'danger';
                header('Location: /study_sharing/AdminCourse/manage');
                exit;
            }

            $course_id = (int)($_POST['course_id'] ?? 0);
            $course_name = trim($_POST['course_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $creator_id = (int)($_POST['creator_id'] ?? 0);
            $max_members = (int)($_POST['max_members'] ?? 50);
            $learn_link = trim($_POST['learn_link'] ?? '');
            $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
            $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
            $status = in_array($_POST['status'] ?? '', ['open', 'in_progress', 'closed']) ? $_POST['status'] : 'open';

            if ($course_id <= 0 || empty($course_name) || $creator_id <= 0 || $max_members <= 0) {
                $_SESSION['message'] = 'ID khóa học, tên khóa học, người tạo và số lượng thành viên tối đa là bắt buộc!';
                $_SESSION['message_type'] = 'danger';
                header('Location: /study_sharing/AdminCourse/manage');
                exit;
            }

            try {
                // Kiểm tra khóa học tồn tại bằng model
                $currentCourse = $this->courseModel->getCourseById($course_id);

                if (!$currentCourse) {
                    $_SESSION['message'] = 'Khóa học không tồn tại!';
                    $_SESSION['message_type'] = 'danger';
                    header('Location: /study_sharing/AdminCourse/manage');
                    exit;
                }

                // Cập nhật khóa học (model không có phương thức update, nên dùng truy vấn trực tiếp)
                $query = "UPDATE courses
                          SET course_name = :course_name, description = :description, creator_id = :creator_id,
                              max_members = :max_members, learn_link = :learn_link, start_date = :start_date,
                              end_date = :end_date, status = :status
                          WHERE course_id = :course_id";
                $stmt = $this->pdo->prepare($query);
                $stmt->bindValue(':course_name', $course_name, PDO::PARAM_STR);
                $stmt->bindValue(':description', $description, PDO::PARAM_STR);
                $stmt->bindValue(':creator_id', $creator_id, PDO::PARAM_INT);
                $stmt->bindValue(':max_members', $max_members, PDO::PARAM_INT);
                $stmt->bindValue(':learn_link', $learn_link ?: null, $learn_link ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $stmt->bindValue(':start_date', $start_date, $start_date ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $stmt->bindValue(':end_date', $end_date, $end_date ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $stmt->bindValue(':status', $status, PDO::PARAM_STR);
                $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
                $stmt->execute();

                $_SESSION['message'] = 'Cập nhật khóa học thành công!';
                $_SESSION['message_type'] = 'success';
                header('Location: /study_sharing/AdminCourse/manage');
            } catch (PDOException $e) {
                error_log("Edit course error: " . $e->getMessage());
                $_SESSION['message'] = 'Lỗi server: ' . $e->getMessage();
                $_SESSION['message_type'] = 'danger';
                header('Location: /study_sharing/AdminCourse/manage');
            }
            exit;
        }
    }

    public function admin_delete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ!']);
            exit;
        }

        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        error_log("Session data: " . print_r($_SESSION, true));
        if (!isset($_SESSION['account_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa khóa học!']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $course_id = (int)($data['course_id'] ?? 0);

        error_log("Received course_id: " . $course_id);

        if ($course_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID khóa học không hợp lệ!']);
            exit;
        }

        try {
            $counts = [
                'member_count' => 0,
                'document_count' => 0
            ];

            $checkStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM course_members WHERE course_id = :course_id");
            $checkStmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $checkStmt->execute();
            $counts['member_count'] = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];

            $checkStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM documents WHERE course_id = :course_id");
            $checkStmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $checkStmt->execute();
            $counts['document_count'] = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];

            if ($counts['member_count'] > 0 || $counts['document_count'] > 0) {
                echo json_encode(['success' => false, 'message' => 'Không thể xóa khóa học vì có thành viên hoặc tài liệu liên quan!']);
                exit;
            }

            // Delete related records from course_members
            $deleteMembersStmt = $this->pdo->prepare("DELETE FROM course_members WHERE course_id = :course_id");
            $deleteMembersStmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $deleteMembersStmt->execute();

            // Delete related records from documents (if needed, adjust based on your logic)
            $deleteDocumentsStmt = $this->pdo->prepare("DELETE FROM documents WHERE course_id = :course_id");
            $deleteDocumentsStmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $deleteDocumentsStmt->execute();

            // Delete the main course record
            $deleteStmt = $this->pdo->prepare("DELETE FROM courses WHERE course_id = :course_id");
            $deleteStmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $deleteStmt->execute();

            $affected = $deleteStmt->rowCount();
            if ($affected > 0) {
                echo json_encode(['success' => true, 'message' => 'Xóa khóa học thành công!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Khóa học không tồn tại!']);
            }
        } catch (PDOException $e) {
            error_log("Delete course error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
        }
        exit;
    }
    public function statistics()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['account_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header('Location: /study_sharing');
            exit;
        }

        try {
            $pdo = $this->pdo;

            // Lấy thông tin người dùng hiện tại
            $userModel = new User($pdo);
            $user = $userModel->getUserById($_SESSION['account_id']);
            // Lấy danh sách khóa học
            $query = "SELECT c.*, a.username, 
                    (SELECT COUNT(*) FROM course_members cm WHERE cm.course_id = c.course_id) as member_count
                      FROM courses c
                      LEFT JOIN accounts a ON c.creator_id = a.account_id
                      ORDER BY c.created_at DESC";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Tính toán thống kê
            $totalCourses = count($courses);
            $totalMembers = 0;
            $totalDocuments = 0;
            foreach ($courses as $course) {
                $totalMembers += $course['member_count'];
                // Lấy số lượng tài liệu cho mỗi khóa học
                $docStmt = $pdo->prepare("SELECT COUNT(*) as doc_count FROM documents WHERE course_id = :course_id");
                $docStmt->bindValue(':course_id', $course['course_id'], PDO::PARAM_INT);
                $docStmt->execute();
                $docCount = $docStmt->fetch(PDO::FETCH_ASSOC)['doc_count'];
                $totalDocuments += $docCount;
            }
            // Tạo nội dung và hiển thị layout
            $title = 'Thống kê khóa học';
            ob_start();
            require __DIR__ . '/../views/course/statistics.php';
            $content = ob_get_clean();
            require __DIR__ . '/../views/layouts/admin_layout.php';
        } catch (PDOException $e) {
            error_log("Statistics error: " . $e->getMessage());
            $_SESSION['message'] = 'Lỗi server khi tải thống kê khóa học: ' . $e->getMessage();
            $_SESSION['message_type'] = 'danger';
            header('Location: /study_sharing');
            exit;
        }
    }
}
