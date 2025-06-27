<style>
    .rating-stars .star {
        font-size: 20px;
        color: #ccc;
    }

    .rating-stars .star.filled {
        color: #ffcc00;
    }
</style>
<div class="container">
    <h1 class="mb-4"><?php echo htmlspecialchars($course['course_name']); ?></h1>

    <!-- Thông tin khóa học -->
    <div class="card mb-4">
        <div class="card-body">
            <p><strong>Mô tả:</strong> <?php echo htmlspecialchars($course['description'] ?? 'Không có mô tả'); ?></p>
            <p><strong>Người tạo:</strong> <?php echo htmlspecialchars($creator['full_name'] ?? 'Ẩn danh'); ?></p>
            <p><strong>Ngày tạo:</strong> <?php echo date('d/m/Y H:i', strtotime($course['created_at'])); ?></p>
            <p><strong>Link học tập:</strong>
                <?php if ($course['learn_link']): ?>
                    <a class="text-decoration-none" href="<?php echo htmlspecialchars($course['learn_link']); ?>" target="_blank"><?php echo htmlspecialchars($course['learn_link']); ?></a>
                <?php else: ?>
                    Chưa có link
                <?php endif; ?>
            </p>
            <p><strong>Ngày bắt đầu:</strong> <?php echo $course['start_date'] ? date('d/m/Y', strtotime($course['start_date'])) : 'Chưa xác định'; ?></p>
            <p><strong>Ngày kết thúc:</strong> <?php echo $course['end_date'] ? date('d/m/Y', strtotime($course['end_date'])) : 'Chưa xác định'; ?></p>
            <p><strong>Trạng thái:</strong>
                <?php
                $statusLabels = ['open' => 'Mở đăng ký', 'closed' => 'Đã đóng', 'in_progress' => 'Đang diễn ra'];
                echo $statusLabels[$course['status']] ?? 'Không xác định';
                ?>
            </p>
            <p><strong>Số lượng thành viên:</strong> <?php echo $member_count; ?> / <?php echo $course['max_members'] ?: 'Không giới hạn'; ?></p>
            <?php if (isset($_SESSION['account_id'])): ?>
                <button id="joinCourseBtn" class="btn btn-primary" data-course-id="<?php echo $course['course_id']; ?>"
                    <?php echo ($course['status'] !== 'open' || ($course['max_members'] && $member_count >= $course['max_members'])) ? 'disabled' : ''; ?>>
                    Tham gia khóa học
                </button>
            <?php else: ?>
                <p class="text-muted mt-2"><a href="#" class="show-login-modal text-decoration-none" data-bs-toggle="modal" data-bs-target="#loginModal">Đăng nhập</a> để tham gia khóa học.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Danh sách tài liệu liên quan -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Tài liệu khóa học</h5>
            <?php if (empty($documents)): ?>
                <p class="text-muted">Chưa có tài liệu nào trong khóa học này.</p>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 g-4">
                    <?php foreach ($documents as $doc): ?>
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a class="text-decoration-none" href="/study_sharing/document/detail/<?php echo $doc['document_id']; ?>">
                                            <?php echo htmlspecialchars($doc['title']); ?>
                                        </a>
                                    </h5>
                                    <p class="card-text"><?php echo htmlspecialchars(substr($doc['description'] ?? '', 0, 100)); ?>...</p>
                                    <p class="card-text"><small class="text-muted">Danh mục: <?php echo htmlspecialchars($doc['category_name'] ?? 'Không có'); ?></small></p>
                                    <p class="card-text"><small class="text-muted">Người tải lên: <?php echo htmlspecialchars($doc['full_name'] ?? 'Ẩn danh'); ?></small></p>
                                    <p class="card-text"><small class="text-muted">Ngày tải: <?php echo date('d/m/Y', strtotime($doc['upload_date'])); ?></small></p>
                                    <p class="card-text">
                                        Rating: <?php echo $doc['avg_rating'] ? number_format($doc['avg_rating'], 1) . '/5' : 'Chưa có đánh giá'; ?>
                                        <span class="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <span class="star <?php echo ($i <= round($doc['avg_rating'])) ? 'filled' : ''; ?>">★</span>
                                            <?php endfor; ?>
                                        </span>
                                    </p>
                                    <p class="card-text">
                                        <?php foreach ($doc['tags'] as $tag): ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($tag); ?></span>
                                        <?php endforeach; ?>
                                    </p>
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
            <h5 class="card-title">Thành viên khóa học</h5>
            <?php if (empty($members)): ?>
                <p class="text-muted">Chưa có thành viên nào tham gia khóa học này.</p>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    <?php foreach ($members as $member): ?>
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <img src="/study_sharing/assets/images/<?php echo $member['avatar'] ?: 'profile.png'; ?>" alt="Avatar" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                    <div>
                                        <strong><?php echo htmlspecialchars($member['full_name']); ?></strong>
                                        <p class="card-text"><small class="text-muted">Tham gia: <?php echo date('d/m/Y', strtotime($member['join_date'])); ?></small></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="/study_sharing/assets/js/course_detail.js"></script>