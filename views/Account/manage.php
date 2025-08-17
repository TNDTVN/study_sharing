<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<style>
    .content {
        padding-top: 0;
    }

    .status-active {
        color: green;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
    }

    .status-inactive {
        color: orange;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
    }

    .status-banned {
        color: red;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
    }

    .container.py-5 {
        padding-top: auto;
        padding-bottom: 0 !important;
    }

    .modal-content {
        border-radius: 15px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        background: #fff;
    }

    .modal-header {
        background: linear-gradient(90deg, #007bff, #0056b3);
        color: white;
        border-bottom: none;
        padding: 1.5rem 2rem;
        display: flex;
        align-items: center;
    }

    .modal-title {
        font-size: 1.5rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .modal-body {
        padding: 2rem;
        background: #f8f9fa;
    }

    .modal-footer {
        border-top: none;
        padding: 1rem 2rem;
        background: #f8f9fa;
    }

    .input-icon .form-control,
    .input-icon .form-select {
        border-radius: 8px;
        border: 1px solid #ced4da;
        padding: 10px;
    }

    .input-icon .form-label {
        font-weight: 600;
        color: #343a40;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
    }

    .avatar-preview {
        width: 120px;
        height: 120px;
        object-fit: cover;
        border-radius: 50%;
        border: 3px solid #e9ecef;
        margin-bottom: 1rem;
    }

    .custom-file-upload {
        display: inline-block;
        padding: 8px 16px;
        background: #007bff;
        color: white;
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.3s;
    }

    .custom-file-upload:hover {
        background: #0056b3;
    }

    .btn-primary,
    .btn-success,
    .btn-secondary {
        border-radius: 8px;
        padding: 10px 20px;
        transition: all 0.3s ease;
    }

    .btn-primary:hover,
    .btn-success:hover,
    .btn-secondary:hover {
        transform: translateY(-2px);
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
    }

    .table th,
    .table td {
        vertical-align: middle;
    }

    #viewUserModal .modal-body {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
    }

    #viewUserModal .info-item {
        flex: 1 1 45%;
        background: #fff;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    #viewUserModal .info-item strong {
        font-weight: 600;
        color: #007bff;
        min-width: 120px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    #viewUserModal .info-item span {
        color: #495057;
    }

    #viewUserModal .avatar-container {
        flex: 1 1 100%;
        text-align: center;
        margin-bottom: 1.5rem;
    }

    #viewUserModal .avatar-container img {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 50%;
        border: 3px solid #e9ecef;
    }

    @media (max-width: 768px) {
        #viewUserModal .info-item {
            flex: 1 1 100%;
        }

        #viewUserModal .avatar-container img {
            width: 100px;
            height: 100px;
        }

        .modal-dialog {
            margin: 1rem;
        }

        .d-flex.justify-content-between {
            flex-direction: column;
            gap: 1rem;
        }

        .d-flex.justify-content-between form {
            width: 100%;
        }
    }
</style>

<h1 class="mb-4 text-primary"><?php echo htmlspecialchars($title); ?></h1>

