<?php
$query = $query ?? '';
$page = $page ?? 1;
$totalPages = $totalPages ?? 1;
$courses = $courses ?? [];
?>

<div class="container">
    <h1 class="mb-4">Danh sách khóa học</h1>

    <!-- Form tìm kiếm -->
    <form class="mb-4" id="courseFilterForm" method="GET" action="/study_sharing/course/list">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" class="form-control" name="query" placeholder="Tìm kiếm khóa học..." value="<?php echo htmlspecialchars($query ?? ''); ?>">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </div>
        </div>
    </form>

    <!-- Danh sách khóa học -->
    <?php if (empty($courses)): ?>
        <div class="alert alert-info">Không tìm thấy khóa học nào.</div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 g-4">
            <?php foreach ($courses as $course): ?>
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">
                                <a class="text-decoration-none" href="/study_sharing/course/detail/<?php echo $course['course_id']; ?>">
                                    <?php echo htmlspecialchars($course['course_name']); ?>
                                </a>
                            </h5>
                            <p class="card-text"><?php echo htmlspecialchars(substr($course['description'] ?? '', 0, 100)); ?>...</p>
                            <p class="card-text"><small class="text-muted">Người tạo: <?php echo htmlspecialchars($course['full_name'] ?? 'Ẩn danh'); ?></small></p>
                            <p class="card-text"><small class="text-muted">Ngày tạo: <?php echo date('d/m/Y', strtotime($course['created_at'])); ?></small></p>
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
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&query=<?php echo urlencode($query ?? ''); ?>">Trước</a>
                    </li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&query=<?php echo urlencode($query ?? ''); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&query=<?php echo urlencode($query ?? ''); ?>">Sau</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<script src="/study_sharing/assets/js/course_list.js"></script>