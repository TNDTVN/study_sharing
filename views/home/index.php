<div class="container-fluid py-5" style="max-width: 1600px;">
    <div class="bg-white p-5 rounded-3 shadow-lg" style="background: linear-gradient(145deg, #ffffff, #e6f0fa);">
        <h1 class="display-4 fw-bold text-center text-blue-dark mb-4 animate__animated animate__fadeIn">
            Chào mừng đến với Website Chia sẻ Tài liệu
        </h1>
        <p class="lead text-center text-blue-light mb-5 animate__animated animate__fadeIn" style="animation-delay: 0.2s; font-size: 1.25rem;">
            Khám phá, chia sẻ và học hỏi từ kho tài liệu phong phú ngay hôm nay!
        </p>

        <!-- Danh mục tài liệu -->
        <section class="mb-5">
            <h2 class="h2 fw-semibold text-blue-dark mb-4 animate__animated animate__fadeInUp">Danh mục tài liệu</h2>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-5 g-4">
                <?php foreach ($categories as $index => $category): ?>
                    <div class="col animate__animated animate__zoomIn" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                        <a href="/study_sharing/document/list?category_id=<?php echo $category['category_id']; ?>" class="card h-100 text-decoration-none border-0 shadow-sm category-card">
                            <div class="card-body text-center p-4">
                                <i class="bi bi-folder-fill fs-1 text-blue-primary mb-3"></i>
                                <h5 class="card-title fw-bold text-blue-dark"><?php echo htmlspecialchars($category['category_name']); ?></h5>
                                <p class="card-text text-blue-light small"><?php echo htmlspecialchars($category['description'] ?? 'Không có mô tả'); ?></p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Tài liệu mới nhất -->
        <section class="mb-5">
            <h2 class="h2 fw-semibold text-blue-dark mb-4 animate__animated animate__fadeInUp">Tài liệu mới nhất</h2>
            <?php if ($latestDocuments): ?>
                <div id="latestDocsCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php foreach (array_chunk($latestDocuments, 4) as $index => $chunk): ?>
                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                                    <?php foreach ($chunk as $doc): ?>
                                        <div class="col animate__animated animate__fadeInUp" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                                            <div class="card h-100 border-0 shadow-sm doc-card">
                                                <div class="card-body p-4">
                                                    <h5 class="card-title fw-bold text-blue-dark">
                                                        <a href="/study_sharing/document/detail/<?php echo $doc['document_id']; ?>" class="text-decoration-none text-blue-primary">
                                                            <?php echo htmlspecialchars($doc['title']); ?>
                                                        </a>
                                                    </h5>
                                                    <p class="card-text text-blue-light small"><?php echo htmlspecialchars($doc['description'] ?? 'Không có mô tả'); ?></p>
                                                    <?php
                                                    $user = $userModel->getUserById($doc['account_id']);
                                                    $uploaderName = $user ? htmlspecialchars($user['full_name']) : 'Không xác định';
                                                    ?>
                                                    <p class="card-text small text-blue-light">
                                                        <i class="bi bi-person-circle me-1"></i> <?php echo $uploaderName; ?> - 
                                                        <i class="bi bi-calendar3 me-1"></i> <?php echo date('d/m/Y', strtotime($doc['upload_date'])); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#latestDocsCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon bg-blue-dark rounded-circle" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#latestDocsCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon bg-blue-dark rounded-circle" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            <?php else: ?>
                <p class="text-blue-light text-center">Không có tài liệu nào để hiển thị.</p>
            <?php endif; ?>
        </section>

        <!-- Khóa học nổi bật -->
        <section class="mb-5">
            <h2 class="h2 fw-semibold text-blue-dark mb-4 animate__animated animate__fadeInUp">Khóa học nổi bật</h2>
            <?php if ($courses): ?>
                <div id="coursesCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php foreach (array_chunk($courses, 4) as $index => $chunk): ?>
                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                                    <?php foreach ($chunk as $course): ?>
                                        <div class="col animate__animated animate__fadeInUp" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                                            <div class="card h-100 border-0 shadow-sm course-card">
                                                <div class="card-body p-4">
                                                    <h5 class="card-title fw-bold text-blue-dark">
                                                        <a href="/study_sharing/course/detail/<?php echo $course['course_id']; ?>" class="text-decoration-none text-blue-primary">
                                                            <?php echo htmlspecialchars($course['course_name']); ?>
                                                        </a>
                                                    </h5>
                                                    <p class="card-text text-blue-light small"><?php echo htmlspecialchars($course['description'] ?? 'Không có mô tả'); ?></p>
                                                    <?php
                                                    $creator = $userModel->getUserById($course['creator_id']);
                                                    $creatorName = $creator ? htmlspecialchars($creator['full_name']) : 'Không xác định';
                                                    ?>
                                                    <p class="card-text small text-blue-light">
                                                        <i class="bi bi-person-circle me-1"></i> <?php echo $creatorName; ?> - 
                                                        <i class="bi bi-calendar3 me-1"></i> <?php echo date('d/m/Y', strtotime($course['created_at'])); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#coursesCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon bg-blue-dark rounded-circle" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#coursesCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon bg-blue-dark rounded-circle" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            <?php else: ?>
                <p class="text-blue-light text-center">Không có khóa học nào để hiển thị.</p>
            <?php endif; ?>
        </section>

        <!-- Thông báo -->
        <?php if (isset($_SESSION['account_id']) && $notifications): ?>
            <section>
                <h2 class="h2 fw-semibold text-blue-dark mb-4 animate__animated animate__fadeInUp">Thông báo</h2>
                <div class="card border-0 shadow-sm notification-card p-4">
                    <?php foreach ($notifications as $index => $notification): ?>
                        <div class="d-flex align-items-center mb-3 animate__animated animate__fadeIn" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                            <i class="bi bi-bell-fill fs-4 me-3 <?php echo $notification['is_read'] ? 'text-blue-light' : 'text-blue-primary'; ?>"></i>
                            <div>
                                <p class="mb-0 <?php echo $notification['is_read'] ? 'text-blue-light' : 'fw-bold text-blue-dark'; ?>">
                                    <?php echo htmlspecialchars($notification['message']); ?>
                                </p>
                                <small class="text-blue-light"><?php echo $notification['created_at']; ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</div>

