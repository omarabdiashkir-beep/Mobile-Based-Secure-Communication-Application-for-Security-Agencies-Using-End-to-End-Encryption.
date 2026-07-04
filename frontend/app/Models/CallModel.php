<?php

namespace App\Models;

use CodeIgniter\Model;

class CallModel extends Model
{
    protected $table         = 'calls';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps  = false;

    protected $allowedFields = [
        'caller_id', 'receiver_id', 'group_id', 'type', 'status',
        'room_id', 'sdp_offer', 'ice_candidates',
        'started_at', 'answered_at', 'ended_at', 'duration', 'created_at',
    ];

    public function getCallHistory(int $userId, int $limit = 30, int $offset = 0): array
    {
        $db = \Config\Database::connect();
        return $db->query("
            SELECT c.*,
                   caller.username as caller_username, caller.avatar_path as caller_avatar,
                   receiver.username as receiver_username, receiver.avatar_path as receiver_avatar
            FROM calls c
            LEFT JOIN users caller ON caller.id = c.caller_id
            LEFT JOIN users receiver ON receiver.id = c.receiver_id
            WHERE c.caller_id = ? OR c.receiver_id = ?
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?
        ", [$userId, $userId, $limit, $offset])->getResultArray();
    }

    public function getMissedCalls(int $userId): array
    {
        return $this->select('calls.*, u.username as caller_username, u.avatar_path as caller_avatar')
            ->join('users u', 'u.id = calls.caller_id', 'left')
            ->where('receiver_id', $userId)
            ->where('status', 'missed')
            ->orderBy('created_at', 'DESC')
            ->limit(50)
            ->findAll();
    }

    public function logEvent(int $callId, int $userId, string $event, array $payload = []): void
    {
        $db = \Config\Database::connect();
        $db->table('call_logs')->insert([
            'call_id'    => $callId,
            'user_id'    => $userId,
            'event'      => $event,
            'payload'    => json_encode($payload),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
