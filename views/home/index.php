<div class="container py-4">
    <div class="bg-white p-4 rounded shadow-sm">
        <h1 class="display-4 text-primary mb-4">Chào mừng đến với Hệ thống Chia sẻ Tài liệu</h1>
        <p class="lead mb-4 text-muted">Khám phá và chia sẻ tài liệu học tập của bạn ngay hôm nay!</p>

        <!-- Danh mục tài liệu -->
        <section class="mb-5">
            <h2 class="h4 mb-3 text-dark">Danh mục tài liệu</h2>
            <div class="row row-cols-1 row-cols-md-4 g-3">
                <?php foreach ($categories as $category): ?>
                    <div class="col">
                        <a href="/study_sharing/document/list?category_id=<?php echo $category['category_id']; ?>" class="card h-100 bg-light text-decoration-none">
                            <div class="card-body text-center">
                                <h5 class="card-title"><?php echo htmlspecialchars($category['category_name']); ?></h5>
                                <p class="card-text text-muted"><?php echo htmlspecialchars($category['description'] ?? ''); ?></p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Tài liệu mới nhất -->
        <section class="mb-5">
            <h2 class="h4 mb-3 text-dark">Tài liệu mới nhất</h2>
            <?php if ($latestDocuments): ?>
                <div class="row row-cols-1 row-cols-md-3 g-3">
                    <?php foreach ($latestDocuments as $doc): ?>
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title text-primary">
                                        <a href="/study_sharing/document/detail/<?php echo $doc['document_id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($doc['title']); ?>
                                        </a>
                                    </h5>
                                    <p class="card-text text-muted"><?php echo htmlspecialchars($doc['description'] ?? ''); ?></p>
                                    <?php
                                    $user = $userModel->getUserById($doc['account_id']);
                                    $uploaderName = $user ? htmlspecialchars($user['full_name']) : 'Không xác định';
                                    ?>
                                    <p class="card-text text-small text-muted">Đăng bởi: <?php echo $uploaderName; ?> - <?php echo date('d/m/Y', strtotime($doc['upload_date'])); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted">Không có tài liệu nào để hiển thị.</p>
            <?php endif; ?>
        </section>

        <!-- Khóa học nổi bật -->
        <section class="mb-5">
            <h2 class="h4 mb-3 text-dark">Khóa học nổi bật</h2>
            <?php if ($courses): ?>
                <div class="row row-cols-1 row-cols-md-3 g-3">
                    <?php foreach ($courses as $course): ?>
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title text-primary">
                                        <a href="/study_sharing/course/detail/<?php echo $course['course_id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($course['course_name']); ?>
                                        </a>
                                    </h5>
                                    <p class="card-text text-muted"><?php echo htmlspecialchars($course['description'] ?? ''); ?></p>
                                    <?php
                                    $creator = $userModel->getUserById($course['creator_id']);
                                    $creatorName = $creator ? htmlspecialchars($creator['full_name']) : 'Không xác định';
                                    ?>
                                    <p class="card-text text-small text-muted">Tạo bởi: <?php echo $creatorName; ?> - <?php echo date('d/m/Y', strtotime($course['created_at'])); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted">Không có khóa học nào để hiển thị.</p>
            <?php endif; ?>
        </section>

        <!-- Thông báo (nếu đã đăng nhập) -->
        <?php if (isset($_SESSION['account_id']) && $notifications): ?>
            <section>
                <h2 class="h4 mb-3 text-dark">Thông báo</h2>
                <div class="card p-3 shadow-sm">
                    <?php foreach ($notifications as $notification): ?>
                        <p class="text-dark <?php echo $notification['is_read'] ? 'text-secondary' : 'fw-bold'; ?>">
                            <?php echo htmlspecialchars($notification['message']); ?> - <?php echo $notification['created_at']; ?>
                        </p>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</div>