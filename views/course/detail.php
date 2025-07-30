
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết khóa học - Hệ thống học tập</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #007bff;
            --primary-dark: #0056b3;
            --secondary: #6c757d;
            --secondary-dark: #5a6268;
            --accent: #ffcc00;
            --inactive: #ccc;
            --light: #f8f9fa;
            --gray: #6c757d;
            --dark: #343a40;
            --radius: 12px;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--light);
            font-family: 'Inter', sans-serif;
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header khóa học */
        .course-header {
            background: linear-gradient(135deg, var(--dark) 0%, var(--primary) 100%);
            color: white;
            padding: 2.5rem;
            border-radius: var(--radius) var(--radius) 0 0;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .course-header::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .course-header::after {
            content: '';
            position: absolute;
            bottom: -30px;
            left: -30px;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .course-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
            position: relative;
            z-index: 2;
        }

        .course-header .meta {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 15px;
            position: relative;
            z-index: 2;
        }

        .course-header .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.95rem;
            background: rgba(255, 255, 255, 0.15);
            padding: 5px 12px;
            border-radius: 20px;
        }

        /* Card chung */
        .card {
            border: none;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            background-color: white;
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
        }

        .card-body {
            padding: 1.8rem;
        }

        .card-title {
            color: var(--dark);
            font-weight: 700;
            margin-bottom: 1.5rem;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            color: var(--primary);
        }

        /* Nút bấm */
        .btn {
            border: none;
            padding: 0.8rem 1.8rem;
            border-radius: 8px;
            font-weight: 600;
            transition: var(--transition);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
        }

        .btn-primary:disabled {
            background-color: var(--gray);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-secondary {
            background-color: var(--secondary);
            color: white;
        }

        .btn-secondary:hover {
            background-color: var(--secondary-dark);
            transform: translateY(-3px);
        }

        /* Rating stars */
        .rating-stars .star {
            font-size: 1.2rem;
            color: var(--inactive);
            transition: color 0.2s ease;
        }

        .rating-stars .star.filled {
            color: var(--accent);
        }

        /* Badge */
        .badge {
            background-color: var(--secondary);
            color: white;
            padding: 0.4rem 0.9rem;
            border-radius: 20px;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            display: inline-block;
            transition: var(--transition);
        }

        .badge:hover {
            transform: translateY(-2px);
        }

        /* Course info */
        .course-info {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .info-item {
            background: var(--light);
            padding: 1.2rem;
            border-radius: 10px;
            transition: var(--transition);
        }

        .info-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        .info-item h3 {
            font-size: 1rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .info-item p {
            font-size: 1.1rem;
            color: var(--dark);
            margin: 0;
            font-weight: 600;
        }

        .info-item .status {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .status-open {
            background: rgba(46, 204, 113, 0.15);
            color: #2ecc71;
        }

        .status-closed {
            background: rgba(231, 76, 60, 0.15);
            color: #e74c3c;
        }

        .status-in_progress {
            background: rgba(0, 123, 255, 0.15);
            color: var(--primary);
        }

        /* Document card */
        .document-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .document-card {
            border-left: 4px solid var(--primary);
            transition: var(--transition);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .document-card:hover {
            border-left: 4px solid var(--accent);
        }

        .document-card .card-body {
            flex: 1;
        }

        .document-card .card-title {
            margin-bottom: 0.8rem;
            font-size: 1.2rem;
        }

        .document-card .card-text {
            color: var(--dark);
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }

        .document-meta {
            display: flex;
            justify-content: space-between;
            margin-top: 1.2rem;
            font-size: 0.85rem;
            color: var(--gray);
        }

        /* Member card */
        .members-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.2rem;
        }

        .member-card {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: var(--light);
            border-radius: 10px;
            transition: var(--transition);
        }

        .member-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
        }

        .member-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
            margin-right: 15px;
        }

        .member-info h4 {
            font-size: 1rem;
            margin-bottom: 0.2rem;
            color: var(--dark);
        }

        .member-info p {
            font-size: 0.85rem;
            color: var(--gray);
            margin: 0;
        }

        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--inactive);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .course-header h1 {
                font-size: 1.8rem;
            }

            .course-info {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
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
                            $statusLabels = ['open' => 'Mở đăng ký', 'closed' => 'Đã đóng', 'in_progress' => 'Đang diễn ra'];
                            $statusClasses = ['open' => 'status-open', 'closed' => 'status-closed', 'in_progress' => 'status-in_progress'];
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
</body>
</html>
