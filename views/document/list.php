<?php
$query = $query ?? '';
$category_id = $category_id ?? 0;
$file_type = $file_type ?? '';
$documents = $documents ?? [];
$categories = $categories ?? [];
$page = $page ?? 1;
$totalPages = $totalPages ?? 1;
?>

<link rel="stylesheet" href="/study_sharing/assets/css/list.css">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Danh sách tài liệu</h1>
        <div class="sort-options mb-4">
            <div class="btn-group" role="group">
                <a href="?sort=newest<?php echo $query ? '&query=' . urlencode($query) : ''; ?><?php echo $category_id ? '&category_id=' . $category_id : ''; ?><?php echo $file_type ? '&file_type=' . $file_type : ''; ?>"
                    class="btn btn-outline-primary <?php echo ($sort === 'newest') ? 'active' : ''; ?>">
                    Mới nhất
                </a>
                <a href="?sort=top_rated<?php echo $query ? '&query=' . urlencode($query) : ''; ?><?php echo $category_id ? '&category_id=' . $category_id : ''; ?><?php echo $file_type ? '&file_type=' . $file_type : ''; ?>"
                    class="btn btn-outline-primary <?php echo ($sort === 'top_rated') ? 'active' : ''; ?>">
                    Đánh giá cao
                </a>
                <a href="?sort=most_downloaded<?php echo $query ? '&query=' . urlencode($query) : ''; ?><?php echo $category_id ? '&category_id=' . $category_id : ''; ?><?php echo $file_type ? '&file_type=' . $file_type : ''; ?>"
                    class="btn btn-outline-primary <?php echo ($sort === 'most_downloaded') ? 'active' : ''; ?>">
                    Tải nhiều
                </a>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <form id="documentFilterForm" method="GET" action="/study_sharing/document/list">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" name="query" placeholder="Tìm kiếm tài liệu..." value="<?php echo htmlspecialchars($query ?? ''); ?>">
                        <button class="btn btn-primary" type="submit">Tìm kiếm</button>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-folder"></i></span>
                        <select class="form-select" name="category_id">
                            <option value="0">Tất cả danh mục</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>" <?php echo ($category_id == $cat['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-file-earmark"></i></span>
                        <select class="form-select" name="file_type">
                            <option value="">Tất cả loại file</option>
                            <option value="pdf" <?php echo (isset($file_type) && $file_type == 'pdf') ? 'selected' : ''; ?>>PDF</option>
                            <option value="docx" <?php echo (isset($file_type) && $file_type == 'docx') ? 'selected' : ''; ?>>DOCX</option>
                            <option value="pptx" <?php echo (isset($file_type) && $file_type == 'pptx') ? 'selected' : ''; ?>>PPTX</option>
                        </select>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Document List -->
    <?php if (empty($documents)): ?>
        <div class="empty-state">
            <i class="bi bi-file-earmark-excel"></i>
            <h3>Không tìm thấy tài liệu</h3>
            <p class="text-muted">Hãy thử thay đổi tiêu chí tìm kiếm hoặc tải lên tài liệu mới</p>
            <a href="/study_sharing/document/create" class="btn btn-primary mt-3">
                <i class="bi bi-upload"></i> Tải lên tài liệu
            </a>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($documents as $doc): ?>
                <div class="col">
                    <div class="card document-card h-100">
                        <div class="card-body">
                            <?php
                            $fileIconClass = 'bi-file-earmark-text';
                            if (isset($doc['file_type'])) {
                                if ($doc['file_type'] === 'pdf') $fileIconClass = 'bi-file-earmark-pdf pdf';
                                elseif ($doc['file_type'] === 'docx') $fileIconClass = 'bi-file-earmark-word docx';
                                elseif ($doc['file_type'] === 'pptx') $fileIconClass = 'bi-file-earmark-ppt pptx';
                            }
                            ?>
                            <div class="file-icon <?php echo isset($doc['file_type']) ? $doc['file_type'] : 'text'; ?>">
                                <i class="bi <?php echo $fileIconClass; ?>"></i>
                            </div>

                            <h5 class="card-title">
                                <a href="/study_sharing/document/detail/<?php echo $doc['document_id']; ?>">
                                    <?php echo htmlspecialchars($doc['title']); ?>
                                </a>
                            </h5>

                            <p class="card-text text-muted mb-3"><?php echo htmlspecialchars(substr($doc['description'] ?? '', 0, 120)); ?>...</p>

                            <div class="document-meta">
                                <div><i class="bi bi-folder"></i> <?php echo htmlspecialchars($doc['category_name'] ?? 'Không có'); ?></div>
                                <?php if (!empty($doc['course_name'])): ?>
                                    <div><i class="bi bi-book"></i> <?php echo htmlspecialchars($doc['course_name']); ?></div>
                                <?php endif; ?>
                                <div><i class="bi bi-person"></i> <?php echo htmlspecialchars($doc['full_name'] ?? 'Ẩn danh'); ?></div>
                                <div><i class="bi bi-calendar"></i> <?php echo date('d/m/Y', strtotime($doc['upload_date'])); ?></div>
                            </div>

                            <?php if (!empty($doc['avg_rating'])): ?>
                                <div class="mb-2">
                                    <span class="me-2"><?php echo number_format($doc['avg_rating'], 1); ?></span>
                                    <span class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="star <?php echo ($i <= round($doc['avg_rating'])) ? 'filled' : ''; ?>">★</span>
                                        <?php endfor; ?>
                                    </span>
                                    <small class="text-muted ms-2">(<?php echo $doc['rating_count'] ?? 0; ?>)</small>
                                </div>
                            <?php else: ?>
                                <div class="mb-2 text-muted">Chưa có đánh giá</div>
                            <?php endif; ?>

                            <?php if (!empty($doc['tags'])): ?>
                                <div class="mt-3">
                                    <?php foreach ($doc['tags'] as $tag): ?>
                                        <span class="tag-badge"><?php echo htmlspecialchars($tag); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-transparent border-top-0">
                            <a href="/study_sharing/document/detail/<?php echo $doc['document_id']; ?>" class="btn btn-sm btn-outline-primary w-100">
                                <i class="bi bi-eye"></i> Xem chi tiết
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation" class="mt-5">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&query=<?php echo urlencode($query ?? ''); ?>&category_id=<?php echo $category_id; ?>&file_type=<?php echo urlencode($file_type ?? ''); ?>">
                            <i class="bi bi-chevron-left"></i> Trước
                        </a>
                    </li>
                <?php endif; ?>

                <?php
                // Show limited pagination links
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);

                if ($start > 1): ?>
                    <li class="page-item"><a class="page-link" href="?page=1&query=<?php echo urlencode($query ?? ''); ?>&category_id=<?php echo $category_id; ?>&file_type=<?php echo urlencode($file_type ?? ''); ?>">1</a></li>
                    <?php if ($start > 2): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&query=<?php echo urlencode($query ?? ''); ?>&category_id=<?php echo $category_id; ?>&file_type=<?php echo urlencode($file_type ?? ''); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($end < $totalPages): ?>
                    <?php if ($end < $totalPages - 1): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                    <li class="page-item"><a class="page-link" href="?page=<?php echo $totalPages; ?>&query=<?php echo urlencode($query ?? ''); ?>&category_id=<?php echo $category_id; ?>&file_type=<?php echo urlencode($file_type ?? ''); ?>"><?php echo $totalPages; ?></a></li>
                <?php endif; ?>

                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&query=<?php echo urlencode($query ?? ''); ?>&category_id=<?php echo $category_id; ?>&file_type=<?php echo urlencode($file_type ?? ''); ?>">
                            Sau <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<script src="/study_sharing/assets/js/list.js"></script>