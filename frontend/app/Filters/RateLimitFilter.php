<?php

namespace App\Filters;

use App\Libraries\ResponseLibrary;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Rate Limit Filter
 * Uses CI4 throttler (backed by cache).
 * Configure cache in app/Config/Cache.php (Redis recommended for production).
 */
class RateLimitFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $response  = new ResponseLibrary();
        $throttler = \Config\Services::throttler();

        // arguments: ['60:1'] = 60 requests per 1 minute
        $limit    = 60;
        $interval = MINUTE;

        if (!empty($arguments[0])) {
            [$limit, $mins] = array_pad(explode(':', $arguments[0]), 2, 1);
            $interval = (int) $mins * MINUTE;
        }

        $identifier = $request->user_id ?? $request->getIPAddress();

        if (!$throttler->check(md5('rate_limit_' . $identifier), (int) $limit, $interval)) {
            $retryAfter = $throttler->getTokentime();
            return $response->error('Rate limit exceeded. Try again in ' . $retryAfter . ' seconds.', 429);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
