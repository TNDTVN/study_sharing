document.addEventListener('DOMContentLoaded', () => {
    const editButtons = document.querySelectorAll('.edit-btn');
    const deleteButtons = document.querySelectorAll('.delete-btn');
    const addForm = document.getElementById('addDocumentForm');
    const editForm = document.getElementById('editDocumentForm');

    // Xử lý nút chỉnh sửa
    editButtons.forEach(button => {
        button.addEventListener('click', () => {
            const id = button.getAttribute('data-id');
            const title = button.getAttribute('data-title');
            const description = button.getAttribute('data-description');
            const categoryId = button.getAttribute('data-category-id');
            const courseId = button.getAttribute('data-course-id');
            const visibility = button.getAttribute('data-visibility');
            const tags = JSON.parse(button.getAttribute('data-tags') || '[]');

            document.getElementById('editDocumentId').value = id;
            document.getElementById('editDocumentTitle').value = title;
            document.getElementById('editDocumentDescription').value = description;
            document.getElementById('editDocumentCategory').value = categoryId || '0';
            document.getElementById('editDocumentCourse').value = courseId || '';
            document.getElementById('editDocumentVisibility').value = visibility;

            // Xử lý select multiple cho tags
            const tagSelect = document.getElementById('editDocumentTags');
            Array.from(tagSelect.options).forEach(option => {
                option.selected = tags.includes(option.value);
            });
        });
    });

    // Xử lý nút xóa
    deleteButtons.forEach(button => {
        button.addEventListener('click', () => {
            const documentId = button.getAttribute('data-id');
            if (confirm('Bạn có chắc chắn muốn xóa tài liệu này?')) {
                fetch('/study_sharing/AdminDocument/admin_delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `document_id=${documentId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Lỗi server khi xóa tài liệu!');
                });
            }
        });
    });

    // Xử lý validation form thêm
    addForm.addEventListener('submit', (e) => {
        if (!addForm.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        addForm.classList.add('was-validated');
    });

    // Xử lý validation form chỉnh sửa
    editForm.addEventListener('submit', (e) => {
        if (!editForm.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        editForm.classList.add('was-validated');
    });
});