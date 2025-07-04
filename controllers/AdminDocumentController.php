<?php

namespace App;

use PDO;
use PDOException;

class AdminDocumentController
{
    private $pdo;
    private $itemsPerPage = 5;
    private $uploadDir = 'uploads/';

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
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

    public function admin_manage()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['account_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header('Location: /study_sharing');
            exit;
        }

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
                    WHERE 1=1";
            $params = [];

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

            $stmt = $this->pdo->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':itemsPerPage', $this->itemsPerPage, PDO::PARAM_INT);
            $stmt->execute();
            $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $categoryStmt = $this->pdo->prepare("SELECT * FROM categories ORDER BY category_name");
            $categoryStmt->execute();
            $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

            $courseStmt = $this->pdo->prepare("SELECT course_id, course_name FROM courses ORDER BY course_name");
            $courseStmt->execute();
            $courses = $courseStmt->fetchAll(PDO::FETCH_ASSOC);

            $countQuery = "SELECT COUNT(*) as total FROM documents WHERE 1=1";
            $countParams = [];

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

            $countStmt = $this->pdo->prepare($countQuery);
            foreach ($countParams as $key => $value) {
                $countStmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $countStmt->execute();
            $totalDocuments = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            $totalPages = ceil($totalDocuments / $this->itemsPerPage);

            foreach ($documents as &$document) {
                $tagStmt = $this->pdo->prepare("
                    SELECT t.tag_name 
                    FROM tags t
                    JOIN document_tags dt ON t.tag_id = dt.tag_id
                    WHERE dt.document_id = :document_id
                ");
                $tagStmt->bindValue(':document_id', $document['document_id'], PDO::PARAM_INT);
                $tagStmt->execute();
                $document['tags'] = $tagStmt->fetchAll(PDO::FETCH_COLUMN);
            }
            unset($document);
        } catch (PDOException $e) {
            error_log("Manage documents error: " . $e->getMessage());
            $_SESSION['message'] = 'Lỗi server khi tải tài liệu: ' . $e->getMessage();
            $_SESSION['message_type'] = 'danger';
            header('Location: /study_sharing');
            exit;
        }

        $title = 'Quản lý tài liệu';
        $pdo = $this->pdo;
        ob_start();
        require __DIR__ . '/../views/document/admin_manage.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layouts/admin_layout.php';
    }

    public function admin_add()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            if (!isset($_SESSION['account_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
                $_SESSION['message'] = 'Bạn không có quyền thêm tài liệu!';
                $_SESSION['message_type'] = 'danger';
                header('Location: /study_sharing/AdminDocument/admin_manage');
                exit;
            }

            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $category_id = (int)($_POST['category_id'] ?? 0);
            $course_id = !empty($_POST['course_id']) ? (int)$_POST['course_id'] : null;
            $visibility = in_array($_POST['visibility'] ?? '', ['public', 'private']) ? $_POST['visibility'] : 'public';
            $tags = !empty($_POST['tags']) ? explode(',', trim($_POST['tags'])) : [];

            if (empty($title) || empty($_FILES['file']['name'])) {
                $_SESSION['message'] = 'Tiêu đề và tệp tài liệu là bắt buộc!';
                $_SESSION['message_type'] = 'danger';
                header('Location: /study_sharing/AdminDocument/admin_manage');
                exit;
            }

            try {
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
                    $_SESSION['message'] = $uploadErrors[$_FILES['file']['error']] ?? 'Lỗi không xác định khi tải tệp!';
                    $_SESSION['message_type'] = 'danger';
                    header('Location: /study_sharing/AdminDocument/admin_manage');
                    exit;
                }

                // Xử lý tệp tải lên
                $file = $_FILES['file'];
                $allowed_types = ['pdf', 'docx', 'pptx'];
                $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($file_ext, $allowed_types)) {
                    $_SESSION['message'] = 'Chỉ chấp nhận các định dạng: ' . implode(', ', $allowed_types);
                    $_SESSION['message_type'] = 'danger';
                    header('Location: /study_sharing/AdminDocument/admin_manage');
                    exit;
                }

                if ($file['size'] > 10 * 1024 * 1024) {
                    $_SESSION['message'] = 'Kích thước tệp không được vượt quá 10MB!';
                    $_SESSION['message_type'] = 'danger';
                    header('Location: /study_sharing/AdminDocument/admin_manage');
                    exit;
                }

                $file_name = uniqid() . '.' . $file_ext;
                $file_path = $file_name; // Chỉ lưu tên file
                $absolute_file_path = __DIR__ . '/../' . $this->uploadDir . $file_name; // Đường dẫn tuyệt đối để lưu file

                if (!move_uploaded_file($file['tmp_name'], $absolute_file_path)) {
                    error_log("Failed to move uploaded file to: {$absolute_file_path}");
                    $_SESSION['message'] = 'Lỗi khi di chuyển tệp đến thư mục uploads!';
                    $_SESSION['message_type'] = 'danger';
                    header('Location: /study_sharing/AdminDocument/admin_manage');
                    exit;
                }

                // Thêm tài liệu
                $query = "INSERT INTO documents (title, description, file_path, account_id, category_id, course_id, visibility, upload_date) 
                VALUES (:title, :description, :file_path, :account_id, :category_id, :course_id, :visibility, NOW())";
                $stmt = $this->pdo->prepare($query);
                $stmt->bindValue(':title', $title, PDO::PARAM_STR);
                $stmt->bindValue(':description', $description, PDO::PARAM_STR);
                $stmt->bindValue(':file_path', $file_path, PDO::PARAM_STR); // Lưu tên file
                $stmt->bindValue(':account_id', $_SESSION['account_id'], PDO::PARAM_INT);
                $stmt->bindValue(':category_id', $category_id ?: null, $category_id ? PDO::PARAM_INT : PDO::PARAM_NULL);
                $stmt->bindValue(':course_id', $course_id, $course_id ? PDO::PARAM_INT : PDO::PARAM_NULL);
                $stmt->bindValue(':visibility', $visibility, PDO::PARAM_STR);
                $stmt->execute();

                $document_id = $this->pdo->lastInsertId();

                // Thêm thẻ
                foreach ($tags as $tag_name) {
                    $tag_name = trim($tag_name);
                    if (!empty($tag_name)) {
                        $tagStmt = $this->pdo->prepare("SELECT tag_id FROM tags WHERE tag_name = :tag_name");
                        $tagStmt->bindValue(':tag_name', $tag_name, PDO::PARAM_STR);
                        $tagStmt->execute();
                        $tag = $tagStmt->fetch(PDO::FETCH_ASSOC);

                        if (!$tag) {
                            $tagStmt = $this->pdo->prepare("INSERT INTO tags (tag_name) VALUES (:tag_name)");
                            $tagStmt->bindValue(':tag_name', $tag_name, PDO::PARAM_STR);
                            $tagStmt->execute();
                            $tag_id = $this->pdo->lastInsertId();
                        } else {
                            $tag_id = $tag['tag_id'];
                        }

                        $docTagStmt = $this->pdo->prepare("INSERT INTO document_tags (document_id, tag_id) VALUES (:document_id, :tag_id)");
                        $docTagStmt->bindValue(':document_id', $document_id, PDO::PARAM_INT);
                        $docTagStmt->bindValue(':tag_id', $tag_id, PDO::PARAM_INT);
                        $docTagStmt->execute();
                    }
                }

                $_SESSION['message'] = 'Thêm tài liệu thành công!';
                $_SESSION['message_type'] = 'success';
                header('Location: /study_sharing/AdminDocument/admin_manage');
            } catch (PDOException $e) {
                error_log("Add document error: " . $e->getMessage());
                $_SESSION['message'] = 'Lỗi server: ' . $e->getMessage();
                $_SESSION['message_type'] = 'danger';
                header('Location: /study_sharing/AdminDocument/admin_manage');
            }
            exit;
        }
    }

    public function admin_edit()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            if (!isset($_SESSION['account_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
                $_SESSION['message'] = 'Bạn không có quyền chỉnh sửa tài liệu!';
                $_SESSION['message_type'] = 'danger';
                header('Location: /study_sharing/AdminDocument/admin_manage');
                exit;
            }

            $document_id = (int)($_POST['document_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $category_id = (int)($_POST['category_id'] ?? 0);
            $course_id = !empty($_POST['course_id']) ? (int)$_POST['course_id'] : null;
            $visibility = in_array($_POST['visibility'] ?? '', ['public', 'private']) ? $_POST['visibility'] : 'public';
            $tags = !empty($_POST['tags']) ? explode(',', trim($_POST['tags'])) : [];

            if ($document_id <= 0 || empty($title)) {
                $_SESSION['message'] = 'ID tài liệu và tiêu đề là bắt buộc!';
                $_SESSION['message_type'] = 'danger';
                header('Location: /study_sharing/AdminDocument/admin_manage');
                exit;
            }

            try {
                $currentDocumentStmt = $this->pdo->prepare("SELECT file_path FROM documents WHERE document_id = :document_id");
                $currentDocumentStmt->bindValue(':document_id', $document_id, PDO::PARAM_INT);
                $currentDocumentStmt->execute();
                $currentDocument = $currentDocumentStmt->fetch(PDO::FETCH_ASSOC);

                if (!$currentDocument) {
                    $_SESSION['message'] = 'Tài liệu không tồn tại!';
                    $_SESSION['message_type'] = 'danger';
                    header('Location: /study_sharing/AdminDocument/admin_manage');
                    exit;
                }

                $file_path = $currentDocument['file_path'];
                if (!empty($_FILES['file']['name'])) {
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
                        $_SESSION['message'] = $uploadErrors[$_FILES['file']['error']] ?? 'Lỗi không xác định khi tải tệp!';
                        $_SESSION['message_type'] = 'danger';
                        header('Location: /study_sharing/AdminDocument/admin_manage');
                        exit;
                    }

                    $file = $_FILES['file'];
                    $allowed_types = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];
                    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if (!in_array($file_ext, $allowed_types)) {
                        $_SESSION['message'] = 'Chỉ chấp nhận các định dạng: ' . implode(', ', $allowed_types);
                        $_SESSION['message_type'] = 'danger';
                        header('Location: /study_sharing/AdminDocument/admin_manage');
                        exit;
                    }

                    if ($file['size'] > 10 * 1024 * 1024) {
                        $_SESSION['message'] = 'Kích thước tệp không được vượt quá 10MB!';
                        $_SESSION['message_type'] = 'danger';
                        header('Location: /study_sharing/AdminDocument/admin_manage');
                        exit;
                    }

                    $file_name = uniqid() . '.' . $file_ext;
                    $file_path = $file_name; // Chỉ lưu tên file
                    $absolute_file_path = __DIR__ . '/../' . $this->uploadDir . $file_name; // Đường dẫn tuyệt đối để lưu file

                    if (!move_uploaded_file($file['tmp_name'], $absolute_file_path)) {
                        error_log("Failed to move uploaded file to: {$absolute_file_path}");
                        $_SESSION['message'] = 'Lỗi khi di chuyển tệp đến thư mục uploads!';
                        $_SESSION['message_type'] = 'danger';
                        header('Location: /study_sharing/AdminDocument/admin_manage');
                        exit;
                    }

                    $versionStmt = $this->pdo->prepare("INSERT INTO document_versions (document_id, version_number, file_path, change_note) 
                                        VALUES (:document_id, (SELECT COALESCE(MAX(version_number), 0) + 1 FROM document_versions WHERE document_id = :document_id), :file_path, :change_note)");
                    $versionStmt->bindValue(':document_id', $document_id, PDO::PARAM_INT);
                    $versionStmt->bindValue(':file_path', $currentDocument['file_path'], PDO::PARAM_STR);
                    $versionStmt->bindValue(':change_note', 'Cập nhật tệp mới', PDO::PARAM_STR);
                    $versionStmt->execute();
                }

                $query = "UPDATE documents
            SET title = :title, description = :description, file_path = :file_path,
                category_id = :category_id, course_id = :course_id, visibility = :visibility
            WHERE document_id = :document_id";
                $stmt = $this->pdo->prepare($query);
                $stmt->bindValue(':title', $title, PDO::PARAM_STR);
                $stmt->bindValue(':description', $description, PDO::PARAM_STR);
                $stmt->bindValue(':file_path', $file_path, PDO::PARAM_STR); // Lưu tên file
                $stmt->bindValue(':category_id', $category_id ?: null, $category_id ? PDO::PARAM_INT : PDO::PARAM_NULL);
                $stmt->bindValue(':course_id', $course_id, $course_id ? PDO::PARAM_INT : PDO::PARAM_NULL);
                $stmt->bindValue(':visibility', $visibility, PDO::PARAM_STR);
                $stmt->bindValue(':document_id', $document_id, PDO::PARAM_INT);
                $stmt->execute();

                $deleteTagStmt = $this->pdo->prepare("DELETE FROM document_tags WHERE document_id = :document_id");
                $deleteTagStmt->bindValue(':document_id', $document_id, PDO::PARAM_INT);
                $deleteTagStmt->execute();

                foreach ($tags as $tag_name) {
                    $tag_name = trim($tag_name);
                    if (!empty($tag_name)) {
                        $tagStmt = $this->pdo->prepare("SELECT tag_id FROM tags WHERE tag_name = :tag_name");
                        $tagStmt->bindValue(':tag_name', $tag_name, PDO::PARAM_STR);
                        $tagStmt->execute();
                        $tag = $tagStmt->fetch(PDO::FETCH_ASSOC);

                        if (!$tag) {
                            $tagStmt = $this->pdo->prepare("INSERT INTO tags (tag_name) VALUES (:tag_name)");
                            $tagStmt->bindValue(':tag_name', $tag_name, PDO::PARAM_STR);
                            $tagStmt->execute();
                            $tag_id = $this->pdo->lastInsertId();
                        } else {
                            $tag_id = $tag['tag_id'];
                        }

                        $docTagStmt = $this->pdo->prepare("INSERT INTO document_tags (document_id, tag_id) VALUES (:document_id, :tag_id)");
                        $docTagStmt->bindValue(':document_id', $document_id, PDO::PARAM_INT);
                        $docTagStmt->bindValue(':tag_id', $tag_id, PDO::PARAM_INT);
                        $docTagStmt->execute();
                    }
                }

                $_SESSION['message'] = 'Cập nhật tài liệu thành công!';
                $_SESSION['message_type'] = 'success';
                header('Location: /study_sharing/AdminDocument/admin_manage');
            } catch (PDOException $e) {
                error_log("Edit document error: " . $e->getMessage());
                $_SESSION['message'] = 'Lỗi server: ' . $e->getMessage();
                $_SESSION['message_type'] = 'danger';
                header('Location: /study_sharing/AdminDocument/admin_manage');
            }
            exit;
        }
    }

    public function admin_delete()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            if (!isset($_SESSION['account_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa tài liệu!']);
                exit;
            }

            $document_id = (int)($_POST['document_id'] ?? 0);

            if ($document_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID tài liệu không hợp lệ!']);
                exit;
            }

            try {
                $counts = [
                    'comment_count' => 0,
                    'download_count' => 0,
                    'version_count' => 0,
                    'rating_count' => 0
                ];

                $checkStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE document_id = :document_id");
                $checkStmt->bindValue(':document_id', $document_id, PDO::PARAM_INT);
                $checkStmt->execute();
                $counts['comment_count'] = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];

                $checkStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM downloads WHERE document_id = :document_id");
                $checkStmt->bindValue(':document_id', $document_id, PDO::PARAM_INT);
                $checkStmt->execute();
                $counts['download_count'] = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];

                $checkStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM document_versions WHERE document_id = :document_id");
                $checkStmt->bindValue(':document_id', $document_id, PDO::PARAM_INT);
                $checkStmt->execute();
                $counts['version_count'] = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];

                $checkStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM ratings WHERE document_id = :document_id");
                $checkStmt->bindValue(':document_id', $document_id, PDO::PARAM_INT);
                $checkStmt->execute();
                $counts['rating_count'] = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];

                if (
                    $counts['comment_count'] > 0 || $counts['download_count'] > 0 ||
                    $counts['version_count'] > 0 || $counts['rating_count'] > 0
                ) {
                    echo json_encode(['success' => false, 'message' => 'Không thể xóa tài liệu vì có dữ liệu liên quan (bình luận, lượt tải, phiên bản, hoặc đánh giá)!']);
                    exit;
                }

                $currentDocumentStmt = $this->pdo->prepare("SELECT file_path FROM documents WHERE document_id = :document_id");
                $currentDocumentStmt->bindValue(':document_id', $document_id, PDO::PARAM_INT);
                $currentDocumentStmt->execute();
                $currentDocument = $currentDocumentStmt->fetch(PDO::FETCH_ASSOC);

                if ($currentDocument) {
                    $file_path = __DIR__ . '/../' . $this->uploadDir . $currentDocument['file_path'];
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }

                $deleteStmt = $this->pdo->prepare("DELETE FROM documents WHERE document_id = :document_id");
                $deleteStmt->bindValue(':document_id', $document_id, PDO::PARAM_INT);
                $deleteStmt->execute();

                $affected = $deleteStmt->rowCount();
                if ($affected > 0) {
                    echo json_encode(['success' => true, 'message' => 'Xóa tài liệu thành công!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Tài liệu không tồn tại!']);
                }
            } catch (PDOException $e) {
                error_log("Delete document error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
            }
            exit;
        }
    }
}
