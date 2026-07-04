<?php

namespace App\Controllers\Api\User;

use App\Libraries\JWTLibrary;
use App\Libraries\ResponseLibrary;
use App\Models\UserModel;
use CodeIgniter\Controller;

/**
 * ─────────────────────────────────────────────
 *  Contacts API
 * ─────────────────────────────────────────────
 *  POST   /api/contacts/add          → add contact by phone
 *  GET    /api/contacts              → list my contacts
 *  GET    /api/contacts/{id}/profile → view contact profile + bio
 *  DELETE /api/contacts/{id}         → remove contact
 * ─────────────────────────────────────────────
 */
class ContactController extends Controller
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
    //  POST /api/contacts/add
    //  Body: { "phone": "+1234567890", "nickname": "optional" }
    //  Searches for a user with that phone number.
    //  If found → adds to contacts.
    // ═══════════════════════════════════════════
    public function add(): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        $body     = $this->request->getJSON(true) ?? [];
        $phone    = trim($body['phone']    ?? '');
        $nickname = trim($body['nickname'] ?? '');

        if (!$phone) {
            return $this->respond->error('phone is required.', 422);
        }

        // ── Find user by phone ───────────────────
        $db      = \Config\Database::connect();
        $contact = $db->table('users')
            ->where('phone', $phone)
            ->where('status', 'active')
            ->get()->getRowArray();

        if (!$contact) {
            return $this->respond->notFound('No user found with that phone number.');
        }

        if ((int)$contact['id'] === $authUser['id']) {
            return $this->respond->error('You cannot add yourself as a contact.', 422);
        }

        // ── Check if already added ───────────────
        $exists = $db->table('contacts')
            ->where('user_id', $authUser['id'])
            ->where('contact_user_id', $contact['id'])
            ->get()->getRowArray();

        if ($exists) {
            return $this->respond->error('This user is already in your contacts.', 409);
        }

        // ── Add contact ──────────────────────────
        $db->table('contacts')->insert([
            'user_id'         => $authUser['id'],
            'contact_user_id' => $contact['id'],
            'nickname'        => $nickname ?: null,
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        return $this->respond->success([
            'contact' => [
                'id'         => $contact['id'],
                'name'       => $contact['name'],
                'username'   => $contact['username'],
                'phone'      => $contact['phone'],
                'photo'      => $contact['photo'] ? base_url($contact['photo']) : null,
                'occupation' => $contact['occupation'],
                'is_online'  => (int)($contact['is_online'] ?? 0),
                'last_seen'  => $contact['last_seen'] ?? null,
                'nickname'   => $nickname ?: null,
            ],
        ], 'Contact added successfully.', 201);
    }

    // ═══════════════════════════════════════════
    //  GET /api/contacts
    //  Returns all my contacts with online status
    // ═══════════════════════════════════════════
    public function index(): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        $db = \Config\Database::connect();

        $contacts = $db->query("
            SELECT
                c.id           AS contact_id,
                c.nickname,
                c.created_at   AS added_at,
                u.id,
                u.name,
                u.username,
                u.phone,
                u.bio,
                u.photo,
                u.occupation,
                u.is_online,
                u.last_seen,
                u.status
            FROM contacts c
            INNER JOIN users u ON u.id = c.contact_user_id
            WHERE c.user_id = ?
            ORDER BY u.name ASC
        ", [$authUser['id']])->getResultArray();

        foreach ($contacts as &$c) {
            $c['photo'] = $c['photo'] ? base_url('uploads/' . $c['photo']) : null;
        }

        return $this->respond->success([
            'total'    => count($contacts),
            'contacts' => $contacts,
        ]);
    }

    // ═══════════════════════════════════════════
    //  GET /api/contacts/{id}/profile
    //  View a contact's full profile + bio
    //  Only works if this person is in your contacts
    // ═══════════════════════════════════════════
    public function profile(int $contactUserId): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        $db = \Config\Database::connect();

        // Must be in contacts to view profile
        $isContact = $db->table('contacts')
            ->where('user_id', $authUser['id'])
            ->where('contact_user_id', $contactUserId)
            ->get()->getRowArray();

        if (!$isContact) {
            return $this->respond->forbidden('This user is not in your contacts.');
        }

        $user = $db->table('users')
            ->select('id, name, username, bio, phone, address, photo, occupation, is_online, last_seen, status, created_at')
            ->where('id', $contactUserId)
            ->get()->getRowArray();

        if (!$user) {
            return $this->respond->notFound('User not found.');
        }

        $user['photo'] = $user['photo'] ? base_url('uploads/' . $user['photo']) : null;

        return $this->respond->success([
            'profile'  => $user,
            'nickname' => $isContact['nickname'],
            'added_at' => $isContact['created_at'],
        ]);
    }

    // ═══════════════════════════════════════════
    //  PUT /api/contacts/{id}
    //  Edit contact nickname
    //  Body: { "nickname": "John Work" }
    // ═══════════════════════════════════════════
    public function edit(int $contactUserId): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        $db = \Config\Database::connect();

        $contact = $db->table('contacts')
            ->where('user_id', $authUser['id'])
            ->where('contact_user_id', $contactUserId)
            ->get()->getRowArray();

        if (!$contact) {
            return $this->respond->notFound('Contact not found.');
        }

        $body     = $this->request->getJSON(true) ?? [];
        $nickname = trim($body['nickname'] ?? '');

        if (!$nickname) {
            return $this->respond->error('nickname is required.', 422);
        }

        $db->table('contacts')
            ->where('user_id', $authUser['id'])
            ->where('contact_user_id', $contactUserId)
            ->update([
                'nickname'   => $nickname,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return $this->respond->success([
            'contact_user_id' => $contactUserId,
            'nickname'        => $nickname,
        ], 'Contact name updated.');
    }

    // ═══════════════════════════════════════════
    //  DELETE /api/contacts/{id}
    //  Remove a contact
    // ═══════════════════════════════════════════
    public function remove(int $contactUserId): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        $db = \Config\Database::connect();

        $deleted = $db->table('contacts')
            ->where('user_id', $authUser['id'])
            ->where('contact_user_id', $contactUserId)
            ->delete();

        if (!$db->affectedRows()) {
            return $this->respond->notFound('Contact not found.');
        }

        return $this->respond->success(null, 'Contact removed.');
    }
}
