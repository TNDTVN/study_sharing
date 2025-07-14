<?php
$categories = $categories ?? [];
$courses = $courses ?? [];
$tags = $tags ?? [];
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script src="https://unpkg.com/jszip@3.10.1/dist/jszip.min.js"></script>
<script src="https://unpkg.com/docx-preview@latest/dist/docx-preview.js"></script>
<style>
    .autocomplete-container {
        position: relative;
    }

    .autocomplete-dropdown {
        position: absolute;
        z-index: 1000;
        width: 100%;
        max-height: 200px;
        overflow-y: auto;
        background: white;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        margin-top: 5px;
        display: none;
    }

    .autocomplete-item {
        padding: 8px 12px;
        cursor: pointer;
    }

    .autocomplete-item:hover {
        background-color: #f8f9fa;
    }

    .autocomplete-item.selected .tick {
        color: green;
        margin-left: 10px;
    }

    .file-upload-label {
        display: block;
        padding: 1rem;
        border: 2px dashed #dee2e6;
        border-radius: 0.375rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
    }

    .file-upload-label:hover {
        border-color: #86b7fe;
        background-color: #f8f9fa;
    }

    .preview-container {
        margin-top: 1rem;
        display: none;
        /* Ẩn mặc định */
    }

    .preview-content {
        max-height: 500px;
        min-height: 0px;
        overflow-y: auto;
        padding: 1rem;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
    }

    .preview-content canvas {
        max-width: 100%;
        margin: 0 auto;
        display: block;
        border-bottom: 1px solid #dee2e6;
        margin-bottom: 1rem;
    }

    .preview-content .docx-wrapper {
        margin: 0 auto;
        max-width: 100%;
        text-align: left;
        /* Đảm bảo nội dung DOCX không bị căn giữa */
    }
</style>

<h1 class="mb-4 text-primary"><i class="bi bi-file-earmark-text me-2"></i>Tải lên tài liệu mới</h1>

<!-- Message Display -->
<div id="uploadMessage"></div>

