<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['account_id']) || !in_array($_SESSION['role'], ['teacher', 'student'])) {
    header('Location: /study_sharing/');
    exit;
}
$keyword = isset($keyword) ? htmlspecialchars($keyword, ENT_QUOTES | ENT_SUBSTITUTE) : '';
$category_id = isset($category_id) ? (int)$category_id : 0;
$file_type = isset($file_type) ? htmlspecialchars($file_type, ENT_QUOTES | ENT_SUBSTITUTE) : '';
$title = "Quản lý tài liệu của tôi";
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script src="https://unpkg.com/jszip@3.10.1/dist/jszip.min.js"></script>
<script src="https://unpkg.com/docx-preview@0.0.3/dist/docx-preview.js"></script>
<link href="/study_sharing/assets/css/manage_document_user.css" rel="stylesheet">

<div class="content px-3">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-primary fs-3 fw-bold"><i class="bi bi-file-earmark-text me-2"></i> Quản lý tài liệu</h1>
            <a href="/study_sharing/document/create" class="btn btn-success btn-sm"><i class="bi bi-plus-circle me-1"></i> Thêm tài liệu</a>
        </div>

        <!-- Notification -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo htmlspecialchars($_SESSION['message_type'], ENT_QUOTES | ENT_SUBSTITUTE); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['message'], ENT_QUOTES | ENT_SUBSTITUTE); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        <?php endif; ?>

        <!-- Search and Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form class="row g-3" method="GET" action="/study_sharing/document/manage">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" name="keyword" placeholder="Tìm kiếm tiêu đề hoặc mô tả..." value="<?php echo $keyword; ?>" aria-label="Tìm kiếm tài liệu">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="category_id">
                            <option value="0">Tất cả danh mục</option>
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>" <?php echo $category_id == $category['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['category_name'], ENT_QUOTES | ENT_SUBSTITUTE); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="0">Không có danh mục</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="file_type">
                            <option value="">Tất cả định dạng</option>
                            <option value="pdf" <?php echo $file_type == 'pdf' ? 'selected' : ''; ?>>PDF</option>
                            <option value="docx" <?php echo $file_type == 'docx' ? 'selected' : ''; ?>>DOCX</option>
                            <option value="pptx" <?php echo $file_type == 'pptx' ? 'selected' : ''; ?>>PPTX</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-primary w-100" type="submit">Tìm</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Documents List -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php if (empty($documents)): ?>
                <div class="col">
                    <div class="card shadow-sm text-center p-5">
                        <i class="bi bi-file-earmark-text display-4 text-muted mb-3"></i>
                        <p class="text-muted">Không tìm thấy tài liệu nào!</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($documents as $index => $document): ?>
                    <div class="col">
                        <div class="card document-card h-100 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title text-primary mb-0 text-truncate" title="<?php echo htmlspecialchars($document['title'], ENT_QUOTES | ENT_SUBSTITUTE); ?>">
                                        <?php echo htmlspecialchars($document['title'], ENT_QUOTES | ENT_SUBSTITUTE); ?>
                                    </h5>
                                    <span class="badge bg-light text-dark"><?php echo strtoupper(pathinfo($document['file_path'], PATHINFO_EXTENSION)); ?></span>
                                </div>
                                <p class="card-text text-muted small mb-2"><?php echo htmlspecialchars($document['category_name'] ?? 'Không có', ENT_QUOTES | ENT_SUBSTITUTE); ?> | <?php echo htmlspecialchars($document['course_name'] ?? 'Không có', ENT_QUOTES | ENT_SUBSTITUTE); ?></p>
                                <p class="card-text text-muted small mb-2"><i class="bi bi-calendar me-1"></i><?php echo date('d/m/Y', strtotime($document['upload_date'])); ?></p>
                                <div class="mb-2">
                                    <?php if (!empty($document['tags'])): ?>
                                        <?php foreach ($document['tags'] as $tag): ?>
                                            <span class="badge bg-secondary me-1"><?php echo htmlspecialchars($tag, ENT_QUOTES | ENT_SUBSTITUTE); ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">Không có thẻ</span>
                                    <?php endif; ?>
                                </div>
                                <p class="card-text small"><?php echo $document['visibility'] === 'public' ? '<span class="text-success"><i class="bi bi-globe me-1"></i>Công khai</span>' : '<span class="text-warning"><i class="bi bi-lock me-1"></i>Riêng tư</span>'; ?></p>
                            </div>
                            <div class="card-footer bg-white border-top-0">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <button type="button" class="btn btn-outline-info btn-sm view-btn" title="Xem tài liệu"
                                            data-id="<?php echo $document['document_id']; ?>"
                                            data-title="<?php echo htmlspecialchars($document['title'], ENT_QUOTES | ENT_SUBSTITUTE); ?>"
                                            data-description="<?php echo htmlspecialchars($document['description'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE); ?>"
                                            data-category-name="<?php echo htmlspecialchars($document['category_name'] ?? 'Không có', ENT_QUOTES | ENT_SUBSTITUTE); ?>"
                                            data-course-name="<?php echo htmlspecialchars($document['course_name'] ?? 'Không có', ENT_QUOTES | ENT_SUBSTITUTE); ?>"
                                            data-upload-date="<?php echo date('d/m/Y', strtotime($document['upload_date'])); ?>"
                                            data-visibility="<?php echo $document['visibility']; ?>"
                                            data-tags="<?php echo htmlspecialchars(implode(',', $document['tags'] ?? []), ENT_QUOTES | ENT_SUBSTITUTE); ?>"
                                            data-file-name="<?php echo htmlspecialchars(basename($document['file_path']), ENT_QUOTES | ENT_SUBSTITUTE); ?>"
                                            data-file-path="/study_sharing/Uploads/<?php echo htmlspecialchars($document['file_path'], ENT_QUOTES | ENT_SUBSTITUTE); ?>"
                                            data-file-ext="<?php echo htmlspecialchars(strtolower(pathinfo($document['file_path'], PATHINFO_EXTENSION)), ENT_QUOTES | ENT_SUBSTITUTE); ?>">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-warning btn-sm edit-btn" data-bs-toggle="modal" data-bs-target="#editDocumentModal" title="Chỉnh sửa"
                                            data-id="<?php echo $document['document_id']; ?>"
                                            data-title="<?php echo htmlspecialchars($document['title'], ENT_QUOTES | ENT_SUBSTITUTE); ?>"
                                            data-description="<?php echo htmlspecialchars($document['description'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE); ?>"
                                            data-category-id="<?php echo $document['category_id'] ?? ''; ?>"
                                            data-course-id="<?php echo $document['course_id'] ?? ''; ?>"
                                            data-visibility="<?php echo $document['visibility']; ?>"
                                            data-tags="<?php echo htmlspecialchars(implode(',', $document['tags'] ?? []), ENT_QUOTES | ENT_SUBSTITUTE); ?>"
                                            data-file-name="<?php echo htmlspecialchars(basename($document['file_path']), ENT_QUOTES | ENT_SUBSTITUTE); ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-danger btn-sm delete-btn" data-id="<?php echo $document['document_id']; ?>" title="Xóa">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                    <div>
                                        <button class="btn btn-outline-info btn-sm version-btn" data-bs-toggle="modal" data-bs-target="#versionModal" title="Lịch sử phiên bản"
                                            data-versions="<?php echo htmlspecialchars(json_encode($document['versions'], JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_APOS), ENT_QUOTES | ENT_SUBSTITUTE); ?>">
                                            <i class="bi bi-clock-history"></i>
                                        </button>
                                        <button class="btn btn-outline-primary btn-sm update-version-btn" data-bs-toggle="modal" data-bs-target="#updateVersionModal" title="Cập nhật phiên bản"
                                            data-id="<?php echo $document['document_id']; ?>">
                                            <i class="bi bi-arrow-up-circle"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if (!empty($documents)): ?>
            <nav aria-label="Document pagination" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo $page > 1 ? '/study_sharing/document/manage?page=' . ($page - 1) . '&keyword=' . urlencode($keyword) . '&category_id=' . $category_id . '&file_type=' . urlencode($file_type) : '#'; ?>">Trước</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="/study_sharing/document/manage?page=<?php echo $i; ?>&keyword=<?php echo urlencode($keyword); ?>&category_id=<?php echo $category_id; ?>&file_type=<?php echo urlencode($file_type); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo $page < $totalPages ? '/study_sharing/document/manage?page=' . ($page + 1) . '&keyword=' . urlencode($keyword) . '&category_id=' . $category_id . '&file_type=' . urlencode($file_type) : '#'; ?>">Sau</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Document Modal -->
