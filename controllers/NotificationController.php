<?php

namespace App;

use App\Notification;
use App\User;

class NotificationController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function list()
    {
        if (!isset($_SESSION['account_id'])) {
            header('Location: /study_sharing/auth/login');
            exit;
        }

        $notificationModel = new Notification($this->pdo);
        $userModel = new User($this->pdo);
        $user = $userModel->getUserById($_SESSION['account_id']);

        // Phân trang
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        $totalNotifications = $notificationModel->countNotificationsByUserId($_SESSION['account_id']);
        $notifications = $notificationModel->getNotificationsByUserId($_SESSION['account_id'], $offset, $perPage);
        $totalPages = ceil($totalNotifications / $perPage);

        $title = 'Thông báo';
        ob_start();
        require __DIR__ . '/../views/notification/list.php';
        $content = ob_get_clean();
        // Truyền $pdo vào layout.php
        $pdo = $this->pdo;
        require __DIR__ . '/../views/layouts/layout.php';
    }

    // Hàm để lấy thông báo cho admin
    public function list_admin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Kiểm tra đã đăng nhập chưa
        if (!isset($_SESSION['account_id'])) {
            header('Location: /study_sharing/auth/login');
            exit;
        }

        $accountId = $_SESSION['account_id'];
        $userModel = new User($this->pdo);
        $user = $userModel->getUserById($accountId);

        // Kiểm tra vai trò có phải admin không
        if (!isset($user['role']) || $user['role'] !== 'admin') {
            echo "Bạn không có quyền truy cập trang này.";
            exit;
        }

        // Khởi tạo model
        $notificationModel = new Notification($this->pdo);

        // Phân trang
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        // Lấy tổng số và danh sách thông báo gửi cho admin
        $totalNotifications = $notificationModel->countNotificationsByUserId($accountId);
        $notifications = $notificationModel->getNotificationsByUserId($accountId, $offset, $perPage);
        $totalPages = ceil($totalNotifications / $perPage);

        $title = 'Thông báo quản trị';

        // Gọi nội dung view
        ob_start();
        // Pass variables explicitly to the view
        require __DIR__ . '/../views/notification/list_admin.php';
        $content = ob_get_clean();

        // Truyền $pdo và các biến khác vào layout
        $pdo = $this->pdo;
        require __DIR__ . '/../views/layouts/admin_layout.php';
    }


    public function markRead()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['account_id'])) {
            $notificationId = $_POST['notification_id'] ?? null;
            if ($notificationId) {
                $notificationModel = new Notification($this->pdo);
                $success = $notificationModel->markAsRead($notificationId, $_SESSION['account_id']);
                echo json_encode(['success' => $success]);
                exit;
            }
        }
        echo json_encode(['success' => false]);
        exit;
    }

    public function markAllRead()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['account_id'])) {
            $userId = $_POST['user_id'] ?? null;
            if ($userId == $_SESSION['account_id']) {
                $notificationModel = new Notification($this->pdo);
                $success = $notificationModel->markAllAsRead($userId);
                echo json_encode(['success' => $success]);
                exit;
            }
        }
        echo json_encode(['success' => false]);
        exit;
    }

    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['account_id'])) {
            $notificationId = $_POST['notification_id'] ?? null;
            if ($notificationId) {
                $notificationModel = new Notification($this->pdo);
                $success = $notificationModel->deleteNotification($notificationId, $_SESSION['account_id']);
                echo json_encode(['success' => $success]);
                exit;
            }
        }
        echo json_encode(['success' => false]);
        exit;
    }

    public function deleteAll()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['account_id'])) {
            $userId = $_POST['user_id'] ?? null;
            if ($userId == $_SESSION['account_id']) {
                $notificationModel = new Notification($this->pdo);
                $success = $notificationModel->deleteAllNotifications($userId);
                echo json_encode(['success' => $success]);
                exit;
            }
        }
        echo json_encode(['success' => false]);
        exit;
    }
}
