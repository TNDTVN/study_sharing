<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$title = "Quản lý khóa học";
?>

<div class="content-1 px-3">
    <h1 class="mb-4 text-primary"><i class="bi bi-book me-2"></i> Quản lý khóa học</h1>

    <div class="d-flex justify-content-between mb-4">
        <form class="input-group w-75" method="GET" action="/study_sharing/Course/manage">
            <input type="text" class="form-control" name="keyword" placeholder="Tìm kiếm theo tên khóa học hoặc mô tả" value="<?php echo htmlspecialchars($_GET['keyword'] ?? ''); ?>" aria-label="Tìm kiếm khóa học">
            <select class="form-select" name="status">
                <option value="">Tất cả trạng thái</option>
                <option value="open" <?php echo ($status ?? '') === 'open' ? 'selected' : ''; ?>>Mở</option>
                <option value="in_progress" <?php echo ($status ?? '') === 'in_progress' ? 'selected' : ''; ?>>Đang học</option>
                <option value="closed" <?php echo ($status ?? '') === 'closed' ? 'selected' : ''; ?>>Đã đóng</option>
                <option value="pending" <?php echo ($status ?? '') === 'pending' ? 'selected' : ''; ?>>Đang duyệt</option>
                <option value="cancelled" <?php echo ($status ?? '') === 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
            </select>
            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Tìm</button>
        </form>
        <a href="/study_sharing/Course/createCourseByTeacher" class="btn btn-success"><i class="bi bi-plus-circle"></i> Thêm khóa học</a>
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
                    <th scope="col" style="width: 5%;">STT</th>
                    <th scope="col" style="width: 40%;">Tên khóa học</th>
                    <th scope="col" style="width: 15%;">Số thành viên</th>
                    <th scope="col" style="width: 20%;">Trạng thái</th>
                    <th scope="col" style="width: 20%;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($courses)): ?>
                    <tr>
                        <td colspan="5" class="text-center">Không tìm thấy khóa học nào!</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($courses as $index => $course): ?>
                        <tr data-course-id="<?php echo $course['course_id']; ?>">
                            <td><?php echo htmlspecialchars($offset + $index + 1); ?></td>
                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                            <td><?php echo $course['member_count'] ?? 0; ?></td>
                            <td>
                                <span class="status-<?php echo htmlspecialchars($course['status']); ?>">
                                    <?php
                                    $statusMap = [
                                        'open' => '<i class="fa fa-check-circle"></i> Mở',
                                        'in_progress' => '<i class="fa fa-pause-circle"></i> Đang học',
                                        'closed' => '<i class="fa fa-ban"></i> Đã đóng',
                                        'pending' => '<i class="fa fa-clock"></i> Đang duyệt',
                                        'cancelled' => '<i class="fa fa-times-circle"></i> Đã hủy'
                                    ];
                                    echo $statusMap[$course['status']] ?? 'Không xác định';
                                    ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <div class="btn-group" role="group">
                                    <button class="btn btn-outline-info btn-sm view-btn" title="Xem chi tiết khóa học" data-course-id="<?php echo $course['course_id']; ?>">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                    <?php if (in_array($course['status'], ['open', 'in_progress', 'closed'])): ?>
                                        <button class="btn btn-outline-warning btn-sm edit-btn" data-bs-toggle="modal" data-bs-target="#editCourseModal" title="Chỉnh sửa khóa học"
                                            onclick="fillEditModal(<?php echo htmlspecialchars(json_encode($course)); ?>)">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary btn-sm manage-members-btn" data-bs-toggle="modal" data-bs-target="#manageMembersModal" title="Quản lý thành viên"
                                            onclick="loadCourseMembers(<?php echo (int)$course['course_id']; ?>)">
                                            <i class="fa fa-users"></i>
                                        </button>
                                        <?php if ($course['status'] === 'closed'): ?>
                                            <button class="btn btn-outline-primary btn-sm request-open-btn" title="Yêu cầu mở khóa học" onclick="requestOpenCourse(<?php echo (int)$course['course_id']; ?>)">
                                                <i class="fa fa-unlock"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <nav aria-label="Course pagination">
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
    </div>

    <!-- Modal chỉnh sửa khóa học -->
    <div class="modal fade" id="editCourseModal" tabindex="-1" aria-labelledby="editCourseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editCourseModalLabel">Chỉnh sửa khóa học</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editCourseForm" method="POST" action="/study_sharing/Course/editCourseByTeacher" class="needs-validation" novalidate>
                        <input type="hidden" id="edit_course_id" name="course_id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_course_name" class="form-label">Tên khóa học <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit_course_name" name="course_name" required>
                                    <div class="invalid-feedback">Vui lòng nhập tên khóa học.</div>
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
                                    <textarea class="form-control" id="edit_description" name="description" style="height: 123px;" rows="4"></textarea>
                                </div>
                            </div>
                            <div class="md-6">
                                <h6 class="section-title"><i class="bi bi-link-45deg text-primary me-2"></i>Link học tập</h6>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="edit_learn_link" name="learn_link" placeholder="Nhập hoặc dán link học tập">
                                    <button class="btn btn-primary copy-success" type="button" id="copy-learn-link">
                                        <i class="bi bi-clipboard me-1"></i> Sao chép
                                    </button>
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
                            <div class="mb-6">
                                <div class="mb-3">
                                    <label class="form-label">Tài liệu liên quan</label>
                                    <ul class="list-group mb-2" id="edit-document-list">
                                        <!-- Sẽ được load giống bên xem chi tiết -->
                                    </ul>
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="openDocumentSelector()">
                                        <i class="bi bi-plus-circle"></i> Chọn thêm tài liệu
                                    </button>
                                    <select class="form-select d-none" id="edit_document_ids" name="document_ids[]" multiple></select>
                                </div>

                            </div>
                        </div>
                        <div style="margin-top: 20px;">
                            <button type="submit" class="btn btn-primary w-100">
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                Cập nhật khóa học
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal xem chi tiết khóa học -->
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

    <!-- Modal quản lý thành viên -->
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
        document.getElementById('edit_course_id').value = course.course_id || '';
        document.getElementById('edit_course_name').value = course.course_name || '';
        document.getElementById('edit_description').value = course.description || '';
        document.getElementById('edit_max_members').value = course.max_members || 50;
        document.getElementById('edit_learn_link').value = course.learn_link || '';
        document.getElementById('edit_start_date').value = course.start_date || '';
        document.getElementById('edit_end_date').value = course.end_date || '';
        document.getElementById('edit_status').value = course.status || 'open';

        // Hiển thị danh sách tài liệu đã gán
        const docList = document.getElementById('edit-document-list');
        docList.innerHTML = '';

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
                const selectEl = document.getElementById('edit_document_ids');
                selectEl.innerHTML = '';

                if (data.success && data.documents) {
                    data.documents.forEach(doc => {
                        const li = document.createElement('li');
                        li.className = 'list-group-item d-flex justify-content-between align-items-center';
                        li.innerHTML = `
                <span>${doc.title} (${doc.file_path})</span>
                <a href="/study_sharing/download?file=${encodeURIComponent(doc.file_path)}" 
                   class="btn btn-sm btn-outline-primary" target="_blank">
                    <i class="bi bi-download"></i>
                </a>
            `;
                        if (doc.course_id == course.course_id) {
                            selectEl.appendChild(new Option(doc.title, doc.document_id, true, true));
                            docList.appendChild(li);
                        }
                    });
                }
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
                                    <i class="fa fa-trash"></i>
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
                        loadCourseMembers(courseId); // Reload members list
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
                                    this.textContent = '<i class="bi bi-clipboard me-1"></i> Sao chép';
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

    .content {
        padding-top: 0px;
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
</style>