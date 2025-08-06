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
                    <th scope="col">Hành động</th>
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
                            <td><?php echo htmlspecialchars($course['username'] ?? 'N/A'); ?></td>
                            <td><?php echo $course['member_count'] ?? 0; ?></td>
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
                                    <textarea class="form-control" id="edit_description" name="description" style="height: 123px;" rows="4"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_learn_link" class="form-label">Link học tập</label>
                                    <input type="url" class="form-control" id="edit_learn_link" name="learn_link" placeholder="https://example.com">
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
                        <p><strong>Ngày tạo:</strong> <span id="detail-created-at"></span></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
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

    function fillEditModal(course) {
        console.log('Course data:', course);
        document.getElementById('edit_course_id').value = course.course_id || '';
        document.getElementById('edit_course_name').value = course.course_name || '';
        document.getElementById('edit_description').value = course.description || '';
        document.getElementById('edit_creator_id').value = course.creator_id || '';
        document.getElementById('edit_max_members').value = course.max_members || 50;
        document.getElementById('edit_learn_link').value = course.learn_link || '';
        document.getElementById('edit_start_date').value = course.start_date || '';
        document.getElementById('edit_end_date').value = course.end_date || '';
    }

    function deleteCourse(courseId) {
        console.log('Deleting course with ID:', courseId);
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
                .then(response => {
                    console.log('Delete response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Delete response data:', data);
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
                    console.log('Trạng thái phản hồi:', response.status);
                    console.log('Tiêu đề phản hồi:', response.headers.get('content-type'));
                    // Kiểm tra xem phản hồi có phải là JSON không
                    if (!response.headers.get('content-type')?.includes('application/json')) {
                        return response.text().then(text => {
                            console.error('Phản hồi không phải JSON:', text);
                            throw new Error('Server trả về phản hồi không phải JSON');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Dữ liệu phản hồi:', data);
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
                        document.getElementById('detail-created-at').textContent = data.course.created_at || 'Chưa xác định';

                        const modal = new bootstrap.Modal(document.getElementById('courseDetailModal'), {
                            backdrop: true
                        });
                        modal.show();
                    } else {
                        console.error('Lỗi từ server:', data.message);
                        alert('Lỗi: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Lỗi fetch:', error);
                    alert('Đã xảy ra lỗi khi lấy chi tiết khóa học: ' + error.message);
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
                console.log('Members response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Members response data:', data);
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
                                        onclick="removeCourseMember(${courseId}, ${member.course_member_id}, '${member.full_name.replace(/'/g, "\\'")}')">
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
                    console.log('Remove member response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Remove member response data:', data);
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

    .container.py-5 {
        padding-top: 1rem !important;
        padding-bottom: 0 !important;
    }

    .action-buttons .btn {
        display: flex;
        align-items: center;
        gap: 5px;
        padding: 5px 10px;
    }
</style>