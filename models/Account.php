<?php

namespace App;

use PDO;
use PDOException;

class Account
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAccountById($account_id)
    {
        $query = "SELECT * FROM accounts WHERE account_id = :account_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':account_id', $account_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllAccounts()
    {
        $query = "
        SELECT a.account_id, a.username, a.email, a.role, a.status, a.created_at, a.updated_at,
            u.full_name, u.avatar, u.date_of_birth, u.phone_number, u.address
        FROM accounts a
        LEFT JOIN users u ON a.account_id = u.account_id
    ";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    public function createAccount($username, $email, $password, $role = 'student', $status = 'active')
    {
        $query = "INSERT INTO accounts (username, email, password, role, status) VALUES (:username, :email, :password, :role, :status)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':password', $password, PDO::PARAM_STR);
        $stmt->bindParam(':role', $role, PDO::PARAM_STR);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->execute();
        return $this->db->lastInsertId();
    }

    public function updateAccount($account_id, $username = null, $email = null, $password = null, $role = null, $status = null)
    {
        $updates = [];
        $params = [':account_id' => $account_id];

        if ($username !== null) {
            $updates[] = 'username = :username';
            $params[':username'] = $username;
        }
        if ($email !== null) {
            $updates[] = 'email = :email';
            $params[':email'] = $email;
        }
        if ($password !== null) {
            $updates[] = 'password = :password';
            $params[':password'] = $password;
        }
        if ($role !== null) {
            $updates[] = 'role = :role';
            $params[':role'] = $role;
        }
        if ($status !== null) {
            $updates[] = 'status = :status';
            $params[':status'] = $status;
        }

        if (empty($updates)) {
            return; // Không có gì để cập nhật
        }

        $updates[] = 'updated_at = NOW()';
        $query = "UPDATE accounts SET " . implode(', ', $updates) . " WHERE account_id = :account_id";
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Update account error: " . $e->getMessage());
            throw $e;
        }
    }

    public function deleteAccount($account_id)
    {
        $query = "DELETE FROM accounts WHERE account_id = :account_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':account_id', $account_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function getAccountByUsernameOrEmail($username_or_email)
    {
        $query = "SELECT * FROM accounts WHERE username = :username OR email = :email LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username', $username_or_email, PDO::PARAM_STR);
        $stmt->bindParam(':email', $username_or_email, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function countAccounts()
    {
        $query = "SELECT COUNT(*) FROM accounts";
        $stmt = $this->db->query($query);
        return $stmt->fetchColumn();
    }

    public function updatePassword($account_id, $password)
    {
        try {
            $query = "UPDATE accounts
                    SET password = :password, updated_at = NOW()
                    WHERE account_id = :account_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':account_id', $account_id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Update password error: " . $e->getMessage());
            throw $e;
        }
    }
    public function getUsersByRole($role)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE role = ?");
        $stmt->execute([$role]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
