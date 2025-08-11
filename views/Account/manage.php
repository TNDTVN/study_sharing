<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>


<style>
    .content {
        padding-top: 0px;
    }

    .status-active {
        color: green;
    }

    .status-inactive {
        color: orange;
    }

    .status-banned {
        color: red;
    }

    .container.py-5 {
        padding-top: 0 !important;
        padding-bottom: 0 !important;
    }
</style>
<div class="container mt-4">
    <h2 class="mb-4 text-primary"><?php echo htmlspecialchars($title); ?></h2>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo htmlspecialchars($_SESSION['message_type']); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <!-- Tìm kiếm và nút thêm người dùng trên cùng một hàng -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <form method="GET" action="/study_sharing/Account/manage" class="w-50">
            <div class="input-group">
                <input type="text" name="keyword" class="form-control" placeholder="Tìm kiếm theo tên, email hoặc tên đầy đủ" value="<?php echo htmlspecialchars($keyword); ?>">
                <button type="submit" class="btn btn-primary">Tìm kiếm</button>
            </div>
        </form>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="bi bi-plus-circle"></i>
            Thêm người dùng
        </button>
    </div>

    <!-- Bảng danh sách người dùng -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên đăng nhập</th>
                    <th>Email</th>
                    <th>Họ tên</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['account_id']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td>
                                <span class="status-<?php echo htmlspecialchars($user['status']); ?>">
                                    <?php if ($user['status'] === 'active'): ?>
                                        <i class="fa fa-check-circle"></i> Hoạt động
                                    <?php elseif ($user['status'] === 'inactive'): ?>
                                        <i class="fa fa-pause-circle"></i> Không hoạt động
                                    <?php else: ?>
                                        <i class="fa fa-ban"></i> Bị khóa
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons d-flex gap-1">
                                    <button type="button" class="btn btn-outline-info btn-sm" title="Xem"
                                        onclick='showUserDetails(<?php echo json_encode($user); ?>)'>
                                        <i class="fa fa-eye"></i>
                                    </button>

                                    <button type="button" class="btn btn-outline-warning btn-sm" title="Sửa"
                                        data-bs-toggle="modal" data-bs-target="#editUserModal"
                                        onclick="fillEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                        <i class="fa fa-edit"></i>
                                    </button>

                                    <?php if ($user['role'] === 'teacher' || $user['role'] === 'student'): ?>
                                        <button type="button" class="btn btn-outline-danger btn-sm"
                                            title="<?php echo $user['status'] === 'banned' ? 'Mở khóa' : 'Khóa'; ?>"
                                            onclick="lockUser(<?php echo $user['account_id']; ?>, '<?php echo $user['status'] === 'banned' ? 'active' : 'banned'; ?>')">
                                            <i class="fa <?php echo $user['status'] === 'banned' ? 'fa-unlock' : 'fa-lock'; ?>"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">Không có người dùng nào.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!--hah-->
    <!-- Phân trang -->
    <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation" class="mt-3">
            <ul class="pagination justify-content-center mb-0">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="/study_sharing/Account/manage?page=<?php echo $page - 1; ?>&keyword=<?php echo urlencode($keyword); ?>">Trước</a>
                    </li>
                <?php endif; ?>
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                if ($endPage - $startPage < 4) {
                    $startPage = max(1, $endPage - 4);
                }
                for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="/study_sharing/Account/manage?page=<?php echo $i; ?>&keyword=<?php echo urlencode($keyword); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="/study_sharing/Account/manage?page=<?php echo $page + 1; ?>&keyword=<?php echo urlencode($keyword); ?>">Sau</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/study_sharing/Account/addUser">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel"><i class="fas fa-user-plus me-2"></i>Thêm người dùng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 input-icon">
                        <label for="username" class="form-label">Tên đăng nhập</label>
                        <i class="fas fa-user"></i>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3 input-icon">
                        <label for="email" class="form-label">Email</label>
                        <i class="fas fa-envelope"></i>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3 input-icon">
                        <label for="password" class="form-label">Mật khẩu</label>
                        <i class="fas fa-lock"></i>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3 input-icon">
                        <label for="full_name" class="form-label">Họ tên</label>
                        <i class="fas fa-address-card"></i>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    <div class="mb-3 input-icon">
                        <label for="role" class="form-label">Vai trò</label>
                        <i class="fas fa-user-tag"></i>
                        <select class="form-select" id="role" name="role">
                            <option value="student">Học sinh</option>
                            <option value="teacher">Giáo viên</option>
                            <option value="admin">Quản trị viên</option>
                        </select>
                    </div>
                    <div class="mb-3 input-icon">
                        <label for="phone_number" class="form-label">Số điện thoại</label>
                        <i class="fas fa-phone"></i>
                        <input type="text" class="form-control" id="phone_number" name="phone_number">
                    </div>
                    <div class="mb-3 input-icon">
                        <label for="address" class="form-label">Địa chỉ</label>
                        <i class="fas fa-map-marker-alt"></i>
                        <input type="text" class="form-control" id="address" name="address">
                    </div>
                    <div class="mb-3 input-icon">
                        <label for="date_of_birth" class="form-label">Ngày sinh</label>
                        <i class="fas fa-calendar-alt"></i>
                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-2"></i>Đóng</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Thêm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/study_sharing/Account/updateUser">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel"><i class="fas fa-user-edit me-2"></i>Sửa người dùng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_account_id" name="account_id">
                    <div class="mb-3 input-icon">
                        <label for="edit_username" class="form-label">Tên đăng nhập</label>
                        <i class="fas fa-user"></i>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                    </div>
                    <div class="mb-3 input-icon">
                        <label for="edit_email" class="form-label">Email</label>
                        <i class="fas fa-envelope"></i>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    <div class="mb-3 input-icon">
                        <label for="edit_full_name" class="form-label">Họ tên</label>
                        <i class="fas fa-address-card"></i>
                        <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                    </div>
                    <div class="mb-3 input-icon">
                        <label for="edit_role" class="form-label">Vai trò</label>
                        <i class="fas fa-user-tag"></i>
                        <select class="form-select" id="edit_role" name="role">
                            <option value="student">Học sinh</option>
                            <option value="teacher">Giáo viên</option>
                            <option value="admin">Quản trị viên</option>
                        </select>
                    </div>
                    <div class="mb-3 input-icon">
                        <label for="edit_phone_number" class="form-label">Số điện thoại</label>
                        <i class="fas fa-phone"></i>
                        <input type="text" class="form-control" id="edit_phone_number" name="phone_number">
                    </div>
                    <div class="mb-3 input-icon">
                        <label for="edit_address" class="form-label">Địa chỉ</label>
                        <i class="fas fa-map-marker-alt"></i>
                        <input type="text" class="form-control" id="edit_address" name="address">
                    </div>
                    <div class="mb-3 input-icon">
                        <label for="edit_date_of_birth" class="form-label">Ngày sinh</label>
                        <i class="fas fa-calendar-alt"></i>
                        <input type="date" class="form-control" id="edit_date_of_birth" name="date_of_birth">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-2"></i>Đóng</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View User Modal -->
