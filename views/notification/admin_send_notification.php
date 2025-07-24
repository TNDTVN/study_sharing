<?php

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

<div class="container py-4">
    <div class="row">
        <!-- Cột chính -->
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white border-bottom d-flex align-items-center">
                    <h4 class="mb-0 text-primary fw-semibold"><i class="bi bi-send-fill me-2"></i> Gửi thông báo đến người dùng</h4>
                </div>

                <div class="card-body">
                    <!-- Hiển thị thông báo kết quả -->
                    <?php if ($response): ?>
                        <div class="alert alert-dismissible fade show <?php echo $response['status'] ? 'alert-success' : 'alert-danger'; ?>" role="alert">
                            <h4 class="alert-heading"><i class="bi <?php echo $response['status'] ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?> me-2"></i>
                                <?php echo $response['status'] ? 'Thành công' : 'Lỗi'; ?>
                            </h4>
                            <p><?php echo htmlspecialchars($response['message']); ?></p>
                            <?php if (isset($response['results']) && !empty($response['results'])): ?>
                                <hr>
                                <div class="mt-3">
                                    <h5>Chi tiết gửi thông báo:</h5>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover table-bordered">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th scope="col">ID tài khoản</th>
                                                    <th scope="col">Trạng thái</th>
                                                    <th scope="col">Thông báo</th>
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
                        <div class="mb-3">
                            <label for="message" class="form-label fw-bold">Nội dung thông báo <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="message" name="message" rows="4" maxlength="500" placeholder="Nhập nội dung thông báo..." required oninput="updateCharCount()"><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">Còn <span id="char_count">500</span>/500 ký tự</small>
                        </div>

                        <div class="mb-3">
                            <label for="target_type" class="form-label fw-bold">Gửi đến <span class="text-danger">*</span></label>
                            <select class="form-select" id="target_type" name="target_type" onchange="toggleTargetOptions()">
                                <option value="all" <?php echo (!isset($_POST['target_type']) || $_POST['target_type'] === 'all') ? 'selected' : ''; ?>>Tất cả người dùng</option>
                                <option value="role" <?php echo (isset($_POST['target_type']) && $_POST['target_type'] === 'role') ? 'selected' : ''; ?>>Theo vai trò</option>
                                <option value="account" <?php echo (isset($_POST['target_type']) && $_POST['target_type'] === 'account') ? 'selected' : ''; ?>>Theo tài khoản</option>
                            </select>
                        </div>

                        <!-- Tùy chọn theo vai trò -->
                        <div class="mb-3" id="role_options" style="display: <?php echo (isset($_POST['target_type']) && $_POST['target_type'] === 'role') ? 'block' : 'none'; ?>;">
                            <label for="role" class="form-label fw-bold">Chọn vai trò</label>
                            <select class="form-select" id="role" name="role" onchange="toggleRoleOptions()">
                                <option value="" <?php echo !isset($_POST['role']) ? 'selected' : ''; ?>>Chọn vai trò</option>
                                <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                <option value="teacher" <?php echo (isset($_POST['role']) && $_POST['role'] === 'teacher') ? 'selected' : ''; ?>>Giáo viên</option>
                                <option value="student" <?php echo (isset($_POST['role']) && $_POST['role'] === 'student') ? 'selected' : ''; ?>>Học sinh</option>
                            </select>

                            <!-- Danh sách tài khoản admin -->
                            <div class="mb-3 mt-3" id="admin_options" style="display: <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'block' : 'none'; ?>;">
                                <label class="form-label fw-bold">Chọn tài khoản Admin</label>
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" id="admin_search" placeholder="Tìm kiếm admin (tên hoặc username)..." oninput="debouncedSearchAccounts('admin_list', 'admin_search')">
                                    <button type="button" class="btn btn-outline-secondary" onclick="debouncedSearchAccounts('admin_list', 'admin_search')"><i class="bi bi-search"></i></button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="clearSearch('admin_search', () => debouncedSearchAccounts('admin_list', 'admin_search'))"><i class="bi bi-x"></i></button>
                                </div>
                                <div class="list-group shadow-sm" style="max-height: 200px; overflow-y: auto;" id="admin_list">
                                    <?php if (empty($admins)): ?>
                                        <div class="list-group-item text-muted text-center">Không có admin nào</div>
                                    <?php else: ?>
                                        <label class="list-group-item d-flex align-items-center">
                                            <input class="form-check-input me-2" type="checkbox" id="select_all_admins" onclick="toggleSelectAll('admin_ids[]', this)">
                                            <span class="fw-bold">Chọn tất cả Admin</span>
                                        </label>
                                        <?php foreach ($admins as $user): ?>
                                            <?php if ($user['account_id'] != $current_user_id): ?>
                                                <label class="list-group-item d-flex align-items-center" data-account-id="<?php echo htmlspecialchars($user['account_id']); ?>">
                                                    <input class="form-check-input me-2" type="checkbox" name="admin_ids[]" value="<?php echo htmlspecialchars($user['account_id']); ?>"
                                                        <?php echo (isset($_POST['admin_ids']) && in_array($user['account_id'], $_POST['admin_ids'])) ? 'checked' : ''; ?>>
                                                    <span data-search="<?php echo htmlspecialchars(strtolower($user['full_name'] . ' ' . $user['username'])); ?>">
                                                        <?php echo htmlspecialchars($user['full_name'] . ' (' . $user['username'] . ')'); ?>
                                                    </span>
                                                </label>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <div class="list-group-item text-muted text-center d-none" id="admin_no_results">Không tìm thấy tài khoản phù hợp</div>
                                </div>
                            </div>

                            <!-- Danh sách tài khoản teacher -->
                            <div class="mb-3 mt-3" id="teacher_options" style="display: <?php echo (isset($_POST['role']) && $_POST['role'] === 'teacher') ? 'block' : 'none'; ?>;">
                                <label class="form-label fw-bold">Chọn tài khoản Giáo viên</label>
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" id="teacher_search" placeholder="Tìm kiếm giáo viên (tên hoặc username)..." oninput="debouncedSearchAccounts('teacher_list', 'teacher_search')">
                                    <button type="button" class="btn btn-outline-secondary" onclick="debouncedSearchAccounts('teacher_list', 'teacher_search')"><i class="bi bi-search"></i></button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="clearSearch('teacher_search', () => debouncedSearchAccounts('teacher_list', 'teacher_search'))"><i class="bi bi-x"></i></button>
                                </div>
                                <div class="list-group shadow-sm" style="max-height: 200px; overflow-y: auto;" id="teacher_list">
                                    <?php if (empty($teachers)): ?>
                                        <div class="list-group-item text-muted text-center">Không có giáo viên nào</div>
                                    <?php else: ?>
                                        <label class="list-group-item d-flex align-items-center">
                                            <input class="form-check-input me-2" type="checkbox" id="select_all_teachers" onclick="toggleSelectAll('teacher_ids[]', this)">
                                            <span class="fw-bold">Chọn tất cả Giáo viên</span>
                                        </label>
                                        <?php foreach ($teachers as $user): ?>
                                            <?php if ($user['account_id'] != $current_user_id): ?>
                                                <label class="list-group-item d-flex align-items-center" data-account-id="<?php echo htmlspecialchars($user['account_id']); ?>">
                                                    <input class="form-check-input me-2" type="checkbox" name="teacher_ids[]" value="<?php echo htmlspecialchars($user['account_id']); ?>"
                                                        <?php echo (isset($_POST['teacher_ids']) && in_array($user['account_id'], $_POST['teacher_ids'])) ? 'checked' : ''; ?>>
                                                    <span data-search="<?php echo htmlspecialchars(strtolower($user['full_name'] . ' ' . $user['username'])); ?>">
                                                        <?php echo htmlspecialchars($user['full_name'] . ' (' . $user['username'] . ')'); ?>
                                                    </span>
                                                </label>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <div class="list-group-item text-muted text-center d-none" id="teacher_no_results">Không tìm thấy tài khoản phù hợp</div>
                                </div>
                            </div>

                            <!-- Danh sách tài khoản student -->
                            <div class="mb-3 mt-3" id="student_options" style="display: <?php echo (isset($_POST['role']) && $_POST['role'] === 'student') ? 'block' : 'none'; ?>;">
                                <label class="form-label fw-bold">Chọn tài khoản Học sinh</label>
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" id="student_search" placeholder="Tìm kiếm học sinh (tên hoặc username)..." oninput="debouncedSearchAccounts('student_list', 'student_search')">
                                    <button type="button" class="btn btn-outline-secondary" onclick="debouncedSearchAccounts('student_list', 'student_search')"><i class="bi bi-search"></i></button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="clearSearch('student_search', () => debouncedSearchAccounts('student_list', 'student_search'))"><i class="bi bi-x"></i></button>
                                </div>
                                <div class="list-group shadow-sm" style="max-height: 200px; overflow-y: auto;" id="student_list">
                                    <?php if (empty($students)): ?>
                                        <div class="list-group-item text-muted text-center">Không có học sinh nào</div>
                                    <?php else: ?>
                                        <label class="list-group-item d-flex align-items-center">
                                            <input class="form-check-input me-2" type="checkbox" id="select_all_students" onclick="toggleSelectAll('student_ids[]', this)">
                                            <span class="fw-bold">Chọn tất cả Học sinh</span>
                                        </label>
                                        <?php foreach ($students as $user): ?>
                                            <?php if ($user['account_id'] != $current_user_id): ?>
                                                <label class="list-group-item d-flex align-items-center" data-account-id="<?php echo htmlspecialchars($user['account_id']); ?>">
                                                    <input class="form-check-input me-2" type="checkbox" name="student_ids[]" value="<?php echo htmlspecialchars($user['account_id']); ?>"
                                                        <?php echo (isset($_POST['student_ids']) && in_array($user['account_id'], $_POST['student_ids'])) ? 'checked' : ''; ?>>
                                                    <span data-search="<?php echo htmlspecialchars(strtolower($user['full_name'] . ' ' . $user['username'])); ?>">
                                                        <?php echo htmlspecialchars($user['full_name'] . ' (' . $user['username'] . ')'); ?>
                                                    </span>
                                                </label>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <div class="list-group-item text-muted text-center d-none" id="student_no_results">Không tìm thấy tài khoản phù hợp</div>
                                </div>
                            </div>
                        </div>

                        <!-- Tùy chọn theo tài khoản -->
                        <div class="mb-3" id="account_options" style="display: <?php echo (isset($_POST['target_type']) && $_POST['target_type'] === 'account') ? 'block' : 'none'; ?>;">
                            <label class="form-label fw-bold">Chọn tài khoản</label>
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" id="account_search" placeholder="Tìm kiếm tài khoản (tên hoặc username)..." oninput="debouncedSearchAccounts('account_list', 'account_search')">
                                <button type="button" class="btn btn-outline-secondary" onclick="debouncedSearchAccounts('account_list', 'account_search')"><i class="bi bi-search"></i></button>
                                <button type="button" class="btn btn-outline-secondary" onclick="clearSearch('account_search', () => debouncedSearchAccounts('account_list', 'account_search'))"><i class="bi bi-x"></i></button>
                            </div>
                            <div class="list-group shadow-sm" style="max-height: 200px; overflow-y: auto;" id="account_list">
                                <?php if (empty($users)): ?>
                                    <div class="list-group-item text-muted text-center">Không có tài khoản nào</div>
                                <?php else: ?>
                                    <label class="list-group-item d-flex align-items-center">
                                        <input class="form-check-input me-2" type="checkbox" id="select_all_accounts" onclick="toggleSelectAll('target_ids[]', this)">
                                        <span class="fw-bold">Chọn tất cả tài khoản</span>
                                    </label>
                                    <?php foreach ($users as $user): ?>
                                        <?php if ($user['account_id'] != $current_user_id): ?>
                                            <label class="list-group-item d-flex align-items-center" data-account-id="<?php echo htmlspecialchars($user['account_id']); ?>">
                                                <input class="form-check-input me-2" type="checkbox" name="target_ids[]" value="<?php echo htmlspecialchars($user['account_id']); ?>"
                                                    <?php echo (isset($_POST['target_ids']) && in_array($user['account_id'], $_POST['target_ids'])) ? 'checked' : ''; ?>>
                                                <span data-search="<?php echo htmlspecialchars(strtolower($user['full_name'] . ' ' . $user['username'])); ?>">
                                                    <?php echo htmlspecialchars($user['full_name'] . ' (' . $user['username'] . ')'); ?>
                                                </span>
                                            </label>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <div class="list-group-item text-muted text-center d-none" id="account_no_results">Không tìm thấy tài khoản phù hợp</div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary" onclick="confirmSendNotification()"><i class="bi bi-send me-2"></i>Gửi thông báo</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="notificationToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto">Thông báo</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body"></div>
        </div>
    </div>
