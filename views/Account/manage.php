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
</style>
<div class="container mt-4">
    <h2 class="mb-4"><?php echo htmlspecialchars($title); ?></h2>

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
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
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
                                    <?php echo htmlspecialchars($user['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button type="button" class="btn btn-info btn-sm" onclick='showUserDetails(<?php echo json_encode($user, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'>
                                        Xem
                                    </button>

                                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editUserModal"
                                        onclick="fillEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                        Sửa
                                    </button>
                                    <?php if ($user['role'] === 'teacher' || $user['role'] === 'student'): ?>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="lockUser(<?php echo $user['account_id']; ?>, '<?php echo $user['status'] === 'banned' ? 'active' : 'banned'; ?>')">
                                            <?php echo $user['status'] === 'banned' ? 'Mở khóa' : 'Khóa'; ?>
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

    <!-- Phân trang dưới bảng -->
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

<!-- Modal thêm người dùng -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/study_sharing/Account/addUser">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Thêm người dùng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="username" class="form-label">Tên đăng nhập</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Họ tên</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Vai trò</label>
                        <select class="form-select" id="role" name="role">
                            <option value="student">Học sinh</option>
                            <option value="teacher">Giáo viên</option>
                            <option value="admin">Quản trị viên</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="phone_number" class="form-label">Số điện thoại</label>
                        <input type="text" class="form-control" id="phone_number" name="phone_number">
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Địa chỉ</label>
                        <input type="text" class="form-control" id="address" name="address">
                    </div>
                    <div class="mb-3">
                        <label for="date_of_birth" class="form-label">Ngày sinh</label>
                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Thêm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal sửa người dùng -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/study_sharing/Account/updateUser">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Sửa người dùng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_account_id" name="account_id">
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Tên đăng nhập</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_full_name" class="form-label">Họ tên</label>
                        <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Vai trò</label>
                        <select class="form-select" id="edit_role" name="role">
                            <option value="student">Học sinh</option>
                            <option value="teacher">Giáo viên</option>
                            <option value="admin">Quản trị viên</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_phone_number" class="form-label">Số điện thoại</label>
                        <input type="text" class="form-control" id="edit_phone_number" name="phone_number">
                    </div>
                    <div class="mb-3">
                        <label for="edit_address" class="form-label">Địa chỉ</label>
                        <input type="text" class="form-control" id="edit_address" name="address">
                    </div>
                    <div class="mb-3">
                        <label for="edit_date_of_birth" class="form-label">Ngày sinh</label>
                        <input type="date" class="form-control" id="edit_date_of_birth" name="date_of_birth">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal xem thông tin người dùng -->
<div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewUserModalLabel">Thông tin người dùng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <p><strong>ID:</strong> <span id="view_account_id"></span></p>
                <p><strong>Tên đăng nhập:</strong> <span id="view_username"></span></p>
                <p><strong>Email:</strong> <span id="view_email"></span></p>
                <p><strong>Họ tên:</strong> <span id="view_full_name"></span></p>
                <p><strong>Vai trò:</strong> <span id="view_role"></span></p>
                <p><strong>Số điện thoại:</strong> <span id="view_phone_number"></span></p>
                <p><strong>Địa chỉ:</strong> <span id="view_address"></span></p>
                <p><strong>Ngày sinh:</strong> <span id="view_date_of_birth"></span></p>
                <p><strong>Trạng thái:</strong> <span id="view_status"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
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