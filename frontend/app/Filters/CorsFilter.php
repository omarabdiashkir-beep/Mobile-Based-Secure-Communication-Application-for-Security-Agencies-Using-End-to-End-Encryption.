<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class CorsFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $allowedOrigins = explode(',', env('CORS_ALLOWED_ORIGINS', '*'));
        $origin         = $request->getHeaderLine('Origin');

        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Device-ID');
        header('Access-Control-Max-Age: 86400');

        if (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins)) {
            header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
            header('Access-Control-Allow-Credentials: true');
        } else {
            header('Access-Control-Allow-Origin: ' . $allowedOrigins[0]);
        }

        // Handle pre-flight
        if ($request->getMethod() === 'options') {
            return response()->setStatusCode(200)->setBody('');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
