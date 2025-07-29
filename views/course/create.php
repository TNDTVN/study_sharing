<?php
$title = "Tạo khóa học mới";

ob_start(); // Bắt đầu ghi output để đưa vào layout
?>

<h2 class="text-primary mb-4"><i class="bi bi-journal-plus me-2"></i> Tạo khóa học mới</h2>

<form method="post" action="/study_sharing/Course/createCourseByTeacher" class="row g-3">
    <div class="col-md-6">
        <label for="course_name" class="form-label">Tên khóa học</label>
        <input type="text" class="form-control" id="course_name" name="course_name" required>
    </div>

    <div class="col-md-6">
        <label for="max_members" class="form-label">Số lượng học viên tối đa</label>
        <input type="number" class="form-control" id="max_members" name="max_members" value="50">
    </div>

    <div class="col-md-12">
        <label for="description" class="form-label">Mô tả</label>
        <textarea class="form-control" id="description" name="description" rows="4"></textarea>
    </div>

    <div class="col-md-6">
        <label for="start_date" class="form-label">Ngày bắt đầu</label>
        <input type="date" class="form-control" id="start_date" name="start_date">
    </div>

    <div class="col-md-6">
        <label for="end_date" class="form-label">Ngày kết thúc</label>
        <input type="date" class="form-control" id="end_date" name="end_date">
    </div>

    <div class="col-md-12">
        <label for="learn_link" class="form-label">Link học (nếu có)</label>
        <input type="text" class="form-control" id="learn_link" name="learn_link">
    </div>

    <div class="col-12 text-end">
        <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i> Tạo khóa học</button>
    </div>
</form>

<?php
$content = ob_get_clean(); // Lấy nội dung form để đưa vào layout

// Gọi layout chung cho giáo viên (bạn có thể thay bằng admin_layout.php nếu là admin)
require_once __DIR__ . '/../layouts/layout.php';
?>