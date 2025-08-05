<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$title = "Duyệt khóa học";

$courseStmt = $pdo->prepare("
    SELECT c.*, a.username 
    FROM courses c 
    LEFT JOIN accounts a ON c.creator_id = a.account_id 
    WHERE c.status = 'pending' 
    ORDER BY c.created_at DESC
");
$courseStmt->execute();
$courses = $courseStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-1 px-3">
    <h1 class="mb-4 text-primary"><i class="bi bi-check-circle me-2"></i> Duyệt khóa học</h1>

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
                    <th scope="col">Ngày tạo</th>
                    <th scope="col">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($courses)): ?>
                    <tr>
                        <td colspan="5" class="text-center">Không có khóa học nào đang chờ duyệt!</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($courses as $index => $course): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($index + 1); ?></td>
                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($course['username'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($course['created_at'] ?? 'Chưa xác định'); ?></td>
                            <td>
                                <button class="btn btn-outline-info btn-sm view-btn" title="Xem chi tiết khóa học" data-course-id="<?php echo $course['course_id']; ?>">
                                    <i class="fa fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-success btn-sm approve-btn" title="Duyệt khóa học" onclick="approveCourse(<?php echo (int)$course['course_id']; ?>)">
                                    <i class="fa fa-check"></i>
                                </button>
                                <button class="btn btn-outline-danger btn-sm cancel-btn" title="Hủy khóa học" data-bs-toggle="modal" data-bs-target="#cancelCourseModal" onclick="setCancelCourseId(<?php echo (int)$course['course_id']; ?>)">
                                    <i class="fa fa-times"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal xem chi tiết khóa học -->
    <div class="modal fade" id="courseDetailModal" tabindex="-1" aria-labelledby="courseDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="courseDetailModalLabel">Chi tiết khóa học</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="course-details">
                        <p><strong>ID:</strong> <span id="detail-course-id"></span></p>
                        <p><strong>Tên khóa học:</strong> <span id="detail-course-name"></span></p>
                        <p><strong>Mô tả:</strong> <span id="detail-description"></span></p>
                        <p><strong>Người tạo:</strong> <span id="detail-creator"></span></p>
                        <p><strong>Số thành viên tối đa:</strong> <span id="detail-max-members"></span></p>
                        <p><strong>Số thành viên hiện tại:</strong> <span id="detail-member-count"></span></p>
                        <p><strong>Link học:</strong> <span id="detail-learn-link"></span></p>
                        <p><strong>Ngày bắt đầu:</strong> <span id="detail-start-date"></span></p>
                        <p><strong>Ngày kết thúc:</strong> <span id="detail-end-date"></span></p>
                        <p><strong>Trạng thái:</strong> <span id="detail-status"></span></p>
                        <p><strong>Ngày tạo:</strong> <span id="detail-created-at"></span></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal hủy khóa học -->
    <div class="modal fade" id="cancelCourseModal" tabindex="-1" aria-labelledby="cancelCourseModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="cancelCourseModalLabel">Hủy khóa học</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="cancelCourseForm" class="needs-validation" novalidate>
                        <input type="hidden" id="cancel_course_id" name="course_id">
                        <div class="mb-3">
                            <label for="cancel_reason" class="form-label">Lý do hủy <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="cancel_reason" name="cancel_reason" rows="4" required></textarea>
                            <div class="invalid-feedback">Vui lòng nhập lý do hủy.</div>
                        </div>
                        <button type="submit" class="btn btn-danger w-100">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            Xác nhận hủy
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

    // Xử lý nút xem chi tiết
    document.querySelectorAll('.view-btn').forEach(button => {
        button.addEventListener('click', function() {
            const courseId = parseInt(this.getAttribute('data-course-id'));
            console.log('Fetching course details for ID:', courseId);

            if (!Number.isInteger(courseId) || courseId <= 0) {
                console.error('Invalid course ID:', courseId);
                alert('ID khóa học không hợp lệ!');
                return;
            }

            fetch('/study_sharing/AdminCourse/getCourseDetails', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        course_id: courseId
                    })
                })
                .then(response => {
                    console.log('View response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('View response data:', data);
                    if (data.success) {
                        document.getElementById('detail-course-id').textContent = data.course.course_id || '';
                        document.getElementById('detail-course-name').textContent = data.course.course_name || '';
                        document.getElementById('detail-description').textContent = data.course.description || 'Không có mô tả';
                        document.getElementById('detail-creator').textContent = data.course.username || 'N/A';
                        document.getElementById('detail-max-members').textContent = data.course.max_members || '50';
                        document.getElementById('detail-member-count').textContent = data.course.member_count || '0';
                        document.getElementById('detail-learn-link').textContent = data.course.learn_link || 'Không có link';
                        document.getElementById('detail-start-date').textContent = data.course.start_date || 'Chưa xác định';
                        document.getElementById('detail-end-date').textContent = data.course.end_date || 'Chưa xác định';
                        const statusMap = {
                            'open': 'Mở',
                            'in_progress': 'Đang học',
                            'closed': 'Đã đóng',
                            'pending': 'Chờ duyệt',
                            'cancelled': 'Đã hủy'
                        };
                        document.getElementById('detail-status').textContent = statusMap[data.course.status] || 'Không xác định';
                        document.getElementById('detail-created-at').textContent = data.course.created_at || 'Không xác định';

                        const modal = new bootstrap.Modal(document.getElementById('courseDetailModal'), {
                            backdrop: true
                        });
                        modal.show();
                    } else {
                        console.error('Error from server:', data.message);
                        alert('Lỗi: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('Đã xảy ra lỗi khi lấy chi tiết khóa học: ' + error.message);
                });
        });
    });

    // Hàm duyệt khóa học
    function approveCourse(courseId) {
        if (!Number.isInteger(courseId) || courseId <= 0) {
            alert('ID khóa học không hợp lệ!');
            return;
        }

        if (confirm('Bạn có chắc chắn muốn duyệt khóa học này?')) {
            fetch('/study_sharing/AdminCourse/approveCourse', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        course_id: courseId
                    })
                })
                .then(response => {
                    console.log('Approve response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Approve response data:', data);
                    alert(data.message);
                    if (data.success) {
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('Đã xảy ra lỗi khi duyệt khóa học: ' + error.message);
                });
        }
    }

    // Hàm thiết lập course_id cho modal hủy
    function setCancelCourseId(courseId) {
        document.getElementById('cancel_course_id').value = courseId;
    }

    // Xử lý form hủy khóa học
    document.getElementById('cancelCourseForm').addEventListener('submit', function(event) {
        event.preventDefault();
        const submitButton = this.querySelector('button[type="submit"]');
        const spinner = submitButton.querySelector('.spinner-border');

        if (!this.checkValidity()) {
            this.classList.add('was-validated');
            return;
        }

        spinner.classList.remove('d-none');
        submitButton.disabled = true;

        const formData = new FormData(this);
        const courseId = parseInt(formData.get('course_id'));
        const cancelReason = formData.get('cancel_reason');

        fetch('/study_sharing/AdminCourse/cancelCourse', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    course_id: courseId,
                    cancel_reason: cancelReason
                })
            })
            .then(response => {
                console.log('Cancel response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                spinner.classList.add('d-none');
                submitButton.disabled = false;
                alert(data.message);
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('cancelCourseModal')).hide();
                    window.location.reload();
                }
            })
            .catch(error => {
                spinner.classList.add('d-none');
                submitButton.disabled = false;
                console.error('Fetch error:', error);
                alert('Đã xảy ra lỗi khi hủy khóa học: ' + error.message);
            });
    });
</script>

<style>
    .modal-body p {
        margin-bottom: 10px;
    }

    .modal-body strong {
        display: inline-block;
        width: 200px;
    }

    .content {
        padding-top: 0px;
    }

    .action-buttons .btn {
        display: flex;
        align-items: center;
        gap: 5px;
        padding: 5px 10px;
    }
</style>