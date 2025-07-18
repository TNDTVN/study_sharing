<div class="container">
    <h1 class="mb-4 text-center"><?php echo htmlspecialchars($title); ?></h1>

    <?php if (empty($overviewStats['total_documents'])): ?>
        <div class="alert alert-info text-center">Bạn chưa có tài liệu nào để thống kê.</div>
    <?php else: ?>
        <!-- Phần tổng quan -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Tổng quan</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h3 class="text-primary"><?php echo $overviewStats['total_documents']; ?></h3>
                                <p class="text-muted">Tài liệu</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 text-center mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h3 class="text-success"><?php echo $overviewStats['total_downloads']; ?></h3>
                                <p class="text-muted">Lượt tải</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 text-center mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h3 class="text-info"><?php echo $overviewStats['total_comments']; ?></h3>
                                <p class="text-muted">Bình luận</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 text-center mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h3 class="text-warning"><?php echo $overviewStats['avg_rating']; ?>/5</h3>
                                <p class="text-muted">Đánh giá TB</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Biểu đồ loại file -->
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Loại tệp tài liệu</h5>
                                <canvas id="fileTypeChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Hoạt động gần đây (7 ngày)</h5>
                                <canvas id="recentActivityChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Phần thống kê theo danh mục -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">Thống kê theo danh mục</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Số lượng tài liệu</h5>
                                <canvas id="documentCountChart" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Lượt tải xuống</h5>
                                <canvas id="downloadCountChart" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Số bình luận</h5>
                                <canvas id="commentCountChart" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Đánh giá trung bình</h5>
                                <canvas id="ratingAvgChart" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Phần thống kê theo thời gian -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">Thống kê theo thời gian</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Hoạt động trong 12 tháng gần nhất</h5>
                                <canvas id="monthlyActivityChart" height="130"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Phần tài liệu phổ biến -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h5 class="card-title mb-0">Tài liệu phổ biến</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Tải nhiều nhất</h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($popularDocs['by_downloads'] as $doc): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <a href="/study_sharing/document/detail/<?php echo $doc['document_id']; ?>" class="no-underline">
                                                <?php echo htmlspecialchars($doc['title']); ?>
                                            </a>
                                            <span class="badge bg-primary rounded-pill"><?php echo $doc['download_count']; ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Bình luận nhiều nhất</h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($popularDocs['by_comments'] as $doc): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <a href="/study_sharing/document/detail/<?php echo $doc['document_id']; ?>" class="no-underline">
                                                <?php echo htmlspecialchars($doc['title']); ?>
                                            </a>
                                            <span class="badge bg-success rounded-pill"><?php echo $doc['comment_count']; ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Đánh giá cao nhất</h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($popularDocs['by_ratings'] as $doc): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <a href="/study_sharing/document/detail/<?php echo $doc['document_id']; ?>" class="no-underline">
                                                <?php echo htmlspecialchars($doc['title']); ?>
                                            </a>
                                            <span class="badge bg-warning rounded-pill"><?php echo round($doc['avg_rating'], 1); ?>/5</span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    .no-underline {
        text-decoration: none;
    }
