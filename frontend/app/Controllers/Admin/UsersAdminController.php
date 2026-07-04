<?php

namespace App\Controllers\Admin;

class UsersAdminController extends BaseAdminController
{
    public function index()
    {
        if ($r = $this->requireAuth()) return $r;

        $db     = $this->db();
        $search = $this->request->getGet('search') ?? '';
        $status = $this->request->getGet('status') ?? '';
        $page   = max(1, (int)($this->request->getGet('page') ?? 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $builder = $db->table('users')
            ->select('users.*, roles.name as role_name')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->orderBy('users.id', 'DESC');

        if ($search) {
            $builder->groupStart()
                ->like('users.name', $search)
                ->orLike('users.username', $search)
                ->orLike('users.email', $search)
                ->groupEnd();
        }
        if ($status) $builder->where('users.status', $status);

        $total = $builder->countAllResults(false);
        $users = $builder->limit($limit, $offset)->get()->getResultArray();

        return view('admin/users/index', compact('users', 'total', 'page', 'limit', 'search', 'status'));
    }

    public function create()
    {
        if ($r = $this->requireAuth()) return $r;

        if (strtolower($this->request->getMethod()) === 'post') {
            $db       = $this->db();
            $name     = trim($this->request->getPost('name'));
            $username = trim($this->request->getPost('username'));
            $email    = trim($this->request->getPost('email'));
            $password = $this->request->getPost('password');
            $phone    = trim($this->request->getPost('phone') ?? '');
            $role_id  = (int)($this->request->getPost('role_id') ?? 2);

            $errors = [];
            if (!$name)     $errors[] = 'Name is required.';
            if (!$username) $errors[] = 'Username is required.';
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
            if (!$password || strlen($password) < 6) $errors[] = 'Password min 6 characters.';

            if ($db->table('users')->where('email', $email)->countAllResults()) $errors[] = 'Email already exists.';
            if ($db->table('users')->where('username', $username)->countAllResults()) $errors[] = 'Username already exists.';

            if (empty($errors)) {
                $db->table('users')->insert([
                    'role_id'    => $role_id,
                    'name'       => $name,
                    'username'   => $username,
                    'email'      => $email,
                    'phone'      => $phone ?: null,
                    'password'   => password_hash($password, PASSWORD_BCRYPT),
                    'status'     => 'active',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                return redirect()->to('/admin/users')->with('success', 'User created successfully.');
            }

            return redirect()->to('/admin/users')
                ->with('create_errors', $errors)
                ->with('create_input', ['name'=>$name,'username'=>$username,'email'=>$email,'phone'=>$phone,'role_id'=>$role_id]);
        }

        return redirect()->to('/admin/users');
    }

    public function show(int $id)
    {
        if ($r = $this->requireAuth()) return $r;

        $db   = $this->db();
        $user = $db->table('users')
            ->select('users.*, roles.name as role_name')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->where('users.id', $id)->get()->getRowArray();

        if (!$user) return redirect()->to('/admin/users')->with('error', 'User not found.');

        $user['photo_url'] = $user['photo'] ? base_url('uploads/' . $user['photo']) : null;

        $messages_sent = $db->table('messages')->where('sender_id', $id)->countAllResults();
        $messages_recv = $db->table('messages')->where('receiver_id', $id)->countAllResults();
        $groups        = $db->query("
            SELECT g.id, g.name, gm.role, gm.joined_at
            FROM group_members gm
            INNER JOIN `groups` g ON g.id = gm.group_id
            WHERE gm.user_id = ?
        ", [$id])->getResultArray();

        $activity = $db->table('api_logs')
            ->where('user_id', $id)
            ->orderBy('id', 'DESC')
            ->limit(20)->get()->getResultArray();

        $recent_messages = $db->query("
            SELECT m.*, u.name as receiver_name
            FROM messages m
            LEFT JOIN users u ON u.id = m.receiver_id
            WHERE m.sender_id = ?
            ORDER BY m.created_at DESC LIMIT 10
        ", [$id])->getResultArray();

        return view('admin/users/show', compact('user', 'messages_sent', 'messages_recv', 'groups', 'activity', 'recent_messages'));
    }

    public function suspend(int $id)
    {
        if ($r = $this->requireAuth()) return $r;
        $this->db()->table('users')->where('id', $id)->update(['status' => 'suspended', 'token' => null]);
        return redirect()->back()->with('success', 'User suspended.');
    }

    public function activate(int $id)
    {
        if ($r = $this->requireAuth()) return $r;
        $this->db()->table('users')->where('id', $id)->update(['status' => 'active']);
        return redirect()->back()->with('success', 'User activated.');
    }

    public function delete(int $id)
    {
        if ($r = $this->requireAuth()) return $r;
        $this->db()->table('users')->where('id', $id)->delete();
        return redirect()->to('/admin/users')->with('success', 'User deleted.');
    }

    public function resetPassword(int $id)
    {
        if ($r = $this->requireAuth()) return $r;
        $newPass = $this->request->getPost('new_password');
        if (!$newPass || strlen($newPass) < 6) return redirect()->back()->with('error', 'Min 6 characters.');
        $this->db()->table('users')->where('id', $id)->update([
            'password' => password_hash($newPass, PASSWORD_BCRYPT),
            'token'    => null,
        ]);
        return redirect()->back()->with('success', 'Password reset. User must login again.');
    }
}
