<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();

/** @var string $title */
/** @var array $users */
/** @var array|null $response */

$title = "Gửi thông báo đến người dùng";
$current_user_id = $_SESSION['user_id'] ?? null;

// Lọc danh sách các vai trò từ $users (lấy từ bảng accounts)
$students = array_filter($users, fn($user) => isset($user['role']) && $user['role'] === 'student');
$admins = array_filter($users, fn($user) => isset($user['role']) && $user['role'] === 'admin');
$teachers = array_filter($users, fn($user) => isset($user['role']) && $user['role'] === 'teacher');
?>
<style>
    :root {
        --primary: #ff6b6b;
        --secondary: #ff8787;
        --accent: #4cc9f0;
        --background: #f4f7fa;
        --card-bg: #ffffff;
        --text: #2d3436;
        --border: #e0e6ed;
        --shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
    }

    body {
        background: linear-gradient(to bottom, var(--background), #dfe6e9);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        color: var(--text);
        line-height: 1.6;
    }

    .content {
        padding-top: 0 !important;
        padding-bottom: 0 !important;
    }

    .container.py-5 {
        padding-top: 0 !important;
        padding-bottom: 0 !important;
    }

    .card {
        border: none;
        border-radius: 16px;
        background: var(--card-bg);
        box-shadow: var(--shadow);
        transition: transform 0.1s ease, box-shadow 0.1s ease;
    }

    .card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
    }

    .card-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 16px 16px 0 0;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .card-header i {
        font-size: 2rem;
        color: #fff;
    }

    .card-header h3 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #fff;
        margin: 0;
    }

    .card-body {
        padding: 2rem;
    }

    .alert {
        border-radius: 12px;
        border-left: 5px solid;
        border-color: #28a745;
        position: relative;
        padding: 1.5rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .alert-danger {
        border-color: #dc3545;
    }

    .alert i {
        font-size: 1.5rem;
        vertical-align: middle;
    }

    .table-responsive {
        max-height: 300px;
        overflow-y: auto;
        border-radius: 8px;
    }

    .table thead th {
        background: #f8f9fa;
        position: sticky;
        top: 0;
        z-index: 1;
    }

    .table tr.table-success {
        background: rgba(40, 167, 69, 0.1);
    }

    .table tr.table-warning {
        background: rgba(255, 193, 7, 0.1);
    }

    .table tr.table-danger {
        background: rgba(220, 53, 69, 0.1);
    }

    .form-label {
        font-weight: 600;
        color: var(--text);
        margin-bottom: 0.5rem;
    }

    .form-control,
    .form-select {
        border-radius: 10px;
        border: 1px solid var(--border);
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 0.2rem rgba(255, 107, 107, 0.25);
    }

    .textarea-container {
        position: relative;
    }

    .char-counter {
        position: absolute;
        bottom: -1.5rem;
        right: 0;
        font-size: 0.85rem;
        color: #6c757d;
    }

    .char-counter.warning {
        color: #dc3545;
        font-weight: 500;
    }

    .target-selector {
        display: flex;
        gap: 1rem;
        margin: 1.5rem 0;
    }

    .target-option {
        flex: 1;
        padding: 1.25rem;
        border: 2px solid var(--border);
        border-radius: 12px;
        text-align: center;
        cursor: pointer;
        background: var(--card-bg);
        transition: all 0.3s ease;
    }

    .target-option:hover {
        background: rgba(255, 107, 107, 0.05);
        border-color: var(--primary);
    }

    .target-option.active {
        border-color: var(--primary);
        background: rgba(255, 107, 107, 0.1);
        box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.2);
    }

    .target-option i {
        font-size: 2rem;
        color: var(--primary);
        margin-bottom: 0.5rem;
    }

    .target-option.active i {
        color: var(--secondary);
    }

    .search-container {
        position: relative;
        margin-bottom: 1rem;
    }

    .search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
    }

    .search-input {
        padding-left: 2.5rem;
        border-radius: 10px;
    }

    .user-list {
        max-height: 250px;
        overflow-y: auto;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: var(--card-bg);
    }

    .user-list .list-group-item {
        border: none;
        border-bottom: 1px solid var(--border);
        padding: 0.75rem 1.25rem;
        transition: background 0.2s ease;
    }

    .user-list .list-group-item:hover {
        background: rgba(255, 107, 107, 0.05);
    }

    .user-list .list-group-item:last-child {
        border-bottom: none;
    }

    .role-badge {
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .badge-admin {
        background: rgba(108, 117, 125, 0.2);
        color: #495057;
    }

    .badge-teacher {
        background: rgba(32, 201, 151, 0.2);
        color: #0a7e5e;
    }

    .badge-student {
        background: rgba(13, 110, 253, 0.2);
        color: #0d6efd;
    }

    .btn-send {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border: none;
        border-radius: 10px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        width: 100%;

        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .btn-send:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(255, 107, 107, 0.3);
    }

    .toast {
        border-radius: 10px;
        box-shadow: var(--shadow);
    }

    .toast-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: #fff;
        border-radius: 10px 10px 0 0;
    }

    .toast-header .btn-close {
        filter: invert(1);
    }

    .empty-state {
        text-align: center;
        padding: 2rem;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 3rem;
        opacity: 0.3;
        margin-bottom: 1rem;
    }

    @media (max-width: 768px) {
        .target-selector {
            flex-direction: column;
        }

        .card-header h3 {
            font-size: 1.25rem;
        }

        .target-option i {
            font-size: 1.5rem;
        }
    }
