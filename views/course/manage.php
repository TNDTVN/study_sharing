<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$title = "Quản lý khóa học";

$accountStmt = $pdo->prepare("SELECT account_id, username FROM accounts ORDER BY username");
$accountStmt->execute();
$accounts = $accountStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-1 px-3">
    <h1 class="mb-4 text-primary"><i class="bi bi-book me-2"></i> Quản lý khóa học</h1>

    <div class="d-flex justify-content-between mb-4">
        <form class="input-group w-75" method="GET" action="/study_sharing/AdminCourse/manage">
            <input type="text" class="form-control" name="keyword" placeholder="Tìm kiếm theo tên khóa học hoặc mô tả" value="<?php echo htmlspecialchars($_GET['keyword'] ?? ''); ?>" aria-label="Tìm kiếm khóa học">
            <select class="form-select" name="category_id">
                <option value="0">Tất cả danh mục</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['category_id']; ?>" <?php echo ($category_id ?? 0) == $category['category_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['category_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select class="form-select" name="status">
                <option value="">Tất cả trạng thái</option>
                <option value="open" <?php echo ($status ?? '') === 'open' ? 'selected' : ''; ?>>Mở</option>
                <option value="in_progress" <?php echo ($status ?? '') === 'in_progress' ? 'selected' : ''; ?>>Đang học</option>
                <option value="closed" <?php echo ($status ?? '') === 'closed' ? 'selected' : ''; ?>>Đã đóng</option>
            </select>
            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Tìm</button>
        </form>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCourseModal" onclick="console.log('Nút thêm khóa học được bấm')"><i class="bi bi-plus-circle"></i> Thêm khóa học</button>
    </div>

    <?php if (!empty($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th scope="col">STT</th>
                    <th scope="col">Tên khóa học</th>
                    <th scope="col">Người tạo</th>
                    <th scope="col">Số thành viên</th>
                    <th scope="col">Trạng thái</th>
                    <th scope="col">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($courses)): ?>
                    <tr>
                        <td colspan="6" class="text-center">Không tìm thấy khóa học nào!</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($courses as $index => $course): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($offset + $index + 1); ?></td>
                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($course['username'] ?? 'N/A'); ?></td>
                            <td><?php echo $course['member_count'] ?? 0; ?></td>
                            <td><?php echo htmlspecialchars($course['status']); ?></td>
                            <td>
                                <button class="btn btn-outline-warning btn-sm edit-btn" data-bs-toggle="modal" data-bs-target="#editCourseModal"
                                    onclick="fillEditModal(<?php echo htmlspecialchars(json_encode($course)); ?>)">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <button class="btn btn-outline-danger btn-sm delete-btn" onclick="deleteCourse(<?php echo (int)$course['course_id']; ?>)">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <nav aria-label="Course pagination">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&keyword=<?php echo urlencode($keyword ?? ''); ?>&category_id=<?php echo $category_id ?? 0; ?>&status=<?php echo urlencode($status ?? ''); ?>">Trước</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&keyword=<?php echo urlencode($keyword ?? ''); ?>&category_id=<?php echo $category_id ?? 0; ?>&status=<?php echo urlencode($status ?? ''); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&keyword=<?php echo urlencode($keyword ?? ''); ?>&category_id=<?php echo $category_id ?? 0; ?>&status=<?php echo urlencode($status ?? ''); ?>">Sau</a>
                </li>
            </ul>
        </nav>
    </div>

    <div class="modal fade" id="addCourseModal" tabindex="-1" aria-labelledby="addCourseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addCourseModalLabel">Thêm khóa học mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addCourseForm" method="POST" action="/study_sharing/AdminCourse/admin_add" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="course_name" class="form-label">Tên khóa học <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="course_name" name="course_name" required>
                                    <div class="invalid-feedback">Vui lòng nhập tên khóa học.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="creator_id" class="form-label">Người tạo <span class="text-danger">*</span></label>
                                    <select class="form-control" id="creator_id" name="creator_id" required>
                                        <option value="">-- Chọn người tạo --</option>
                                        <?php foreach ($accounts as $account): ?>
                                            <option value="<?php echo $account['account_id']; ?>">
                                                <?php echo htmlspecialchars($account['username']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Vui lòng chọn người tạo.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="max_members" class="form-label">Số thành viên tối đa <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="max_members" name="max_members" min="1" value="50" required>
                                    <div class="invalid-feedback">Vui lòng nhập số thành viên tối đa (lớn hơn 0).</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Mô tả</label>
                                    <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="learn_link" class="form-label">Link học tập</label>
                                    <input type="url" class="form-control" id="learn_link" name="learn_link" placeholder="https://example.com">
                                </div>
                                <div class="mb-3">
                                    <label for="status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                    <select class="form-control" id="status" name="status" required>
                                        <option value="open">Mở</option>
                                        <option value="in_progress">Đang học</option>
                                        <option value="closed">Đã đóng</option>
                                    </select>
                                    <div class="invalid-feedback">Vui lòng chọn trạng thái.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Ngày bắt đầu</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">Ngày kết thúc</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date">
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" id="addCourseSubmit">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            Thêm khóa học
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editCourseModal" tabindex="-1" aria-labelledby="editCourseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editCourseModalLabel">Chỉnh sửa khóa học</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editCourseForm" method="POST" action="/study_sharing/AdminCourse/admin_edit" class="needs-validation" novalidate>
                        <input type="hidden" id="edit_course_id" name="course_id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_course_name" class="form-label">Tên khóa học <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit_course_name" name="course_name" required>
                                    <div class="invalid-feedback">Vui lòng nhập tên khóa học.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_creator_id" class="form-label">Người tạo <span class="text-danger">*</span></label>
                                    <select class="form-control" id="edit_creator_id" name="creator_id" required>
                                        <option value="">-- Chọn người tạo --</option>
                                        <?php foreach ($accounts as $account): ?>
                                            <option value="<?php echo $account['account_id']; ?>">
                                                <?php echo htmlspecialchars($account['username']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Vui lòng chọn người tạo.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_max_members" class="form-label">Số thành viên tối đa <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="edit_max_members" name="max_members" min="1" required>
                                    <div class="invalid-feedback">Vui lòng nhập số thành viên tối đa (lớn hơn 0).</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_description" class="form-label">Mô tả</label>
                                    <textarea class="form-control" id="edit_description" name="description" rows="4"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_learn_link" class="form-label">Link học tập</label>
                                    <input type="url" class="form-control" id="edit_learn_link" name="learn_link" placeholder="https://example.com">
                                </div>
                                <div class="mb-3">
                                    <label for="edit_status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                    <select class="form-control" id="edit_status" name="status" required>
                                        <option value="open">Mở</option>
                                        <option value="in_progress">Đang học</option>
                                        <option value="closed">Đã đóng</option>
                                    </select>
                                    <div class="invalid-feedback">Vui lòng chọn trạng thái.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_start_date" class="form-label">Ngày bắt đầu</label>
                                    <input type="date" class="form-control" id="edit_start_date" name="start_date">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_end_date" class="form-label">Ngày kết thúc</label>
                                    <input type="date" class="form-control" id="edit_end_date" name="end_date">
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            Cập nhật khóa học
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        'use strict';
        const forms = document.querySelectorAll('.needs-validation');
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();

    document.getElementById('addCourseForm').addEventListener('submit', function(event) {
        event.preventDefault();
        const submitButton = document.getElementById('addCourseSubmit');
        const spinner = submitButton.querySelector('.spinner-border');

        if (!this.checkValidity()) {
            this.classList.add('was-validated');
            return;
        }

        spinner.classList.remove('d-none');
        submitButton.disabled = true;

        const formData = new FormData(this);
        console.log('Sending AJAX request to /study_sharing/AdminCourse/admin_add');
        fetch('/study_sharing/AdminCourse/admin_add', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers.get('content-type'));
                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                try {
                    const data = JSON.parse(text);
                    spinner.classList.add('d-none');
                    submitButton.disabled = false;
                    alert(data.message);
                    if (data.success) {
                        document.getElementById('addCourseForm').reset();
                        bootstrap.Modal.getInstance(document.getElementById('addCourseModal')).hide();
                        window.location.reload();
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    alert('Lỗi server: Phản hồi không phải JSON');
                    spinner.classList.add('d-none');
                    submitButton.disabled = false;
                }
            })
            .catch(error => {
                spinner.classList.add('d-none');
                submitButton.disabled = false;
                console.error('Fetch error:', error);
                alert('Lỗi server khi thêm khóa học.');
            });
    });

    function fillEditModal(course) {
        document.getElementById('edit_course_id').value = course.course_id;
        document.getElementById('edit_course_name').value = course.course_name;
        document.getElementById('edit_description').value = course.description || '';
        document.getElementById('edit_creator_id').value = course.creator_id || '';
        document.getElementById('edit_max_members').value = course.max_members || 50;
        document.getElementById('edit_learn_link').value = course.learn_link || '';
        document.getElementById('edit_start_date').value = course.start_date || '';
        document.getElementById('edit_end_date').value = course.end_date || '';
        document.getElementById('edit_status').value = course.status || 'open';
    }

    function deleteCourse(courseId) {
        console.log('Deleting course with ID:', courseId);
        if (!Number.isInteger(courseId) || courseId <= 0) {
            alert('ID khóa học không hợp lệ!');
            return;
        }
        if (confirm('Bạn chắc chắn muốn xóa khóa học này?')) {
            const requestBody = JSON.stringify({
                course_id: courseId
            });
            console.log('Request body:', requestBody);
            fetch('/study_sharing/AdminCourse/admin_delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: requestBody
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    if (data.success) {
                        alert(data.message);
                        window.location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('Lỗi server khi xóa khóa học.');
                });
        }
    }
</script>