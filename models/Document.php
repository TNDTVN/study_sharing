<?php

namespace App;

use PDO;

class Document
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getDocumentById($document_id)
    {
        $query = "SELECT d.*, c.course_name
                FROM documents d
                LEFT JOIN courses c ON d.course_id = c.course_id
                WHERE d.document_id = :document_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':document_id', $document_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllDocuments()
    {
        $query = "SELECT d.*, c.course_name
                FROM documents d
                LEFT JOIN courses c ON d.course_id = c.course_id";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createDocument($title, $description, $file_path, $account_id, $category_id, $course_id = null, $visibility = 'public')
    {
        $query = "INSERT INTO documents (title, description, file_path, account_id, category_id, course_id, visibility) 
                VALUES (:title, :description, :file_path, :account_id, :category_id, :course_id, :visibility)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':file_path', $file_path, PDO::PARAM_STR);
        $stmt->bindParam(':account_id', $account_id, PDO::PARAM_INT);
        $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
        $stmt->bindParam(':course_id', $course_id, $course_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(':visibility', $visibility, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function updateDocument($document_id, $title, $description, $file_path, $category_id, $course_id = null, $visibility = 'public')
    {
        $query = "UPDATE documents
                SET title = :title, description = :description, file_path = :file_path, 
                    category_id = :category_id, course_id = :course_id, visibility = :visibility 
                WHERE document_id = :document_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':document_id', $document_id, PDO::PARAM_INT);
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':file_path', $file_path, PDO::PARAM_STR);
        $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
        $stmt->bindParam(':course_id', $course_id, $course_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(':visibility', $visibility, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function deleteDocument($document_id)
    {
        $query = "DELETE FROM documents WHERE document_id = :document_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':document_id', $document_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function countDocuments()
    {
        $query = "SELECT COUNT(*) FROM documents";
        $stmt = $this->db->query($query);
        return $stmt->fetchColumn();
    }
}