<style>
    body {
        background-color: #e6f0fa;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        margin: 0;
    }

    /* Color definitions */
    :root {
        --blue-primary: #007bff;
        --blue-dark: #005a8d;
        --blue-light: #6b7280;
        --white: #ffffff;
        --white-light: #f8f9fa;
    }

    /* Container and card styles */
    .container-fluid {
        padding-left: 2rem;
        padding-right: 2rem;
    }

    .card {
        background: var(--white);
        border-radius: 12px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        overflow: hidden;
    }

    .card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 24px rgba(0, 91, 141, 0.15) !important;
    }

    .category-card {
        border-left: 6px solid var(--blue-primary);
    }

    .doc-card {
        border-left: 6px solid #00b4d8;
    }

    .course-card {
        border-left: 6px solid #4b9cd3;
    }

    .notification-card {
        border-left: 6px solid #4ba3c7;
        background: linear-gradient(145deg, var(--white), var(--white-light));
    }

    /* Text colors */
    .text-blue-dark {
        color: var(--blue-dark);
    }

    .text-blue-primary {
        color: var(--blue-primary);
    }

    .text-blue-light {
        color: var(--blue-light);
    }

    /* Carousel controls */
    .carousel-control-prev, .carousel-control-next {
        width: 4%;
        opacity: 0.8;
        transition: opacity 0.3s ease, transform 0.3s ease;
        transform: translateX(0);
    }

    .carousel-control-prev {
        transform: translateX(-30px);
    }

    .carousel-control-next {
        transform: translateX(30px);
    }

    .carousel-control-prev:hover {
        opacity: 1;
        transform: translateX(-35px);
    }

    .carousel-control-next:hover {
        transform: translateX(35px);
    }

    .carousel-control-prev-icon, .carousel-control-next-icon {
        width: 3rem;
        height: 3rem;
        background-size: 50%;
        background-color: var(--blue-dark);
        border-radius: 50%;
    }

    /* Responsive adjustments */
    @media (max-width: 992px) {
        .carousel-control-prev {
            transform: translateX(-20px);
        }

        .carousel-control-next {
            transform: translateX(20px);
        }

        .carousel-control-prev:hover {
            transform: translateX(-25px);
        }

        .carousel-control-next:hover {
            transform: translateX(25px);
        }

        .carousel-control-prev, .carousel-control-next {
            width: 8%;
        }
    }

    @media (max-width: 576px) {
        .carousel-control-prev {
            transform: translateX(-15px);
        }

        .carousel-control-next {
            transform: translateX(15px);
        }

        .carousel-control-prev:hover {
            transform: translateX(-20px);
        }

        .carousel-control-next:hover {
            transform: translateX(20px);
        }

        .carousel-control-prev, .carousel-control-next {
            width: 12%;
        }
    }

    /* Animation */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes zoomIn {
        from { opacity: 0; transform: scale(0.9); }
        to { opacity: 1; transform: scale(1); }
    }
</style>

<!-- Include Animate.css for animations -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">