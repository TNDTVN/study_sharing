<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/db.php';

use App\UserController;
use App\DocumentController;
use App\HomeController;
use App\HomeAdminController;
use App\AuthController;
use App\CourseController;
use App\AdminCourseController;
use App\CategoryController;
use App\AdminController;
use App\AccountController;
use App\AdminDocumentController;
use App\TagController;
use App\NotificationController;
use App\NotificationAdminController;

session_start();

// Lấy URI và loại bỏ prefix '/study_sharing'
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = trim(str_replace('/study_sharing', '', $uri), '/');
$method = $_SERVER['REQUEST_METHOD'];

// Định nghĩa các tuyến đường tĩnh
$staticRoutes = [
    // Thêm các tuyến đường tĩnh nếu cần
];

// Định nghĩa các controller được phép
$allowedControllers = [
    'UserController' => UserController::class,
    'DocumentController' => DocumentController::class,
    'HomeController' => HomeController::class,
    'HomeAdminController' => HomeAdminController::class,
    'AuthController' => AuthController::class,
    'CourseController' => CourseController::class,
    'CategoryController' => CategoryController::class,
    'AdminController' => AdminController::class,
    'AccountController' => AccountController::class,
    'AdminDocumentController' => AdminDocumentController::class,
    'TagController' => TagController::class,
    'NotificationController' => NotificationController::class,
    'NotificationAdminController' => NotificationAdminController::class,
    'AdminCourseController' => AdminCourseController::class,
];

// Xử lý tuyến đường
function handleRoute($uri, $method, $pdo, $staticRoutes, $allowedControllers)
{
    // Kiểm tra tuyến đường tĩnh
    if (array_key_exists($uri, $staticRoutes)) {
        $route = $staticRoutes[$uri];
        if ($method === $route['method']) {
            $title = $route['title'];
            $layout = $route['layout'];
            ob_start();
            require $route['view'];
            $content = ob_get_clean();
            require __DIR__ . '/views/layouts/' . $layout;
            exit;
        } else {
            http_response_code(405);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }
    }

    // Xử lý tuyến đường động (controller-based)
    $parts = explode('/', $uri);
    $controllerName = !empty($parts[0]) ? ucfirst($parts[0]) . 'Controller' : 'HomeController';
    $action = !empty($parts[1]) ? $parts[1] : 'index';
    $params = array_slice($parts, 2);

    error_log("Processing URI: $uri, Controller: $controllerName, Action: $action");

    if (array_key_exists($controllerName, $allowedControllers)) {
        $controllerClass = $allowedControllers[$controllerName];
        if (class_exists($controllerClass)) {
            $controller = new $controllerClass($pdo);
            if (method_exists($controller, $action) && is_callable([$controller, $action])) {
                if (in_array($method, ['GET', 'POST'])) {
                    ob_start();
                    call_user_func_array([$controller, $action], $params);
                    $output = ob_get_clean();
                    if (!headers_sent() && !empty($output)) {
                        echo $output;
                    }
                    exit;
                } else {
                    http_response_code(405);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                    exit;
                }
            } else {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => "Action '$action' not found in $controllerName"]);
                exit;
            }
        } else {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => "Controller class $controllerClass not found"]);
            exit;
        }
    }

    // Nếu không khớp, trả về 404
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Page not found']);
    exit;
}

// Gọi hàm xử lý tuyến đường
handleRoute($uri, $method, $pdo, $staticRoutes, $allowedControllers);
