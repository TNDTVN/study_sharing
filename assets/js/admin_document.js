document.addEventListener('DOMContentLoaded', () => {
    const editButtons = document.querySelectorAll('.edit-btn');
    const deleteButtons = document.querySelectorAll('.delete-btn');
    const viewButtons = document.querySelectorAll('.view-btn');
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

    // Initialize PDF.js worker
    if (typeof pdfjsLib !== 'undefined') {
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
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
    const addTags = addTagsInput ? initializeTags(addTagsInput, addTagsDropdown) : null;
    const editTags = editTagsInput ? initializeTags(editTagsInput, editTagsDropdown) : null;

    // Xử lý hiển thị tên file
    if (addFileInput && addFileNameDisplay) {
        addFileInput.addEventListener('change', () => {
            const fileName = addFileInput.files.length > 0 ? addFileInput.files[0].name : '';
            addFileNameDisplay.textContent = fileName || '';
        });
    }

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

    // Hàm hiển thị file preview
    let currentScale = 1.0;
    let pdfDoc = null;
    let totalPages = 0;

    function renderAllPages(container) {
        container.innerHTML = '';
        for (let pageNum = 1; pageNum <= totalPages; pageNum++) {
            pdfDoc.getPage(pageNum).then(function(page) {
                const viewport = page.getViewport({ scale: currentScale });
                const canvas = document.createElement('canvas');
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
            pdfjsLib.getDocument(fileUrl).promise.then(function(pdf) {
                pdfDoc = pdf;
                totalPages = pdf.numPages;
                renderAllPages(container);
                updatePageInfo();
                container.scrollTop = 0;
            }).catch(function(error) {
                console.error('Error loading PDF:', error);
                container.innerHTML = '<p>Tài liệu không thể hiển thị. <a href="' + fileUrl + '" download>Vui lòng tải xuống để xem.</a></p>';
            });
        } else if (fileExt === 'docx') {
            if (typeof docx === 'undefined' || typeof docx.renderAsync !== 'function') {
                console.error('docx-preview library is not loaded');
                container.innerHTML = '<p>Thư viện docx-preview không được tải. <a href="' + fileUrl + '" download>Tải xuống để xem.</a></p>';
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
            fetch('/study_sharing/convert_pptx_to_pdf.php?file=' + encodeURIComponent(fileUrl))
                .then(response => {
                    if (!response.ok || !response.headers.get('content-type').includes('application/json')) {
                        return response.text().then(text => {
                            throw new Error('Phản hồi không phải JSON: ' + text);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        pdfjsLib.getDocument(data.pdfPath).promise.then(function(pdf) {
                            pdfDoc = pdf;
                            totalPages = pdf.numPages;
                            renderAllPages(container);
                            updatePageInfo();
                            container.scrollTop = 0;
                        }).catch(function(error) {
                            console.error('Error loading converted PDF:', error);
                            container.innerHTML = '<p>Tài liệu không thể hiển thị. <a href="' + fileUrl + '" download>Vui lòng tải xuống để xem.</a></p>';
                        });
                    } else {
                        console.error('Conversion failed:', data.message);
                        container.innerHTML = '<p>Tài liệu PPTX không thể hiển thị: ' + data.message + '. <a href="' + fileUrl + '" download>Vui lòng tải xuống để xem.</a></p>';
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

    // Xử lý nút xem chi tiết
    viewButtons.forEach(button => {
        button.addEventListener('click', () => {
            const id = button.getAttribute('data-id');
            const title = button.getAttribute('data-title');
            const description = button.getAttribute('data-description');
            const categoryName = button.getAttribute('data-category-name');
            const courseName = button.getAttribute('data-course-name');
            const uploaderName = button.getAttribute('data-uploader-name');
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
            document.getElementById('viewDocumentUploader').textContent = uploaderName || 'Ẩn danh';
            document.getElementById('viewDocumentUploadDate').textContent = uploadDate;
            document.getElementById('viewDocumentVisibility').textContent = visibility === 'public' ? 'Công khai' : 'Riêng tư';
            document.getElementById('viewDocumentTags').innerHTML = tags.length > 0 ? tags.map(tag => `<span class="badge bg-secondary me-1">${tag}</span>`).join('') : '<span class="text-muted">Không có thẻ</span>';
            document.getElementById('viewDocumentFileName').textContent = fileName || 'Không có tệp';
            document.getElementById('viewDocumentFileType').textContent = fileExt ? fileExt.toUpperCase() : 'Không xác định';
            const downloadLink = document.getElementById('viewDocumentDownloadLink');
            downloadLink.href = filePath || '#';
            downloadLink.style.display = filePath ? 'inline-block' : 'none';

            // Load document preview
            const documentContainer = document.getElementById('viewDocumentContainer');
            currentScale = 1.0;
            pdfDoc = null;
            totalPages = 0;
            if (filePath && fileExt) {
                loadDocumentPreview(filePath, fileExt, documentContainer);
            } else {
                documentContainer.innerHTML = '<p>Không có tệp để hiển thị.</p>';
            }

            // Zoom and Fit Width controls
            const zoomInBtn = document.getElementById('zoomInBtn');
            const zoomOutBtn = document.getElementById('zoomOutBtn');
            const fitWidthBtn = document.getElementById('fitWidthBtn');

            zoomInBtn.addEventListener('click', () => {
                if (pdfDoc && (fileExt === 'pdf' || fileExt === 'pptx')) {
                    currentScale += 0.2;
                    renderAllPages(documentContainer);
                }
            });

            zoomOutBtn.addEventListener('click', () => {
                if (pdfDoc && (fileExt === 'pdf' || fileExt === 'pptx')) {
                    currentScale = Math.max(0.5, currentScale - 0.2);
                    renderAllPages(documentContainer);
                }
            });

            fitWidthBtn.addEventListener('click', () => {
                if (pdfDoc && (fileExt === 'pdf' || fileExt === 'pptx')) {
                    const containerWidth = documentContainer.clientWidth;
                    pdfDoc.getPage(1).then(page => {
                        const viewport = page.getViewport({ scale: 1.0 });
                        currentScale = (containerWidth - 20) / viewport.width;
                        renderAllPages(documentContainer);
                    });
                }
            });

            const viewModal = new bootstrap.Modal(document.getElementById('viewDocumentModal'));
            viewModal.show();
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