<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-<?php echo htmlspecialchars($_SESSION['message_type']); ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_SESSION['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <form method="GET" action="/study_sharing/Account/manage" class="w-50">
        <div class="input-group">
            <input type="text" name="keyword" class="form-control" placeholder="Tìm kiếm theo tên, email hoặc họ tên" value="<?php echo htmlspecialchars($keyword); ?>">
            <button type="submit" class="btn btn-primary">Tìm kiếm</button>
        </div>
    </form>
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="bi bi-plus-circle"></i> Thêm người dùng</button>
</div>

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
                                <button type="button" class="btn btn-outline-info btn-sm" title="Xem" onclick='showUserDetails(<?php echo json_encode($user); ?>)'>
                                    <i class="fa fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm" title="Sửa" data-bs-toggle="modal" data-bs-target="#editUserModal" onclick="fillEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <?php if ($user['role'] === 'teacher' || $user['role'] === 'student'): ?>
                                    <button type="button" class="btn btn-outline-danger btn-sm" title="<?php echo $user['status'] === 'banned' ? 'Mở khóa' : 'Khóa'; ?>" onclick="lockUser(<?php echo $user['account_id']; ?>, '<?php echo $user['status'] === 'banned' ? 'active' : 'banned'; ?>')">
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

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="/study_sharing/Account/addUser" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel"><i class="fas fa-user-plus"></i> Thêm người dùng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 text-center mb-4">
                            <img src="/study_sharing/assets/images/profile.png" alt="Avatar Preview" class="avatar-preview" id="addAvatarPreview">
                            <label for="add_avatar" class="form-label d-block fw-bold mb-2"><i class="fas fa-image"></i> Ảnh đại diện</label>
                            <input type="file" class="form-control d-none" id="add_avatar" name="avatar" accept="image/*">
                            <label for="add_avatar" class="custom-file-upload"><i class="bi bi-upload me-2"></i> Chọn ảnh</label>
                            <div class="form-text mt-2">Chấp nhận file JPEG, PNG, GIF. Tối đa 5MB.</div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3 input-icon">
                                <label for="username" class="form-label"><i class="fas fa-user"></i> Tên đăng nhập</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3 input-icon">
                                <label for="email" class="form-label"><i class="fas fa-envelope"></i> Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3 input-icon">
                                <label for="password" class="form-label"><i class="fas fa-lock"></i> Mật khẩu</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3 input-icon">
                                <label for="full_name" class="form-label"><i class="fas fa-address-card"></i> Họ tên</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3 input-icon">
                                <label for="role" class="form-label"><i class="fas fa-user-tag"></i> Vai trò</label>
                                <select class="form-select" id="role" name="role">
                                    <option value="student">Học sinh</option>
                                    <option value="teacher">Giáo viên</option>
                                    <option value="admin">Quản trị viên</option>
                                </select>
                            </div>
                            <div class="mb-3 input-icon">
                                <label for="phone_number" class="form-label"><i class="fas fa-phone"></i> Số điện thoại</label>
                                <input type="text" class="form-control" id="phone_number" name="phone_number">
                            </div>
                            <div class="mb-3 input-icon">
                                <label for="address" class="form-label"><i class="fas fa-map-marker-alt"></i> Địa chỉ</label>
                                <input type="text" class="form-control" id="address" name="address">
                            </div>
                            <div class="mb-3 input-icon">
                                <label for="date_of_birth" class="form-label"><i class="fas fa-calendar-alt"></i> Ngày sinh</label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth">
                            </div>
                        </div>
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="/study_sharing/Account/updateUser" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel"><i class="fas fa-user-edit"></i> Sửa người dùng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_account_id" name="account_id">
                    <input type="hidden" id="edit_current_avatar" name="current_avatar">
                    <div class="row">
                        <div class="col-md-12 text-center mb-4">
                            <img src="/study_sharing/assets/images/profile.png" alt="Avatar Preview" class="avatar-preview" id="editAvatarPreview">
                            <label for="edit_avatar" class="form-label d-block fw-bold mb-2"><i class="fas fa-image"></i> Ảnh đại diện</label>
                            <input type="file" class="form-control d-none" id="edit_avatar" name="avatar" accept="image/*">
                            <label for="edit_avatar" class="custom-file-upload"><i class="bi bi-upload me-2"></i> Chọn ảnh</label>
                            <div class="form-text mt-2">Chấp nhận file JPEG, PNG, GIF. Tối đa 5MB.</div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3 input-icon">
                                <label for="edit_username" class="form-label"><i class="fas fa-user"></i> Tên đăng nhập</label>
                                <input type="text" class="form-control" id="edit_username" name="username" required>
                            </div>
                            <div class="mb-3 input-icon">
                                <label for="edit_email" class="form-label"><i class="fas fa-envelope"></i> Email</label>
                                <input type="email" class="form-control" id="edit_email" name="email" required>
                            </div>
                            <div class="mb-3 input-icon">
                                <label for="edit_password" class="form-label"><i class="fas fa-lock"></i> Mật khẩu mới (để trống nếu không đổi)</label>
                                <input type="password" class="form-control" id="edit_password" name="password">
                            </div>
                            <div class="mb-3 input-icon">
                                <label for="edit_full_name" class="form-label"><i class="fas fa-address-card"></i> Họ tên</label>
                                <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3 input-icon">
                                <label for="edit_role" class="form-label"><i class="fas fa-user-tag"></i> Vai trò</label>
                                <select class="form-select" id="edit_role" name="role">
                                    <option value="student">Học sinh</option>
                                    <option value="teacher">Giáo viên</option>
                                    <option value="admin">Quản trị viên</option>
                                </select>
                            </div>
                            <div class="mb-3 input-icon">
                                <label for="edit_phone_number" class="form-label"><i class="fas fa-phone"></i> Số điện thoại</label>
                                <input type="text" class="form-control" id="edit_phone_number" name="phone_number">
                            </div>
                            <div class="mb-3 input-icon">
                                <label for="edit_address" class="form-label"><i class="fas fa-map-marker-alt"></i> Địa chỉ</label>
                                <input type="text" class="form-control" id="edit_address" name="address">
                            </div>
                            <div class="mb-3 input-icon">
                                <label for="edit_date_of_birth" class="form-label"><i class="fas fa-calendar-alt"></i> Ngày sinh</label>
                                <input type="date" class="form-control" id="edit_date_of_birth" name="date_of_birth">
                            </div>
                        </div>
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewUserModalLabel"><i class="fas fa-user"></i> Thông tin người dùng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div class="avatar-container">
                    <img src="/study_sharing/assets/images/profile.png" alt="Avatar" class="avatar-preview" id="viewAvatar">
                </div>
                <div class="info-item">
                    <strong><i class="fas fa-id-badge"></i> ID:</strong>
                    <span id="view_account_id"></span>
                </div>
                <div class="info-item">
                    <strong><i class="fas fa-user"></i> Tên đăng nhập:</strong>
                    <span id="view_username"></span>
                </div>
                <div class="info-item">
                    <strong><i class="fas fa-envelope"></i> Email:</strong>
                    <span id="view_email"></span>
                </div>
                <div class="info-item">
                    <strong><i class="fas fa-address-card"></i> Họ tên:</strong>
                    <span id="view_full_name"></span>
                </div>
                <div class="info-item">
                    <strong><i class="fas fa-user-tag"></i> Vai trò:</strong>
                    <span id="view_role"></span>
                </div>
                <div class="info-item">
                    <strong><i class="fas fa-phone"></i> Số điện thoại:</strong>
                    <span id="view_phone_number"></span>
                </div>
                <div class="info-item">
                    <strong><i class="fas fa-map-marker-alt"></i> Địa chỉ:</strong>
                    <span id="view_address"></span>
                </div>
                <div class="info-item">
                    <strong><i class="fas fa-calendar-alt"></i> Ngày sinh:</strong>
                    <span id="view_date_of_birth"></span>
                </div>
                <div class="info-item">
                    <strong><i class="fas fa-info-circle"></i> Trạng thái:</strong>
                    <span id="view_status"></span>
                </div>
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
        document.getElementById('edit_current_avatar').value = user.avatar || 'profile.png';
        document.getElementById('editAvatarPreview').src = user.avatar ? '/study_sharing/assets/images/' + user.avatar : '/study_sharing/assets/images/profile.png';
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
        document.getElementById('viewAvatar').src = user.avatar ? '/study_sharing/assets/images/' + user.avatar : '/study_sharing/assets/images/profile.png';

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

    // Avatar preview for Add User Modal
    document.getElementById('add_avatar')?.addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('addAvatarPreview').src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });

    // Avatar preview for Edit User Modal
    document.getElementById('edit_avatar')?.addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('editAvatarPreview').src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
</script>