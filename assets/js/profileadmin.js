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