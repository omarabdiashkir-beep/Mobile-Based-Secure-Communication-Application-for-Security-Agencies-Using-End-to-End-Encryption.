<?php

namespace App\Libraries;

/**
 * Lightweight JWT (HS256) — no external package needed.
 *
 * When JWT_ACCESS_TTL=0 the token has NO expiry (unlimited).
 */
class JWTLibrary
{
    private string $secret;

    public function __construct()
    {
        $this->secret = env('JWT_SECRET', 'CHANGE_THIS_TO_A_STRONG_SECRET_KEY_MIN32');
    }

    // ──────────────────────────────────────────────────
    // Generate a token
    // Pass ttl = 0  →  no exp claim  →  never expires
    // ──────────────────────────────────────────────────
    public function generate(array $payload, int $ttl = 0): string
    {
        $payload['iat'] = time();
        $payload['jti'] = bin2hex(random_bytes(16));

        if ($ttl > 0) {
            $payload['exp'] = time() + $ttl;
        }
        // ttl = 0  → no exp field → unlimited token

        $header    = $this->b64u(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $body      = $this->b64u(json_encode($payload));
        $signature = $this->b64u(hash_hmac('sha256', "$header.$body", $this->secret, true));

        return "$header.$body.$signature";
    }

    // ──────────────────────────────────────────────────
    // Validate & decode a token
    // Returns ['valid' => true, 'payload' => [...]]
    //      or ['valid' => false, 'error'   => '...']
    // ──────────────────────────────────────────────────
    public function validate(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return ['valid' => false, 'error' => 'Malformed token'];
        }

        [$header, $body, $sig] = $parts;

        // Verify signature
        $expected = $this->b64u(hash_hmac('sha256', "$header.$body", $this->secret, true));
        if (!hash_equals($expected, $sig)) {
            return ['valid' => false, 'error' => 'Invalid signature'];
        }

        $payload = json_decode(base64_decode(strtr($body, '-_', '+/')), true);
        if (!$payload) {
            return ['valid' => false, 'error' => 'Invalid payload'];
        }

        // Only check expiry when the claim EXISTS
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return ['valid' => false, 'error' => 'Token expired'];
        }

        return ['valid' => true, 'payload' => $payload];
    }

    // ──────────────────────────────────────────────────
    // Decode without validation (for reading payload only)
    // ──────────────────────────────────────────────────
    public function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;
        return json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
    }

    private function b64u(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
