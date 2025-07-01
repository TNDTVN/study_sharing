<?php
$title = "Hồ sơ quản trị viên";
$avatar = $user && $user['avatar'] ? '/study_sharing/assets/images/' . htmlspecialchars($user['avatar']) : '/study_sharing/assets/images/profile.png';
$role = $user['role'] ?? 'admin';
$created_at = $user['created_at'] ?? 'Không xác định';
$date_of_birth_display = $user['date_of_birth'] ? date('d/m/Y', strtotime($user['date_of_birth'])) : '';
?>

<style>
    .content {
        padding-top: 0px;
    }

    .admin-profile-container {
        width: 100%;
        margin: 0;
    }

    .profile-header {
        background: linear-gradient(135deg, #dc3545 0%, #a71d2a 100%);
        /* Màu đỏ cho admin */
        color: white;
        padding: 1.5rem;
        border-bottom: none;
    }

    .profile-row {
        display: flex;
        flex-wrap: wrap;
        min-height: 100%;
    }

    .admin-profile-container .avatar-section {
        background-color: #f8f9fc;
        padding: 2rem 1.5rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        border-right: 1px solid #e3e6f0;
        flex: 1;
        min-height: 100%;
    }

    .admin-profile-container .avatar-img {
        width: 180px;
        height: 180px;
        object-fit: cover;
        border: 5px solid white;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-bottom: 1.5rem;
    }

    .admin-profile-container .custom-file-upload {
        display: inline-block;
        padding: 8px 16px;
        cursor: pointer;
        background: linear-gradient(135deg, #dc3545 0%, #a71d2a 100%);
        color: white;
        border-radius: 6px;
        transition: all 0.3s;
        font-weight: 500;
        border: none;
        margin-bottom: 1rem;
    }

    .admin-profile-container .custom-file-upload:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .admin-profile-container .info-item {
        margin-bottom: 1.25rem;
        width: 100%;
        padding: 0.75rem;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        text-align: center;
    }

    .admin-profile-container .info-item strong {
        color: #5a5c69;
    }

    .admin-profile-container .form-section {
        padding: 2rem;
        flex: 1;
    }

    .admin-profile-container .form-control,
    .admin-profile-container .form-select {
        border-radius: 6px;
        padding: 10px 15px;
        border: 1px solid #d1d3e2;
    }

    .admin-profile-container .form-control:focus,
    .admin-profile-container .form-select:focus {
        border-color: #bac8f3;
        box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
    }

    .admin-profile-container .btn-primary {
        background: linear-gradient(135deg, #dc3545 0%, #a71d2a 100%);
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: 600;
        letter-spacing: 0.5px;
        transition: all 0.3s;
    }

    .admin-profile-container .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .admin-profile-container .form-text {
        color: #858796;
        font-size: 0.85rem;
    }

    .admin-profile-container .invalid-feedback {
        font-size: 0.85rem;
    }

    .admin-profile-container .flatpickr-input {
        background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/></svg>') no-repeat right 0.75rem center/16px 16px;
        cursor: pointer;
    }

    @media (max-width: 768px) {
        .admin-profile-container .profile-row {
            flex-direction: column;
        }

        .admin-profile-container .avatar-section {
            border-right: none;
            border-bottom: 1px solid #e3e6f0;
            min-height: auto;
        }

        .admin-profile-container .form-section {
            padding: 1.5rem;
        }
    }

    .container {
        padding: 0% !important;
    }
</style>

<div class="admin-profile-container">
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <div class="profile-header">
        <h1 class="mb-0"><i class="bi bi-person-circle me-2"></i> Hồ sơ quản trị viên</h1>
    </div>
    <form id="profileForm" method="POST" action="/study_sharing/admin/updateProfile" enctype="multipart/form-data" class="needs-validation" novalidate>
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
                    <span class="d-block mt-1">Quản trị viên</span>
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
            dateFormat: 'd/m/Y',
            altInput: false,
            altFormat: 'd/m/Y',
            maxDate: 'today',
            allowInput: false,
            onChange: function(selectedDates, dateStr) {
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

        // Validate form
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            form.classList.add('was-validated');

            const password = document.getElementById('password').value;
            if (password && password.length < 6) {
                document.getElementById('password').classList.add('is-invalid');
                document.getElementById('password').nextElementSibling.textContent = 'Mật khẩu phải có ít nhất 6 ký tự.';
                return;
            }

            const phoneNumber = document.getElementById('phone_number').value;
            if (phoneNumber && !/^\d{10,11}$/.test(phoneNumber)) {
                document.getElementById('phone_number').classList.add('is-invalid');
                document.getElementById('phone_number').nextElementSibling.textContent = 'Số điện thoại phải có 10-11 chữ số.';
                return;
            }

            const dateValue = dateInputHidden.value;
            if (dateValue) {
                const date = new Date(dateValue);
                if (isNaN(date.getTime()) || date > new Date()) {
                    document.getElementById('date_of_birth_display').classList.add('is-invalid');
                    document.getElementById('date_of_birth_display').nextElementSibling.textContent = 'Ngày sinh không hợp lệ hoặc lớn hơn ngày hiện tại.';
                    return;
                }
            }

            if (!form.checkValidity()) {
                return;
            }

            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.querySelector('.spinner-border').classList.remove('d-none');

            const formData = new FormData(form);
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    alert(data.message);
                    window.location.href = data.redirect;
                } else {
                    alert(data.message);
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                }
            } catch (error) {
                alert('Đã xảy ra lỗi: ' + error.message);
            } finally {
                submitBtn.disabled = false;
                submitBtn.querySelector('.spinner-border').classList.add('d-none');
            }
        });

        const avatarInput = document.getElementById('avatar');
        const avatarPreview = document.getElementById('avatarPreview');
        avatarInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    alert('Kích thước file không được vượt quá 5MB');
                    avatarInput.value = '';
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