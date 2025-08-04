<?php

namespace App;

use PDO;
use Exception;

class Course
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Cập nhật trạng thái của khóa học
     *
     * @param int $course_id ID của khóa học
     * @param string $status Trạng thái mới của khóa học (open, closed, in_progress, pending, cancelled)
     * @return bool Trả về true nếu cập nhật thành công, false nếu thất bại
     * @throws Exception Nếu course_id hoặc status không hợp lệ
     */
    public function updateCourseStatus(int $course_id, string $status): bool
    {
        // Kiểm tra course_id hợp lệ
        if ($course_id <= 0) {
            throw new Exception('ID khóa học không hợp lệ.');
        }

        // Kiểm tra trạng thái hợp lệ
        $allowedStatuses = ['open', 'closed', 'in_progress', 'pending', 'cancelled'];
        if (!in_array($status, $allowedStatuses)) {
            throw new Exception('Trạng thái không hợp lệ. Trạng thái phải là: ' . implode(', ', $allowedStatuses));
        }

        try {
            $query = "UPDATE courses SET status = :status WHERE course_id = :course_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
            $success = $stmt->execute();

            if (!$success) {
                error_log("Failed to update course status for course_id: $course_id, status: $status");
                return false;
            }

            return true;
        } catch (Exception $e) {
            error_log("Error updating course status for course_id: $course_id, status: $status. Error: " . $e->getMessage());
            throw new Exception('Lỗi khi cập nhật trạng thái khóa học: ' . $e->getMessage());
        }
    }

    /**
     * Tạo khóa học mới với trạng thái mặc định là 'pending'
     *
     * @param string $course_name Tên khóa học
     * @param string $description Mô tả khóa học
     * @param int $account_id ID của người tạo khóa học
     * @return bool Trả về true nếu tạo thành công, false nếu thất bại
     */
    public function createCourse($course_name, $description, $account_id)
    {
        $query = "INSERT INTO courses (course_name, description, creator_id, status) 
                  VALUES (:course_name, :description, :account_id, 'pending')";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':course_name', $course_name, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':account_id', $account_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function getCourseById($course_id)
    {
        $query = "SELECT * FROM courses WHERE course_id = :course_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllCourses()
    {
        $query = "SELECT * FROM courses";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countCourses()
    {
        $query = "SELECT COUNT(*) FROM courses";
        $stmt = $this->db->query($query);
        return $stmt->fetchColumn();
    }

    public function getCoursesByTeacher($teacher_id)
    {
        $query = "SELECT * FROM courses WHERE creator_id = :teacher_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCoursesByStudent($student_id)
    {
        $query = "SELECT * FROM courses";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateCourse($course_id, $course_name, $description, $max_members, $learn_link, $start_date, $end_date)
    {
        $query = "UPDATE courses 
                 SET course_name = :course_name,
                     description = :description,
                     max_members = :max_members,
                     learn_link = :learn_link,
                     start_date = :start_date,
                     end_date = :end_date
                 WHERE course_id = :course_id";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':course_name', $course_name, PDO::PARAM_STR);
        $stmt->bindValue(':description', $description ?: null, $description ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':max_members', $max_members, PDO::PARAM_INT);
        $stmt->bindValue(':learn_link', $learn_link ?: null, $learn_link ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':start_date', $start_date, $start_date ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':end_date', $end_date, $end_date ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
