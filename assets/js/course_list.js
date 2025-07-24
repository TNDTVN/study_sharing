document.addEventListener('DOMContentLoaded', function() {
        'use strict';

        // Xử lý form filter
        const form = document.getElementById('courseFilterForm');
        if (form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        }

        // Thêm class active cho nút sắp xếp được chọn
        const sortButtons = document.querySelectorAll('.sort-options .btn');
        sortButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                sortButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
            });
        });
    });

document.addEventListener('DOMContentLoaded', function() {
    // Xử lý khi click nút đăng nhập trong empty state
    document.querySelectorAll('[data-bs-target="#loginModal"]').forEach(btn => {
        btn.addEventListener('click', function() {
            // Có thể thêm logic lưu URL hiện tại để redirect sau khi đăng nhập
            localStorage.setItem('redirectAfterLogin', window.location.href);
        });
    });
});