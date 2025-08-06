<?php

namespace App;

use PDO;
use PDOException;
use Exception;

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
            $status = isset($_GET['status']) && in_array(trim($_GET['status']), ['open', 'closed', 'in_progress', 'pending', 'cancelled']) ? trim($_GET['status']) : '';
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

    public function getCourseDetails()
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
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xem chi tiết khóa học!']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $course_id = (int)($data['course_id'] ?? 0);

        if ($course_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID khóa học không hợp lệ!']);
            exit;
        }

        try {
            $course = $this->courseModel->getCourseById($course_id);

            if ($course) {
                // Lấy thêm thông tin username của người tạo
                $query = "SELECT a.username FROM accounts a WHERE a.account_id = :creator_id";
                $stmt = $this->pdo->prepare($query);
                $stmt->bindValue(':creator_id', $course['creator_id'], PDO::PARAM_INT);
                $stmt->execute();
                $creator = $stmt->fetch(PDO::FETCH_ASSOC);
                $course['username'] = $creator ? $creator['username'] : 'Không xác định';

                // Đếm số thành viên
                $query = "SELECT COUNT(*) as member_count FROM course_members WHERE course_id = :course_id";
                $stmt = $this->pdo->prepare($query);
                $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
                $stmt->execute();
                $course['member_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['member_count'];

                echo json_encode(['success' => true, 'course' => $course]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Khóa học không tồn tại!']);
            }
        } catch (PDOException $e) {
            error_log("Get course details error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
        }
        exit;
    }

    public function admin_edit()
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
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền chỉnh sửa khóa học!']);
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

        if ($course_id <= 0 || empty($course_name) || $creator_id <= 0 || $max_members <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID khóa học, tên khóa học, người tạo và số lượng thành viên tối đa là bắt buộc!']);
            exit;
        }

        try {
            $currentCourse = $this->courseModel->getCourseById($course_id);

            if (!$currentCourse) {
                echo json_encode(['success' => false, 'message' => 'Khóa học không tồn tại!']);
                exit;
            }

            $updateResult = $this->courseModel->updateCourse($course_id, $course_name, $description, $max_members, $learn_link, $start_date, $end_date);

            if ($updateResult) {
                echo json_encode(['success' => true, 'message' => 'Cập nhật khóa học thành công!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Cập nhật khóa học thất bại!']);
            }
        } catch (Exception $e) {
            error_log("Edit course error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
        }
        exit;
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

        if (!isset($_SESSION['account_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa khóa học!']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $course_id = (int)($data['course_id'] ?? 0);

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

            // Xóa khóa học
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

    public function updateStatus()
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
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền cập nhật trạng thái khóa học!']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $course_id = (int)($data['course_id'] ?? 0);
        $status = trim($data['status'] ?? '');

        if ($course_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID khóa học không hợp lệ!']);
            exit;
        }

        try {
            $result = $this->courseModel->updateCourseStatus($course_id, $status);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái khóa học thành công!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Cập nhật trạng thái khóa học thất bại!']);
            }
        } catch (Exception $e) {
            error_log("Update course status error: " . $e->getMessage());
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
            $courses = $this->courseModel->getAllCourses();

            // Tính toán thống kê
            $totalCourses = count($courses);
            $totalMembers = 0;
            $totalDocuments = 0;
            foreach ($courses as $course) {
                $docStmt = $this->pdo->prepare("SELECT COUNT(*) as doc_count FROM documents WHERE course_id = :course_id");
                $docStmt->bindValue(':course_id', $course['course_id'], PDO::PARAM_INT);
                $docStmt->execute();
                $docCount = $docStmt->fetch(PDO::FETCH_ASSOC)['doc_count'];
                $totalDocuments += $docCount;

                $memberStmt = $this->pdo->prepare("SELECT COUNT(*) as member_count FROM course_members WHERE course_id = :course_id");
                $memberStmt->bindValue(':course_id', $course['course_id'], PDO::PARAM_INT);
                $memberStmt->execute();
                $totalMembers += $memberStmt->fetch(PDO::FETCH_ASSOC)['member_count'];
            }

            // Tạo nội dung và hiển thị layout
            $title = 'Thống kê khóa học';
            ob_start();
            require __DIR__ . '/../views/course/Admin_statistics.php';
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

    public function getCourseMembers()
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
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xem danh sách thành viên!']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $course_id = (int)($data['course_id'] ?? 0);

        if ($course_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID khóa học không hợp lệ!']);
            exit;
        }

        try {
            $query = "SELECT cm.course_member_id, a.full_name, cm.join_date
                      FROM course_members cm
                      JOIN accounts a ON cm.account_id = a.account_id
                      WHERE cm.course_id = :course_id
                      ORDER BY cm.join_date DESC";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->execute();
            $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'members' => $members]);
        } catch (PDOException $e) {
            error_log("Get course members error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
        }
        exit;
    }

    public function removeCourseMember()
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
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa thành viên!']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $course_id = (int)($data['course_id'] ?? 0);
        $course_member_id = (int)($data['course_member_id'] ?? 0);

        if ($course_id <= 0 || $course_member_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID khóa học hoặc ID thành viên không hợp lệ!']);
            exit;
        }

        try {
            $this->pdo->beginTransaction();

            // Xóa thành viên
            $stmt = $this->pdo->prepare("DELETE FROM course_members WHERE course_member_id = :course_member_id AND course_id = :course_id");
            $stmt->bindValue(':course_member_id', $course_member_id, PDO::PARAM_INT);
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                // Gửi thông báo cho sinh viên
                $notificationStmt = $this->pdo->prepare("
                    INSERT INTO notifications (account_id, content, type, created_at)
                    SELECT account_id, :content, 'course_removed', NOW()
                    FROM course_members WHERE course_member_id = :course_member_id
                ");
                $notificationStmt->bindValue(':course_member_id', $course_member_id, PDO::PARAM_INT);
                $notificationStmt->bindValue(':content', 'Bạn đã bị xóa khỏi khóa học ID ' . $course_id, PDO::PARAM_STR);
                $notificationStmt->execute();

                $this->pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Xóa thành viên thành công!']);
            } else {
                $this->pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Thành viên không tồn tại hoặc đã bị xóa!']);
            }
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Remove course member error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
        }
        exit;
    }

    public function approveCourses()
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

            // Tạo nội dung và hiển thị layout
            $title = 'Duyệt khóa học';
            ob_start();
            require __DIR__ . '/../views/course/approve_courses.php';
            $content = ob_get_clean();
            require __DIR__ . '/../views/layouts/admin_layout.php';
        } catch (PDOException $e) {
            error_log("Approve courses error: " . $e->getMessage());
            $_SESSION['message'] = 'Lỗi server khi tải danh sách khóa học: ' . $e->getMessage();
            $_SESSION['message_type'] = 'danger';
            header('Location: /study_sharing');
            exit;
        }
    }

    public function approveCourse()
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
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền duyệt khóa học!']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $course_id = (int)($data['course_id'] ?? 0);

        if ($course_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID khóa học không hợp lệ!']);
            exit;
        }

        try {
            $this->pdo->beginTransaction();

            // Kiểm tra khóa học
            $course = $this->courseModel->getCourseById($course_id);
            if (!$course || $course['status'] !== 'pending') {
                $this->pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Khóa học không tồn tại hoặc không ở trạng thái chờ duyệt!']);
                exit;
            }

            // Cập nhật trạng thái khóa học
            $result = $this->courseModel->updateCourseStatus($course_id, 'open');
            if ($result) {
                // Gửi thông báo cho người tạo
                $notificationStmt = $this->pdo->prepare("
                    INSERT INTO notifications (account_id, content, type, created_at)
                    VALUES (:account_id, :content, 'course_approved', NOW())
                ");
                $notificationStmt->bindValue(':account_id', $course['creator_id'], PDO::PARAM_INT);
                $notificationStmt->bindValue(':content', "Khóa học '{$course['course_name']}' của bạn đã được duyệt.", PDO::PARAM_STR);
                $notificationStmt->execute();

                $this->pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Duyệt khóa học thành công!']);
            } else {
                $this->pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Duyệt khóa học thất bại!']);
            }
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Approve course error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
        }
        exit;
    }

    public function cancelCourse()
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
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền hủy khóa học!']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $course_id = (int)($data['course_id'] ?? 0);
        $cancel_reason = trim($data['cancel_reason'] ?? '');

        if ($course_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID khóa học không hợp lệ!']);
            exit;
        }

        if (empty($cancel_reason)) {
            echo json_encode(['success' => false, 'message' => 'Lý do hủy là bắt buộc!']);
            exit;
        }

        try {
            $this->pdo->beginTransaction();

            // Kiểm tra khóa học
            $course = $this->courseModel->getCourseById($course_id);
            if (!$course || $course['status'] !== 'pending') {
                $this->pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Khóa học không tồn tại hoặc không ở trạng thái chờ duyệt!']);
                exit;
            }

            // Cập nhật trạng thái khóa học
            $result = $this->courseModel->updateCourseStatus($course_id, 'cancelled');
            if ($result) {
                // Gửi thông báo cho người tạo
                $notificationStmt = $this->pdo->prepare("
                    INSERT INTO notifications (account_id, content, type, created_at)
                    VALUES (:account_id, :content, 'course_cancelled', NOW())
                ");
                $notificationStmt->bindValue(':account_id', $course['creator_id'], PDO::PARAM_INT);
                $notificationStmt->bindValue(':content', "Khóa học '{$course['course_name']}' của bạn đã bị hủy. Lý do: {$cancel_reason}", PDO::PARAM_STR);
                $notificationStmt->execute();

                $this->pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Hủy khóa học thành công!']);
            } else {
                $this->pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Hủy khóa học thất bại!']);
            }
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Cancel course error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
        }
        exit;
    }
}
