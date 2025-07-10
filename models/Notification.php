<?php

namespace App;

use PDO;

class Notification
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getNotificationById($notification_id)
    {
        $query = "SELECT * FROM notifications WHERE notification_id = :notification_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':notification_id', $notification_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getNotificationsByUserId($account_id, $offset = 0, $limit = 10)
    {
        $query = "SELECT * FROM notifications WHERE account_id = :account_id ORDER BY created_at DESC LIMIT :offset, :limit";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':account_id', $account_id, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countNotificationsByUserId($account_id)
    {
        $query = "SELECT COUNT(*) FROM notifications WHERE account_id = :account_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':account_id', $account_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function createNotification($account_id, $message, $is_read = false)
    {
        $query = "INSERT INTO notifications (account_id, message, is_read) VALUES (:account_id, :message, :is_read)";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':account_id', $account_id, PDO::PARAM_INT);
        $stmt->bindParam(':message', $message, PDO::PARAM_STR);
        $stmt->bindParam(':is_read', $is_read, PDO::PARAM_BOOL);
        return $stmt->execute();
    }

    public function markAsRead($notification_id, $account_id)
    {
        $query = "UPDATE notifications SET is_read = 1 WHERE notification_id = :notification_id AND account_id = :account_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':notification_id', $notification_id, PDO::PARAM_INT);
        $stmt->bindParam(':account_id', $account_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function markAllAsRead($account_id)
    {
        $query = "UPDATE notifications SET is_read = 1 WHERE account_id = :account_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':account_id', $account_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function deleteNotification($notification_id, $account_id)
    {
        $query = "DELETE FROM notifications WHERE notification_id = :notification_id AND account_id = :account_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':notification_id', $notification_id, PDO::PARAM_INT);
        $stmt->bindParam(':account_id', $account_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function deleteAllNotifications($account_id)
    {
        $query = "DELETE FROM notifications WHERE account_id = :account_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':account_id', $account_id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
