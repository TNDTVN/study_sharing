<?php
$title = "Quản lý thẻ";
?>
<style>
    .content {
        padding-top: 0px;
    }
</style>
<h1 class="mb-4 text-primary"><i class="bi bi-tags me-2"></i> Quản lý thẻ</h1>

<!-- Search and Add New Tag -->
<div class="d-flex justify-content-between mb-4">
    <form class="input-group w-50" method="GET" action="/study_sharing/tag/searchTagsWithDocuments">
        <input type="text" class="form-control" name="keyword" placeholder="Tìm kiếm theo tên thẻ hoặc mô tả..." value="<?php echo htmlspecialchars($keyword ?? ''); ?>" aria-label="Tìm kiếm thẻ">
        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Tìm</button>
    </form>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addTagModal"><i class="bi bi-plus-circle"></i> Thêm thẻ</button>
</div>

<!-- Message Display -->
<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_SESSION['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
<?php endif; ?>

<!-- Tags Table -->
<div class="card shadow-sm">
    <div class="card-body">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Tên thẻ</th>
                    <th scope="col">Mô tả</th>
                    <th scope="col">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tags)): ?>
                    <tr>
                        <td colspan="4" class="text-center">Không tìm thấy thẻ nào!</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tags as $tag): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($tag['tag_id']); ?></td>
                            <td><?php echo htmlspecialchars($tag['tag_name']); ?></td>
                            <td><?php echo htmlspecialchars($tag['description'] ?? ''); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary edit-btn" data-bs-toggle="modal" data-bs-target="#editTagModal"
                                    data-id="<?php echo $tag['tag_id']; ?>"
                                    data-name="<?php echo htmlspecialchars($tag['tag_name']); ?>"
                                    data-description="<?php echo htmlspecialchars($tag['description'] ?? ''); ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $tag['tag_id']; ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <nav aria-label="Tag pagination">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="/study_sharing/tag/searchTagsWithDocuments?page=<?php echo $page - 1; ?>&keyword=<?php echo urlencode($keyword ?? ''); ?>">Trước</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="/study_sharing/tag/searchTagsWithDocuments?page=<?php echo $i; ?>&keyword=<?php echo urlencode($keyword ?? ''); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="/study_sharing/tag/searchTagsWithDocuments?page=<?php echo $page + 1; ?>&keyword=<?php echo urlencode($keyword ?? ''); ?>">Sau</a>
                </li>
            </ul>
        </nav>
    </div>
</div>

<!-- Add Tag Modal -->
<div class="modal fade" id="addTagModal" tabindex="-1" aria-labelledby="addTagModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addTagModalLabel">Thêm thẻ mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addTagForm" method="POST" action="/study_sharing/tag/addTag" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="addTagName" class="form-label">Tên thẻ</label>
                        <input type="text" class="form-control" id="addTagName" name="tag_name" required>
                        <div class="invalid-feedback">Vui lòng nhập tên thẻ.</div>
                    </div>
                    <div class="mb-3">
                        <label for="addTagDescription" class="form-label">Mô tả</label>
                        <textarea class="form-control" id="addTagDescription" name="description" rows="4"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Thêm thẻ
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Tag Modal -->
<div class="modal fade" id="editTagModal" tabindex="-1" aria-labelledby="editTagModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editTagModalLabel">Chỉnh sửa thẻ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editTagForm" method="POST" action="/study_sharing/tag/editTag" class="needs-validation" novalidate>
                    <input type="hidden" id="editTagId" name="tag_id">
                    <div class="mb-3">
                        <label for="editTagName" class="form-label">Tên thẻ</label>
                        <input type="text" class="form-control" id="editTagName" name="tag_name" required>
                        <div class="invalid-feedback">Vui lòng nhập tên thẻ.</div>
                    </div>
                    <div class="mb-3">
                        <label for="editTagDescription" class="form-label">Mô tả</label>
                        <textarea class="form-control" id="editTagDescription" name="description" rows="4"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Cập nhật thẻ
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Form validation
        ['addTagForm', 'editTagForm'].forEach(formId => {
            const form = document.getElementById(formId);
            form.addEventListener('submit', (e) => {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });

        // Edit button click handler
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('editTagId').value = btn.dataset.id;
                document.getElementById('editTagName').value = btn.dataset.name;
                document.getElementById('editTagDescription').value = btn.dataset.description;
            });
        });

        // Delete button click handler
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                if (confirm('Bạn có chắc muốn xóa thẻ này?')) {
                    const formData = new FormData();
                    formData.append('tag_id', btn.dataset.id);
                    fetch('/study_sharing/tag/deleteTag', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Phản hồi mạng không thành công: ' + response.status);
                            }
                            return response.json();
                        })
                        .then(data => {
                            alert(data.message); // Hiển thị thông báo từ server
                            if (data.success) {
                                window.location.reload(); // Tải lại trang nếu xóa thành công
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Lỗi server: ' + error.message);
                        });
                }
            });
        });
    });
</script>