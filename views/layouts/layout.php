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
    <title><?php echo isset($title) ? $title : 'Quản lý và Chia sẻ Tài liệu'; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="/study_sharing/assets/css/custom.css" rel="stylesheet">
    <link rel="stylesheet" href="/study_sharing/assets/css/navbar_layout.css">
</head>

<body class="bg-light d-flex flex-column min-vh-100">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container-fluid">
            <!-- Logo -->
            <a class="navbar-brand" href="/study_sharing">
                <img src="/study_sharing/assets/images/logo.png" alt="Logo" class="rounded-circle" style="height: 40px; width: 40px; object-fit: cover;">
                <span class="ms-2 d-none d-md-inline">Study Sharing</span>
            </a>

            <!-- Mobile Toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navbar Content -->
            <div class="collapse navbar-collapse" id="navbarContent">
                <!-- Thanh tìm kiếm -->
                <form class="d-flex mx-lg-3 my-2 my-lg-0 search-container" role="search" action="/study_sharing/document/list" method="GET">
                    <div class="input-group">
                        <input class="form-control form-control-sm" type="search" name="query" placeholder="Tìm kiếm tài liệu..." aria-label="Search">
                        <button class="btn btn-light btn-sm" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                </form>

                <!-- Nút chức năng -->
                <ul class="navbar-nav ms-auto">
                    <?php
                    $user = isset($_SESSION['account_id']) ? (new \App\User($pdo))->getUserById($_SESSION['account_id']) : null;
                    $role = $user ? $user['role'] : null;
                    $avatar = $user && $user['avatar'] ? '/study_sharing/assets/images/' . htmlspecialchars($user['avatar']) : '/study_sharing/assets/images/profile.png';
                    ?>

                    <!-- Main Navigation Items -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-file-earmark-text-fill"></i> Tài liệu
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/study_sharing/document/list"><i class="bi bi-collection"></i> Xem tất cả</a></li>
                            <?php if ($user && in_array($role, ['teacher', 'student'])): ?>
                                <li><a class="dropdown-item" href="/study_sharing/document/upload"><i class="bi bi-upload"></i> Tải lên</a></li>
                                <li><a class="dropdown-item" href="/study_sharing/document/delete"><i class="bi bi-trash"></i> Quản lý</a></li>
                            <?php endif; ?>
                            <?php if ($role === 'student'): ?>
                                <li><a class="dropdown-item" href="/study_sharing/document/my_documents"><i class="bi bi-journal-bookmark"></i> Tài liệu của tôi</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-book-fill"></i> Khóa học
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/study_sharing/course/list"><i class="bi bi-collection"></i> Xem tất cả</a></li>
                            <?php if ($role === 'teacher'): ?>
                                <li><a class="dropdown-item" href="/study_sharing/course/create"><i class="bi bi-plus-circle"></i> Tạo khóa học</a></li>
                                <li><a class="dropdown-item" href="/study_sharing/course/manage"><i class="bi bi-gear"></i> Quản lý khóa học</a></li>
                            <?php endif; ?>
                            <?php if ($role === 'student'): ?>
                                <li><a class="dropdown-item" href="/study_sharing/course/my_courses"><i class="bi bi-journal-text"></i> Khóa học của tôi</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>

                    <?php if ($user): ?>
                        <!-- User Dropdown -->
                        <li class="nav-item dropdown ms-lg-2">
                            <div class="d-flex align-items-center">
                                <a class="nav-link dropdown-toggle pe-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="d-none d-lg-inline"><?php echo htmlspecialchars($user['full_name']); ?></span>
                                </a>
                                <a class="avatar-container" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                                    <img src="<?php echo $avatar; ?>" alt="Avatar" class="avatar-img rounded-circle" style="height: 36px; width: 36px; object-fit: cover;">
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="/study_sharing/user/profile"><i class="bi bi-person"></i> Hồ sơ</a></li>
                                    <li><a class="dropdown-item" href="/study_sharing/notification/list"><i class="bi bi-bell"></i> Thông báo</a></li>
                                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal"><i class="bi bi-key"></i> Đổi mật khẩu</a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item text-danger" href="/study_sharing/auth/logout"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a></li>
                                </ul>
                            </div>
                        </li>
                    <?php else: ?>
                        <!-- Guest Actions Dropdown -->
                        <li class="nav-item dropdown ms-lg-2">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="/study_sharing/assets/images/profile.png" alt="Avatar" class="rounded-circle avatar-img" style="height: 30px; width: 30px; object-fit: cover;">
                                <span class="d-none d-lg-inline">Tài khoản</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#loginModal"><i class="bi bi-box-arrow-in-right"></i> Đăng nhập</a></li>
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#registerModal"><i class="bi bi-person-plus"></i> Đăng ký</a></li>
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal"><i class="bi bi-question-circle"></i> Quên mật khẩu</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="content flex-grow-1 py-4">
        <div class="container-fluid">
            <?php echo $content; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center p-4 mt-auto">
        <div class="container">
            <p class="mb-0">© 2025 - Hệ thống Quản lý và Chia sẻ Tài liệu. Đã đăng ký bản quyền.</p>
        </div>
    </footer>

    <!-- Modal Đăng nhập -->
    <div id="loginModal" class="modal fade" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="loginModalLabel">Đăng nhập</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="loginMessage"></div>
                    <form id="loginForm" method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="loginUsername" class="form-label">Tên đăng nhập hoặc Email</label>
                            <input type="text" class="form-control" id="loginUsername" name="username" required>
                            <div class="invalid-feedback">Vui lòng nhập tên đăng nhập hoặc email.</div>
                        </div>
                        <div class="mb-3">
                            <label for="loginPassword" class="form-label">Mật khẩu</label>
                            <input type="password" class="form-control" id="loginPassword" name="password" required>
                            <div class="invalid-feedback">Vui lòng nhập mật khẩu.</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            Đăng nhập
                        </button>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-bs-toggle="modal" data-bs-target="#registerModal" data-bs-dismiss="modal">Đăng ký tài khoản</button>
                    <button type="button" class="btn btn-link" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal" data-bs-dismiss="modal">Quên mật khẩu?</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Đăng ký -->
    <div id="registerModal" class="modal fade" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="registerModalLabel">Đăng ký</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="registerMessage"></div>
                    <form id="registerForm" method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="registerUsername" class="form-label">Tên đăng nhập</label>
                            <input type="text" class="form-control" id="registerUsername" name="username" required>
                            <div class="invalid-feedback">Vui lòng nhập tên đăng nhập.</div>
                        </div>
                        <div class="mb-3">
                            <label for="registerEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="registerEmail" name="email" required>
                            <div class="invalid-feedback">Vui lòng nhập email hợp lệ.</div>
                        </div>
                        <div class="mb-3">
                            <label for="registerPassword" class="form-label">Mật khẩu</label>
                            <input type="password" class="form-control" id="registerPassword" name="password" required>
                            <div class="invalid-feedback">Vui lòng nhập mật khẩu.</div>
                        </div>
                        <div class="mb-3">
                            <label for="registerFullName" class="form-label">Họ và tên</label>
                            <input type="text" class="form-control" id="registerFullName" name="full_name" required>
                            <div class="invalid-feedback">Vui lòng nhập họ và tên.</div>
                        </div>
                        <div class="mb-3">
                            <label for="registerRole" class="form-label">Vai trò</label>
                            <select class="form-control" id="registerRole" name="role" required>
                                <option value="" disabled selected>Chọn vai trò</option>
                                <option value="student">Học sinh (Sinh viên)</option>
                                <option value="teacher">Giảng viên</option>
                            </select>
                            <div class="invalid-feedback">Vui lòng chọn vai trò.</div>
                        </div>
                        <div class="mb-3">
                            <label for="registerDateOfBirth" class="form-label">Ngày sinh (tùy chọn)</label>
                            <input type="date" class="form-control" id="registerDateOfBirth" name="date_of_birth">
                            <div class="invalid-feedback">Vui lòng nhập ngày sinh hợp lệ.</div>
                        </div>
                        <div class="mb-3">
                            <label for="registerPhoneNumber" class="form-label">Số điện thoại (tùy chọn)</label>
                            <input type="tel" class="form-control" id="registerPhoneNumber" name="phone_number" pattern="[0-9]{10}">
                            <div class="invalid-feedback">Vui lòng nhập số điện thoại hợp lệ (10 số).</div>
                        </div>
                        <div class="mb-3">
                            <label for="registerAddress" class="form-label">Địa chỉ (tùy chọn)</label>
                            <input type="text" class="form-control" id="registerAddress" name="address">
                            <div class="invalid-feedback">Vui lòng nhập địa chỉ hợp lệ.</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            Đăng ký
                        </button>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="modal">Đã có tài khoản? Đăng nhập</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Quên mật khẩu -->
    <div id="forgotPasswordModal" class="modal fade" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="forgotPasswordModalLabel">Quên mật khẩu</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="forgotPasswordMessage"></div>
                    <form id="forgotPasswordForm" method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="forgotPasswordEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="forgotPasswordEmail" name="email" required>
                            <div class="invalid-feedback">Vui lòng nhập email hợp lệ.</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" id="forgotPasswordSubmit">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            Gửi liên kết đặt lại mật khẩu
                        </button>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="modal">Quay lại đăng nhập</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Đổi mật khẩu -->
    <div id="changePasswordModal" class="modal fade" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="changePasswordModalLabel">Đổi mật khẩu</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
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
    <script src="/study_sharing/assets/js/index.js"></script>
</body>

</html>