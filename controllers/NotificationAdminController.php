<?php

namespace App;

use App\User;
use App\Notification;

class NotificationAdminController
{
    private $pdo;
    private $userModel;
    private $notificationModel;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->userModel = new User($pdo);
        $this->notificationModel = new Notification($pdo);
    }

    // View gửi thông báo
    public function admin_send_notification()
    {
        global $pdo;

        $userModel = new \App\User($pdo);
        $users = $userModel->getAllUsers();
        $response = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $message = trim($_POST['message'] ?? '');
            $target_id = $_POST['target_id'] ?? 'all';

            $account_id = ($target_id === 'all') ? null : (int)$target_id;
            $response = $this->sendNotification($message, $account_id);
        }

        $title = "Gửi thông báo đến người dùng";

        // Dữ liệu truyền sang view
        ob_start();
        require __DIR__ . '/../views/notification/admin_send_notification.php';
        $content = ob_get_clean();

        // Giao diện chính
        require __DIR__ . '/../views/layouts/admin_layout.php';
    }


    // Hàm xử lý gửi thông báo đã có sẵn ở đây
    public function sendNotification(string $message, ?int $account_id = null): array
    {
        $results = [];

        if (empty($message)) {
            return ['status' => false, 'message' => 'Nội dung thông báo không được để trống.'];
        }

        try {
            if ($account_id === null) {
                $users = $this->userModel->getAllUsers();

                foreach ($users as $user) {
                    $success = $this->notificationModel->createNotification($user['account_id'], $message, false);
                    $results[] = [
                        'account_id' => $user['account_id'],
                        'status' => $success ? 'sent' : 'failed'
                    ];
                }

                return ['status' => true, 'message' => 'Đã gửi thông báo đến tất cả người dùng.', 'results' => $results];
            } else {
                $user = $this->userModel->getUserById($account_id);
                if (!$user) {
                    return ['status' => false, 'message' => 'Người dùng không tồn tại.'];
                }

                $success = $this->notificationModel->createNotification($account_id, $message, false);

                return [
                    'status' => $success,
                    'message' => $success ? 'Thông báo đã được gửi.' : 'Không thể gửi thông báo.'
                ];
            }
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
}
