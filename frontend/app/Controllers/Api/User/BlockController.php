<?php

namespace App\Controllers\Api\User;

use App\Libraries\JWTLibrary;
use App\Libraries\ResponseLibrary;
use App\Models\UserModel;
use CodeIgniter\Controller;

/**
 * ─────────────────────────────────────────────
 *  Block / Unblock API
 * ─────────────────────────────────────────────
 *  POST   /api/user/block/{id}    → block a user
 *  POST   /api/user/unblock/{id}  → unblock a user
 *  GET    /api/user/blocked       → get all blocked users
 * ─────────────────────────────────────────────
 *  Rules:
 *  - Blocked user cannot send you messages
 *  - Blocked user cannot see your online status
 *  - Blocked user is removed from your contacts
 *  - You are removed from blocked user's contacts
 * ─────────────────────────────────────────────
 */
class BlockController extends Controller
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
    //  POST /api/user/block/{id}
    //  Block a user by their user ID
    // ═══════════════════════════════════════════
    public function block(int $targetId): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        if ($authUser['id'] === $targetId) {
            return $this->respond->error('You cannot block yourself.', 422);
        }

        $target = $this->users->find($targetId);
        if (!$target) {
            return $this->respond->notFound('User not found.');
        }

        $db = \Config\Database::connect();

        // ── Already blocked? ─────────────────────
        $exists = $db->table('blocked_users')
            ->where('user_id', $authUser['id'])
            ->where('blocked_user_id', $targetId)
            ->get()->getRowArray();

        if ($exists) {
            return $this->respond->error('You have already blocked this user.', 409);
        }

        // ── Block ────────────────────────────────
        $db->table('blocked_users')->insert([
            'user_id'         => $authUser['id'],
            'blocked_user_id' => $targetId,
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        // ── Remove from contacts (both sides) ────
        $db->table('contacts')
            ->groupStart()
                ->where('user_id', $authUser['id'])
                ->where('contact_user_id', $targetId)
            ->groupEnd()
            ->orGroupStart()
                ->where('user_id', $targetId)
                ->where('contact_user_id', $authUser['id'])
            ->groupEnd()
            ->delete();

        return $this->respond->success([
            'blocked_user' => [
                'id'       => $target['id'],
                'name'     => $target['name'],
                'username' => $target['username'],
            ],
        ], 'User blocked successfully.');
    }

    // ═══════════════════════════════════════════
    //  POST /api/user/unblock/{id}
    //  Unblock a previously blocked user
    // ═══════════════════════════════════════════
    public function unblock(int $targetId): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        $db = \Config\Database::connect();

        $block = $db->table('blocked_users')
            ->where('user_id', $authUser['id'])
            ->where('blocked_user_id', $targetId)
            ->get()->getRowArray();

        if (!$block) {
            return $this->respond->notFound('This user is not in your blocked list.');
        }

        $db->table('blocked_users')
            ->where('user_id', $authUser['id'])
            ->where('blocked_user_id', $targetId)
            ->delete();

        $target = $this->users->find($targetId);

        return $this->respond->success([
            'unblocked_user' => [
                'id'       => $target['id'],
                'name'     => $target['name'],
                'username' => $target['username'],
            ],
        ], 'User unblocked successfully.');
    }

    // ═══════════════════════════════════════════
    //  GET /api/user/blocked
    //  Returns all users I have blocked
    // ═══════════════════════════════════════════
    public function blocked(): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        $db = \Config\Database::connect();

        $blocked = $db->query("
            SELECT
                bu.id         AS block_id,
                bu.created_at AS blocked_at,
                u.id,
                u.name,
                u.username,
                u.phone,
                u.photo,
                u.occupation,
                u.status
            FROM blocked_users bu
            INNER JOIN users u ON u.id = bu.blocked_user_id
            WHERE bu.user_id = ?
            ORDER BY bu.created_at DESC
        ", [$authUser['id']])->getResultArray();

        foreach ($blocked as &$b) {
            $b['photo'] = $b['photo'] ? base_url('uploads/' . $b['photo']) : null;
        }

        return $this->respond->success([
            'total'   => count($blocked),
            'blocked' => $blocked,
        ]);
    }

    // GET /api/user/{id}/block-status
    public function blockStatus(int $targetId): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        $db = \Config\Database::connect();

        $iBlocked = (bool) $db->table('blocked_users')
            ->where('user_id', $authUser['id'])
            ->where('blocked_user_id', $targetId)
            ->countAllResults();

        $theyBlocked = (bool) $db->table('blocked_users')
            ->where('user_id', $targetId)
            ->where('blocked_user_id', $authUser['id'])
            ->countAllResults();

        $status = 'none';
        if ($iBlocked)    $status = 'you_blocked';
        if ($theyBlocked) $status = 'you_are_blocked';
        if ($iBlocked && $theyBlocked) $status = 'both_blocked';

        return $this->respond->success([
            'user_id'        => $targetId,
            'blocked_status' => $status,
            'you_blocked'    => $iBlocked,
            'they_blocked'   => $theyBlocked,
        ]);
    }
}
