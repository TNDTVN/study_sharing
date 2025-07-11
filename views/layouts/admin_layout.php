<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'Bảng điều khiển Quản trị'; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="/study_sharing/assets/css/custom.css" rel="stylesheet">
    <link href="/study_sharing/assets/css/admin.css" rel="stylesheet">
    <link href="/study_sharing/assets/css/sidebar.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="/study_sharing/assets/images/logo.png">

</head>

<body class="bg-light">
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary top-navbar">
        <div class="container-fluid">
            <!-- Sidebar Toggle -->
            <button class="sidebar-toggle" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <!-- User Dropdown -->
            <ul class="navbar-nav ms-auto">
                <?php
                $user = isset($_SESSION['account_id']) ? (new \App\User($pdo))->getUserById($_SESSION['account_id']) : null;
                $avatar = $user && $user['avatar'] ? '/study_sharing/assets/images/' . htmlspecialchars($user['avatar']) : '/study_sharing/assets/images/profile.png';
                ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="me-2 d-none d-lg-inline"><?php echo htmlspecialchars($user['full_name'] ?? 'Admin'); ?></span>
                        <span class="avatar-container">
                            <img src="<?php echo $avatar; ?>" alt="Avatar" class="avatar-img rounded-circle" style="height: 36px; width: 36px; object-fit: cover;">
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="/study_sharing/admin/profile"><i class="bi bi-person"></i> Hồ sơ</a></li>
                        <li><a class="dropdown-item" href="/notification/list"><i class="bi bi-bell"></i> Thông báo</a></li>
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal"><i class="bi bi-key"></i> Đổi mật khẩu</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item text-danger" href="/study_sharing/auth/logout"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Logo -->
        <div class="logo-container">
            <a href="/study_sharing/HomeAdmin/index">
                <img src="/study_sharing/assets/images/logo.png" alt="Logo" class="rounded-circle" style="height: 60px; width: 60px; object-fit: cover;">
                <div class="mt-2 text-white">Study Sharing Admin</div>
            </a>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/HomeAdmin/index') !== false ? 'active' : ''; ?>" href="/study_sharing/HomeAdmin/index">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/Account/manage') !== false ? 'active' : ''; ?>" href="/study_sharing/Account/manage">
                <i class="bi bi-people"></i> Quản lý người dùng
            </a>
            <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/category/manage') !== false ? 'active' : ''; ?>" href="/study_sharing/category/manage">
                <i class="bi bi-folder"></i> Quản lý danh mục
            </a>
            <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/tag/manage') !== false ? 'active' : ''; ?>" href="/study_sharing/tag/manage">
                <i class="bi bi-tag"></i> Quản lý thẻ
            </a>
            <!-- Document Dropdown -->
            <a class="nav-link dropdown-toggle <?php echo strpos($_SERVER['REQUEST_URI'], '/document/') !== false ? 'active' : ''; ?>" href="#" role="button" onclick="toggleDropdown(this)">
                <i class="bi bi-file-earmark-text"></i> Quản lý tài liệu
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="/study_sharing/AdminDocument/admin_manage"><i class="bi bi-folder"></i> Quản lý tài liệu</a></li>
                <li><a class="dropdown-item" href="/document/approve"><i class="bi bi-check-circle"></i> Phê duyệt tài liệu</a></li>
                <li><a class="dropdown-item" href="/document/statistics"><i class="bi bi-bar-chart"></i> Thống kê tài liệu</a></li>
            </ul>
            <!-- Course Dropdown -->
            <a class="nav-link dropdown-toggle <?php echo strpos($_SERVER['REQUEST_URI'], '/course/') !== false ? 'active' : ''; ?>" href="#" role="button" onclick="toggleDropdown(this)">
                <i class="bi bi-book"></i> Quản lý khóa học
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="/course/manage"><i class="bi bi-gear"></i> Quản lý khóa học</a></li>
                <li><a class="dropdown-item" href="/course/approve"><i class="bi bi-check-circle"></i> Phê duyệt khóa học</a></li>
                <li><a class="dropdown-item" href="/course/statistics"><i class="bi bi-bar-chart"></i> Thống kê khóa học</a></li>
            </ul>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <main class="content">
            <div class="container py-5">
                <?php echo $content; ?>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-dark text-white text-center py-3">
            <div class="container">
                <p class="mb-0">© 2025 - Hệ thống Quản lý và Chia sẻ Tài liệu. Đã đăng ký bản quyền.</pp>
            </div>
    </div>
    </footer>
    </div>

    <!-- Modal Đổi mật khẩu -->
    <div id="changePasswordModal" class="modal fade" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="changePasswordModalLabel">Đổi mật khẩu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="changePasswordMessage"></div>
                    <form id="changePasswordForm" method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">Mật khẩu hiện tại</label>
                            <input type="password" class="form-control" id="currentPassword" name="current_password" required>
                            <div class="invalid-feedback">Vui lòng nhập mật khẩu hiện tại.</div>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">Mật khẩu mới</label>
                            <input type="password" class="form-control" id="newPassword" name="new_password" required>
                            <div class="invalid-feedback">Vui lòng nhập mật khẩu mới.</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirmNewPassword" class="form-label">Xác nhận mật khẩu mới</label>
                            <input type="password" class="form-control" id="confirmNewPassword" name="confirm_new_password" required>
                            <div class="invalid-feedback">Vui lòng xác nhận mật khẩu mới.</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            Đổi mật khẩu
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    <!-- Admin JS -->
    <script src="/study_sharing/assets/js/admin.js"></script>
</body>

</html>