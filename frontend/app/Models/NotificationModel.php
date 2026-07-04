<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table         = 'notifications';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'user_id', 'sender_id', 'type', 'title', 'body', 'data',
        'action_url', 'is_read', 'read_at', 'created_at',
    ];

    // ── Fetch ────────────────────────────────────────

    public function getForUser(int $userId, int $limit = 20, int $offset = 0): array
    {
        return $this->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit, $offset)
            ->findAll();
    }

    public function getTotalForUser(int $userId): int
    {
        return $this->where('user_id', $userId)->countAllResults();
    }

    public function getUnreadCount(int $userId): int
    {
        return $this->where('user_id', $userId)->where('is_read', 0)->countAllResults();
    }

    // ── Mark read ────────────────────────────────────

    public function markRead(int $notifId, int $userId): bool
    {
        return $this->where('id', $notifId)
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->set(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')])
            ->update();
    }

    public function markAllRead(int $userId): void
    {
        $this->where('user_id', $userId)
            ->where('is_read', 0)
            ->set(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')])
            ->update();
    }

    // ── Send (insert one row per recipient) ──────────

    /**
     * Send a notification to multiple users at once.
     * Inserts one row per recipient (same data, different user_id).
     */
    public function sendToMany(array $payload, array $recipientIds): int
    {
        if (empty($recipientIds)) return 0;

        $now  = date('Y-m-d H:i:s');
        $rows = [];
        foreach (array_unique($recipientIds) as $uid) {
            $rows[] = [
                'user_id'    => (int)$uid,
                'sender_id'  => $payload['sender_id']  ?? null,
                'type'       => $payload['type']        ?? 'general',
                'title'      => $payload['title'],
                'body'       => $payload['body'],
                'data'       => isset($payload['data']) ? json_encode($payload['data']) : null,
                'action_url' => $payload['action_url']  ?? null,
                'is_read'    => 0,
                'created_at' => $now,
            ];
        }

        $this->db->table('notifications')->insertBatch($rows);
        return count($rows);
    }

    // ── Helpers for target resolution ────────────────

    public function groupMemberIds(int $groupId): array
    {
        $rows = $this->db->table('group_members')
            ->select('user_id')
            ->where('group_id', $groupId)
            ->get()->getResultArray();
        return array_column($rows, 'user_id');
    }

    public function allActiveUserIds(): array
    {
        $rows = $this->db->table('users')
            ->select('id')
            ->where('status', 'active')
            ->get()->getResultArray();
        return array_column($rows, 'id');
    }

    // ── Admin panel helpers ──────────────────────────

    public function recentSent(int $limit = 40): array
    {
        return $this->db->query("
            SELECT
                MIN(n.id)          AS id,
                n.title,
                n.body,
                n.type,
                n.action_url,
                n.sender_id,
                u.name             AS sender_name,
                MAX(n.created_at)  AS created_at,
                COUNT(n.id)        AS recipient_count,
                SUM(n.is_read)     AS read_count
            FROM notifications n
            LEFT JOIN users u ON u.id = n.sender_id
            GROUP BY n.title, n.body, n.type, n.action_url, n.sender_id, u.name,
                     DATE_FORMAT(n.created_at,'%Y-%m-%d %H:%i:%s')
            ORDER BY MAX(n.created_at) DESC
            LIMIT ?
        ", [$limit])->getResultArray();
    }

    // ── Detail: all recipients of a sent batch ───────

    /**
     * Return all recipient rows for a notification batch.
     * A batch = same title+body+type sent at the same second.
     * Pass the MIN(id) returned by recentSent().
     */
    public function getRecipientsOf(int $anchorId): array
    {
        // Fetch the anchor row to match on
        $anchor = $this->find($anchorId);
        if (!$anchor) return [];

        $ts = date('Y-m-d H:i:%', strtotime($anchor['created_at']));

        return $this->db->query("
            SELECT
                n.id, n.user_id, n.is_read, n.read_at,
                u.name, u.username, u.status
            FROM notifications n
            JOIN users u ON u.id = n.user_id
            WHERE n.title    = ?
              AND n.body     = ?
              AND n.type     = ?
              AND n.created_at LIKE ?
            ORDER BY n.is_read DESC, n.read_at DESC, u.name ASC
        ", [
            $anchor['title'],
            $anchor['body'],
            $anchor['type'],
            $ts,
        ])->getResultArray();
    }

    public function getById(int $id): ?array
    {
        return $this->find($id) ?: null;
    }

    // ── Legacy compat ────────────────────────────────

    public function getUserNotifications(int $userId, bool $unreadOnly = false, int $limit = 50): array
    {
        return $this->getForUser($userId, $limit);
    }

    public function createNotification(int $userId, string $type, string $title, string $body, array $data = []): int
    {
        $this->sendToMany([
            'type'  => $type,
            'title' => $title,
            'body'  => $body,
            'data'  => $data,
        ], [$userId]);
        return (int)$this->db->insertID();
    }
}
