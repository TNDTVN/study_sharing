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

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
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
</head>

<body>
    <div class="container mt-5">
        <h2 class="mb-4"><?php echo htmlspecialchars($title); ?></h2>

        <!-- Thông báo -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo htmlspecialchars($_SESSION['message_type']); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        <?php endif; ?>

        <!-- Thông tin người dùng -->
        <div class="card">
            <div class="card-header">
                <h5>Thông tin chi tiết</h5>
            </div>
            <div class="card-body">
                <p><strong>ID:</strong> <?php echo htmlspecialchars($user['account_id']); ?></p>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Họ tên:</strong> <?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></p>
                <p><strong>Vai trò:</strong> <?php echo htmlspecialchars($user['role']); ?></p>
                <p><strong>Trạng thái:</strong> <span class="status-<?php echo htmlspecialchars(strtolower($user['status'])); ?>"><?php echo htmlspecialchars($user['status']); ?></span></p>
                <p><strong>Ngày sinh:</strong> <?php echo htmlspecialchars($user['date_of_birth'] ?? 'N/A'); ?></p>
                <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($user['phone_number'] ?? 'N/A'); ?></p>
                <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($user['address'] ?? 'N/A'); ?></p>
                <p><strong>Ngày tạo:</strong> <?php echo htmlspecialchars($user['created_at']); ?></p>
                <p><strong>Ngày cập nhật:</strong> <?php echo htmlspecialchars($user['updated_at']); ?></p>
                <p><strong>Avatar:</strong>
                    <?php if ($user['avatar']): ?>
                        <img src="/study_sharing/uploads/<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar" style="max-width: 100px;">
                    <?php else: ?>
                        Chưa có avatar
                    <?php endif; ?>
                </p>
            </div>
            <div class="card-footer">
                <a href="/study_sharing/account/manage" class="btn btn-primary">Quay lại</a>
                <button type="button" class="btn btn-danger" onclick="banUser(<?php echo htmlspecialchars($user['account_id']); ?>, '<?php echo $user['status'] === 'banned' ? 'active' : 'banned'; ?>')">
                    <?php echo $user['status'] === 'banned' ? 'Mở khóa' : 'Khóa'; ?>
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <script>
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
</body>

</html>