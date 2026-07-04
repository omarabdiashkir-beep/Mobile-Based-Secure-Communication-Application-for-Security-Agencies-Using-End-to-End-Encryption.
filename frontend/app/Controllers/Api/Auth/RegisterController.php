<?php

namespace App\Controllers\Api\Auth;

use App\Libraries\JWTLibrary;
use App\Libraries\ResponseLibrary;
use App\Models\UserModel;
use CodeIgniter\Controller;

/**
 * ─────────────────────────────────────────────
 *  Register API
 * ─────────────────────────────────────────────
 *  POST /api/auth/register
 *
 *  Body (JSON):
 *    name        required
 *    email       required, unique
 *    password    required, min 8 chars
 *    phone       optional
 *    address     optional
 *    occupation  optional
 * ─────────────────────────────────────────────
 */
class RegisterController extends Controller
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

    public function register(): \CodeIgniter\HTTP\Response
    {
        // ── 1. Parse body ───────────────────────
        $body = $this->request->getJSON(true) ?? [];

        $name       = trim($body['name']       ?? '');
        $email      = strtolower(trim($body['email']      ?? ''));
        $password   = trim($body['password']   ?? '');
        $phone      = trim($body['phone']      ?? '');
        $address    = trim($body['address']    ?? '');
        $occupation = trim($body['occupation'] ?? '');

        // ── 2. Validation ───────────────────────
        $errors = [];

        if (!$name) {
            $errors['name'] = 'Name is required.';
        }

        if (!$email) {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format.';
        } elseif ($this->users->findByEmail($email)) {
            $errors['email'] = 'Email is already registered.';
        }

        if (!$password) {
            $errors['password'] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors['password'] = 'Password must contain at least one uppercase letter.';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors['password'] = 'Password must contain at least one number.';
        }

        if ($errors) {
            return $this->respond->error('Validation failed.', 422, $errors);
        }

        // ── 3. Hash password (ARGON2ID) ─────────
        $hashed = password_hash($password, PASSWORD_ARGON2ID);

        // ── 4. Get default role (User = role_id 2) ──
        $db     = \Config\Database::connect();
        $role   = $db->table('roles')->where('slug', 'user')->get()->getRowArray();
        $roleId = $role ? $role['id'] : 2;

        // ── 5. Insert user ───────────────────────
        $userId = $this->users->insert([
            'role_id'               => $roleId,
            'name'                  => $name,
            'email'                 => $email,
            'password'              => $hashed,
            'phone'                 => $phone    ?: null,
            'address'               => $address  ?: null,
            'occupation'            => $occupation ?: null,
            'status'                => 'active',
            '2FA'                   => 0,
            'password_changed'      => 0,          // force change on first login
            'password_last_changed' => null,
        ]);

        if (!$userId) {
            return $this->respond->error('Registration failed. Please try again.', 500);
        }

        // ── 6. Generate token ────────────────────
        $token = $this->jwt->generate([
            'user_id' => (string) $userId,
            'email'   => $email,
            'role'    => 'user',
        ], 0);

        // ── 7. Save token in DB ──────────────────
        $this->users->saveToken($userId, $token);

        // ── 8. Fetch full user record ────────────
        $user = $this->users->findByToken($token);

        // ── 9. Return ────────────────────────────
        return $this->respond->success([
            'token'                => $token,
            'token_type'           => 'Bearer',
            'must_change_password' => true,   // always true on first register
            'user' => [
                'id'         => $user['id'],
                'name'       => $user['name'],
                'email'      => $user['email'],
                'phone'      => $user['phone'],
                'address'    => $user['address'],
                'photo'      => null,
                'occupation' => $user['occupation'],
                'role_name'  => $user['role_name'] ?? 'User',
                'role_slug'  => $user['role_slug'] ?? 'user',
                'status'     => $user['status'],
                '2FA'        => 0,
            ],
        ], 'Registration successful.', 201);
    }
}
