<?php

namespace App;

use PDO;

class Rating
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getRatingById($rating_id)
    {
        $query = "SELECT * FROM ratings WHERE rating_id = :rating_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':rating_id', $rating_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getRatingsByDocumentId($document_id)
    {
        $query = "SELECT * FROM ratings WHERE document_id = :document_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':document_id', $document_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createRating($document_id, $account_id, $rating_value)
    {
        $query = "INSERT INTO ratings (document_id, account_id, rating_value, created_at) VALUES (:document_id, :account_id, :rating_value, NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':document_id', $document_id, PDO::PARAM_INT);
        $stmt->bindParam(':account_id', $account_id, PDO::PARAM_INT);
        $stmt->bindParam(':rating_value', $rating_value, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
