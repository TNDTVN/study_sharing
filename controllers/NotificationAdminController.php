<?php

namespace App;

use App\Account; // Thay User bằng Account
use App\Notification;
use Exception;

class NotificationAdminController
{
    private $pdo;
    private $accountModel;
    private $notificationModel;
    private $courseModel;
    private $current_user_id;


    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->accountModel = new Account($pdo);
        $this->notificationModel = new Notification($pdo);
        $this->courseModel = new Course($pdo);
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
    public function handleOpenCourseRequest()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xử lý yêu cầu này']);
            exit;
        }



        $data = json_decode(file_get_contents('php://input'), true);
        error_log("Input data: " . print_r($data, true));
        $course_id = (int)($data['course_id'] ?? 0);
        $action = $data['action'] ?? '';
        $notification_id = (int)($data['notification_id'] ?? 0);

        if ($course_id <= 0 || !in_array($action, ['accept', 'reject'])) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }
        if ($notification_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID thông báo không hợp lệ']);
            exit;
        }
        if (!is_int($this->current_user_id) || $this->current_user_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID quản trị viên không hợp lệ']);
            exit;
        }

        try {
            $course = $this->courseModel->getCourseById($course_id);
            if (!$course) {
                echo json_encode(['success' => false, 'message' => 'Khóa học không tồn tại']);
                exit;
            }

            $notification = $this->notificationModel->getNotificationById($notification_id);
            if (!$notification || $notification['account_id'] != $this->current_user_id) {
                echo json_encode(['success' => false, 'message' => 'Thông báo không tồn tại hoặc bạn không có quyền xử lý']);
                exit;
            }
        } catch (Exception $e) {
            error_log("Handle open course request error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()]);
            exit;
        } catch (Exception $e) {
            error_log("Handle open course request error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
            exit;
        }
    }
}
