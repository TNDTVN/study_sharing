document.addEventListener('DOMContentLoaded', () => {
    // Form validation
    ['addCategoryForm', 'editCategoryForm'].forEach(formId => {
        const form = document.getElementById(formId);
        form.addEventListener('submit', (e) => {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Edit button click handler
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('editCategoryId').value = btn.dataset.id;
            document.getElementById('editCategoryName').value = btn.dataset.name;
            document.getElementById('editCategoryDescription').value = btn.dataset.description;
        });
    });

    // Delete button click handler
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            if (confirm('Bạn có chắc muốn xóa danh mục này?')) {
                const formData = new FormData();
                formData.append('category_id', btn.dataset.id);
                fetch('/study_sharing/category/deleteCategory', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Phản hồi mạng không thành công: ' + response.status);
                        }
                        return response.json();
                    })
                    .then(data => {
                        alert(data.message); // Hiển thị thông báo từ server
                        if (data.success) {
                            window.location.reload(); // Tải lại trang nếu xóa thành công
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Lỗi server: ' + error.message);
                    });
            }
        });
    });
});