<div class="modal fade" id="editDocumentModal" tabindex="-1" aria-labelledby="editDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editDocumentModalLabel"><i class="bi bi-pencil-square me-2"></i>Chỉnh sửa tài liệu</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editDocumentForm" method="POST" action="/study_sharing/document/edit" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <input type="hidden" id="editDocumentId" name="document_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="editDocumentTitle" class="form-label">Tiêu đề <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editDocumentTitle" name="title" required maxlength="255" placeholder="Nhập tiêu đề tài liệu">
                            <div class="invalid-feedback">Vui lòng nhập tiêu đề (tối đa 255 ký tự).</div>
                        </div>
                        <div class="col-md-6">
                            <label for="editDocumentVisibility" class="form-label">Chế độ hiển thị <span class="text-danger">*</span></label>
                            <select class="form-select" id="editDocumentVisibility" name="visibility" required>
                                <option value="public">Công khai</option>
                                <option value="private">Riêng tư</option>
                            </select>
                            <div class="invalid-feedback">Vui lòng chọn chế độ hiển thị.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="editDocumentCategory" class="form-label">Danh mục</label>
                            <select class="form-select" id="editDocumentCategory" name="category_id">
                                <option value="0">Không chọn</option>
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['category_name'], ENT_QUOTES | ENT_SUBSTITUTE); ?></option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="0">Không có danh mục</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="editDocumentCourse" class="form-label">Khóa học</label>
                            <select class="form-select" id="editDocumentCourse" name="course_id">
                                <option value="">Không chọn</option>
                                <?php if (!empty($courses)): ?>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['course_id']; ?>"><?php echo htmlspecialchars($course['course_name'], ENT_QUOTES | ENT_SUBSTITUTE); ?></option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">Không có khóa học</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="editDocumentDescription" class="form-label">Mô tả</label>
                            <textarea class="form-control" id="editDocumentDescription" name="description" rows="4" placeholder="Mô tả nội dung tài liệu..."></textarea>
                        </div>
                        <div class="col-12">
                            <label for="editDocumentTags" class="form-label">Thẻ</label>
                            <input type="text" class="form-control" id="editDocumentTags" name="tags" placeholder="Chọn thẻ..." readonly>
                            <ul class="autocomplete-dropdown list-unstyled m-0 shadow-sm">
                                <?php if (!empty($tags)): ?>
                                    <?php foreach ($tags as $tag): ?>
                                        <li class="autocomplete-item" data-value="<?php echo htmlspecialchars($tag['tag_name'], ENT_QUOTES | ENT_SUBSTITUTE); ?>">
                                            <?php echo htmlspecialchars($tag['tag_name'], ENT_QUOTES | ENT_SUBSTITUTE); ?>
                                            <span class="tick d-none"><i class="bi bi-check"></i></span>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="autocomplete-item text-muted">Không có thẻ</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <div class="col-12">
                            <label for="editDocumentFile" class="form-label">Tệp tài liệu</label>
                            <div class="file-upload-area border rounded p-3 text-center bg-light">
                                <label for="editDocumentFile" class="file-upload-label d-block cursor-pointer">
                                    <i class="bi bi-cloud-arrow-up fs-3 text-primary"></i>
                                    <div class="file-upload-text mt-2">Kéo thả hoặc nhấn để tải lên (PDF, DOCX, PPTX)</div>
                                    <div id="currentFileName" class="text-primary mt-2 fw-medium"></div>
                                </label>
                                <input type="file" class="form-control d-none" id="editDocumentFile" name="file" accept=".pdf,.docx,.pptx">
                                <small class="text-muted d-block mt-1">Tối đa 10MB. Để trống nếu không thay đổi.</small>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-3">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Cập nhật tài liệu
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- View Document Modal -->
<div class="modal fade" id="viewDocumentModal" tabindex="-1" aria-labelledby="viewDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="viewDocumentModalLabel"><i class="bi bi-file-earmark-text me-2"></i>Chi tiết tài liệu</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">
                    <!-- Metadata -->
                    <div class="col-lg-4 p-4 bg-light border-end">
                        <h4 id="viewDocumentTitle" class="text-primary mb-4 fs-5"></h4>
                        <div class="mb-4">
                            <small class="text-muted d-block">Danh mục</small>
                            <span id="viewDocumentCategory" class="fw-medium"></span>
                        </div>
                        <div class="mb-4">
                            <small class="text-muted d-block">Khóa học</small>
                            <span id="viewDocumentCourse" class="fw-medium"></span>
                        </div>
                        <div class="mb-4">
                            <small class="text-muted d-block">Ngày tải lên</small>
                            <span id="viewDocumentUploadDate" class="fw-medium"></span>
                        </div>
                        <div class="mb-4">
                            <small class="text-muted d-block">Chế độ hiển thị</small>
                            <span id="viewDocumentVisibility" class="fw-medium"></span>
                        </div>
                        <div class="mb-4">
                            <small class="text-muted d-block">Định dạng</small>
                            <span id="viewDocumentFileType" class="fw-medium text-uppercase"></span>
                        </div>
                        <div class="mb-4">
                            <small class="text-muted d-block">Mô tả</small>
                            <p id="viewDocumentDescription" class="text-dark" style="white-space: pre-line;"></p>
                        </div>
                        <div class="mb-4">
                            <small class="text-muted d-block">Thẻ</small>
                            <div id="viewDocumentTags" class="d-flex flex-wrap gap-2"></div>
                        </div>
                        <a id="viewDocumentDownloadLink" class="btn btn-primary w-100" href="#" download>
                            <i class="bi bi-download me-2"></i>Tải xuống
                        </a>
                    </div>
                    <!-- Preview -->
                    <div class="col-lg-8 p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0"><i class="bi bi-file-earmark me-2"></i>Xem trước</h5>
                            <span class="badge bg-light text-dark"><span id="viewDocumentFileName"></span></span>
                        </div>
                        <div id="viewDocumentContainer" class="document-container shadow-sm rounded" style="height: calc(100vh - 200px); overflow-y: auto; background-color: #fff;">
                            <div class="d-flex justify-content-center align-items-center h-100">
                                <div class="text-center text-muted">
                                    <i class="bi bi-file-earmark-text display-4 mb-3"></i>
                                    <p>Đang tải xem trước...</p>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <button id="prevPage" class="btn btn-outline-primary btn-sm"><i class="bi bi-chevron-left"></i> Trang trước</button>
                            <small class="text-muted" id="pageInfo"></small>
                            <button id="nextPage" class="btn btn-outline-primary btn-sm">Trang sau <i class="bi bi-chevron-right"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Version History Modal -->
