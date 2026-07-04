<?php

namespace App\Controllers\Api\Group;

use App\Libraries\JWTLibrary;
use App\Libraries\ResponseLibrary;
use App\Models\GroupModel;
use App\Models\UserModel;
use CodeIgniter\Controller;

class GroupController extends Controller
{
    private GroupModel      $groups;
    private UserModel       $users;
    private ResponseLibrary $respond;
    private JWTLibrary      $jwt;

    public function __construct()
    {
        $this->groups  = new GroupModel();
        $this->users   = new UserModel();
        $this->respond = new ResponseLibrary();
        $this->jwt     = new JWTLibrary();
    }

    private function auth(): array|object
    {
        $header = $this->request->getHeaderLine('Authorization');
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return $this->respond->unauthorized('Token required.');
        }
        $token  = trim(substr($header, 7));
        $result = $this->jwt->validate($token);
        if (!$result['valid']) return $this->respond->unauthorized('Invalid token.');
        $user = $this->users->findByToken($token);
        if (!$user) return $this->respond->unauthorized('Token revoked.');
        return $user;
    }

    // POST /api/groups
    public function create(): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        $body = $this->request->getJSON(true) ?? [];
        $name = trim($body['name'] ?? '');

        if (!$name) {
            return $this->respond->error('Group name is required.', 422);
        }

        $db  = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        $db->table('groups')->insert([
            'name'        => $name,
            'description' => $body['description'] ?? null,
            'created_by'  => $authUser['id'],
            'type'        => $body['type'] ?? 'group',
            'is_active'   => 1,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);

        $groupId = $db->insertID();

        // Creator becomes admin
        $db->table('group_members')->insert([
            'group_id'  => $groupId,
            'user_id'   => $authUser['id'],
            'role'      => 'admin',
            'added_by'  => $authUser['id'],
            'joined_at' => $now,
        ]);

        // Add initial members if provided
        $memberIds = $body['member_ids'] ?? [];
        foreach ($memberIds as $memberId) {
            $memberId = (int) $memberId;
            if ($memberId === $authUser['id']) continue;
            if (!$this->users->find($memberId)) continue;
            $db->table('group_members')->insert([
                'group_id'  => $groupId,
                'user_id'   => $memberId,
                'role'      => 'member',
                'added_by'  => $authUser['id'],
                'joined_at' => $now,
            ]);
        }

        $group = $db->table('groups')->where('id', $groupId)->get()->getRowArray();
        $group['avatar_url'] = $group['avatar_path'] ? base_url('uploads/' . $group['avatar_path']) : null;

        return $this->respond->success($group, 'Group created.', 201);
    }

    // GET /api/groups
    public function myGroups(): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        $db = \Config\Database::connect();
        $groups = $db->query("
            SELECT g.id, g.name, g.description, g.avatar_path, g.type, g.created_by,
                   gm.role AS my_role,
                   (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) AS member_count,
                   (SELECT content FROM group_messages WHERE group_id = g.id ORDER BY created_at DESC LIMIT 1) AS last_message,
                   (SELECT created_at FROM group_messages WHERE group_id = g.id ORDER BY created_at DESC LIMIT 1) AS last_message_at
            FROM `groups` g
            INNER JOIN group_members gm ON gm.group_id = g.id AND gm.user_id = ?
            WHERE g.is_active = 1
            ORDER BY last_message_at DESC
        ", [$authUser['id']])->getResultArray();

        foreach ($groups as &$g) {
            $g['avatar_url'] = $g['avatar_path'] ? base_url('uploads/' . $g['avatar_path']) : null;
        }

        return $this->respond->success($groups);
    }

    // GET /api/groups/{id}
    public function show(int $groupId): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        $db    = \Config\Database::connect();
        $group = $db->table('groups')->where('id', $groupId)->where('is_active', 1)->get()->getRowArray();
        if (!$group) return $this->respond->notFound('Group not found.');

        $isMember = $db->table('group_members')
            ->where('group_id', $groupId)->where('user_id', $authUser['id'])
            ->countAllResults();
        if (!$isMember) return $this->respond->forbidden('You are not a member of this group.');

        $members = $db->query("
            SELECT u.id, u.name, u.username, u.photo, u.is_online, gm.role, gm.joined_at
            FROM group_members gm
            INNER JOIN users u ON u.id = gm.user_id
            WHERE gm.group_id = ?
            ORDER BY FIELD(gm.role, 'admin', 'member'), u.name
        ", [$groupId])->getResultArray();

        foreach ($members as &$m) {
            $m['photo_url'] = $m['photo'] ? base_url('uploads/' . $m['photo']) : null;
        }

        $group['avatar_url'] = $group['avatar_path'] ? base_url('uploads/' . $group['avatar_path']) : null;
        $group['members']    = $members;
        $group['member_count'] = count($members);

        return $this->respond->success($group);
    }

    // PUT /api/groups/{id}
    public function update(int $groupId): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        $db = \Config\Database::connect();
        $isAdmin = $db->table('group_members')
            ->where('group_id', $groupId)->where('user_id', $authUser['id'])->where('role', 'admin')
            ->countAllResults();
        if (!$isAdmin) return $this->respond->forbidden('Only admins can update the group.');

        $body = $this->request->getJSON(true) ?? [];
        $data = array_filter([
            'name'        => $body['name'] ?? null,
            'description' => $body['description'] ?? null,
        ]);

        if (empty($data)) return $this->respond->error('Nothing to update.', 422);

        $data['updated_at'] = date('Y-m-d H:i:s');
        $db->table('groups')->where('id', $groupId)->update($data);

        $group = $db->table('groups')->where('id', $groupId)->get()->getRowArray();
        return $this->respond->success($group, 'Group updated.');
    }

    // DELETE /api/groups/{id}
    public function delete(int $groupId): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        $db = \Config\Database::connect();
        $group = $db->table('groups')->where('id', $groupId)->get()->getRowArray();
        if (!$group) return $this->respond->notFound('Group not found.');

        if ((int)$group['created_by'] !== $authUser['id']) {
            return $this->respond->forbidden('Only the group creator can delete it.');
        }

        $db->table('groups')->where('id', $groupId)->update(['is_active' => 0, 'updated_at' => date('Y-m-d H:i:s')]);

        return $this->respond->success(null, 'Group deleted.');
    }

    // POST /api/groups/{id}/members
    public function addMember(int $groupId): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        $db = \Config\Database::connect();
        $isAdmin = $db->table('group_members')
            ->where('group_id', $groupId)->where('user_id', $authUser['id'])->where('role', 'admin')
            ->countAllResults();
        if (!$isAdmin) return $this->respond->forbidden('Only admins can add members.');

        $body     = $this->request->getJSON(true) ?? [];
        $userId   = (int)($body['user_id'] ?? 0);
        if (!$userId) return $this->respond->error('user_id is required.', 422);

        if (!$this->users->find($userId)) return $this->respond->notFound('User not found.');

        $already = $db->table('group_members')
            ->where('group_id', $groupId)->where('user_id', $userId)->countAllResults();
        if ($already) return $this->respond->error('User is already a member.', 422);

        $db->table('group_members')->insert([
            'group_id'  => $groupId,
            'user_id'   => $userId,
            'role'      => 'member',
            'added_by'  => $authUser['id'],
            'joined_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->respond->success(null, 'Member added.', 201);
    }

    // DELETE /api/groups/{id}/members/{user_id}
    public function removeMember(int $groupId, int $userId): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        $db = \Config\Database::connect();
        $isAdmin = $db->table('group_members')
            ->where('group_id', $groupId)->where('user_id', $authUser['id'])->where('role', 'admin')
            ->countAllResults();
        if (!$isAdmin) return $this->respond->forbidden('Only admins can remove members.');

        $db->table('group_members')->where('group_id', $groupId)->where('user_id', $userId)->delete();

        return $this->respond->success(null, 'Member removed.');
    }

    // POST /api/groups/{id}/leave
    public function leave(int $groupId): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        $db = \Config\Database::connect();
        $db->table('group_members')
            ->where('group_id', $groupId)->where('user_id', $authUser['id'])->delete();

        return $this->respond->success(null, 'You left the group.');
    }
}
