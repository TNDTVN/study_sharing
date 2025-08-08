<?php

namespace App;

use App\Account;
use App\Notification;
use Exception;
use HTMLPurifier;
use HTMLPurifier_Config;

class NotificationAdminController
{
    private $pdo;
    private $accountModel;
    private $notificationModel;
    private $courseModel;
    private $current_user_id;
    private $purifier;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->accountModel = new Account($pdo);
        $this->notificationModel = new Notification($pdo);
        $this->courseModel = new Course($pdo);
        $this->current_user_id = $_SESSION['user_id'] ?? null;

        // Khởi tạo HTMLPurifier
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,b,i,u,strong,em,a[href],ul,ol,li,br');
        $this->purifier = new HTMLPurifier($config);
    }

    public function admin_send_notification()
    {
        global $pdo;

        $accountModel = new \App\Account($pdo);
        $users = $accountModel->getAllAccounts();
        $response = null;

        // Kiểm tra số lượng người dùng theo vai trò
        $students = array_filter($users, fn($user) => isset($user['role']) && $user['role'] === 'student');
        $teachers = array_filter($users, fn($user) => isset($user['role']) && $user['role'] === 'teacher');

        // Tạo thông báo nếu không có giáo viên hoặc học sinh
        $warnings = [];
        if (empty($teachers)) {
            $warnings[] = 'Hệ thống hiện không có giáo viên nào được đăng ký.';
        }
        if (empty($students)) {
            $warnings[] = 'Hệ thống hiện không có học sinh nào được đăng ký.';
        }
        if (!empty($warnings)) {
            $response = [
                'status' => false,
                'message' => implode(' ', $warnings)
            ];
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $message = $_POST['message'] ?? '';
            // Làm sạch nội dung HTML từ TinyMCE
            $message = $this->purifier->purify(trim($message));
            if (empty($message)) {
                $response = ['status' => false, 'message' => 'Nội dung thông báo không được để trống.'];
            } else {
                $target_type = $_POST['target_type'] ?? 'all';
                $target_ids = $_POST['target_ids'] ?? [];
                $role = $_POST['role'] ?? null;
                $admin_ids = $_POST['admin_ids'] ?? [];
                $teacher_ids = $_POST['teacher_ids'] ?? [];
                $student_ids = $_POST['student_ids'] ?? [];

                // Kiểm tra khi chọn gửi theo vai trò
                if ($target_type === 'role') {
                    if ($role === 'teacher' && empty($teacher_ids) && empty($teachers)) {
                        $response = ['status' => false, 'message' => 'Không có giáo viên nào để gửi thông báo.'];
                    } elseif ($role === 'student' && empty($student_ids) && empty($students)) {
                        $response = ['status' => false, 'message' => 'Không có học sinh nào để gửi thông báo.'];
                    } else {
                        $response = $this->sendNotification($message, $target_type, $target_ids, $role, $admin_ids, $teacher_ids, $student_ids);
                    }
                } else {
                    $response = $this->sendNotification($message, $target_type, $target_ids, $role, $admin_ids, $teacher_ids, $student_ids);
                }
            }
        }

        $title = "Gửi thông báo đến người dùng";

        ob_start();
        require __DIR__ . '/../views/notification/admin_send_notification.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layouts/admin_layout.php';
    }

    private function sendToUser(int $account_id, string $message, array &$results): void
    {
        if ($account_id == $this->current_user_id) {
            $results[] = [
                'account_id' => $account_id,
                'status' => 'skipped',
                'message' => 'Không thể gửi thông báo cho chính bạn.'
            ];
            return;
        }

        $user = $this->accountModel->getAccountById($account_id);
        if (!$user) {
            $results[] = [
                'account_id' => $account_id,
                'status' => 'failed',
                'message' => 'Người dùng không tồn tại.'
            ];
            return;
        }

        $success = $this->notificationModel->createNotification($account_id, $message, false);
        $results[] = [
            'account_id' => $account_id,
            'status' => $success ? 'sent' : 'failed'
        ];
    }

    public function sendNotification(string $message, string $target_type, array $target_ids = [], ?string $role = null, array $admin_ids = [], array $teacher_ids = [], array $student_ids = []): array
    {
        $results = [];

        if (empty($message)) {
            return ['status' => false, 'message' => 'Nội dung thông báo không được để trống.'];
        }

        try {
            if ($target_type === 'all') {
                $users = $this->accountModel->getAllAccounts();
                if (empty($users)) {
                    return ['status' => false, 'message' => 'Không có người dùng nào để gửi thông báo.'];
                }
                foreach ($users as $user) {
                    $this->sendToUser($user['account_id'], $message, $results);
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
                        $this->sendToUser($account_id, $message, $results);
                    }
                    return ['status' => true, 'message' => "Đã gửi thông báo đến các tài khoản được chọn trong vai trò $role (trừ tài khoản của bạn).", 'results' => $results];
                }

                $users = $this->accountModel->getUsersByRole($role);
                if (empty($users)) {
                    return ['status' => false, 'message' => "Không tìm thấy người dùng với vai trò $role."];
                }
                foreach ($users as $user) {
                    $this->sendToUser($user['account_id'], $message, $results);
                }
                return ['status' => true, 'message' => "Đã gửi thông báo đến tất cả người dùng có vai trò $role (trừ tài khoản của bạn).", 'results' => $results];
            } elseif ($target_type === 'account') {
                if (empty($target_ids)) {
                    return ['status' => false, 'message' => 'Vui lòng chọn ít nhất một tài khoản.'];
                }
                foreach ($target_ids as $account_id) {
                    $this->sendToUser($account_id, $message, $results);
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
            if (!$notification) {
                echo json_encode(['success' => false, 'message' => 'Thông báo không tồn tại']);
                exit;
            }

            // Thực hiện hành động (chấp nhận hoặc từ chối khóa học)
            if ($action === 'accept') {
                $this->courseModel->updateCourseStatus($course_id, 'active');
                $this->notificationModel->createNotification(
                    $course['created_by'],
                    $this->purifier->purify("Yêu cầu mở khóa học '{$course['course_name']}' đã được chấp nhận."),
                    false
                );
            } else {
                $this->courseModel->updateCourseStatus($course_id, 'rejected');
                $this->notificationModel->createNotification(
                    $course['created_by'],
                    $this->purifier->purify("Yêu cầu mở khóa học '{$course['course_name']}' đã bị từ chối."),
                    false
                );
            }

            // Đánh dấu thông báo là đã đọc
            $this->notificationModel->markAsRead($notification_id, $notification['account_id']);

            echo json_encode(['success' => true, 'message' => 'Yêu cầu đã được xử lý thành công']);
            exit;
        } catch (Exception $e) {
            error_log("Handle open course request error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
            exit;
        }
    }
}