<!-- Upload Form -->
<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Thông tin tài liệu</h5>
    </div>
    <div class="card-body">
        <form id="uploadForm" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="documentTitle" class="form-label">Tiêu đề <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="documentTitle" name="title" required>
                        <div class="invalid-feedback">Vui lòng nhập tiêu đề.</div>
                    </div>
                    <div class="mb-3">
                        <label for="documentCategory" class="form-label">Danh mục</label>
                        <select class="form-select" id="documentCategory" name="category_id">
                            <option value="0">Không chọn</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>">
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="documentCourse" class="form-label">Khóa học</label>
                        <select class="form-select" id="documentCourse" name="course_id">
                            <option value="">Không chọn</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['course_id']; ?>">
                                    <?php echo htmlspecialchars($course['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="documentDescription" class="form-label">Mô tả</label>
                        <textarea class="form-control" id="documentDescription" rows="4" style="height: 123px;" name="description"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="documentVisibility" class="form-label">Chế độ hiển thị <span class="text-danger">*</span></label>
                        <select class="form-select" id="documentVisibility" name="visibility" required>
                            <option value="public">Công khai</option>
                            <option value="private">Riêng tư</option>
                        </select>
                        <div class="invalid-feedback">Vui lòng chọn chế độ hiển thị.</div>
                    </div>
                </div>
            </div>
            <div class="mb-4 autocomplete-container">
                <label for="documentTags" class="form-label">Thẻ (click để chọn thẻ từ danh sách)</label>
                <input type="text" class="form-control" id="documentTags" name="tags" placeholder="Click để chọn thẻ..." readonly>
                <ul class="autocomplete-dropdown list-unstyled m-0">
                    <?php foreach ($tags as $tag): ?>
                        <li class="autocomplete-item" data-value="<?php echo htmlspecialchars($tag['tag_name']); ?>">
                            <?php echo htmlspecialchars($tag['tag_name']); ?>
                            <span class="tick d-none"><i class="bi bi-check"></i></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="mb-4">
                <label for="documentFile" class="form-label">Tệp tài liệu <span class="text-danger">*</span></label>
                <label for="documentFile" class="file-upload-label">
                    <i class="bi bi-cloud-arrow-up fs-3"></i>
                    <div class="file-upload-text">Nhấn để tải lên tệp (PDF, DOCX, PPTX)</div>
                    <div id="fileName" class="text-primary mt-2 fw-medium"></div>
                </label>
                <input type="file" class="form-control d-none" id="documentFile" name="file" accept=".pdf,.docx,.pptx" required>
                <div class="invalid-feedback">Vui lòng chọn tệp tài liệu.</div>
                <div id="previewContainer" class="preview-container">
                    <div id="previewMessage" class="text-muted"></div>
                    <div id="previewContent" class="preview-content"></div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                Tải lên tài liệu
            </button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('uploadForm');
        const tagsInput = document.getElementById('documentTags');
        const tagsDropdown = tagsInput.nextElementSibling;
        const fileInput = document.getElementById('documentFile');
        const fileNameDisplay = document.getElementById('fileName');
        const previewContainer = document.getElementById('previewContainer');
        const previewMessage = document.getElementById('previewMessage');
        const previewContent = document.getElementById('previewContent');

        // Initialize tags
        let selectedTags = [];
        let isDropdownVisible = false;

        function updateTagsInput() {
            tagsInput.value = selectedTags.join(', ');
        }

        function toggleTagsDropdown(show) {
            isDropdownVisible = show;
            tagsDropdown.style.display = show ? 'block' : 'none';
        }

        tagsInput.addEventListener('click', () => {
            if (!isDropdownVisible) {
                toggleTagsDropdown(true);
            }
        });

        tagsDropdown.querySelectorAll('.autocomplete-item').forEach(item => {
            item.addEventListener('click', () => {
                const tag = item.dataset.value;
                if (selectedTags.includes(tag)) {
                    selectedTags = selectedTags.filter(t => t !== tag);
                    item.querySelector('.tick').classList.add('d-none');
                } else {
                    selectedTags.push(tag);
                    item.querySelector('.tick').classList.remove('d-none');
                }
                updateTagsInput();
            });
        });

        document.addEventListener('click', (e) => {
            if (!tagsInput.contains(e.target) && !tagsDropdown.contains(e.target)) {
                toggleTagsDropdown(false);
            }
        });

        // Handle file input and preview
        fileInput.addEventListener('change', () => {
            const file = fileInput.files[0];
            const fileName = file ? file.name : '';
            fileNameDisplay.textContent = fileName || '';
            previewContainer.style.display = 'none'; // Ẩn ô xem trước mặc định
            previewContent.innerHTML = '';
            previewMessage.textContent = '';

            if (file) {
                const fileExt = file.name.split('.').pop().toLowerCase();
                previewContainer.style.display = 'block'; // Hiện ô xem trước khi có tệp

                if (fileExt === 'pdf') {
                    previewMessage.textContent = 'Đang tải bản xem trước PDF...';
                    const fileReader = new FileReader();
                    fileReader.onload = function() {
                        const typedArray = new Uint8Array(this.result);
                        pdfjsLib.getDocument(typedArray).promise.then(pdf => {
                            previewMessage.textContent = '';
                            const numPages = pdf.numPages;
                            for (let pageNum = 1; pageNum <= numPages; pageNum++) {
                                pdf.getPage(pageNum).then(page => {
                                    const canvas = document.createElement('canvas');
                                    canvas.style.marginBottom = '1rem';
                                    previewContent.appendChild(canvas);
                                    const context = canvas.getContext('2d');
                                    const viewport = page.getViewport({
                                        scale: 1.0
                                    });
                                    canvas.height = viewport.height;
                                    canvas.width = viewport.width;
                                    page.render({
                                        canvasContext: context,
                                        viewport: viewport
                                    }).promise.then(() => {
                                        if (pageNum === numPages) {
                                            previewMessage.textContent = 'Bản xem trước toàn bộ tài liệu PDF';
                                        }
                                    });
                                });
                            }
                        }).catch(error => {
                            previewMessage.textContent = 'Lỗi khi tải bản xem trước PDF: ' + error.message;
                        });
                    };
                    fileReader.readAsArrayBuffer(file);
                } else if (fileExt === 'docx') {
                    previewMessage.textContent = 'Đang tải bản xem trước DOCX...';
                    const fileReader = new FileReader();
                    fileReader.onload = function() {
                        const arrayBuffer = this.result;
                        previewContent.innerHTML = '';
                        docx.renderAsync(arrayBuffer, previewContent).then(() => {
                            previewMessage.textContent = 'Bản xem trước tài liệu DOCX';
                        }).catch(error => {
                            previewMessage.textContent = 'Lỗi khi tải bản xem trước DOCX: ' + error.message;
                        });
                    };
                    fileReader.readAsArrayBuffer(file);
                } else if (fileExt === 'pptx') {
                    previewMessage.textContent = 'Tệp PPTX không hỗ trợ xem trước, nhưng bạn vẫn có thể tải lên.';
                } else {
                    previewMessage.textContent = 'Định dạng tệp không được hỗ trợ xem trước.';
                }
            } else {
                previewContainer.style.display = 'none'; // Ẩn ô xem trước nếu không có tệp
            }
        });

        // Handle form submission
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const submitButton = form.querySelector('button[type="submit"]');
            const spinner = submitButton.querySelector('.spinner-border');
            const messageDiv = document.getElementById('uploadMessage');

            if (!form.checkValidity()) {
                event.stopPropagation();
                form.classList.add('was-validated');
                return;
            }

            submitButton.disabled = true;
            spinner.classList.remove('d-none');

            const formData = new FormData(form);
            fetch('/study_sharing/document/create', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    messageDiv.innerHTML = `<div class="alert alert-${data.success ? 'success' : 'danger'} mt-3">${data.message}</div>`;
                    if (data.success) {
                        form.reset();
                        form.classList.remove('was-validated');
                        fileNameDisplay.textContent = '';
                        previewContainer.style.display = 'none';
                        previewContent.innerHTML = '';
                        previewMessage.textContent = '';
                        selectedTags = [];
                        updateTagsInput();
                        tagsDropdown.querySelectorAll('.tick').forEach(tick => tick.classList.add('d-none'));
                        if (data.redirect) {
                            setTimeout(() => {
                                window.location.href = data.redirect;
                            }, 2000);
                        }
                    }
                })
                .catch(error => {
                    messageDiv.innerHTML = '<div class="alert alert-danger mt-3">Lỗi server, vui lòng thử lại!</div>';
                })
                .finally(() => {
                    submitButton.disabled = false;
                    spinner.classList.add('d-none');
                });

            form.classList.add('was-validated');
        });
    });
</script>