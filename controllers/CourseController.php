<?php

namespace App;

use PDO;
use Exception;

class CourseController
{
    private $db;
    private $course;
    private $user;

    public function __construct($db)
    {
        $this->db = $db;
        $this->course = new Course($db);
        $this->user = new User($db);
    }

    public function list()
    {
        $query = isset($_GET['query']) ? trim($_GET['query']) : '';
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 10;

        $sql = "SELECT c.*, u.full_name 
                FROM courses c 
                LEFT JOIN users u ON c.creator_id = u.account_id";
        $bindParams = [];
        $hasWhere = false;

        if ($query !== '') {
            $sql .= $hasWhere ? " AND " : " WHERE ";
            $sql .= "(c.course_name LIKE :query1 OR c.description LIKE :query2)";
            $bindParams[':query1'] = "%$query%";
            $bindParams[':query2'] = "%$query%";
            $hasWhere = true;
        }

        $countSql = "SELECT COUNT(*) FROM courses c";
        $countBindParams = [];
        if ($query !== '') {
            $countSql .= " WHERE (c.course_name LIKE :query1 OR c.description LIKE :query2)";
            $countBindParams[':query1'] = "%$query%";
            $countBindParams[':query2'] = "%$query%";
        }

        $countStmt = $this->db->prepare($countSql);
        foreach ($countBindParams as $key => $value) {
            $countStmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $countStmt->execute();
        $total = $countStmt->fetchColumn();

        $sql .= " ORDER BY c.created_at DESC LIMIT :offset, :perPage";
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
        $course = $this->course->getCourseById($course_id);
        if (!$course) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Khóa học không tồn tại']);
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
}
