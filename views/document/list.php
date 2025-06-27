<?php
$query = $query ?? '';
$category_id = $category_id ?? 0;
$file_type = $file_type ?? '';
$documents = $documents ?? [];
$categories = $categories ?? [];
$page = $page ?? 1;
$totalPages = $totalPages ?? 1;
?>
<style>
    .rating-stars .star {
        font-size: 20px;
        color: #ccc;
    }

    .rating-stars .star.filled {
        color: #ffcc00;
    }
</style>
<div class="container">
    <h1 class="mb-4">Danh sách tài liệu</h1>

    <!-- Form tìm kiếm và lọc -->
    <form class="mb-4" id="documentFilterForm" method="GET" action="/study_sharing/document/list">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" class="form-control" name="query" placeholder="Tìm kiếm tài liệu..." value="<?php echo htmlspecialchars($query ?? ''); ?>">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="category_id">
                    <option value="0">Tất cả danh mục</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>" <?php echo ($category_id == $cat['category_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="file_type">
                    <option value="">Tất cả loại file</option>
                    <option value="pdf" <?php echo (isset($file_type) && $file_type == 'pdf') ? 'selected' : ''; ?>>PDF</option>
                    <option value="doc" <?php echo (isset($file_type) && $file_type == 'doc') ? 'selected' : ''; ?>>DOC</option>
                    <option value="docx" <?php echo (isset($file_type) && $file_type == 'docx') ? 'selected' : ''; ?>>DOCX</option>
                    <option value="ppt" <?php echo (isset($file_type) && $file_type == 'ppt') ? 'selected' : ''; ?>>PPT</option>
                    <option value="pptx" <?php echo (isset($file_type) && $file_type == 'pptx') ? 'selected' : ''; ?>>PPTX</option>
                </select>
            </div>
        </div>
    </form>

    <!-- Danh sách tài liệu -->
    <?php if (empty($documents)): ?>
        <div class="alert alert-info">Không tìm thấy tài liệu nào.</div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 g-4">
            <?php foreach ($documents as $doc): ?>
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="/study_sharing/document/detail/<?php echo $doc['document_id']; ?>">
                                    <?php echo htmlspecialchars($doc['title']); ?>
                                </a>
                            </h5>
                            <p class="card-text"><?php echo htmlspecialchars(substr($doc['description'] ?? '', 0, 100)); ?>...</p>
                            <p class="card-text"><small class="text-muted">Danh mục: <?php echo htmlspecialchars($doc['category_name'] ?? 'Không có'); ?></small></p>
                            <?php if (!empty($doc['course_name'])): ?>
                                <p class="card-text"><small class="text-muted">Khóa học: <?php echo htmlspecialchars($doc['course_name']); ?></small></p>
                            <?php endif; ?>
                            <p class="card-text"><small class="text-muted">Người tải lên: <?php echo htmlspecialchars($doc['full_name'] ?? 'Ẩn danh'); ?></small></p>
                            <p class="card-text"><small class="text-muted">Ngày tải: <?php echo date('d/m/Y', strtotime($doc['upload_date'])); ?></small></p>
                            <p class="card-text">
                                Rating: <?php echo $doc['avg_rating'] ? number_format($doc['avg_rating'], 1) . '/5' : 'Chưa có đánh giá'; ?>
                                <span class="rating-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?php echo ($i <= round($doc['avg_rating'])) ? 'filled' : ''; ?>">★</span>
                                    <?php endfor; ?>
                                </span>
                            </p>
                            <p class="card-text">
                                <?php foreach ($doc['tags'] as $tag): ?>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($tag); ?></span>
                                <?php endforeach; ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Phân trang -->
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&query=<?php echo urlencode($query ?? ''); ?>&category_id=<?php echo $category_id; ?>&file_type=<?php echo urlencode($file_type ?? ''); ?>">Trước</a>
                    </li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&query=<?php echo urlencode($query ?? ''); ?>&category_id=<?php echo $category_id; ?>&file_type=<?php echo urlencode($file_type ?? ''); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&query=<?php echo urlencode($query ?? ''); ?>&category_id=<?php echo $category_id; ?>&file_type=<?php echo urlencode($file_type ?? ''); ?>">Sau</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<script src="/study_sharing/assets/js/list.js"></script>