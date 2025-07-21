document.addEventListener('DOMContentLoaded', function() {
    // Biểu đồ hoạt động theo tháng
    const monthlyCtx = document.getElementById('monthlyActivityChart').getContext('2d');
    new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6',
                'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'
            ],
            datasets: [{
                    label: 'Tài liệu mới',
                    data: Object.values(window.stats.monthly).map(item => item.upload_count),
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Lượt tải',
                    data: Object.values(window.stats.monthly).map(item => item.download_count),
                    borderColor: '#1cc88a',
                    backgroundColor: 'rgba(28, 200, 138, 0.1)',
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Bình luận',
                    data: Object.values(window.stats.monthly).map(item => item.comment_count),
                    borderColor: '#36b9cc',
                    backgroundColor: 'rgba(54, 185, 204, 0.1)',
                    tension: 0.3,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
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
            }
        }
    });

    // Biểu đồ cột: Tài liệu theo danh mục
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(categoryCtx, {
        type: 'bar',
        data: {
            labels: window.stats.categories.map(item => item.category_name),
            datasets: [{
                label: 'Số tài liệu',
                data: window.stats.categories.map(item => item.document_count),
                backgroundColor: 'rgba(78, 115, 223, 0.5)',
                borderColor: '#4e73df',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
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
                        text: 'Danh mục'
                    }
                }
            }
        }
    });

    // Biểu đồ tròn: Tài liệu theo định dạng
    const fileTypeCtx = document.getElementById('fileTypeChart').getContext('2d');
    new Chart(fileTypeCtx, {
        type: 'pie',
        data: {
            labels: window.stats.file_type_counts.map(item => item.file_type),
            datasets: [{
                label: 'Số tài liệu',
                data: window.stats.file_type_counts.map(item => item.count),
                backgroundColor: [
                    'rgba(78, 115, 223, 0.5)',
                    'rgba(28, 200, 138, 0.5)',
                    'rgba(54, 185, 204, 0.5)',
                    'rgba(255, 99, 132, 0.5)',
                    'rgba(255, 159, 64, 0.5)'
                ],
                borderColor: [
                    '#4e73df',
                    '#1cc88a',
                    '#36b9cc',
                    '#ff6384',
                    '#ff9f40'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            let value = context.parsed || 0;
                            let total = context.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
});