</style>

<div class="container-fluid px-2 py-4">
    <div class="mx-auto">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-megaphone"></i>
                <h3><?php echo htmlspecialchars($title); ?></h3>
            </div>
            <div class="card-body">
                <!-- Hiển thị thông báo kết quả -->
                <?php if ($response): ?>
                    <div class="alert alert-dismissible fade show <?php echo $response['status'] ? 'alert-success' : 'alert-danger'; ?>" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi <?php echo $response['status'] ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> me-3"></i>
                            <div>
                                <h4 class="alert-heading"><?php echo $response['status'] ? 'Thành công' : 'Lỗi'; ?></h4>
                                <p class="mb-0"><?php echo htmlspecialchars($response['message']); ?></p>
                            </div>
                        </div>
                        <?php if (isset($response['results']) && !empty($response['results'])): ?>
                            <hr>
                            <div class="mt-3">
                                <h5 class="fw-bold">Chi tiết gửi thông báo:</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID tài khoản</th>
                                                <th>Trạng thái</th>
                                                <th>Thông báo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($response['results'] as $res): ?>
                                                <tr class="<?php echo $res['status'] === 'sent' ? 'table-success' : ($res['status'] === 'skipped' ? 'table-warning' : 'table-danger'); ?>">
                                                    <td><?php echo htmlspecialchars($res['account_id']); ?></td>
                                                    <td>
                                                        <span class="badge rounded-pill <?php
                                                                                        echo $res['status'] === 'sent' ? 'bg-success' : ($res['status'] === 'skipped' ? 'bg-warning' : 'bg-danger');
                                                                                        ?>">
                                                            <?php echo htmlspecialchars($res['status'] === 'sent' ? 'Gửi thành công' : ($res['status'] === 'skipped' ? 'Bỏ qua' : 'Thất bại')); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo isset($res['message']) ? htmlspecialchars($res['message']) : ''; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Form gửi thông báo -->
                <form method="POST" id="notificationForm">
                    <div class="mb-4 textarea-container">
                        <label for="message" class="form-label">Nội dung thông báo <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="message" name="message" rows="5" maxlength="500" placeholder="Nhập nội dung thông báo..." required oninput="updateCharCount()"><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        <div class="char-counter" id="char_count">500 ký tự còn lại</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Gửi đến <span class="text-danger">*</span></label>
                        <div class="target-selector">
                            <div class="target-option <?php echo (!isset($_POST['target_type']) || $_POST['target_type'] === 'all') ? 'active' : ''; ?>" data-target="all" onclick="selectTarget('all')">
                                <i class="bi bi-people-fill"></i>
                                <div>Tất cả người dùng</div>
                            </div>
                            <div class="target-option <?php echo (isset($_POST['target_type']) && $_POST['target_type'] === 'role') ? 'active' : ''; ?>" data-target="role" onclick="selectTarget('role')">
                                <i class="bi bi-person-badge-fill"></i>
                                <div>Theo vai trò</div>
                            </div>
                            <div class="target-option <?php echo (isset($_POST['target_type']) && $_POST['target_type'] === 'account') ? 'active' : ''; ?>" data-target="account" onclick="selectTarget('account')">
                                <i class="bi bi-person-fill"></i>
                                <div>Theo tài khoản</div>
                            </div>
                        </div>
                        <input type="hidden" id="target_type" name="target_type" value="<?php echo !isset($_POST['target_type']) || $_POST['target_type'] === 'all' ? 'all' : $_POST['target_type']; ?>">
                    </div>

                    <div id="role_options" style="display: <?php echo (isset($_POST['target_type']) && $_POST['target_type'] === 'role') ? 'block' : 'none'; ?>;">
                        <div class="mb-4">
                            <label for="role" class="form-label">Chọn vai trò</label>
                            <select class="form-select" id="role" name="role" onchange="toggleRoleOptions()">
                                <option value="" <?php echo !isset($_POST['role']) ? 'selected' : ''; ?>>Chọn vai trò</option>
                                <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                <option value="teacher" <?php echo (isset($_POST['role']) && $_POST['role'] === 'teacher') ? 'selected' : ''; ?>>Giáo viên</option>
                                <option value="student" <?php echo (isset($_POST['role']) && $_POST['role'] === 'student') ? 'selected' : ''; ?>>Học sinh</option>
                            </select>
                        </div>

                        <div class="mb-4" id="admin_options" style="display: <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'block' : 'none'; ?>;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label">Chọn tài khoản Admin</label>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleSelectAll('admin_ids[]', 'select_all_admins')">
                                    <i class="bi bi-check2-all me-1"></i> Chọn tất cả
                                </button>
                                <input type="checkbox" class="d-none" id="select_all_admins">
                            </div>
                            <div class="search-container">
                                <i class="bi bi-search search-icon"></i>
                                <input type="text" class="form-control search-input" id="admin_search" placeholder="Tìm kiếm admin..." oninput="debouncedSearchAccounts('admin_options', 'admin_search')">
                            </div>
                            <div class="user-list" id="admin_list">
                                <?php if (empty($admins)): ?>
                                    <div class="empty-state">
                                        <i class="bi bi-people"></i>
                                        <p>Không có admin nào</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($admins as $user): ?>
                                        <?php if ($user['account_id'] != $current_user_id): ?>
                                            <label class="list-group-item d-flex align-items-center" data-account-id="<?php echo htmlspecialchars($user['account_id']); ?>">
                                                <input class="form-check-input me-3" type="checkbox" name="admin_ids[]" value="<?php echo htmlspecialchars($user['account_id']); ?>" <?php echo (isset($_POST['admin_ids']) && in_array($user['account_id'], $_POST['admin_ids'])) ? 'checked' : ''; ?>>
                                                <div>
                                                    <div class="fw-medium"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                                        <span class="role-badge badge-admin">Admin</span>
                                                    </div>
                                                </div>
                                            </label>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <div class="list-group-item text-muted text-center d-none" id="admin_no_results">Không tìm thấy tài khoản</div>
                            </div>
                        </div>

                        <div class="mb-4" id="teacher_options" style="display: <?php echo (isset($_POST['role']) && $_POST['role'] === 'teacher') ? 'block' : 'none'; ?>;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label">Chọn tài khoản Giáo viên</label>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleSelectAll('teacher_ids[]', 'select_all_teachers')">
                                    <i class="bi bi-check2-all me-1"></i> Chọn tất cả
                                </button>
                                <input type="checkbox" class="d-none" id="select_all_teachers">
                            </div>
                            <div class="search-container">
                                <i class="bi bi-search search-icon"></i>
                                <input type="text" class="form-control search-input" id="teacher_search" placeholder="Tìm kiếm giáo viên..." oninput="debouncedSearchAccounts('teacher_options', 'teacher_search')">
                            </div>
                            <div class="user-list" id="teacher_list">
                                <?php if (empty($teachers)): ?>
                                    <div class="empty-state">
                                        <i class="bi bi-people"></i>
                                        <p>Không có giáo viên nào</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($teachers as $user): ?>
                                        <?php if ($user['account_id'] != $current_user_id): ?>
                                            <label class="list-group-item d-flex align-items-center" data-account-id="<?php echo htmlspecialchars($user['account_id']); ?>">
                                                <input class="form-check-input me-3" type="checkbox" name="teacher_ids[]" value="<?php echo htmlspecialchars($user['account_id']); ?>" <?php echo (isset($_POST['teacher_ids']) && in_array($user['account_id'], $_POST['teacher_ids'])) ? 'checked' : ''; ?>>
                                                <div>
                                                    <div class="fw-medium"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                                        <span class="role-badge badge-teacher">Giáo viên</span>
                                                    </div>
                                                </div>
                                            </label>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <div class="list-group-item text-muted text-center d-none" id="teacher_no_results">Không tìm thấy tài khoản</div>
                            </div>
                        </div>

                        <div class="mb-4" id="student_options" style="display: <?php echo (isset($_POST['role']) && $_POST['role'] === 'student') ? 'block' : 'none'; ?>;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label">Chọn tài khoản Học sinh</label>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleSelectAll('student_ids[]', 'select_all_students')">
                                    <i class="bi bi-check2-all me-1"></i> Chọn tất cả
                                </button>
                                <input type="checkbox" class="d-none" id="select_all_students">
                            </div>
                            <div class="search-container">
                                <i class="bi bi-search search-icon"></i>
                                <input type="text" class="form-control search-input" id="student_search" placeholder="Tìm kiếm học sinh..." oninput="debouncedSearchAccounts('student_options', 'student_search')">
                            </div>
                            <div class="user-list" id="student_list">
                                <?php if (empty($students)): ?>
                                    <div class="empty-state">
                                        <i class="bi bi-people"></i>
                                        <p>Không có học sinh nào</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($students as $user): ?>
                                        <?php if ($user['account_id'] != $current_user_id): ?>
                                            <label class="list-group-item d-flex align-items-center" data-account-id="<?php echo htmlspecialchars($user['account_id']); ?>">
                                                <input class="form-check-input me-3" type="checkbox" name="student_ids[]" value="<?php echo htmlspecialchars($user['account_id']); ?>" <?php echo (isset($_POST['student_ids']) && in_array($user['account_id'], $_POST['student_ids'])) ? 'checked' : ''; ?>>
                                                <div>
                                                    <div class="fw-medium"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                                        <span class="role-badge badge-student">Học sinh</span>
                                                    </div>
                                                </div>
                                            </label>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <div class="list-group-item text-muted text-center d-none" id="student_no_results">Không tìm thấy tài khoản</div>
                            </div>
                        </div>
                    </div>

                    <div id="account_options" style="display: <?php echo (isset($_POST['target_type']) && $_POST['target_type'] === 'account') ? 'block' : 'none'; ?>;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="form-label">Chọn tài khoản</label>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleSelectAll('target_ids[]', 'select_all_accounts')">
                                <i class="bi bi-check2-all me-1"></i> Chọn tất cả
                            </button>
                            <input type="checkbox" class="d-none" id="select_all_accounts">
                        </div>
                        <div class="search-container">
                            <i class="bi bi-search search-icon"></i>
                            <input type="text" class="form-control search-input" id="account_search" placeholder="Tìm kiếm tài khoản..." oninput="debouncedSearchAccounts('account_options', 'account_search')">
                        </div>
                        <div class="user-list" id="account_list">
                            <?php if (empty($users)): ?>
                                <div class="empty-state">
                                    <i class="bi bi-people"></i>
                                    <p>Không có tài khoản nào</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <?php if ($user['account_id'] != $current_user_id): ?>
                                        <label class="list-group-item d-flex align-items-center" data-account-id="<?php echo htmlspecialchars($user['account_id']); ?>">
                                            <input class="form-check-input me-3" type="checkbox" name="target_ids[]" value="<?php echo htmlspecialchars($user['account_id']); ?>" <?php echo (isset($_POST['target_ids']) && in_array($user['account_id'], $_POST['target_ids'])) ? 'checked' : ''; ?>>
                                            <div>
                                                <div class="fw-medium"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                                    <span class="role-badge <?php
                                                                            echo $user['role'] === 'admin' ? 'badge-admin' : ($user['role'] === 'teacher' ? 'badge-teacher' : 'badge-student');
                                                                            ?>">
                                                        <?php echo htmlspecialchars($user['role'] === 'admin' ? 'Admin' : ($user['role'] === 'teacher' ? 'Giáo viên' : 'Học sinh')); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </label>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <div class="list-group-item text-muted text-center d-none" id="account_no_results">Không tìm thấy tài khoản</div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-send" onclick="confirmSendNotification()">
                        <i class="bi bi-send"></i> Gửi thông báo
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>



