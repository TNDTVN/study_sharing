<?php
$title = "Quản lý tài liệu";

// Lấy danh sách thẻ từ bảng tags
$tagStmt = $pdo->prepare("SELECT tag_id, tag_name FROM tags ORDER BY tag_name");
$tagStmt->execute();
$tags = $tagStmt->fetchAll(PDO::FETCH_ASSOC);
?>


<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script src="https://unpkg.com/jszip@3.10.1/dist/jszip.min.js"></script>
<script src="https://unpkg.com/docx-preview@latest/dist/docx-preview.js"></script>
<link href="/study_sharing/assets/css/manage_document.css" rel="stylesheet">

<div class="content-1 px-3">
    <h1 class="mb-4 text-primary"><i class="bi bi-file-earmark-text me-2"></i> Quản lý tài liệu</h1>

    <!-- Search and Add New Document -->
    <div class="d-flex justify-content-between mb-4">
        <form class="input-group w-75" method="GET" action="/study_sharing/AdminDocument/admin_manage">
            <input type="text" class="form-control" name="keyword" placeholder="Tìm kiếm theo tiêu đề hoặc mô tả" value="<?php echo htmlspecialchars($keyword ?? ''); ?>" aria-label="Tìm kiếm tài liệu">
            <select class="form-select" name="category_id">
                <option value="0">Tất cả danh mục</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['category_id']; ?>" <?php echo $category_id == $category['category_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['category_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select class="form-select" name="file_type">
                <option value="">Tất cả định dạng</option>
                <option value="pdf" <?php echo $file_type == 'pdf' ? 'selected' : ''; ?>>PDF</option>
                <option value="docx" <?php echo $file_type == 'docx' ? 'selected' : ''; ?>>DOCX</option>
                <option value="pptx" <?php echo $file_type == 'pptx' ? 'selected' : ''; ?>>PPTX</option>
            </select>
            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Tìm</button>
        </form>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addDocumentModal"><i class="bi bi-plus-circle"></i> Thêm tài liệu</button>
    </div>

    <!-- Message Display -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <!-- Documents Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th scope="col">STT</th>
                    <th scope="col">Tiêu đề</th>
                    <th scope="col">Danh mục</th>
                    <th scope="col">Khóa học</th>
                    <th scope="col">Người tải lên</th>
                    <th scope="col">Ngày tải lên</th>
                    <th scope="col">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($documents)): ?>
                    <tr>
                        <td colspan="7" class="text-center">Không tìm thấy tài liệu nào!</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($documents as $index => $document): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($offset + $index + 1); ?></td>
                            <td><?php echo htmlspecialchars($document['title']); ?></td>
                            <td><?php echo htmlspecialchars($document['category_name'] ?? 'Không có'); ?></td>
                            <td><?php echo htmlspecialchars($document['course_name'] ?? 'Không có'); ?></td>
                            <td><?php echo htmlspecialchars($document['full_name'] ?? 'Ẩn danh'); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($document['upload_date'])); ?></td>
                            <td>
                                <button type="button" class="btn btn-outline-info btn-sm view-btn" title="Xem"
                                    data-id="<?php echo $document['document_id']; ?>"
                                    data-title="<?php echo htmlspecialchars($document['title']); ?>"
                                    data-description="<?php echo htmlspecialchars($document['description'] ?? ''); ?>"
                                    data-category-name="<?php echo htmlspecialchars($document['category_name'] ?? 'Không có'); ?>"
                                    data-course-name="<?php echo htmlspecialchars($document['course_name'] ?? 'Không có'); ?>"
                                    data-uploader-name="<?php echo htmlspecialchars($document['full_name'] ?? 'Ẩn danh'); ?>"
                                    data-upload-date="<?php echo date('d/m/Y', strtotime($document['upload_date'])); ?>"
                                    data-visibility="<?php echo $document['visibility']; ?>"
                                    data-tags="<?php echo htmlspecialchars(implode(',', $document['tags'] ?? [])); ?>"
                                    data-file-name="<?php echo htmlspecialchars(basename($document['file_path'])); ?>"
                                    data-file-path="/study_sharing/uploads/<?php echo htmlspecialchars($document['file_path']); ?>"
                                    data-file-ext="<?php echo htmlspecialchars(strtolower(pathinfo($document['file_path'], PATHINFO_EXTENSION))); ?>">
                                    <i class="fa fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-warning btn-sm edit-btn" data-bs-toggle="modal" data-bs-target="#editDocumentModal"
                                    data-id="<?php echo $document['document_id']; ?>"
                                    data-title="<?php echo htmlspecialchars($document['title']); ?>"
                                    data-description="<?php echo htmlspecialchars($document['description'] ?? ''); ?>"
                                    data-category-id="<?php echo $document['category_id'] ?? ''; ?>"
                                    data-course-id="<?php echo $document['course_id'] ?? ''; ?>"
                                    data-visibility="<?php echo $document['visibility']; ?>"
                                    data-tags="<?php echo htmlspecialchars(implode(',', $document['tags'] ?? [])); ?>"
                                    data-file-name="<?php echo htmlspecialchars(basename($document['file_path'])); ?>">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <button class="btn btn-outline-danger btn-sm delete-btn" data-id="<?php echo $document['document_id']; ?>">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <nav aria-label="Document pagination">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="/study_sharing/AdminDocument/admin_manage?page=<?php echo $page - 1; ?>&keyword=<?php echo urlencode($keyword ?? ''); ?>&category_id=<?php echo $category_id; ?>&file_type=<?php echo urlencode($file_type ?? ''); ?>">Trước</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="/study_sharing/AdminDocument/admin_manage?page=<?php echo $i; ?>&keyword=<?php echo urlencode($keyword ?? ''); ?>&category_id=<?php echo $category_id; ?>&file_type=<?php echo urlencode($file_type ?? ''); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="/study_sharing/AdminDocument/admin_manage?page=<?php echo $page + 1; ?>&keyword=<?php echo urlencode($keyword ?? ''); ?>&category_id=<?php echo $category_id; ?>&file_type=<?php echo urlencode($file_type ?? ''); ?>">Sau</a>
                </li>
            </ul>
        </nav>
    </div>
