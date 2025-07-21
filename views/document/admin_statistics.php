<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<link href="/study_sharing/assets/css/admin_statistics.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script src="https://unpkg.com/jszip@3.10.1/dist/jszip.min.js"></script>
<script src="https://unpkg.com/docx-preview@latest/dist/docx-preview.js"></script>
<script src="/study_sharing/assets/js/admin_document.js"></script>
<script>
    // Truyền dữ liệu từ PHP sang JavaScript
    window.stats = <?php echo json_encode($stats); ?>;
</script>
<script src="/study_sharing/assets/js/admin_statistics.js"></script>

<div class="container-fluid">
    <h2 class="mb-4"><i class="bi bi-bar-chart"></i> Thống kê hệ thống</h2>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo htmlspecialchars($_SESSION['message_type']); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <!-- Tổng quan hệ thống -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stat-card bg-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-1"><i class="fas fa-file-alt"></i> Tài liệu</h5>
                            <p class="card-text display-5 mb-0"><?php echo $stats['total_documents']; ?></p>
                        </div>
                        <i class="fas fa-file-alt fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stat-card bg-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-1"><i class="fas fa-download"></i> Lượt tải</h5>
                            <p class="card-text display-5 mb-0"><?php echo $stats['total_downloads']; ?></p>
                        </div>
                        <i class="fas fa-download fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stat-card bg-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-1"><i class="fas fa-star"></i> Đánh giá TB</h5>
                            <p class="card-text display-5 mb-0"><?php echo $stats['avg_rating']; ?>/5</p>
                        </div>
                        <i class="fas fa-star fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stat-card bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-1"><i class="fas fa-file"></i> Định dạng file</h5>
                            <p class="card-text display-5 mb-0"><?php echo count($stats['file_type_counts']); ?></p>
                        </div>
                        <i class="fas fa-file fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Biểu đồ -->
    <div class="row mb-4">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Tài liệu theo danh mục</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Tài liệu theo định dạng</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="fileTypeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Biểu đồ hoạt động theo tháng -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Hoạt động hệ thống trong năm <?php echo date('Y'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="monthlyActivityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Thống kê theo danh mục -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Thống kê theo danh mục</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-primary">
                                <tr>
                                    <th>Danh mục</th>
                                    <th class="text-center">Tài liệu</th>
                                    <th class="text-center">Lượt tải</th>
                                    <th class="text-center">Đánh giá TB</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['categories'] as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                        <td class="text-center"><?php echo $category['document_count']; ?></td>
                                        <td class="text-center"><?php echo $category['download_count']; ?></td>
                                        <td class="text-center">
                                            <?php if ($category['avg_rating']): ?>
                                                <span class="badge bg-warning text-dark"><?php echo round($category['avg_rating'], 1); ?>/5</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Chưa có</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tài liệu phổ biến -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-trophy"></i> Tài liệu phổ biến</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-success">
                                <tr>
                                    <th>Tên tài liệu</th>
                                    <th class="text-center">Lượt tải</th>
                                    <th class="text-center">Bình luận</th>
                                    <th class="text-center">Đánh giá</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['top_downloads'] as $doc): ?>
                                    <?php
                                    // Lấy thông tin bổ sung từ documents
                                    $docStmt = $pdo->prepare("
                                        SELECT d.*, c.category_name, u.full_name, co.course_name,
                                               GROUP_CONCAT(t.tag_name) as tags
                                        FROM documents d
                                        LEFT JOIN categories c ON d.category_id = c.category_id
                                        LEFT JOIN users u ON d.account_id = u.account_id
                                        LEFT JOIN courses co ON d.course_id = co.course_id
                                        LEFT JOIN document_tags dt ON d.document_id = dt.document_id
                                        LEFT JOIN tags t ON dt.tag_id = t.tag_id
                                        WHERE d.document_id = :document_id
                                        GROUP BY d.document_id
                                    ");
                                    $docStmt->bindValue(':document_id', $doc['document_id'], PDO::PARAM_INT);
                                    $docStmt->execute();
                                    $docDetails = $docStmt->fetch(PDO::FETCH_ASSOC);
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="document-title view-btn"
                                                data-id="<?php echo $doc['document_id']; ?>"
                                                data-title="<?php echo htmlspecialchars($docDetails['title']); ?>"
                                                data-description="<?php echo htmlspecialchars($docDetails['description'] ?? ''); ?>"
                                                data-category-name="<?php echo htmlspecialchars($docDetails['category_name'] ?? 'Không có'); ?>"
                                                data-course-name="<?php echo htmlspecialchars($docDetails['course_name'] ?? 'Không có'); ?>"
                                                data-uploader-name="<?php echo htmlspecialchars($docDetails['full_name'] ?? 'Ẩn danh'); ?>"
                                                data-upload-date="<?php echo date('d/m/Y', strtotime($docDetails['upload_date'])); ?>"
                                                data-visibility="<?php echo $docDetails['visibility']; ?>"
                                                data-tags="<?php echo htmlspecialchars($docDetails['tags'] ?? ''); ?>"
                                                data-file-name="<?php echo htmlspecialchars(basename($docDetails['file_path'])); ?>"
                                                data-file-path="/study_sharing/uploads/<?php echo htmlspecialchars($docDetails['file_path']); ?>"
                                                data-file-ext="<?php echo htmlspecialchars(strtolower(pathinfo($docDetails['file_path'], PATHINFO_EXTENSION))); ?>">
                                                <?php echo htmlspecialchars($doc['title']); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary badge-stat"><?php echo $doc['download_count']; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success badge-stat">
                                                <?php
                                                $comment_count = 0;
                                                foreach ($stats['top_comments'] as $c) {
                                                    if ($c['document_id'] == $doc['document_id']) {
                                                        $comment_count = $c['comment_count'];
                                                        break;
                                                    }
                                                }
                                                echo $comment_count;
                                                ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-warning text-dark badge-stat">
                                                <?php
                                                $rating = 0;
                                                foreach ($stats['top_ratings'] as $r) {
                                                    if ($r['document_id'] == $doc['document_id']) {
                                                        $rating = round($r['avg_rating'], 1);
                                                        break;
                                                    }
                                                }
                                                echo $rating ? $rating . '/5' : 'Chưa có';
                                                ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Document Modal -->
    <div class="modal fade" id="viewDocumentModal" tabindex="-1" aria-labelledby="viewDocumentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="viewDocumentModalLabel"><i class="bi bi-file-earmark-text me-2"></i> Chi tiết tài liệu</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="container-fluid">
                        <div class="row g-0">
                            <!-- Document Metadata - Left Column -->
                            <div class="col-lg-5 p-4 border-end">
                                <div class="d-flex flex-column h-100">
                                    <h4 id="viewDocumentTitle" class="text-primary mb-3"></h4>

                                    <!-- Document Info Card -->
                                    <div class="card mb-3 shadow-sm">
                                        <div class="card-header bg-light">
                                            <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Thông tin tài liệu</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <div class="mb-2">
                                                        <small class="text-muted d-block">Danh mục</small>
                                                        <span id="viewDocumentCategory" class="fw-medium">Không có</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-2">
                                                        <small class="text-muted d-block">Khóa học</small>
                                                        <span id="viewDocumentCourse" class="fw-medium">Không có</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-2">
                                                        <small class="text-muted d-block">Người tải lên</small>
                                                        <span id="viewDocumentUploader" class="fw-medium">Ẩn danh</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-2">
                                                        <small class="text-muted d-block">Ngày tải lên</small>
                                                        <span id="viewDocumentUploadDate" class="fw-medium"></span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-2">
                                                        <small class="text-muted d-block">Chế độ hiển thị</small>
                                                        <span id="viewDocumentVisibility" class="fw-medium"></span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-2">
                                                        <small class="text-muted d-block">Định dạng</small>
                                                        <span id="viewDocumentFileType" class="fw-medium text-uppercase"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Description Card -->
                                    <div class="card mb-3 shadow-sm flex-grow-1">
                                        <div class="card-header bg-light">
                                            <h5 class="mb-0"><i class="bi bi-card-text me-2"></i>Mô tả</h5>
                                        </div>
                                        <div class="card-body">
                                            <div id="viewDocumentDescription" class="text-dark" style="white-space: pre-line;">Không có mô tả</div>
                                        </div>
                                    </div>

                                    <!-- Tags Card -->
                                    <div class="card shadow-sm">
                                        <div class="card-header bg-light">
                                            <h5 class="mb-0"><i class="bi bi-tags me-2"></i>Thẻ</h5>
                                        </div>
                                        <div class="card-body">
                                            <div id="viewDocumentTags" class="d-flex flex-wrap gap-2">
                                                <span class="text-muted">Không có thẻ</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Download Button -->
                                    <div class="mt-3 d-grid">
                                        <a id="viewDocumentDownloadLink" class="btn btn-primary" href="#" download>
                                            <i class="bi bi-download me-2"></i>Tải xuống tài liệu
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Document Preview - Right Column -->
                            <div class="col-lg-7 p-4">
                                <div class="d-flex flex-column h-100">
                                    <!-- File Info -->
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5><i class="bi bi-file-earmark me-2"></i>Xem trước tài liệu</h5>
                                        <div class="badge bg-light text-dark">
                                            <i class="bi bi-file-earmark-text me-1"></i>
                                            <span id="viewDocumentFileName"></span>
                                        </div>
                                    </div>

                                    <!-- Preview Container -->
                                    <div id="viewDocumentContainer" class="document-container flex-grow-1 shadow-sm"
                                        style="height: calc(100vh - 250px); overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.25rem; background-color: #f8f9fa;">
                                        <div class="d-flex justify-content-center align-items-center h-100">
                                            <div class="text-center text-muted">
                                                <i class="bi bi-file-earmark-text display-4 mb-3"></i>
                                                <p>Đang tải xem trước tài liệu...</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Preview Controls -->
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <div class="btn-group">
                                            <button class="btn btn-outline-secondary btn-sm" id="zoomInBtn">
                                                <i class="bi bi-zoom-in"></i>
                                            </button>
                                            <button class="btn btn-outline-secondary btn-sm" id="zoomOutBtn">
                                                <i class="bi bi-zoom-out"></i>
                                            </button>
                                            <button class="btn btn-outline-secondary btn-sm" id="fitWidthBtn">
                                                <i class="bi bi-arrows-angle-expand"></i> Vừa chiều rộng
                                            </button>
                                        </div>
                                        <small class="text-muted" id="pageInfo"></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
</div>