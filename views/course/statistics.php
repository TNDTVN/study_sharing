<div class="container mt-4">
    <h2 class="mb-4"><?= htmlspecialchars($title) ?></h2>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Tổng số khóa học</h5>
                    <p class="card-text fs-3"><?= $totalCourses ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Tổng số thành viên</h5>
                    <p class="card-text fs-3"><?= $totalMembers ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info mb-3">
                <div class="card-body">
                    <h5 class="card-title">Tổng số tài liệu</h5>
                    <p class="card-text fs-3"><?= $totalDocuments ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Biểu đồ: Số thành viên theo khóa học -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Biểu đồ số thành viên theo khóa học</h5>
            <canvas id="memberChart"></canvas>
        </div>
    </div>

    <!-- Bảng dữ liệu -->
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Chi tiết khóa học</h5>
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Tên khóa học</th>
                        <th>Người tạo</th>
                        <th>Số thành viên</th>
                        <th>Số tài liệu</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courses as $course): ?>
                        <tr>
                            <td><?= htmlspecialchars($course['course_name']) ?></td>
                            <td><?= htmlspecialchars($course['username']) ?></td>
                            <td><?= $course['member_count'] ?></td>
                            <td>
                                <?php
                                $docStmt = $pdo->prepare("SELECT COUNT(*) FROM documents WHERE course_id = :id");
                                $docStmt->execute([':id' => $course['course_id']]);
                                echo $docStmt->fetchColumn();
                                ?>
                            </td>
                            <td><?= htmlspecialchars($course['status']) ?></td>
                            <td><?= htmlspecialchars($course['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('memberChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($courses, 'course_name')) ?>,
            datasets: [{
                label: 'Số thành viên',
                data: <?= json_encode(array_column($courses, 'member_count')) ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>