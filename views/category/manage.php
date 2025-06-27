<?php
$title = "Quản lý danh mục";
?>

<div class="container py-5">
    <h1 class="mb-4 text-primary"><i class="bi bi-folder me-2"></i> Quản lý danh mục</h1>

    <!-- Search and Add New Category -->
    <div class="d-flex justify-content-between mb-4">
        <form class="input-group w-50" method="GET" action="/study_sharing/category/manage">
            <input type="text" class="form-control" name="keyword" placeholder="Tìm kiếm danh mục..." value="<?php echo htmlspecialchars($keyword ?? ''); ?>" aria-label="Tìm kiếm danh mục">
            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Tìm</button>
        </form>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCategoryModal"><i class="bi bi-plus-circle"></i> Thêm danh mục</button>
    </div>

    <!-- Message Display -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <!-- Categories Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Tên danh mục</th>
                        <th scope="col">Mô tả</th>
                        <th scope="col">Ngày tạo</th>
                        <th scope="col">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="5" class="text-center">Không tìm thấy danh mục nào!</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['category_id']); ?></td>
                                <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($category['description'] ?? ''); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($category['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary edit-btn" data-bs-toggle="modal" data-bs-target="#editCategoryModal"
                                        data-id="<?php echo $category['category_id']; ?>"
                                        data-name="<?php echo htmlspecialchars($category['category_name']); ?>"
                                        data-description="<?php echo htmlspecialchars($category['description'] ?? ''); ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $category['category_id']; ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <nav aria-label="Category pagination">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="/study_sharing/category/manage?page=<?php echo $page - 1; ?>&keyword=<?php echo urlencode($keyword ?? ''); ?>">Trước</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="/study_sharing/category/manage?page=<?php echo $i; ?>&keyword=<?php echo urlencode($keyword ?? ''); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="/study_sharing/category/manage?page=<?php echo $page + 1; ?>&keyword=<?php echo urlencode($keyword ?? ''); ?>">Sau</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addCategoryModalLabel">Thêm danh mục mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addCategoryForm" method="POST" action="/study_sharing/category/addCategory" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="addCategoryName" class="form-label">Tên danh mục</label>
                            <input type="text" class="form-control" id="addCategoryName" name="category_name" required>
                            <div class="invalid-feedback">Vui lòng nhập tên danh mục.</div>
                        </div>
                        <div class="mb-3">
                            <label for="addCategoryDescription" class="form-label">Mô tả</label>
                            <textarea class="form-control" id="addCategoryDescription" name="description" rows="4"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            Thêm danh mục
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editCategoryModalLabel">Chỉnh sửa danh mục</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editCategoryForm" method="POST" action="/study_sharing/category/editCategory" class="needs-validation" novalidate>
                        <input type="hidden" id="editCategoryId" name="category_id">
                        <div class="mb-3">
                            <label for="editCategoryName" class="form-label">Tên danh mục</label>
                            <input type="text" class="form-control" id="editCategoryName" name="category_name" required>
                            <div class="invalid-feedback">Vui lòng nhập tên danh mục.</div>
                        </div>
                        <div class="mb-3">
                            <label for="editCategoryDescription" class="form-label">Mô tả</label>
                            <textarea class="form-control" id="editCategoryDescription" name="description" rows="4"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            Cập nhật danh mục
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Form validation
        ['addCategoryForm', 'editCategoryForm'].forEach(formId => {
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
                document.getElementById('editCategoryId').value = btn.dataset.id;
                document.getElementById('editCategoryName').value = btn.dataset.name;
                document.getElementById('editCategoryDescription').value = btn.dataset.description;
            });
        });

        // Delete button click handler
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                if (confirm('Bạn có chắc muốn xóa danh mục này?')) {
                    const formData = new FormData();
                    formData.append('category_id', btn.dataset.id);
                    fetch('/study_sharing/category/deleteCategory', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                window.location.reload();
                            } else {
                                alert(data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Lỗi server!');
                        });
                }
            });
        });
    });
</script>