<?php

namespace App\Controllers\Api\Auth;

use App\Libraries\JWTLibrary;
use App\Libraries\ResponseLibrary;
use App\Models\UserModel;
use CodeIgniter\Controller;

/**
 * ─────────────────────────────────────────────
 *  Change Password API
 * ─────────────────────────────────────────────
 *  POST /api/auth/change-password
 *
 *  Works for two cases:
 *   A) First-time login  — old_password is NOT required
 *      (must_change_password = true returned from login)
 *   B) Normal change     — old_password IS required
 *
 *  Body (JSON):
 *   {
 *     "old_password":     "Admin@1234",   // omit if first-time login
 *     "password":         "NewPass@123",
 *     "password_confirm": "NewPass@123"
 *   }
 *
 *  Header:
 *   Authorization: Bearer <token>
 * ─────────────────────────────────────────────
 */
class ChangePasswordController extends Controller
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

    public function change(): \CodeIgniter\HTTP\Response
    {
        // ── 1. Authenticate via token ────────────
        $header = $this->request->getHeaderLine('Authorization');
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return $this->respond->unauthorized('Token required.');
        }

        $token  = trim(substr($header, 7));
        $result = $this->jwt->validate($token);
        if (!$result['valid']) {
            return $this->respond->unauthorized('Invalid or expired token.');
        }

        $user = $this->users->find($result['payload']['user_id']);
        if (!$user || $user['status'] !== 'active') {
            return $this->respond->error('Account not found or inactive.', 403);
        }

        // ── 2. Parse body ────────────────────────
        $body            = $this->request->getJSON(true) ?? [];
        $oldPassword     = $body['old_password']     ?? '';
        $password        = $body['password']         ?? '';
        $passwordConfirm = $body['password_confirm'] ?? '';

        // ── 3. Validate new password ─────────────
        if (!$password || !$passwordConfirm) {
            return $this->respond->error('password and password_confirm are required.', 422);
        }

        if ($password !== $passwordConfirm) {
            return $this->respond->error('Passwords do not match.', 422);
        }

        if (strlen($password) < 8) {
            return $this->respond->error('Password must be at least 8 characters.', 422);
        }

        // ── 4. First-time vs normal change ───────
        $isFirstTime = ((int)$user['password_changed'] === 0);

        if (!$isFirstTime) {
            // Normal change — require old password
            if (!$oldPassword) {
                return $this->respond->error('old_password is required.', 422);
            }
            if (!password_verify($oldPassword, $user['password'])) {
                return $this->respond->error('Old password is incorrect.', 401);
            }
            if ($oldPassword === $password) {
                return $this->respond->error('New password must be different from old password.', 422);
            }
        }

        // ── 5. Save new password ─────────────────
        $now = date('Y-m-d H:i:s');

        $this->users->update($user['id'], [
            'password'              => password_hash($password, PASSWORD_BCRYPT),
            'password_changed'      => 1,
            'password_last_changed' => $now,
            'token'                 => null,   // force re-login with new password
        ]);

        return $this->respond->success([
            'must_change_password'  => false,
            'password_last_changed' => $now,
            'password_expire_date'  => date('Y-m-d H:i:s', strtotime($now) + (30 * 86400)),
        ], 'Password changed successfully. Please login again with your new password.');
    }
}