</div>

<!-- Script để xử lý giao diện -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Debounce function để giảm tần suất gọi hàm tìm kiếm
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

    function toggleTargetOptions() {
        const targetType = document.getElementById('target_type')?.value;
        if (!targetType) return;
        document.getElementById('role_options').style.display = targetType === 'role' ? 'block' : 'none';
        document.getElementById('account_options').style.display = targetType === 'account' ? 'block' : 'none';
        if (targetType === 'role') {
            toggleRoleOptions();
        } else {
            document.getElementById('admin_options').style.display = 'none';
            document.getElementById('teacher_options').style.display = 'none';
            document.getElementById('student_options').style.display = 'none';
        }
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
    }

    function toggleSelectAll(name, checkbox) {
        const checkboxes = document.getElementsByName(name);
        for (const cb of checkboxes) {
            if (cb.parentElement.style.display !== 'none') {
                cb.checked = checkbox.checked;
            }
        }
    }

    function searchAccounts(containerId, searchInputId) {
        const searchInputElement = document.getElementById(searchInputId);
        if (!searchInputElement) return;
        const searchInput = searchInputElement.value.toLowerCase().trim();
        const items = document.querySelectorAll(`#${containerId} .list-group-item[data-account-id]`);
        const noResults = document.getElementById(`${containerId.split('_')[0]}_no_results`);
        let hasVisibleItems = false;

        items.forEach(item => {
            const searchText = item.querySelector('[data-search]')?.getAttribute('data-search') || '';
            const isVisible = searchText.includes(searchInput);
            item.style.display = isVisible ? '' : 'none';
            if (isVisible) hasVisibleItems = true;
        });

        if (noResults) {
            noResults.classList.toggle('d-none', hasVisibleItems || items.length === 0);
        }
        const selectAllCheckbox = document.getElementById(`select_all_${containerId.split('_')[0]}`);
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
        }
    }

    const debouncedSearchAccounts = debounce(searchAccounts, 300);

    function clearSearch(inputId, callback) {
        const input = document.getElementById(inputId);
        if (input) {
            input.value = '';
            callback();
        }
    }

    function updateCharCount() {
        const message = document.getElementById('message')?.value || '';
        const charCount = document.getElementById('char_count');
        if (charCount) {
            charCount.textContent = 500 - message.length;
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
        const toast = document.getElementById('notificationToast');
        if (!toast) return;
        toast.querySelector('.toast-body').textContent = message;
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
    }

    document.addEventListener('DOMContentLoaded', () => {
        toggleTargetOptions();
        updateCharCount();
    });
</script>

<style>
    .container.py-5 {
        padding: 0 !important;
        margin-bottom: 0rem;
    }

    .list-group-item {
        transition: background-color 0.2s ease;
    }

    .list-group-item:hover {
        background-color: #e9ecef;
    }

    .table-responsive {
        max-height: 300px;
        overflow-y: auto;
    }

    .table thead.sticky-top {
        position: sticky;
        top: 0;
        z-index: 1;
        background-color: #fff;
    }

    .list-group-item.text-muted {
        font-style: italic;
    }

    .input-group .btn-outline-secondary {
        padding: 0 12px;
        transition: background-color 0.2s ease;
    }

    .input-group .btn-outline-secondary:hover {
        background-color: #e9ecef;
    }

    .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
    }

    .char-count-warning {
        color: #dc3545;
    }
</style>