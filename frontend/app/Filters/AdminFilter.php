<?php

namespace App\Filters;

use App\Libraries\ResponseLibrary;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $response = new ResponseLibrary();

        $allowedRoles = $arguments ?? ['admin', 'super_admin'];

        if (!isset($request->user_role) || !in_array($request->user_role, $allowedRoles)) {
            return $response->forbidden('Admin access required');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
