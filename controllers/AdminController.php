<?php

namespace App;

use PDOException;

require 'vendor/autoload.php';

class AdminController
{
    private $pdo;
    private $userModel;
    private $accountModel;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->userModel = new User($pdo);
        $this->accountModel = new Account($pdo);
        date_default_timezone_set('Asia/Ho_Chi_Minh');
    }

    public function profile()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['account_id'])) {
            error_log("Access denied: No account_id in session");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để truy cập hồ sơ!', 'redirect' => '/study_sharing']);
            exit;
        }

        $account_id = $_SESSION['account_id'];
        $user = $this->userModel->getUserById($account_id);

        if (!$user || $user['role'] !== 'admin') {
            error_log("Access denied: Not an admin or user not found for account_id: " . $account_id);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền truy cập trang này!', 'redirect' => '/study_sharing']);
            exit;
        }

        $title = 'Hồ sơ quản trị viên';
        $pdo = $this->pdo;
        ob_start();
        require __DIR__ . '/../views/admin/profile.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layouts/admin_layout.php';
        exit;
    }

    public function updateProfile()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['account_id'])) {
            error_log("Access denied: Invalid request or no account_id in session");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để cập nhật hồ sơ!', 'redirect' => '/study_sharing']);
            exit;
        }

        $account_id = $_SESSION['account_id'];
        $user = $this->userModel->getUserById($account_id);

        if (!$user || $user['role'] !== 'admin') {
            error_log("Access denied: Not an admin for account_id: " . $account_id);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện hành động này!', 'redirect' => '/study_sharing']);
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
        $full_name = trim($_POST['full_name'] ?? '');
        $date_of_birth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
        $phone_number = !empty($_POST['phone_number']) ? $_POST['phone_number'] : null;
        $address = !empty($_POST['address']) ? $_POST['address'] : null;
        $avatar = $_POST['current_avatar'] ?? 'profile.png';

        // Xử lý tải lên avatar
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
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Định dạng file không được hỗ trợ! Chỉ chấp nhận JPEG, PNG, GIF.', 'redirect' => '/study_sharing/admin/profile']);
                exit;
            }

            if ($fileSize > $maxFileSize) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Kích thước file quá lớn! Tối đa 5MB.', 'redirect' => '/study_sharing/admin/profile']);
                exit;
            }

            if (move_uploaded_file($fileTmp, $uploadPath)) {
                $avatar = $newFileName;
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Lỗi khi tải lên avatar!', 'redirect' => '/study_sharing/admin/profile']);
                exit;
            }
        }

        // Kiểm tra dữ liệu đầu vào
        if (empty($email) || empty($full_name)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Email và họ tên là bắt buộc!', 'redirect' => '/study_sharing/admin/profile']);
            exit;
        }

        try {
            $this->pdo->beginTransaction();

            // Kiểm tra email đã tồn tại
            $existingAccount = $this->accountModel->getAccountByUsernameOrEmail($email);
            if ($existingAccount && $existingAccount['account_id'] != $account_id && $existingAccount['email'] == $email) {
                $this->pdo->rollBack();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Email đã tồn tại!', 'redirect' => '/study_sharing/admin/profile']);
                exit;
            }

            // Cập nhật thông tin
            $this->accountModel->updateAccount($account_id, null, $email, $password);
            $this->userModel->updateUser($account_id, $full_name, $avatar, $date_of_birth, $phone_number, $address);

            $this->pdo->commit();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Cập nhật hồ sơ thành công!', 'redirect' => '/study_sharing/admin/profile']);
            exit;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Update profile error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật: ' . $e->getMessage(), 'redirect' => '/study_sharing/admin/profile']);
            exit;
        }
    }
}
