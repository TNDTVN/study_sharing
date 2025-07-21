<?php

namespace App;

use App\User;
use App\Document;
use App\Course;
use App\Category;

class HomeAdminController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function index()
    {
        $pdo = $this->pdo;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Kiểm tra quyền admin
        if (!isset($_SESSION['account_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header('Location: /study_sharing');
            exit;
        }

        // Lấy dữ liệu thống kê
        $userModel = new User($pdo);
        $documentModel = new Document($pdo);
        $courseModel = new Course($pdo);
        $categoryModel = new Category($pdo);

        $totalUsers = $userModel->countUsers();
        $totalDocuments = $documentModel->countDocuments();
        $totalCourses = $courseModel->countCourses();
        $totalCategories = $categoryModel->countCategories();
        $documentsByCategory = $documentModel->countDocumentsByCategory();

        $title = 'Bảng điều khiển Admin';
        ob_start();
        require __DIR__ . '/../views/HomeAdmin/index.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layouts/admin_layout.php';
    }
}
