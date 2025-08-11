<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$title = "Quản lý khóa học";

$accountStmt = $pdo->prepare("SELECT a.account_id, u.full_name FROM accounts a LEFT JOIN users u ON a.account_id = u.account_id ORDER BY u.full_name");
$accountStmt->execute();
$accounts = $accountStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
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

    .container.py-5 {
        padding-top: 1rem !important;
        padding-bottom: 0 !important;
    }

    .copy-success {
        transition: all 0.3s ease;
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

    /* New styles for document cards */
    .document-card {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem;
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 0.375rem;
        margin-bottom: 0.5rem;
    }

    .document-card .document-title {
        flex-grow: 1;
        font-size: 0.9rem;
        color: #333;
    }

    .document-card .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }
</style>

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
            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Tìm</button>
        </form>
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
                        <td colspan="6" class="text-center">Không tìm thấy khóa học nào! Vui lòng kiểm tra dữ liệu hoặc liên hệ admin.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($courses as $index => $course): ?>
                        <tr data-course-id="<?php echo $course['course_id']; ?>">
                            <td><?php echo htmlspecialchars($offset + $index + 1); ?></td>
                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($course['full_name'] ?? 'N/A'); ?></td>
                            <td><?php echo $course['member_count'] ?? 0; ?></td>
                            <td>
                                <?php
                                $status = $course['status'] ?? 'pending';
                                $statusMap = [
                                    'open' => ['Đang mở', 'success'],
                                    'closed' => ['Đã đóng', 'danger'],
                                    'in_progress' => ['Đang tiến hành', 'primary'],
                                    'pending' => ['Chờ duyệt', 'warning'],
                                    'cancelled' => ['Đã hủy', 'secondary']
                                ];
                                $statusInfo = $statusMap[$status] ?? ['Chưa xác định', 'secondary'];
                                ?>
                                <span class="badge bg-<?php echo $statusInfo[1]; ?> bg-opacity-10 text-<?php echo $statusInfo[1]; ?>">
                                    <?php echo $statusInfo[0]; ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-outline-info btn-sm view-btn" title="Xem chi tiết khóa học" data-course-id="<?php echo $course['course_id']; ?>">
                                    <i class="fa fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-warning btn-sm edit-btn" data-bs-toggle="modal" data-bs-target="#editCourseModal" title="Chỉnh sửa khóa học"
                                    onclick="fillEditModal(<?php echo htmlspecialchars(json_encode($course)); ?>)">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <button class="btn btn-outline-secondary btn-sm manage-members-btn" data-bs-toggle="modal" data-bs-target="#manageMembersModal" title="Quản lý thành viên"
                                    onclick="loadCourseMembers(<?php echo (int)$course['course_id']; ?>)">
                                    <i class="fa fa-users"></i>
                                </button>
                                <button class="btn btn-outline-danger btn-sm delete-btn" title="Xóa khóa học" onclick="deleteCourse(<?php echo (int)$course['course_id']; ?>)">
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
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&keyword=<?php echo urlencode($keyword ?? ''); ?>&category_id=<?php echo $category_id ?? 0; ?>">Trước</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&keyword=<?php echo urlencode($keyword ?? ''); ?>&category_id=<?php echo $category_id ?? 0; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&keyword=<?php echo urlencode($keyword ?? ''); ?>&category_id=<?php echo $category_id ?? 0; ?>">Sau</a>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Modal chỉnh sửa khóa học -->
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
                    <form id="editCourseForm" method="POST" action="/study_sharing/AdminCourse/admin_edit" class="needs-validation" novalidate>
                        <input type="hidden" id="edit_course_id" name="course_id">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="edit_course_name" name="course_name" placeholder="Tên khóa học" required>
                                    <label for="edit_course_name">Tên khóa học <span class="text-danger">*</span></label>
                                    <div class="invalid-feedback">Vui lòng nhập tên khóa học.</div>
                                </div>

                                <div class="form-floating mb-3">
                                    <select class="form-control" id="edit_creator_id" name="creator_id" required>
                                        <option value="">-- Chọn người tạo --</option>
                                        <?php foreach ($accounts as $account): ?>
                                            <option value="<?php echo $account['account_id']; ?>">
                                                <?php echo htmlspecialchars($account['full_name'] ?? 'N/A'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="edit_creator_id">Người tạo <span class="text-danger">*</span></label>
                                    <div class="invalid-feedback">Vui lòng chọn người tạo.</div>
                                </div>

                                <div class="form-floating mb-3">
                                    <input type="number" class="form-control" id="edit_max_members" name="max_members" min="1" placeholder="Số thành viên tối đa" required>
                                    <label for="edit_max_members">Số thành viên tối đa <span class="text-danger">*</span></label>
                                    <div class="invalid-feedback">Vui lòng nhập số thành viên tối đa (lớn hơn 0).</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <textarea class="form-control" id="edit_description" name="description" placeholder="Mô tả khóa học" style="height: 132px;"></textarea>
                                    <label for="edit_description">Mô tả khóa học</label>
                                </div>

                                <div class="form-floating mb-3">
                                    <input type="url" class="form-control" id="edit_learn_link" name="learn_link" placeholder="Link học tập">
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
                                    <label for="edit_document_ids" class="form-label">Tài liệu liên quan</label>
                                    <select class="form-select select2-documents" id="edit_document_ids" name="document_ids[]" multiple>
                                        <!-- Options will be populated by JavaScript -->
                                    </select>
                                    <div class="form-text">Nhấn để tìm kiếm và chọn nhiều tài liệu</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="edit_status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_status" name="status" required>
                                    <option value="open">Đang mở</option>
                                    <option value="closed">Đã đóng</option>
                                    <option value="in_progress">Đang tiến hành</option>
                                    <option value="pending">Chờ duyệt</option>
                                    <option value="cancelled">Đã hủy</option>
                                </select>
                                <div class="invalid-feedback">Vui lòng chọn trạng thái.</div>
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
                        <div class="col-12 mt-3">
                            <div class="info-card">
                                <h6 class="section-title"><i class="bi bi-file-earmark-text text-primary me-2"></i>Tài liệu liên quan</h6>
                                <div id="detail-documents" class="document-container">
                                    <!-- Danh sách tài liệu sẽ được điền bằng JavaScript -->
                                </div>
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
        fetch('/study_sharing/AdminCourse/admin_edit', {
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
                console.error('Fetch error:', error);
                alert('Lỗi server khi cập nhật khóa học.');
            });
    });

    let currentCourseData = null;

    function fillEditModal(course) {
        currentCourseData = course;
        const form = document.getElementById('editCourseForm');
        form.classList.remove('was-validated');
        form.reset();

        document.getElementById('edit_course_id').value = course.course_id || '';
        document.getElementById('edit_course_name').value = course.course_name || '';
        document.getElementById('edit_description').value = course.description || '';
        document.getElementById('edit_creator_id').value = course.creator_id || '';
        document.getElementById('edit_max_members').value = course.max_members || 50;
        document.getElementById('edit_learn_link').value = course.learn_link || '';
        document.getElementById('edit_start_date').value = course.start_date || '';
        document.getElementById('edit_end_date').value = course.end_date || '';
        document.getElementById('edit_status').value = course.status || 'pending';

        const $select = $('#edit_document_ids');
        $select.select2({
            placeholder: "Tìm kiếm tài liệu...",
            allowClear: true,
            width: '100%',
            dropdownParent: $('#editCourseModal')
        });

        $select.val(null).trigger('change');

        // Sửa endpoint thành /study_sharing/AdminCourse/getAvailableDocuments
        fetch('/study_sharing/AdminCourse/getAvailableDocuments', {
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
                    $select.empty();
                    data.documents.forEach(doc => {
                        const isSelected = course.documents && course.documents.some(d => d.document_id == doc.document_id);
                        const option = new Option(
                            `${doc.title} (${doc.file_path.split('/').pop()}) - ${doc.status}`,
                            doc.document_id,
                            isSelected,
                            isSelected
                        );
                        $select.append(option);
                    });
                    $select.trigger('change');
                } else {
                    $select.empty();
                    const option = new Option('Không có tài liệu nào', '', false, false);
                    $select.append(option).trigger('change');
                    $select.prop('disabled', true);
                }
            })
    }

    document.getElementById('edit-course-btn').addEventListener('click', function() {
        if (currentCourseData) {
            fillEditModal(currentCourseData);
            const detailModal = bootstrap.Modal.getInstance(document.getElementById('courseDetailModal'));
            detailModal.hide();
            const editModal = new bootstrap.Modal(document.getElementById('editCourseModal'));
            editModal.show();
        } else {
            alert('Không có thông tin khóa học để chỉnh sửa!');
        }
    });

    function deleteCourse(courseId) {
        if (!Number.isInteger(courseId) || courseId <= 0) {
            alert('ID khóa học không hợp lệ!');
            return;
        }
        if (confirm('Bạn chắc chắn muốn xóa khóa học này?')) {
            fetch('/study_sharing/AdminCourse/admin_delete', {
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
                    console.error('Delete fetch error:', error);
                    alert('Lỗi server khi xóa khóa học.');
                });
        }
    }

    document.querySelectorAll('.view-btn').forEach(button => {
        button.addEventListener('click', function() {
            const courseId = parseInt(this.getAttribute('data-course-id'));
            console.log('Đang lấy chi tiết khóa học cho ID:', courseId);

            if (!Number.isInteger(courseId) || courseId <= 0) {
                console.error('ID khóa học không hợp lệ:', courseId);
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
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    console.log('Dữ liệu phản hồi:', data);
                    if (data.success) {
                        currentCourseData = data.course;
                        document.getElementById('detail-course-id').textContent = data.course.course_id || 'N/A';
                        document.getElementById('detail-course-name').textContent = data.course.course_name || 'Không xác định';
                        document.getElementById('detail-full-name').textContent = data.course.full_name || 'N/A';
                        document.getElementById('detail-max-members').textContent = data.course.max_members || '50';
                        document.getElementById('detail-member-count').textContent = data.course.member_count || '0';

                        const documentsContainer = document.getElementById('detail-documents');
                        documentsContainer.innerHTML = '';
                        if (data.course.documents && data.course.documents.length > 0) {
                            data.course.documents.forEach(doc => {
                                const div = document.createElement('div');
                                div.className = 'document-card';
                                div.innerHTML = `
                                    <span class="document-title">${doc.title} (${doc.file_path.split('/').pop()})</span>
                                    <a href="/study_sharing/download?file=${encodeURIComponent(doc.file_path)}" class="btn btn-sm btn-outline-primary" target="_blank">
                                        <i class="bi bi-download"></i> Tải xuống
                                    </a>
                                `;
                                documentsContainer.appendChild(div);
                            });
                        } else {
                            const div = document.createElement('div');
                            div.className = 'document-card text-muted';
                            div.textContent = 'Không có tài liệu liên quan.';
                            documentsContainer.appendChild(div);
                        }

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

                        const modal = new bootstrap.Modal(document.getElementById('courseDetailModal'));
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
                    console.error('Lỗi fetch:', error);
                    alert('Đã xảy ra lỗi khi lấy chi tiết khóa học');
                });
        });
    });

    let currentManageMembersModal = null;

    function loadCourseMembers(courseId) {
        if (!Number.isInteger(courseId) || courseId <= 0) {
            alert('ID khóa học không hợp lệ!');
            return;
        }

        fetch('/study_sharing/AdminCourse/getCourseMembers', {
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
                const membersTableBody = document.getElementById('members-table-body');
                membersTableBody.innerHTML = '';

                if (data.success && data.members && data.members.length > 0) {
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
                                    onclick="removeCourseMember(${courseId}, ${member.course_member_id}, '${(member.full_name || 'Ẩn danh').replace(/'/g, "\\'")}')">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    `;
                        membersTableBody.appendChild(row);
                    });
                } else {
                    membersTableBody.innerHTML = '<tr><td colspan="4" class="text-center">Không có sinh viên nào trong khóa học!</td></tr>';
                }

                if (currentManageMembersModal) {
                    currentManageMembersModal.hide();
                }

                currentManageMembersModal = new bootstrap.Modal(document.getElementById('manageMembersModal'), {
                    backdrop: true
                });
                currentManageMembersModal.show();

                document.getElementById('manageMembersModal').addEventListener('hidden.bs.modal', function() {
                    document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.paddingRight = '';
                    currentManageMembersModal = null;
                }, {
                    once: true
                });
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('Đã xảy ra lỗi khi lấy danh sách thành viên: ' + error.message);
            });
    }

    function removeCourseMember(courseId, courseMemberId, memberName) {
        if (!Number.isInteger(courseId) || courseId <= 0 || !Number.isInteger(courseMemberId) || courseMemberId <= 0) {
            alert('ID khóa học hoặc ID thành viên không hợp lệ!');
            return;
        }

        if (confirm(`Bạn có chắc chắn muốn xóa sinh viên "${memberName}" khỏi khóa học này?`)) {
            fetch('/study_sharing/AdminCourse/removeCourseMember', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        course_id: courseId,
                        course_member_id: courseMemberId
                    })
                })
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        loadCourseMembers(courseId);
                        const memberCountCell = document.querySelector(`tr[data-course-id="${courseId}"] td:nth-child(4)`);
                        if (memberCountCell) {
                            memberCountCell.textContent = parseInt(memberCountCell.textContent) - 1;
                        }
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('Đã xảy ra lỗi khi xóa thành viên: ' + error.message);
                });
        }
    }
</script>