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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
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
                        <li><a class="dropdown-item" href="/study_sharing/notification/list_admin"><i class="bi bi-bell"></i> Thông báo</a></li>
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
            <a class="nav-link dropdown-toggle <?php echo strpos($_SERVER['REQUEST_URI'], '/study_sharing/AdminDocument/') !== false ? 'active' : ''; ?>" href="#" role="button" onclick="toggleDropdown(this)">
                <i class="bi bi-file-earmark-text"></i> Quản lý tài liệu
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="/study_sharing/AdminDocument/admin_manage"><i class="bi bi-folder"></i> Quản lý tài liệu</a></li>
                <li><a class="dropdown-item" href="/study_sharing/AdminDocument/admin_statistics"><i class="bi bi-bar-chart"></i> Thống kê tài liệu</a></li>
            </ul>
            <!-- Course Dropdown -->
            <a class="nav-link dropdown-toggle <?php echo strpos($_SERVER['REQUEST_URI'], '/study_sharing/AdminCourse/') !== false ? 'active' : ''; ?>" href="#" role="button" onclick="toggleDropdown(this)">
                <i class="bi bi-book"></i> Quản lý khóa học
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="/study_sharing/AdminCourse/manage"><i class="bi bi-gear"></i> Quản lý khóa học</a></li>
                <li><a class="dropdown-item" href="/study_sharing/AdminCourse/approve"><i class="bi bi-check-circle"></i> Phê duyệt khóa học</a></li>
                <li><a class="dropdown-item" href="/study_sharing/AdminCourse/statistics"><i class="bi bi-bar-chart"></i> Thống kê khóa học</a></li>
            </ul>
            <!-- Notification Link -->
            <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/NotificationAdmin/admin_send_notification') !== false ? 'active' : ''; ?>" href="/study_sharing/NotificationAdmin/admin_send_notification">
                <i class="bi bi-bell"></i> Quản lý thông báo
            </a>


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
        <!DOCTYPE html>
        <html lang="vi">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <!-- Liên kết Font Awesome cho các biểu tượng -->
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <style>
                /* CSS cho footer */
                footer {
                    background: linear-gradient(to bottom, #1a202c, #2d3748);
                    color: white;
                    padding: 3rem 0;
                    margin-top: 3rem;
                    position: relative;
                    overflow: hidden;
                }

                .wave-divider {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 3rem;
                }

                .wave-divider svg {
                    width: 100%;
                    height: 100%;
                    fill: #2d3748;
                }

                .footer-grid {
                    display: grid;
                    grid-template-columns: 1fr;
                    gap: 2.5rem;
                    padding: 0 2rem;
                }

                @media (min-width: 640px) {
                    .footer-grid {
                        grid-template-columns: repeat(2, 1fr);
                    }
                }

                @media (min-width: 1024px) {
                    .footer-grid {
                        grid-template-columns: repeat(4, 1fr);
                        padding: 0 5rem;
                    }

                    .footer-grid>div {
                        text-align: left;
                    }
                }

                .footer-section h3 {
                    font-size: 1.5rem;
                    font-weight: 700;
                    margin-bottom: 1.25rem;
                    position: relative;
                }

                .footer-section h3::after {
                    content: '';
                    position: absolute;
                    bottom: 0;
                    left: 0;
                    width: 3rem;
                    height: 0.25rem;
                    border-radius: 9999px;
                    transition: width 0.3s;
                }

                .intro h3 {
                    color: #f56565;
                }

                .intro h3::after {
                    background-color: #f56565;
                }

                .contact h3 {
                    color: #63b3ed;
                }

                .contact h3::after {
                    background-color: #63b3ed;
                }

                .address h3 {
                    color: #68d391;
                }

                .address h3::after {
                    background-color: #68d391;
                }

                .follow h3 {
                    color: #b794f4;
                }

                .follow h3::after {
                    background-color: #b794f4;
                }

                .footer-section p {
                    color: #e2e8f0;
                    line-height: 1.75;
                    font-size: 1rem;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 0.75rem;
                    transition: color 0.3s;
                }

                @media (min-width: 1024px) {
                    .footer-section p {
                        justify-content: flex-start;
                    }
                }

                .footer-section p:hover {
                    color: white;
                }

                .footer-section a {
                    color: inherit;
                    text-decoration: none;
                }

                .footer-section a:hover {
                    text-decoration: underline;
                }

                .footer-section i {
                    transition: transform 0.3s;
                }

                .footer-section p:hover i {
                    transform: scale(1.1);
                }

                .social-links {
                    display: flex;
                    justify-content: center;
                    gap: 1.5rem;
                    font-size: 1.75rem;
                }

                @media (min-width: 1024px) {
                    .social-links {
                        justify-content: flex-start;
                    }
                }

                .social-links a {
                    color: #e2e8f0;
                    transition: all 0.3s;
                }

                .social-links a:hover {
                    transform: scale(1.1);
                    filter: drop-shadow(0 0 10px rgba(255, 255, 255, 0.5));
                }

                .social-links a.github:hover {
                    color: white;
                }

                .social-links a.youtube:hover {
                    color: #ef4444;
                }

                .social-links a.facebook:hover {
                    color: #3b5998;
                }

                .social-links a.linkedin:hover {
                    color: #0a66c2;
                }
            </style>
        </head>

        <body>
            <footer>
                <!-- Grid 4 cột -->
                <div class="footer-grid">
                    <!-- Giới thiệu -->
                    <div class="footer-section intro">
                        <h3>Giới Thiệu</h3>
                        <p>Study Saring -Nền tảng quản lý và chia sẻ tài liệu học tập tiện lợi, đáng tin cậy, hỗ trợ mọi nhu cầu học tập</p>
                    </div>

                    <!-- Liên hệ -->
                    <div class="footer-section contact">
                        <h3>Liên Hệ</h3>
                        <p>
                            <i class="fas fa-phone"></i>
                            <a href="tel:0338111591">0338 111 591</a>
                        </p>
                        <p>
                            <i class="fas fa-envelope"></i>
                            <a href="mailto:contact@certicrypt.com">contact@certicrypt.com</a>
                        </p>
                    </div>

                    <!-- Địa chỉ -->
                    <div class="footer-section address">
                        <h3>Địa Chỉ</h3>
                        <p>
                            <i class="fas fa-map-marker-alt"></i>
                            <a href="https://maps.google.com/?q=Phạm+Hữu+Lầu,+P.6,+TP.+Cao+Lãnh,+Đồng+Tháp" target="_blank" rel="noopener noreferrer">
                                Phạm Hữu Lầu, P.6, TP. Cao Lãnh, Đồng Tháp
                            </a>
                        </p>
                    </div>

                    <!-- Nền tảng -->
                    <div class="footer-section follow">
                        <h3>Theo Dõi</h3>
                        <div class="social-links">
                            <a href="https://github.com" target="_blank" rel="noopener noreferrer" class="github"><i class="fab fa-github"></i></a>
                            <a href="https://youtube.com" target="_blank" rel="noopener noreferrer" class="youtube"><i class="fab fa-youtube"></i></a>
                            <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" class="facebook"><i class="fab fa-facebook"></i></a>
                            <a href="https://linkedin.com" target="_blank" rel="noopener noreferrer" class="linkedin"><i class="fab fa-linkedin"></i></a>
                        </div>
                    </div>
                </div>
            </footer>
        </body>

        </html>
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