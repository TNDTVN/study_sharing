<?php

namespace App;

use PDO;
use Exception;
use DateTime;
use DateInterval;

class DocumentController
{
    private $db;
    private $document;
    private $category;
    private $tag;
    private $comment;
    private $user;
    private $notification;
    private $uploadDir = 'uploads/';
    private $itemsPerPage = 10;

    public function __construct($db)
    {
        $this->db = $db;
        $this->document = new Document($db);
        $this->category = new Category($db);
        $this->tag = new Tag($db);
        $this->comment = new Comment($db);
        $this->user = new User($db);
        $this->notification = new Notification($db);
        $this->ensureUploadDirectory();
    }

    private function ensureUploadDirectory()
    {
        $absolutePath = __DIR__ . '/../' . $this->uploadDir;
        if (!is_dir($absolutePath)) {
            mkdir($absolutePath, 0775, true);
            chmod($absolutePath, 0775);
        }
        if (!is_writable($absolutePath)) {
            error_log("Thư mục {$absolutePath} không có quyền ghi!");
        }
    }

    public function list()
    {
        $valid_file_types = ['pdf', 'docx', 'pptx'];
        $query = isset($_GET['query']) ? trim($_GET['query']) : '';
        $category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
        $file_type = (isset($_GET['file_type']) && in_array(trim($_GET['file_type']), $valid_file_types)) ? trim($_GET['file_type']) : '';
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 10;

        $sql = "SELECT d.*, c.category_name, u.full_name, co.course_name 
                FROM documents d 
                LEFT JOIN categories c ON d.category_id = c.category_id
                LEFT JOIN users u ON d.account_id = u.account_id
                LEFT JOIN courses co ON d.course_id = co.course_id";
        $bindParams = [];
        $hasWhere = false;

        if (isset($_SESSION['account_id'])) {
            $sql .= " WHERE (d.visibility = 'public' OR d.account_id = :account_id)";
            $bindParams[':account_id'] = $_SESSION['account_id'];
            $hasWhere = true;
        } else {
            $sql .= " WHERE d.visibility = 'public'";
            $hasWhere = true;
        }

        if ($query !== '') {
            $sql .= $hasWhere ? " AND " : " WHERE ";
            $sql .= "(d.title LIKE :query1 OR d.description LIKE :query2)";
            $bindParams[':query1'] = "%$query%";
            $bindParams[':query2'] = "%$query%";
            $hasWhere = true;
        }

        if ($category_id > 0) {
            $sql .= $hasWhere ? " AND " : " WHERE ";
            $sql .= "d.category_id = :category_id";
            $bindParams[':category_id'] = $category_id;
            $hasWhere = true;
        }

        if ($file_type !== '') {
            $sql .= $hasWhere ? " AND " : " WHERE ";
            $sql .= "d.file_path LIKE :file_type";
            $bindParams[':file_type'] = "%.$file_type";
            $hasWhere = true;
        }

        $countSql = "SELECT COUNT(*) FROM documents d";
        $countBindParams = [];
        $hasCountWhere = false;

        if (isset($_SESSION['account_id'])) {
            $countSql .= " WHERE (d.visibility = 'public' OR d.account_id = :account_id)";
            $countBindParams[':account_id'] = $_SESSION['account_id'];
            $hasCountWhere = true;
        } else {
            $countSql .= " WHERE d.visibility = 'public'";
            $hasCountWhere = true;
        }

        if ($query !== '') {
            $countSql .= $hasCountWhere ? " AND " : " WHERE ";
            $countSql .= "(d.title LIKE :query1 OR d.description LIKE :query2)";
            $countBindParams[':query1'] = "%$query%";
            $countBindParams[':query2'] = "%$query%";
            $hasCountWhere = true;
        }

        if ($category_id > 0) {
            $countSql .= $hasCountWhere ? " AND " : " WHERE ";
            $countSql .= "d.category_id = :category_id";
            $countBindParams[':category_id'] = $category_id;
            $hasCountWhere = true;
        }

        if ($file_type !== '') {
            $countSql .= $hasCountWhere ? " AND " : " WHERE ";
            $countSql .= "d.file_path LIKE :file_type";
            $countBindParams[':file_type'] = "%.$file_type";
            $hasCountWhere = true;
        }

        $countStmt = $this->db->prepare($countSql);
        foreach ($countBindParams as $key => $value) {
            $countStmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $countStmt->execute();
        $total = $countStmt->fetchColumn();

        $sql .= " ORDER BY d.upload_date DESC LIMIT :offset, :perPage";
        $stmt = $this->db->prepare($sql);
        foreach ($bindParams as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $categories = $this->category->getAllCategories();

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

        $totalPages = ceil($total / $perPage);

        $title = 'Danh sách tài liệu';
        $layout = 'layout.php';
        ob_start();
        require __DIR__ . '/../views/document/list.php';
        $content = ob_get_clean();
        $pdo = $this->db;
        require __DIR__ . '/../views/layouts/' . $layout;
    }

    public function detail($document_id)
    {
        $document = $this->document->getDocumentById($document_id);
        if (!$document || ($document['visibility'] !== 'public' && (!isset($_SESSION['account_id']) || $_SESSION['account_id'] != $document['account_id']))) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Tài liệu không tồn tại hoặc không có quyền truy cập']);
            exit;
        }
        $file_ext = strtolower(pathinfo($document['file_path'], PATHINFO_EXTENSION));
        $category = $document['category_id'] ? $this->category->getCategoryById($document['category_id']) : null;
        $uploader = $document['account_id'] ? $this->user->getUserById($document['account_id']) : null;

        $tags = $this->db->prepare("SELECT t.tag_name FROM document_tags dt JOIN tags t ON dt.tag_id = t.tag_id WHERE dt.document_id = :document_id");
        $tags->bindValue(':document_id', $document_id, PDO::PARAM_INT);
        $tags->execute();
        $document['tags'] = $tags->fetchAll(PDO::FETCH_COLUMN);

        $commentData = $this->comment->getCommentsByDocumentId($document_id, 5, 0);
        $comments = $commentData['comments'];
        $totalComments = $commentData['total'];

        function attachUserInfo(&$comments, $userModel)
        {
            foreach ($comments as &$comment) {
                $comment['user'] = $userModel->getUserById($comment['account_id']);
                if (!$comment['user']) {
                    $comment['user'] = ['avatar' => null, 'full_name' => 'Ẩn danh'];
                    error_log("User not found for comment ID: " . $comment['comment_id'] . ", Account ID: " . $comment['account_id']);
                }
                if (!empty($comment['replies'])) {
                    attachUserInfo($comment['replies'], $userModel);
                }
            }
            unset($comment);
        }

        attachUserInfo($comments, $this->user);

        $documentVersion = new DocumentVersion($this->db);
        $versions = $documentVersion->getVersionsByDocumentId($document_id);

        $title = $document['title'];
        $layout = 'layout.php';
        ob_start();
        require __DIR__ . '/../views/document/detail.php';
        $content = ob_get_clean();
        $pdo = $this->db;
        require __DIR__ . '/../views/layouts/' . $layout;
    }

    public function comment()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        if (!isset($_SESSION['account_id'])) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để bình luận']);
            exit;
        }

