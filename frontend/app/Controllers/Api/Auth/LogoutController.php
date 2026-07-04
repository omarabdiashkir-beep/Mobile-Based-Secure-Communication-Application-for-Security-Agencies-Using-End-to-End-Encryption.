<?php

namespace App\Controllers\Api\Auth;

use App\Libraries\JWTLibrary;
use App\Libraries\ResponseLibrary;
use App\Models\UserModel;
use CodeIgniter\Controller;

class LogoutController extends Controller
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

    // POST /api/auth/logout
    public function logout(): \CodeIgniter\HTTP\Response
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

        // Revoke token and set offline
        $this->users->update($user['id'], [
            'token'      => null,
            'is_online'  => 0,
            'last_seen'  => date('Y-m-d H:i:s'),
        ]);

        return $this->respond->success(null, 'Logged out successfully.');
    }
}
