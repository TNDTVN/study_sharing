<?php
$title = "Chỉnh sửa hồ sơ";
$avatar = $user && $user['avatar'] ? '/study_sharing/assets/images/' . htmlspecialchars($user['avatar']) : '/study_sharing/assets/images/profile.png';
$role = $user['role'] ?? 'Không xác định';
$created_at = $user['created_at'] ?? 'Không xác định';
$date_of_birth_display = $user['date_of_birth'] ? date('d/m/Y', strtotime($user['date_of_birth'])) : '';
?>

<link rel="stylesheet" href="/study_sharing/assets/css/profile.css">

<div class="profile-container">
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <div class="card profile-card">
        <div class="card-header">
            <h1 class="mb-0"><i class="bi bi-person-circle me-2"></i> Chỉnh sửa hồ sơ</h1>
        </div>
        <div class="card-body p-0">
            <form id="profileForm" method="POST" action="/study_sharing/user/updateProfile" enctype="multipart/form-data" class="needs-validation" novalidate>
                <input type="hidden" name="current_avatar" value="<?php echo htmlspecialchars($user['avatar'] ?? 'profile.png'); ?>">
                <input type="hidden" name="date_of_birth" id="date_of_birth_hidden" value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>">
                <div class="row g-0 profile-row">
                    <!-- Avatar Section -->
                    <div class="col-lg-4 avatar-section">
                        <img src="<?php echo $avatar; ?>" alt="Avatar" class="avatar-img rounded-circle" id="avatarPreview">
                        <div class="text-center mb-3">
                            <label for="avatar" class="form-label d-block fw-bold mb-2">Ảnh đại diện</label>
                            <input type="file" class="form-control d-none" id="avatar" name="avatar" accept="image/*">
                            <label for="avatar" class="custom-file-upload"><i class="bi bi-upload me-2"></i> Chọn ảnh</label>
                            <div class="form-text mt-2">Chấp nhận file JPEG, PNG, GIF.<br>Tối đa 5MB.</div>
                        </div>
                        <div class="info-item">
                            <strong>Vai trò:</strong>
                            <span class="d-block mt-1">
                                <?php
                                echo htmlspecialchars($role === 'admin' ? 'Quản trị viên' : ($role === 'teacher' ? 'Giảng viên' : 'Học sinh'));
                                ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <strong>Ngày tạo tài khoản:</strong>
                            <span class="d-block mt-1">
                                <?php echo htmlspecialchars(date('d/m/Y', strtotime($created_at))); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Form Fields -->
                    <div class="col-lg-8 form-section">
                        <div class="mb-4">
                            <label for="username" class="form-label fw-bold">Tên đăng nhập</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" disabled>
                            <div class="form-text">Tên đăng nhập không thể thay đổi.</div>
                        </div>
                        <div class="mb-4">
                            <label for="email" class="form-label fw-bold">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                            <div class="invalid-feedback">Vui lòng nhập email hợp lệ.</div>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label fw-bold">Mật khẩu mới (để trống nếu không đổi)</label>
                            <input type="password" class="form-control" id="password" name="password">
                            <div class="invalid-feedback">Mật khẩu phải có ít nhất 6 ký tự.</div>
                        </div>
                        <div class="mb-4">
                            <label for="full_name" class="form-label fw-bold">Họ và tên</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                            <div class="invalid-feedback">Vui lòng nhập họ và tên.</div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="date_of_birth_display" class="form-label fw-bold">Ngày sinh</label>
                                <input type="text" class="form-control flatpickr-input" id="date_of_birth_display" value="<?php echo htmlspecialchars($date_of_birth_display); ?>" placeholder="dd/mm/yyyy" readonly>
                                <div class="invalid-feedback">Ngày sinh không hợp lệ.</div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label for="phone_number" class="form-label fw-bold">Số điện thoại</label>
                                <input type="tel" class="form-control" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>">
                                <div class="invalid-feedback">Số điện thoại phải có 10-11 chữ số.</div>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="address" class="form-label fw-bold">Địa chỉ</label>
                            <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 mt-2">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            <i class="bi bi-save-fill me-2"></i>Cập nhật hồ sơ
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include flatpickr CSS và JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script src="/study_sharing/assets/js/profile.css"></script>