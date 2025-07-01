<?php
// Kiểm tra session và quyền admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['account_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /study_sharing');
    exit;
}
?>

<style>
    .content {
        padding-top: 0 !important;

    }

    .header {
        background: linear-gradient(90deg, #4facfe, #00f2fe);
        color: white;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .table-custom {
        background-color: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .table-custom th {
        background-color: #4facfe;
        color: white;
    }

    .table-custom tr:hover {
        background-color: #e9f5ff;
        transition: background-color 0.3s ease;
    }

    .btn-custom {
        background: linear-gradient(90deg, #4facfe, #00f2fe);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 20px;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .btn-custom:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(0, 242, 254, 0.4);
    }

    .status-active {
        color: #28a745;
    }

    .status-inactive {
        color: #ffc107;
    }

    .status-banned {
        color: #dc3545;
    }

    .pagination .page-link {
        background: linear-gradient(90deg, #4facfe, #00f2fe);
        color: white;
        border: none;
        border-radius: 20px;
        margin: 0 5px;
    }

    .pagination .page-link:hover {
        background: linear-gradient(90deg, #3d9af2, #00d9e5);
    }

    .pagination .page-item.active .page-link {
        background: linear-gradient(90deg, #3d9af2, #00d9e5);
        opacity: 0.9;
    }

    .modal-content {
        border: none;
        border-radius: 15px;
        overflow: hidden;
        border: 1px solid transparent;
        background: linear-gradient(white, white) padding-box,
            linear-gradient(90deg, #4facfe, #00f2fe) border-box;
    }

    .modal-header {
        background: linear-gradient(90deg, #4facfe, #00f2fe);
        color: white;
    }
</style>




<div class="header">
    <h2 class="mb-0"><?php echo htmlspecialchars($title); ?></h2>
</div>

<!-- Thông báo -->
<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-<?php echo htmlspecialchars($_SESSION['message_type']); ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_SESSION['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
<?php endif; ?>

<!-- Form tìm kiếm -->
<form method="GET" action="/study_sharing/account/manage" class="mb-4">
    <div class="input-group">
        <input type="text" name="keyword" class="form-control" placeholder="Tìm kiếm theo username, email hoặc tên" value="<?php echo htmlspecialchars($keyword); ?>" style="border-radius: 20px;">
        <button type="submit" class="btn btn-custom ms-2">Tìm kiếm</button>
    </div>
</form>

<!-- Nút mở modal thêm người dùng -->
<button type="button" class="btn btn-custom mb-3" data-bs-toggle="modal" data-bs-target="#addUserModal">Thêm người dùng</button>

<!-- Bảng danh sách người dùng -->
<div class="table-custom">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Họ tên</th>
                <th>Vai trò</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="7" class="text-center">Không có người dùng nào</td>
                </tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['account_id']); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                        <td class="status-<?php echo htmlspecialchars(strtolower($user['status'])); ?>">
                            <?php echo htmlspecialchars($user['status']); ?>
                        </td>
                        <td>
                            <a href="/study_sharing/account/view?account_id=<?php echo htmlspecialchars($user['account_id']); ?>" class="btn btn-info btn-sm" style="border-radius: 20px;">Xem</a>
                            <button type="button" class="btn btn-warning btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#editUserModal"
                                data-account-id="<?php echo htmlspecialchars($user['account_id']); ?>"
                                data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                data-full-name="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>"
                                data-role="<?php echo htmlspecialchars($user['role']); ?>"
                                data-phone-number="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>"
                                data-address="<?php echo htmlspecialchars($user['address'] ?? ''); ?>"
                                data-date-of-birth="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>" style="border-radius: 20px;">Sửa</button>
                            <button type="button" class="btn btn-danger btn-sm ms-2" onclick="banUser(<?php echo htmlspecialchars($user['account_id']); ?>, '<?php echo $user['status'] === 'banned' ? 'active' : 'banned'; ?>')" style="border-radius: 20px;">
                                <?php echo $user['status'] === 'banned' ? 'Mở khóa' : 'Khóa'; ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Phân trang -->
<?php if ($totalPages > 1): ?>
    <nav aria-label="Page navigation" class="d-flex justify-content-center mt-4">
        <ul class="pagination">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="/study_sharing/account/manage?page=<?php echo $page - 1; ?>&keyword=<?php echo urlencode($keyword); ?>">Trước</a>
                </li>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="/study_sharing/account/manage?page=<?php echo $i; ?>&keyword=<?php echo urlencode($keyword); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="/study_sharing/account/manage?page=<?php echo $page + 1; ?>&keyword=<?php echo urlencode($keyword); ?>">Sau</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>

<!-- Modal thêm người dùng -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Thêm người dùng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="/study_sharing/account/add">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="username" required style="border-radius: 20px;">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required style="border-radius: 20px;">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" required style="border-radius: 20px;">
                    </div>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Họ tên <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required style="border-radius: 20px;">
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Vai trò</label>
                        <select class="form-select" id="role" name="role" style="border-radius: 20px;">
                            <option value="student">Student</option>
                            <option value="teacher">Teacher</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="phone_number" class="form-label">Số điện thoại</label>
                        <input type="text" class="form-control" id="phone_number" name="phone_number" style="border-radius: 20px;">
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Địa chỉ</label>
                        <input type="text" class="form-control" id="address" name="address" style="border-radius: 20px;">
                    </div>
                    <div class="mb-3">
                        <label for="date_of_birth" class="form-label">Ngày sinh</label>
                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" style="border-radius: 20px;">
                    </div>
                    <button type="submit" class="btn btn-custom">Thêm</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal sửa người dùng -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Sửa người dùng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="/study_sharing/account/update">
                    <input type="hidden" id="edit_account_id" name="account_id">
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_username" name="username" required style="border-radius: 20px;">
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="edit_email" name="email" required style="border-radius: 20px;">
                    </div>
                    <div class="mb-3">
                        <label for="edit_full_name" class="form-label">Họ tên <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_full_name" name="full_name" required style="border-radius: 20px;">
                    </div>
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Vai trò</label>
                        <select class="form-select" id="edit_role" name="role" style="border-radius: 20px;">
                            <option value="student">Student</option>
                            <option value="teacher">Teacher</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_phone_number" class="form-label">Số điện thoại</label>
                        <input type="text" class="form-control" id="edit_phone_number" name="phone_number" style="border-radius: 20px;">
                    </div>
                    <div class="mb-3">
                        <label for="edit_address" class="form-label">Địa chỉ</label>
                        <input type="text" class="form-control" id="edit_address" name="address" style="border-radius: 20px;">
                    </div>
                    <div class="mb-3">
                        <label for="edit_date_of_birth" class="form-label">Ngày sinh</label>
                        <input type="date" class="form-control" id="edit_date_of_birth" name="date_of_birth" style="border-radius: 20px;">
                    </div>
                    <button type="submit" class="btn btn-custom">Cập nhật</button>
                </form>
            </div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
<script>
    // Điền dữ liệu vào modal sửa người dùng
    document.getElementById('editUserModal').addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const accountId = button.getAttribute('data-account-id');
        const username = button.getAttribute('data-username');
        const email = button.getAttribute('data-email');
        const fullName = button.getAttribute('data-full-name');
        const role = button.getAttribute('data-role');
        const phoneNumber = button.getAttribute('data-phone-number');
        const address = button.getAttribute('data-address');
        const dateOfBirth = button.getAttribute('data-date-of-birth');

        const modal = this;
        modal.querySelector('#edit_account_id').value = accountId;
        modal.querySelector('#edit_username').value = username;
        modal.querySelector('#edit_email').value = email;
        modal.querySelector('#edit_full_name').value = fullName;
        modal.querySelector('#edit_role').value = role;
        modal.querySelector('#edit_phone_number').value = phoneNumber;
        modal.querySelector('#edit_address').value = address;
        modal.querySelector('#edit_date_of_birth').value = dateOfBirth;
    });

    // Hàm khóa/mở khóa người dùng
    function banUser(accountId, status) {
        if (confirm('Bạn có chắc muốn ' + (status === 'banned' ? 'khóa' : 'mở khóa') + ' tài khoản này?')) {
            fetch('/study_sharing/account/ban', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'account_id=' + encodeURIComponent(accountId) + '&status=' + encodeURIComponent(status)
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        window.location.reload();
                    }
                })
                .catch(error => {
                    alert('Lỗi: ' + error.message);
                });
        }
    }
</script>