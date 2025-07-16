<div class="container py-4">
    <div class="bg-white p-4 rounded shadow-sm">
        <h1 class="h3 mb-4 text-primary">Thông báo của bạn</h1>

        <?php if ($notifications): ?>
            <div class="mb-3">
                <button class="btn btn-primary btn-sm" id="markAllRead"><i class="bi bi-check-all"></i> Đánh dấu tất cả đã đọc</button>
                <button class="btn btn-danger btn-sm" id="deleteAll"><i class="bi bi-trash"></i> Xóa tất cả</button>
            </div>

            <div class="list-group">
                <?php foreach ($notifications as $notification): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-start <?php echo $notification['is_read'] ? 'text-secondary' : 'fw-bold'; ?>">
                        <div>
                            <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                            <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?></small>
                        </div>
                        <div>
                            <?php if (!$notification['is_read']): ?>
                                <button class="btn btn-outline-primary btn-sm mark-read" data-id="<?php echo $notification['notification_id']; ?>"><i class="bi bi-check"></i></button>
                            <?php endif; ?>
                            <button class="btn btn-outline-danger btn-sm delete-notification" data-id="<?php echo $notification['notification_id']; ?>"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Phân trang -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                <span aria-hidden="true">«</span>
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                                <span aria-hidden="true">»</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <p class="text-muted">Bạn chưa có thông báo nào.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Đánh dấu đã đọc
        document.querySelectorAll('.mark-read').forEach(button => {
            button.addEventListener('click', function() {
                const notificationId = this.getAttribute('data-id');
                fetch('/study_sharing/notification/markRead', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `notification_id=${notificationId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            button.closest('.list-group-item').classList.remove('fw-bold');
                            button.closest('.list-group-item').classList.add('text-secondary');
                            button.remove();
                        } else {
                            alert('Có lỗi xảy ra khi đánh dấu đã đọc.');
                        }
                    });
            });
        });

        // Xóa thông báo
        document.querySelectorAll('.delete-notification').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('Bạn có chắc chắn muốn xóa thông báo này?')) {
                    const notificationId = this.getAttribute('data-id');
                    fetch('/study_sharing/notification/delete', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `notification_id=${notificationId}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                button.closest('.list-group-item').remove();
                            } else {
                                alert('Có lỗi xảy ra khi xóa thông báo.');
                            }
                        });
                }
            });
        });

        // Đánh dấu tất cả đã đọc
        document.getElementById('markAllRead').addEventListener('click', function() {
            fetch('/study_sharing/notification/markAllRead', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `user_id=<?php echo $_SESSION['account_id']; ?>`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelectorAll('.list-group-item').forEach(item => {
                            item.classList.remove('fw-bold');
                            item.classList.add('text-secondary');
                            const markReadButton = item.querySelector('.mark-read');
                            if (markReadButton) markReadButton.remove();
                        });
                    } else {
                        alert('Có lỗi xảy ra khi đánh dấu tất cả đã đọc.');
                    }
                });
        });

        // Xóa tất cả thông báo
        document.getElementById('deleteAll').addEventListener('click', function() {
            if (confirm('Bạn có chắc chắn muốn xóa tất cả thông báo?')) {
                fetch('/study_sharing/notification/deleteAll', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `user_id=<?php echo $_SESSION['account_id']; ?>`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.querySelector('.list-group').innerHTML = '<p class="text-muted">Bạn chưa có thông báo nào.</p>';
                            document.getElementById('markAllRead').remove();
                            document.getElementById('deleteAll').remove();
                        } else {
                            alert('Có lỗi xảy ra khi xóa tất cả thông báo.');
                        }
                    });
            }
        });
    });
</script>