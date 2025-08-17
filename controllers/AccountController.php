<?php

namespace App;

use PDO;
use PDOException;

class AccountController
{
    private $pdo;
    private $itemsPerPage = 5;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function manage()
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
        $offset = ($page - 1) * $this->itemsPerPage;
        $users = [];
        $totalPages = 1;

        try {
            if (!$this->pdo) {
                throw new PDOException("Kết nối cơ sở dữ liệu không hợp lệ");
            }

            $query = "
                SELECT DISTINCT a.account_id, a.username, a.email, a.role, a.status,
                               u.full_name, u.date_of_birth, u.phone_number, u.address, u.avatar
                FROM accounts a
                LEFT JOIN users u ON a.account_id = u.account_id
                WHERE 1=1
            ";
            $params = [];

            if (!empty($keyword)) {
                $query .= " AND (
                    a.username LIKE :keyword1 OR
                    a.email LIKE :keyword2 OR
                    COALESCE(u.full_name, '') LIKE :keyword3
                )";
                $params[':keyword1'] = '%' . $keyword . '%';
                $params[':keyword2'] = '%' . $keyword . '%';
                $params[':keyword3'] = '%' . $keyword . '%';
            }

            $query .= " ORDER BY a.account_id ASC LIMIT :offset, :itemsPerPage";

            $stmt = $this->pdo->prepare($query);

