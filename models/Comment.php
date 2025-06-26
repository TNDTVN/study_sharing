<?php

namespace App;

use PDO;
use Exception;

class Comment
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getCommentById($comment_id)
    {
        $query = "SELECT * FROM comments WHERE comment_id = :comment_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':comment_id', $comment_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createComment($document_id, $account_id, $comment_text, $parent_comment_id = null)
    {
        try {
            $query = "INSERT INTO comments (document_id, account_id, comment_text, comment_date, parent_comment_id) 
                      VALUES (:document_id, :account_id, :comment_text, NOW(), :parent_comment_id)";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':document_id', $document_id, PDO::PARAM_INT);
            $stmt->bindValue(':account_id', $account_id, PDO::PARAM_INT);
            $stmt->bindValue(':comment_text', $comment_text, PDO::PARAM_STR);
            $stmt->bindValue(':parent_comment_id', $parent_comment_id, $parent_comment_id !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error creating comment: " . $e->getMessage());
            return false;
        }
    }

    public function getCommentsByDocumentId($document_id, $limit = 5, $offset = 0)
    {
        $query = "SELECT c.*, u.full_name, u.avatar 
                  FROM comments c 
                  LEFT JOIN users u ON c.account_id = u.account_id 
                  WHERE c.document_id = :document_id 
                  ORDER BY 
                    CASE WHEN c.parent_comment_id IS NULL THEN 0 ELSE 1 END, 
                    c.parent_comment_id, 
                    CASE WHEN c.parent_comment_id IS NULL THEN c.comment_date END DESC,
                    CASE WHEN c.parent_comment_id IS NOT NULL THEN c.comment_date END ASC";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':document_id', $document_id, PDO::PARAM_INT);
        $stmt->execute();
        $allComments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $comments = [];
        $repliesMap = [];

        foreach ($allComments as &$comment) {
            $comment['replies'] = [];
            $comment['user'] = [
                'full_name' => $comment['full_name'] ?: 'Ẩn danh',
                'avatar' => $comment['avatar']
            ];
            unset($comment['full_name'], $comment['avatar']);
        }
        unset($comment);

        foreach ($allComments as $comment) {
            if ($comment['parent_comment_id'] === null) {
                $comments[$comment['comment_id']] = $comment;
            } else {
                $repliesMap[$comment['parent_comment_id']][] = $comment;
            }
        }

        function buildCommentTree(&$comments, $repliesMap, $parentId)
        {
            if (isset($repliesMap[$parentId])) {
                usort($repliesMap[$parentId], function ($a, $b) {
                    return strtotime($a['comment_date']) - strtotime($b['comment_date']);
                });
                foreach ($repliesMap[$parentId] as $reply) {
                    $comments[$parentId]['replies'][$reply['comment_id']] = $reply;
                    buildCommentTree($comments[$parentId]['replies'], $repliesMap, $reply['comment_id']);
                }
            }
        }

        foreach ($comments as $commentId => &$comment) {
            buildCommentTree($comments, $repliesMap, $commentId);
        }
        unset($comment);

        uasort($comments, function ($a, $b) {
            return strtotime($b['comment_date']) - strtotime($a['comment_date']);
        });

        $totalComments = count($comments);
        $pagedComments = array_slice($comments, $offset, $limit, true);

        return [
            'comments' => array_values($pagedComments),
            'total' => $totalComments
        ];
    }

    public function deleteComment($comment_id, $account_id)
    {
        try {
            // Kiểm tra bình luận có thuộc về người dùng và trong vòng 1 giờ
            $query = "SELECT comment_date FROM comments WHERE comment_id = :comment_id AND account_id = :account_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':comment_id', $comment_id, PDO::PARAM_INT);
            $stmt->bindValue(':account_id', $account_id, PDO::PARAM_INT);
            $stmt->execute();
            $comment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$comment) {
                return false;
            }

            $commentTime = strtotime($comment['comment_date']);
            $currentTime = time();
            if (($currentTime - $commentTime) > 3600) {
                return false;
            }

            // Xóa bình luận
            $deleteQuery = "DELETE FROM comments WHERE comment_id = :comment_id";
            $deleteStmt = $this->db->prepare($deleteQuery);
            $deleteStmt->bindValue(':comment_id', $comment_id, PDO::PARAM_INT);
            return $deleteStmt->execute();
        } catch (Exception $e) {
            error_log("Error deleting comment: " . $e->getMessage());
            return false;
        }
    }
}