</style>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Màu sắc theo danh mục
        const categoryColors = {};
        const categories = <?php echo json_encode(array_column($categoryStats['document_counts'], 'category_name')); ?>;
        const colorPalette = [
            '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
            '#5a5c69', '#858796', '#dddfeb', '#f8f9fc', '#5f6b6d'
        ];

        categories.forEach((category, index) => {
            categoryColors[category] = colorPalette[index % colorPalette.length];
        });

        // 1. Biểu đồ loại file
        const fileTypeCtx = document.getElementById('fileTypeChart').getContext('2d');
        new Chart(fileTypeCtx, {
            type: 'pie',
            data: {
                labels: ['PDF', 'DOCX', 'PPTX'],
                datasets: [{
                    data: [
                        <?php echo $overviewStats['file_types']['pdf_count']; ?>,
                        <?php echo $overviewStats['file_types']['docx_count']; ?>,
                        <?php echo $overviewStats['file_types']['pptx_count']; ?>
                    ],
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc'],
                    borderColor: '#fff',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
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

        // 2. Biểu đồ hoạt động gần đây
        const recentActivityCtx = document.getElementById('recentActivityChart').getContext('2d');
        new Chart(recentActivityCtx, {
            type: 'bar',
            data: {
                labels: ['Tài liệu mới', 'Lượt tải', 'Bình luận'],
                datasets: [{
                    label: 'Số lượng',
                    data: [
                        <?php echo $timeStats['recent_uploads']; ?>,
                        <?php echo $timeStats['recent_downloads']; ?>,
                        <?php echo $timeStats['recent_comments']; ?>
                    ],
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc'],
                    borderColor: ['#4e73df', '#1cc88a', '#36b9cc'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // 3.1 Số lượng tài liệu
        const documentCountCtx = document.getElementById('documentCountChart').getContext('2d');
        const documentLabels = <?php echo json_encode(array_column($categoryStats['document_counts'], 'category_name')); ?>;
        const documentData = <?php echo json_encode(array_column($categoryStats['document_counts'], 'document_count')); ?>;
        new Chart(documentCountCtx, {
            type: 'pie',
            data: {
                labels: documentLabels,
                datasets: [{
                    data: documentData,
                    backgroundColor: documentLabels.map(label => categoryColors[label]),
                    borderColor: '#fff',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
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

        // 3.2 Lượt tải
        const downloadCountCtx = document.getElementById('downloadCountChart').getContext('2d');
        const downloadLabels = <?php echo json_encode(array_column($categoryStats['download_counts'], 'category_name')); ?>;
        const downloadData = <?php echo json_encode(array_column($categoryStats['download_counts'], 'download_count')); ?>;
        new Chart(downloadCountCtx, {
            type: 'pie',
            data: {
                labels: downloadLabels,
                datasets: [{
                    data: downloadData,
                    backgroundColor: downloadLabels.map(label => categoryColors[label]),
                    borderColor: '#fff',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
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

        // 3.3 Bình luận
        const commentCountCtx = document.getElementById('commentCountChart').getContext('2d');
        const commentLabels = <?php echo json_encode(array_column($categoryStats['comment_counts'], 'category_name')); ?>;
        const commentData = <?php echo json_encode(array_column($categoryStats['comment_counts'], 'comment_count')); ?>;
        new Chart(commentCountCtx, {
            type: 'pie',
            data: {
                labels: commentLabels,
                datasets: [{
                    data: commentData,
                    backgroundColor: commentLabels.map(label => categoryColors[label]),
                    borderColor: '#fff',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
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

        // 3.4 Đánh giá trung bình
        const ratingAvgCtx = document.getElementById('ratingAvgChart').getContext('2d');
        const ratingLabels = <?php echo json_encode(array_column($categoryStats['rating_avgs'], 'category_name')); ?>;
        const ratingData = <?php echo json_encode(array_map(function ($item) {
                                return $item['avg_rating'] ? round($item['avg_rating'], 1) : 0;
                            }, $categoryStats['rating_avgs'])); ?>;
        new Chart(ratingAvgCtx, {
            type: 'bar',
            data: {
                labels: ratingLabels,
                datasets: [{
                    label: 'Đánh giá trung bình',
                    data: ratingData,
                    backgroundColor: ratingLabels.map(label => categoryColors[label]),
                    borderColor: ratingLabels.map(label => categoryColors[label]),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 5,
                        title: {
                            display: true,
                            text: 'Điểm đánh giá (0-5)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Danh mục'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.parsed.y.toFixed(1)}/5`;
                            }
                        }
                    }
                }
            }
        });

        // 4. Biểu đồ hoạt động theo tháng
        const monthlyActivityCtx = document.getElementById('monthlyActivityChart').getContext('2d');
        new Chart(monthlyActivityCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($timeStats['monthly_stats'], 'month')); ?>,
                datasets: [{
                        label: 'Tài liệu mới',
                        data: <?php echo json_encode(array_column($timeStats['monthly_stats'], 'uploads')); ?>,
                        backgroundColor: 'rgba(78, 115, 223, 0.05)',
                        borderColor: '#4e73df',
                        pointBackgroundColor: '#4e73df',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#4e73df',
                        pointHoverBorderColor: '#4e73df',
                        borderWidth: 2,
                        tension: 0.3
                    },
                    {
                        label: 'Lượt tải',
                        data: <?php echo json_encode(array_column($timeStats['monthly_stats'], 'downloads')); ?>,
                        backgroundColor: 'rgba(28, 200, 138, 0.05)',
                        borderColor: '#1cc88a',
                        pointBackgroundColor: '#1cc88a',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#1cc88a',
                        pointHoverBorderColor: '#1cc88a',
                        borderWidth: 2,
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Số lượng'
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
                        position: 'top',
                    }
                }
            }
        });
    });
</script>