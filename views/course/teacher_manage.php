<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$title = "Quản lý khóa học";
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<div class="content-1 px-3">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-primary fs-3 fw-bold"><i class="bi bi-book me-2"></i> Quản lý khóa học</h1>
            <a href="/study_sharing/Course/createCourseByTeacher" class="btn btn-success btn-sm"><i class="bi bi-plus-circle me-1"></i> Thêm khóa học</a>
        </div>

        <!-- Notification -->
        <?php if (!empty($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo htmlspecialchars($_SESSION['message_type'] ?? 'info', ENT_QUOTES | ENT_SUBSTITUTE); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['message'], ENT_QUOTES | ENT_SUBSTITUTE); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
            </div>
        <?php endif; ?>

        <!-- Search and Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form class="row g-3" method="GET" action="/study_sharing/Course/manage">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" name="keyword" placeholder="Tìm kiếm theo tên khóa học hoặc mô tả" value="<?php echo htmlspecialchars($_GET['keyword'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE); ?>" aria-label="Tìm kiếm khóa học">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="">Tất cả trạng thái</option>
                            <option value="open" <?php echo ($status ?? '') === 'open' ? 'selected' : ''; ?>>Mở</option>
                            <option value="in_progress" <?php echo ($status ?? '') === 'in_progress' ? 'selected' : ''; ?>>Đang học</option>
                            <option value="closed" <?php echo ($status ?? '') === 'closed' ? 'selected' : ''; ?>>Đã đóng</option>
                            <option value="pending" <?php echo ($status ?? '') === 'pending' ? 'selected' : ''; ?>>Đang duyệt</option>
                            <option value="cancelled" <?php echo ($status ?? '') === 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100" type="submit">Tìm</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Courses List -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php if (empty($courses)): ?>
                <div class="col">
                    <div class="card shadow-sm text-center p-5">
                        <i class="bi bi-book display-4 text-muted mb-3"></i>
                        <p class="text-muted">Không tìm thấy khóa học nào!</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($courses as $index => $course): ?>
                    <div class="col">
                        <div class="card course-card h-100 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title text-primary mb-0 text-truncate" title="<?php echo htmlspecialchars($course['course_name'], ENT_QUOTES | ENT_SUBSTITUTE); ?>">
                                        <?php echo htmlspecialchars($course['course_name'], ENT_QUOTES | ENT_SUBSTITUTE); ?>
                                    </h5>
                                    <span class="badge bg-<?php echo $course['status'] === 'open' ? 'success' : ($course['status'] === 'in_progress' ? 'warning' : ($course['status'] === 'closed' ? 'danger' : ($course['status'] === 'pending' ? 'info' : 'secondary'))); ?>">
                                        <?php
                                        $statusMap = [
                                            'open' => 'Mở',
                                            'in_progress' => 'Đang học',
                                            'closed' => 'Đã đóng',
                                            'pending' => 'Đang duyệt',
                                            'cancelled' => 'Đã hủy'
                                        ];
                                        echo $statusMap[$course['status']] ?? 'Không xác định';
                                        ?>
                                    </span>
                                </div>
                                <p class="card-text text-muted small mb-2"><i class="bi bi-people me-1"></i>Thành viên: <?php echo $course['member_count'] ?? 0; ?> / <?php echo $course['max_members'] ?? 'Không giới hạn'; ?></p>
                                <p class="card-text text-muted small mb-2"><i class="bi bi-calendar me-1"></i>Ngày tạo: <?php echo date('d/m/Y', strtotime($course['created_at'])); ?></p>
                                <p class="card-text small text-truncate" title="<?php echo htmlspecialchars($course['description'] ?? 'Không có mô tả', ENT_QUOTES | ENT_SUBSTITUTE); ?>">
                                    <?php echo htmlspecialchars($course['description'] ?? 'Không có mô tả', ENT_QUOTES | ENT_SUBSTITUTE); ?>
                                </p>
                            </div>
                            <div class="card-footer bg-white border-top-0">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <button class="btn btn-outline-info btn-sm view-btn" title="Xem chi tiết khóa học" data-course-id="<?php echo $course['course_id']; ?>">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <?php if (in_array($course['status'], ['open', 'in_progress', 'closed'])): ?>
                                            <button class="btn btn-outline-warning btn-sm edit-btn" data-bs-toggle="modal" data-bs-target="#editCourseModal" title="Chỉnh sửa khóa học"
                                                onclick="fillEditModal(<?php echo htmlspecialchars(json_encode($course, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_APOS)); ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-outline-secondary btn-sm manage-members-btn" data-bs-toggle="modal" data-bs-target="#manageMembersModal" title="Quản lý thành viên"
                                                onclick="loadCourseMembers(<?php echo (int)$course['course_id']; ?>)">
                                                <i class="bi bi-people-fill"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($course['status'] === 'closed'): ?>
                                        <button class="btn btn-outline-primary btn-sm request-open-btn" title="Yêu cầu mở khóa học" onclick="requestOpenCourse(<?php echo (int)$course['course_id']; ?>)">
                                            <i class="bi bi-unlock"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if (!empty($courses)): ?>
            <nav aria-label="Course pagination" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&keyword=<?php echo urlencode($keyword ?? ''); ?>&status=<?php echo urlencode($status ?? ''); ?>">Trước</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&keyword=<?php echo urlencode($keyword ?? ''); ?>&status=<?php echo urlencode($status ?? ''); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&keyword=<?php echo urlencode($keyword ?? ''); ?>&status=<?php echo urlencode($status ?? ''); ?>">Sau</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Course Modal -->
<div class="modal fade" id="editCourseModal" tabindex="-1" aria-labelledby="editCourseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editCourseModalLabel">
                    <i class="bi bi-pencil-square me-2"></i> Chỉnh sửa khóa học
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editCourseForm" method="POST" action="/study_sharing/Course/editCourseByTeacher" class="needs-validation" novalidate>
                    <input type="hidden" id="edit_course_id" name="course_id">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="edit_course_name" name="course_name" placeholder="Tên khóa học" required>
                                <label for="edit_course_name">Tên khóa học <span class="text-danger">*</span></label>
                                <div class="invalid-feedback">Vui lòng nhập tên khóa học.</div>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="number" class="form-control" id="edit_max_members" name="max_members" min="1" placeholder="Số thành viên tối đa" required>
                                <label for="edit_max_members">Số thành viên tối đa <span class="text-danger">*</span></label>
                                <div class="invalid-feedback">Vui lòng nhập số thành viên tối đa (lớn hơn 0).</div>
                            </div>

                            <div class="mb-3">
                                <label for="edit_status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_status" name="status" required>
                                    <option value="open">Mở</option>
                                    <option value="in_progress">Đang học</option>
                                    <option value="closed">Đóng</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <textarea class="form-control" id="edit_description" name="description" placeholder="Mô tả khóa học" style="height: 132px;"></textarea>
                                <label for="edit_description">Mô tả khóa học</label>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="url" class="form-control" id="edit_learn_link" name="learn_link" placeholder="Link học tập" style="margin-top: 27.5px;">
                                <label for="edit_learn_link">Link học tập</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="date" class="form-control" id="edit_start_date" name="start_date" placeholder="Ngày bắt đầu">
                                <label for="edit_start_date">Ngày bắt đầu</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="date" class="form-control" id="edit_end_date" name="end_date" placeholder="Ngày kết thúc">
                                <label for="edit_end_date">Ngày kết thúc</label>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="edit_documents" class="form-label">Tài liệu liên quan</label>
                                <select class="form-select select2-documents" id="edit_documents" name="document_ids[]" multiple>
                                    <!-- Danh sách tài liệu sẽ được điền bằng JavaScript -->
                                </select>
                                <div class="form-text">Nhấn để tìm kiếm và chọn nhiều tài liệu</div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg py-2">
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                <span class="submit-text">Cập nhật khóa học</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Course Detail Modal -->
<div class="modal fade" id="courseDetailModal" tabindex="-1" aria-labelledby="courseDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient-primary text-white" style="background-color: blue;">
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
                                <input type="text" class="form-control" id="detail-learn-link">
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
                    <div class="col-12 mt-3">
                        <div class="info-card">
                            <h6 class="section-title"><i class="bi bi-file-earmark-text text-primary me-2"></i>Tài liệu liên quan</h6>
                            <ul class="list-group" id="detail-documents">
                                <!-- Danh sách tài liệu sẽ được điền bằng JavaScript -->
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i> Đóng
                </button>
                <button type="button" class="btn btn-primary rounded-pill px-4" id="edit-course-btn">
                    <i class="bi bi-pencil-square me-1"></i> Chỉnh sửa
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Manage Members Modal -->
<div class="modal fade" id="manageMembersModal" tabindex="-1" aria-labelledby="manageMembersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="manageMembersModalLabel">Quản lý thành viên</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="members-list">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th scope="col">STT</th>
                                <th scope="col">Tên sinh viên</th>
                                <th scope="col">Ngày tham gia</th>
                                <th scope="col">Hành động</th>
                            </tr>
                        </thead>
                        <tbody id="members-table-body">
                            <!-- Danh sách thành viên sẽ được thêm bằng JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
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

    document.getElementById('editCourseForm').addEventListener('submit', function(event) {
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
        fetch('/study_sharing/Course/editCourseByTeacher', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                spinner.classList.add('d-none');
                submitButton.disabled = false;
                alert(data.message);
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('editCourseModal')).hide();
                    window.location.reload();
                }
            })
            .catch(error => {
                spinner.classList.add('d-none');
                submitButton.disabled = false;
                console.error('Lỗi fetch:', error);
                alert('Lỗi server khi cập nhật khóa học.');
            });
    });

    function fillEditModal(course) {
        console.log('Course data:', course);
        console.log('Documents:', course.documents);

        const form = document.getElementById('editCourseForm');
        form.classList.remove('was-validated');
        form.reset();

        document.getElementById('edit_course_id').value = course.course_id || '';
        document.getElementById('edit_course_name').value = course.course_name || '';
        document.getElementById('edit_description').value = course.description || '';
        document.getElementById('edit_max_members').value = course.max_members || 50;
        document.getElementById('edit_learn_link').value = course.learn_link || '';
        document.getElementById('edit_start_date').value = course.start_date || '';
        document.getElementById('edit_end_date').value = course.end_date || '';
        document.getElementById('edit_status').value = course.status || 'open';

        const $select = $('#edit_documents');
        $select.select2({
            placeholder: course.documents && course.documents.length > 0 ? "Chọn tài liệu liên quan" : "Không có tài liệu liên quan",
            allowClear: true,
            width: '100%',
            dropdownParent: $('#editCourseModal')
        });

        $select.val(null).trigger('change');
        $select.empty();

        fetch('/study_sharing/Course/getAvailableDocuments', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    course_id: course.course_id
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.documents && data.documents.length > 0) {
                    const selectedDocumentIds = course.documents ?
                        course.documents.map(doc => Number(doc.document_id)) : [];
                    console.log('Selected document IDs:', selectedDocumentIds);

                    data.documents.forEach(doc => {
                        const isSelected = selectedDocumentIds.includes(Number(doc.document_id));
                        const option = new Option(
                            `${doc.title} (${doc.file_path.split('/').pop()})`,
                            doc.document_id,
                            isSelected,
                            isSelected
                        );
                        $select.append(option);
                    });

                    $select.val(selectedDocumentIds).trigger('change');
                    $select.prop('disabled', false);
                } else {
                    $select.append(new Option('Không có tài liệu nào', '', false, false)).trigger('change');
                    $select.prop('disabled', true);
                }
            })
            .catch(error => {
                console.error('Lỗi khi tải tài liệu:', error);
                alert('Đã xảy ra lỗi khi tải danh sách tài liệu.');
            });
    }

    function requestOpenCourse(courseId) {
        if (!Number.isInteger(courseId) || courseId <= 0) {
            alert('ID khóa học không hợp lệ!');
            return;
        }
        if (confirm('Bạn có muốn gửi yêu cầu mở khóa học này?')) {
            fetch('/study_sharing/Course/requestOpenCourse', {
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
                    alert(data.message);
                    if (data.success) {
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Lỗi fetch:', error);
                    alert('Lỗi server khi gửi yêu cầu mở khóa học.');
                });
        }
    }

    function loadCourseMembers(courseId) {
        if (!Number.isInteger(courseId) || courseId <= 0) {
            alert('ID khóa học không hợp lệ!');
            return;
        }

        fetch('/study_sharing/Course/getCourseMembers', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    course_id: courseId
                })
            })
            .then(response => response.json())
            .then(data => {
                const membersTableBody = document.getElementById('members-table-body');
                membersTableBody.innerHTML = '';

                if (data.success && data.members.length > 0) {
                    data.members.forEach((member, index) => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${index + 1}</td>
                            <td>${member.full_name || 'Ẩn danh'}</td>
                            <td>${member.join_date || 'Chưa xác định'}</td>
                            <td>
                                <button class="btn btn-outline-danger btn-sm remove-member-btn"
                                    title="Xóa sinh viên"
                                    data-course-id="${courseId}"
                                    data-course-member-id="${member.course_member_id}"
                                    onclick="removeCourseMember(${courseId}, ${member.course_member_id})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        `;
                        membersTableBody.appendChild(row);
                    });
                } else {
                    membersTableBody.innerHTML = '<tr><td colspan="4" class="text-center">Không có thành viên nào trong khóa học.</td></tr>';
                }
            })
            .catch(error => {
                console.error('Lỗi fetch:', error);
                alert('Đã xảy ra lỗi khi lấy danh sách thành viên.');
            });
    }

    function removeCourseMember(courseId, courseMemberId) {
        if (!Number.isInteger(courseId) || courseId <= 0 || !Number.isInteger(courseMemberId) || courseMemberId <= 0) {
            alert('ID khóa học hoặc ID thành viên không hợp lệ!');
            return;
        }

        if (confirm('Bạn có chắc chắn muốn xóa thành viên này khỏi khóa học?')) {
            fetch('/study_sharing/Course/removeCourseMember', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        course_id: courseId,
                        course_member_id: courseMemberId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        loadCourseMembers(courseId);
                    }
                })
                .catch(error => {
                    console.error('Lỗi fetch:', error);
                    alert('Đã xảy ra lỗi khi xóa thành viên.');
                });
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.view-btn').forEach(button => {
            button.addEventListener('click', function() {
                const courseId = this.getAttribute('data-course-id');
                fetch('/study_sharing/Course/getCourseDetails', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            course_id: courseId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log('Course details:', data.course);
                            document.getElementById('detail-course-id').textContent = data.course.course_id || '';
                            document.getElementById('detail-course-name').textContent = data.course.course_name || '';
                            document.getElementById('detail-course-status').textContent = data.course.status ? `Trạng thái: ${data.course.status}` : '';
                            document.getElementById('detail-course-status').className = `badge bg-opacity-10 mb-2 bg-${data.course.status === 'open' ? 'success' : data.course.status === 'in_progress' ? 'warning' : 'secondary'}`;
                            document.getElementById('detail-full-name').textContent = data.course.full_name || 'Ẩn danh';
                            document.getElementById('detail-start-date').textContent = data.course.start_date || 'Chưa xác định';
                            document.getElementById('detail-end-date').textContent = data.course.end_date || 'Chưa xác định';
                            document.getElementById('detail-member-count').textContent = data.course.member_count || '0';
                            document.getElementById('detail-max-members').textContent = data.course.max_members || 'Không giới hạn';
                            document.getElementById('detail-learn-link').value = data.course.learn_link || '';
                            document.getElementById('detail-description').textContent = data.course.description || 'Không có mô tả';
                            document.getElementById('detail-created-at').textContent = data.course.created_at || 'Không xác định';

                            const documentsList = document.getElementById('detail-documents');
                            documentsList.innerHTML = '';
                            if (data.course.documents && data.course.documents.length > 0) {
                                data.course.documents.forEach(doc => {
                                    const li = document.createElement('li');
                                    li.className = 'list-group-item d-flex justify-content-between align-items-center';
                                    li.innerHTML = `
                                        <span>${doc.title} (${doc.file_path})</span>
                                        <a href="/study_sharing/download?file=${encodeURIComponent(doc.file_path)}" class="btn btn-sm btn-outline-primary" target="_blank">
                                            <i class="bi bi-download"></i> Tải xuống
                                        </a>
                                    `;
                                    documentsList.appendChild(li);
                                });
                            } else {
                                const li = document.createElement('li');
                                li.className = 'list-group-item text-muted';
                                li.textContent = 'Không có tài liệu liên quan.';
                                documentsList.appendChild(li);
                            }

                            const modal = new bootstrap.Modal(document.getElementById('courseDetailModal'));
                            modal.show();

                            document.getElementById('copy-learn-link').addEventListener('click', function() {
                                const linkInput = document.getElementById('detail-learn-link');
                                linkInput.select();
                                document.execCommand('copy');
                                this.textContent = 'Đã sao chép!';
                                setTimeout(() => {
                                    this.innerHTML = '<i class="bi bi-clipboard me-1"></i> Sao chép';
                                }, 2000);
                            });

                            document.getElementById('edit-course-btn').addEventListener('click', function() {
                                fillEditModal(data.course);
                                bootstrap.Modal.getInstance(document.getElementById('courseDetailModal')).hide();
                                const editModal = new bootstrap.Modal(document.getElementById('editCourseModal'));
                                editModal.show();
                            });
                        } else {
                            alert('Lỗi: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Lỗi:', error);
                        alert('Đã xảy ra lỗi khi lấy chi tiết khóa học.');
                    });
            });
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

    .content-1 {
        padding-top: 0px;
    }

    .course-card {
        transition: transform 0.2s ease-in-out;
    }

    .course-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1) !important;
    }

    .course-card .card-title {
        font-size: 1.25rem;
        font-weight: 600;
    }

    .course-card .card-text {
        font-size: 0.875rem;
    }

    .course-card .card-footer {
        padding: 0.75rem 1.25rem;
    }

    .course-card .btn-sm {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }

    .status-open {
        color: green;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .status-in_progress {
        color: orange;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .status-closed {
        color: red;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .status-pending {
        color: blue;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .status-cancelled {
        color: gray;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .modal-dialog {
        max-width: 700px;
    }

    .container.py-5 {
        padding-top: 1rem !important;
        padding-bottom: 0 !important;
    }

    .action-buttons .btn {
        gap: 5px;
        margin-right: 5px;
    }

    .info-card {
        padding: 15px;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        background-color: #fff;
    }

    .section-title {
        font-size: 1.1rem;
        margin-bottom: 10px;
        color: #333;
    }

    .info-label {
        font-weight: 500;
        color: #6c757d;
        min-width: 120px;
    }

    .info-value {
        color: #212529;
    }

    .description-content {
        padding: 10px;
        background-color: #f8f9fa;
        border-radius: 5px;
    }

    .copy-success {
        transition: all 0.3s ease;
    }

    .copy-success:active {
        transform: scale(0.95);
    }

    .select2-container--default .select2-selection--multiple {
        min-height: 45px;
        border: 1px solid #ced4da;
        padding: 5px;
        border-radius: 0.375rem;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #e9ecef;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
    }
</style>