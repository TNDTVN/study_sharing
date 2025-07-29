<div class="container">
    <h1 class="mb-4">Quản lý khóa học</h1>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo htmlspecialchars($_SESSION['message_type']); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <!-- Form lọc -->
    <form method="GET" action="/study_sharing/course/manage" class="mb-4">
        <div class="row g-3">
            <div class="col-md-6">
                <input type="text" class="form-control" name="keyword" placeholder="Tìm kiếm khóa học..." value="<?php echo htmlspecialchars($keyword ?? ''); ?>">
            </div>
            <div class="col-md-4">
                <select class="form-control" name="status">
                    <option value="">Tất cả trạng thái</option>
                    <option value="open" <?php echo ($status ?? '') === 'open' ? 'selected' : ''; ?>>Mở</option>
                    <option value="in_progress" <?php echo ($status ?? '') === 'in_progress' ? 'selected' : ''; ?>>Đang diễn ra</option>
                    <option value="closed" <?php echo ($status ?? '') === 'closed' ? 'selected' : ''; ?>>Đóng</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Lọc</button>
            </div>
        </div>
    </form>

    <!-- Danh sách khóa học -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Danh sách khóa học</h5>
        </div>
        <div class="card-body">
            <?php if (empty($courses)): ?>
                <p class="text-muted">Không tìm thấy khóa học nào.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tên khóa học</th>
                                <th>Mô tả</th>
                                <th>Thành viên</th>
                                <th>Trạng thái</th>
                                <th>Ngày bắt đầu</th>
                                <th>Ngày kết thúc</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($course['description'], 0, 50)); ?>...</td>
                                    <td><?php echo $course['member_count']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php
                                                                echo $course['status'] === 'open' ? 'success' : ($course['status'] === 'in_progress' ? 'warning' : 'danger');
                                                                ?>">
                                            <i class="bi bi-<?php
                                                            echo $course['status'] === 'open' ? 'check-circle' : ($course['status'] === 'in_progress' ? 'play-circle' : 'ban');
                                                            ?>"></i>
                                            <?php echo $course['status'] === 'open' ? 'Mở' : ($course['status'] === 'in_progress' ? 'Đang diễn ra' : 'Đóng'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $course['start_date'] ? date('d/m/Y', strtotime($course['start_date'])) : '-'; ?></td>
                                    <td><?php echo $course['end_date'] ? date('d/m/Y', strtotime($course['end_date'])) : '-'; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info view-course-btn"
                                            data-course-id="<?php echo $course['course_id']; ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewCourseModal">
                                            <i class="bi bi-eye"></i> Xem
                                        </button>
                                        <button class="btn btn-sm btn-warning edit-course-btn"
                                            data-course-id="<?php echo $course['course_id']; ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editCourseModal">
                                            <i class="bi bi-pencil"></i> Sửa
                                        </button>
                                        <?php if ($course['status'] === 'closed'): ?>
                                            <button class="btn btn-sm btn-success request-open-btn"
                                                data-course-id="<?php echo $course['course_id']; ?>">
                                                <i class="bi bi-unlock"></i> Yêu cầu mở
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Phân trang -->
    <?php if ($totalPages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&keyword=<?php echo urlencode($keyword ?? ''); ?>&status=<?php echo urlencode($status ?? ''); ?>">Trước</a>
                    </li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&keyword=<?php echo urlencode($keyword ?? ''); ?>&status=<?php echo urlencode($status ?? ''); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&keyword=<?php echo urlencode($keyword ?? ''); ?>&status=<?php echo urlencode($status ?? ''); ?>">Sau</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>

    <!-- Modal xem chi tiết khóa học -->
    <div id="viewCourseModal" class="modal fade" tabindex="-1" aria-labelledby="viewCourseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="viewCourseModalLabel">Chi tiết khóa học</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="viewCourseMessage"></div>
                    <div id="courseDetails">
                        <p><strong>Tên khóa học:</strong> <span id="view_course_name"></span></p>
                        <p><strong>Mô tả:</strong> <span id="view_description"></span></p>
                        <p><strong>Số thành viên tối đa:</strong> <span id="view_max_members"></span></p>
                        <p><strong>Link học tập:</strong> <a id="view_learn_link" href="#" target="_blank"></a></p>
                        <p><strong>Ngày bắt đầu:</strong> <span id="view_start_date"></span></p>
                        <p><strong>Ngày kết thúc:</strong> <span id="view_end_date"></span></p>
                        <p><strong>Trạng thái:</strong> <span id="view_status"></span></p>
                        <p><strong>Ngày tạo:</strong> <span id="view_created_at"></span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal chỉnh sửa khóa học -->
    <div id="editCourseModal" class="modal fade" tabindex="-1" aria-labelledby="editCourseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editCourseModalLabel">Chỉnh sửa khóa học</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="editCourseMessage"></div>
                    <form id="editCourseForm" method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="course_id" id="edit_course_id">
                        <div class="mb-3">
                            <label for="edit_course_name" class="form-label">Tên khóa học <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_course_name" name="course_name" required>
                            <div class="invalid-feedback">Vui lòng nhập tên khóa học.</div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Mô tả</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="5"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_max_members" class="form-label">Số lượng thành viên tối đa <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit_max_members" name="max_members" min="1" required>
                            <div class="invalid-feedback">Vui lòng nhập số lượng thành viên tối đa (lớn hơn 0).</div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_learn_link" class="form-label">Link học tập</label>
                            <input type="url" class="form-control" id="edit_learn_link" name="learn_link" placeholder="https://example.com">
                            <div class="invalid-feedback">Vui lòng nhập URL hợp lệ.</div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_start_date" class="form-label">Ngày bắt đầu</label>
                            <input type="date" class="form-control" id="edit_start_date" name="start_date">
                        </div>
                        <div class="mb-3">
                            <label for="edit_end_date" class="form-label">Ngày kết thúc</label>
                            <input type="date" class="form-control" id="edit_end_date" name="end_date">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            Cập nhật
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Xem chi tiết khóa học
    document.querySelectorAll('.view-course-btn').forEach(button => {
        button.addEventListener('click', function() {
            const courseId = this.getAttribute('data-course-id');
            fetch('/study_sharing/course/getCourseDetails', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        course_id: courseId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('view_course_name').textContent = data.course.course_name;
                        document.getElementById('view_description').textContent = data.course.description || 'Không có mô tả';
                        document.getElementById('view_max_members').textContent = data.course.max_members || 'Không giới hạn';
                        const learnLink = document.getElementById('view_learn_link');
                        learnLink.href = data.course.learn_link || '#';
                        learnLink.textContent = data.course.learn_link || 'Không có link';
                        document.getElementById('view_start_date').textContent = data.course.start_date ? new Date(data.course.start_date).toLocaleDateString('vi-VN') : '-';
                        document.getElementById('view_end_date').textContent = data.course.end_date ? new Date(data.course.end_date).toLocaleDateString('vi-VN') : '-';
                        document.getElementById('view_status').textContent = data.course.status === 'open' ? 'Mở' :
                            data.course.status === 'in_progress' ? 'Đang diễn ra' : 'Đóng';
                        document.getElementById('view_created_at').textContent = data.course.created_at ? new Date(data.course.created_at).toLocaleDateString('vi-VN') : '-';
                        document.getElementById('viewCourseMessage').innerHTML = '';
                    } else {
                        document.getElementById('viewCourseMessage').innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                    }
                })
                .catch(error => {
                    document.getElementById('viewCourseMessage').innerHTML = `<div class="alert alert-danger">Lỗi: ${error.message}</div>`;
                });
        });
    });

    // Chỉnh sửa khóa học
    document.querySelectorAll('.edit-course-btn').forEach(button => {
        button.addEventListener('click', function() {
            const courseId = this.getAttribute('data-course-id');
            fetch('/study_sharing/course/getCourseDetails', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        course_id: courseId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('edit_course_id').value = data.course.course_id;
                        document.getElementById('edit_course_name').value = data.course.course_name;
                        document.getElementById('edit_description').value = data.course.description || '';
                        document.getElementById('edit_max_members').value = data.course.max_members || 50;
                        document.getElementById('edit_learn_link').value = data.course.learn_link || '';
                        document.getElementById('edit_start_date').value = data.course.start_date || '';
                        document.getElementById('edit_end_date').value = data.course.end_date || '';
                    } else {
                        document.getElementById('editCourseMessage').innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                    }
                })
                .catch(error => {
                    document.getElementById('editCourseMessage').innerHTML = `<div class="alert alert-danger">Lỗi: ${error.message}</div>`;
                });
        });
    });

    // Xử lý form chỉnh sửa
    document.getElementById('editCourseForm').addEventListener('submit', function(e) {
        e.preventDefault();
        if (this.checkValidity()) {
            let submitButton = this.querySelector('button[type="submit"]');
            let spinner = submitButton.querySelector('.spinner-border');
            submitButton.disabled = true;
            spinner.classList.remove('d-none');

            let formData = new FormData(this);
            fetch('/study_sharing/course/edit', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    let messageDiv = document.getElementById('editCourseMessage');
                    messageDiv.innerHTML = `<div class="alert alert-${data.success ? 'success' : 'danger'}">${data.message}</div>`;
                    if (data.success) {
                        setTimeout(() => location.reload(), 1000);
                    }
                })
                .finally(() => {
                    submitButton.disabled = false;
                    spinner.classList.add('d-none');
                });
        } else {
            this.classList.add('was-validated');
        }
    });


    document.querySelectorAll('.request-open-btn').forEach(button => {
        button.addEventListener('click', function() {
            const courseId = this.getAttribute('data-course-id');
            if (!courseId || courseId <= 0) {
                alert('ID khóa học không hợp lệ');
                return;
            }
            if (confirm('Bạn có chắc chắn muốn gửi yêu cầu mở khóa học này tới admin?')) {
                fetch('/study_sharing/course/requestOpenCourse', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            course_id: parseInt(courseId)
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        alert(data.message);
                        if (data.success) {
                            location.reload();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Lỗi khi gửi yêu cầu: ' + error.message);
                    });
            }
        });
    });
</script>