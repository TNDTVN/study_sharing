<?php
$categories = $categories ?? [];
$courses = $courses ?? [];
$tags = $tags ?? [];
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script src="https://unpkg.com/jszip@3.10.1/dist/jszip.min.js"></script>
<script src="https://unpkg.com/docx-preview@latest/dist/docx-preview.js"></script>
<link rel="stylesheet" href="/study_sharing/assets/css/create_document.css">

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

<script src="/study_sharing/assets/js/create_document.js"></script>