        $document_id = isset($_POST['document_id']) ? (int)$_POST['document_id'] : 0;
        $comment_text = isset($_POST['comment_text']) ? trim($_POST['comment_text']) : '';

        if ($document_id <= 0 || empty($comment_text)) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }

        $document = $this->document->getDocumentById($document_id);
        if (!$document) {
            echo json_encode(['success' => false, 'message' => 'Tài liệu không tồn tại']);
            exit;
        }

        $success = $this->comment->createComment($document_id, $_SESSION['account_id'], $comment_text);
        if ($success) {
            // Gửi thông báo đến chủ tài liệu nếu người bình luận không phải chủ tài liệu
            if ($_SESSION['account_id'] != $document['account_id']) {
                $commenter = $this->user->getUserById($_SESSION['account_id']);
                $commenter_name = $commenter ? htmlspecialchars($commenter['full_name']) : 'Ẩn danh';
                $message = "$commenter_name đã bình luận trên tài liệu của bạn: \"" . htmlspecialchars($document['title']) . "\"";
                $this->notification->createNotification($document['account_id'], $message, false);
            }
            echo json_encode(['success' => true, 'message' => 'Bình luận đã được gửi']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi gửi bình luận']);
        }
    }

    public function replyComment()
    {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed', 405);
            }

            if (!isset($_SESSION['account_id'])) {
                throw new Exception('Vui lòng đăng nhập để trả lời', 401);
            }

            $document_id = isset($_POST['document_id']) ? (int)$_POST['document_id'] : 0;
            $parent_comment_id = isset($_POST['parent_comment_id']) ? (int)$_POST['parent_comment_id'] : 0;
            $comment_text = isset($_POST['comment_text']) ? trim($_POST['comment_text']) : '';
            $tagged_user_id = isset($_POST['tagged_user_id']) ? (int)$_POST['tagged_user_id'] : 0;

            if ($document_id <= 0 || $parent_comment_id <= 0 || empty($comment_text)) {
                throw new Exception('Dữ liệu không hợp lệ', 400);
            }

            $document = $this->document->getDocumentById($document_id);
            if (!$document) {
                throw new Exception('Tài liệu không tồn tại', 404);
            }

            $parentComment = $this->comment->getCommentById($parent_comment_id);
            if (!$parentComment || $parentComment['document_id'] != $document_id) {
                throw new Exception('Bình luận cha không tồn tại hoặc không thuộc tài liệu này', 404);
            }

            $final_parent_comment_id = $parent_comment_id;

            if ($parentComment['parent_comment_id'] !== null) {
                $grandParentComment = $this->comment->getCommentById($parentComment['parent_comment_id']);
                if ($grandParentComment && $grandParentComment['parent_comment_id'] === null) {
                    $final_parent_comment_id = $parent_comment_id;
                } else {
                    $final_parent_comment_id = $parentComment['parent_comment_id'];
                    if ($tagged_user_id > 0) {
                        $taggedUser = $this->user->getUserById($tagged_user_id);
                        if ($taggedUser) {
                            $comment_text = '<span class="tagged-user">@' . htmlspecialchars($taggedUser['full_name'], ENT_QUOTES, 'UTF-8') . '</span> ' . htmlspecialchars($comment_text, ENT_QUOTES, 'UTF-8');
                        }
                    }
                }
            } else {
                $final_parent_comment_id = $parent_comment_id;
                if ($tagged_user_id > 0) {
                    $taggedUser = $this->user->getUserById($tagged_user_id);
                    if ($taggedUser) {
                        $comment_text = '<span class="tagged-user">@' . htmlspecialchars($taggedUser['full_name'], ENT_QUOTES, 'UTF-8') . '</span> ' . htmlspecialchars($comment_text, ENT_QUOTES, 'UTF-8');
                    }
                }
            }

            $success = $this->comment->createComment($document_id, $_SESSION['account_id'], $comment_text, $final_parent_comment_id);
            if ($success) {
                // Gửi thông báo đến người được trả lời (người sở hữu bình luận cha) nếu không phải chính họ
                if ($tagged_user_id > 0 && $tagged_user_id != $_SESSION['account_id']) {
                    $replier = $this->user->getUserById($_SESSION['account_id']);
                    $replier_name = $replier ? htmlspecialchars($replier['full_name']) : 'Ẩn danh';
                    $message = "$replier_name đã trả lời bình luận của bạn trong tài liệu: \"" . htmlspecialchars($document['title']) . "\"";
                    $this->notification->createNotification($tagged_user_id, $message, false);
                }
                // Gửi thông báo đến chủ tài liệu nếu người trả lời không phải chủ tài liệu
                if ($_SESSION['account_id'] != $document['account_id']) {
                    $replier = $this->user->getUserById($_SESSION['account_id']);
                    $replier_name = $replier ? htmlspecialchars($replier['full_name']) : 'Ẩn danh';
                    $message = "$replier_name đã trả lời một bình luận trong tài liệu của bạn: \"" . htmlspecialchars($document['title']) . "\"";
                    $this->notification->createNotification($document['account_id'], $message, false);
                }
                echo json_encode(['success' => true, 'message' => 'Trả lời đã được gửi']);
            } else {
                throw new Exception('Lỗi khi gửi trả lời', 500);
            }
        } catch (Exception $e) {
            http_response_code($e->getCode() ?: 500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteComment()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        if (!isset($_SESSION['account_id'])) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để xóa bình luận']);
            exit;
        }

        $comment_id = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;
        if ($comment_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }

        $success = $this->comment->deleteComment($comment_id, $_SESSION['account_id']);
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Bình luận đã được xóa']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể xóa bình luận. Có thể bình luận không phải của bạn hoặc đã quá 1 giờ.']);
        }
    }

    public function rateDocument()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        if (!isset($_SESSION['account_id'])) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để đánh giá']);
            exit;
        }

        $document_id = isset($_POST['document_id']) ? (int)$_POST['document_id'] : 0;
        $rating_value = isset($_POST['rating_value']) ? (int)$_POST['rating_value'] : 0;
        $account_id = $_SESSION['account_id'];

        if ($document_id <= 0 || $rating_value < 1 || $rating_value > 5) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }

        $document = $this->document->getDocumentById($document_id);
        if (!$document) {
            echo json_encode(['success' => false, 'message' => 'Tài liệu không tồn tại']);
            exit;
        }

        $rating = new Rating($this->db);

        $existingRatingStmt = $this->db->prepare("SELECT rating_id FROM ratings WHERE document_id = :document_id AND account_id = :account_id");
        $existingRatingStmt->bindValue(':document_id', $document_id, PDO::PARAM_INT);
        $existingRatingStmt->bindValue(':account_id', $account_id, PDO::PARAM_INT);
        $existingRatingStmt->execute();
        $existingRating = $existingRatingStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingRating) {
            $updateStmt = $this->db->prepare("UPDATE ratings SET rating_value = :rating_value, created_at = NOW() WHERE rating_id = :rating_id");
            $updateStmt->bindValue(':rating_value', $rating_value, PDO::PARAM_INT);
            $updateStmt->bindValue(':rating_id', $existingRating['rating_id'], PDO::PARAM_INT);
            $success = $updateStmt->execute();
        } else {
            $success = $rating->createRating($document_id, $account_id, $rating_value);
        }

        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Đánh giá đã được gửi']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi gửi đánh giá']);
        }
    }

    public function recordDownload()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $document_id = isset($_POST['document_id']) ? (int)$_POST['document_id'] : 0;

        if ($document_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }

        $document = $this->document->getDocumentById($document_id);
        // Kiểm tra quyền truy cập: tài liệu công khai hoặc thuộc về người dùng
        if (!$document || ($document['visibility'] !== 'public' && (!isset($_SESSION['account_id']) || $_SESSION['account_id'] != $document['account_id']))) {
            echo json_encode(['success' => false, 'message' => 'Tài liệu không tồn tại hoặc không có quyền truy cập']);
            exit;
        }

        if (isset($_SESSION['account_id'])) {
            $account_id = $_SESSION['account_id'];
            $download = new Download($this->db);

            $existingDownloadStmt = $this->db->prepare("SELECT download_id FROM downloads WHERE document_id = :document_id AND account_id = :account_id");
            $existingDownloadStmt->bindValue(':document_id', $document_id, PDO::PARAM_INT);
            $existingDownloadStmt->bindValue(':account_id', $account_id, PDO::PARAM_INT);
            $existingDownloadStmt->execute();
            $existingDownload = $existingDownloadStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingDownload) {
                $updateStmt = $this->db->prepare("UPDATE downloads SET download_date = NOW() WHERE download_id = :download_id");
                $updateStmt->bindValue(':download_id', $existingDownload['download_id'], PDO::PARAM_INT);
                $success = $updateStmt->execute();
            } else {
                $success = $download->recordDownload($document_id, $account_id);
            }

            if (!$success) {
                echo json_encode(['success' => false, 'message' => 'Lỗi khi ghi nhận tải xuống']);
                exit;
            }
        }

        echo json_encode(['success' => true, 'message' => 'Tải xuống được phép']);
    }

    public function loadMoreComments()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $document_id = isset($_POST['document_id']) ? (int)$_POST['document_id'] : 0;
        $offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;

        if ($document_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }

        $result = $this->comment->getCommentsByDocumentId($document_id, 5, $offset);
        $comments = $result['comments'];
        $totalComments = $result['total'];

        echo json_encode([
            'success' => true,
            'comments' => $comments,
            'hasMore' => $offset + count($comments) < $totalComments
        ]);
    }

    public function downloadHistory()
    {
        if (!isset($_SESSION['account_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để xem lịch sử tải tài liệu']);
            exit;
        }

        $query = isset($_GET['query']) ? trim($_GET['query']) : '';
        $category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
        $file_type = isset($_GET['file_type']) && in_array(trim($_GET['file_type']), ['pdf', 'docx', 'pptx']) ? trim($_GET['file_type']) : '';
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 10;

        $sql = "SELECT d.*, c.category_name, u.full_name, co.course_name, dl.download_date
                FROM documents d
                LEFT JOIN categories c ON d.category_id = c.category_id
                LEFT JOIN users u ON d.account_id = u.account_id
                LEFT JOIN courses co ON d.course_id = co.course_id
                JOIN downloads dl ON d.document_id = dl.document_id
                WHERE dl.account_id = :account_id";
        $bindParams = [':account_id' => $_SESSION['account_id']];
        $hasWhere = true;

        if ($query !== '') {
            $sql .= " AND (d.title LIKE :query1 OR d.description LIKE :query2)";
            $bindParams[':query1'] = "%$query%";
            $bindParams[':query2'] = "%$query%";
        }

        if ($category_id > 0) {
            $sql .= " AND d.category_id = :category_id";
            $bindParams[':category_id'] = $category_id;
        }

        if ($file_type !== '') {
            $sql .= " AND d.file_path LIKE :file_type";
            $bindParams[':file_type'] = "%.$file_type";
        }

        $countSql = "SELECT COUNT(*)
                    FROM documents d
                    JOIN downloads dl ON d.document_id = dl.document_id
                    WHERE dl.account_id = :account_id";
        $countBindParams = [':account_id' => $_SESSION['account_id']];
        if ($query !== '') {
            $countSql .= " AND (d.title LIKE :query1 OR d.description LIKE :query2)";
            $countBindParams[':query1'] = "%$query%";
            $countBindParams[':query2'] = "%$query%";
        }

        if ($category_id > 0) {
            $countSql .= " AND d.category_id = :category_id";
            $countBindParams[':category_id'] = $category_id;
        }

        if ($file_type !== '') {
            $countSql .= " AND d.file_path LIKE :file_type";
            $countBindParams[':file_type'] = "%.$file_type";
        }

        $countStmt = $this->db->prepare($countSql);
        foreach ($countBindParams as $key => $value) {
            $countStmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $countStmt->execute();
        $total = $countStmt->fetchColumn();

        $sql .= " ORDER BY dl.download_date DESC LIMIT :offset, :perPage";
        $stmt = $this->db->prepare($sql);
        foreach ($bindParams as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $categories = $this->category->getAllCategories();

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

        $totalPages = ceil($total / $perPage);

        $title = 'Lịch sử tải tài liệu';
        $layout = 'layout.php';
        ob_start();
        require __DIR__ . '/../views/document/download_history.php';
        $content = ob_get_clean();
        $pdo = $this->db;
        require __DIR__ . '/../views/layouts/' . $layout;
    }

    public function create()
    {
        // Kiểm tra người dùng đã đăng nhập
        if (!isset($_SESSION['account_id'])) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để tải lên tài liệu']);
            exit;
        }

        $user = $this->user->getUserById($_SESSION['account_id']);
        if (!in_array($user['role'], ['teacher', 'student'])) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền tải lên tài liệu']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Xử lý yêu cầu POST (tải lên tài liệu)
            try {
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $category_id = (int)($_POST['category_id'] ?? 0);
                $course_id = !empty($_POST['course_id']) ? (int)$_POST['course_id'] : null;
                $visibility = in_array($_POST['visibility'] ?? '', ['public', 'private']) ? $_POST['visibility'] : 'public';
                $tags = !empty($_POST['tags']) ? array_map('trim', explode(',', $_POST['tags'])) : [];

                // Kiểm tra dữ liệu đầu vào
                if (empty($title) || empty($_FILES['file']['name'])) {
                    throw new Exception('Tiêu đề và tệp tài liệu là bắt buộc!');
                }

                // Kiểm tra lỗi upload
                if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                    $uploadErrors = [
                        UPLOAD_ERR_INI_SIZE => 'Kích thước tệp vượt quá giới hạn php.ini!',
                        UPLOAD_ERR_FORM_SIZE => 'Kích thước tệp vượt quá giới hạn form!',
                        UPLOAD_ERR_PARTIAL => 'Tệp chỉ được tải lên một phần!',
                        UPLOAD_ERR_NO_FILE => 'Không có tệp nào được tải lên!',
                        UPLOAD_ERR_NO_TMP_DIR => 'Thiếu thư mục tạm để tải lên!',
                        UPLOAD_ERR_CANT_WRITE => 'Không thể ghi tệp vào đĩa!',
                        UPLOAD_ERR_EXTENSION => 'Phần mở rộng PHP ngăn tải lên!'
                    ];
                    throw new Exception($uploadErrors[$_FILES['file']['error']] ?? 'Lỗi không xác định khi tải tệp!');
                }

                // Kiểm tra file
                $file = $_FILES['file'];
                $allowed_types = ['pdf', 'docx', 'pptx'];
                $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($file_ext, $allowed_types)) {
                    throw new Exception('Chỉ chấp nhận các định crawl: ' . implode(', ', $allowed_types));
                }

                if ($file['size'] > 10 * 1024 * 1024) {
                    throw new Exception('Kích thước tệp không được vượt quá 10MB!');
                }

                // Xử lý upload file
                $file_name = uniqid() . '.' . $file_ext;
                $file_path = $this->uploadDir . $file_name;
                $absolute_file_path = __DIR__ . '/../' . $file_path;

                if (!move_uploaded_file($file['tmp_name'], $absolute_file_path)) {
                    error_log("Failed to move uploaded file to: {$absolute_file_path}");
                    throw new Exception('Lổi khi di chuyển tệp đến thư mục uploads!');
                }

                // Thêm tài liệu vào database
                $query = "INSERT INTO documents (title, description, file_path, account_id, category_id, course_id, visibility, upload_date) 
                    VALUES (:title, :description, :file_path, :account_id, :category_id, :course_id, :visibility, NOW())";
                $stmt = $this->db->prepare($query);
                $stmt->bindValue(':title', $title, PDO::PARAM_STR);
                $stmt->bindValue(':description', $description, PDO::PARAM_STR);
                $stmt->bindValue(':file_path', $file_name, PDO::PARAM_STR);
                $stmt->bindValue(':account_id', $_SESSION['account_id'], PDO::PARAM_INT);
                $stmt->bindValue(':category_id', $category_id ?: null, $category_id ? PDO::PARAM_INT : PDO::PARAM_NULL);
                $stmt->bindValue(':course_id', $course_id, $course_id ? PDO::PARAM_INT : PDO::PARAM_NULL);
                $stmt->bindValue(':visibility', $visibility, PDO::PARAM_STR);
                $stmt->execute();

                $document_id = $this->db->lastInsertId();

                // Thêm phiên bản đầu tiên vào document_versions
                $documentVersion = new DocumentVersion($this->db);
                $version_number = 1;
                $change_note = 'Phiên bản đầu tiên';
                $success = $documentVersion->createVersion($document_id, $version_number, $file_name, $change_note);

                if (!$success) {
                    throw new Exception('Lỗi khi tạo phiên bản tài liệu!');
                }

                // Thêm thẻ (tags)
                foreach ($tags as $tag_name) {
                    if (!empty($tag_name)) {
                        $tagStmt = $this->db->prepare("SELECT tag_id FROM tags WHERE tag_name = :tag_name");
                        $tagStmt->bindValue(':tag_name', $tag_name, PDO::PARAM_STR);
                        $tagStmt->execute();
                        $tag = $tagStmt->fetch(PDO::FETCH_ASSOC);

                        if (!$tag) {
                            $tagStmt = $this->db->prepare("INSERT INTO tags (tag_name) VALUES (:tag_name)");
                            $tagStmt->bindValue(':tag_name', $tag_name, PDO::PARAM_STR);
                            $tagStmt->execute();
                            $tag_id = $this->db->lastInsertId();
                        } else {
                            $tag_id = $tag['tag_id'];
                        }

                        $docTagStmt = $this->db->prepare("INSERT INTO document_tags (document_id, tag_id) VALUES (:document_id, :tag_id)");
                        $docTagStmt->bindValue(':document_id', $document_id, PDO::PARAM_INT);
                        $docTagStmt->bindValue(':tag_id', $tag_id, PDO::PARAM_INT);
                        $docTagStmt->execute();
                    }
                }

                // Gửi thông báo
                $this->notification->createNotification(
                    $_SESSION['account_id'],
                    "Tài liệu \"$title\" đã được tải lên thành công.",
                    false
                );

                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Tải lên tài liệu thành công!', 'redirect' => '/study_sharing/document/list']);
                exit;
            } catch (Exception $e) {
                header('Content-Type: application/json');
                http_response_code($e->getCode() ?: 500);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            }
        } else {
            // Hiển thị form tải lên (GET)
            $categories = $this->category->getAllCategories();
            $courses = [];
            if ($user['role'] === 'teacher') {
                $courseModel = new Course($this->db);
                $courses = $courseModel->getCoursesByTeacher($_SESSION['account_id']);
            } elseif ($user['role'] === 'student') {
                $courseModel = new Course($this->db);
                $courses = $courseModel->getCoursesByStudent($_SESSION['account_id']);
            }

            $tagsStmt = $this->db->prepare("SELECT tag_id, tag_name FROM tags ORDER BY tag_name");
            $tagsStmt->execute();
            $tags = $tagsStmt->fetchAll(PDO::FETCH_ASSOC);

            $title = 'Tải lên tài liệu';
            $layout = 'layout.php';
            ob_start();
            require __DIR__ . '/../views/document/create.php';
            $content = ob_get_clean();
            $pdo = $this->db;
            require __DIR__ . '/../views/layouts/' . $layout;
        }
    }

    public function manage()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Logic lấy dữ liệu tài liệu, phân trang, tìm kiếm...
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
        $category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
        $file_type = isset($_GET['file_type']) && in_array(trim($_GET['file_type']), ['pdf', 'docx', 'pptx']) ? trim($_GET['file_type']) : '';
        $offset = ($page - 1) * $this->itemsPerPage;

        try {
            $query = "SELECT d.*, c.category_name, u.full_name, co.course_name
                FROM documents d
                LEFT JOIN categories c ON d.category_id = c.category_id
                LEFT JOIN users u ON d.account_id = u.account_id
                LEFT JOIN courses co ON d.course_id = co.course_id
                WHERE d.account_id = :account_id";
            $params = [':account_id' => $_SESSION['account_id']];

            if (!empty($keyword)) {
                $query .= " AND (d.title LIKE :keyword1 OR d.description LIKE :keyword2)";
                $params[':keyword1'] = '%' . $keyword . '%';
                $params[':keyword2'] = '%' . $keyword . '%';
            }

            if ($category_id > 0) {
                $query .= " AND d.category_id = :category_id";
                $params[':category_id'] = $category_id;
            }

            if ($file_type !== '') {
                $query .= " AND d.file_path LIKE :file_type";
                $params[':file_type'] = "%.$file_type";
            }

            $query .= " ORDER BY d.upload_date DESC LIMIT :offset, :itemsPerPage";

            $stmt = $this->db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':itemsPerPage', $this->itemsPerPage, PDO::PARAM_INT);
            $stmt->execute();
            $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Lấy danh sách danh mục và khóa học
            $categoryStmt = $this->db->prepare("SELECT * FROM categories ORDER BY category_name");
            $categoryStmt->execute();
            $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

            $courseStmt = $this->db->prepare("SELECT course_id, course_name FROM courses ORDER BY course_name");
            $courseStmt->execute();
            $courses = $courseStmt->fetchAll(PDO::FETCH_ASSOC);

            // Lấy tổng số tài liệu
            $countQuery = "SELECT COUNT(*) as total FROM documents WHERE account_id = :account_id";
            $countParams = [':account_id' => $_SESSION['account_id']];
            if (!empty($keyword)) {
                $countQuery .= " AND (title LIKE :keyword1 OR description LIKE :keyword2)";
                $countParams[':keyword1'] = '%' . $keyword . '%';
                $countParams[':keyword2'] = '%' . $keyword . '%';
            }
            if ($category_id > 0) {
                $countQuery .= " AND category_id = :category_id";
                $countParams[':category_id'] = $category_id;
            }
            if ($file_type !== '') {
                $countQuery .= " AND file_path LIKE :file_type";
                $countParams[':file_type'] = "%.$file_type";
            }
            $countStmt = $this->db->prepare($countQuery);
            foreach ($countParams as $key => $value) {
                $countStmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $countStmt->execute();
            $totalDocuments = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            $totalPages = ceil($totalDocuments / $this->itemsPerPage);

            // Lấy thẻ và phiên bản
            foreach ($documents as &$document) {
                $tagStmt = $this->db->prepare("
                    SELECT t.tag_name
                    FROM tags t
                    JOIN document_tags dt ON t.tag_id = dt.tag_id
                    WHERE dt.document_id = :document_id
                ");
                $tagStmt->bindValue(':document_id', $document['document_id'], PDO::PARAM_INT);
                $tagStmt->execute();
                $document['tags'] = $tagStmt->fetchAll(PDO::FETCH_COLUMN);

                $versionStmt = $this->db->prepare("
                    SELECT version_number, file_path, change_note, created_at
                    FROM document_versions
                    WHERE document_id = :document_id
                    ORDER BY version_number DESC
                ");
                $versionStmt->bindValue(':document_id', $document['document_id'], PDO::PARAM_INT);
                $versionStmt->execute();
                $document['versions'] = $versionStmt->fetchAll(PDO::FETCH_ASSOC);
            }
            unset($document);

            // Tạo CSRF token nếu chưa có
            if (!isset($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }

            $title = 'Quản lý tài liệu của tôi';
            ob_start();
            require __DIR__ . '/../views/document/manage.php';
            $content = ob_get_clean();
            $pdo = $this->db;
            $layout = 'layout.php';
            require __DIR__ . '/../views/layouts/layout.php';
        } catch (\PDOException $e) {
            error_log("Manage documents error: " . $e->getMessage());
            $_SESSION['message'] = 'Lỗi server khi tải tài liệu: ' . $e->getMessage();
            $_SESSION['message_type'] = 'danger';
            header('Location: /study_sharing');
            exit;
        }
    }

    public function edit()
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['account_id']) || !in_array($_SESSION['role'], ['teacher', 'student'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để chỉnh sửa tài liệu!']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ!']);
            exit;
        }

        $document_id = isset($_POST['document_id']) ? (int)$_POST['document_id'] : 0;
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        $course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $visibility = isset($_POST['visibility']) && in_array($_POST['visibility'], ['public', 'private']) ? $_POST['visibility'] : 'private';
        $tags = isset($_POST['tags']) ? array_filter(array_map('trim', explode(',', $_POST['tags']))) : [];

        if ($document_id <= 0 || empty($title)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID tài liệu và tiêu đề là bắt buộc!']);
            exit;
        }

        try {
            $stmt = $this->db->prepare("SELECT * FROM documents WHERE document_id = :document_id AND account_id = :account_id");
            $stmt->execute([':document_id' => $document_id, ':account_id' => $_SESSION['account_id']]);
            $document = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$document) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Tài liệu không tồn tại hoặc bạn không có quyền chỉnh sửa!']);
                exit;
            }

            $this->db->beginTransaction();

            $updateStmt = $this->db->prepare("
                UPDATE documents
                SET title = :title, description = :description, category_id = :category_id, course_id = :course_id, visibility = :visibility
                WHERE document_id = :document_id
            ");
            $updateStmt->execute([
                ':title' => $title,
                ':description' => $description ?: null,
                ':category_id' => $category_id > 0 ? $category_id : null,
                ':course_id' => $course_id > 0 ? $course_id : null,
                ':visibility' => $visibility,
                ':document_id' => $document_id
            ]);

            $this->db->prepare("DELETE FROM document_tags WHERE document_id = :document_id")->execute([':document_id' => $document_id]);
            foreach ($tags as $tag_name) {
                if (!empty($tag_name)) {
                    $tagStmt = $this->db->prepare("SELECT tag_id FROM tags WHERE tag_name = :tag_name");
                    $tagStmt->execute([':tag_name' => $tag_name]);
                    $tag = $tagStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$tag) {
                        $tagStmt = $this->db->prepare("INSERT INTO tags (tag_name) VALUES (:tag_name)");
                        $tagStmt->execute([':tag_name' => $tag_name]);
                        $tag_id = $this->db->lastInsertId();
                    } else {
                        $tag_id = $tag['tag_id'];
                    }

                    $this->db->prepare("INSERT INTO document_tags (document_id, tag_id) VALUES (:document_id, :tag_id)")
                        ->execute([':document_id' => $document_id, ':tag_id' => $tag_id]);
                }
            }

            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'];
                $file_type = $_FILES['file']['type'];
                $file_ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

                if (!in_array($file_type, $allowed_types) || !in_array($file_ext, ['pdf', 'docx', 'pptx'])) {
                    $this->db->rollBack();
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Định dạng tệp không hợp lệ! Chỉ hỗ trợ PDF, DOCX, PPTX.']);
                    exit;
                }

                $upload_dir = __DIR__ . '/../uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $versionStmt = $this->db->prepare("SELECT MAX(version_number) as max_version FROM document_versions WHERE document_id = :document_id");
                $versionStmt->execute([':document_id' => $document_id]);
                $current_version = $versionStmt->fetch(PDO::FETCH_ASSOC)['max_version'] ?? 0;
                $new_version = $current_version + 1;

                $file_name = $document_id . '_v' . $new_version . '.' . $file_ext;
                $file_path = $file_name;

                if (!move_uploaded_file($_FILES['file']['tmp_name'], $upload_dir . $file_name)) {
                    $this->db->rollBack();
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Lỗi khi tải lên tệp!']);
                    exit;
                }

                $this->db->prepare("UPDATE documents SET file_path = :file_path WHERE document_id = :document_id")
                    ->execute([':file_path' => $file_path, ':document_id' => $document_id]);

                $this->db->prepare("
                    INSERT INTO document_versions (document_id, version_number, file_path, change_note, created_at)
                    VALUES (:document_id, :version_number, :file_path, :change_note, NOW())
                ")->execute([
                    ':document_id' => $document_id,
                    ':version_number' => $new_version,
                    ':file_path' => $file_path,
                    ':change_note' => 'Cập nhật tệp mới'
                ]);
            }

            $this->db->commit();
            echo json_encode(['success' => true, 'message' => 'Cập nhật tài liệu thành công!', 'redirect' => '/study_sharing/document/manage']);
        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Lỗi server khi cập nhật tài liệu: ' . $e->getMessage()]);
        }
        exit;
    }

    public function delete()
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['account_id']) || !in_array($_SESSION['role'], ['teacher', 'student(link:javascript:;student'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để xóa tài liệu!']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ!']);
            exit;
        }

        $document_id = isset($_POST['document_id']) ? (int)$_POST['document_id'] : 0;

        if ($document_id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID tài liệu không hợp lệ!']);
            exit;
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT file_path FROM documents WHERE document_id = :document_id AND account_id = :account_id");
            $stmt->execute([':document_id' => $document_id, ':account_id' => $_SESSION['account_id']]);
            $document = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$document) {
                $this->db->rollBack();
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Tài liệu không tồn tại hoặc bạn không có quyền xóa!']);
                exit;
            }

            $counts = [
                'comment_count' => 0,
                'download_count' => 0,
                'rating_count' => 0
            ];

            $checkStmt = $this->db->prepare("SELECT COUNT(*) as count FROM comments WHERE document_id = :document_id");
            $checkStmt->bindValue(':document_id', $document_id, PDO::PARAM_INT);
            $checkStmt->execute();
            $counts['comment_count'] = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];

            $checkStmt = $this->db->prepare("SELECT COUNT(*) as count FROM downloads WHERE document_id = :document_id");
            $checkStmt->bindValue(':document_id', $document_id, PDO::PARAM_INT);
            $checkStmt->execute();
            $counts['download_count'] = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];

            $checkStmt = $this->db->prepare("SELECT COUNT(*) as count FROM ratings WHERE document_id = :document_id");
            $checkStmt->bindValue(':document_id', $document_id, PDO::PARAM_INT);
            $checkStmt->execute();
            $counts['rating_count'] = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];

            if (
                $counts['comment_count'] > 0 ||
                $counts['download_count'] > 0 ||
                $counts['rating_count'] > 0
            ) {
                $this->db->rollBack();
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Không thể xóa tài liệu vì có dữ liệu liên quan (bình luận, lượt tải, hoặc đánh giá)!']);
                exit;
            }

            $versionStmt = $this->db->prepare("SELECT file_path FROM document_versions WHERE document_id = :document_id");
            $versionStmt->bindValue(':document_id', $document_id, PDO::PARAM_INT);
            $versionStmt->execute();
            $versionFiles = $versionStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($versionFiles as $version) {
                $file_path = __DIR__ . '/../Uploads/' . $version['file_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                $converted_dir = __DIR__ . '/../Uploads/converted/';
                $converted_file_name = pathinfo($version['file_path'], PATHINFO_FILENAME) . '.pdf';
                $converted_file_path = $converted_dir . $converted_file_name;
                if (file_exists($converted_file_path)) {
                    unlink($converted_file_path);
                }
            }

            $this->db->prepare("DELETE FROM document_versions WHERE document_id = :document_id")
                ->execute([':document_id' => $document_id]);

            $this->db->prepare("DELETE FROM document_tags WHERE document_id = :document_id")
                ->execute([':document_id' => $document_id]);

            $file_path = __DIR__ . '/../Uploads/' . $document['file_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }

            $deleteStmt = $this->db->prepare("DELETE FROM documents WHERE document_id = :document_id");
            $deleteStmt->bindValue(':document_id', $document_id, PDO::PARAM_INT);
            $deleteStmt->execute();

            $affected = $deleteStmt->rowCount();
            if ($affected > 0) {
                $this->db->commit();
                echo json_encode(['success' => true, 'message' => 'Xóa tài liệu thành công!']);
            } else {
                $this->db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Tài liệu không tồn tại!']);
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Delete document error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Lỗi server khi xóa tài liệu: ' . $e->getMessage()]);
        }
        exit;
    }

    public function updateVersion()
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['account_id']) || !in_array($_SESSION['role'], ['teacher', 'student'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để cập nhật phiên bản tài liệu!']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ!']);
            exit;
        }

        $document_id = isset($_POST['document_id']) ? (int)$_POST['document_id'] : 0;
        $change_note = isset($_POST['change_note']) ? trim($_POST['change_note']) : 'Cập nhật phiên bản mới';

        if ($document_id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID tài liệu không hợp lệ!']);
            exit;
        }

        try {
            $stmt = $this->db->prepare("SELECT * FROM documents WHERE document_id = :document_id AND account_id = :account_id");
            $stmt->execute([':document_id' => $document_id, ':account_id' => $_SESSION['account_id']]);
            $document = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$document) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Tài liệu không tồn tại hoặc bạn không có quyền cập nhật!']);
                exit;
            }

            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Vui lòng chọn tệp để cập nhật phiên bản!']);
                exit;
            }

            $allowed_types = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'];
            $file_type = $_FILES['file']['type'];
            $file_ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

            if (!in_array($file_type, $allowed_types) || !in_array($file_ext, ['pdf', 'docx', 'pptx'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Định dạng tệp không hợp lệ! Chỉ hỗ trợ PDF, DOCX, PPTX.']);
                exit;
            }

            $this->db->beginTransaction();

            $versionStmt = $this->db->prepare("SELECT MAX(version_number) as max_version FROM document_versions WHERE document_id = :document_id");
            $versionStmt->execute([':document_id' => $document_id]);
            $current_version = $versionStmt->fetch(PDO::FETCH_ASSOC)['max_version'] ?? 0;
            $new_version = $current_version + 1;

            $upload_dir = __DIR__ . '/../Uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_name = $document_id . '_v' . $new_version . '.' . $file_ext;
            $file_path = $file_name;

            if (!move_uploaded_file($_FILES['file']['tmp_name'], $upload_dir . $file_name)) {
                $this->db->rollBack();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Lỗi khi tải lên tệp!']);
                exit;
            }

            $this->db->prepare("UPDATE documents SET file_path = :file_path WHERE document_id = :document_id")
                ->execute([':file_path' => $file_path, ':document_id' => $document_id]);

            $this->db->prepare("
                INSERT INTO document_versions (document_id, version_number, file_path, change_note, created_at)
                VALUES (:document_id, :version_number, :file_path, :change_note, NOW())
            ")->execute([
                ':document_id' => $document_id,
                ':version_number' => $new_version,
                ':file_path' => $file_path,
                ':change_note' => $change_note
            ]);

            $this->db->commit();
            echo json_encode(['success' => true, 'message' => 'Cập nhật phiên bản tài liệu thành công!', 'redirect' => '/study_sharing/document/manage']);
        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Lỗi server khi cập nhật phiên bản: ' . $e->getMessage()]);
        }
        exit;
    }

    public function statistics()
    {
        if (!isset($_SESSION['account_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để xem thống kê tài liệu']);
            exit;
        }

        try {
            $account_id = $_SESSION['account_id'];

            // 1. Thống kê tổng quan
            $overviewStats = $this->getOverviewStatistics($account_id);

            // 2. Thống kê theo danh mục
            $categoryStats = $this->getCategoryStatistics($account_id);

            // 3. Thống kê theo thời gian (7 ngày gần nhất, 30 ngày gần nhất)
            $timeStats = $this->getTimeStatistics($account_id);

            // 4. Thống kê tài liệu phổ biến
            $popularDocs = $this->getPopularDocuments($account_id);

            // Chuẩn bị dữ liệu cho view
            $title = 'Thống kê tài liệu';
            $layout = 'layout.php';
            ob_start();
            require __DIR__ . '/../views/document/statistics.php';
            $content = ob_get_clean();
            $pdo = $this->db;
            require __DIR__ . '/../views/layouts/' . $layout;
        } catch (\PDOException $e) {
            error_log("Statistics error: " . $e->getMessage());
            $_SESSION['message'] = 'Lỗi server khi tải thống kê: ' . $e->getMessage();
            $_SESSION['message_type'] = 'danger';
            header('Location: /study_sharing');
            exit;
        }
    }

    private function getOverviewStatistics($account_id)
    {
        $stats = [];

        // Tổng số tài liệu
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM documents WHERE account_id = :account_id");
        $stmt->bindValue(':account_id', $account_id, PDO::PARAM_INT);
        $stmt->execute();
        $stats['total_documents'] = $stmt->fetchColumn();

        // Tổng lượt tải
        $stmt = $this->db->prepare("
        SELECT COUNT(*) 
        FROM downloads dl
        JOIN documents d ON dl.document_id = d.document_id
        WHERE d.account_id = :account_id
    ");
        $stmt->bindValue(':account_id', $account_id, PDO::PARAM_INT);
        $stmt->execute();
        $stats['total_downloads'] = $stmt->fetchColumn();

        // Tổng bình luận
        $stmt = $this->db->prepare("
        SELECT COUNT(*) 
        FROM comments cm
        JOIN documents d ON cm.document_id = d.document_id
        WHERE d.account_id = :account_id
    ");
        $stmt->bindValue(':account_id', $account_id, PDO::PARAM_INT);
        $stmt->execute();
        $stats['total_comments'] = $stmt->fetchColumn();

        // Đánh giá trung bình
        $stmt = $this->db->prepare("
        SELECT AVG(r.rating_value) 
        FROM ratings r
        JOIN documents d ON r.document_id = d.document_id
        WHERE d.account_id = :account_id
    ");
        $stmt->bindValue(':account_id', $account_id, PDO::PARAM_INT);
        $stmt->execute();
        $stats['avg_rating'] = round($stmt->fetchColumn(), 1);

        // Số lượng tài liệu theo loại file
        $stmt = $this->db->prepare("
        SELECT 
            SUM(CASE WHEN file_path LIKE '%.pdf' THEN 1 ELSE 0 END) as pdf_count,
            SUM(CASE WHEN file_path LIKE '%.docx' THEN 1 ELSE 0 END) as docx_count,
            SUM(CASE WHEN file_path LIKE '%.pptx' THEN 1 ELSE 0 END) as pptx_count
        FROM documents 
        WHERE account_id = :account_id
    ");
        $stmt->bindValue(':account_id', $account_id, PDO::PARAM_INT);
        $stmt->execute();
        $fileTypes = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['file_types'] = $fileTypes;

        return $stats;
    }

    private function getCategoryStatistics($account_id)
    {
        $stats = [];

        // Thống kê số lượng tài liệu theo danh mục
        $stmt = $this->db->prepare("
        SELECT c.category_name, COUNT(d.document_id) as document_count
        FROM categories c
        LEFT JOIN documents d ON c.category_id = d.category_id AND d.account_id = :account_id
        GROUP BY c.category_id, c.category_name
    ");
        $stmt->bindValue(':account_id', $account_id, PDO::PARAM_INT);
        $stmt->execute();
        $stats['document_counts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Thống kê số lượt tải xuống theo danh mục
        $stmt = $this->db->prepare("
        SELECT c.category_name, COUNT(dl.download_id) as download_count
        FROM categories c
        LEFT JOIN documents d ON c.category_id = d.category_id AND d.account_id = :account_id
        LEFT JOIN downloads dl ON d.document_id = dl.document_id
        GROUP BY c.category_id, c.category_name
    ");
        $stmt->bindValue(':account_id', $account_id, PDO::PARAM_INT);
        $stmt->execute();
        $stats['download_counts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Thống kê số bình luận theo danh mục
        $stmt = $this->db->prepare("
        SELECT c.category_name, COUNT(cm.comment_id) as comment_count
        FROM categories c
        LEFT JOIN documents d ON c.category_id = d.category_id AND d.account_id = :account_id
        LEFT JOIN comments cm ON d.document_id = cm.document_id
        GROUP BY c.category_id, c.category_name
    ");
        $stmt->bindValue(':account_id', $account_id, PDO::PARAM_INT);
        $stmt->execute();
        $stats['comment_counts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Thống kê đánh giá trung bình theo danh mục
        $stmt = $this->db->prepare("
        SELECT c.category_name, AVG(r.rating_value) as avg_rating
        FROM categories c
        LEFT JOIN documents d ON c.category_id = d.category_id AND d.account_id = :account_id
        LEFT JOIN ratings r ON d.document_id = r.document_id
        GROUP BY c.category_id, c.category_name
    ");
        $stmt->bindValue(':account_id', $account_id, PDO::PARAM_INT);
        $stmt->execute();
        $stats['rating_avgs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $stats;
    }

    private function getTimeStatistics($account_id)
    {
        $stats = [];
        $now = new DateTime();

        // Thống kê 7 ngày gần nhất
        $sevenDaysAgo = (new DateTime())->sub(new DateInterval('P7D'))->format('Y-m-d');

        // Tài liệu mới upload
        $stmt = $this->db->prepare("
        SELECT COUNT(*) 
        FROM documents 
        WHERE account_id = :account_id AND upload_date >= :date
    ");
        $stmt->bindValue(':account_id', $account_id, PDO::PARAM_INT);
        $stmt->bindValue(':date', $sevenDaysAgo);
        $stmt->execute();
        $stats['recent_uploads'] = $stmt->fetchColumn();

        // Lượt tải gần đây
        $stmt = $this->db->prepare("
        SELECT COUNT(*) 
        FROM downloads dl
        JOIN documents d ON dl.document_id = d.document_id
        WHERE d.account_id = :account_id AND dl.download_date >= :date
    ");
        $stmt->bindValue(':account_id', $account_id, PDO::PARAM_INT);
        $stmt->bindValue(':date', $sevenDaysAgo);
        $stmt->execute();
        $stats['recent_downloads'] = $stmt->fetchColumn();

        // Bình luận gần đây
        $stmt = $this->db->prepare("
        SELECT COUNT(*) 
        FROM comments cm
        JOIN documents d ON cm.document_id = d.document_id
        WHERE d.account_id = :account_id AND cm.comment_date >= :date
    ");
        $stmt->bindValue(':account_id', $account_id, PDO::PARAM_INT);
        $stmt->bindValue(':date', $sevenDaysAgo);
        $stmt->execute();
        $stats['recent_comments'] = $stmt->fetchColumn();

        // Thống kê theo tháng (30 ngày)
        $monthlyStats = [];
        $monthNames = [
            'Tháng 1',
            'Tháng 2',
            'Tháng 3',
            'Tháng 4',
            'Tháng 5',
            'Tháng 6',
            'Tháng 7',
            'Tháng 8',
            'Tháng 9',
            'Tháng 10',
            'Tháng 11',
            'Tháng 12'
        ];
        for ($i = 0; $i < 12; $i++) {
            $startDate = (new DateTime())->sub(new DateInterval('P' . (11 - $i) . 'M'))->format('Y-m-01');
            $endDate = (new DateTime())->sub(new DateInterval('P' . (11 - $i) . 'M'))->format('Y-m-t');

            $monthIndex = (new DateTime($startDate))->format('n') - 1; // Lấy số tháng (1-12) và điều chỉnh về 0-11
            $year = (new DateTime($startDate))->format('Y');
            $monthName = $monthNames[$monthIndex] . ' ' . $year;

            // Tài liệu upload
            $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM documents 
            WHERE account_id = :account_id 
            AND upload_date BETWEEN :start_date AND :end_date
        ");
            $stmt->bindValue(':account_id', $account_id, PDO::PARAM_INT);
            $stmt->bindValue(':start_date', $startDate);
            $stmt->bindValue(':end_date', $endDate);
            $stmt->execute();
            $uploads = $stmt->fetchColumn();

            // Lượt tải
            $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM downloads dl
            JOIN documents d ON dl.document_id = d.document_id
            WHERE d.account_id = :account_id 
            AND dl.download_date BETWEEN :start_date AND :end_date
        ");
            $stmt->bindValue(':account_id', $account_id, PDO::PARAM_INT);
            $stmt->bindValue(':start_date', $startDate);
            $stmt->bindValue(':end_date', $endDate);
            $stmt->execute();
            $downloads = $stmt->fetchColumn();

            $monthlyStats[] = [
                'month' => $monthName,
                'uploads' => $uploads,
                'downloads' => $downloads
            ];
        }

        $stats['monthly_stats'] = $monthlyStats;

        return $stats;
    }

    private function getPopularDocuments($account_id)
    {
        $popularDocs = [];

        // Top 5 tài liệu có lượt tải nhiều nhất
        $stmt = $this->db->prepare("
        SELECT d.document_id, d.title, COUNT(dl.download_id) as download_count
        FROM documents d
        LEFT JOIN downloads dl ON d.document_id = dl.document_id
        WHERE d.account_id = :account_id
        GROUP BY d.document_id, d.title
        ORDER BY download_count DESC
        LIMIT 5
    ");
        $stmt->bindValue(':account_id', $account_id, PDO::PARAM_INT);
        $stmt->execute();
        $popularDocs['by_downloads'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Top 5 tài liệu có nhiều bình luận nhất
        $stmt = $this->db->prepare("
        SELECT d.document_id, d.title, COUNT(cm.comment_id) as comment_count
        FROM documents d
        LEFT JOIN comments cm ON d.document_id = cm.document_id
        WHERE d.account_id = :account_id
        GROUP BY d.document_id, d.title
        ORDER BY comment_count DESC
        LIMIT 5
    ");
        $stmt->bindValue(':account_id', $account_id, PDO::PARAM_INT);
        $stmt->execute();
        $popularDocs['by_comments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Top 5 tài liệu có đánh giá cao nhất
        $stmt = $this->db->prepare("
        SELECT d.document_id, d.title, AVG(r.rating_value) as avg_rating
        FROM documents d
        LEFT JOIN ratings r ON d.document_id = r.document_id
        WHERE d.account_id = :account_id
        GROUP BY d.document_id, d.title
        HAVING avg_rating IS NOT NULL
        ORDER BY avg_rating DESC
        LIMIT 5
    ");
        $stmt->bindValue(':account_id', $account_id, PDO::PARAM_INT);
        $stmt->execute();
        $popularDocs['by_ratings'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $popularDocs;
    }
}
