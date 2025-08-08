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
                        <p class="card-text fs-3 fw-bold mb-0"><?= $totalCourses ?></p>
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
                        <canvas id="statusChart" height="150" width=""></canvas>
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
                    <tbody>
                        <?php
                        // Map English statuses to Vietnamese
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
    new Chart(statusCtx, {
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
            plugins: {
                legend: {
                    position: 'top',
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
</script>