<div class="modal fade" id="versionModal" tabindex="-1" aria-labelledby="versionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="versionModalLabel"><i class="bi bi-clock-history me-2"></i>Lịch sử phiên bản</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Phiên bản</th>
                                <th>Tệp</th>
                                <th>Ghi chú thay đổi</th>
                                <th>Ngày tạo</th>
                            </tr>
                        </thead>
                        <tbody id="versionTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Version Modal -->
<div class="modal fade" id="updateVersionModal" tabindex="-1" aria-labelledby="updateVersionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="updateVersionModalLabel"><i class="bi bi-arrow-up-circle me-2"></i>Cập nhật phiên bản</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="updateVersionForm" method="POST" action="/study_sharing/document/updateversion" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <input type="hidden" id="updateVersionDocumentId" name="document_id">
                    <div class="mb-3">
                        <label for="updateVersionFile" class="form-label">Tệp mới <span class="text-danger">*</span></label>
                        <div class="file-upload-area border rounded p-3 text-center bg-light">
                            <label for="updateVersionFile" class="file-upload-label d-block cursor-pointer">
                                <i class="bi bi-cloud-arrow-up fs-3 text-primary"></i>
                                <div class="file-upload-text mt-2">Kéo thả hoặc nhấn để tải lên (PDF, DOCX, PPTX)</div>
                                <div id="updateVersionFileName" class="text-primary mt-2 fw-medium"></div>
                            </label>
                            <input type="file" class="form-control d-none" id="updateVersionFile" name="file" accept=".pdf,.docx,.pptx" required>
                            <small class="text-muted d-block mt-1">Tối đa 10MB</small>
                            <div class="invalid-feedback">Vui lòng chọn tệp (PDF, DOCX, PPTX).</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="updateVersionChangeNote" class="form-label">Ghi chú thay đổi</label>
                        <textarea class="form-control" id="updateVersionChangeNote" name="change_note" rows="3" placeholder="Mô tả thay đổi..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Cập nhật
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="successModalLabel"><i class="bi bi-check-circle me-2"></i>Thành công</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="successModalMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script src="/study_sharing/assets/js/user_document.js"></script>