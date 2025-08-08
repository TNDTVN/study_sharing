<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<style>
    .content {
        padding-top: 0 !important;
    }
</style>

<div class="container mt-4">
    <h2 class="mb-4 text-primary fw-bold"><?= htmlspecialchars($title) ?></h2>

    <!-- Summary Cards -->
    <div class="row mb-5 g-3">
        <div class="col-md-4 col-sm-6">
            <div class="card shadow-sm border-0 bg-gradient-primary text-white">
                <div class="card-body d-flex align-items-center">
                    <i class="bi bi-book fs-2 me-3"></i>
                    <div>
                        <h5 class="card-title mb-1">Tổng số khóa học</h5>
                        <p class="card-text fs-3 fw-bold mb-0"><?= $totalCoursesCount ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="card shadow-sm border-0 bg-gradient-success text-white">
                <div class="card-body d-flex align-items-center">
                    <i class="bi bi-person-check fs-2 me-3"></i>
                    <div>
                        <h5 class="card-title mb-1">Tổng số người tạo</h5>
                        <p class="card-text fs-3 fw-bold mb-0"><?= $totalCreators ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="card shadow-sm border-0 bg-gradient-warning text-white">
                <div class="card-body d-flex align-items-center">
                    <i class="bi bi-clock fs-2 me-3"></i>
                    <div>
                        <h5 class="card-title mb-1">Thời lượng trung bình</h5>
                        <p class="card-text fs-3 fw-bold mb-0"><?= $avgDuration ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row mb-5 g-3">
        <!-- Courses per Creator Chart -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title text-primary fw-bold">Số khóa học theo người tạo</h5>
                    <div class="chart-container">
                        <canvas id="creatorChart" height="150"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <!-- Course Status Chart -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title text-primary fw-bold">Phân bố trạng thái khóa học</h5>
                    <div class="chart-container chart-centered">
                        <canvas id="statusChart" height="150"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <!-- Course Creation Over Time Chart -->
        <div class="col-lg-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title text-primary fw-bold">Khóa học tạo mới theo thời gian</h5>
                    <canvas id="creationChart" height="150"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <h5 class="card-title text-primary fw-bold">Lọc khóa học</h5>
            <form id="filterForm" onsubmit="filterCourses(1); return false;">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="keyword" id="keyword" class="form-control" placeholder="Tìm kiếm tên hoặc mô tả" value="<?= htmlspecialchars($keyword ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <select name="status" id="status" class="form-select">
                            <option value="">Tất cả trạng thái</option>
                            <option value="open" <?= isset($status) && $status === 'open' ? 'selected' : '' ?>>Đang mở</option>
                            <option value="closed" <?= isset($status) && $status === 'closed' ? 'selected' : '' ?>>Đã đóng</option>
                            <option value="in_progress" <?= isset($status) && $status === 'in_progress' ? 'selected' : '' ?>>Đang tiến hành</option>
                            <option value="pending" <?= isset($status) && $status === 'pending' ? 'selected' : '' ?>>Chờ duyệt</option>
                            <option value="cancelled" <?= isset($status) && $status === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">Lọc</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Course Details Table -->
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h5 class="card-title text-primary fw-bold">Chi tiết khóa học</h5>
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th>Tên khóa học</th>
                            <th>Người tạo</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                        </tr>
                    </thead>
                    <tbody id="courseTableBody">
                        <?php
                        $statusTranslations = [
                            'open' => 'Đang mở',
                            'closed' => 'Đã đóng',
                            'in_progress' => 'Đang tiến hành',
                            'pending' => 'Chờ duyệt',
                            'cancelled' => 'Đã hủy'
                        ];
                        foreach ($courses as $course):
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($course['course_name']) ?></td>
                                <td><?= htmlspecialchars($course['full_name'] ?? 'N/A') ?></td>
                                <td>
                                    <span class="badge bg-<?= $course['status'] === 'open' ? 'success' : ($course['status'] === 'closed' ? 'danger' : 'warning') ?>">
                                        <?= htmlspecialchars($statusTranslations[$course['status']] ?? $course['status']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($course['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div id="pagination">
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="#" onclick="filterCourses(<?= $page - 1 ?>)" <?= $page <= 1 ? 'tabindex="-1" aria-disabled="true"' : '' ?>>Trước</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="#" onclick="filterCourses(<?= $i ?>)"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="#" onclick="filterCourses(<?= $page + 1 ?>)" <?= $page >= $totalPages ? 'tabindex="-1" aria-disabled="true"' : '' ?>>Sau</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #007bff, #0056b3);
    }

    .bg-gradient-success {
        background: linear-gradient(135deg, #28a745, #1e7e34);
    }

    .bg-gradient-warning {
        background: linear-gradient(135deg, #ffc107, #e0a800);
    }

    .card {
        transition: transform 0.3s ease;
    }

    .card:hover {
        transform: translateY(-5px);
    }

    .badge {
        padding: 0.5em 1em;
        font-size: 0.9em;
    }

    .table th,
    .table td {
        vertical-align: middle;
    }

    .chart-container {
        min-height: 200px;
        max-height: 200px;
        position: relative;
    }

    .chart-centered {
        display: flex;
        justify-content: center;
        align-items: center;
    }
</style>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Courses per Creator Chart
    const creatorCtx = document.getElementById('creatorChart').getContext('2d');
    new Chart(creatorCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($creatorCourses, 'full_name')) ?>,
            datasets: [{
                label: 'Số khóa học',
                data: <?= json_encode(array_column($creatorCourses, 'course_count')) ?>,
                backgroundColor: 'rgba(40, 167, 69, 0.7)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Số khóa học'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Người tạo'
                    }
                }
            }
        }
    });

    // Course Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusCounts = <?= json_encode(array_count_values(array_column($courses, 'status'))) ?>;
    const statusLabels = <?= json_encode(array_map(function ($status) use ($statusTranslations) {
                                return $statusTranslations[$status] ?? $status;
                            }, array_keys(array_count_values(array_column($courses, 'status'))))) ?>;
    const statusChart = new Chart(statusCtx, {
        type: 'pie',
        data: {
            labels: statusLabels,
            datasets: [{
                data: Object.values(statusCounts),
                backgroundColor: [
                    'rgba(40, 167, 69, 0.7)', // Đang mở
                    'rgba(220, 53, 69, 0.7)', // Đã đóng
                    'rgba(255, 193, 7, 0.7)', // Chờ duyệt
                    'rgba(108, 117, 125, 0.7)', // Đã hủy
                    'rgba(111, 66, 193, 0.7)' // Đang tiến hành
                ],
                borderColor: [
                    'rgba(40, 167, 69, 1)',
                    'rgba(220, 53, 69, 1)',
                    'rgba(255, 193, 7, 1)',
                    'rgba(108, 117, 125, 1)',
                    'rgba(111, 66, 193, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    align: 'center',
                    labels: {
                        boxWidth: 20,
                        padding: 10,
                        font: {
                            size: 12
                        },
                        usePointStyle: true
                    }
                }
            },
            layout: {
                padding: {
                    top: 20,
                    bottom: 20
                }
            }
        }
    });

    // Course Creation Over Time Chart
    const creationCtx = document.getElementById('creationChart').getContext('2d');
    new Chart(creationCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($creations, 'creation_date')) ?>,
            datasets: [{
                label: 'Số khóa học tạo mới',
                data: <?= json_encode(array_column($creations, 'count')) ?>,
                backgroundColor: 'rgba(23, 162, 184, 0.7)',
                borderColor: 'rgba(23, 162, 184, 1)',
                borderWidth: 2,
                fill: false,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Số khóa học'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Ngày tạo'
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            }
        }
    });

    // AJAX function to filter courses
    function filterCourses(page) {
        const keyword = document.getElementById('keyword').value;
        const status = document.getElementById('status').value;

        fetch(`/study_sharing/AdminCourse/filterCourses?page=${page}&keyword=${encodeURIComponent(keyword)}&status=${encodeURIComponent(status)}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('courseTableBody').innerHTML = data.tableRows;
                    document.getElementById('pagination').innerHTML = data.pagination;
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Lỗi khi tải dữ liệu!');
            });
    }
</script>