<div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewUserModalLabel"><i class="fas fa-user me-2"></i>Thông tin người dùng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <p><strong><i class="fas fa-id-badge me-2"></i>ID:</strong> <span id="view_account_id"></span></p>
                <p><strong><i class="fas fa-user me-2"></i>Tên đăng nhập:</strong> <span id="view_username"></span></p>
                <p><strong><i class="fas fa-envelope me-2"></i>Email:</strong> <span id="view_email"></span></p>
                <p><strong><i class="fas fa-address-card me-2"></i>Họ tên:</strong> <span id="view_full_name"></span></p>
                <p><strong><i class="fas fa-user-tag me-2"></i>Vai trò:</strong> <span id="view_role"></span></p>
                <p><strong><i class="fas fa-phone me-2"></i>Số điện thoại:</strong> <span id="view_phone_number"></span></p>
                <p><strong><i class="fas fa-map-marker-alt me-2"></i>Địa chỉ:</strong> <span id="view_address"></span></p>
                <p><strong><i class="fas fa-calendar-alt me-2"></i>Ngày sinh:</strong> <span id="view_date_of_birth"></span></p>
                <p><strong><i class="fas fa-info-circle me-2"></i>Trạng thái:</strong> <span id="view_status"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-2"></i>Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
</div>


<script>
    function fillEditModal(user) {
        document.getElementById('edit_account_id').value = user.account_id;
        document.getElementById('edit_username').value = user.username;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_full_name').value = user.full_name || '';
        document.getElementById('edit_role').value = user.role;
        document.getElementById('edit_phone_number').value = user.phone_number || '';
        document.getElementById('edit_address').value = user.address || '';
        document.getElementById('edit_date_of_birth').value = user.date_of_birth || '';
    }

    function showUserDetails(user) {
        document.getElementById('view_account_id').textContent = user.account_id;
        document.getElementById('view_username').textContent = user.username;
        document.getElementById('view_email').textContent = user.email;
        document.getElementById('view_full_name').textContent = user.full_name || '';
        document.getElementById('view_role').textContent = user.role;
        document.getElementById('view_phone_number').textContent = user.phone_number || '';
        document.getElementById('view_address').textContent = user.address || '';
        document.getElementById('view_date_of_birth').textContent = user.date_of_birth || '';
        document.getElementById('view_status').textContent = user.status;

        var viewModal = new bootstrap.Modal(document.getElementById('viewUserModal'));
        viewModal.show();
    }


    function lockUser(accountId, status) {
        if (confirm('Bạn có chắc chắn muốn ' + (status === 'banned' ? 'khóa' : 'mở khóa') + ' tài khoản này?')) {
            fetch('/study_sharing/Account/lockUser', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'account_id=' + accountId + '&status=' + status + '&_token=' + '<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        window.location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    alert('Lỗi server: ' + error.message);
                });
        }
    }
