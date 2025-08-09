<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<div class="container">
    <h1 class="mb-4">Tạo khóa học mới</h1>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo htmlspecialchars($_SESSION['message_type']); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <form id="createCourseForm" method="POST" action="/study_sharing/course/createCourseByTeacher" class="needs-validation" novalidate>
        <div class="mb-3">
            <label for="course_name" class="form-label">Tên khóa học <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="course_name" name="course_name" required>
            <div class="invalid-feedback">Vui lòng nhập tên khóa học.</div>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Mô tả</label>
            <textarea class="form-control" id="description" name="description" rows="5"></textarea>
        </div>

        <div class="mb-3">
            <label for="max_members" class="form-label">Số lượng thành viên tối đa <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="max_members" name="max_members" value="50" min="1" required>
            <div class="invalid-feedback">Vui lòng nhập số lượng thành viên tối đa (lớn hơn 0).</div>
        </div>

        <div class="mb-3">
            <label for="start_date" class="form-label">Ngày bắt đầu</label>
            <input type="date" class="form-control" id="start_date" name="start_date">
        </div>

        <div class="mb-3">
            <label for="end_date" class="form-label">Ngày kết thúc</label>
            <input type="date" class="form-control" id="end_date" name="end_date">
        </div>

        <div class="mb-3">
            <label for="learn_link" class="form-label">Link học tập</label>
            <input type="url" class="form-control" id="learn_link" name="learn_link" placeholder="https://example.com">
            <div class="invalid-feedback">Vui lòng nhập URL hợp lệ.</div>
        </div>

        <div class="mb-3">
            <label for="documents" class="form-label">Chọn tài liệu liên quan (tùy chọn)</label>
            <select class="form-select" id="documents" name="documents[]" multiple>
                <?php if (!empty($availableDocuments)): ?>
                    <?php foreach ($availableDocuments as $doc): ?>
                        <option value="<?php echo htmlspecialchars($doc['document_id']); ?>">
                            <?php echo htmlspecialchars($doc['title']) . " (" . htmlspecialchars($doc['file_path']) . ")"; ?>
                        </option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option disabled>Không có tài liệu nào để chọn</option>
                <?php endif; ?>
            </select>
            <div class="form-text">Sử dụng thanh tìm kiếm để chọn nhiều tài liệu.</div>
        </div>

        <button type="submit" class="btn btn-primary">
            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
            Tạo khóa học
        </button>
    </form>
</div>

<!-- Nạp Select2 CSS và JS từ CDN -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    // Khởi tạo Select2 cho trường tài liệu
    document.addEventListener('DOMContentLoaded', function() {
        $('#documents').select2({
            placeholder: "Tìm và chọn tài liệu",
            allowClear: true,
            width: '100%'
        });
    });

    // Client-side validation và xử lý submit form
    document.getElementById('createCourseForm').addEventListener('submit', function(e) {
        e.preventDefault();
        let form = this;

        // Validate end_date >= start_date
        let startDate = document.getElementById('start_date').value;
        let endDate = document.getElementById('end_date').value;
        if (startDate && endDate && new Date(endDate) < new Date(startDate)) {
            e.stopPropagation();
            let endDateInput = document.getElementById('end_date');
            endDateInput.classList.add('is-invalid');
            endDateInput.nextElementSibling.textContent = 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu';
            form.classList.add('was-validated');
            return;
        }

        if (form.checkValidity()) {
            let submitButton = form.querySelector('button[type="submit"]');
            let spinner = submitButton.querySelector('.spinner-border');
            submitButton.disabled = true;
            spinner.classList.remove('d-none');

            let formData = new FormData(form);
            fetch('/study_sharing/course/createCourseByTeacher', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    let messageDiv = document.createElement('div');
                    messageDiv.className = `alert alert-${data.success ? 'success' : 'danger'} alert-dismissible fade show`;
                    messageDiv.innerHTML = `${data.message} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
                    form.parentNode.insertBefore(messageDiv, form);

                    if (data.success) {
                        form.reset();
                        form.classList.remove('was-validated');
                        $('#documents').val(null).trigger('change'); // Reset Select2
                    }
                })
                .catch(error => {
                    let messageDiv = document.createElement('div');
                    messageDiv.className = 'alert alert-danger alert-dismissible fade show';
                    messageDiv.innerHTML = `Lỗi: ${error.message} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
                    form.parentNode.insertBefore(messageDiv, form);
                })
                .finally(() => {
                    submitButton.disabled = false;
                    spinner.classList.add('d-none');
                });
        } else {
            form.classList.add('was-validated');
        }
    });
</script>