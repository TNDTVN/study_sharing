<?php

namespace App;

use PDO;

class Course
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
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

    public function createCourse($course_name, $description, $account_id)
    {
        $query = "INSERT INTO courses (course_name, description, creator_id) VALUES (:course_name, :description, :account_id)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':course_name', $course_name, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':account_id', $account_id, PDO::PARAM_INT);
        return $stmt->execute();
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
