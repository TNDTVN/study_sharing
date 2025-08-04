<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<title>Chi tiết khóa học - Hệ thống học tập</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="/study_sharing/assets/css/course_detail.css">

<div class="container">
    <!-- Header khóa học -->
    <div class="course-header">
        <h1><?php echo htmlspecialchars($course['course_name']); ?></h1>
        <div class="meta">
            <div class="meta-item">
                <i class="fas fa-user"></i>
                <span><?php echo htmlspecialchars($creator['full_name'] ?? 'Ẩn danh'); ?></span>
            </div>
            <div class="meta-item">
                <i class="fas fa-calendar"></i>
                <span><?php echo date('d/m/Y', strtotime($course['created_at'])); ?></span>
            </div>
            <div class="meta-item">
                <i class="fas fa-users"></i>
                <span><?php echo $member_count; ?> thành viên</span>
            </div>
        </div>
    </div>

    <!-- Thông tin khóa học -->
    <div class="card">
        <div class="card-body">
            <h2 class="card-title"><i class="fas fa-info-circle"></i> Thông tin khóa học</h2>
            <div class="course-info">
                <div class="info-item">
                    <h3>Mô tả</h3>
                    <p><?php echo htmlspecialchars($course['description'] ?? 'Không có mô tả'); ?></p>
                </div>
                <div class="info-item">
                    <h3>Người tạo</h3>
                    <p><?php echo htmlspecialchars($creator['full_name'] ?? 'Ẩn danh'); ?></p>
                </div>
                <div class="info-item">
                    <h3>Ngày tạo</h3>
                    <p><?php echo date('d/m/Y H:i', strtotime($course['created_at'])); ?></p>
                </div>
                <div class="info-item">
                    <h3>Link học tập</h3>
                    <p>
                        <?php if ($course['learn_link']): ?>
                            <a href="<?php echo htmlspecialchars($course['learn_link']); ?>" target="_blank" style="color: var(--primary); text-decoration: none;">
                                <?php echo htmlspecialchars($course['learn_link']); ?>
                            </a>
                        <?php else: ?>
                            Chưa có link
                        <?php endif; ?>
                    </p>
                </div>
                <div class="info-item">
                    <h3>Ngày bắt đầu</h3>
                    <p><?php echo $course['start_date'] ? date('d/m/Y', strtotime($course['start_date'])) : 'Chưa xác định'; ?></p>
                </div>
                <div class="info-item">
                    <h3>Ngày kết thúc</h3>
                    <p><?php echo $course['end_date'] ? date('d/m/Y', strtotime($course['end_date'])) : 'Chưa xác định'; ?></p>
                </div>
                <div class="info-item">
                    <h3>Trạng thái</h3>
                    <p>
                        <?php
                        $statusLabels = [
                            'open' => 'Mở đăng ký',
                            'closed' => 'Đã đóng',
                            'in_progress' => 'Đang diễn ra',
                            'pending' => 'Chưa duyệt',
                            'cancelled' => 'Đã hủy'
                        ];
                        $statusClasses = [
                            'open' => 'status-open',
                            'closed' => 'status-closed',
                            'in_progress' => 'status-in_progress',
                            'pending' => 'status-pending',
                            'cancelled' => 'status-cancelled'
                        ];
                        $status = $course['status'] ?? 'Không xác định';
                        ?>
                        <span class="status <?php echo $statusClasses[$status] ?? ''; ?>">
                            <?php echo $statusLabels[$status] ?? 'Không xác định'; ?>
                        </span>
                    </p>
                </div>
                <div class="info-item">
                    <h3>Số lượng thành viên</h3>
                    <p><?php echo $member_count; ?> / <?php echo $course['max_members'] ?: 'Không giới hạn'; ?></p>
                </div>
            </div>
            <div class="action-buttons">
                <?php
                $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
                $isCreator = isset($_SESSION['account_id']) && $course['creator_id'] == $_SESSION['account_id'];
                ?>
                <?php if ($course['status'] === 'pending' && !$isAdmin && !$isCreator): ?>
                    <p class="text-muted mt-2">Khóa học đang chờ duyệt và không thể tham gia.</p>
                <?php elseif ($course['status'] === 'cancelled' && !$isAdmin && !$isCreator): ?>
                    <p class="text-muted mt-2">Khóa học đã bị hủy và không thể tham gia.</p>
                <?php else: ?>
                    <?php if (isset($_SESSION['account_id'])): ?>
                        <button id="joinCourseBtn" class="btn btn-primary" data-course-id="<?php echo $course['course_id']; ?>"
                            <?php echo ($course['status'] !== 'open' || ($course['max_members'] && $member_count >= $course['max_members'])) ? 'disabled' : ''; ?>>
                            <i class="fas fa-user-plus"></i> Tham gia khóa học
                        </button>
                    <?php else: ?>
                        <p class="text-muted mt-2">
                            <a href="#" class="show-login-modal" style="color: var(--primary); text-decoration: none;" data-bs-toggle="modal" data-bs-target="#loginModal">
                                Đăng nhập
                            </a> để tham gia khóa học.
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
                <a href="#" id="backButton" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Trở về</a>
            </div>
        </div>
    </div>

    <!-- Danh sách tài liệu liên quan -->
    <div class="card">
        <div class="card-body">
            <h2 class="card-title"><i class="fas fa-file-alt"></i> Tài liệu khóa học</h2>
            <?php if (empty($documents)): ?>
                <div class="empty-state">
                    <i class="fas fa-file"></i>
                    <p>Chưa có tài liệu nào trong khóa học này.</p>
                </div>
            <?php else: ?>
                <div class="document-grid">
                    <?php foreach ($documents as $doc): ?>
                        <div class="card document-card">
                            <div class="card-body">
                                <h3 class="card-title">
                                    <a href="/study_sharing/document/detail/<?php echo $doc['document_id']; ?>" style="color: var(--dark); text-decoration: none;">
                                        <?php echo htmlspecialchars($doc['title']); ?>
                                    </a>
                                </h3>
                                <p class="card-text"><?php echo htmlspecialchars(substr($doc['description'] ?? '', 0, 100)); ?>...</p>
                                <div>
                                    <?php foreach ($doc['tags'] as $tag): ?>
                                        <span class="badge"><?php echo htmlspecialchars($tag); ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <div class="document-meta">
                                    <div>
                                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($doc['full_name'] ?? 'Ẩn danh'); ?>
                                    </div>
                                    <div>
                                        <i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($doc['upload_date'])); ?>
                                    </div>
                                </div>
                                <div class="rating-stars" style="margin-top: 15px;">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?php echo ($i <= round($doc['avg_rating'])) ? 'filled' : ''; ?>">★</span>
                                    <?php endfor; ?>
                                    <span style="margin-left: 5px; color: var(--dark);">
                                        <?php echo $doc['avg_rating'] ? number_format($doc['avg_rating'], 1) : 'Chưa có đánh giá'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Danh sách thành viên -->
    <div class="card">
        <div class="card-body">
            <h2 class="card-title"><i class="fas fa-users"></i> Thành viên khóa học</h2>
            <?php if (empty($members)): ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <p>Chưa có thành viên nào tham gia khóa học này.</p>
                </div>
            <?php else: ?>
                <div class="members-grid">
                    <?php foreach ($members as $member): ?>
                        <div class="member-card">
                            <img src="/study_sharing/assets/images/<?php echo $member['avatar'] ?: 'profile.png'; ?>" alt="Avatar" class="member-avatar">
                            <div class="member-info">
                                <h4><?php echo htmlspecialchars($member['full_name']); ?></h4>
                                <p>Tham gia: <?php echo date('d/m/Y', strtotime($member['join_date'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="/study_sharing/assets/js/course_detail.js"></script>
<script>
    document.getElementById('backButton').addEventListener('click', function(e) {
        e.preventDefault();
        if (document.referrer && document.referrer.includes('/study_sharing/')) {
            window.history.back();
        } else {
            window.location.href = '/study_sharing/';
        }
    });

    document.querySelectorAll('.card, .info-item, .member-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.cursor = 'pointer';
        });
        card.addEventListener('mouseleave', function() {
            this.style.cursor = 'default';
        });
    });
</script>