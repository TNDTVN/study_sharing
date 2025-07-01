<?php

namespace App;

use PDOException;

require 'vendor/autoload.php';

class UserController
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

        if (!$user) {
            error_log("User not found for account_id: " . $account_id);
            $_SESSION['message'] = 'Không tìm thấy thông tin người dùng!';
            $_SESSION['message_type'] = 'danger';
            header('Location: /study_sharing');
            exit;
        }

        $title = 'Chỉnh sửa hồ sơ';
        $pdo = $this->pdo; // Truyền $pdo vào scope của layout
        ob_start();
        require __DIR__ . '/../views/users/profile.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layouts/layout.php';
        exit;
    }

    public function updateProfile()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['account_id'])) {
            $account_id = $_SESSION['account_id'];
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
                    $_SESSION['message'] = 'Định dạng file không được hỗ trợ! Chỉ chấp nhận JPEG, PNG, GIF.';
                    $_SESSION['message_type'] = 'danger';
                    header('Location: /study_sharing/user/profile');
                    exit;
                }

                if ($fileSize > $maxFileSize) {
                    $_SESSION['message'] = 'Kích thước file quá lớn! Tối đa 5MB.';
                    $_SESSION['message_type'] = 'danger';
                    header('Location: /study_sharing/user/profile');
                    exit;
                }

                if (move_uploaded_file($fileTmp, $uploadPath)) {
                    $avatar = $newFileName;
                } else {
                    $_SESSION['message'] = 'Lỗi khi tải lên avatar!';
                    $_SESSION['message_type'] = 'danger';
                    header('Location: /study_sharing/user/profile');
                    exit;
                }
            }

            // Kiểm tra dữ liệu đầu vào
            if (empty($email) || empty($full_name)) {
                $_SESSION['message'] = 'Email và họ tên là bắt buộc!';
                $_SESSION['message_type'] = 'danger';
                header('Location: /study_sharing/user/profile');
                exit;
            }

            try {
                $this->pdo->beginTransaction();

                // Kiểm tra email đã tồn tại
                $existingAccount = $this->accountModel->getAccountByUsernameOrEmail($email);
                if ($existingAccount && $existingAccount['account_id'] != $account_id && $existingAccount['email'] == $email) {
                    $this->pdo->rollBack();
                    $_SESSION['message'] = 'Email đã tồn tại!';
                    $_SESSION['message_type'] = 'danger';
                    header('Location: /study_sharing/user/profile');
                    exit;
                }

                // Cập nhật thông tin (không cập nhật username)
                $this->accountModel->updateAccount($account_id, null, $email, $password);
                $this->userModel->updateUser($account_id, $full_name, $avatar, $date_of_birth, $phone_number, $address);

                $this->pdo->commit();
                $_SESSION['message'] = 'Cập nhật hồ sơ thành công!';
                $_SESSION['message_type'] = 'success';
                header('Location: /study_sharing/user/profile');
            } catch (PDOException $e) {
                $this->pdo->rollBack();
                error_log("Update profile error: " . $e->getMessage());
                $_SESSION['message'] = 'Lỗi cập nhật: ' . $e->getMessage();
                $_SESSION['message_type'] = 'danger';
                header('Location: /study_sharing/user/profile');
            }
            exit;
        }

        // Nếu không phải POST, chuyển hướng về trang profile
        header('Location: /study_sharing/user/profile');
        exit;
    }
}
