<?php

namespace App\Controllers\Admin;

class GroupsAdminController extends BaseAdminController
{
    public function index()
    {
        if ($r = $this->requireAuth()) return $r;

        $db     = $this->db();
        $search = $this->request->getGet('search') ?? '';
        $page   = max(1, (int)($this->request->getGet('page') ?? 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $builder = $db->table('`groups`')
            ->select('`groups`.*, users.name as creator_name, (SELECT COUNT(*) FROM group_members WHERE group_id = `groups`.id) as member_count')
            ->join('users', 'users.id = `groups`.created_by', 'left')
            ->orderBy('`groups`.id', 'DESC');

        if ($search) $builder->like('`groups`.name', $search);

        $total  = $builder->countAllResults(false);
        $groups = $builder->limit($limit, $offset)->get()->getResultArray();
        $users  = $this->allUsers();

        return view('admin/groups/index', compact('groups', 'total', 'page', 'limit', 'search', 'users'));
    }

    public function show(int $id)
    {
        if ($r = $this->requireAuth()) return $r;

        $db    = $this->db();
        $group = $db->query("
            SELECT g.*, u.name as creator_name
            FROM `groups` g
            LEFT JOIN users u ON u.id = g.created_by
            WHERE g.id = ?
        ", [$id])->getRowArray();

        if (!$group) return redirect()->to('/admin/groups')->with('error', 'Group not found.');

        $members = $db->query("
            SELECT gm.*, u.name, u.username, u.photo, u.is_online, u.status
            FROM group_members gm
            INNER JOIN users u ON u.id = gm.user_id
            WHERE gm.group_id = ?
            ORDER BY gm.role DESC, gm.joined_at ASC
        ", [$id])->getResultArray();

        $messages = $db->query("
            SELECT gm.*, u.name as sender_name
            FROM group_messages gm
            LEFT JOIN users u ON u.id = gm.sender_id
            WHERE gm.group_id = ?
            ORDER BY gm.created_at DESC LIMIT 20
        ", [$id])->getResultArray();

        return view('admin/groups/show', compact('group', 'members', 'messages'));
    }

    public function create()
    {
        if ($r = $this->requireAuth()) return $r;

        $db = $this->db();

        if (strtolower($this->request->getMethod()) === 'post') {
            $name        = trim($this->request->getPost('name'));
            $description = trim($this->request->getPost('description') ?? '');
            $created_by  = (int)($this->request->getPost('created_by') ?? 0);

            if (!$name) {
                return redirect()->to('/admin/groups')->with('create_error', 'Group name is required.');
            }
            if (!$created_by) {
                return redirect()->to('/admin/groups')->with('create_error', 'Please select a group owner.');
            }

            $db->table('`groups`')->insert([
                'name'        => $name,
                'description' => $description ?: null,
                'created_by'  => $created_by,
                'is_active'   => 1,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
            $groupId = $db->insertID();

            // Add owner as group admin member
            $db->table('group_members')->insert([
                'group_id'  => $groupId,
                'user_id'   => $created_by,
                'role'      => 'admin',
                'joined_at' => date('Y-m-d H:i:s'),
            ]);

            return redirect()->to('/admin/groups/' . $groupId)->with('success', 'Group created.');
        }

        return redirect()->to('/admin/groups');
    }

    public function addMember(int $id)
    {
        if ($r = $this->requireAuth()) return $r;
        $db     = $this->db();
        $userId = (int)$this->request->getPost('user_id');

        $exists = $db->table('group_members')->where('group_id', $id)->where('user_id', $userId)->countAllResults();
        if (!$exists) {
            $db->table('group_members')->insert([
                'group_id'  => $id,
                'user_id'   => $userId,
                'role'      => 'member',
                'joined_at' => date('Y-m-d H:i:s'),
            ]);
        }
        return redirect()->to('/admin/groups/' . $id)->with('success', 'Member added.');
    }

    public function removeMember(int $id, int $userId)
    {
        if ($r = $this->requireAuth()) return $r;
        $this->db()->table('group_members')->where('group_id', $id)->where('user_id', $userId)->delete();
        return redirect()->to('/admin/groups/' . $id)->with('success', 'Member removed.');
    }

    public function delete(int $id)
    {
        if ($r = $this->requireAuth()) return $r;
        $db = $this->db();
        $db->table('group_members')->where('group_id', $id)->delete();
        $db->table('group_messages')->where('group_id', $id)->delete();
        $db->table('`groups`')->where('id', $id)->delete();
        return redirect()->to('/admin/groups')->with('success', 'Group deleted.');
    }

    private function allUsers(): array
    {
        return $this->db()->table('users')->select('id, name, username')->orderBy('name')->get()->getResultArray();
    }
}
