<?php

namespace App\Controllers\Api\User;

use App\Libraries\JWTLibrary;
use App\Libraries\ResponseLibrary;
use App\Models\UserModel;
use CodeIgniter\Controller;

/**
 * ─────────────────────────────────────────────
 *  Online / Offline Status API
 * ─────────────────────────────────────────────
 *  POST /api/user/online        → set me online  (call on app open)
 *  POST /api/user/offline       → set me offline (call on app close)
 *  GET  /api/user/{id}/status   → get any user's online status + last seen
 * ─────────────────────────────────────────────
 */
class OnlineStatusController extends Controller
{
    private UserModel       $users;
    private ResponseLibrary $respond;
    private JWTLibrary      $jwt;

    public function __construct()
    {
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
        if (!$result['valid']) {
            return $this->respond->unauthorized('Invalid token.');
        }
        $user = $this->users->findByToken($token);
        if (!$user) {
            return $this->respond->unauthorized('Token revoked.');
        }
        return $user;
    }

    // ═══════════════════════════════════════════
    //  POST /api/user/online  (kept for compatibility — no-op)
    //  Online status is now automatic: any API call = online
    // ═══════════════════════════════════════════
    public function online(): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        $now = date('Y-m-d H:i:s');
        return $this->respond->success([
            'is_online' => true,
            'last_seen' => $now,
        ], 'You are now online.');
    }

    // ═══════════════════════════════════════════
    //  POST /api/user/offline  (kept for compatibility — no-op)
    //  Offline is now automatic: no API activity for 3 min = offline
    // ═══════════════════════════════════════════
    public function offline(): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        // Push last_seen back 2 minutes so the 1-min threshold immediately shows offline
        $lastSeen = date('Y-m-d H:i:s', time() - 120);
        \Config\Database::connect()
            ->table('users')
            ->where('id', $authUser['id'])
            ->update(['last_seen' => $lastSeen]);

        return $this->respond->success([
            'is_online' => false,
            'last_seen' => $lastSeen,
        ], 'You are now offline.');
    }

    // ═══════════════════════════════════════════
    //  GET /api/user/{id}/status
    //  Get any user's online status and last seen
    //  Anyone with a token can check this
    // ═══════════════════════════════════════════
    public function status(int $userId): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        $db   = \Config\Database::connect();
        $user = $db->table('users')
            ->select('id, name, username, photo, last_seen')
            ->where('id', $userId)
            ->get()->getRowArray();

        if (!$user) {
            return $this->respond->notFound('User not found.');
        }

        // Online = last API activity within the past 3 minutes
        $isOnline = false;
        $lastSeenText = null;
        if ($user['last_seen']) {
            $diff     = time() - strtotime($user['last_seen']);
            $isOnline = $diff < 60; // 1 minute
            if ($isOnline) {
                $lastSeenText = 'online';
            } elseif ($diff < 3600) {
                $lastSeenText = 'last seen ' . floor($diff / 60) . ' minutes ago';
            } elseif ($diff < 86400) {
                $lastSeenText = 'last seen ' . floor($diff / 3600) . ' hours ago';
            } else {
                $lastSeenText = 'last seen ' . date('M j', strtotime($user['last_seen']));
            }
        }

        return $this->respond->success([
            'id'             => $user['id'],
            'name'           => $user['name'],
            'username'       => $user['username'],
            'photo'          => $user['photo'] ? base_url('uploads/' . $user['photo']) : null,
            'is_online'      => $isOnline,
            'last_seen'      => $user['last_seen'],
            'last_seen_text' => $lastSeenText ?? 'a long time ago',
        ]);
    }
}
