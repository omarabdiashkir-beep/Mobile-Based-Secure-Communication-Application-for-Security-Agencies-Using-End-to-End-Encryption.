<?php

namespace App\Controllers\Api\Auth;

use App\Libraries\JWTLibrary;
use App\Libraries\ResponseLibrary;
use App\Models\UserModel;
use CodeIgniter\Controller;

/**
 * ─────────────────────────────────────────────
 *  Authentication API
 * ─────────────────────────────────────────────
 *  POST  /api/auth/login
 * ─────────────────────────────────────────────
 */
class AuthController extends Controller
{
    private UserModel       $users;
    private JWTLibrary      $jwt;
    private ResponseLibrary $respond;

    public function __construct()
    {
        $this->users   = new UserModel();
        $this->jwt     = new JWTLibrary();
        $this->respond = new ResponseLibrary();
    }

    // ═══════════════════════════════════════════
    //  POST /api/auth/login
    //
    //  Body (JSON):
    //    { "email": "...", "password": "..." }
    //
    //  Returns:
    //    token        — unlimited JWT (no expiry)
    //    user.id
    //    user.name
    //    user.email
    //    user.phone
    //    user.address
    //    user.photo
    //    user.occupation
    //    user.role_name   (Admin / User)
    //    user.role_slug   (admin / user)
    // ═══════════════════════════════════════════

    public function login(): \CodeIgniter\HTTP\Response
    {
        // ── 1. Parse JSON body ──────────────────
        $body = $this->request->getJSON(true) ?? [];

        $email    = trim($body['email']    ?? '');
        $password = trim($body['password'] ?? '');

        // ── 2. Basic validation ─────────────────
        if (!$email || !$password) {
            return $this->respond->error('Email and password are required.', 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->respond->error('Invalid email format.', 422);
        }

        // ── 3. Find user ────────────────────────
        $user = $this->users->findByEmail($email);

        if (!$user) {
            return $this->respond->error('Invalid credentials.', 401);
        }

        // ── 4. Verify password ──────────────────
        if (!password_verify($password, $user['password'])) {
            return $this->respond->error('Invalid credentials.', 401);
        }

        // ── 5. Check account status ─────────────
        if ($user['status'] === 'suspended') {
            return response()->setStatusCode(403)->setJSON([
                'status'         => 'error',
                'account_status' => 'suspended',
                'message'        => 'Your account has been suspended. Please contact support.',
            ]);
        }

        if ($user['status'] !== 'active') {
            return response()->setStatusCode(403)->setJSON([
                'status'         => 'error',
                'account_status' => $user['status'],
                'message'        => 'Your account is ' . $user['status'] . '. Please contact support.',
            ]);
        }

        // ── 6. Check password policy ────────────
        // Flag A: first login — password never changed
        $mustChangePassword = ((int)$user['password_changed'] === 0);

        // Flag B: password expiry date (last changed + 30 days)
        $passwordExpiredDate = null;
        $passwordExpired     = false;
        if ($user['password_last_changed']) {
            $expireTimestamp     = strtotime($user['password_last_changed']) + (30 * 86400);
            $passwordExpiredDate = date('Y-m-d H:i:s', $expireTimestamp);
            if (time() >= $expireTimestamp) {
                $passwordExpired = true;
            }
        }

        // ── 7. Generate UNLIMITED token (ttl=0) ─
        $token = $this->jwt->generate([
            'user_id'   => $user['id'],
            'email'     => $user['email'],
            'role'      => $user['role_slug'],
        ], 0); // 0 = no expiry

        // ── 8. Persist token in DB ──────────────
        $this->users->saveToken($user['id'], $token);

        // ── 9. Build clean user profile ─────────
        $profile = [
            'id'         => $user['id'],
            'name'       => $user['name'],
            'email'      => $user['email'],
            'phone'      => $user['phone'],
            'address'    => $user['address'],
            'photo'      => $user['photo'] ? base_url($user['photo']) : null,
            'occupation' => $user['occupation'],
            'role_name'  => $user['role_name'],
            'role_slug'  => $user['role_slug'],
            'status'                 => $user['status'],
            '2FA'                    => (int) ($user['2FA'] ?? 0),
            'password_changed'       => (int) ($user['password_changed'] ?? 0),
            'password_last_changed'  => $user['password_last_changed'],
        ];

        // ── 10. Return ───────────────────────────
        return $this->respond->success([
            'token'                  => $token,
            'token_type'             => 'Bearer',
            'must_change_password'   => $mustChangePassword,     // true = first login, force change
            'password_expired'       => $passwordExpired,        // true = 30 days passed, force change
            'password_expire_date'   => $passwordExpiredDate,    // date password expires (last_changed + 30 days)
            'user'                   => $profile,
        ], 'Login successful.');
    }
}
