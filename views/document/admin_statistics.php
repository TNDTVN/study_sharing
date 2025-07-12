<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<style>
    .content {
        padding-top: 0;
    }

    .stat-card {
        transition: transform 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .chart-container {
        position: relative;
        height: 300px;
    }

    .card {
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }

    .card-header {
        border-radius: 10px 10px 0 0 !important;
        background-color: #f8f9fa;
        border-bottom: 1px solid #eaeaea;
    }

    h2 {
        color: #2c3e50;
        font-weight: 600;
        border-bottom: 2px solid #3498db;
        padding-bottom: 10px;
        margin-bottom: 25px;
    }

    .table {
        border-radius: 5px;
        overflow: hidden;
    }

    .table th {
        background-color: #3498db;
        color: white;
    }
</style>
<div class="container">
    <h2 class="mb-4">📊 Thống kê tài liệu</h2>

    <!-- Thông báo -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo htmlspecialchars($_SESSION['message_type']); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <!-- Tổng quan -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary stat-card">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-file-alt"></i> Tổng số tài liệu</h5>
                    <p class="card-text display-4"><?php echo $totalDocuments; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success stat-card">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-download"></i> Tổng lượt tải xuống</h5>
                    <p class="card-text display-4"><?php echo $totalDownloads; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info stat-card">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-folder"></i> Danh mục tài liệu</h5>
                    <p class="card-text display-4"><?php echo count($documentsByCategory); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Biểu đồ -->
    <div class="row mb-4">
        <!-- Biểu đồ số lượng tài liệu theo tháng -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5><i class="fas fa-calendar-alt"></i> Số lượng tài liệu theo tháng (Năm <?php echo $currentYear; ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="documentsByMonthChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Biểu đồ loại tệp -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5><i class="fas fa-file"></i> Số lượng tài liệu theo loại tệp</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="documentsByFileTypeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bảng danh mục -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-list"></i> Số lượng tài liệu theo danh mục</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Danh mục</th>
                                    <th>Số tài liệu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documentsByCategory as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['category_name'] ?? 'Không danh mục'); ?></td>
                                        <td><?php echo $category['document_count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top 5 tài liệu -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-trophy"></i> Top 5 tài liệu được tải nhiều nhất</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Tiêu đề</th>
                                    <th>Lượt tải</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topDocuments as $doc): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($doc['title']); ?></td>
                                        <td><?php echo $doc['download_count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript để vẽ biểu đồ -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Chart === 'undefined') {
            console.error('Chart.js không được tải! Vui lòng kiểm tra kết nối mạng hoặc đường dẫn CDN.');
            return;
        }

        // Biểu đồ số tài liệu theo tháng
        const ctxMonth = document.getElementById('documentsByMonthChart').getContext('2d');
        new Chart(ctxMonth, {
            type: 'bar',
            data: {
                labels: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'],
                datasets: [{
                    label: 'Số tài liệu',
                    data: [<?php echo implode(',', $months); ?>],
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Số lượng tài liệu'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Tháng'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });

        // Biểu đồ số tài liệu theo loại tệp
        const ctxFileType = document.getElementById('documentsByFileTypeChart').getContext('2d');
        new Chart(ctxFileType, {
            type: 'pie',
            data: {
                labels: [<?php echo !empty($documentsByFileType) ? '"' . implode('","', array_column($documentsByFileType, 'file_type')) . '"' : '""'; ?>],
                datasets: [{
                    label: 'Số tài liệu',
                    data: [<?php echo !empty($documentsByFileType) ? implode(',', array_column($documentsByFileType, 'document_count')) : '0'; ?>],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.6)',
                        'rgba(54, 162, 235, 0.6)',
                        'rgba(255, 206, 86, 0.6)',
                        'rgba(75, 192, 192, 0.6)',
                        'rgba(153, 102, 255, 0.6)',
                        'rgba(255, 159, 64, 0.6)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    });
</script>