<?php
$title = "Quản lý danh mục";
?>
<style>
    .content {
        padding-top: 0px;
    }
</style>
<h1 class="mb-4 text-primary"><i class="bi bi-folder me-2"></i> Quản lý danh mục</h1>

<!-- Search and Add New Category -->
<div class="d-flex justify-content-between mb-4">
    <form class="input-group w-50" method="GET" action="/study_sharing/category/searchCategoriesWithDocuments">
        <input type="text" class="form-control" name="keyword" placeholder="Tìm kiếm theo tên danh mục hoặc mô tả..." value="<?php echo htmlspecialchars($keyword ?? ''); ?>" aria-label="Tìm kiếm danh mục">
        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Tìm kiếm</button>
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
<!-- Bảng danh sách danh mục -->
<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên danh mục</th>
                <th>Mô tả</th>
                <th>Ngày tạo</th>
                <th>Hành động</th>
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
                        <td><?php echo htmlspecialchars($category['description'] ?? 'N/A'); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($category['created_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editCategoryModal"
                                    onclick="fillEditModal(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <button class="btn btn-outline-danger btn-sm" onclick="deleteCategory(<?php echo $category['category_id']; ?>)">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Phân trang -->
<?php if ($totalPages > 1): ?>
    <nav aria-label="Page navigation" class="mt-3">
        <ul class="pagination justify-content-center mb-0">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="/study_sharing/category/searchCategoriesWithDocuments?page=<?php echo $page - 1; ?>&keyword=<?php echo urlencode($keyword); ?>">Trước</a>
                </li>
            <?php endif; ?>
            <?php
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            if ($endPage - $startPage < 4) {
                $startPage = max(1, $endPage - 4);
            }
            for ($i = $startPage; $i <= $endPage; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="/study_sharing/category/searchCategoriesWithDocuments?page=<?php echo $i; ?>&keyword=<?php echo urlencode($keyword); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="/study_sharing/category/searchCategoriesWithDocuments?page=<?php echo $page + 1; ?>&keyword=<?php echo urlencode($keyword); ?>">Sau</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>


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
<script>
    function fillEditModal(category) {
        document.getElementById('editCategoryId').value = category.category_id;
        document.getElementById('editCategoryName').value = category.category_name;
        document.getElementById('editCategoryDescription').value = category.description || '';
    }

    function deleteCategory(categoryId) {
        if (confirm('Bạn có chắc chắn muốn xóa danh mục này?')) {
            fetch('/study_sharing/category/deleteCategory', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'category_id=' + encodeURIComponent(categoryId)
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Lỗi:', error);
                    alert('Có lỗi xảy ra khi xóa danh mục.');
                });
        }
    }
</script>