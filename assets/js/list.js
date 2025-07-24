document.addEventListener('DOMContentLoaded', function() {
        'use strict';

        // Xử lý form filter (giữ nguyên)
        const form = document.getElementById('documentFilterForm');
        if (form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                } else {
                    form.classList.add('was-validated');
                }
            });

            const selects = form.querySelectorAll('select');
            selects.forEach(select => {
                select.addEventListener('change', function() {
                    form.submit();
                });
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