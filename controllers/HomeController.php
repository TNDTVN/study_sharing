<?php

namespace App;

use App\Document;
use App\Category;
use App\Course;
use App\Notification;
use App\User;

class HomeController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function index()
    {
        $pdo = $this->pdo;

        $documentModel = new Document($this->pdo);
        $categoryModel = new Category($this->pdo);
        $courseModel = new Course($this->pdo);
        $notificationModel = new Notification($this->pdo);
        $userModel = new User($this->pdo);

        $latestDocuments = array_slice($documentModel->getAllDocuments(), 0, 6);
        $categories = $categoryModel->getAllCategories();
        $courses = array_slice($courseModel->getAllCourses(), 0, 6);
        $notifications = [];
        if (isset($_SESSION['account_id'])) {
            $allNotifications = $notificationModel->getUnreadNotificationsByUserId($_SESSION['account_id']);
            $notifications = array_slice($allNotifications, 0, 5);
        }
        $title = 'Trang chủ';
        ob_start();
        require __DIR__ . '/../views/home/index.php';
        $content = ob_get_clean();
        // Truyền $pdo vào layout.php
        $pdo = $this->pdo;
        require __DIR__ . '/../views/layouts/layout.php';
    }
}
