<?php

namespace App\Controllers\Api\Message;

use App\Libraries\JWTLibrary;
use App\Libraries\ResponseLibrary;
use App\Models\MessageModel;
use App\Models\UserModel;
use CodeIgniter\Controller;

/**
 * ═══════════════════════════════════════════════
 *  Messaging API  (1-to-1)
 * ═══════════════════════════════════════════════
 *
 *  POST   /api/messages/send              → send message (text or file)
 *  GET    /api/messages/{receiver_id}     → get conversation (auto-marks delivered)
 *  GET    /api/messages/inbox             → inbox (last msg per contact + online)
 *  DELETE /api/messages/{id}              → delete message
 *                                           body: {"delete_for":"me"|"all"}
 *  POST   /api/messages/{id}/react        → add/change reaction
 *  DELETE /api/messages/{id}/react        → remove reaction
 *  POST   /api/messages/read              → mark messages as read (✓✓)
 *  POST   /api/messages/delivered         → mark messages as delivered (✓)
 *  GET    /api/messages/unread-count      → total unread count
 *  POST   /api/messages/{id}/reply        → reply to message
 *  GET    /api/messages/{id}/reactions    → get reactions
 *  GET    /api/messages/{user_id}/status  → get user online/offline status
 *  GET    /api/messages/{user_id}/images|videos|voices|audio|documents|replies
 */
class MessageController extends Controller
{
    private MessageModel    $messages;
    private UserModel       $users;
    private ResponseLibrary $respond;
    private JWTLibrary      $jwt;

