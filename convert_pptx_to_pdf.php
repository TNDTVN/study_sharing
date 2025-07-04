<?php
// Tắt hiển thị lỗi để ngăn HTML phá hỏng JSON
error_reporting(0);
ini_set('display_errors', 0);

// Ghi log lỗi vào console trình duyệt
function logToConsole($message)
{
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

function convertPptxToPdfCOM($pptxPath, $outputDir)
{
    try {
        // Kiểm tra xem extension COM có được tải không
        if (!extension_loaded('com_dotnet')) {
            error_log('Extension com_dotnet không được tải.');
            logToConsole('Extension com_dotnet không được tải. Vui lòng kích hoạt trong php.ini.');
        }

        // Ánh xạ đường dẫn tương đối thành đường dẫn tuyệt đối
        $relativePath = str_replace('/study_sharing', '', $pptxPath);
        $absolutePptxPath = __DIR__ . $relativePath;
        $absolutePptxPath = str_replace('/', DIRECTORY_SEPARATOR, $absolutePptxPath);
        error_log('Checking PPTX Path: ' . $absolutePptxPath);

        // Kiểm tra file PPTX tồn tại
        if (!file_exists($absolutePptxPath)) {
            error_log('File PPTX không tồn tại: ' . $absolutePptxPath);
            logToConsole('File PPTX không tồn tại: ' . $absolutePptxPath);
        }

        // Kiểm tra quyền đọc file
        if (!is_readable($absolutePptxPath)) {
            error_log('Không có quyền đọc file PPTX: ' . $absolutePptxPath);
            logToConsole('Không có quyền đọc file PPTX: ' . $absolutePptxPath);
        }

        // Đảm bảo thư mục đầu ra tồn tại
        if (!is_dir($outputDir)) {
            if (!mkdir($outputDir, 0777, true)) {
                error_log('Không thể tạo thư mục đầu ra: ' . $outputDir);
                logToConsole('Không thể tạo thư mục đầu ra: ' . $outputDir);
            }
        }

        // Kiểm tra quyền ghi thư mục
        if (!is_writable($outputDir)) {
            error_log('Không có quyền ghi vào thư mục: ' . $outputDir);
            logToConsole('Không có quyền ghi vào thư mục: ' . $outputDir);
        }

        // Tạo đường dẫn file PDF đầu ra
        $outputPath = $outputDir . DIRECTORY_SEPARATOR . basename($pptxPath, '.pptx') . '.pdf';
        error_log('Output PDF Path: ' . $outputPath);

        // Sử dụng PowerPoint COM để chuyển đổi
        $powerPoint = new COM("PowerPoint.Application");
        // Bỏ $powerPoint->Visible = false để tránh lỗi
        $presentation = $powerPoint->Presentations->Open($absolutePptxPath, false, false, false);
        $presentation->SaveAs($outputPath, 32); // 32 là định dạng PDF
        $presentation->Close();
        $powerPoint->Quit();
        unset($presentation, $powerPoint); // Giải phóng tài nguyên

        // Kiểm tra file PDF đã được tạo
        if (!file_exists($outputPath)) {
            error_log('File PDF không được tạo: ' . $outputPath);
            logToConsole('Chuyển đổi PPTX sang PDF thất bại: File PDF không được tạo.');
        }

        // Chuyển đường dẫn tuyệt đối thành đường dẫn tương đối
        $relativePdfPath = '/study_sharing' . str_replace(__DIR__, '', $outputPath);
        $relativePdfPath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePdfPath);
        return $relativePdfPath;
    } catch (Exception $e) {
        error_log('COM Error: ' . $e->getMessage());
        logToConsole('Lỗi PowerPoint COM: ' . $e->getMessage());
        return false;
    }
}

// Xử lý yêu cầu
header('Content-Type: application/json; charset=utf-8');
if (isset($_GET['file'])) {
    $pptxPath = $_GET['file'];
    $outputDir = __DIR__ . '/uploads/converted';

    // Ghi log đường dẫn để debug
    error_log('Received PPTX Path: ' . $pptxPath);

    try {
        $pdfPath = convertPptxToPdfCOM($pptxPath, $outputDir);
        if ($pdfPath) {
            echo json_encode(['success' => true, 'pdfPath' => $pdfPath]);
        } else {
            logToConsole('Chuyển đổi PPTX sang PDF thất bại.');
        }
    } catch (Exception $e) {
        error_log('Request Error: ' . $e->getMessage());
        logToConsole('Lỗi xử lý yêu cầu: ' . $e->getMessage());
    }
} else {
    logToConsole('Không có đường dẫn file được cung cấp');
}
