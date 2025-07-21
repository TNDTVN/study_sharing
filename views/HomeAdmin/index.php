<h1 class="mb-4 text-primary"><i class="bi bi-speedometer2 me-2"></i> Dashboard Quản trị</h1>

<!-- Thống kê -->
<div class="row g-4 mb-5">
    <div class="col-md-3 col-sm-6">
        <div class="card dashboard-card card-users shadow-sm">
            <div class="card-body">
                <i class="bi bi-people card-icon"></i>
                <h5 class="card-title">Người dùng</h5>
                <p class="card-text"><?php echo $totalUsers; ?></p>
                <a href="/study_sharing/Account/manage" class="quick-link">Quản lý người dùng</a>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card dashboard-card card-documents shadow-sm">
            <div class="card-body">
                <i class="bi bi-file-earmark-text card-icon"></i>
                <h5 class="card-title">Tài liệu</h5>
                <p class="card-text"><?php echo $totalDocuments; ?></p>
                <a href="/study_sharing/AdminDocument/admin_manage" class="quick-link">Quản lý tài liệu</a>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card dashboard-card card-courses shadow-sm">
            <div class="card-body">
                <i class="bi bi-book card-icon"></i>
                <h5 class="card-title">Khóa học</h5>
                <p class="card-text"><?php echo $totalCourses; ?></p>
                <a href="/course/manage" class="quick-link">Quản lý khóa học</a>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card dashboard-card card-categories shadow-sm">
            <div class="card-body">
                <i class="bi bi-folder card-icon"></i>
                <h5 class="card-title">Danh mục</h5>
                <p class="card-text"><?php echo $totalCategories; ?></p>
                <a href="/study_sharing/category/manage" class="quick-link">Quản lý danh mục</a>
            </div>
        </div>
    </div>
</div>
<!-- Biểu đồ thống kê -->
<div class="row g-4 mb-5">
    <div class="col-12">
        <h3 class="mb-4 text-primary"><i class="bi bi-bar-chart me-2"></i> Thống kê chi tiết</h3>
        <div class="row g-4">
            <!-- Biểu đồ cột: Tổng quan -->
            <div class="col-md-6">
                <div class="card dashboard-card card-users shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-bar-chart-fill card-icon text-primary"></i>
                            <h5 class="card-title ms-2">Tổng quan số lượng</h5>
                        </div>
                        <canvas id="overviewChart" style="max-height: 300px;"></canvas>
                    </div>
                </div>
            </div>
            <!-- Biểu đồ tròn: Phân phối tài liệu theo danh mục -->
            <div class="col-md-6">
                <div class="card dashboard-card card-documents shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-pie-chart-fill card-icon text-primary"></i>
                            <h5 class="card-title ms-2">Phân phối tài liệu theo danh mục</h5>
                        </div>
                        <canvas id="categoryDistributionChart" style="max-height: 300px;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .dashboard-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1) !important;
    }
    .card-icon {
        font-size: 2rem;
        color: #3b82f6;
    }
    .card-title {
        font-size: 1.25rem;
        font-weight: 600;
    }
    canvas {
        width: 100% !important;
    }
</style>

<script>
    // Cấu hình chung cho Chart.js
    Chart.defaults.font.family = "'Inter', 'Helvetica', 'Arial', sans-serif";
    Chart.defaults.font.size = 14;

    // Biểu đồ cột: Tổng quan
    const overviewChart = new Chart(document.getElementById('overviewChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['Người dùng', 'Tài liệu', 'Khóa học', 'Danh mục'],
            datasets: [{
                label: 'Số lượng',
                data: [<?php echo $totalUsers; ?>, <?php echo $totalDocuments; ?>, <?php echo $totalCourses; ?>, <?php echo $totalCategories; ?>],
                backgroundColor: [
                    'rgba(59, 130, 246, 0.7)',  // Xanh primary (đồng bộ card-users)
                    'rgba(239, 68, 68, 0.7)',   // Đỏ (đồng bộ card-documents)
                    'rgba(16, 185, 129, 0.7)',  // Xanh lá (đồng bộ card-courses)
                    'rgba(168, 85, 247, 0.7)'   // Tím (đồng bộ card-categories)
                ],
                borderColor: [
                    'rgba(59, 130, 246, 1)',
                    'rgba(239, 68, 68, 1)',
                    'rgba(16, 185, 129, 1)',
                    'rgba(168, 85, 247, 1)'
                ],
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleFont: { size: 16 },
                    bodyFont: { size: 14 },
                    padding: 10
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Số lượng',
                        font: { size: 16 }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Hạng mục',
                        font: { size: 16 }
                    },
                    grid: {
                        display: false
                    }
                }
            },
            animation: {
                duration: 1000,
                easing: 'easeOutQuart'
            }
        }
    });

    // Biểu đồ tròn: Phân phối tài liệu theo danh mục
    const categoryDistributionChart = new Chart(document.getElementById('categoryDistributionChart').getContext('2d'), {
        type: 'pie',
        data: {
            labels: [<?php foreach ($documentsByCategory as $category) { echo "'" . htmlspecialchars($category['category_name']) . "',"; } ?>],
            datasets: [{
                data: [<?php foreach ($documentsByCategory as $category) { echo $category['count'] . ","; } ?>],
                backgroundColor: [
                    'rgba(59, 130, 246, 0.7)',
                    'rgba(239, 68, 68, 0.7)',
                    'rgba(16, 185, 129, 0.7)',
                    'rgba(168, 85, 247, 0.7)',
                    'rgba(234, 179, 8, 0.7)'
                ],
                borderColor: [
                    'rgba(59, 130, 246, 1)',
                    'rgba(239, 68, 68, 1)',
                    'rgba(16, 185, 129, 1)',
                    'rgba(168, 85, 247, 1)',
                    'rgba(234, 179, 8, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 15,
                        padding: 20,
                        font: { size: 14 }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleFont: { size: 16 },
                    bodyFont: { size: 14 },
                    padding: 10
                }
            },
            animation: {
                duration: 1000,
                easing: 'easeOutQuart'
            }
        }
    });
</script>
