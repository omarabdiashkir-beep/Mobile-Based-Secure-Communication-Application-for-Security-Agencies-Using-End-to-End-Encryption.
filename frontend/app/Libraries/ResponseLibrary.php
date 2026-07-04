<?php

namespace App\Libraries;

/**
 * Standard JSON response builder.
 * Every API endpoint uses this for a consistent shape.
 */
class ResponseLibrary
{
    public function success($data = null, string $message = 'Success', int $code = 200): \CodeIgniter\HTTP\Response
    {
        return response()->setStatusCode($code)->setJSON([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ]);
    }

    public function error(string $message = 'Error', int $code = 400, $errors = null): \CodeIgniter\HTTP\Response
    {
        $body = ['status' => 'error', 'message' => $message];
        if ($errors !== null) $body['errors'] = $errors;
        return response()->setStatusCode($code)->setJSON($body);
    }

    public function unauthorized(string $message = 'Unauthorized'): \CodeIgniter\HTTP\Response
    {
        return $this->error($message, 401);
    }

    public function forbidden(string $message = 'Forbidden'): \CodeIgniter\HTTP\Response
    {
        return $this->error($message, 403);
    }

    public function notFound(string $message = 'Not found'): \CodeIgniter\HTTP\Response
    {
        return $this->error($message, 404);
    }
}
