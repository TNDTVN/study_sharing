document.addEventListener('DOMContentLoaded', () => {
    const editButtons = document.querySelectorAll('.edit-btn');
    const deleteButtons = document.querySelectorAll('.delete-btn');
    const addForm = document.getElementById('addDocumentForm');
    const editForm = document.getElementById('editDocumentForm');
    const addTagsInput = document.getElementById('addDocumentTags');
    const editTagsInput = document.getElementById('editDocumentTags');
    const addTagsDropdown = addTagsInput?.nextElementSibling;
    const editTagsDropdown = editTagsInput?.nextElementSibling;
    const addFileInput = document.getElementById('addDocumentFile');
    const editFileInput = document.getElementById('editDocumentFile');
    const addFileNameDisplay = document.getElementById('addFileName');
    const editFileNameDisplay = document.getElementById('currentFileName');

    // Hàm khởi tạo tag cho input
    function initializeTags(input, dropdown) {
        let selectedTags = [];
        let isDropdownVisible = false;

        // Cập nhật giá trị input từ danh sách thẻ đã chọn
        function updateInput() {
            input.value = selectedTags.join(', ');
        }

        // Hiển thị/ẩn dropdown
        function toggleDropdown(show) {
            isDropdownVisible = show;
            dropdown.style.display = show ? 'block' : 'none';
        }

        // Xử lý khi click vào input
        input.addEventListener('click', () => {
            if (!isDropdownVisible) {
                toggleDropdown(true);
            }
        });

        // Xử lý click vào mục gợi ý
        dropdown.querySelectorAll('.autocomplete-item').forEach(item => {
            item.addEventListener('click', () => {
                const tag = item.dataset.value;
                if (selectedTags.includes(tag)) {
                    selectedTags = selectedTags.filter(t => t !== tag);
                    item.querySelector('.tick').classList.add('d-none');
                } else {
                    selectedTags.push(tag);
                    item.querySelector('.tick').classList.remove('d-none');
                }
                updateInput();
                // Không đóng dropdown
            });
        });

        // Ẩn dropdown khi click ra ngoài
        document.addEventListener('click', (e) => {
            if (!input.contains(e.target) && !dropdown.contains(e.target)) {
                toggleDropdown(false);
            }
        });

        // Hàm để set tags từ ngoài (cho modal chỉnh sửa)
        return {
            setTags: (tags) => {
                selectedTags = tags;
                updateInput();
                dropdown.querySelectorAll('.autocomplete-item').forEach(item => {
                    const tick = item.querySelector('.tick');
                    if (selectedTags.includes(item.dataset.value)) {
                        tick.classList.remove('d-none');
                    } else {
                        tick.classList.add('d-none');
                    }
                });
            }
        };
    }

    // Khởi tạo tags cho cả hai input
    const addTags = addTagsInput ? initializeTags(addTagsInput, addTagsDropdown) : null;
    const editTags = editTagsInput ? initializeTags(editTagsInput, editTagsDropdown) : null;

    // Xử lý hiển thị tên file khi chọn file trong modal thêm
    if (addFileInput && addFileNameDisplay) {
        addFileInput.addEventListener('change', () => {
            const fileName = addFileInput.files.length > 0 ? addFileInput.files[0].name : '';
            addFileNameDisplay.textContent = fileName || '';
        });
    }

    // Xử lý hiển thị tên file khi chọn file trong modal chỉnh sửa
    if (editFileInput && editFileNameDisplay) {
        editFileInput.addEventListener('change', () => {
            const fileName = editFileInput.files.length > 0 ? editFileInput.files[0].name : '';
            editFileNameDisplay.textContent = fileName || editFileNameDisplay.dataset.currentFile || '';
        });
    }

    // Xử lý nút chỉnh sửa
    editButtons.forEach(button => {
        button.addEventListener('click', () => {
            const id = button.getAttribute('data-id');
            const title = button.getAttribute('data-title');
            const description = button.getAttribute('data-description');
            const categoryId = button.getAttribute('data-category-id');
            const courseId = button.getAttribute('data-course-id');
            const visibility = button.getAttribute('data-visibility');
            const tags = button.getAttribute('data-tags') ? button.getAttribute('data-tags').split(',').map(t => t.trim()) : [];
            const fileName = button.getAttribute('data-file-name');

            document.getElementById('editDocumentId').value = id;
            document.getElementById('editDocumentTitle').value = title;
            document.getElementById('editDocumentDescription').value = description;
            document.getElementById('editDocumentCategory').value = categoryId || '0';
            document.getElementById('editDocumentCourse').value = courseId || '';
            document.getElementById('editDocumentVisibility').value = visibility;
            if (editTags) {
                editTags.setTags(tags);
            }
            if (editFileNameDisplay) {
                editFileNameDisplay.textContent = fileName || '';
                editFileNameDisplay.dataset.currentFile = fileName || '';
            }
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