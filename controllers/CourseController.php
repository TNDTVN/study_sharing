<?php

namespace App;

use PDO;
use Exception;

class CourseController
{
    private $db;
    private $course;
    private $user;
    private $notification;
    private $courseModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->course = new Course($db);
        $this->user = new User($db);
        $this->notification = new Notification($db);
        $this->courseModel = new Course($db);
    }

    public function list()
    {
        $valid_sorts = ['newest', 'popular', 'name'];
        $query = isset($_GET['query']) ? trim($_GET['query']) : '';
        $sort = isset($_GET['sort']) && in_array($_GET['sort'], $valid_sorts) ? $_GET['sort'] : 'newest';
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 9;

        $sql = "SELECT c.*, u.full_name, 
            (SELECT COUNT(*) FROM course_members WHERE course_id = c.course_id) as member_count
            FROM courses c 
            LEFT JOIN users u ON c.creator_id = u.account_id
            WHERE c.status NOT IN ('pending', 'cancelled')";

        $bindParams = [];
        $hasWhere = true;

        if ($query !== '') {
            $sql .= " AND (c.course_name LIKE :query1 OR c.description LIKE :query2)";
            $bindParams[':query1'] = "%$query%";
            $bindParams[':query2'] = "%$query%";
        }

        switch ($sort) {
            case 'popular':
                $sql .= " ORDER BY member_count DESC";
                break;
            case 'name':
                $sql .= " ORDER BY c.course_name ASC";
                break;
            case 'newest':
            default:
                $sql .= " ORDER BY c.created_at DESC";
                break;
        }

        $countSql = "SELECT COUNT(*) FROM courses c WHERE c.status NOT IN ('pending', 'cancelled')";
        $countBindParams = [];
        if ($query !== '') {
            $countSql .= " AND (c.course_name LIKE :query1 OR c.description LIKE :query2)";
            $countBindParams[':query1'] = "%$query%";
            $countBindParams[':query2'] = "%$query%";
        }

        $countStmt = $this->db->prepare($countSql);
        foreach ($countBindParams as $key => $value) {
            $countStmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $countStmt->execute();
        $total = $countStmt->fetchColumn();

        $sql .= " LIMIT :offset, :perPage";
        $stmt = $this->db->prepare($sql);
        foreach ($bindParams as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalPages = ceil($total / $perPage);

        $title = 'Danh sách khóa học';
        $layout = 'layout.php';
        ob_start();
        require __DIR__ . '/../views/course/list.php';
        $content = ob_get_clean();
        $pdo = $this->db;
        require __DIR__ . '/../views/layouts/' . $layout;
    }

    public function detail($course_id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $course = $this->course->getCourseById($course_id);
        if (!$course) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Khóa học không tồn tại']);
            exit;
        }

        $allowedStatuses = ['open', 'closed', 'in_progress'];
        $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
        $isCreator = isset($_SESSION['account_id']) && $course['creator_id'] == $_SESSION['account_id'];

        if (!in_array($course['status'], $allowedStatuses) && !$isAdmin && !$isCreator) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xem chi tiết khóa học này']);
            exit;
        }

        $creator = $course['creator_id'] ? $this->user->getUserById($course['creator_id']) : null;

        $documentsStmt = $this->db->prepare("
            SELECT d.*, c.category_name, u.full_name 
            FROM documents d 
            LEFT JOIN categories c ON d.category_id = c.category_id
            LEFT JOIN users u ON d.account_id = u.account_id
            WHERE d.course_id = :course_id
        ");
        $documentsStmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $documentsStmt->execute();
        $documents = $documentsStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($documents as &$doc) {
            $tagsStmt = $this->db->prepare("SELECT t.tag_name FROM document_tags dt JOIN tags t ON dt.tag_id = t.tag_id WHERE dt.document_id = :document_id");
            $tagsStmt->bindValue(':document_id', $doc['document_id'], PDO::PARAM_INT);
            $tagsStmt->execute();
            $doc['tags'] = $tagsStmt->fetchAll(PDO::FETCH_COLUMN);

            $ratingStmt = $this->db->prepare("SELECT AVG(rating_value) as avg_rating FROM ratings WHERE document_id = :document_id");
            $ratingStmt->bindValue(':document_id', $doc['document_id'], PDO::PARAM_INT);
            $ratingStmt->execute();
            $rating = $ratingStmt->fetch(PDO::FETCH_ASSOC);
            $doc['avg_rating'] = $rating['avg_rating'] ? round($rating['avg_rating'], 1) : 0;
        }
        unset($doc);

        $membersStmt = $this->db->prepare("
            SELECT u.full_name, u.avatar, cm.join_date 
            FROM course_members cm 
            JOIN users u ON cm.account_id = u.account_id 
            WHERE cm.course_id = :course_id
        ");
        $membersStmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $membersStmt->execute();
        $members = $membersStmt->fetchAll(PDO::FETCH_ASSOC);
        $member_count = count($members);

        $title = $course['course_name'];
        $layout = 'layout.php';
        ob_start();
        require __DIR__ . '/../views/course/detail.php';
        $content = ob_get_clean();
        $pdo = $this->db;
        require __DIR__ . '/../views/layouts/' . $layout;
    }

    public function joinCourse()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Phương thức không được phép']);
            exit;
        }

        if (!isset($_SESSION['account_id'])) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để tham gia khóa học']);
            exit;
        }

        $course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        if ($course_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }

        $course = $this->course->getCourseById($course_id);
        if (!$course) {
            echo json_encode(['success' => false, 'message' => 'Khóa học không tồn tại']);
            exit;
        }

        if ($_SESSION['account_id'] == $course['creator_id']) {
            echo json_encode(['success' => false, 'message' => 'Bạn là người tạo khóa học, không thể tham gia khóa học của chính mình']);
            exit;
        }

        if ($course['status'] !== 'open') {
            echo json_encode(['success' => false, 'message' => 'Khóa học hiện không mở đăng ký']);
            exit;
        }

        $membersStmt = $this->db->prepare("SELECT COUNT(*) FROM course_members WHERE course_id = :course_id");
        $membersStmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $membersStmt->execute();
        $member_count = $membersStmt->fetchColumn();
        if ($course['max_members'] && $member_count >= $course['max_members']) {
            echo json_encode(['success' => false, 'message' => 'Khóa học đã đạt số lượng thành viên tối đa']);
            exit;
        }

        $stmt = $this->db->prepare("SELECT course_member_id FROM course_members WHERE course_id = :course_id AND account_id = :account_id");
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':account_id', $_SESSION['account_id'], PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Bạn đã tham gia khóa học này']);
            exit;
        }

        $stmt = $this->db->prepare("INSERT INTO course_members (course_id, account_id) VALUES (:course_id, :account_id)");
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':account_id', $_SESSION['account_id'], PDO::PARAM_INT);
        $success = $stmt->execute();

        if ($success) {
            $joiner = $this->user->getUserById($_SESSION['account_id']);
            $joiner_name = $joiner ? htmlspecialchars($joiner['full_name']) : 'Ẩn danh';
            $message = "$joiner_name đã tham gia khóa học của bạn: \"" . htmlspecialchars($course['course_name']) . "\"";
            $this->notification->createNotification($course['creator_id'], $message, false);

            echo json_encode(['success' => true, 'message' => 'Tham gia khóa học thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi tham gia khóa học']);
        }
    }

    public function myCourses()
    {
        if (!isset($_SESSION['account_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để xem khóa học của bạn']);
            exit;
        }

        $query = isset($_GET['query']) ? trim($_GET['query']) : '';
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 10;

        $sql = "SELECT c.*, u.full_name
                FROM courses c
                LEFT JOIN users u ON c.creator_id = u.account_id
                JOIN course_members cm ON c.course_id = cm.course_id
                WHERE cm.account_id = :account_id";
        $bindParams = [':account_id' => $_SESSION['account_id']];
        $hasWhere = true;

        if ($query !== '') {
            $sql .= " AND (c.course_name LIKE :query1 OR c.description LIKE :query2)";
            $bindParams[':query1'] = "%$query%";
            $bindParams[':query2'] = "%$query%";
        }

        $countSql = "SELECT COUNT(*)
                    FROM courses c
                    JOIN course_members cm ON c.course_id = cm.course_id
                    WHERE cm.account_id = :account_id";
        $countBindParams = [':account_id' => $_SESSION['account_id']];
        if ($query !== '') {
            $countSql .= " AND (c.course_name LIKE :query1 OR c.description LIKE :query2)";
            $countBindParams[':query1'] = "%$query%";
            $countBindParams[':query2'] = "%$query%";
        }

        $countStmt = $this->db->prepare($countSql);
        foreach ($countBindParams as $key => $value) {
            $countStmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $countStmt->execute();
        $total = $countStmt->fetchColumn();

        $sql .= " ORDER BY c.created_at DESC LIMIT :offset, :perPage";
        $stmt = $this->db->prepare($sql);
        foreach ($bindParams as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalPages = ceil($total / $perPage);

        $title = 'Khóa học của tôi';
        $layout = 'layout.php';
        ob_start();
        require __DIR__ . '/../views/course/my_courses.php';
        $content = ob_get_clean();
        $pdo = $this->db;
        require __DIR__ . '/../views/layouts/' . $layout;
    }

    public function createCourseByTeacher()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['account_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
            if (str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Chỉ giảng viên mới có quyền tạo khóa học']);
                exit;
            } else {
                $_SESSION['message'] = 'Chỉ giảng viên mới có quyền tạo khóa học';
                $_SESSION['message_type'] = 'danger';
                header('Location: /study_sharing');
                exit;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $title = 'Tạo khóa học mới';
            $layout = 'layout.php';

            // Lấy danh sách tài liệu chưa gán của giảng viên
            $availableDocuments = [];
            try {
                $documentStmt = $this->db->prepare("
                    SELECT document_id, title, file_path 
                    FROM documents 
                    WHERE account_id = :account_id AND (course_id IS NULL OR course_id = 0)
                ");
                $documentStmt->bindValue(':account_id', $_SESSION['account_id'], PDO::PARAM_INT);
                $documentStmt->execute();
                $availableDocuments = $documentStmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $_SESSION['message'] = 'Lỗi tải tài liệu: ' . htmlspecialchars($e->getMessage());
                $_SESSION['message_type'] = 'danger';
            }

            ob_start();
            require __DIR__ . '/../views/course/create.php';
            $content = ob_get_clean();
            $pdo = $this->db;
            require __DIR__ . '/../views/layouts/' . $layout;
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Phương thức không được phép']);
            exit;
        }

        header('Content-Type: application/json');

        $course_name = trim($_POST['course_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $max_members = isset($_POST['max_members']) ? (int)$_POST['max_members'] : 50;
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $learn_link = trim($_POST['learn_link'] ?? '');
        $document_ids = isset($_POST['documents']) ? (array)$_POST['documents'] : [];

        if (empty($course_name)) {
            echo json_encode(['success' => false, 'message' => 'Tên khóa học không được để trống']);
            exit;
        }

        if ($max_members <= 0) {
            echo json_encode(['success' => false, 'message' => 'Số lượng thành viên tối đa phải lớn hơn 0']);
            exit;
        }

        if ($learn_link && !filter_var($learn_link, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'message' => 'Link học tập không hợp lệ']);
            exit;
        }

        if ($start_date && $end_date && strtotime($end_date) < strtotime($start_date)) {
            echo json_encode(['success' => false, 'message' => 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu']);
            exit;
        }

        // Validate document IDs
        foreach ($document_ids as $doc_id) {
            if (!is_numeric($doc_id) || (int)$doc_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID tài liệu không hợp lệ']);
                exit;
            }
        }

        try {
            $this->db->beginTransaction();
            $creator_id = $_SESSION['account_id'];

            $result = $this->course->createCourse($course_name, $description, $creator_id);

            if ($result) {
                $course_id = $this->db->lastInsertId();

                // Update related documents
                if (!empty($document_ids)) {
                    $placeholders = [];
                    $bindParams = [':account_id' => $creator_id];
                    foreach ($document_ids as $index => $doc_id) {
                        $paramName = ':doc_id_' . $index;
                        $placeholders[] = $paramName;
                        $bindParams[$paramName] = (int)$doc_id;
                    }
                    $placeholdersStr = implode(',', $placeholders);
                    $docCheckStmt = $this->db->prepare("
                        SELECT document_id 
                        FROM documents 
                        WHERE document_id IN ($placeholdersStr) AND account_id = :account_id
                    ");
                    foreach ($bindParams as $key => $value) {
                        $docCheckStmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
                    }
                    $docCheckStmt->execute();
                    $validDocs = $docCheckStmt->fetchAll(PDO::FETCH_COLUMN);

                    foreach ($document_ids as $doc_id) {
                        if (in_array($doc_id, $validDocs)) {
                            $updateStmt = $this->db->prepare("
                                UPDATE documents 
                                SET course_id = :new_course_id 
                                WHERE document_id = :document_id
                            ");
                            $updateStmt->bindValue(':new_course_id', $course_id, PDO::PARAM_INT);
                            $updateStmt->bindValue(':document_id', $doc_id, PDO::PARAM_INT);
                            $updateStmt->execute();
                        }
                    }
                }

                $update = $this->db->prepare("
                    UPDATE courses SET 
                        max_members = :max_members, 
                        learn_link = :learn_link, 
                        start_date = :start_date, 
                        end_date = :end_date
                    WHERE course_id = :course_id
                ");
                $update->bindValue(':max_members', $max_members, PDO::PARAM_INT);
                $update->bindValue(':learn_link', $learn_link ?: null, $learn_link ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $update->bindValue(':start_date', $start_date, $start_date ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $update->bindValue(':end_date', $end_date, $end_date ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $update->bindValue(':course_id', $course_id, PDO::PARAM_INT);
                $update->execute();

                $this->db->commit();

                $message = "Khóa học \"" . htmlspecialchars($course_name) . "\" đã được tạo thành công và đang chờ duyệt.";
                $this->notification->createNotification($creator_id, $message, false);

                echo json_encode(['success' => true, 'message' => 'Tạo khóa học thành công. Trạng thái mặc định: đang chờ duyệt']);
            } else {
                $this->db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Không thể tạo khóa học']);
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Create course error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
        }
        exit;
    }

    public function editCourseByTeacher()
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['account_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
            echo json_encode(['success' => false, 'message' => 'Chỉ giảng viên mới có quyền chỉnh sửa khóa học']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Phương thức không được phép']);
            exit;
        }

        $course_id = (int)($_POST['course_id'] ?? 0);
        $course_name = trim($_POST['course_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $max_members = (int)($_POST['max_members'] ?? 50);
        $learn_link = trim($_POST['learn_link'] ?? '');
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $status = trim($_POST['status'] ?? '');
        $document_ids = isset($_POST['document_ids']) ? (array)$_POST['document_ids'] : [];

        $validStatuses = ['open', 'closed', 'in_progress'];
        if (!in_array($status, $validStatuses)) {
            echo json_encode(['success' => false, 'message' => 'Trạng thái không hợp lệ. Chỉ được chọn: Mở, Đóng, Đang học']);
            exit;
        }

        if ($course_id <= 0 || empty($course_name)) {
            echo json_encode(['success' => false, 'message' => 'ID khóa học và tên khóa học là bắt buộc']);
            exit;
        }

        if ($max_members <= 0) {
            echo json_encode(['success' => false, 'message' => 'Số lượng thành viên tối đa phải lớn hơn 0']);
            exit;
        }

        if ($learn_link && !filter_var($learn_link, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'message' => 'Link học tập không hợp lệ']);
            exit;
        }

        if ($start_date && $end_date && strtotime($end_date) < strtotime($start_date)) {
            echo json_encode(['success' => false, 'message' => 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu']);
            exit;
        }

        // Validate document IDs
        foreach ($document_ids as $doc_id) {
            if (!is_numeric($doc_id) || (int)$doc_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID tài liệu không hợp lệ']);
                exit;
            }
        }

        try {
            $this->db->beginTransaction();

            $currentCourse = $this->courseModel->getCourseById($course_id);
            if (!$currentCourse) {
                $this->db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Khóa học không tồn tại']);
                exit;
            }

            if ($currentCourse['creator_id'] != $_SESSION['account_id']) {
                $this->db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền chỉnh sửa khóa học này']);
                exit;
            }

            $memberStmt = $this->db->prepare("SELECT COUNT(*) FROM course_members WHERE course_id = :course_id");
            $memberStmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $memberStmt->execute();
            $current_members = $memberStmt->fetchColumn();

            if ($max_members < $current_members) {
                $this->db->rollBack();
                echo json_encode(['success' => false, 'message' => "Số thành viên tối đa phải lớn hơn hoặc bằng số thành viên hiện tại ($current_members)"]);
                exit;
            }

            // Update course details
            $query = "
            UPDATE courses SET 
                course_name = :course_name,
                description = :description,
                max_members = :max_members,
                learn_link = :learn_link,
                start_date = :start_date,
                end_date = :end_date,
                status = :status
            WHERE course_id = :course_id
        ";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':course_name', $course_name, PDO::PARAM_STR);
            $stmt->bindValue(':description', $description ?: null, $description ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':max_members', $max_members, PDO::PARAM_INT);
            $stmt->bindValue(':learn_link', $learn_link ?: null, $learn_link ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':start_date', $start_date, $start_date ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':end_date', $end_date, $end_date ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $result = $stmt->execute();

            // Update related documents
            // First, disassociate all documents from this course
            $disassociateStmt = $this->db->prepare("UPDATE documents SET course_id = NULL WHERE course_id = :course_id");
            $disassociateStmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $disassociateStmt->execute();

            // Trong hàm editCourseByTeacher, thay thế phần "Update related documents" bằng:
            if (!empty($document_ids)) {
                $placeholders = [];
                $bindParams = [':account_id' => $_SESSION['account_id']];
                foreach ($document_ids as $index => $doc_id) {
                    $paramName = ':doc_id_' . $index;
                    $placeholders[] = $paramName;
                    $bindParams[$paramName] = (int)$doc_id;
                }
                $placeholdersStr = implode(',', $placeholders);
                $docCheckStmt = $this->db->prepare("
        SELECT document_id 
        FROM documents 
        WHERE document_id IN ($placeholdersStr) AND account_id = :account_id
    ");
                foreach ($bindParams as $key => $value) {
                    $docCheckStmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
                }
                $docCheckStmt->execute();
                $validDocs = $docCheckStmt->fetchAll(PDO::FETCH_COLUMN);

                foreach ($document_ids as $doc_id) {
                    if (in_array($doc_id, $validDocs)) {
                        $updateDocStmt = $this->db->prepare("
                UPDATE documents 
                SET course_id = :course_id 
                WHERE document_id = :document_id
            ");
                        $updateDocStmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
                        $updateDocStmt->bindValue(':document_id', $doc_id, PDO::PARAM_INT);
                        $updateDocStmt->execute();
                    }
                }
            }

            if ($result) {
                $this->db->commit();

                $message = "Khóa học \"" . htmlspecialchars($course_name) . "\" (ID: $course_id) đã được cập nhật thành công.";
                $this->notification->createNotification($_SESSION['account_id'], $message, false);

                echo json_encode(['success' => true, 'message' => 'Cập nhật khóa học thành công']);
            } else {
                $this->db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Không thể cập nhật khóa học']);
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Lỗi chỉnh sửa khóa học: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
        }
        exit;
    }

    public function manage()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['account_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
            $_SESSION['message'] = 'Chỉ giảng viên mới có quyền quản lý khóa học';
            $_SESSION['message_type'] = 'danger';
            header('Location: /study_sharing');
            exit;
        }

        try {
            $pdo = $this->db;

            $user = $this->user->getUserById($_SESSION['account_id']);

            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
            $status = isset($_GET['status']) && in_array(trim($_GET['status']), ['open', 'in_progress', 'closed', 'pending', 'cancelled']) ? trim($_GET['status']) : '';
            $itemsPerPage = 5;
            $offset = ($page - 1) * $itemsPerPage;

            $query = "SELECT c.*, 
                 (SELECT COUNT(*) FROM course_members cm WHERE cm.course_id = c.course_id) as member_count
              FROM courses c
              WHERE c.creator_id = :creator_id";
            $params = [':creator_id' => $_SESSION['account_id']];

            if (!empty($keyword)) {
                $query .= " AND (c.course_name LIKE :keyword1 OR c.description LIKE :keyword2)";
                $params[':keyword1'] = "%$keyword%";
                $params[':keyword2'] = "%$keyword%";
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
            $stmt->bindValue(':itemsPerPage', $itemsPerPage, PDO::PARAM_INT);
            $stmt->execute();
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($courses as &$course) {
                $documentsStmt = $this->db->prepare("
                SELECT d.document_id, d.title, d.file_path 
                FROM documents d 
                WHERE d.course_id = :course_id
            ");
                $documentsStmt->bindValue(':course_id', $course['course_id'], PDO::PARAM_INT);
                $documentsStmt->execute();
                $course['documents'] = $documentsStmt->fetchAll(PDO::FETCH_ASSOC);
            }
            unset($course);

            $countQuery = "SELECT COUNT(*) as total FROM courses WHERE creator_id = :creator_id";
            $countParams = [':creator_id' => $_SESSION['account_id']];

            if (!empty($keyword)) {
                $countQuery .= " AND (course_name LIKE :keyword1 OR description LIKE :keyword2)";
                $countParams[':keyword1'] = "%$keyword%";
                $countParams[':keyword2'] = "%$keyword%";
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
            $totalPages = ceil($totalCourses / $itemsPerPage);

            $title = 'Quản lý khóa học';
            ob_start();
            require __DIR__ . '/../views/course/teacher_manage.php';
            $content = ob_get_clean();
            require __DIR__ . '/../views/layouts/layout.php';
        } catch (Exception $e) {
            error_log("Manage courses error: " . $e->getMessage());
            $_SESSION['message'] = 'Lỗi server khi tải khóa học: ' . $e->getMessage();
            $_SESSION['message_type'] = 'danger';
            header('Location: /study_sharing');
            exit;
        }
    }

    public function requestOpenCourse()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['account_id']) || $_SESSION['role'] !== 'teacher') {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền gửi yêu cầu mở khóa học']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        error_log("Input data: " . print_r($data, true));
        $course_id = (int)($data['course_id'] ?? 0);

        if ($course_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID khóa học không hợp lệ']);
            exit;
        }

        try {
            $course = $this->course->getCourseById($course_id);
            if (!$course || $course['creator_id'] != $_SESSION['account_id']) {
                echo json_encode(['success' => false, 'message' => 'Khóa học không tồn tại hoặc bạn không phải người tạo']);
                exit;
            }

            if ($course['status'] !== 'closed') {
                echo json_encode(['success' => false, 'message' => 'Khóa học không ở trạng thái đóng']);
                exit;
            }

            $adminStmt = $this->db->prepare("SELECT account_id FROM accounts WHERE role = 'admin'");
            $adminStmt->execute();
            $admins = $adminStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($admins)) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy admin để gửi yêu cầu']);
                exit;
            }

            $success = true;
            foreach ($admins as $admin) {
                if (!is_int($admin['account_id']) || $admin['account_id'] <= 0) {
                    error_log("Invalid admin account_id: " . var_export($admin['account_id'], true));
                    continue;
                }
                $message = "Giảng viên " . htmlspecialchars($_SESSION['full_name']) . " yêu cầu mở khóa học \"" . htmlspecialchars($course['course_name']) . "\" (ID: $course_id)";
                $result = $this->notification->createNotification($admin['account_id'], $message, false);
                if (!$result) {
                    $success = false;
                    error_log("Failed to send notification to admin ID: " . $admin['account_id']);
                }
            }

            $teacher_message = "Yêu cầu mở khóa học \"" . htmlspecialchars($course['course_name']) . "\" đã được gửi tới admin";
            $this->notification->createNotification($_SESSION['account_id'], $teacher_message, false);

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Yêu cầu mở khóa học đã được gửi tới tất cả admin']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gửi yêu cầu thất bại tới một số admin']);
            }
        } catch (Exception $e) {
            error_log("Request open course error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()]);
        }
        exit;
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

        if (!isset($_SESSION['account_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
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
            $query = "SELECT c.*, u.full_name,
            (SELECT COUNT(*) FROM course_members cm WHERE cm.course_id = c.course_id) as member_count
        FROM courses c
        LEFT JOIN users u ON c.creator_id = u.account_id
        WHERE c.course_id = :course_id AND c.creator_id = :creator_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->bindValue(':creator_id', $_SESSION['account_id'], PDO::PARAM_INT);
            $stmt->execute();
            $course = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($course) {
                $documentsStmt = $this->db->prepare("
                SELECT d.document_id, d.title, d.file_path 
                FROM documents d 
                WHERE d.course_id = :course_id
            ");
                $documentsStmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
                $documentsStmt->execute();
                $course['documents'] = $documentsStmt->fetchAll(PDO::FETCH_ASSOC);

                error_log("Course details: " . print_r($course, true));
                echo json_encode(['success' => true, 'course' => $course]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Khóa học không tồn tại hoặc bạn không phải người tạo!']);
            }
        } catch (Exception $e) {
            error_log("Get course details error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
        }
        exit;
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

        if (!isset($_SESSION['account_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
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
            $query = "SELECT c.course_id FROM courses c WHERE c.course_id = :course_id AND c.creator_id = :creator_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->bindValue(':creator_id', $_SESSION['account_id'], PDO::PARAM_INT);
            $stmt->execute();
            $course = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$course) {
                echo json_encode(['success' => false, 'message' => 'Khóa học không tồn tại hoặc bạn không phải người tạo!']);
                exit;
            }

            $query = "SELECT cm.course_member_id, u.full_name, u.account_id, cm.join_date
                    FROM course_members cm
                    JOIN users u ON cm.account_id = u.account_id
                    WHERE cm.course_id = :course_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->execute();
            $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'members' => $members]);
        } catch (Exception $e) {
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

        if (!isset($_SESSION['account_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
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
            $this->db->beginTransaction();

            $query = "SELECT c.course_id, c.course_name FROM courses c WHERE c.course_id = :course_id AND c.creator_id = :creator_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->bindValue(':creator_id', $_SESSION['account_id'], PDO::PARAM_INT);
            $stmt->execute();
            $course = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$course) {
                $this->db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Khóa học không tồn tại hoặc bạn không phải người tạo!']);
                exit;
            }

            $query = "SELECT cm.account_id, u.full_name
                    FROM course_members cm
                    JOIN users u ON cm.account_id = u.account_id
                    WHERE cm.course_member_id = :course_member_id AND cm.course_id = :course_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':course_member_id', $course_member_id, PDO::PARAM_INT);
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->execute();
            $member = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$member) {
                $this->db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Thành viên không tồn tại trong khóa học!']);
                exit;
            }

            $query = "DELETE FROM course_members WHERE course_member_id = :course_member_id AND course_id = :course_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':course_member_id', $course_member_id, PDO::PARAM_INT);
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $result = $stmt->execute();

            if ($result) {
                $this->db->commit();

                $message = "Bạn đã bị xóa khỏi khóa học \"" . htmlspecialchars($course['course_name']) . "\".";
                $this->notification->createNotification($member['account_id'], $message, false);

                $teacher_message = "Bạn đã xóa sinh viên " . htmlspecialchars($member['full_name']) . " khỏi khóa học \"" . htmlspecialchars($course['course_name']) . "\".";
                $this->notification->createNotification($_SESSION['account_id'], $teacher_message, false);

                echo json_encode(['success' => true, 'message' => 'Xóa thành viên thành công']);
            } else {
                $this->db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Không thể xóa thành viên']);
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Remove course member error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
        }
        exit;
    }

    public function getAvailableDocuments()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ!']);
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['account_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xem danh sách tài liệu!']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $course_id = isset($data['course_id']) ? (int)$data['course_id'] : 0;

        if ($course_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID khóa học không hợp lệ!']);
            exit;
        }

        try {
            // Kiểm tra xem giảng viên có quyền chỉnh sửa khóa học này không
            $query = "SELECT course_id FROM courses WHERE course_id = :course_id AND creator_id = :creator_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->bindValue(':creator_id', $_SESSION['account_id'], PDO::PARAM_INT);
            $stmt->execute();
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền truy cập khóa học này!']);
                exit;
            }

            // Lấy tài liệu liên quan đến khóa học và tài liệu chưa gán của giảng viên
            $query = "
            SELECT d.document_id, d.title, d.file_path, d.course_id, c.category_name, u.full_name 
            FROM documents d 
            LEFT JOIN categories c ON d.category_id = c.category_id
            LEFT JOIN users u ON d.account_id = u.account_id
            WHERE d.account_id = :account_id 
            AND (d.course_id = :course_id OR d.course_id IS NULL)
        ";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':account_id', $_SESSION['account_id'], PDO::PARAM_INT);
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->execute();
            $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'documents' => $documents]);
        } catch (Exception $e) {
            error_log("Get available documents error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
        }
        exit;
    }
}