    private array $allowed = [
        'image'    => ['image/jpeg','image/png','image/webp','image/gif'],
        'video'    => ['video/mp4','video/quicktime','video/x-msvideo','video/webm'],
        'voice'    => ['audio/mpeg','audio/mp4','audio/ogg','audio/wav','audio/webm','audio/aac','audio/x-m4a','audio/m4a','video/mp4','video/webm'],
        'document' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'application/zip',
        ],
    ];

    private array $maxSize = [
        'image'    => 10  * 1024 * 1024,
        'video'    => 100 * 1024 * 1024,
        'voice'    => 20  * 1024 * 1024,
        'document' => 50  * 1024 * 1024,
    ];

    public function __construct()
    {
        $this->messages = new MessageModel();
        $this->users    = new UserModel();
        $this->respond  = new ResponseLibrary();
        $this->jwt      = new JWTLibrary();
    }

    // ─────────────────────────────────────────
    // Auth helper
    // ─────────────────────────────────────────
    private function auth(): array|object
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

        return $user;
    }

    // ═══════════════════════════════════════════
    //  POST /api/messages/send
    // ═══════════════════════════════════════════
    public function send(): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        $senderId    = $authUser['id'];
        $contentType = $this->request->getHeaderLine('Content-Type');
        $isMultipart = str_contains($contentType, 'multipart/form-data');

        if ($isMultipart) {
            $receiverId = (int) $this->request->getPost('receiver_id');
            $type       = trim($this->request->getPost('type') ?? 'text');
            $content    = trim($this->request->getPost('content') ?? '');
            $replyTo    = $this->request->getPost('reply_to_id') ?: null;
        } else {
            $body       = $this->request->getJSON(true) ?? [];
            $receiverId = (int) ($body['receiver_id'] ?? 0);
            $type       = 'text';
            $content    = trim($body['content'] ?? '');
            $replyTo    = $body['reply_to_id'] ?? null;
        }

        if (!$receiverId) return $this->respond->error('receiver_id is required.', 422);
        if ($receiverId === $senderId) return $this->respond->error('Cannot send message to yourself.', 422);

        $receiver = $this->users->find($receiverId);
        if (!$receiver) return $this->respond->notFound('Receiver not found.');

        $db = \Config\Database::connect();

        $iBlocked = $db->table('blocked_users')
            ->where('user_id', $senderId)->where('blocked_user_id', $receiverId)
            ->countAllResults();
        if ($iBlocked) {
            return response()->setStatusCode(403)->setJSON([
                'status' => 'error', 'blocked_status' => 'you_blocked',
                'message' => 'You have blocked this user. Unblock them to send messages.',
            ]);
        }

        $theyBlocked = $db->table('blocked_users')
            ->where('user_id', $receiverId)->where('blocked_user_id', $senderId)
            ->countAllResults();
        if ($theyBlocked) {
            return response()->setStatusCode(403)->setJSON([
                'status' => 'error', 'blocked_status' => 'you_are_blocked',
                'message' => 'You have been blocked by this user.',
            ]);
        }

        $validTypes = ['text', 'image', 'video', 'voice', 'document'];
        if (!in_array($type, $validTypes)) {
            return $this->respond->error('Invalid type. Use: text, image, video, voice, document.', 422);
        }

        if ($type === 'text') {
            if (!$content) return $this->respond->error('content is required for text messages.', 422);

            $id = $this->messages->insert([
                'sender_id'   => $senderId,
                'receiver_id' => $receiverId,
                'type'        => 'text',
                'content'     => $content,
                'reply_to_id' => $replyTo,
            ]);

            $this->createStatus($id, $receiverId);
            return $this->respond->success($this->buildMessage($id), 'Message sent.', 201);
        }

        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid()) return $this->respond->error('No valid file uploaded.', 422);

        $mime = $file->getMimeType();
        if (!in_array($mime, $this->allowed[$type])) {
            return $this->respond->error("Invalid file type for {$type}. Got: {$mime}", 422);
        }
        if ($file->getSize() > $this->maxSize[$type]) {
            return $this->respond->error('File too large. Max ' . ($this->maxSize[$type] / 1048576) . 'MB for ' . $type . '.', 422);
        }

        $folder  = ROOTPATH . "uploads/{$type}s/";
        if (!is_dir($folder)) mkdir($folder, 0755, true);

        $newName      = bin2hex(random_bytes(16)) . '.' . $file->getExtension();
        $file->move($folder, $newName);
        $relativePath = "{$type}s/{$newName}";
        $fileUrl      = base_url("uploads/{$relativePath}");

        $id = $this->messages->insert([
            'sender_id'   => $senderId,
            'receiver_id' => $receiverId,
            'type'        => $type,
            'content'     => $content ?: null,
            'file_path'   => $relativePath,
            'file_name'   => $file->getClientName(),
            'file_size'   => $file->getSize(),
            'file_mime'   => $mime,
            'file_url'    => $fileUrl,
            'reply_to_id' => $replyTo,
        ]);

        $this->createStatus($id, $receiverId);
        return $this->respond->success($this->buildMessage($id), ucfirst($type) . ' sent.', 201);
    }

    // ═══════════════════════════════════════════
    //  GET /api/messages/{receiver_id}
    //  Auto-marks received messages as delivered.
    //  Response includes receiver online/offline status.
    // ═══════════════════════════════════════════
    public function conversation(int $receiverId): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        $myId   = $authUser['id'];
        $page   = max(1, (int) ($this->request->getGet('page')  ?? 1));
        $limit  = min(100, (int) ($this->request->getGet('limit') ?? 50));
        $offset = ($page - 1) * $limit;

        // Auto-mark messages sent TO me as delivered
        $this->markAsDeliveredInternal($receiverId, $myId);

        $msgs = $this->messages->getConversation($myId, $receiverId, $limit, $offset);

        foreach ($msgs as &$msg) {
            $msg['reactions'] = $msg['reactions'] ? json_decode($msg['reactions'], true) : [];
            if ($msg['file_path']) {
                $msg['file_url'] = base_url('uploads/' . $msg['file_path']);
            }
            // Recompute is_online from last_seen (3-minute threshold)
            $sls = $msg['sender_last_seen']   ?? null;
            $rls = $msg['receiver_last_seen'] ?? null;
            $msg['sender_is_online']   = $sls && (time() - strtotime($sls))   < 60;
            $msg['receiver_is_online'] = $rls && (time() - strtotime($rls))   < 60;
        }

        // Get the other user's online status
        // Online = last API activity within 3 minutes
        $other = $this->users->find($receiverId);
        $contactStatus = null;
        if ($other) {
            $lastSeen  = $other['last_seen'] ?? null;
            $isOnline  = $lastSeen && (time() - strtotime($lastSeen)) < 60;
            $contactStatus = [
                'user_id'   => $receiverId,
                'is_online' => $isOnline,
                'last_seen' => $lastSeen,
            ];
        }

        return $this->respond->success([
            'page'           => $page,
            'limit'          => $limit,
            'contact_status' => $contactStatus,
            'messages'       => array_reverse($msgs),
        ]);
    }

    // ═══════════════════════════════════════════
    //  GET /api/messages/inbox
    // ═══════════════════════════════════════════
    public function inbox(): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        $inbox = $this->messages->getInbox($authUser['id']);

        foreach ($inbox as &$row) {
            if ($row['file_path']) {
                $row['file_url'] = base_url('uploads/' . $row['file_path']);
            }
            if ($row['contact_photo']) {
                $row['contact_photo_url'] = base_url('uploads/' . $row['contact_photo']);
            }
            // Compute online/offline from last_seen (1-minute threshold)
            $ls       = $row['contact_last_seen'] ?? null;
            $isOnline = $ls && (time() - strtotime($ls)) < 60;
            $row['contact_is_online'] = $isOnline;
            if (!$ls) {
                $row['contact_last_seen_text'] = null;
            } elseif ($isOnline) {
                $row['contact_last_seen_text'] = 'online';
            } else {
                $diff = time() - strtotime($ls);
                if ($diff < 3600)       $row['contact_last_seen_text'] = 'last seen ' . floor($diff/60) . ' min ago';
                elseif ($diff < 86400)  $row['contact_last_seen_text'] = 'last seen ' . floor($diff/3600) . ' hours ago';
                else                    $row['contact_last_seen_text'] = 'last seen ' . date('M j', strtotime($ls));
            }
        }

        return $this->respond->success($inbox);
    }

    // ═══════════════════════════════════════════
    //  DELETE /api/messages/{id}
    //
    //  Body: { "delete_for": "me" }   — hides from you only (any participant)
    //        { "delete_for": "all" }  — hides for everyone (sender only)
    //
    //  Neither option deletes the file from disk (soft delete only).
    //  Default is "me" if delete_for is omitted.
    // ═══════════════════════════════════════════
    public function delete(int $id): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        $myId    = $authUser['id'];
        $message = $this->messages->find($id);

        if (!$message || $message['is_deleted']) {
            return $this->respond->notFound('Message not found.');
        }

        $senderId   = (int) $message['sender_id'];
        $receiverId = (int) $message['receiver_id'];

        // Must be a participant
        if ($myId !== $senderId && $myId !== $receiverId) {
            return $this->respond->forbidden('Access denied.');
        }

        $body      = $this->request->getJSON(true) ?? [];
        $deleteFor = strtolower(trim($body['delete_for'] ?? 'me'));

        if ($deleteFor === 'all') {
            // Only the sender can delete for everyone
            if ($myId !== $senderId) {
                return $this->respond->forbidden('Only the sender can delete a message for everyone.');
            }
            $this->messages->update($id, [
                'is_deleted' => 1,
                'deleted_by' => $myId,
            ]);
            return $this->respond->success(
                ['delete_for' => 'all', 'message_id' => $id],
                'Message deleted for everyone.'
            );
        }

        // delete_for = "me" (default)
        $db  = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        $already = $db->table('message_deletions')
            ->where('message_id', $id)
            ->where('user_id', $myId)
            ->countAllResults();

        if (!$already) {
            $db->table('message_deletions')->insert([
                'message_id' => $id,
                'user_id'    => $myId,
                'deleted_at' => $now,
            ]);
        }

        return $this->respond->success(
            ['delete_for' => 'me', 'message_id' => $id],
            'Message deleted for you.'
        );
    }

    // ═══════════════════════════════════════════
    //  POST /api/messages/{id}/react
    //  Body: { "reaction": "👍" }
    // ═══════════════════════════════════════════
    public function react(int $id): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        $message = $this->messages->find($id);
        if (!$message || $message['is_deleted']) {
            return $this->respond->notFound('Message not found.');
        }

        $body     = $this->request->getJSON(true) ?? [];
        $reaction = trim($body['reaction'] ?? '');
        if (!$reaction) return $this->respond->error('reaction is required.', 422);

        $db       = \Config\Database::connect();
        $existing = $db->table('message_reactions')
            ->where('message_id', $id)->where('user_id', $authUser['id'])
            ->get()->getRowArray();

        if ($existing) {
            $db->table('message_reactions')->where('id', $existing['id'])->update(['reaction' => $reaction]);
            $msg = 'Reaction updated.';
        } else {
            $db->table('message_reactions')->insert([
                'message_id' => $id, 'user_id' => $authUser['id'],
                'reaction'   => $reaction, 'created_at' => date('Y-m-d H:i:s'),
            ]);
            $msg = 'Reaction added.';
        }

        $reactions = $db->table('message_reactions')
            ->select('message_reactions.reaction, users.name, users.username')
            ->join('users', 'users.id = message_reactions.user_id', 'left')
            ->where('message_id', $id)->get()->getResultArray();

        return $this->respond->success(['message_id' => $id, 'reactions' => $reactions], $msg);
    }

    // ═══════════════════════════════════════════
    //  DELETE /api/messages/{id}/react
    // ═══════════════════════════════════════════
    public function removeReact(int $id): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        \Config\Database::connect()->table('message_reactions')
            ->where('message_id', $id)->where('user_id', $authUser['id'])->delete();

        return $this->respond->success(null, 'Reaction removed.');
    }

    // ═══════════════════════════════════════════
    //  POST /api/messages/read
    //  Marks messages as READ (double-tick ✓✓ seen)
    //  Body: { "message_ids": [1,2,3] }
    //     OR { "sender_id": 5 }  → all from sender
    // ═══════════════════════════════════════════
    public function markRead(): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        $body       = $this->request->getJSON(true) ?? [];
        $messageIds = $body['message_ids'] ?? [];
        $senderId   = (int) ($body['sender_id'] ?? 0);
        $now        = date('Y-m-d H:i:s');
        $db         = \Config\Database::connect();

        if ($senderId && empty($messageIds)) {
            $rows = $this->messages
                ->select('id')
                ->where('sender_id', $senderId)
                ->where('receiver_id', $authUser['id'])
                ->where('is_deleted', 0)
                ->findAll();
            $messageIds = array_column($rows, 'id');
        }

        if (empty($messageIds)) {
            return $this->respond->error('message_ids or sender_id required.', 422);
        }

        foreach ($messageIds as $msgId) {
            $exists = $db->table('message_status')
                ->where('message_id', $msgId)->where('user_id', $authUser['id'])
                ->get()->getRowArray();

            if ($exists) {
                if ($exists['status'] !== 'read') {
                    $db->table('message_status')
                        ->where('message_id', $msgId)->where('user_id', $authUser['id'])
                        ->update(['status' => 'read', 'read_at' => $now]);
                }
            } else {
                $db->table('message_status')->insert([
                    'message_id'   => $msgId,
                    'user_id'      => $authUser['id'],
                    'status'       => 'read',
                    'delivered_at' => $now,
                    'read_at'      => $now,
                ]);
            }
        }

        return $this->respond->success(['marked_count' => count($messageIds)], 'Messages marked as read.');
    }

    // ═══════════════════════════════════════════
    //  POST /api/messages/delivered
    //  Marks messages as DELIVERED (single-tick ✓)
    //  Body: { "message_ids": [1,2,3] }
    //     OR { "sender_id": 5 }  → all from sender
    // ═══════════════════════════════════════════
    public function markDelivered(): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        $body       = $this->request->getJSON(true) ?? [];
        $messageIds = $body['message_ids'] ?? [];
        $senderId   = (int) ($body['sender_id'] ?? 0);
        $now        = date('Y-m-d H:i:s');
        $db         = \Config\Database::connect();

        if ($senderId && empty($messageIds)) {
            $rows = $this->messages
                ->select('id')
                ->where('sender_id', $senderId)
                ->where('receiver_id', $authUser['id'])
                ->where('is_deleted', 0)
                ->findAll();
            $messageIds = array_column($rows, 'id');
        }

        if (empty($messageIds)) {
            return $this->respond->error('message_ids or sender_id required.', 422);
        }

        foreach ($messageIds as $msgId) {
            $exists = $db->table('message_status')
                ->where('message_id', $msgId)->where('user_id', $authUser['id'])
                ->get()->getRowArray();

            if ($exists) {
                if ($exists['status'] === 'sent') {
                    $db->table('message_status')
                        ->where('message_id', $msgId)->where('user_id', $authUser['id'])
                        ->update(['status' => 'delivered', 'delivered_at' => $now]);
                }
            } else {
                $db->table('message_status')->insert([
                    'message_id'   => $msgId,
                    'user_id'      => $authUser['id'],
                    'status'       => 'delivered',
                    'delivered_at' => $now,
                ]);
            }
        }

        return $this->respond->success(['marked_count' => count($messageIds)], 'Messages marked as delivered.');
    }

    // ═══════════════════════════════════════════
    //  GET /api/messages/unread-count
    // ═══════════════════════════════════════════
    public function unreadCount(): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        return $this->respond->success(['unread_count' => $this->messages->getUnreadCount($authUser['id'])]);
    }

    // ═══════════════════════════════════════════
    //  GET /api/messages/{user_id}/status
    //  Returns online/offline status + last_seen of a user
    // ═══════════════════════════════════════════
    public function userStatus(int $userId): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        $user = $this->users->find($userId);
        if (!$user) return $this->respond->notFound('User not found.');

        $lastSeen = $user['last_seen'] ?? null;
        $isOnline = $lastSeen && (time() - strtotime($lastSeen)) < 60;
        return $this->respond->success([
            'user_id'   => $userId,
            'is_online' => $isOnline,
            'last_seen' => $lastSeen,
        ]);
    }

    // ═══════════════════════════════════════════
    //  POST /api/messages/{id}/reply
    // ═══════════════════════════════════════════
    public function reply(int $messageId): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        $original = $this->messages->find($messageId);
        if (!$original || $original['is_deleted']) {
            return $this->respond->notFound('Message not found.');
        }

        $senderId   = $authUser['id'];
        $origSender = (int) $original['sender_id'];
        $origRecv   = (int) $original['receiver_id'];

        if ($senderId === $origSender) {
            $receiverId = $origRecv;
        } elseif ($senderId === $origRecv) {
            $receiverId = $origSender;
        } else {
            $receiverId = $origSender;
        }

        $body    = $this->request->getJSON(true) ?? [];
        $content = trim($body['content'] ?? '');
        if (!$content) return $this->respond->error('content is required.', 422);

        $id = $this->messages->insert([
            'sender_id'   => $senderId,
            'receiver_id' => $receiverId,
            'type'        => 'text',
            'content'     => $content,
            'reply_to_id' => $messageId,
        ]);

        $this->createStatus($id, $receiverId);

        $msg = $this->messages->find($id);
        $msg['reply_to'] = [
            'id'        => $original['id'],
            'type'      => $original['type'],
            'content'   => $original['content'],
            'sender_id' => $original['sender_id'],
        ];

        return $this->respond->success($msg, 'Reply sent.', 201);
    }

    // ═══════════════════════════════════════════
    //  GET /api/messages/{message_id}/reactions
    // ═══════════════════════════════════════════
    public function reactions(int $messageId): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        $message = $this->messages->find($messageId);
        if (!$message || $message['is_deleted']) {
            return $this->respond->notFound('Message not found.');
        }

        if ((int)$message['sender_id'] !== $authUser['id'] && (int)$message['receiver_id'] !== $authUser['id']) {
            return $this->respond->forbidden('Access denied.');
        }

        $reactions = $this->messages->getReactions($messageId);

        $summary = [];
        foreach ($reactions as $r) {
            $emoji = $r['reaction'];
            if (!isset($summary[$emoji])) {
                $summary[$emoji] = ['reaction' => $emoji, 'count' => 0, 'users' => []];
            }
            $summary[$emoji]['count']++;
            $summary[$emoji]['users'][] = [
                'id' => $r['user_id'], 'name' => $r['user_name'], 'username' => $r['user_username'],
            ];
        }

        return $this->respond->success([
            'message_id' => $messageId,
            'total'      => count($reactions),
            'summary'    => array_values($summary),
            'reactions'  => $reactions,
        ]);
    }

    // ─────────────────────────────────────────
    // Shared media helpers
    // ─────────────────────────────────────────
    private function getMediaByType(int $userId, string $type): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        if (!$this->users->find($userId)) return $this->respond->notFound('User not found.');

        $page   = max(1, (int) ($this->request->getGet('page')  ?? 1));
        $limit  = min(100, (int) ($this->request->getGet('limit') ?? 50));
        $offset = ($page - 1) * $limit;

        $items = $this->messages->getMediaMessages($authUser['id'], $userId, $type, $limit, $offset);

        foreach ($items as &$item) {
            if ($item['file_path']) $item['file_url'] = base_url('uploads/' . $item['file_path']);
            if ($item['sender_photo']) $item['sender_photo'] = base_url('uploads/' . $item['sender_photo']);
        }

        return $this->respond->success(['type' => $type, 'page' => $page, 'limit' => $limit, 'total' => count($items), 'items' => $items]);
    }

    public function images(int $userId): \CodeIgniter\HTTP\Response    { return $this->getMediaByType($userId, 'image'); }
    public function videos(int $userId): \CodeIgniter\HTTP\Response    { return $this->getMediaByType($userId, 'video'); }
    public function voices(int $userId): \CodeIgniter\HTTP\Response    { return $this->getMediaByType($userId, 'voice'); }
    public function audio(int $userId): \CodeIgniter\HTTP\Response     { return $this->getMediaByType($userId, 'voice'); }
    public function documents(int $userId): \CodeIgniter\HTTP\Response { return $this->getMediaByType($userId, 'document'); }

    // ═══════════════════════════════════════════
    //  GET /api/messages/{user_id}/replies
    // ═══════════════════════════════════════════
    public function replies(int $userId): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        if (!$this->users->find($userId)) return $this->respond->notFound('User not found.');

        $page   = max(1, (int) ($this->request->getGet('page')  ?? 1));
        $limit  = min(100, (int) ($this->request->getGet('limit') ?? 50));
        $offset = ($page - 1) * $limit;

        $items = $this->messages->getReplies($authUser['id'], $userId, $limit, $offset);

        foreach ($items as &$item) {
            if ($item['file_path']) $item['file_url'] = base_url('uploads/' . $item['file_path']);
        }

        return $this->respond->success(['page' => $page, 'limit' => $limit, 'total' => count($items), 'replies' => $items]);
    }

    // ─────────────────────────────────────────
    // Internal helpers
    // ─────────────────────────────────────────

    // Create initial 'sent' status row for receiver
    private function createStatus(int $messageId, int $receiverId): void
    {
        \Config\Database::connect()->table('message_status')->insert([
            'message_id' => $messageId,
            'user_id'    => $receiverId,
            'status'     => 'sent',
        ]);
    }

    // Auto-mark messages from $senderId to $receiverId as 'delivered'
    private function markAsDeliveredInternal(int $senderId, int $receiverId): void
    {
        $db  = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        // Find messages sent to me that are still 'sent'
        $rows = $this->messages
            ->select('messages.id')
            ->join('message_status', 'message_status.message_id = messages.id AND message_status.user_id = ' . $receiverId, 'left')
            ->where('messages.sender_id', $senderId)
            ->where('messages.receiver_id', $receiverId)
            ->where('messages.is_deleted', 0)
            ->groupStart()
                ->where('message_status.status', 'sent')
                ->orWhere('message_status.status IS NULL')
            ->groupEnd()
            ->findAll();

        if (empty($rows)) return;

        $ids = array_column($rows, 'id');

        foreach ($ids as $msgId) {
            $exists = $db->table('message_status')
                ->where('message_id', $msgId)->where('user_id', $receiverId)
                ->get()->getRowArray();

            if ($exists) {
                $db->table('message_status')
                    ->where('message_id', $msgId)->where('user_id', $receiverId)
                    ->update(['status' => 'delivered', 'delivered_at' => $now]);
            } else {
                $db->table('message_status')->insert([
                    'message_id'   => $msgId,
                    'user_id'      => $receiverId,
                    'status'       => 'delivered',
                    'delivered_at' => $now,
                ]);
            }
        }
    }

    private function buildMessage(int $id): array
    {
        $db  = \Config\Database::connect();
        $msg = $this->messages->find($id);
        if (!$msg) return [];

        if ($msg['file_path']) {
            $msg['file_url'] = base_url('uploads/' . $msg['file_path']);
        }

        // Include delivery status
        $status = $db->table('message_status')
            ->where('message_id', $id)
            ->where('user_id', $msg['receiver_id'])
            ->get()->getRowArray();

        $msg['delivery_status'] = $status['status']     ?? 'sent';
        $msg['delivered_at']    = $status['delivered_at'] ?? null;
        $msg['read_at']         = $status['read_at']      ?? null;

        return $msg;
    }
}
