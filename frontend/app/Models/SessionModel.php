<?php

namespace App\Models;

use CodeIgniter\Model;

class SessionModel extends Model
{
    protected $table         = 'sessions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'user_id', 'jti', 'refresh_token', 'device_id', 'device_name',
        'platform', 'ip_address', 'user_agent', 'is_active', 'expires_at',
        'last_used_at', 'created_at', 'revoked_at',
    ];

    public function createSession(array $data): int
    {
        $data['created_at']   = date('Y-m-d H:i:s');
        $data['last_used_at'] = date('Y-m-d H:i:s');
        $this->insert($data);
        return $this->db->insertID();
    }

    public function getActiveSession(int $userId, string $jti): ?array
    {
        return $this->where('user_id', $userId)
            ->where('jti', $jti)
            ->where('is_active', 1)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->first();
    }

    public function getByRefreshToken(string $refreshToken): ?array
    {
        return $this->where('refresh_token', hash('sha256', $refreshToken))
            ->where('is_active', 1)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->first();
    }

    public function revokeSession(string $jti): void
    {
        $this->where('jti', $jti)->set([
            'is_active'  => 0,
            'revoked_at' => date('Y-m-d H:i:s'),
        ])->update();
    }

    public function revokeAllUserSessions(int $userId, string $exceptJti = ''): void
    {
        $q = $this->where('user_id', $userId)->where('is_active', 1);
        if ($exceptJti) $q->where('jti !=', $exceptJti);
        $q->set(['is_active' => 0, 'revoked_at' => date('Y-m-d H:i:s')])->update();
    }

    public function getUserSessions(int $userId): array
    {
        return $this->select('id, device_id, device_name, platform, ip_address, last_used_at, created_at, is_active')
            ->where('user_id', $userId)
            ->orderBy('last_used_at', 'DESC')
            ->findAll();
    }

    public function touchSession(string $jti): void
    {
        $this->where('jti', $jti)->set(['last_used_at' => date('Y-m-d H:i:s')])->update();
    }
}
