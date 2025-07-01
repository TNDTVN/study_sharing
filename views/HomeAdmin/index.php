<h1 class="mb-4 text-primary"><i class="bi bi-speedometer2 me-2"></i> Dashboard Quản trị</h1>

<!-- Thống kê -->
<div class="row g-4 mb-5">
    <div class="col-md-3 col-sm-6">
        <div class="card dashboard-card card-users shadow-sm">
            <div class="card-body">
                <i class="bi bi-people card-icon"></i>
                <h5 class="card-title">Người dùng</h5>
                <p class="card-text"><?php echo $totalUsers; ?></p>
                <a href="/Account/manage" class="quick-link">Quản lý người dùng</a>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card dashboard-card card-documents shadow-sm">
            <div class="card-body">
                <i class="bi bi-file-earmark-text card-icon"></i>
                <h5 class="card-title">Tài liệu</h5>
                <p class="card-text"><?php echo $totalDocuments; ?></p>
                <a href="/document/delete" class="quick-link">Quản lý tài liệu</a>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card dashboard-card card-courses shadow-sm">
            <div class="card-body">
                <i class="bi bi-book card-icon"></i>
                <h5 class="card-title">Khóa học</h5>
                <p class="card-text"><?php echo $totalCourses; ?></p>
                <a href="/course/manage" class="quick-link">Quản lý khóa học</a>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card dashboard-card card-categories shadow-sm">
            <div class="card-body">
                <i class="bi bi-folder card-icon"></i>
                <h5 class="card-title">Danh mục</h5>
                <p class="card-text"><?php echo $totalCategories; ?></p>
                <a href="/category/manage" class="quick-link">Quản lý danh mục</a>
            </div>
        </div>
    </div>
</div>

<!-- Nút điều hướng nhanh -->
<div class="row g-4">
    <div class="col-12">
        <h3 class="mb-3 text-primary">Hành động nhanh</h3>
        <div class="d-flex flex-wrap gap-3">
            <a href="/Account/manage" class="btn btn-outline-primary quick-action-btn"><i class="bi bi-people me-2"></i> Quản lý người dùng</a>
            <a href="/category/manage" class="btn btn-outline-primary quick-action-btn"><i class="bi bi-folder me-2"></i> Quản lý danh mục</a>
            <a href="/tag/manage" class="btn btn-outline-primary quick-action-btn"><i class="bi bi-tag me-2"></i> Quản lý thẻ</a>
            <a href="/document/delete" class="btn btn-outline-primary quick-action-btn"><i class="bi bi-file-earmark-text me-2"></i> Quản lý tài liệu</a>
            <a href="/course/manage" class="btn btn-outline-primary quick-action-btn"><i class="bi bi-book me-2"></i> Quản lý khóa học</a>
        </div>
    </div>
</div>