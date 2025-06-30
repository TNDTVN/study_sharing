<?php
$title = "Chỉnh sửa hồ sơ";
$avatar = $user && $user['avatar'] ? '/study_sharing/assets/images/' . htmlspecialchars($user['avatar']) : '/study_sharing/assets/images/profile.png';
$role = $user['role'] ?? 'Không xác định';
$created_at = $user['created_at'] ?? 'Không xác định';
// Chuyển đổi date_of_birth sang DD/MM/YYYY để hiển thị
$date_of_birth_display = $user['date_of_birth'] ? date('d/m/Y', strtotime($user['date_of_birth'])) : '';
?>

<style>
    .profile-container {
        max-width: 900px;
        margin: 1rem auto;
    }

    .profile-card {
        border-radius: 12px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        border: none;
    }

    .card-header {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        color: white;
        padding: 1.5rem;
        border-bottom: none;
    }

    .profile-row {
        display: flex;
        flex-wrap: wrap;
        min-height: 100%;
    }

    .avatar-section {
        background-color: #f8f9fc;
        padding: 2rem 1.5rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        border-right: 1px solid #e3e6f0;
        flex: 1;
        min-height: 100%;
    }

    .avatar-img {
        width: 180px;
        height: 180px;
        object-fit: cover;
        border: 5px solid white;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-bottom: 1.5rem;
    }

    .custom-file-upload {
        display: inline-block;
        padding: 8px 16px;
        cursor: pointer;
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        color: white;
        border-radius: 6px;
        transition: all 0.3s;
        font-weight: 500;
        border: none;
        margin-bottom: 1rem;
    }

    .custom-file-upload:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .info-item {
        margin-bottom: 1.25rem;
        width: 100%;
        padding: 0.75rem;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        text-align: center;
    }

    .info-item strong {
        color: #5a5c69;
    }

    .form-section {
        padding: 2rem;
        flex: 1;
    }

    .form-control,
    .form-select {
        border-radius: 6px;
        padding: 10px 15px;
        border: 1px solid #d1d3e2;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #bac8f3;
        box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
    }

    .btn-primary {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: 600;
        letter-spacing: 0.5px;
        transition: all 0.3s;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .form-text {
        color: #858796;
        font-size: 0.85rem;
    }

    .invalid-feedback {
        font-size: 0.85rem;
    }

    /* Tùy chỉnh flatpickr */
    .flatpickr-input {
        background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/></svg>') no-repeat right 0.75rem center/16px 16px;
        cursor: pointer;
    }

    @media (max-width: 768px) {
        .profile-row {
            flex-direction: column;
        }

        .avatar-section {
            border-right: none;
            border-bottom: 1px solid #e3e6f0;
            min-height: auto;
        }

        .form-section {
            padding: 1.5rem;
        }
    }
</style>

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

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('profileForm');
        const dateInputDisplay = document.getElementById('date_of_birth_display');
        const dateInputHidden = document.getElementById('date_of_birth_hidden');

        // Khởi tạo flatpickr
        flatpickr(dateInputDisplay, {
            dateFormat: 'd/m/Y', // Hiển thị DD/MM/YYYY
            altInput: false,
            altFormat: 'd/m/Y',
            maxDate: 'today', // Không cho chọn ngày trong tương lai
            allowInput: false, // Không cho phép nhập tay
            onChange: function(selectedDates, dateStr) {
                // Cập nhật input ẩn với định dạng YYYY-MM-DD
                if (selectedDates.length > 0) {
                    const date = selectedDates[0];
                    const ymd = date.getFullYear() + '-' +
                        ('0' + (date.getMonth() + 1)).slice(-2) + '-' +
                        ('0' + date.getDate()).slice(-2);
                    dateInputHidden.value = ymd;
                } else {
                    dateInputHidden.value = '';
                }
            }
        });

        // Validate form khi submit
        form.addEventListener('submit', (e) => {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');

            // Xác thực mật khẩu
            const password = document.getElementById('password').value;
            if (password && password.length < 6) {
                e.preventDefault();
                document.getElementById('password').classList.add('is-invalid');
                document.getElementById('password').nextElementSibling.textContent = 'Mật khẩu phải có ít nhất 6 ký tự.';
            }

            // Xác thực số điện thoại
            const phoneNumber = document.getElementById('phone_number').value;
            if (phoneNumber && !/^\d{10,11}$/.test(phoneNumber)) {
                e.preventDefault();
                document.getElementById('phone_number').classList.add('is-invalid');
                document.getElementById('phone_number').nextElementSibling.textContent = 'Số điện thoại phải có 10-11 chữ số.';
            }

            // Xác thực ngày sinh
            const dateValue = dateInputHidden.value;
            if (dateValue) {
                const date = new Date(dateValue);
                if (isNaN(date.getTime()) || date > new Date()) {
                    e.preventDefault();
                    document.getElementById('date_of_birth_display').classList.add('is-invalid');
                    document.getElementById('date_of_birth_display').nextElementSibling.textContent = 'Ngày sinh không hợp lệ hoặc lớn hơn ngày hiện tại.';
                }
            }

            // Hiển thị spinner khi form hợp lệ
            if (form.checkValidity()) {
                const submitBtn = form.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.querySelector('.spinner-border').classList.remove('d-none');
            }
        }, false);

        // Xem trước avatar
        const avatarInput = document.getElementById('avatar');
        const avatarPreview = document.getElementById('avatarPreview');
        avatarInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    alert('Kích thước file không được vượt quá 5MB');
                    avatarInput.value = ''; // Xóa file đã chọn
                    return;
                }

                const reader = new FileReader();
                reader.onload = (e) => {
                    avatarPreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    });
</script>