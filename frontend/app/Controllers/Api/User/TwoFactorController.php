<?php

namespace App\Controllers\Api\User;

use App\Libraries\JWTLibrary;
use App\Libraries\ResponseLibrary;
use App\Models\UserModel;
use CodeIgniter\Controller;

/**
 * ─────────────────────────────────────────────
 *  2FA Toggle API
 * ─────────────────────────────────────────────
 *  POST /api/user/2fa
 *
 *  Header:
 *    Authorization: Bearer <token>
 *
 *  Body (JSON):
 *    { "enable": 1 }   → enable  2FA
 *    { "enable": 0 }   → disable 2FA
 * ─────────────────────────────────────────────
 */
class TwoFactorController extends Controller
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

    public function toggle(): \CodeIgniter\HTTP\Response
    {
        // ── 1. Authenticate ──────────────────────
        $authHeader = $this->request->getHeaderLine('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->respond->unauthorized('Token required.');
        }

        $token  = trim(substr($authHeader, 7));
        $result = $this->jwt->validate($token);

        if (!$result['valid']) {
            return $this->respond->unauthorized('Invalid token: ' . $result['error']);
        }

        $authUser = $this->users->findByToken($token);
        if (!$authUser) {
            return $this->respond->unauthorized('Token revoked or user not found.');
        }

        // ── 2. Read body ─────────────────────────
        $body   = $this->request->getJSON(true) ?? [];
        $enable = $body['enable'] ?? null;

        // ── 3. Validate value ────────────────────
        if (!isset($body['enable']) || !in_array((int) $enable, [0, 1], true)) {
            return $this->respond->error('Field "enable" is required and must be 0 or 1.', 422);
        }

        $enable = (int) $enable;

        // ── 4. Check already in same state ───────
        if ((int) $authUser['2FA'] === $enable) {
            $state = $enable ? 'already enabled' : 'already disabled';
            return $this->respond->error('2FA is ' . $state . '.', 409);
        }

        // ── 5. Update in DB ──────────────────────
        $this->users->update($authUser['id'], ['2FA' => $enable]);

        // ── 6. Return ────────────────────────────
        $message = $enable ? '2FA enabled successfully.' : '2FA disabled successfully.';

        return $this->respond->success([
            'user_id' => $authUser['id'],
            'email'   => $authUser['email'],
            '2FA'     => $enable,
        ], $message);
    }
}
