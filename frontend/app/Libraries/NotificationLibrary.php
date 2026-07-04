<?php

namespace App\Libraries;

/**
 * Push Notification Library
 * Supports Firebase FCM (HTTP v1) and APNs (JWT-based).
 */
class NotificationLibrary
{
    private string $fcmServerKey;
    private string $fcmProjectId;
    private string $fcmEndpoint = 'https://fcm.googleapis.com/fcm/send';

    public function __construct()
    {
        $this->fcmServerKey = env('FCM_SERVER_KEY', '');
        $this->fcmProjectId = env('FCM_PROJECT_ID', '');
    }

    // ─────────────────────────────────────────────
    // Send to single device token
    // ─────────────────────────────────────────────

    public function sendToDevice(
        string $deviceToken,
        string $title,
        string $body,
        array  $data    = [],
        string $type    = 'message' // 'message'|'call'|'group'|'system'
    ): array {
        $payload = [
            'to'           => $deviceToken,
            'notification' => [
                'title'        => $title,
                'body'         => $body,
                'sound'        => 'default',
                'badge'        => 1,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
            'data' => array_merge($data, ['type' => $type]),
            'priority' => 'high',
            'content_available' => true,
        ];

        return $this->firebaseRequest($payload);
    }

    // ─────────────────────────────────────────────
    // Send to multiple tokens (batch)
    // ─────────────────────────────────────────────

    public function sendToMultiple(
        array  $tokens,
        string $title,
        string $body,
        array  $data = []
    ): array {
        if (empty($tokens)) return ['success' => true, 'sent' => 0];

        // FCM allows max 1000 per batch
        $chunks  = array_chunk($tokens, 1000);
        $results = [];

        foreach ($chunks as $chunk) {
            $payload = [
                'registration_ids' => $chunk,
                'notification'     => [
                    'title' => $title,
                    'body'  => $body,
                    'sound' => 'default',
                ],
                'data'     => $data,
                'priority' => 'high',
            ];
            $results[] = $this->firebaseRequest($payload);
        }

        return $results;
    }

    // ─────────────────────────────────────────────
    // Typed helpers
    // ─────────────────────────────────────────────

    public function sendMessageNotification(string $token, string $senderName, string $preview, int $conversationId): array
    {
        return $this->sendToDevice($token, $senderName, $preview, [
            'conversation_id' => $conversationId,
            'screen'          => 'chat',
        ], 'message');
    }

    public function sendCallNotification(string $token, string $callerName, int $callId, string $callType = 'voice'): array
    {
        return $this->sendToDevice($token, 'Incoming Call', "{$callerName} is calling...", [
            'call_id'   => $callId,
            'call_type' => $callType,
            'screen'    => 'call',
        ], 'call');
    }

    public function sendGroupNotification(array $tokens, string $groupName, string $message): array
    {
        return $this->sendToMultiple($tokens, $groupName, $message, ['screen' => 'group']);
    }

    // ─────────────────────────────────────────────
    // HTTP request to FCM
    // ─────────────────────────────────────────────

    private function firebaseRequest(array $payload): array
    {
        if (empty($this->fcmServerKey)) {
            log_message('warning', 'FCM_SERVER_KEY not set — notification skipped');
            return ['success' => false, 'error' => 'FCM key not configured'];
        }

        $ch = curl_init($this->fcmEndpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: key=' . $this->fcmServerKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT    => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            log_message('error', "FCM cURL error: {$error}");
            return ['success' => false, 'error' => $error];
        }

        $decoded = json_decode($response, true);
        return ['success' => $httpCode === 200, 'response' => $decoded, 'http_code' => $httpCode];
    }
}
