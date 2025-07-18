document.addEventListener('DOMContentLoaded', () => {
    // Kiểm tra Bootstrap
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap is not loaded');
        alert('Giao diện không hoạt động đúng do thiếu thư viện Bootstrap.');
        return;
    }

    const editButtons = document.querySelectorAll('.edit-btn');
    const deleteButtons = document.querySelectorAll('.delete-btn');
    const viewButtons = document.querySelectorAll('.view-btn');
    const versionButtons = document.querySelectorAll('.version-btn');
    const updateVersionButtons = document.querySelectorAll('.update-version-btn');
    const editForm = document.getElementById('editDocumentForm');
    const updateVersionForm = document.getElementById('updateVersionForm');
    const editTagsInput = document.getElementById('editDocumentTags');
    const editTagsDropdown = editTagsInput?.nextElementSibling;
    const editFileInput = document.getElementById('editDocumentFile');
    const editFileNameDisplay = document.getElementById('currentFileName');
    const updateVersionFileInput = document.getElementById('updateVersionFile');
    const updateVersionFileNameDisplay = document.getElementById('updateVersionFileName');
    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
    const successModalMessage = document.getElementById('successModalMessage');

    // Initialize PDF.js worker
    if (typeof pdfjsLib !== 'undefined') {
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
    } else {
        console.error('PDF.js is not loaded');
    }

    // Hàm hiển thị modal thành công
    function showSuccessModal(message, callback) {
        successModalMessage.textContent = message;
        successModal.show();
        const modalElement = document.getElementById('successModal');
        modalElement.addEventListener('hidden.bs.modal', callback, { once: true });
    }

    // Hàm khởi tạo tag cho input
    function initializeTags(input, dropdown) {
        let selectedTags = [];
        let isDropdownVisible = false;

        function updateInput() {
            input.value = selectedTags.join(', ');
        }

        function toggleDropdown(show) {
            isDropdownVisible = show;
            dropdown.style.display = show ? 'block' : 'none';
        }

        input.addEventListener('click', () => {
            if (!isDropdownVisible) {
                toggleDropdown(true);
            }
        });

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
            });
        });

        document.addEventListener('click', (e) => {
            if (!input.contains(e.target) && !dropdown.contains(e.target)) {
                toggleDropdown(false);
            }
        });

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

    // Khởi tạo tags
    const editTags = editTagsInput ? initializeTags(editTagsInput, editTagsDropdown) : null;

    // Xử lý hiển thị tên file
    if (editFileInput && editFileNameDisplay) {
        editFileInput.addEventListener('change', () => {
            const file = editFileInput.files[0];
            if (file) {
                const validExtensions = ['pdf', 'docx', 'pptx'];
                const ext = file.name.split('.').pop().toLowerCase();
                if (!validExtensions.includes(ext)) {
                    alert('Chỉ hỗ trợ các định dạng PDF, DOCX, PPTX.');
                    editFileInput.value = '';
                    editFileNameDisplay.textContent = editFileNameDisplay.dataset.currentFile || '';
                } else if (file.size > 10 * 1024 * 1024) {
                    alert('Kích thước tệp không được vượt quá 10MB.');
                    editFileInput.value = '';
                    editFileNameDisplay.textContent = editFileNameDisplay.dataset.currentFile || '';
                } else {
                    editFileNameDisplay.textContent = file.name;
                }
            }
        });
    }

    if (updateVersionFileInput && updateVersionFileNameDisplay) {
        updateVersionFileInput.addEventListener('change', () => {
            const file = updateVersionFileInput.files[0];
            if (file) {
                const validExtensions = ['pdf', 'docx', 'pptx'];
                const ext = file.name.split('.').pop().toLowerCase();
                if (!validExtensions.includes(ext)) {
                    alert('Chỉ hỗ trợ các định dạng PDF, DOCX, PPTX.');
                    updateVersionFileInput.value = '';
                    updateVersionFileNameDisplay.textContent = '';
                } else if (file.size > 10 * 1024 * 1024) {
                    alert('Kích thước tệp không được vượt quá 10MB.');
                    updateVersionFileInput.value = '';
                    updateVersionFileNameDisplay.textContent = '';
                } else {
                    updateVersionFileNameDisplay.textContent = file.name;
                }
            }
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

    // Xử lý submit form chỉnh sửa
    editForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const title = document.getElementById('editDocumentTitle').value;
        const file = editFileInput.files[0];
        if (!editForm.checkValidity()) {
            editForm.classList.add('was-validated');
            return;
        } else if (title.length > 255) {
            alert('Tiêu đề không được vượt quá 255 ký tự.');
            return;
        } else if (file && file.size > 10 * 1024 * 1024) {
            alert('Kích thước tệp không được vượt quá 10MB.');
            return;
        }

        const formData = new FormData(editForm);
        fetch(editForm.action, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showSuccessModal(data.message, () => {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                });
            } else {
                alert(data.message || 'Lỗi khi cập nhật tài liệu.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Lỗi kết nối hoặc server khi cập nhật tài liệu: ' + error.message);
        });
    });

    // Xử lý nút xóa
    deleteButtons.forEach(button => {
        button.addEventListener('click', () => {
            const documentId = button.getAttribute('data-id');
            if (confirm('Bạn có chắc chắn muốn xóa tài liệu này?')) {
                fetch('/study_sharing/document/delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `document_id=${documentId}`
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showSuccessModal(data.message, () => {
                            location.reload();
                        });
                    } else {
                        alert(data.message || 'Lỗi không xác định khi xóa tài liệu.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Lỗi kết nối hoặc server khi xóa tài liệu: ' + error.message);
                });
            }
        });
    });

    // Xử lý nút cập nhật phiên bản
    updateVersionButtons.forEach(button => {
        button.addEventListener('click', () => {
            const documentId = button.getAttribute('data-id');
            document.getElementById('updateVersionDocumentId').value = documentId;
            updateVersionFileNameDisplay.textContent = '';
            updateVersionFileInput.value = '';
            document.getElementById('updateVersionChangeNote').value = '';
        });
    });

    // Xử lý submit form cập nhật phiên bản
    updateVersionForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const file = updateVersionFileInput.files[0];
        if (!updateVersionForm.checkValidity()) {
            updateVersionForm.classList.add('was-validated');
            return;
        } else if (file && file.size > 10 * 1024 * 1024) {
            alert('Kích thước tệp không được vượt quá 10MB.');
            return;
        }

        const formData = new FormData(updateVersionForm);
        fetch(updateVersionForm.action, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showSuccessModal(data.message, () => {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                });
            } else {
                alert(data.message || 'Lỗi khi cập nhật phiên bản.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Lỗi kết nối hoặc server khi cập nhật phiên bản: ' + error.message);
        });
    });

    // Xử lý nút xem chi tiết
    let pdfDoc = null;
    let totalPages = 0;

    viewButtons.forEach(button => {
        button.addEventListener('click', () => {
            const id = button.getAttribute('data-id');
            const title = button.getAttribute('data-title');
            const description = button.getAttribute('data-description');
            const categoryName = button.getAttribute('data-category-name');
            const courseName = button.getAttribute('data-course-name');
            const uploadDate = button.getAttribute('data-upload-date');
            const visibility = button.getAttribute('data-visibility');
            const tags = button.getAttribute('data-tags') ? button.getAttribute('data-tags').split(',').map(t => t.trim()) : [];
            const fileName = button.getAttribute('data-file-name');
            const filePath = button.getAttribute('data-file-path');
            const fileExt = button.getAttribute('data-file-ext');

            document.getElementById('viewDocumentTitle').textContent = title;
            document.getElementById('viewDocumentDescription').textContent = description || 'Không có mô tả';
            document.getElementById('viewDocumentCategory').textContent = categoryName || 'Không có';
            document.getElementById('viewDocumentCourse').textContent = courseName || 'Không có';
            document.getElementById('viewDocumentUploadDate').textContent = uploadDate;
            document.getElementById('viewDocumentVisibility').textContent = visibility === 'public' ? 'Công khai' : 'Riêng tư';
            document.getElementById('viewDocumentTags').innerHTML = tags.length > 0 ? tags.map(tag => `<span class="badge bg-secondary me-1">${tag}</span>`).join('') : '<span class="text-muted">Không có thẻ</span>';
            document.getElementById('viewDocumentFileName').textContent = fileName || 'Không có tệp';
            document.getElementById('viewDocumentFileType').textContent = fileExt ? fileExt.toUpperCase() : 'Không xác định';
            const downloadLink = document.getElementById('viewDocumentDownloadLink');
            downloadLink.href = filePath || '#';
            downloadLink.style.display = filePath ? 'inline-block' : 'none';

            const documentContainer = document.getElementById('viewDocumentContainer');
            pdfDoc = null;
            totalPages = 0;

            fetch(filePath, { method: 'HEAD' })
                .then(response => {
                    const fileSize = response.headers.get('content-length');
                    if (fileSize && parseInt(fileSize) > 50 * 1024 * 1024) {
                        documentContainer.innerHTML = '<p>Tệp quá lớn để hiển thị. <a href="' + filePath + '" download>Vui lòng tải xuống để xem.</a></p>';
                        return;
                    }
                    loadDocumentPreview(filePath, fileExt, documentContainer);
                })
                .catch(error => {
                    console.error('Error checking file size:', error);
                    documentContainer.innerHTML = '<p>Lỗi khi kiểm tra tệp. <a href="' + filePath + '" download>Vui lòng tải xuống để xem.</a></p>';
                });

            const viewModal = new bootstrap.Modal(document.getElementById('viewDocumentModal'));
            viewModal.show();
        });
    });

    // Reset trạng thái khi modal đóng
    document.getElementById('viewDocumentModal').addEventListener('hidden.bs.modal', () => {
        pdfDoc = null;
        totalPages = 0;
        document.getElementById('viewDocumentContainer').innerHTML = '';
        document.getElementById('pageInfo').textContent = '';
    });

    // Reset trạng thái khi modal lịch sử phiên bản đóng
    document.getElementById('versionModal').addEventListener('hidden.bs.modal', () => {
        const versionModal = document.getElementById('versionModal');
        const tbody = document.getElementById('versionTableBody');
        tbody.innerHTML = '';
        versionModal.classList.remove('show');
        versionModal.style.display = 'none';
        document.body.classList.remove('modal-open');
        const modalBackdrop = document.querySelector('.modal-backdrop');
        if (modalBackdrop) {
            modalBackdrop.remove();
        }
    });

    // Xử lý nút xem lịch sử phiên bản
    versionButtons.forEach(button => {
        button.addEventListener('click', () => {
            const versions = JSON.parse(button.getAttribute('data-versions'));
            const tbody = document.getElementById('versionTableBody');
            tbody.innerHTML = '';
            versions.forEach(version => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${version.version_number}</td>
                    <td><a href="/study_sharing/Uploads/${version.file_path}" target="_blank">${version.file_path}</a></td>
                    <td>${version.change_note}</td>
                    <td>${new Date(version.created_at).toLocaleDateString('vi-VN')}</td>
                `;
                tbody.appendChild(row);
            });
            const versionModal = new bootstrap.Modal(document.getElementById('versionModal'));
            versionModal.show();
        });
    });

    // Hàm hiển thị file preview
    function renderPage(pageNum, container) {
        pdfDoc.getPage(pageNum).then(page => {
            const viewport = page.getViewport({ scale: 1.0 });
            const canvas = document.createElement('canvas');
            canvas.setAttribute('data-page', pageNum);
            const context = canvas.getContext('2d');
            canvas.height = viewport.height;
            canvas.width = viewport.width;
            canvas.style.maxWidth = '100%';
            canvas.style.margin = '0 auto';
            canvas.style.display = 'block';
            canvas.style.borderBottom = '1px solid #dee2e6';
            canvas.style.marginBottom = '10px';
            container.appendChild(canvas);
            page.render({ canvasContext: context, viewport: viewport });
        });
    }

    function updatePageInfo() {
        const pageInfo = document.getElementById('pageInfo');
        if (pdfDoc && totalPages > 0) {
            pageInfo.textContent = `Tổng số trang: ${totalPages}`;
        } else {
            pageInfo.textContent = '';
        }
    }

    function loadDocumentPreview(fileUrl, fileExt, container) {
        container.innerHTML = '<div class="d-flex justify-content-center align-items-center h-100"><div class="text-center text-muted"><i class="bi bi-file-earmark-text display-4 mb-3"></i><p>Đang tải xem trước tài liệu...</p></div></div>';

        if (fileExt === 'pdf') {
            if (typeof pdfjsLib === 'undefined') {
                container.innerHTML = '<p>Thư viện PDF.js không được tải. <a href="' + fileUrl + '" download>Vui lòng tải xuống để xem.</a></p>';
                return;
            }
            pdfjsLib.getDocument(fileUrl).promise.then(function(pdf) {
                pdfDoc = pdf;
                totalPages = pdf.numPages;
                if (totalPages > 50) {
                    container.innerHTML = '<p>Tài liệu có quá nhiều trang để hiển thị. <a href="' + fileUrl + '" download>Vui lòng tải xuống để xem.</a></p>';
                    return;
                }
                container.innerHTML = '';
                for (let pageNum = 1; pageNum <= totalPages; pageNum++) {
                    renderPage(pageNum, container);
                }
                updatePageInfo();
                container.scrollTop = 0;
            }).catch(function(error) {
                console.error('Error loading PDF:', error);
                container.innerHTML = '<p>Tài liệu không thể hiển thị. <a href="' + fileUrl + '" download>Vui lòng tải xuống để xem.</a></p>';
            });
        } else if (fileExt === 'docx') {
            if (typeof docx === 'undefined' || typeof docx.renderAsync !== 'function') {
                console.error('docx-preview library is not loaded');
                container.innerHTML = '<p>Thư viện docx-preview không được tải. <a href="' + fileUrl + '" download>Vui lòng tải xuống để xem.</a></p>';
                return;
            }
            fetch(fileUrl)
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                    return response.arrayBuffer();
                })
                .then(buffer => {
                    docx.renderAsync(buffer, container, null, {
                        ignoreWidth: false,
                        ignoreHeight: false,
                        breakPages: true,
                        renderHeaders: true,
                        renderFooters: true,
                        useBase64URL: true
                    }).then(() => {
                        container.scrollTop = 0;
                        document.getElementById('pageInfo').textContent = '';
                    }).catch(error => {
                        console.error('Error rendering DOCX:', error);
                        container.innerHTML = '<p>Tài liệu DOCX không thể hiển thị. <a href="' + fileUrl + '" download>Vui lòng tải xuống để xem.</a></p>';
                    });
                })
                .catch(error => {
                    console.error('Error loading DOCX:', error);
                    container.innerHTML = '<p>Tài liệu không thể hiển thị. <a href="' + fileUrl + '" download>Vui lòng tải xuống để xem.</a></p>';
                });
        } else if (fileExt === 'pptx') {
            if (typeof JSZip === 'undefined') {
                console.error('JSZip library is not loaded');
                container.innerHTML = '<p>Thư viện JSZip không được tải. <a href="' + fileUrl + '" download>Vui lòng tải xuống để xem.</a></p>';
                return;
            }
            fetch('/study_sharing/convert_pptx_to_pdf.php?file=' + encodeURIComponent(fileUrl))
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error(`HTTP error! Status: ${response.status}, Response: ${text}`);
                        });
                    }
                    const contentType = response.headers.get('content-type');
                    if (!contentType.includes('application/json')) {
                        throw new Error('Phản hồi không phải JSON');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        pdfjsLib.getDocument(data.pdfPath).promise.then(function(pdf) {
                            pdfDoc = pdf;
                            totalPages = pdf.numPages;
                            if (totalPages > 50) {
                                container.innerHTML = '<p>Tài liệu có quá nhiều trang để hiển thị. <a href="' + fileUrl + '" download>Vui lòng tải xuống để xem.</a></p>';
                                return;
                            }
                            container.innerHTML = '';
                            for (let pageNum = 1; pageNum <= totalPages; pageNum++) {
                                renderPage(pageNum, container);
                            }
                            updatePageInfo();
                            container.scrollTop = 0;
                        }).catch(function(error) {
                            console.error('Error loading converted PDF:', error);
                            container.innerHTML = '<p>Tài liệu PPTX không thể hiển thị: Lỗi khi tải PDF đã chuyển đổi. <a href="' + fileUrl + '" download>Vui lòng tải xuống để xem.</a></p>';
                        });
                    } else {
                        console.error('Conversion failed:', data.message);
                        container.innerHTML = '<p>Tài liệu PPTX không thể hiển thị: ' + (data.message || 'Lỗi không xác định') + '. <a href="' + fileUrl + '" download>Vui lòng tải xuống để xem.</a></p>';
                    }
                })
                .catch(error => {
                    console.error('Error converting PPTX:', error);
                    container.innerHTML = '<p>Tài liệu PPTX không thể hiển thị: ' + error.message + '. <a href="' + fileUrl + '" download>Vui lòng tải xuống để xem.</a></p>';
                });
        } else {
            container.innerHTML = '<p>Định dạng file không được hỗ trợ. <a href="' + fileUrl + '" download>Vui lòng tải xuống để xem.</a></p>';
        }
    }
});