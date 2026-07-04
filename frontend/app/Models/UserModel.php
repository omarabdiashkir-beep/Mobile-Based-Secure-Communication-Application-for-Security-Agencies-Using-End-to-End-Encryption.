<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table          = 'users';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;

    protected $allowedFields  = [
        'role_id', 'email', 'password', 'token',
        'name', 'username', 'bio', 'phone', 'address', 'photo', 'occupation', 'status', '2FA',
        'password_changed', 'password_last_changed',
        'is_online', 'last_seen',
    ];

    // Never return password or token in a result
    protected $hiddenFields = ['password'];

    // ────────────────────────────────────
    // Finders
    // ────────────────────────────────────

    public function findByEmail(string $email): ?array
    {
        // withDeleted = false, we still need password here so bypass hidden
        return $this->builder()
            ->select('users.id, users.role_id, users.email, users.password, users.token,
                      users.name, users.phone, users.address, users.photo, users.occupation,
                      users.status, users.`2FA`,
                      users.password_changed, users.password_last_changed,
                      users.created_at, users.updated_at,
                      roles.name as role_name, roles.slug as role_slug')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->where('users.email', $email)
            ->get()->getRowArray();
    }

    public function getProfile(int $id): ?array
    {
        return $this->builder()
            ->select('users.id, users.name, users.email, users.phone, users.address,
                      users.photo, users.occupation, users.status, users.created_at,
                      roles.name as role_name, roles.slug as role_slug')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->where('users.id', $id)
            ->get()->getRowArray();
    }

    public function saveToken(int $id, string $token): void
    {
        $this->update($id, ['token' => $token]);
    }

    public function clearToken(int $id): void
    {
        $this->update($id, ['token' => null]);
    }

    public function findByToken(string $token): ?array
    {
        return $this->builder()
            ->select('users.*, roles.name as role_name, roles.slug as role_slug')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->where('users.token', $token)
            ->where('users.status', 'active')
            ->get()->getRowArray();
    }
}
