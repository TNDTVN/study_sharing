<?php
// File: views/course/manage.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<div class="container">
    <h1 class="mb-4">Quản lý khóa học</h1>
    <?php if (!empty($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'info'; ?>">
            <?php echo $_SESSION['message'];
            unset($_SESSION['message'], $_SESSION['message_type']); ?>
        </div>
    <?php endif; ?>

    <form method="GET" class="row g-2 align-items-end mb-4">
        <div class="col-md-3">
            <input type="text" name="keyword" class="form-control" placeholder="Tên khóa học hoặc mô tả"
                value="<?php echo htmlspecialchars($_GET['keyword'] ?? ''); ?>">
        </div>
        <div class="col-md-3">
            <select name="category_id" class="form-select">
                <option value="0">Tất cả danh mục</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['category_id']; ?>"
                        <?php if (($category_id ?? 0) == $category['category_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($category['category_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select">
                <option value="">Tất cả trạng thái</option>
                <option value="open" <?php if (($status ?? '') === 'open') echo 'selected'; ?>>Mở</option>
                <option value="in_progress" <?php if (($status ?? '') === 'in_progress') echo 'selected'; ?>>Đang học</option>
                <option value="closed" <?php if (($status ?? '') === 'closed') echo 'selected'; ?>>Đã đóng</option>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100">Lọc</button>
        </div>
        <div class="col-md-2 text-end">
            <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                <i class="bi bi-plus-circle"></i> Thêm khóa học
            </button>
        </div>
    </form>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Tên khóa học</th>
                <th>Người tạo</th>
                <th>Số thành viên</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($courses)): ?>
                <?php foreach ($courses as $course): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                        <td><?php echo htmlspecialchars($course['username'] ?? ''); ?></td>
                        <td><?php echo $course['member_count'] ?? 0; ?></td>
                        <td><?php echo htmlspecialchars($course['status']); ?></td>
                        <td>
                            <a href="/study_sharing/AdminCourse/edit?course_id=<?php echo $course['course_id']; ?>" class="btn btn-outline-warning btn-sm edit-btn"> <i class="fa fa-pencil"></i></a>
                            <form action="/study_sharing/AdminCourse/delete" method="POST" class="d-inline" onsubmit="return confirm('Bạn chắc chắn muốn xóa?')">
                                <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm delete-btn"> <i class="fa fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center">Không có khóa học nào.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
        <nav>
            <ul class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&keyword=<?php echo urlencode($keyword ?? ''); ?>&category_id=<?php echo $category_id ?? 0; ?>&status=<?php echo urlencode($status ?? ''); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>

    <!-- Modal thêm khóa học -->
    <div class="modal fade" id="addCourseModal" tabindex="-1" aria-labelledby="addCourseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addCourseModalLabel">Thêm khóa học mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addCourseForm" method="POST" action="/study_sharing/AdminCourse/store" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="course_name" class="form-label">Tên khóa học</label>
                                    <input type="text" class="form-control" id="course_name" name="course_name" required>
                                    <div class="invalid-feedback">Vui lòng nhập tên khóa học.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Danh mục</label>
                                    <select class="form-select" id="category_id" name="category_id" required>
                                        <option value="">-- Chọn danh mục --</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['category_id']; ?>">
                                                <?php echo htmlspecialchars($category['category_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Vui lòng chọn danh mục.</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Mô tả</label>
                                    <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="status" class="form-label">Trạng thái</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="open">Mở</option>
                                        <option value="in_progress">Đang học</option>
                                        <option value="closed">Đã đóng</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary w-100">
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                Thêm khóa học
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>