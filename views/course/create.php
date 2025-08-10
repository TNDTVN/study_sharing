<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<div class="container py-4">
    <div class="card border-0 shadow-lg">
        <div class="card-header bg-primary text-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="h4 mb-0"><i class="bi bi-book me-2"></i> Tạo khóa học mới</h2>
                <a href="/study_sharing/course/manage" class="btn btn-light btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Quay lại danh sách khóa học
                </a>
            </div>
        </div>

        <div class="card-body p-4">
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo htmlspecialchars($_SESSION['message_type']); ?> alert-dismissible fade show" role="alert">
                    <i class="bi <?php echo $_SESSION['message_type'] === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
            <?php endif; ?>

            <form id="createCourseForm" method="POST" action="/study_sharing/course/createCourseByTeacher" class="needs-validation" novalidate>
                <div class="row g-3">
                    <!-- Tên khóa học -->
                    <div class="col-md-12">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="course_name" name="course_name" placeholder="Nhập tên khóa học" required>
                            <label for="course_name" class="form-label">Tên khóa học <span class="text-danger">*</span></label>
                            <div class="invalid-feedback">Vui lòng nhập tên khóa học.</div>
                        </div>
                    </div>

                    <!-- Mô tả -->
                    <div class="col-md-12">
                        <div class="form-floating mb-3">
                            <textarea class="form-control" id="description" name="description" placeholder="Nhập mô tả khóa học" style="height: 120px;"></textarea>
                            <label for="description" class="form-label">Mô tả khóa học</label>
                        </div>
                    </div>

                    <!-- Thông tin cơ bản -->
                    <div class="col-md-6">
                        <div class="form-floating mb-3">
                            <input type="number" class="form-control" id="max_members" name="max_members" value="50" min="1" placeholder="Nhập số lượng" required>
                            <label for="max_members" class="form-label">Số lượng thành viên tối đa <span class="text-danger">*</span></label>
                            <div class="invalid-feedback">Vui lòng nhập số lượng hợp lệ (≥1).</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating mb-3">
                            <input type="url" class="form-control" id="learn_link" name="learn_link" placeholder="https://example.com">
                            <label for="learn_link" class="form-label">Link học tập</label>
                            <div class="invalid-feedback">Vui lòng nhập URL hợp lệ.</div>
                        </div>
                    </div>

                    <!-- Ngày bắt đầu/kết thúc -->
                    <div class="col-md-6">
                        <div class="form-floating mb-3">
                            <input type="date" class="form-control" id="start_date" name="start_date" placeholder="Ngày bắt đầu">
                            <label for="start_date" class="form-label">Ngày bắt đầu</label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating mb-3">
                            <input type="date" class="form-control" id="end_date" name="end_date" placeholder="Ngày kết thúc">
                            <label for="end_date" class="form-label">Ngày kết thúc</label>
                            <div class="invalid-feedback end-date-feedback d-none">Ngày kết thúc phải ≥ ngày bắt đầu</div>
                        </div>
                    </div>

                    <!-- Tài liệu liên quan -->
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="documents" class="form-label">Tài liệu liên quan</label>
                            <select class="form-select select2-documents" id="documents" name="documents[]" multiple>
                                <?php if (!empty($availableDocuments)): ?>
                                    <?php foreach ($availableDocuments as $doc): ?>
                                        <option value="<?php echo htmlspecialchars($doc['document_id']); ?>">
                                            <?php echo htmlspecialchars($doc['title']) . " (" . htmlspecialchars(basename($doc['file_path'])) . ")"; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option disabled>Không có tài liệu nào để chọn</option>
                                <?php endif; ?>
                            </select>
                            <div class="form-text">Nhấn để tìm kiếm và chọn nhiều tài liệu</div>
                        </div>
                    </div>

                    <!-- Nút submit -->
                    <div class="col-12 mt-2">
                        <button type="submit" class="btn btn-primary btn-lg px-4 py-2">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            <i class="bi bi-save me-2"></i> Tạo khóa học
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- CSS và JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
<style>
    .select2-container--default .select2-selection--multiple {
        min-height: 45px;
        border: 1px solid #ced4da;
        padding: 5px;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #e9ecef;
        border: 1px solid #ced4da;
    }

    .form-floating>label {
        padding: 0.8rem 1rem;
    }

    .form-control,
    .form-select {
        padding: 0.8rem 1rem;
    }

    .card {
        border-radius: 0.5rem;
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // Khởi tạo Select2
        $('.select2-documents').select2({
            placeholder: "Tìm kiếm tài liệu...",
            allowClear: true,
            width: '100%',
            dropdownParent: $('#createCourseForm')
        });

        // Validate form
        $('#createCourseForm').on('submit', function(e) {
            e.preventDefault();
            let form = this;

            // Validate ngày
            let startDate = $('#start_date').val();
            let endDate = $('#end_date').val();
            if (startDate && endDate && new Date(endDate) < new Date(startDate)) {
                $('#end_date').addClass('is-invalid');
                $('.end-date-feedback').removeClass('d-none');
                return false;
            } else {
                $('#end_date').removeClass('is-invalid');
                $('.end-date-feedback').addClass('d-none');
            }

            if (form.checkValidity()) {
                let submitButton = $(form).find('button[type="submit"]');
                let spinner = submitButton.find('.spinner-border');
                submitButton.prop('disabled', true);
                spinner.removeClass('d-none');

                let formData = new FormData(form);
                fetch('/study_sharing/course/createCourseByTeacher', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Hiển thị thông báo thành công
                            let alertHtml = `
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle me-2"></i>
                                ${data.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        `;
                            $(form).before(alertHtml);

                            // Reset form nếu thành công
                            form.reset();
                            $(form).removeClass('was-validated');
                            $('.select2-documents').val(null).trigger('change');
                        } else {
                            // Hiển thị thông báo lỗi
                            let alertHtml = `
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                ${data.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        `;
                            $(form).before(alertHtml);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        let alertHtml = `
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Đã xảy ra lỗi khi gửi dữ liệu
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `;
                        $(form).before(alertHtml);
                    })
                    .finally(() => {
                        submitButton.prop('disabled', false);
                        spinner.addClass('d-none');
                    });
            } else {
                $(form).addClass('was-validated');
            }
        });
    });
</script>