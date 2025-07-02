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