<?php

namespace App;

use PDO;
use PDOException;

class User
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getUserById($account_id)
    {
        $query = "
            SELECT a.account_id, a.username, a.email, a.role, a.status, a.created_at, a.updated_at,
                u.user_id, u.full_name, u.avatar, u.date_of_birth, u.phone_number, u.address
            FROM accounts a
            LEFT JOIN users u ON a.account_id = u.account_id
            WHERE a.account_id = :account_id
        ";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':account_id', $account_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllUsers()
    {
        $query = "
            SELECT a.account_id, a.username, a.email, a.role, a.status, a.created_at, a.updated_at,
                u.user_id, u.full_name, u.avatar, u.date_of_birth, u.phone_number, u.address
            FROM accounts a
            LEFT JOIN users u ON a.account_id = u.account_id
        ";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createUser($account_id, $full_name, $avatar = 'profile.png', $date_of_birth = null, $phone_number = null, $address = null)
    {
        $query = "INSERT INTO users (account_id, full_name, avatar, date_of_birth, phone_number, address) VALUES (:account_id, :full_name, :avatar, :date_of_birth, :phone_number, :address)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':account_id', $account_id, PDO::PARAM_INT);
        $stmt->bindParam(':full_name', $full_name, PDO::PARAM_STR);
        $stmt->bindParam(':avatar', $avatar, PDO::PARAM_STR);
        $stmt->bindParam(':date_of_birth', $date_of_birth, PDO::PARAM_STR);
        $stmt->bindParam(':phone_number', $phone_number, PDO::PARAM_STR);
        $stmt->bindParam(':address', $address, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function updateUser($account_id, $full_name, $avatar = 'profile.png', $date_of_birth = null, $phone_number = null, $address = null)
    {
        $query = "UPDATE users SET full_name = :full_name, avatar = :avatar, date_of_birth = :date_of_birth, phone_number = :phone_number, address = :address WHERE account_id = :account_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':account_id', $account_id, PDO::PARAM_INT);
        $stmt->bindParam(':full_name', $full_name, PDO::PARAM_STR);
        $stmt->bindParam(':avatar', $avatar, PDO::PARAM_STR);
        $stmt->bindParam(':date_of_birth', $date_of_birth, PDO::PARAM_STR);
        $stmt->bindParam(':phone_number', $phone_number, PDO::PARAM_STR);
        $stmt->bindParam(':address', $address, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function deleteUser($account_id)
    {
        $query = "DELETE FROM users WHERE account_id = :account_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':account_id', $account_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function updateUserAvatar($account_id, $avatar)
    {
        $query = "UPDATE users SET avatar = :avatar WHERE account_id = :account_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':account_id', $account_id, PDO::PARAM_INT);
        $stmt->bindParam(':avatar', $avatar, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function getUserByUsernameOrEmail($username_or_email)
    {
        $query = "
            SELECT a.account_id, a.username, a.email, a.password, a.role, a.status, a.created_at, a.updated_at,
                u.user_id, u.full_name, u.avatar, u.date_of_birth, u.phone_number, u.address
            FROM accounts a
            LEFT JOIN users u ON a.account_id = u.account_id
            WHERE a.username = :username_or_email OR a.email = :username_or_email
            LIMIT 1
        ";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username_or_email', $username_or_email, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function countUsers()
    {
        $query = "SELECT COUNT(*) FROM users";
        $stmt = $this->db->query($query);
        return $stmt->fetchColumn();
    }
}
