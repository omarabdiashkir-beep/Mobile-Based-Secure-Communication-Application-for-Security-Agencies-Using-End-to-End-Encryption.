<?php

namespace App\Filters;

use App\Libraries\JWTLibrary;
use App\Libraries\ResponseLibrary;
use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * JWT Auth Filter
 * Add to any route that requires a logged-in user.
 *
 * Header expected:
 *   Authorization: Bearer <token>
 */
class JWTFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $jwt      = new JWTLibrary();
        $respond  = new ResponseLibrary();
        $users    = new UserModel();

        // ── 1. Extract token ────────────────────
        $header = $request->getHeaderLine('Authorization');

        if (empty($header) || !str_starts_with($header, 'Bearer ')) {
            return $respond->unauthorized('Token missing. Use: Authorization: Bearer <token>');
        }

        $token = trim(substr($header, 7));

        // ── 2. Validate JWT signature & expiry ──
        $result = $jwt->validate($token);

        if (!$result['valid']) {
            return $respond->unauthorized('Invalid token: ' . $result['error']);
        }

        $payload = $result['payload'];

        // ── 3. Check token still exists in DB ───
        //      (logout / revocation support)
        $user = $users->findByToken($token);

        if (!$user) {
            return $respond->unauthorized('Token revoked or user not found.');
        }

        // ── 4. Inject user into request ─────────
        $request->user_id   = $user['id'];
        $request->user_role = $user['role_slug'];
        $request->user      = $user;

        return null; // continue to controller
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
