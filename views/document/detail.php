<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($document['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/study_sharing/assets/css/detail_document.css">
    <script src="https://unpkg.com/jszip@3.10.1/dist/jszip.min.js"></script>
    <script src="https://unpkg.com/docx-preview@latest/dist/docx-preview.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            background-color: #fff;
        }
        .card-header {
            background-color: #007bff;
            color: white;
            border-radius: 12px 12px 0 0;
            padding: 1rem 1.5rem;
            font-size: 1.25rem;
            font-weight: 600;
        }
        .card-body {
            padding: 1.5rem;
        }
        h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 1.5rem;
        }
        .btn-primary {
            background-color: #007bff;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            transition: background-color 0.2s;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .btn-secondary {
            background-color: #6c757d;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
        }
        .btn-outline-primary {
            border-radius: 8px;
        }
        .badge {
            background-color: #e9ecef;
            color: #333;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            margin-right: 0.5rem;
        }
        .document-container {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            height: 600px;
            overflow-y: auto;
            background-color: #fff;
        }
        .rating-stars .star {
            font-size: 1.5rem;
            cursor: pointer;
            color: #ddd;
            transition: color 0.2s;
        }
        .rating-stars .star.filled, .rating-stars .star:hover {
            color: #ffc107;
        }
        .comment-item, .reply-item {
            padding: 1rem;
            border-radius: 8px;
            background-color: #f8f9fa;
            margin-bottom: 1rem;
        }
        .replies {
            margin-left: 2rem;
        }
        .reply-form {
            margin-top: 1rem;
        }
        .form-control {
            border-radius: 8px;
            border: 1px solid #ced4da;
        }
        .form-select {
            border-radius: 8px;
        }
        @media (max-width: 768px) {
            h1 {
                font-size: 1.5rem;
            }
            .card-header {
                font-size: 1rem;
            }
            .btn {
                padding: 0.5rem 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($document['title']); ?></h1>

        <!-- Document Information -->
        <div class="card">
            <div class="card-header">Thông tin tài liệu</div>
            <div class="card-body">
                <p><strong>Mô tả:</strong> <?php echo htmlspecialchars($document['description'] ?? 'Không có mô tả'); ?></p>
                <p><strong>Danh mục:</strong> <?php echo htmlspecialchars($category['category_name'] ?? 'Không có'); ?></p>
                <?php if (!empty($document['course_name'])): ?>
                    <p><strong>Khóa học:</strong> <a class="text-decoration-none text-primary" href="/study_sharing/course/detail/<?php echo $document['course_id']; ?>"><?php echo htmlspecialchars($document['course_name']); ?></a></p>
                <?php endif; ?>
                <p><strong>Người tải lên:</strong> <?php echo htmlspecialchars($uploader['full_name'] ?? 'Ẩn danh'); ?></p>
                <p><strong>Ngày tải:</strong> <?php echo date('d/m/Y H:i', strtotime($document['upload_date'])); ?></p>
                <p><strong>Thẻ:</strong>
                    <?php foreach ($document['tags'] as $tag): ?>
                        <span class="badge"><?php echo htmlspecialchars($tag); ?></span>
                    <?php endforeach; ?>
                </p>
                <div class="d-flex gap-2">
                    <a href="#" id="backButton" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Trở về</a>
                    <a href="#" id="downloadLink" class="btn btn-primary" onclick="recordDownload(<?php echo $document['document_id']; ?>, event)"><i class="fas fa-download me-2"></i>Tải xuống</a>
                </div>
            </div>
        </div>

        <!-- Document Content -->
        <div class="card">
            <div class="card-header">Nội dung tài liệu</div>
            <div class="card-body">
                <?php
                $file_ext = strtolower(pathinfo($document['file_path'], PATHINFO_EXTENSION));
                $valid_file_types = ['pdf', 'docx', 'pptx'];
                ?>
                <?php if (in_array($file_ext, $valid_file_types)): ?>
                    <div class="mb-3">
                        <label for="versionSelect" class="form-label fw-bold">Chọn version:</label>
                        <select id="versionSelect" class="form-select" onchange="loadVersion(this.value, '<?php echo $file_ext; ?>')">
                            <option value="/study_sharing/uploads/<?php echo htmlspecialchars($document['file_path']); ?>" <?php echo !isset($_GET['version']) ? 'selected' : ''; ?>>Version hiện tại</option>
                            <?php foreach ($versions as $version): ?>
                                <option value="/study_sharing/uploads/<?php echo htmlspecialchars($version['file_path']); ?>" <?php echo isset($_GET['version']) && $_GET['version'] == $version['version_number'] ? 'selected' : ''; ?>>
                                    Version <?php echo htmlspecialchars($version['version_number']); ?> (<?php echo htmlspecialchars($version['change_note']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="document-container" class="document-container"></div>
                <?php else: ?>
                    <p class="text-muted">Định dạng file không được hỗ trợ. Vui lòng tải xuống để xem nội dung.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Document Rating -->
        <div class="card">
            <div class="card-header">Đánh giá tài liệu</div>
            <div class="card-body">
                <?php
                $ratingStmt = $this->db->prepare("SELECT AVG(rating_value) as avg_rating FROM ratings WHERE document_id = :document_id");
                $ratingStmt->bindValue(':document_id', $document['document_id'], PDO::PARAM_INT);
                $ratingStmt->execute();
                $rating = $ratingStmt->fetch(PDO::FETCH_ASSOC);
                $avg_rating = $rating['avg_rating'] ? round($rating['avg_rating'], 1) : 0;

                $userRating = 0;
                if (isset($_SESSION['account_id'])) {
                    $userRatingStmt = $this->db->prepare("SELECT rating_value FROM ratings WHERE document_id = :document_id AND account_id = :account_id");
                    $userRatingStmt->bindValue(':document_id', $document['document_id'], PDO::PARAM_INT);
                    $userRatingStmt->bindValue(':account_id', $_SESSION['account_id'], PDO::PARAM_INT);
                    $userRatingStmt->execute();
                    $userRatingResult = $userRatingStmt->fetch(PDO::FETCH_ASSOC);
                    $userRating = $userRatingResult ? $userRatingResult['rating_value'] : 0;
                }
                ?>
                <p class="fw-bold">Đánh giá trung bình: <?php echo $avg_rating ? $avg_rating . '/5' : 'Chưa có đánh giá'; ?></p>
                <div id="rating-stars" class="rating-stars" data-document-id="<?php echo $document['document_id']; ?>" data-user-rating="<?php echo $userRating; ?>">
                    <span class="star" data-value="1">★</span>
                    <span class="star" data-value="2">★</span>
                    <span class="star" data-value="3">★</span>
                    <span class="star" data-value="4">★</span>
                    <span class="star" data-value="5">★</span>
                </div>
                <?php if (!isset($_SESSION['account_id'])): ?>
                    <p class="text-muted mt-2"><a href="#" class="show-login-modal text-decoration-none text-primary" data-bs-toggle="modal" data-bs-target="#loginModal">Đăng nhập</a> để đánh giá tài liệu.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Comments Section -->
        <div class="card">
            <div class="card-header">Bình luận</div>
            <div class="card-body">
                <?php if (isset($_SESSION['account_id'])): ?>
                    <form id="commentForm" class="mt-4" novalidate>
                        <input type="hidden" name="document_id" value="<?php echo $document['document_id']; ?>">
                        <div class="mb-3">
                            <textarea class="form-control" name="comment_text" rows="4" required placeholder="Viết bình luận của bạn..."></textarea>
                            <div class="invalid-feedback">Vui lòng nhập nội dung bình luận.</div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            Gửi bình luận
                        </button>
                    </form>
                <?php else: ?>
                    <p class="text-muted mt-4">Vui lòng <a href="#" class="show-login-modal text-decoration-none text-primary" data-bs-toggle="modal" data-bs-target="#loginModal">đăng nhập</a> để bình luận.</p>
                <?php endif; ?>
                <div id="comments-container" class="mt-5" data-is-logged-in="<?php echo isset($_SESSION['account_id']) ? 'true' : 'false'; ?>" data-current-user-id="<?php echo isset($_SESSION['account_id']) ? $_SESSION['account_id'] : 0; ?>">
                    <h4 class="fw-bold mb-4">Bình luận</h4>
                    <?php if (empty($comments)): ?>
                        <p class="text-muted">Chưa có bình luận nào.</p>
                    <?php else: ?>
                        <?php
                        function renderReplies($replies, $document, $level = 1) {
                            if (empty($replies)) return '';
                            $html = '<div class="replies mt-3 ms-' . ($level * 3) . '">';
                            foreach ($replies as $reply) {
                                $replyTime = strtotime($reply['comment_date']);
                                $currentTime = time();
                                $replyWithinOneHour = ($currentTime - $replyTime) <= 3600;
                                $replyUser = isset($reply['user']) && is_array($reply['user']) ? $reply['user'] : ['avatar' => null, 'full_name' => 'Ẩn danh'];

                                $html .= '
                                <div class="reply-item reply-level-' . $level . '" data-comment-id="' . $reply['comment_id'] . '">
                                    <div class="d-flex align-items-center mb-2 position-relative">
                                        <img src="/study_sharing/assets/images/' . ($replyUser['avatar'] ?: 'profile.png') . '" alt="Avatar" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                        <div>
                                            <strong>' . htmlspecialchars($replyUser['full_name'] ?: 'Ẩn danh') . '</strong>
                                            <small class="text-muted ms-2">' . date('d/m/Y H:i', strtotime($reply['comment_date'])) . '</small>
                                        </div>';
                                if (isset($_SESSION['account_id'])) {
                                    $html .= '
                                    <div class="dropdown ms-auto">
                                        <button class="btn btn-link text-muted p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-h"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item reply-comment" href="#" data-comment-id="' . $reply['comment_id'] . '" data-user-id="' . $reply['account_id'] . '" data-user-name="' . htmlspecialchars($replyUser['full_name'] ?: 'Ẩn danh') . '">Trả lời</a></li>';
                                    if ($reply['account_id'] == $_SESSION['account_id'] && $replyWithinOneHour) {
                                        $html .= '<li><a class="dropdown-item delete-comment" href="#" data-comment-id="' . $reply['comment_id'] . '">Xóa</a></li>';
                                    }
                                    $html .= '</ul></div>';
                                }
                                $html .= '
                                    </div>
                                    <p class="mb-0">' . htmlspecialchars($reply['comment_text']) . '</p>';
                                $html .= '
                                    <form class="reply-form mt-3 d-none" data-parent-comment-id="' . $reply['comment_id'] . '">
                                        <input type="hidden" name="document_id" value="' . $document['document_id'] . '">
                                        <input type="hidden" name="parent_comment_id" value="' . $reply['comment_id'] . '">
                                        <input type="hidden" name="tagged_user_id" value="' . $reply['account_id'] . '">
                                        <div class="mb-3">
                                            <textarea class="form-control" name="comment_text" rows="3" required placeholder="Trả lời ' . htmlspecialchars($replyUser['full_name'] ?: 'Ẩn danh') . '..."></textarea>
                                            <div class="invalid-feedback">Vui lòng nhập nội dung trả lời.</div>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                            Gửi trả lời
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm cancel-reply">Hủy</button>
                                    </form>';
                                if (!empty($reply['replies'])) {
                                    $html .= renderReplies($reply['replies'], $document, $level + 1);
                                }
                                $html .= '</div>';
                            }
                            $html .= '</div>';
                            return $html;
                        }
                        ?>
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment-item" data-comment-id="<?php echo $comment['comment_id']; ?>">
                                <?php $commentUser = isset($comment['user']) && is_array($comment['user']) ? $comment['user'] : ['avatar' => null, 'full_name' => 'Ẩn danh']; ?>
                                <div class="d-flex align-items-center mb-2 position-relative">
                                    <img src="/study_sharing/assets/images/<?php echo $commentUser['avatar'] ?: 'profile.png'; ?>" alt="Avatar" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                    <div>
                                        <strong><?php echo htmlspecialchars($commentUser['full_name'] ?: 'Ẩn danh'); ?></strong>
                                        <small class="text-muted ms-2"><?php echo date('d/m/Y H:i', strtotime($comment['comment_date'])); ?></small>
                                    </div>
                                    <?php if (isset($_SESSION['account_id'])): ?>
                                        <?php
                                        $commentTime = strtotime($comment['comment_date']);
                                        $currentTime = time();
                                        $withinOneHour = ($currentTime - $commentTime) <= 3600;
                                        ?>
                                        <div class="dropdown ms-auto">
                                            <button class="btn btn-link text-muted p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-h"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item reply-comment" href="#" data-comment-id="<?php echo $comment['comment_id']; ?>" data-user-id="<?php echo $comment['account_id']; ?>" data-user-name="<?php echo htmlspecialchars($commentUser['full_name'] ?: 'Ẩn danh'); ?>">Trả lời</a></li>
                                                <?php if ($comment['account_id'] == $_SESSION['account_id'] && $withinOneHour): ?>
                                                    <li><a class="dropdown-item delete-comment" href="#" data-comment-id="<?php echo $comment['comment_id']; ?>">Xóa</a></li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <p class="mb-0"><?php echo htmlspecialchars($comment['comment_text']); ?></p>
                                <form class="reply-form mt-3 d-none" data-parent-comment-id="<?php echo $comment['comment_id']; ?>">
                                    <input type="hidden" name="document_id" value="<?php echo $document['document_id']; ?>">
                                    <input type="hidden" name="parent_comment_id" value="<?php echo $comment['comment_id']; ?>">
                                    <input type="hidden" name="tagged_user_id" value="<?php echo $comment['account_id']; ?>">
                                    <div class="mb-3">
                                        <textarea class="form-control" name="comment_text" rows="3" required placeholder="Trả lời <?php echo htmlspecialchars($commentUser['full_name'] ?: 'Ẩn danh'); ?>..."></textarea>
                                        <div class="invalid-feedback">Vui lòng nhập nội dung trả lời.</div>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                        Gửi trả lời
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-sm cancel-reply">Hủy</button>
                                </form>
                                <?php if (!empty($comment['replies'])): ?>
                                    <?php echo renderReplies($comment['replies'], $document); ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($totalComments > 5): ?>
                            <button id="loadMoreComments" class="btn btn-outline-primary" data-document-id="<?php echo $document['document_id']; ?>" data-offset="5">Tải thêm bình luận</button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/study_sharing/assets/js/document.js"></script>
    <script>
        document.getElementById('backButton').addEventListener('click', function(e) {
            e.preventDefault();
            if (document.referrer && document.referrer.includes('/study_sharing/')) {
                window.history.back();
            } else {
                window.location.href = '/study_sharing';
            }
        });

        // Rating stars interactivity
        document.querySelectorAll('.rating-stars .star').forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.dataset.value;
                const documentId = this.parentElement.dataset.documentId;
                // Add logic to submit rating via AJAX
            });
            star.addEventListener('mouseover', function() {
                const value = this.dataset.value;
                const stars = this.parentElement.querySelectorAll('.star');
                stars.forEach(s => {
                    if (s.dataset.value <= value) {
                        s.classList.add('filled');
                    } else {
                        s.classList.remove('filled');
                    }
                });
            });
            star.addEventListener('mouseout', function() {
                const userRating = this.parentElement.dataset.userRating;
                const stars = this.parentElement.querySelectorAll('.star');
                stars.forEach(s => {
                    if (s.dataset.value <= userRating) {
                        s.classList.add('filled');
                    } else {
                        s.classList.remove('filled');
                    }
                });
            });
        });
    </script>
</body>
</html>