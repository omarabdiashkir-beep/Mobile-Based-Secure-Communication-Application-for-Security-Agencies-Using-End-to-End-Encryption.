<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ApiLogFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // nothing before — we log after response is ready
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        try {
            $uri    = (string) $request->getUri();
            $method = $request->getMethod();

            // Only log /api/* routes
            if (!str_contains($uri, '/api/')) return $response;

            $db = \Config\Database::connect();

            // Extract token and user_id if present
            // Also stamp last_seen = now() so online status is activity-based
            $userId = null;
            $header = $request->getHeaderLine('Authorization');
            if ($header && str_starts_with($header, 'Bearer ')) {
                $token = trim(substr($header, 7));
                $user  = $db->table('users')->where('token', $token)->get()->getRowArray();
                if ($user) {
                    $userId = $user['id'];
                    // Stamp last_seen on every API call EXCEPT the offline endpoint
                    // (offline sets last_seen back 5 min to force immediate offline status)
                    $isOfflineCall = str_contains($uri, '/api/user/offline');
                    if (!$isOfflineCall) {
                        $db->table('users')->where('id', $userId)->update([
                            'last_seen' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }
            }

            // Request body (hide passwords)
            $body = [];
            $contentType = $request->getHeaderLine('Content-Type');
            if (str_contains($contentType, 'application/json')) {
                $body = $request->getJSON(true) ?? [];
            } elseif (str_contains($contentType, 'multipart/form-data')) {
                $body = $request->getPost() ?? [];
            }
            foreach (['password', 'old_password', 'new_password', 'password_confirm'] as $f) {
                if (isset($body[$f])) $body[$f] = '***';
            }

            // Response body (trim if large)
            $responseBody = (string) $response->getBody();
            if (strlen($responseBody) > 2000) {
                $responseBody = substr($responseBody, 0, 2000) . '...[truncated]';
            }

            $db->table('api_logs')->insert([
                'user_id'       => $userId,
                'method'        => strtoupper($method),
                'uri'           => $uri,
                'status_code'   => $response->getStatusCode(),
                'request_body'  => $body ? json_encode($body) : null,
                'response_body' => $responseBody,
                'ip_address'    => $request->getIPAddress(),
                'user_agent'    => $request->getUserAgent()->getAgentString(),
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Never break the API because of logging
        }

        return $response;
    }
}
