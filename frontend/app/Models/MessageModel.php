<?php

namespace App\Models;

use CodeIgniter\Model;

class MessageModel extends Model
{
    protected $table         = 'messages';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'sender_id', 'receiver_id', 'type', 'content',
        'file_path', 'file_name', 'file_size', 'file_mime', 'file_url',
        'reply_to_id', 'is_deleted', 'deleted_by',
    ];

    // ─────────────────────────────────────────
    // Get full conversation between two users
    // Excludes: globally deleted (is_deleted=1) AND
    //           messages the requesting user deleted for themselves
    // Includes: delivery_status, read_at, sender online status
    // ─────────────────────────────────────────
    public function getConversation(int $userA, int $userB, int $limit = 50, int $offset = 0): array
    {
        return $this->db->query("
            SELECT
                m.*,
                s.username   AS sender_username,
                s.name       AS sender_name,
                s.photo      AS sender_photo,
                s.is_online  AS sender_is_online,
                s.last_seen  AS sender_last_seen,
                r.username   AS receiver_username,
                r.name       AS receiver_name,
                r.is_online  AS receiver_is_online,
                r.last_seen  AS receiver_last_seen,
                COALESCE(ms.status, 'sent') AS delivery_status,
                ms.delivered_at,
                ms.read_at,
                -- reactions as JSON array
                IFNULL(
                    (
                        SELECT CONCAT('[',
                            GROUP_CONCAT(
                                JSON_OBJECT('user_id', mr.user_id, 'reaction', mr.reaction)
                                SEPARATOR ','
                            ),
                        ']')
                        FROM message_reactions mr
                        WHERE mr.message_id = m.id
                    ),
                    '[]'
                ) AS reactions,
                -- reply preview
                rm.content       AS reply_content,
                rm.type          AS reply_type,
                rm.file_name     AS reply_file_name,
                rs.name          AS reply_sender_name
            FROM messages m
            INNER JOIN users s  ON s.id = m.sender_id
            INNER JOIN users r  ON r.id = m.receiver_id
            LEFT  JOIN message_status ms
                   ON ms.message_id = m.id AND ms.user_id = m.receiver_id
            LEFT  JOIN messages rm ON rm.id = m.reply_to_id
            LEFT  JOIN users    rs ON rs.id = rm.sender_id
            WHERE (
                (m.sender_id = ? AND m.receiver_id = ?)
                OR
                (m.sender_id = ? AND m.receiver_id = ?)
            )
            AND m.is_deleted = 0
            AND m.id NOT IN (
                SELECT message_id FROM message_deletions WHERE user_id = ?
            )
            ORDER BY m.created_at DESC
            LIMIT ? OFFSET ?
        ", [$userA, $userB, $userB, $userA, $userA, $limit, $offset])
        ->getResultArray();
    }

    // ─────────────────────────────────────────
    // Conversation list (inbox — last message per contact)
    // Excludes messages deleted for the user
    // ─────────────────────────────────────────
    public function getInbox(int $userId): array
    {
        return $this->db->query("
            SELECT
                m.*,
                u.id          AS contact_id,
                u.name        AS contact_name,
                u.username    AS contact_username,
                u.photo       AS contact_photo,
                u.is_online   AS contact_is_online,
                u.last_seen   AS contact_last_seen,
                COALESCE(ms_inbox.status, 'sent') AS last_message_status,
                ms_inbox.delivered_at             AS last_delivered_at,
                ms_inbox.read_at                  AS last_read_at,
                (
                    SELECT COUNT(*) FROM messages unread
                    LEFT JOIN message_status ms2
                        ON ms2.message_id = unread.id AND ms2.user_id = ?
                    WHERE unread.sender_id   = u.id
                      AND unread.receiver_id = ?
                      AND (ms2.status IS NULL OR ms2.status != 'read')
                      AND unread.is_deleted = 0
                      AND unread.id NOT IN (
                          SELECT message_id FROM message_deletions WHERE user_id = ?
                      )
                ) AS unread_count
            FROM messages m
            INNER JOIN users u ON u.id = IF(m.sender_id = ?, m.receiver_id, m.sender_id)
            LEFT  JOIN message_status ms_inbox
                    ON ms_inbox.message_id = m.id AND ms_inbox.user_id = m.receiver_id
            WHERE (m.sender_id = ? OR m.receiver_id = ?)
              AND m.is_deleted = 0
              AND m.id NOT IN (
                  SELECT message_id FROM message_deletions WHERE user_id = ?
              )
              AND m.id = (
                  SELECT id FROM messages m2
                  WHERE (
                      (m2.sender_id = ? AND m2.receiver_id = u.id)
                      OR
                      (m2.sender_id = u.id AND m2.receiver_id = ?)
                  )
                  AND m2.is_deleted = 0
                  AND m2.id NOT IN (
                      SELECT message_id FROM message_deletions WHERE user_id = ?
                  )
                  ORDER BY m2.created_at DESC
                  LIMIT 1
              )
            ORDER BY m.created_at DESC
        ", [
            $userId, $userId, $userId,
            $userId, $userId, $userId, $userId,
            $userId, $userId, $userId
        ])
        ->getResultArray();
    }

    // ─────────────────────────────────────────
    // Get media messages by type
    // ─────────────────────────────────────────
    public function getMediaMessages(int $userA, int $userB, string $type, int $limit = 50, int $offset = 0): array
    {
        return $this->db->query("
            SELECT
                m.*,
                s.name     AS sender_name,
                s.username AS sender_username,
                s.photo    AS sender_photo
            FROM messages m
            INNER JOIN users s ON s.id = m.sender_id
            WHERE (
                (m.sender_id = ? AND m.receiver_id = ?)
                OR
                (m.sender_id = ? AND m.receiver_id = ?)
            )
            AND m.type       = ?
            AND m.is_deleted = 0
            AND m.id NOT IN (
                SELECT message_id FROM message_deletions WHERE user_id = ?
            )
            ORDER BY m.created_at DESC
            LIMIT ? OFFSET ?
        ", [$userA, $userB, $userB, $userA, $type, $userA, $limit, $offset])
        ->getResultArray();
    }

    // ─────────────────────────────────────────
    // Get all reply messages in a conversation
    // ─────────────────────────────────────────
    public function getReplies(int $userA, int $userB, int $limit = 50, int $offset = 0): array
    {
        return $this->db->query("
            SELECT
                m.*,
                s.name       AS sender_name,
                s.username   AS sender_username,
                s.photo      AS sender_photo,
                rm.content   AS reply_content,
                rm.type      AS reply_type,
                rm.file_name AS reply_file_name,
                rs.name      AS reply_sender_name
            FROM messages m
            INNER JOIN users s     ON s.id  = m.sender_id
            INNER JOIN messages rm ON rm.id = m.reply_to_id
            INNER JOIN users    rs ON rs.id = rm.sender_id
            WHERE (
                (m.sender_id = ? AND m.receiver_id = ?)
                OR
                (m.sender_id = ? AND m.receiver_id = ?)
            )
            AND m.reply_to_id IS NOT NULL
            AND m.is_deleted  = 0
            AND m.id NOT IN (
                SELECT message_id FROM message_deletions WHERE user_id = ?
            )
            ORDER BY m.created_at DESC
            LIMIT ? OFFSET ?
        ", [$userA, $userB, $userB, $userA, $userA, $limit, $offset])
        ->getResultArray();
    }

    // ─────────────────────────────────────────
    // Get reactions for a specific message
    // ─────────────────────────────────────────
    public function getReactions(int $messageId): array
    {
        return $this->db->query("
            SELECT
                mr.reaction,
                mr.created_at,
                u.id       AS user_id,
                u.name     AS user_name,
                u.username AS user_username,
                u.photo    AS user_photo
            FROM message_reactions mr
            INNER JOIN users u ON u.id = mr.user_id
            WHERE mr.message_id = ?
            ORDER BY mr.created_at ASC
        ", [$messageId])
        ->getResultArray();
    }

    // ─────────────────────────────────────────
    // Unread count total for a user
    // ─────────────────────────────────────────
    public function getUnreadCount(int $userId): int
    {
        $row = $this->db->query("
            SELECT COUNT(*) AS total
            FROM messages m
            LEFT JOIN message_status ms ON ms.message_id = m.id AND ms.user_id = ?
            WHERE m.receiver_id = ?
              AND (ms.status IS NULL OR ms.status != 'read')
              AND m.is_deleted = 0
              AND m.id NOT IN (
                  SELECT message_id FROM message_deletions WHERE user_id = ?
              )
        ", [$userId, $userId, $userId])->getRowArray();

        return (int) ($row['total'] ?? 0);
    }
}
