<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$title = "Duyệt khóa học";
?>

<style>
    .content {
        padding-top: 0 !important;
    }

    .modal-content {
        border-radius: 12px;
        overflow: hidden;
    }

    .modal-header {
        border-bottom: none;
        background: linear-gradient(90deg, #007bff, #0056b3);
        padding: 0.75rem 1rem;
    }

    .modal-body {
        padding: 1.5rem;
        max-height: 80vh;
        overflow-y: auto;
    }

    .modal-dialog {
        max-width: 700px;
    }

    .info-card {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .section-title {
        font-size: 1rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 0.75rem;
    }

    .info-label {
        width: 120px;
        font-weight: 500;
        color: #555;
        font-size: 0.9rem;
    }

    .info-value {
        flex: 1;
        color: #333;
        font-size: 0.9rem;
    }

    .description-content {
        background: #fff;
        border: 1px solid #e9ecef;
        min-height: 80px;
        padding: 0.75rem;
        font-size: 0.9rem;
    }

    .badge {
        font-size: 0.8rem;
        padding: 0.3em 0.7em;
    }

    .content-1 {
        padding-top: 0;
    }

    .pagination .page-link {
        border-radius: 0.25rem;
        margin: 0 2px;
        color: #007bff;
    }

    .pagination .page-item.active .page-link {
        background-color: #007bff;
        border-color: #007bff;
    }

    .pagination .page-item.disabled .page-link {
        color: #6c757d;
    }

    .copy-success {
        transition: all 0.3s ease;
    }
</style>

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
                            <td><?php echo htmlspecialchars($index + 1 + ($page - 1) * 5); ?></td>
                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($course['full_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($course['created_at']))); ?></td>
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

    <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
        <div class="text-center mt-2">
            <small>Hiển thị <?php echo count($courses); ?> / <?php echo $totalCourses; ?> khóa học</small>
        </div>
    <?php endif; ?>

    <!-- Modal xem chi tiết khóa học -->
    <div class="modal fade" id="courseDetailModal" tabindex="-1" aria-labelledby="courseDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title fw-bold" id="courseDetailModalLabel">
                        <i class="bi bi-journal-bookmark-fill me-2"></i>THÔNG TIN CHI TIẾT KHÓA HỌC
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <h4 class="text-primary fw-bold mb-1" id="detail-course-name"></h4>
                            <div class="badge bg-opacity-10 mb-2" id="detail-course-status"></div>
                        </div>
                        <div class="col-12">
                            <div class="info-card mb-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="section-title"><i class="bi bi-info-circle-fill text-primary me-2"></i>Thông tin cơ bản</h6>
                                        <ul class="list-unstyled">
                                            <li class="mb-2 d-flex">
                                                <span class="info-label"><i class="bi bi-hash text-muted me-2"></i>Mã khóa học:</span>
                                                <span class="info-value fw-medium" id="detail-course-id"></span>
                                            </li>
                                            <li class="mb-2 d-flex">
                                                <span class="info-label"><i class="bi bi-person-badge text-muted me-2"></i>Giảng viên:</span>
                                                <span class="info-value fw-medium" id="detail-full-name"></span>
                                            </li>
                                            <li class="mb-2 d-flex">
                                                <span class="info-label"><i class="bi bi-calendar-range text-muted me-2"></i>Thời gian:</span>
                                                <span class="info-value fw-medium">
                                                    <span id="detail-start-date"></span> - <span id="detail-end-date"></span>
                                                </span>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="section-title"><i class="bi bi-people-fill text-primary me-2"></i>Thành viên</h6>
                                        <ul class="list-unstyled">
                                            <li class="mb-2 d-flex">
                                                <span class="info-label"><i class="bi bi-person-plus text-muted me-2"></i>Hiện có:</span>
                                                <span class="info-value fw-medium" id="detail-member-count"></span>
                                            </li>
                                            <li class="mb-2 d-flex">
                                                <span class="info-label"><i class="bi bi-person-check text-muted me-2"></i>Tối đa:</span>
                                                <span class="info-value fw-medium" id="detail-max-members"></span>
                                            </li>
                                            <li class="mb-2 d-flex">
                                                <span class="info-label"><i class="bi bi-clock-history text-muted me-2"></i>Ngày tạo:</span>
                                                <span class="info-value fw-medium" id="detail-created-at"></span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="info-card mb-3">
                                <h6 class="section-title"><i class="bi bi-link-45deg text-primary me-2"></i>Link học tập</h6>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="detail-learn-link" readonly>
                                    <button class="btn btn-primary copy-success" type="button" id="copy-learn-link">
                                        <i class="bi bi-clipboard me-1"></i> Sao chép
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="info-card">
                                <h6 class="section-title"><i class="bi bi-card-text text-primary me-2"></i>Mô tả khóa học</h6>
                                <div id="detail-description" class="description-content rounded"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i> Đóng
                    </button>
                    <button type="button" class="btn btn-outline-success rounded-pill px-4 approve-btn" id="modal-approve-btn">
                        <i class="bi bi-check-lg me-1"></i> Duyệt
                    </button>
                    <button type="button" class="btn btn-outline-danger rounded-pill px-4 cancel-btn" data-bs-toggle="modal" data-bs-target="#cancelCourseModal" id="modal-cancel-btn">
                        <i class="bi bi-x-circle me-1"></i> Hủy
                    </button>
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

    let currentCourseId = null;

    document.querySelectorAll('.view-btn').forEach(button => {
        button.addEventListener('click', function() {
            currentCourseId = parseInt(this.getAttribute('data-course-id'));
            console.log('Fetching course details for ID:', currentCourseId);

            if (!Number.isInteger(currentCourseId) || currentCourseId <= 0) {
                console.error('Invalid course ID:', currentCourseId);
                alert('ID khóa học không hợp lệ!');
                return;
            }

            fetch('/study_sharing/AdminCourse/getCourseDetails', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        course_id: currentCourseId
                    })
                })
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    console.log('View response data:', data);
                    if (data.success) {
                        document.getElementById('detail-course-id').textContent = data.course.course_id || 'N/A';
                        document.getElementById('detail-course-name').textContent = data.course.course_name || 'Không xác định';
                        document.getElementById('detail-full-name').textContent = data.course.full_name || 'N/A';
                        document.getElementById('detail-max-members').textContent = data.course.max_members || '50';
                        document.getElementById('detail-member-count').textContent = data.course.member_count || '0';

                        const descriptionElement = document.getElementById('detail-description');
                        descriptionElement.innerHTML = data.course.description ?
                            data.course.description.replace(/\n/g, '<br>') :
                            '<em>Không có mô tả</em>';

                        const learnLink = data.course.learn_link || '';
                        const learnLinkInput = document.getElementById('detail-learn-link');
                        const copyButton = document.getElementById('copy-learn-link');

                        if (learnLink) {
                            learnLinkInput.value = learnLink;
                            copyButton.disabled = false;
                            copyButton.classList.remove('opacity-50');
                            copyButton.onclick = () => {
                                navigator.clipboard.writeText(learnLink).then(() => {
                                    copyButton.innerHTML = '<i class="bi bi-check-circle me-1"></i> Sao chép thành công';
                                    copyButton.classList.add('btn-success');
                                    copyButton.classList.remove('btn-primary');
                                    setTimeout(() => {
                                        copyButton.innerHTML = '<i class="bi bi-clipboard me-1"></i> Sao chép';
                                        copyButton.classList.add('btn-primary');
                                        copyButton.classList.remove('btn-success');
                                    }, 2000);
                                }).catch(() => {
                                    copyButton.innerHTML = '<i class="bi bi-x-circle me-1"></i> Lỗi sao chép';
                                    copyButton.classList.add('btn-danger');
                                    copyButton.classList.remove('btn-primary');
                                    setTimeout(() => {
                                        copyButton.innerHTML = '<i class="bi bi-clipboard me-1"></i> Sao chép';
                                        copyButton.classList.add('btn-primary');
                                        copyButton.classList.remove('btn-danger');
                                    }, 2000);
                                });
                            };
                        } else {
                            learnLinkInput.value = 'Không có link học tập';
                            copyButton.disabled = true;
                            copyButton.classList.add('opacity-50');
                            copyButton.onclick = null;
                        }

                        const formatDate = (dateString) => {
                            if (!dateString) return 'Chưa xác định';
                            return new Date(dateString).toLocaleDateString('vi-VN', {
                                day: '2-digit',
                                month: '2-digit',
                                year: 'numeric'
                            });
                        };

                        document.getElementById('detail-start-date').textContent = formatDate(data.course.start_date);
                        document.getElementById('detail-end-date').textContent = formatDate(data.course.end_date);
                        document.getElementById('detail-created-at').textContent = formatDate(data.course.created_at);

                        const statusElement = document.getElementById('detail-course-status');
                        const status = data.course.status || 'pending';
                        const statusMap = {
                            open: {
                                text: 'Đang mở',
                                class: 'bg-success text-success'
                            },
                            closed: {
                                text: 'Đã đóng',
                                class: 'bg-danger text-danger'
                            },
                            in_progress: {
                                text: 'Đang tiến hành',
                                class: 'bg-primary text-primary'
                            },
                            pending: {
                                text: 'Chờ duyệt',
                                class: 'bg-warning text-warning'
                            },
                            cancelled: {
                                text: 'Đã hủy',
                                class: 'bg-secondary text-secondary'
                            }
                        };
                        const statusInfo = statusMap[status] || {
                            text: 'Chưa xác định',
                            class: 'bg-secondary text-secondary'
                        };
                        statusElement.textContent = statusInfo.text;
                        statusElement.className = `badge bg-opacity-10 ${statusInfo.class} mb-2`;

                        const modal = new bootstrap.Modal(document.getElementById('courseDetailModal'), {
                            backdrop: true
                        });
                        modal.show();

                        modal._element.addEventListener('hidden.bs.modal', function() {
                            document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
                            document.body.classList.remove('modal-open');
                            document.body.style.paddingRight = '';
                        }, {
                            once: true
                        });
                    } else {
                        alert('Lỗi: ' + (data.message || 'Không thể lấy thông tin khóa học'));
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('Đã xảy ra lỗi khi lấy chi tiết khóa học: ' + error.message);
                });
        });
    });

    document.getElementById('modal-approve-btn').addEventListener('click', function() {
        if (currentCourseId) {
            approveCourse(currentCourseId);
        }
    });

    document.getElementById('modal-cancel-btn').addEventListener('click', function() {
        if (currentCourseId) {
            setCancelCourseId(currentCourseId);
        }
    });

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
                    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('courseDetailModal')).hide();
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('Đã xảy ra lỗi khi duyệt khóa học: ' + error.message);
                });
        }
    }

    function setCancelCourseId(courseId) {
        document.getElementById('cancel_course_id').value = courseId;
    }

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
                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                return response.json();
            })
            .then(data => {
                spinner.classList.add('d-none');
                submitButton.disabled = false;
                alert(data.message);
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('cancelCourseModal')).hide();
                    bootstrap.Modal.getInstance(document.getElementById('courseDetailModal')).hide();
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