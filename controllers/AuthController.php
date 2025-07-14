<?php

namespace App;

use PDO;
use PDOException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

class AuthController
{
    private $pdo;
    private $userModel;
    private $accountModel;
    private $passwordResetTokenModel;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->userModel = new User($pdo);
        $this->accountModel = new Account($pdo);
        $this->passwordResetTokenModel = new PasswordResetToken($pdo);
        date_default_timezone_set('Asia/Ho_Chi_Minh');
    }

    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim(htmlspecialchars($_POST['username'] ?? ''));
            $email = trim(htmlspecialchars($_POST['email'] ?? ''));
            $raw_password = $_POST['password'] ?? '';
            $full_name = trim(htmlspecialchars($_POST['full_name'] ?? ''));
            $role = trim(htmlspecialchars($_POST['role'] ?? ''));
            $date_of_birth = trim(htmlspecialchars($_POST['date_of_birth'] ?? '')); // Sửa lỗi
            $phone_number = trim(htmlspecialchars($_POST['phone_number'] ?? ''));   // Sửa lỗi
            $address = trim(htmlspecialchars($_POST['address'] ?? ''));             // Sửa lỗi
            $avatar = 'profile.png';

            // Kiểm tra vai trò
            if (!in_array($role, ['student', 'teacher'])) {
                $this->sendJsonResponse(false, 'Vai trò không hợp lệ!');
            }

            // Kiểm tra tên đăng nhập
            if (!preg_match('/^[a-zA-Z0-9_-]{6,20}$/', $username)) {
                $this->sendJsonResponse(false, 'Tên đăng nhập không hợp lệ! Chỉ cho phép chữ cái, số, dấu gạch dưới hoặc gạch ngang, từ 6-20 ký tự.');
            }

            // Kiểm tra email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->sendJsonResponse(false, 'Email không hợp lệ!');
            }

            // Kiểm tra mật khẩu
            if (strlen($raw_password) < 6) {
                $this->sendJsonResponse(false, 'Mật khẩu phải dài ít nhất 6 ký tự!');
            }
            $password = password_hash($raw_password, PASSWORD_BCRYPT);

            // Kiểm tra ngày sinh
            if ($date_of_birth && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_of_birth)) {
                $this->sendJsonResponse(false, 'Ngày sinh không đúng định dạng (YYYY-MM-DD)!');
            }

            // Kiểm tra số điện thoại
            if ($phone_number && !preg_match('/^\+?\d{10,15}$/', $phone_number)) {
                $this->sendJsonResponse(false, 'Số điện thoại không hợp lệ!');
            }

            // Kiểm tra trùng tên đăng nhập hoặc email
            $existingAccount = $this->accountModel->getAccountByUsernameOrEmail($username, $email);
            if ($existingAccount) {
                if ($existingAccount['username'] === $username) {
                    $this->sendJsonResponse(false, 'Tên đăng nhập đã được sử dụng!');
                }
                if ($existingAccount['email'] === $email) {
                    $this->sendJsonResponse(false, 'Email đã được sử dụng!');
                }
            }

            // Kiểm tra các trường bắt buộc
            if ($username && $email && $raw_password && $full_name && $role) {
                try {
                    $this->pdo->beginTransaction();

                    $account_id = $this->accountModel->createAccount($username, $email, $password, $role, 'active');
                    $this->userModel->createUser($account_id, $full_name, $avatar, $date_of_birth, $phone_number, $address);

                    $this->pdo->commit();
                    $this->sendJsonResponse(true, 'Đăng ký thành công!');
                } catch (PDOException $e) {
                    $this->pdo->rollBack();
                    error_log("Register error: " . $e->getMessage());
                    $this->sendJsonResponse(false, 'Lỗi đăng ký: ' . $e->getMessage());
                }
            } else {
                $this->sendJsonResponse(false, 'Vui lòng điền đầy đủ thông tin bắt buộc!');
            }
        }
    }

    private function sendJsonResponse($success, $message)
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    }

    // public function login()
    // {
    //     if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    //         header('Content-Type: application/json');
    //         echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ!']);
    //         exit;
    //     }

    //     $username = $_POST['username'] ?? '';
    //     $password = $_POST['password'] ?? '';

    //     if (empty($username) || empty($password)) {
    //         header('Content-Type: application/json');
    //         echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin!']);
    //         exit;
    //     }

    //     try {
    //         $account = $this->accountModel->getAccountByUsernameOrEmail($username);
    //         if ($account && password_verify($password, $account['password'])) {
    //             if (session_status() === PHP_SESSION_NONE) {
    //                 session_start();
    //             }
    //             $_SESSION['account_id'] = $account['account_id'];
    //             $_SESSION['role'] = $account['role'];
    //             header('Content-Type: application/json');
    //             echo json_encode([
    //                 'success' => true,
    //                 'message' => 'Đăng nhập thành công!',
    //                 'role' => $account['role']
    //             ]);
    //         } else {
    //             header('Content-Type: application/json');
    //             echo json_encode(['success' => false, 'message' => 'Tên đăng nhập hoặc mật khẩu không đúng!']);
    //         }
    //     } catch (PDOException $e) {
    //         error_log("Login error: " . $e->getMessage());
    //         header('Content-Type: application/json');
    //         echo json_encode(['success' => false, 'message' => 'Lỗi server, vui lòng thử lại sau!']);
    //     }
    //     exit;
    // }
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ!']);
            exit;
        }

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin!']);
            exit;
        }

        try {
            $account = $this->accountModel->getAccountByUsernameOrEmail($username);
            if ($account && password_verify($password, $account['password'])) {
                // Kiểm tra trạng thái tài khoản
                if ($account['status'] !== 'active') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Tài khoản của bạn đã bị khóa hoặc không hoạt động!']);
                    exit;
                }

                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['account_id'] = $account['account_id'];
                $_SESSION['role'] = $account['role'];
                $_SESSION['username'] = $account['username'];


                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Đăng nhập thành công!',
                    'role' => $account['role']
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Tên đăng nhập hoặc mật khẩu không đúng!']);
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Lỗi server, vui lòng thử lại sau!']);
        }
        exit;
    }

    public function forgotPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ!']);
            exit;
        }

        $email = $_POST['email'] ?? '';

        if (empty($email)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập email!']);
            exit;
        }

        try {
            $account = $this->accountModel->getAccountByUsernameOrEmail($email);
            if (!$account) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Email không tồn tại!']);
                exit;
            }

            $token = bin2hex(random_bytes(32));
            $current_time = time();
            $expires_at = date('Y-m-d H:i:s', $current_time + 3600);

            $this->passwordResetTokenModel->createToken($account['account_id'], $token, $expires_at);

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = '0022410169@student.dthu.edu.vn';
                $mail->Password = 'mebx bfpj kdzz xhqy';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->CharSet = 'UTF-8';
                $mail->Encoding = 'base64';

                $mail->setFrom('0022410169@student.dthu.edu.vn', 'Study Sharing', true);
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Đặt lại mật khẩu - Study Sharing';

                $resetLink = "http://localhost/study_sharing/auth/reset_password?token=$token";

                $mail->Body = $this->getPasswordResetEmailTemplate($resetLink);

                $mail->AltBody = mb_convert_encoding(
                    "Nhấn vào liên kết sau để đặt lại mật khẩu của bạn: $resetLink\nLiên kết này có hiệu lực trong 1 giờ.",
                    'UTF-8'
                );

                $mail->send();
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Liên kết đặt lại mật khẩu đã được gửi đến email của bạn!']);
            } catch (Exception $e) {
                error_log("Email error: " . $mail->ErrorInfo);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Lỗi gửi email: ' . $mail->ErrorInfo]);
            }
        } catch (PDOException $e) {
            error_log("Forgot password error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Lỗi server, vui lòng thử lại sau!']);
        }
        exit;
    }

    private function getPasswordResetEmailTemplate($resetLink)
    {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="vi">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Đặt lại mật khẩu - Study Sharing</title>
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    margin: 0;
                    padding: 0;
                    background-color: #f5f5f5;
                }
                .email-container {
                    max-width: 600px;
                    margin: 0 auto;
                    background-color: #ffffff;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                }
                .email-header {
                    background-color: #0d6efd;
                    color: white;
                    padding: 20px;
                    text-align: center;
                }
                .email-header img {
                    height: 50px;
                }
                .email-body {
                    padding: 30px;
                }
                .email-footer {
                    background-color: #f8f9fa;
                    padding: 20px;
                    text-align: center;
                    font-size: 12px;
                    color: #6c757d;
                }
                .btn-reset {
                    display: inline-block;
                    padding: 12px 24px;
                    background-color: #0d6efd;
                    color: white !important;
                    text-decoration: none;
                    border-radius: 4px;
                    font-weight: bold;
                    margin: 20px 0;
                }
                .text-muted {
                    color: #6c757d;
                    font-size: 14px;
                }
                .divider {
                    border-top: 1px solid #e9ecef;
                    margin: 20px 0;
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="email-header">
                    <h2>Study Sharing</h2>
                    <p>Đặt lại mật khẩu</p>
                </div>
                
                <div class="email-body">
                    <p>Xin chào,</p>
                    <p>Chúng tôi đã nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.</p>
                    
                    <div style="text-align: center;">
                        <a href="$resetLink" class="btn-reset">Đặt lại mật khẩu</a>
                    </div>
                    
                    <p>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
                    
                    <div class="divider"></div>
                    
                    <p class="text-muted">
                        <strong>Liên kết này sẽ hết hạn sau 1 giờ.</strong><br>
                        Nếu nút trên không hoạt động, bạn có thể sao chép và dán đường dẫn sau vào trình duyệt:<br>
                        <a href="$resetLink" style="word-break: break-all;">$resetLink</a>
                    </p>
                </div>
                
                <div class="email-footer">
                    <p>© 2025 Study Sharing. Đã đăng ký bản quyền.</p>
                    <p>Đây là email tự động, vui lòng không trả lời.</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    public function reset_password()
    {
        $title = "Đặt lại mật khẩu";
        require __DIR__ . '/../views/auth/reset_password.php';
    }

    public function resetPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['token'] ?? '';
            $password = $_POST['password'] ?? '';

            if (empty($token) || empty($password)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin!']);
                exit;
            }

            try {
                error_log("Reset password attempt with token: $token");
                $query = "SELECT * FROM password_reset_tokens WHERE token = :token AND expires_at > NOW()";
                $stmt = $this->pdo->prepare($query);
                $stmt->bindParam(':token', $token, PDO::PARAM_STR);
                $stmt->execute();
                $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$tokenData) {
                    error_log("Token not found or expired: $token");
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Token không hợp lệ hoặc đã hết hạn!']);
                    exit;
                }

                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $account_id = $tokenData['account_id'];
                $this->accountModel->updatePassword($account_id, $hashedPassword);

                $this->passwordResetTokenModel->deleteToken($tokenData['token_id']);

                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Mật khẩu đã được đặt lại thành công!']);
            } catch (PDOException $e) {
                error_log("Reset password error: " . $e->getMessage());
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Lỗi server, vui lòng thử lại sau!']);
            }
            exit;
        }
    }

    public function changePassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['account_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ hoặc chưa đăng nhập!']);
            exit;
        }

        $account_id = $_SESSION['account_id'];
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_new_password = $_POST['confirm_new_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin!']);
            exit;
        }

        if ($new_password !== $confirm_new_password) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Mật khẩu mới và xác nhận mật khẩu không khớp!']);
            exit;
        }

        try {
            $account = $this->accountModel->getAccountById($account_id);
            if (!$account || !password_verify($current_password, $account['password'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Mật khẩu hiện tại không đúng!']);
                exit;
            }

            $hashedPassword = password_hash($new_password, PASSWORD_BCRYPT);
            $this->accountModel->updatePassword($account_id, $hashedPassword);

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Đổi mật khẩu thành công!']);
        } catch (PDOException $e) {
            error_log("Change password error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Lỗi server, vui lòng thử lại sau!']);
        }
        exit;
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        session_destroy();
        header('Location: /study_sharing');
        exit;
    }
}