            if (!empty($keyword)) {
                $stmt->bindValue(':keyword1', $params[':keyword1'], PDO::PARAM_STR);
                $stmt->bindValue(':keyword2', $params[':keyword2'], PDO::PARAM_STR);
                $stmt->bindValue(':keyword3', $params[':keyword3'], PDO::PARAM_STR);
            }

            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':itemsPerPage', $this->itemsPerPage, PDO::PARAM_INT);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $countQuery = "
                SELECT COUNT(DISTINCT a.account_id) as total
                FROM accounts a
                LEFT JOIN users u ON a.account_id = u.account_id
                WHERE 1=1
            ";

            if (!empty($keyword)) {
                $countQuery .= " AND (
                    a.username LIKE :keyword1 OR
                    a.email LIKE :keyword2 OR
                    COALESCE(u.full_name, '') LIKE :keyword3
                )";
            }

            $countStmt = $this->pdo->prepare($countQuery);

            if (!empty($keyword)) {
                $countStmt->bindValue(':keyword1', $params[':keyword1'], PDO::PARAM_STR);
                $countStmt->bindValue(':keyword2', $params[':keyword2'], PDO::PARAM_STR);
                $countStmt->bindValue(':keyword3', $params[':keyword3'], PDO::PARAM_STR);
            }

            $countStmt->execute();
            $totalUsers = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            $totalPages = ceil($totalUsers / $this->itemsPerPage);

            if (empty($users) && !empty($keyword)) {
                $_SESSION['message'] = 'Không tìm thấy người dùng nào khớp với từ khóa: ' . htmlspecialchars($keyword);
                $_SESSION['message_type'] = 'warning';
            }
        } catch (PDOException $e) {
            error_log("Manage users error: " . $e->getMessage());
            $_SESSION['message'] = 'Lỗi khi truy vấn dữ liệu: ' . $e->getMessage();
            $_SESSION['message_type'] = 'danger';
        }

        $keyword = htmlspecialchars($keyword);
        $title = 'Quản lý người dùng';
        $pdo = $this->pdo;

        ob_start();
        require __DIR__ . '/../views/Account/manage.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layouts/admin_layout.php';
        exit;
    }

    public function addUser()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            if (!isset($_SESSION['account_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
                $_SESSION['message'] = 'Bạn không có quyền thêm người dùng!';
                $_SESSION['message_type'] = 'danger';
                header('Location: /study_sharing/Account/manage');
                exit;
            }

            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $full_name = trim($_POST['full_name'] ?? '');
            $role = $_POST['role'] ?? 'student';
            $phone_number = trim($_POST['phone_number'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $date_of_birth = $_POST['date_of_birth'] ?? null;
            $avatar = 'profile.png';

            if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
                $_SESSION['message'] = 'Vui lòng điền đầy đủ thông tin bắt buộc!';
                $_SESSION['message_type'] = 'danger';
                header('Location: /study_sharing/Account/manage');
                exit;
            }

            // Handle avatar upload
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../assets/images/';
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $maxFileSize = 5 * 1024 * 1024; // 5MB

                $fileType = $_FILES['avatar']['type'];
                $fileSize = $_FILES['avatar']['size'];
                $fileTmp = $_FILES['avatar']['tmp_name'];
                $fileExt = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                $newFileName = 'avatar_' . time() . '_' . rand(1000, 9999) . '.' . $fileExt;
                $uploadPath = $uploadDir . $newFileName;

                if (!in_array($fileType, $allowedTypes)) {
                    $_SESSION['message'] = 'Định dạng file không được hỗ trợ! Chỉ chấp nhận JPEG, PNG, GIF.';
                    $_SESSION['message_type'] = 'danger';
                    header('Location: /study_sharing/Account/manage');
                    exit;
                }

                if ($fileSize > $maxFileSize) {
                    $_SESSION['message'] = 'Kích thước file quá lớn! Tối đa 5MB.';
                    $_SESSION['message_type'] = 'danger';
                    header('Location: /study_sharing/Account/manage');
                    exit;
                }

                if (!move_uploaded_file($fileTmp, $uploadPath)) {
                    $_SESSION['message'] = 'Lỗi khi tải lên avatar!';
                    $_SESSION['message_type'] = 'danger';
                    header('Location: /study_sharing/Account/manage');
                    exit;
                }

                $avatar = $newFileName;
            }

            try {
                $query = "SELECT * FROM accounts WHERE username = :username OR email = :email";
                $stmt = $this->pdo->prepare($query);
                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->execute();

                if ($stmt->fetch()) {
                    $_SESSION['message'] = 'Username hoặc email đã tồn tại!';
                    $_SESSION['message_type'] = 'danger';
                    header('Location: /study_sharing/Account/manage');
                    exit;
                }

                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                $this->pdo->beginTransaction();
                $insertAccountQuery = "
                    INSERT INTO accounts (username, email, password, role, status)
                    VALUES (:username, :email, :password, :role, 'active')
                ";
                $insertStmt = $this->pdo->prepare($insertAccountQuery);
                $insertStmt->bindParam(':username', $username, PDO::PARAM_STR);
                $insertStmt->bindParam(':email', $email, PDO::PARAM_STR);
                $insertStmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
                $insertStmt->bindParam(':role', $role, PDO::PARAM_STR);
                $insertStmt->execute();

                $account_id = $this->pdo->lastInsertId();

                $insertUserQuery = "
                    INSERT INTO users (account_id, full_name, phone_number, address, date_of_birth, avatar)
                    VALUES (:account_id, :full_name, :phone_number, :address, :date_of_birth, :avatar)
                ";
                $insertUserStmt = $this->pdo->prepare($insertUserQuery);
                $insertUserStmt->bindParam(':account_id', $account_id, PDO::PARAM_INT);
                $insertUserStmt->bindParam(':full_name', $full_name, PDO::PARAM_STR);
                $insertUserStmt->bindParam(':phone_number', $phone_number, PDO::PARAM_STR);
                $insertUserStmt->bindParam(':address', $address, PDO::PARAM_STR);
                $insertUserStmt->bindParam(':date_of_birth', $date_of_birth, $date_of_birth ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $insertUserStmt->bindParam(':avatar', $avatar, PDO::PARAM_STR);
                $insertUserStmt->execute();

                $this->pdo->commit();

                $_SESSION['message'] = 'Thêm người dùng thành công!';
                $_SESSION['message_type'] = 'success';
                header('Location: /study_sharing/Account/manage');
            } catch (PDOException $e) {
                $this->pdo->rollBack();
                error_log("Add user error: " . $e->getMessage());
                $_SESSION['message'] = 'Lỗi server: ' . $e->getMessage();
                $_SESSION['message_type'] = 'danger';
                header('Location: /study_sharing/Account/manage');
            }
            exit;
        }
    }

    public function updateUser()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            if (!isset($_SESSION['account_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
                $_SESSION['message'] = 'Bạn không có quyền cập nhật người dùng!';
                $_SESSION['message_type'] = 'danger';
                header('Location: /study_sharing/Account/manage');
                exit;
            }

            $account_id = $_POST['account_id'] ?? '';
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $full_name = trim($_POST['full_name'] ?? '');
            $role = $_POST['role'] ?? 'student';
            $phone_number = trim($_POST['phone_number'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $date_of_birth = $_POST['date_of_birth'] ?? null;
            $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : null;
            $avatar = $_POST['current_avatar'] ?? 'profile.png';

            if (empty($account_id) || empty($username) || empty($email) || empty($full_name)) {
                $_SESSION['message'] = 'Vui lòng điền đầy đủ thông tin bắt buộc!';
                $_SESSION['message_type'] = 'danger';
                header('Location: /study_sharing/Account/manage');
                exit;
            }

            // Handle avatar upload
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../assets/images/';
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $maxFileSize = 5 * 1024 * 1024; // 5MB

                $fileType = $_FILES['avatar']['type'];
                $fileSize = $_FILES['avatar']['size'];
                $fileTmp = $_FILES['avatar']['tmp_name'];
                $fileExt = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                $newFileName = 'avatar_' . $account_id . '_' . time() . '.' . $fileExt;
                $uploadPath = $uploadDir . $newFileName;

                if (!in_array($fileType, $allowedTypes)) {
                    $_SESSION['message'] = 'Định dạng file không được hỗ trợ! Chỉ chấp nhận JPEG, PNG, GIF.';
                    $_SESSION['message_type'] = 'danger';
                    header('Location: /study_sharing/Account/manage');
                    exit;
                }

                if ($fileSize > $maxFileSize) {
                    $_SESSION['message'] = 'Kích thước file quá lớn! Tối đa 5MB.';
                    $_SESSION['message_type'] = 'danger';
                    header('Location: /study_sharing/Account/manage');
                    exit;
                }

                if (!move_uploaded_file($fileTmp, $uploadPath)) {
                    $_SESSION['message'] = 'Lỗi khi tải lên avatar!';
                    $_SESSION['message_type'] = 'danger';
                    header('Location: /study_sharing/Account/manage');
                    exit;
                }

                $avatar = $newFileName;
            }

            try {
                $query = "SELECT * FROM accounts WHERE (username = :username OR email = :email) AND account_id != :account_id";
                $stmt = $this->pdo->prepare($query);
                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->bindParam(':account_id', $account_id, PDO::PARAM_INT);
                $stmt->execute();

                if ($stmt->fetch()) {
                    $_SESSION['message'] = 'Username hoặc email đã tồn tại!';
                    $_SESSION['message_type'] = 'danger';
                    header('Location: /study_sharing/Account/manage');
                    exit;
                }

                $this->pdo->beginTransaction();
                $updateAccountQuery = "
                    UPDATE accounts 
                    SET username = :username, email = :email, role = :role
                    " . ($password ? ", password = :password" : "") . "
                    WHERE account_id = :account_id
                ";
                $stmt = $this->pdo->prepare($updateAccountQuery);
                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->bindParam(':role', $role, PDO::PARAM_STR);
                $stmt->bindParam(':account_id', $account_id, PDO::PARAM_INT);
                if ($password) {
                    $stmt->bindParam(':password', $password, PDO::PARAM_STR);
                }
                $stmt->execute();

                $updateUserQuery = "
                    UPDATE users 
                    SET full_name = :full_name, phone_number = :phone_number, address = :address, date_of_birth = :date_of_birth, avatar = :avatar
                    WHERE account_id = :account_id
                ";
                $updateUserStmt = $this->pdo->prepare($updateUserQuery);
                $updateUserStmt->bindParam(':full_name', $full_name, PDO::PARAM_STR);
                $updateUserStmt->bindParam(':phone_number', $phone_number, PDO::PARAM_STR);
                $updateUserStmt->bindParam(':address', $address, PDO::PARAM_STR);
                $updateUserStmt->bindParam(':date_of_birth', $date_of_birth, $date_of_birth ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $updateUserStmt->bindParam(':avatar', $avatar, PDO::PARAM_STR);
                $updateUserStmt->bindParam(':account_id', $account_id, PDO::PARAM_INT);
                $updateUserStmt->execute();

                $this->pdo->commit();

                $_SESSION['message'] = 'Cập nhật người dùng thành công!';
                $_SESSION['message_type'] = 'success';
                header('Location: /study_sharing/Account/manage');
            } catch (PDOException $e) {
                $this->pdo->rollBack();
                error_log("Update user error: " . $e->getMessage());
                $_SESSION['message'] = 'Lỗi server: ' . $e->getMessage();
                $_SESSION['message_type'] = 'danger';
                header('Location: /study_sharing/Account/manage');
            }
            exit;
        }
    }

    public function lockUser()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            if (!isset($_SESSION['account_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền khóa tài khoản!']);
                exit;
            }

            $account_id = $_POST['account_id'] ?? '';
            $status = $_POST['status'] ?? 'banned';

            if (empty($account_id)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Vui lòng cung cấp ID tài khoản!']);
                exit;
            }

            try {
                if ($account_id == $_SESSION['account_id']) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Không thể khóa tài khoản của chính bạn!']);
                    exit;
                }

                $query = "UPDATE accounts SET status = :status WHERE account_id = :account_id";
                $stmt = $this->pdo->prepare($query);
                $stmt->bindParam(':status', $status, PDO::PARAM_STR);
                $stmt->bindParam(':account_id', $account_id, PDO::PARAM_INT);
                $stmt->execute();

                $affected = $stmt->rowCount();
                if ($affected > 0) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái tài khoản thành công!']);
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản để cập nhật!']);
                }
            } catch (PDOException $e) {
                error_log("Lock user error: " . $e->getMessage());
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
            }
            exit;
        }
    }

    public function viewUser($account_id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['account_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header('Location: /study_sharing');
            exit;
        }

        try {
            $query = "
                SELECT a.account_id, a.username, a.email, a.role, a.status, a.created_at, a.updated_at,
                       u.full_name, u.avatar, u.date_of_birth, u.phone_number, u.address
                FROM accounts a
                LEFT JOIN users u ON a.account_id = u.account_id
                WHERE a.account_id = :account_id
            ";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':account_id', $account_id, PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $_SESSION['message'] = 'Không tìm thấy người dùng!';
                $_SESSION['message_type'] = 'danger';
                header('Location: /study_sharing/Account/manage');
                exit;
            }

            $title = 'Thông tin người dùng';
            $pdo = $this->pdo;

            ob_start();
            require __DIR__ . '/../views/Account/view.php';
            $content = ob_get_clean();
            require __DIR__ . '/../views/layouts/admin_layout.php';
        } catch (PDOException $e) {
            error_log("View user error: " . $e->getMessage());
            $_SESSION['message'] = 'Lỗi server: ' . $e->getMessage();
            $_SESSION['message_type'] = 'danger';
            header('Location: /study_sharing/Account/manage');
        }
        exit;
    }

    public function countUsers()
    {
        try {
            $query = "SELECT COUNT(*) as total FROM accounts";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (PDOException $e) {
            error_log("Count users error: " . $e->getMessage());
            return 0;
        }
    }
}
