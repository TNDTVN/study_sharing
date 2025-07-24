<?php
$query = $query ?? '';
$page = $page ?? 1;
$totalPages = $totalPages ?? 1;
$courses = $courses ?? [];
$sort = $sort ?? 'newest';
?>

<link rel="stylesheet" href="/study_sharing/assets/css/course_list.css">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Danh sách khóa học</h1>
        <?php if (isset($_SESSION['account_id']) && $_SESSION['account_role'] === 'teacher'): ?>
            <a href="/study_sharing/course/create" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Tạo khóa học mới
            </a>
        <?php endif; ?>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <form id="courseFilterForm" method="GET" action="/study_sharing/course/list">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label for="searchQuery" class="form-label">Tìm kiếm khóa học</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="searchQuery" name="query" placeholder="Nhập tên hoặc mô tả khóa học..." value="<?php echo htmlspecialchars($query); ?>">
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Sắp xếp theo</label>
                    <div class="btn-group w-100" role="group">
                        <a href="?sort=newest<?php echo $query ? '&query=' . urlencode($query) : ''; ?>"
                            class="btn btn-outline-primary <?php echo ($sort === 'newest') ? 'active' : ''; ?>">
                            Mới nhất
                        </a>
                        <a href="?sort=popular<?php echo $query ? '&query=' . urlencode($query) : ''; ?>"
                            class="btn btn-outline-primary <?php echo ($sort === 'popular') ? 'active' : ''; ?>">
                            Phổ biến
                        </a>
                        <a href="?sort=name<?php echo $query ? '&query=' . urlencode($query) : ''; ?>"
                            class="btn btn-outline-primary <?php echo ($sort === 'name') ? 'active' : ''; ?>">
                            Tên A-Z
                        </a>
                    </div>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel"></i> Lọc
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Course List -->
    <?php if (empty($courses)): ?>
        <div class="empty-state">
            <i class="bi bi-book"></i>
            <h3>Không tìm thấy khóa học</h3>
            <p class="text-muted">Hãy thử thay đổi tiêu chí tìm kiếm hoặc tạo khóa học mới</p>
            <?php if (isset($_SESSION['account_id']) && $_SESSION['account_role'] === 'teacher'): ?>
                <a href="/study_sharing/course/create" class="btn btn-primary mt-3">
                    <i class="bi bi-plus-circle"></i> Tạo khóa học
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($courses as $course): ?>
                <div class="col">
                    <div class="card course-card h-100">
                        <div class="card-body">
                            <div class="course-icon">
                                <i class="bi bi-journal-bookmark"></i>
                            </div>

                            <h5 class="card-title">
                                <a href="/study_sharing/course/detail/<?php echo $course['course_id']; ?>">
                                    <?php echo htmlspecialchars($course['course_name']); ?>
                                </a>
                            </h5>

                            <p class="card-text text-muted mb-3"><?php echo htmlspecialchars(substr($course['description'] ?? '', 0, 120)); ?>...</p>

                            <div class="course-meta">
                                <div>
                                    <i class="bi bi-person"></i>
                                    <?php echo htmlspecialchars($course['full_name'] ?? 'Ẩn danh'); ?>
                                </div>
                                <div>
                                    <i class="bi bi-calendar"></i>
                                    Ngày tạo: <?php echo date('d/m/Y', strtotime($course['created_at'])); ?>
                                </div>
                                <div>
                                    <i class="bi bi-people"></i>
                                    Số thành viên: <?php echo $course['member_count'] ?? 0; ?> / <?php echo $course['max_members'] ?? 50; ?>
                                </div>
                                <div>
                                    <i class="bi bi-clock"></i>
                                    <?php if ($course['start_date'] && $course['end_date']): ?>
                                        <?php echo date('d/m/Y', strtotime($course['start_date'])); ?> - <?php echo date('d/m/Y', strtotime($course['end_date'])); ?>
                                    <?php else: ?>
                                        Chưa có lịch học
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <span class="course-status status-<?php echo str_replace(' ', '_', strtolower($course['status'] ?? 'open')); ?>">
                                        <?php
                                        switch ($course['status']) {
                                            case 'open':
                                                echo 'Đang mở';
                                                break;
                                            case 'closed':
                                                echo 'Đã đóng';
                                                break;
                                            case 'in_progress':
                                                echo 'Đang diễn ra';
                                                break;
                                            default:
                                                echo $course['status'];
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-top-0">
                            <a href="/study_sharing/course/detail/<?php echo $course['course_id']; ?>" class="btn btn-sm btn-outline-primary w-100">
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
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&query=<?php echo urlencode($query); ?>&sort=<?php echo $sort; ?>">
                            <i class="bi bi-chevron-left"></i> Trước
                        </a>
                    </li>
                <?php endif; ?>

                <?php
                // Show limited pagination links
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);

                if ($start > 1): ?>
                    <li class="page-item"><a class="page-link" href="?page=1&query=<?php echo urlencode($query); ?>&sort=<?php echo $sort; ?>">1</a></li>
                    <?php if ($start > 2): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&query=<?php echo urlencode($query); ?>&sort=<?php echo $sort; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($end < $totalPages): ?>
                    <?php if ($end < $totalPages - 1): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                    <li class="page-item"><a class="page-link" href="?page=<?php echo $totalPages; ?>&query=<?php echo urlencode($query); ?>&sort=<?php echo $sort; ?>"><?php echo $totalPages; ?></a></li>
                <?php endif; ?>

                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&query=<?php echo urlencode($query); ?>&sort=<?php echo $sort; ?>">
                            Sau <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<script src="/study_sharing/assets/js/course_list.js"></script>