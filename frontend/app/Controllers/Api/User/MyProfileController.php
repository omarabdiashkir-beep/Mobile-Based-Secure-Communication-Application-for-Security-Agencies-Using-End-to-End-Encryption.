<?php

namespace App\Controllers\Api\User;

use App\Libraries\JWTLibrary;
use App\Libraries\ResponseLibrary;
use App\Models\UserModel;
use CodeIgniter\Controller;

class MyProfileController extends Controller
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

    // GET /api/user/me
    public function me(): \CodeIgniter\HTTP\Response
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

        $db = \Config\Database::connect();

        $profile = $db->query("
            SELECT
                u.id,
                u.name,
                u.username,
                u.email,
                u.phone,
                u.bio,
                u.address,
                u.occupation,
                u.photo,
                u.status,
                u.`2FA`,
                u.is_online,
                u.last_seen,
                u.created_at,
                u.updated_at,
                r.name  AS role_name,
                r.slug  AS role_slug,
                (SELECT COUNT(*) FROM contacts WHERE user_id = u.id)      AS contacts_count,
                (SELECT COUNT(*) FROM blocked_users WHERE user_id = u.id) AS blocked_count,
                (SELECT COUNT(*) FROM messages WHERE sender_id = u.id)    AS messages_sent
            FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE u.id = ?
        ", [$user['id']])->getRowArray();

        if (!$profile) {
            return $this->respond->notFound('User not found.');
        }

        $profile['photo_url'] = $profile['photo']
            ? base_url('uploads/' . $profile['photo'])
            : null;

        $profile['2FA']         = (bool) $profile['2FA'];
        $profile['is_online']   = (bool) $profile['is_online'];
        $profile['contacts_count'] = (int) $profile['contacts_count'];
        $profile['blocked_count']  = (int) $profile['blocked_count'];
        $profile['messages_sent']  = (int) $profile['messages_sent'];

        return $this->respond->success($profile);
    }

    // GET /api/user/{id}
    public function show(int $id): \CodeIgniter\HTTP\Response
    {
        $header = $this->request->getHeaderLine('Authorization');
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return $this->respond->unauthorized('Token required.');
        }
        $token  = trim(substr($header, 7));
        $result = $this->jwt->validate($token);
        if (!$result['valid']) return $this->respond->unauthorized('Invalid token.');
        if (!$this->users->findByToken($token)) return $this->respond->unauthorized('Token revoked.');

        $db = \Config\Database::connect();

        $profile = $db->query("
            SELECT
                u.id, u.name, u.username, u.email, u.phone,
                u.bio, u.address, u.occupation, u.photo,
                u.status, u.is_online, u.last_seen, u.created_at,
                r.name AS role_name,
                (SELECT COUNT(*) FROM contacts WHERE user_id = u.id) AS contacts_count,
                (SELECT COUNT(*) FROM messages WHERE sender_id = u.id) AS messages_sent
            FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE u.id = ?
        ", [$id])->getRowArray();

        if (!$profile) return $this->respond->notFound('User not found.');

        $profile['photo_url'] = $profile['photo']
            ? base_url('uploads/' . $profile['photo'])
            : null;

        $profile['is_online']      = (bool) $profile['is_online'];
        $profile['contacts_count'] = (int)  $profile['contacts_count'];
        $profile['messages_sent']  = (int)  $profile['messages_sent'];

        return $this->respond->success($profile);
    }
}