</div>
<!-- Add Document Modal -->
<div class="modal fade" id="addDocumentModal" tabindex="-1" aria-labelledby="addDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addDocumentModalLabel">Thêm tài liệu mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addDocumentForm" method="POST" action="/study_sharing/AdminDocument/admin_add" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="addDocumentTitle" class="form-label">Tiêu đề</label>
                                <input type="text" class="form-control" id="addDocumentTitle" name="title" required>
                                <div class="invalid-feedback">Vui lòng nhập tiêu đề.</div>
                            </div>
                            <div class="mb-3">
                                <label for="addDocumentCategory" class="form-label">Danh mục</label>
                                <select class="form-control" id="addDocumentCategory" name="category_id">
                                    <option value="0">Không chọn</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['category_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="addDocumentCourse" class="form-label">Khóa học</label>
                                <select class="form-control" id="addDocumentCourse" name="course_id">
                                    <option value="">Không chọn</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['course_id']; ?>"><?php echo htmlspecialchars($course['course_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="addDocumentDescription" class="form-label">Mô tả</label>
                                <textarea class="form-control" id="addDocumentDescription" name="description" rows="4"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="addDocumentVisibility" class="form-label">Chế độ hiển thị</label>
                                <select class="form-control" id="addDocumentVisibility" name="visibility" required>
                                    <option value="public">Công khai</option>
                                    <option value="private">Riêng tư</option>
                                </select>
                                <div class="invalid-feedback">Vui lòng chọn chế độ hiển thị.</div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4 autocomplete-container">
                        <label for="addDocumentTags" class="form-label">Thẻ (click để chọn thẻ từ danh sách)</label>
                        <input type="text" class="form-control" id="addDocumentTags" name="tags" placeholder="Click để chọn thẻ..." readonly>
                        <ul class="autocomplete-dropdown list-unstyled m-0">
                            <?php foreach ($tags as $tag): ?>
                                <li class="autocomplete-item" data-value="<?php echo htmlspecialchars($tag['tag_name']); ?>">
                                    <?php echo htmlspecialchars($tag['tag_name']); ?>
                                    <span class="tick d-none"><i class="bi bi-check"></i></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="mb-4">
                        <label for="addDocumentFile" class="form-label">Tệp tài liệu <span class="text-danger">*</span></label>
                        <label for="addDocumentFile" class="file-upload-label">
                            <i class="bi bi-cloud-arrow-up fs-3"></i>
                            <div class="file-upload-text">Nhấn để tải lên tệp (PDF, DOCX, PPTX)</div>
                            <div id="addFileName" class="text-primary mt-2 fw-medium"></div>
                        </label>
                        <input type="file" class="form-control d-none" id="addDocumentFile" name="file" accept=".pdf,.docx,.pptx" required>
                        <div class="invalid-feedback">Vui lòng chọn tệp tài liệu.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Thêm tài liệu
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Document Modal -->
<div class="modal fade" id="editDocumentModal" tabindex="-1" aria-labelledby="editDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editDocumentModalLabel">Chỉnh sửa tài liệu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editDocumentForm" method="POST" action="/study_sharing/AdminDocument/admin_edit" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <input type="hidden" id="editDocumentId" name="document_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editDocumentTitle" class="form-label">Tiêu đề</label>
                                <input type="text" class="form-control" id="editDocumentTitle" name="title" required>
                                <div class="invalid-feedback">Vui lòng nhập tiêu đề.</div>
                            </div>
                            <div class="mb-3">
                                <label for="editDocumentCategory" class="form-label">Danh mục</label>
                                <select class="form-control" id="editDocumentCategory" name="category_id">
                                    <option value="0">Không chọn</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['category_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="editDocumentCourse" class="form-label">Khóa học</label>
                                <select class="form-control" id="editDocumentCourse" name="course_id">
                                    <option value="">Không chọn</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['course_id']; ?>"><?php echo htmlspecialchars($course['course_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editDocumentDescription" class="form-label">Mô tả</label>
                                <textarea class="form-control" id="editDocumentDescription" name="description" rows="4"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="editDocumentVisibility" class="form-label">Chế độ hiển thị</label>
                                <select class="form-control" id="editDocumentVisibility" name="visibility" required>
                                    <option value="public">Công khai</option>
                                    <option value="private">Riêng tư</option>
                                </select>
                                <div class="invalid-feedback">Vui lòng chọn chế độ hiển thị.</div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4 autocomplete-container">
                        <label for="editDocumentTags" class="form-label">Thẻ (click để chọn thẻ từ danh sách)</label>
                        <input type="text" class="form-control" id="editDocumentTags" name="tags" placeholder="Click để chọn thẻ..." readonly>
                        <ul class="autocomplete-dropdown list-unstyled m-0">
                            <?php foreach ($tags as $tag): ?>
                                <li class="autocomplete-item" data-value="<?php echo htmlspecialchars($tag['tag_name']); ?>">
                                    <?php echo htmlspecialchars($tag['tag_name']); ?>
                                    <span class="tick d-none"><i class="bi bi-check"></i></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="mb-4">
                        <label for="editDocumentFile" class="form-label">Tệp tài liệu</label>
                        <label for="editDocumentFile" class="file-upload-label">
                            <i class="bi bi-cloud-arrow-up fs-3"></i>
                            <div class="file-upload-text">Nhấn để thay đổi tệp (PDF, DOCX, PPTX)</div>
                            <div id="currentFileName" class="text-primary mt-2 fw-medium"></div>
                        </label>
                        <input type="file" class="form-control d-none" id="editDocumentFile" name="file" accept=".pdf,.docx,.pptx">
                        <small class="text-muted d-block mt-1">Để trống nếu không muốn thay đổi tệp</small>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
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

<script src="/study_sharing/assets/js/admin_document.js"></script>