<?php

namespace App\Models;

use CodeIgniter\Model;

class GroupModel extends Model
{
    protected $table         = 'groups';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'name', 'description', 'avatar_path', 'created_by',
        'invite_link', 'type', 'max_members', 'is_active', 'settings',
    ];

    public function getUserGroups(int $userId): array
    {
        $db = \Config\Database::connect();
        return $db->query("
            SELECT g.*, gm.role as my_role, gm.is_muted,
                   (SELECT COUNT(*) FROM group_members WHERE group_id = g.id AND left_at IS NULL) as member_count,
                   (SELECT content FROM messages WHERE group_id = g.id ORDER BY created_at DESC LIMIT 1) as last_message,
                   (SELECT created_at FROM messages WHERE group_id = g.id ORDER BY created_at DESC LIMIT 1) as last_message_at
            FROM `groups` g
            INNER JOIN group_members gm ON gm.group_id = g.id AND gm.user_id = ? AND gm.left_at IS NULL
            WHERE g.deleted_at IS NULL AND g.is_active = 1
            ORDER BY last_message_at DESC
        ", [$userId])->getResultArray();
    }

    public function getGroupWithMembers(int $groupId): ?array
    {
        $group = $this->find($groupId);
        if (!$group) return null;

        $db = \Config\Database::connect();
        $group['members'] = $db->query("
            SELECT u.id, u.username, u.full_name, u.avatar_path, u.online_status, gm.role, gm.joined_at
            FROM group_members gm
            INNER JOIN users u ON u.id = gm.user_id
            WHERE gm.group_id = ? AND gm.left_at IS NULL
            ORDER BY FIELD(gm.role, 'owner', 'admin', 'member'), u.full_name
        ", [$groupId])->getResultArray();

        return $group;
    }

    public function isMember(int $groupId, int $userId): bool
    {
        $db = \Config\Database::connect();
        return (bool) $db->table('group_members')
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->where('left_at', null)
            ->countAllResults();
    }

    public function getMemberRole(int $groupId, int $userId): ?string
    {
        $db = \Config\Database::connect();
        $row = $db->table('group_members')
            ->select('role')
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->where('left_at', null)
            ->get()->getRowArray();
        return $row['role'] ?? null;
    }

    public function addMember(int $groupId, int $userId, string $role = 'member', int $addedBy = 0): void
    {
        $db = \Config\Database::connect();
        $db->table('group_members')->insert([
            'group_id'  => $groupId,
            'user_id'   => $userId,
            'role'      => $role,
            'added_by'  => $addedBy,
            'joined_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function removeMember(int $groupId, int $userId): void
    {
        $db = \Config\Database::connect();
        $db->table('group_members')
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->update(['left_at' => date('Y-m-d H:i:s')]);
    }

    public function getMemberCount(int $groupId): int
    {
        $db = \Config\Database::connect();
        return $db->table('group_members')
            ->where('group_id', $groupId)
            ->where('left_at', null)
            ->countAllResults();
    }
}
