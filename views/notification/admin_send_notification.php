<?php

/** @var string $title */
/** @var array $users */
/** @var array|null $response */

$title = "Gửi thông báo đến người dùng";
$current_user_id = $_SESSION['user_id'] ?? null;

// Lọc danh sách các vai trò từ $users (lấy từ bảng accounts)
$students = array_filter($users, fn($user) => $user['role'] === 'student');
$admins = array_filter($users, fn($user) => $user['role'] === 'admin');
$teachers = array_filter($users, fn($user) => $user['role'] === 'teacher');
?>

<div class="container py-4">
    <h1 class="text-primary mb-4"><i class="bi bi-megaphone"></i> Gửi thông báo</h1>

    <!-- Nút kích hoạt popup -->
    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#notificationModal">
        <i class="bi bi-plus-circle"></i> Tạo thông báo mới
    </button>

    <?php if ($response): ?>
        <div class="alert <?= $response['status'] ? 'alert-success' : 'alert-danger' ?>">
            <?= htmlspecialchars($response['message']) ?>
        </div>

        <?php if (isset($response['results'])): ?>
            <ul class="list-group mb-3">
                <?php foreach ($response['results'] as $res): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>ID: <?= htmlspecialchars($res['account_id']) ?></span>
                        <span class="badge 
                            <?php
                            if ($res['status'] === 'sent') {
                                echo 'bg-success';
                            } elseif ($res['status'] === 'skipped') {
                                echo 'bg-warning';
                            } else {
                                echo 'bg-danger';
                            }
                            ?>">
                            <?= htmlspecialchars($res['status']) ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Modal Popup -->
    <div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="notificationModalLabel">Gửi thông báo mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="notificationForm">
                        <div class="mb-3">
                            <label for="message" class="form-label">Nội dung thông báo</label>
                            <textarea class="form-control" id="message" name="message" rows="3" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="target_type" class="form-label">Gửi đến</label>
                            <select class="form-select" id="target_type" name="target_type" onchange="toggleTargetOptions()">
                                <option value="all" <?= (!isset($_POST['target_type']) || $_POST['target_type'] === 'all') ? 'selected' : '' ?>>Tất cả người dùng</option>
                                <option value="role" <?= (isset($_POST['target_type']) && $_POST['target_type'] === 'role') ? 'selected' : '' ?>>Theo vai trò</option>
                                <option value="account" <?= (isset($_POST['target_type']) && $_POST['target_type'] === 'account') ? 'selected' : '' ?>>Theo tài khoản</option>
                            </select>
                        </div>

                        <!-- Tùy chọn theo vai trò -->
                        <div class="mb-3" id="role_options" style="display: <?= (isset($_POST['target_type']) && $_POST['target_type'] === 'role') ? 'block' : 'none' ?>;">
                            <label for="role" class="form-label">Chọn vai trò</label>
                            <select class="form-select" id="role" name="role" onchange="toggleRoleOptions()">
                                <option value="admin" <?= (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                                <option value="teacher" <?= (isset($_POST['role']) && $_POST['role'] === 'teacher') ? 'selected' : '' ?>>Teacher</option>
                                <option value="student" <?= (isset($_POST['role']) && $_POST['role'] === 'student') ? 'selected' : '' ?>>Student</option>
                            </select>

                            <!-- Danh sách tài khoản admin khi chọn vai trò admin -->
                            <div class="mb-3 mt-3" id="admin_options" style="display: <?= (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'block' : 'none' ?>;">
                                <label class="form-label">Chọn tài khoản Admin</label>
                                <div class="list-group" style="max-height: 200px; overflow-y: auto;">
                                    <label class="list-group-item d-flex align-items-center">
                                        <input class="form-check-input me-2" type="checkbox" id="select_all_admins" onclick="toggleSelectAll('admin_ids[]', this)">
                                        Chọn tất cả Admin
                                    </label>
                                    <?php foreach ($admins as $user): ?>
                                        <?php if ($user['account_id'] != $current_user_id): ?>
                                            <label class="list-group-item d-flex align-items-center">
                                                <input class="form-check-input me-2" type="checkbox" name="admin_ids[]" value="<?= $user['account_id'] ?>"
                                                    <?= (isset($_POST['admin_ids']) && in_array($user['account_id'], $_POST['admin_ids'])) ? 'checked' : '' ?>>
                                                <?= htmlspecialchars($user['full_name'] . ' (' . $user['username'] . ')') ?>
                                            </label>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Danh sách tài khoản teacher khi chọn vai trò teacher -->
                            <div class="mb-3 mt-3" id="teacher_options" style="display: <?= (isset($_POST['role']) && $_POST['role'] === 'teacher') ? 'block' : 'none' ?>;">
                                <label class="form-label">Chọn tài khoản Teacher</label>
                                <div class="list-group" style="max-height: 200px; overflow-y: auto;">
                                    <label class="list-group-item d-flex align-items-center">
                                        <input class="form-check-input me-2" type="checkbox" id="select_all_teachers" onclick="toggleSelectAll('teacher_ids[]', this)">
                                        Chọn tất cả Teacher
                                    </label>
                                    <?php foreach ($teachers as $user): ?>
                                        <?php if ($user['account_id'] != $current_user_id): ?>
                                            <label class="list-group-item d-flex align-items-center">
                                                <input class="form-check-input me-2" type="checkbox" name="teacher_ids[]" value="<?= $user['account_id'] ?>"
                                                    <?= (isset($_POST['teacher_ids']) && in_array($user['account_id'], $_POST['teacher_ids'])) ? 'checked' : '' ?>>
                                                <?= htmlspecialchars($user['full_name'] . ' (' . $user['username'] . ')') ?>
                                            </label>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Danh sách tài khoản student khi chọn vai trò student -->
                            <div class="mb-3 mt-3" id="student_options" style="display: <?= (isset($_POST['role']) && $_POST['role'] === 'student') ? 'block' : 'none' ?>;">
                                <label class="form-label">Chọn tài khoản Student</label>
                                <div class="list-group" style="max-height: 200px; overflow-y: auto;">
                                    <label class="list-group-item d-flex align-items-center">
                                        <input class="form-check-input me-2" type="checkbox" id="select_all_students" onclick="toggleSelectAll('student_ids[]', this)">
                                        Chọn tất cả Student
                                    </label>
                                    <?php foreach ($students as $user): ?>
                                        <?php if ($user['account_id'] != $current_user_id): ?>
                                            <label class="list-group-item d-flex align-items-center">
                                                <input class="form-check-input me-2" type="checkbox" name="student_ids[]" value="<?= $user['account_id'] ?>"
                                                    <?= (isset($_POST['student_ids']) && in_array($user['account_id'], $_POST['student_ids'])) ? 'checked' : '' ?>>
                                                <?= htmlspecialchars($user['full_name'] . ' (' . $user['username'] . ')') ?>
                                            </label>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Tùy chọn theo tài khoản -->
                        <div class="mb-3" id="account_options" style="display: <?= (isset($_POST['target_type']) && $_POST['target_type'] === 'account') ? 'block' : 'none' ?>;">
                            <label class="form-label">Chọn tài khoản</label>
                            <div class="list-group" style="max-height: 200px; overflow-y: auto;">
                                <label class="list-group-item d-flex align-items-center">
                                    <input class="form-check-input me-2" type="checkbox" id="select_all_accounts" onclick="toggleSelectAll('target_ids[]', this)">
                                    Chọn tất cả tài khoản
                                </label>
                                <?php foreach ($users as $user): ?>
                                    <?php if ($user['account_id'] != $current_user_id): ?>
                                        <label class="list-group-item d-flex align-items-center">
                                            <input class="form-check-input me-2" type="checkbox" name="target_ids[]" value="<?= $user['account_id'] ?>"
                                                <?= (isset($_POST['target_ids']) && in_array($user['account_id'], $_POST['target_ids'])) ? 'checked' : '' ?>>
                                            <?= htmlspecialchars($user['full_name'] . ' (' . $user['username'] . ')') ?>
                                        </label>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Gửi thông báo</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Script để xử lý hiển thị tùy chọn và chọn tất cả -->
    <script>
        function toggleTargetOptions() {
            const targetType = document.getElementById('target_type').value;
            document.getElementById('role_options').style.display = targetType === 'role' ? 'block' : 'none';
            document.getElementById('account_options').style.display = targetType === 'account' ? 'block' : 'none';
            if (targetType === 'role') {
                toggleRoleOptions();
            }
        }

        function toggleRoleOptions() {
            const role = document.getElementById('role').value;
            document.getElementById('admin_options').style.display = role === 'admin' ? 'block' : 'none';
            document.getElementById('teacher_options').style.display = role === 'teacher' ? 'block' : 'none';
            document.getElementById('student_options').style.display = role === 'student' ? 'block' : 'none';
        }

        function toggleSelectAll(name, checkbox) {
            const checkboxes = document.getElementsByName(name);
            for (let i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = checkbox.checked;
            }
        }
    </script>
</div>