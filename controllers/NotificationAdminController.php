<?php

namespace App;

use App\Account; // Thay User bằng Account
use App\Notification;

class NotificationAdminController
{
    private $pdo;
    private $accountModel; // Thay userModel bằng accountModel
    private $notificationModel;
    private $current_user_id;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->accountModel = new Account($pdo); // Sử dụng Account model
        $this->notificationModel = new Notification($pdo);
        $this->current_user_id = $_SESSION['user_id'] ?? null;
    }

    public function admin_send_notification()
    {
        global $pdo;

        $accountModel = new \App\Account($pdo); // Sử dụng Account model
        $users = $accountModel->getAllAccounts(); // Lấy từ bảng accounts
        $response = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $message = trim($_POST['message'] ?? '');
            $target_type = $_POST['target_type'] ?? 'all';
            $target_ids = $_POST['target_ids'] ?? [];
            $role = $_POST['role'] ?? null;
            $admin_ids = $_POST['admin_ids'] ?? [];
            $teacher_ids = $_POST['teacher_ids'] ?? [];
            $student_ids = $_POST['student_ids'] ?? [];

            $response = $this->sendNotification($message, $target_type, $target_ids, $role, $admin_ids, $teacher_ids, $student_ids);
        }

        $title = "Gửi thông báo đến người dùng";

        ob_start();
        require __DIR__ . '/../views/notification/admin_send_notification.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layouts/admin_layout.php';
    }

    public function sendNotification(string $message, string $target_type, array $target_ids = [], ?string $role = null, array $admin_ids = [], array $teacher_ids = [], array $student_ids = []): array
    {
        $results = [];

        if (empty($message)) {
            return ['status' => false, 'message' => 'Nội dung thông báo không được để trống.'];
        }

        try {
            if ($target_type === 'all') {
                $users = $this->accountModel->getAllAccounts(); // Sử dụng accountModel
                foreach ($users as $user) {
                    if ($user['account_id'] == $this->current_user_id) {
                        continue;
                    }
                    $success = $this->notificationModel->createNotification($user['account_id'], $message, false);
                    $results[] = [
                        'account_id' => $user['account_id'],
                        'status' => $success ? 'sent' : 'failed'
                    ];
                }
                return ['status' => true, 'message' => 'Đã gửi thông báo đến tất cả người dùng (trừ tài khoản của bạn).', 'results' => $results];
            } elseif ($target_type === 'role') {
                if (empty($role) || !in_array($role, ['admin', 'teacher', 'student'])) {
                    return ['status' => false, 'message' => 'Vai trò không hợp lệ.'];
                }
                $selected_ids = [];
                if ($role === 'admin' && !empty($admin_ids)) {
                    $selected_ids = $admin_ids;
                } elseif ($role === 'teacher' && !empty($teacher_ids)) {
                    $selected_ids = $teacher_ids;
                } elseif ($role === 'student' && !empty($student_ids)) {
                    $selected_ids = $student_ids;
                }

                if (!empty($selected_ids)) {
                    foreach ($selected_ids as $account_id) {
                        if ($account_id == $this->current_user_id) {
                            $results[] = [
                                'account_id' => $account_id,
                                'status' => 'skipped',
                                'message' => 'Không thể gửi thông báo cho chính bạn.'
                            ];
                            continue;
                        }
                        $user = $this->accountModel->getAccountById($account_id); // Sử dụng accountModel
                        if (!$user || $user['role'] !== $role) {
                            $results[] = [
                                'account_id' => $account_id,
                                'status' => 'failed',
                                'message' => 'Người dùng không tồn tại hoặc không thuộc vai trò này.'
                            ];
                            continue;
                        }
                        $success = $this->notificationModel->createNotification($account_id, $message, false);
                        $results[] = [
                            'account_id' => $account_id,
                            'status' => $success ? 'sent' : 'failed'
                        ];
                    }
                    return ['status' => true, 'message' => "Đã gửi thông báo đến các tài khoản được chọn trong vai trò $role (trừ tài khoản của bạn).", 'results' => $results];
                }
                $users = $this->accountModel->getUsersByRole($role); // Sử dụng accountModel
                if (empty($users)) {
                    return ['status' => false, 'message' => 'Không tìm thấy người dùng với vai trò này.'];
                }
                foreach ($users as $user) {
                    if ($user['account_id'] == $this->current_user_id) {
                        continue;
                    }
                    $success = $this->notificationModel->createNotification($user['account_id'], $message, false);
                    $results[] = [
                        'account_id' => $user['account_id'],
                        'status' => $success ? 'sent' : 'failed'
                    ];
                }
                return ['status' => true, 'message' => "Đã gửi thông báo đến tất cả người dùng có vai trò $role (trừ tài khoản của bạn).", 'results' => $results];
            } elseif ($target_type === 'account') {
                if (empty($target_ids)) {
                    return ['status' => false, 'message' => 'Vui lòng chọn ít nhất một tài khoản.'];
                }
                foreach ($target_ids as $account_id) {
                    if ($account_id == $this->current_user_id) {
                        $results[] = [
                            'account_id' => $account_id,
                            'status' => 'skipped',
                            'message' => 'Không thể gửi thông báo cho chính bạn.'
                        ];
                        continue;
                    }
                    $user = $this->accountModel->getAccountById($account_id); // Sử dụng accountModel
                    if (!$user) {
                        $results[] = [
                            'account_id' => $account_id,
                            'status' => 'failed',
                            'message' => 'Người dùng không tồn tại.'
                        ];
                        continue;
                    }
                    $success = $this->notificationModel->createNotification($account_id, $message, false);
                    $results[] = [
                        'account_id' => $account_id,
                        'status' => $success ? 'sent' : 'failed'
                    ];
                }
                return ['status' => true, 'message' => 'Đã gửi thông báo đến các tài khoản được chọn (trừ tài khoản của bạn).', 'results' => $results];
            } else {
                return ['status' => false, 'message' => 'Loại mục tiêu không hợp lệ.'];
            }
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
}