<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="notificationToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto">Thông báo</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body"></div>
    </div>
</div>

<script>
    const accounts = <?php
                        $filtered_users = array_filter($users, function ($user) use ($current_user_id) {
                            return isset($user['account_id'], $user['full_name'], $user['username'], $user['role']) &&
                                $user['account_id'] != $current_user_id;
                        });
                        echo json_encode($filtered_users, JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
                        ?>;

    // Debounce function to limit the frequency of search calls
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Search accounts function
    function searchAccounts(containerId, searchInputId) {
        const searchInputElement = document.getElementById(searchInputId);
        if (!searchInputElement) return;
        const searchInput = searchInputElement.value.toLowerCase().trim();
        const items = document.querySelectorAll(`#${containerId} .list-group-item[data-account-id]`);
        const noResults = document.getElementById(`${containerId.split('_')[0]}_no_results`);
        let hasVisibleItems = false;
        items.forEach(item => {
            const searchText = item.textContent.toLowerCase();
            const isVisible = searchText.includes(searchInput);
            item.style.display = isVisible ? '' : 'none';
            if (isVisible) hasVisibleItems = true;
        });
        if (noResults) {
            noResults.classList.toggle('d-none', hasVisibleItems || items.length === 0);
        }
    }

    // Debounced search function
    const debouncedSearchAccounts = debounce(searchAccounts, 300);

    function selectTarget(targetType) {
        document.querySelectorAll('.target-option').forEach(option => {
            option.classList.remove('active');
        });
        document.querySelector(`.target-option[data-target="${targetType}"]`).classList.add('active');
        document.getElementById('target_type').value = targetType;
        document.getElementById('role_options').style.display = targetType === 'role' ? 'block' : 'none';
        document.getElementById('account_options').style.display = targetType === 'account' ? 'block' : 'none';
        if (targetType === 'role') {
            toggleRoleOptions();
        } else {
            document.getElementById('admin_options').style.display = 'none';
            document.getElementById('teacher_options').style.display = 'none';
            document.getElementById('student_options').style.display = 'none';
        }
        debouncedSearchAccounts('admin_options', 'admin_search');
        debouncedSearchAccounts('teacher_options', 'teacher_search');
        debouncedSearchAccounts('student_options', 'student_search');
        debouncedSearchAccounts('account_options', 'account_search');
    }

    function toggleRoleOptions() {
        const role = document.getElementById('role')?.value;
        if (!role) return;
        document.getElementById('admin_options').style.display = role === 'admin' ? 'block' : 'none';
        document.getElementById('teacher_options').style.display = role === 'teacher' ? 'block' : 'none';
        document.getElementById('student_options').style.display = role === 'student' ? 'block' : 'none';
        document.getElementById('admin_search').value = '';
        document.getElementById('teacher_search').value = '';
        document.getElementById('student_search').value = '';
        debouncedSearchAccounts('admin_options', 'admin_search');
        debouncedSearchAccounts('teacher_options', 'teacher_search');
        debouncedSearchAccounts('student_options', 'student_search');
    }

    function toggleSelectAll(name, checkboxId) {
        const checkbox = document.getElementById(checkboxId);
        checkbox.checked = !checkbox.checked;
        const checkboxes = document.getElementsByName(name);
        for (const cb of checkboxes) {
            if (cb.parentElement.style.display !== 'none') {
                cb.checked = checkbox.checked;
            }
        }
    }

    function updateCharCount() {
        const message = document.getElementById('message')?.value || '';
        const charCount = document.getElementById('char_count');
        const remaining = 500 - message.length;
        if (charCount) {
            charCount.textContent = `${remaining} ký tự còn lại`;
            charCount.classList.toggle('warning', remaining < 50);
        }
    }

    function confirmSendNotification() {
        const message = document.getElementById('message')?.value.trim();
        if (!message) {
            showToast('Vui lòng nhập nội dung thông báo!');
            return;
        }
        const targetType = document.getElementById('target_type')?.value;
        if (targetType === 'role') {
            const role = document.getElementById('role')?.value;
            if (!role) {
                showToast('Vui lòng chọn vai trò!');
                return;
            }
        }
        if (confirm('Bạn có chắc chắn muốn gửi thông báo này?')) {
            document.getElementById('notificationForm')?.submit();
        }
    }

    function showToast(message) {
        const toastEl = document.getElementById('notificationToast');
        if (!toastEl) return;
        toastEl.querySelector('.toast-body').textContent = message;
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
    }

    document.addEventListener('DOMContentLoaded', () => {
        const targetTypeElement = document.getElementById('target_type');
        if (targetTypeElement) {
            selectTarget(targetTypeElement.value || 'all');
        }
        debouncedSearchAccounts('admin_options', 'admin_search');
        debouncedSearchAccounts('teacher_options', 'teacher_search');
        debouncedSearchAccounts('student_options', 'student_search');
        debouncedSearchAccounts('account_options', 'account_search');
        updateCharCount();
    });
</script>
<?php ob_end_flush(); ?>