</script>
<style>
    /* General container styling */
    .container.mt-4 {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 1.5rem;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .content {
        padding-top: 0;
    }

    /* Status styling */
    .status-active {
        color: #28a745;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
    }

    .status-inactive {
        color: #ffc107;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
    }

    .status-banned {
        color: #dc3545;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
    }


    /* Action buttons */
    .action-buttons .btn {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        transition: all 0.3s ease;
        border-radius: 5px;
    }

    .action-buttons .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
    }

    /* Modal styling */
    .modal-content {
        border: none;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
        animation: slideIn 0.3s ease-out;
    }

    .modal-header {
        background: linear-gradient(90deg, #007bff, #0056b3);
        color: #fff;
        border-bottom: none;
        padding: 1.5rem;
    }

    .modal-title {
        font-size: 1.5rem;
        font-weight: 600;
    }

    .modal-body {
        padding: 2rem;
        background: #f9f9f9;
    }

    .modal-footer {
        border-top: none;
        padding: 1rem 2rem;
        background: #fff;
    }

    /* Input group styling */
    .input-icon {
        position: relative;
    }

    .input-icon i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
        font-size: 1.1rem;
    }

    .input-icon .form-control,
    .input-icon .form-select {
        padding-left: 2.5rem;
        border-radius: 8px;
        border: 1px solid #ced4da;
        transition: border-color 0.3s, box-shadow 0.3s;
    }

    .input-icon .form-control:focus,
    .input-icon .form-select:focus {
        border-color: #007bff;
        box-shadow: 0 0 8px rgba(0, 123, 255, 0.3);
    }

    /* View modal specific styling */
    #viewUserModal .modal-body p {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 1.2rem;
        font-size: 1rem;
        color: #333;
    }

    #viewUserModal .modal-body strong {
        font-weight: 600;
        color: #007bff;
        min-width: 120px;
    }

    #viewUserModal .modal-body span {
        color: #495057;
    }

    /* Edit modal specific styling */
    #editUserModal .form-label {
        font-weight: 500;
        color: #333;
    }

    /* Button styling */
    .btn-primary {
        background: #007bff;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        transition: background 0.3s, transform 0.2s;
    }

    .btn-primary:hover {
        background: #0056b3;
        transform: translateY(-2px);
    }

    .btn-secondary {
        background: #6c757d;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        transition: background 0.3s, transform 0.2s;
    }

    .btn-secondary:hover {
        background: #5a6268;
        transform: translateY(-2px);
    }

    /* Animation for modal */
    @keyframes slideIn {
        from {
            transform: translateY(-50px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    /* Loading overlay */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }

    .loading-spinner {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #007bff;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .container.mt-4 {
            padding: 1rem;
        }

        .d-flex.justify-content-between {
            flex-direction: column;
            gap: 1rem;
        }

        .d-flex.justify-content-between form {
            width: 100%;
        }

        .modal-dialog {
            margin: 1rem;
        }
    }
</style>