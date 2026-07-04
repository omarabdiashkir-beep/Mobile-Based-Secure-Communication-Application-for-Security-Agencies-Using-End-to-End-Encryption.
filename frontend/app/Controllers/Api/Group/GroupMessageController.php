<?php

namespace App\Controllers\Api\Group;

use App\Libraries\JWTLibrary;
use App\Libraries\ResponseLibrary;
use App\Models\UserModel;
use CodeIgniter\Controller;

class GroupMessageController extends Controller
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

    private function auth(): array|object
    {
        $header = $this->request->getHeaderLine('Authorization');
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return $this->respond->unauthorized('Token required.');
        }
        $token  = trim(substr($header, 7));
        $result = $this->jwt->validate($token);
        if (!$result['valid']) return $this->respond->unauthorized('Invalid token.');
        $user = $this->users->findByToken($token);
        if (!$user) return $this->respond->unauthorized('Token revoked.');
        return $user;
    }

    private function isMember(int $groupId, int $userId): bool
    {
        $db = \Config\Database::connect();
        return (bool) $db->table('group_members')
            ->where('group_id', $groupId)->where('user_id', $userId)
            ->get()->getRowArray();
    }

    private function isAdmin(int $groupId, int $userId): bool
    {
        $db = \Config\Database::connect();
        return (bool) $db->table('group_members')
            ->where('group_id', $groupId)->where('user_id', $userId)->where('role', 'admin')
            ->get()->getRowArray();
    }

    // POST /api/groups/{id}/messages/send
    public function send(int $groupId): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        if (!$this->isMember($groupId, $authUser['id'])) {
            return $this->respond->forbidden('You are not a member of this group.');
        }

        $db    = \Config\Database::connect();
        $group = $db->table('groups')->where('id', $groupId)->get()->getRowArray();
        if (!$group) return $this->respond->notFound('Group not found.');

        if ($group['only_admin_can_send'] && !$this->isAdmin($groupId, $authUser['id'])) {
            return $this->respond->forbidden('Only admin can send messages in this group.');
        }

        $contentType = $this->request->getHeaderLine('Content-Type');
        $isMultipart = str_contains($contentType, 'multipart/form-data');
        $body        = $isMultipart ? $this->request->getPost() : ($this->request->getJSON(true) ?? []);

        $type      = trim($body['type']          ?? 'text');
        $content   = trim($body['content']       ?? '');
        $replyToId = !empty($body['reply_to_id']) ? (int)$body['reply_to_id'] : null;

        $allowedTypes = ['text', 'image', 'video', 'voice', 'document'];
        if (!in_array($type, $allowedTypes)) {
            return $this->respond->error('Invalid type. Use: text, image, video, voice, document.', 422);
        }

        if (!$content && $type === 'text') {
            return $this->respond->error('content is required for text messages.', 422);
        }

        // File upload
        $filePath = null;
        $fileName = null;
        $fileSize = null;

        if ($isMultipart && $type !== 'text') {
            $file = $this->request->getFile('file');
            if (!$file || !$file->isValid() || $file->hasMoved()) {
                return $this->respond->error('File is required for ' . $type . ' messages.', 422);
            }

            $limits = ['image' => 10, 'video' => 100, 'voice' => 20, 'document' => 50];
            $maxMB  = ($limits[$type] ?? 10) * 1024 * 1024;
            if ($file->getSize() > $maxMB) {
                return $this->respond->error('File too large.', 422);
            }

            $uploadDir = FCPATH . 'uploads/' . $type . 's/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $newName  = bin2hex(random_bytes(16)) . '.' . $file->getExtension();
            $file->move($uploadDir, $newName);
            $filePath = $type . 's/' . $newName;
            $fileName = $file->getClientName();
            $fileSize = $file->getSize();
        }

        $now = date('Y-m-d H:i:s');
        $db->table('group_messages')->insert([
            'group_id'   => $groupId,
            'sender_id'  => $authUser['id'],
            'type'       => $type,
            'content'    => $content ?: null,
            'file_path'  => $filePath,
            'file_name'  => $fileName,
            'file_size'  => $fileSize,
            'reply_to_id'=> $replyToId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $msgId = $db->insertID();
        $msg   = $db->table('group_messages')->where('id', $msgId)->get()->getRowArray();
        $msg['file_url'] = $msg['file_path'] ? base_url('uploads/' . $msg['file_path']) : null;

        return $this->respond->success(['message' => $msg], 'Message sent.', 201);
    }

    // GET /api/groups/{id}/messages
    public function messages(int $groupId): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        if (!$this->isMember($groupId, $authUser['id'])) {
            return $this->respond->forbidden('You are not a member of this group.');
        }

        $page   = max(1, (int)($this->request->getGet('page')  ?? 1));
        $limit  = min(100, (int)($this->request->getGet('limit') ?? 50));
        $offset = ($page - 1) * $limit;

        $db   = \Config\Database::connect();
        $msgs = $db->query("
            SELECT
                gm.id, gm.group_id, gm.sender_id, gm.type, gm.content,
                gm.file_path, gm.file_name, gm.file_size,
                gm.reply_to_id, gm.created_at,
                u.name AS sender_name, u.username AS sender_username, u.photo AS sender_photo,
                rm.content AS reply_content, rm.type AS reply_type, rm.sender_id AS reply_sender_id
            FROM group_messages gm
            INNER JOIN users u ON u.id = gm.sender_id
            LEFT JOIN group_messages rm ON rm.id = gm.reply_to_id
            WHERE gm.group_id = ?
            ORDER BY gm.created_at ASC
            LIMIT ? OFFSET ?
        ", [$groupId, $limit, $offset])->getResultArray();

        foreach ($msgs as &$m) {
            $m['file_url']     = $m['file_path'] ? base_url('uploads/' . $m['file_path']) : null;
            $m['sender_photo'] = $m['sender_photo'] ? base_url('uploads/' . $m['sender_photo']) : null;
        }

        return $this->respond->success([
            'page'     => $page,
            'limit'    => $limit,
            'messages' => $msgs,
        ]);
    }

    // DELETE /api/groups/{id}/messages/{msg_id}
    public function deleteMessage(int $groupId, int $msgId): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        $db  = \Config\Database::connect();
        $msg = $db->table('group_messages')
            ->where('id', $msgId)->where('group_id', $groupId)
            ->get()->getRowArray();

        if (!$msg) return $this->respond->notFound('Message not found.');

        $isSender = (int)$msg['sender_id'] === $authUser['id'];
        $isAdmin  = $this->isAdmin($groupId, $authUser['id']);

        if (!$isSender && !$isAdmin) {
            return $this->respond->forbidden('You can only delete your own messages.');
        }

        if ($msg['file_path'] && file_exists(FCPATH . 'uploads/' . $msg['file_path'])) {
            @unlink(FCPATH . 'uploads/' . $msg['file_path']);
        }

        $db->table('group_messages')->where('id', $msgId)->delete();

        return $this->respond->success(null, 'Message deleted.');
    }

    // POST /api/groups/{id}/messages/{msg_id}/reply
    public function reply(int $groupId, int $msgId): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        if (!$this->isMember($groupId, $authUser['id'])) {
            return $this->respond->forbidden('You are not a member of this group.');
        }

        $db    = \Config\Database::connect();
        $group = $db->table('groups')->where('id', $groupId)->get()->getRowArray();

        if ($group['only_admin_can_send'] && !$this->isAdmin($groupId, $authUser['id'])) {
            return $this->respond->forbidden('Only admin can send messages in this group.');
        }

        $original = $db->table('group_messages')
            ->where('id', $msgId)->where('group_id', $groupId)
            ->get()->getRowArray();
        if (!$original) return $this->respond->notFound('Original message not found.');

        $body    = $this->request->getJSON(true) ?? [];
        $content = trim($body['content'] ?? '');

        if (!$content) {
            return $this->respond->error('content is required.', 422);
        }

        $now = date('Y-m-d H:i:s');
        $db->table('group_messages')->insert([
            'group_id'   => $groupId,
            'sender_id'  => $authUser['id'],
            'type'       => 'text',
            'content'    => $content,
            'reply_to_id'=> $msgId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $newMsgId = $db->insertID();
        $msg      = $db->table('group_messages')->where('id', $newMsgId)->get()->getRowArray();

        return $this->respond->success([
            'message'  => $msg,
            'reply_to' => $original,
        ], 'Reply sent.', 201);
    }

    // Media filters
    private function mediaByType(int $groupId, string $type): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        if (!$this->isMember($groupId, $authUser['id'])) {
            return $this->respond->forbidden('You are not a member of this group.');
        }

        $page   = max(1, (int)($this->request->getGet('page')  ?? 1));
        $limit  = min(100, (int)($this->request->getGet('limit') ?? 50));
        $offset = ($page - 1) * $limit;

        $db = \Config\Database::connect();

        if ($type === 'replies') {
            $items = $db->query("
                SELECT gm.*, u.name AS sender_name,
                       rm.content AS reply_to_content, rm.type AS reply_to_type
                FROM group_messages gm
                INNER JOIN users u ON u.id = gm.sender_id
                LEFT JOIN group_messages rm ON rm.id = gm.reply_to_id
                WHERE gm.group_id = ? AND gm.reply_to_id IS NOT NULL
                ORDER BY gm.created_at DESC
                LIMIT ? OFFSET ?
            ", [$groupId, $limit, $offset])->getResultArray();
        } else {
            $items = $db->query("
                SELECT gm.*, u.name AS sender_name
                FROM group_messages gm
                INNER JOIN users u ON u.id = gm.sender_id
                WHERE gm.group_id = ? AND gm.type = ?
                ORDER BY gm.created_at DESC
                LIMIT ? OFFSET ?
            ", [$groupId, $type, $limit, $offset])->getResultArray();
        }

        foreach ($items as &$item) {
            $item['file_url'] = $item['file_path'] ? base_url('uploads/' . $item['file_path']) : null;
        }

        return $this->respond->success([
            'type'  => $type,
            'page'  => $page,
            'limit' => $limit,
            'total' => count($items),
            'items' => $items,
        ]);
    }

    public function images(int $groupId): \CodeIgniter\HTTP\Response    { return $this->mediaByType($groupId, 'image'); }
    public function videos(int $groupId): \CodeIgniter\HTTP\Response    { return $this->mediaByType($groupId, 'video'); }
    public function voices(int $groupId): \CodeIgniter\HTTP\Response    { return $this->mediaByType($groupId, 'voice'); }
    public function documents(int $groupId): \CodeIgniter\HTTP\Response { return $this->mediaByType($groupId, 'document'); }
    public function replies(int $groupId): \CodeIgniter\HTTP\Response   { return $this->mediaByType($groupId, 'replies'); }

    // POST /api/groups/{id}/messages/{msg_id}/react
    public function react(int $groupId, int $msgId): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        if (!$this->isMember($groupId, $authUser['id'])) {
            return $this->respond->forbidden('You are not a member of this group.');
        }

        $db  = \Config\Database::connect();
        $msg = $db->table('group_messages')
            ->where('id', $msgId)->where('group_id', $groupId)
            ->get()->getRowArray();
        if (!$msg) return $this->respond->notFound('Message not found.');

        $body     = $this->request->getJSON(true) ?? [];
        $reaction = trim($body['reaction'] ?? '');
        if (!$reaction) return $this->respond->error('reaction is required.', 422);

        $existing = $db->table('group_message_reactions')
            ->where('message_id', $msgId)->where('user_id', $authUser['id'])
            ->get()->getRowArray();

        if ($existing) {
            $db->table('group_message_reactions')
                ->where('message_id', $msgId)->where('user_id', $authUser['id'])
                ->update(['reaction' => $reaction]);
        } else {
            $db->table('group_message_reactions')->insert([
                'message_id' => $msgId,
                'user_id'    => $authUser['id'],
                'reaction'   => $reaction,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->respond->success([
            'message_id' => $msgId,
            'reactions'  => $this->getReactionSummary($msgId, $db),
        ], 'Reaction added.');
    }

    // DELETE /api/groups/{id}/messages/{msg_id}/react
    public function removeReact(int $groupId, int $msgId): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        $db = \Config\Database::connect();
        $db->table('group_message_reactions')
            ->where('message_id', $msgId)->where('user_id', $authUser['id'])
            ->delete();

        return $this->respond->success(null, 'Reaction removed.');
    }

    // GET /api/groups/{id}/messages/{msg_id}/reactions
    public function reactions(int $groupId, int $msgId): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        if (!$this->isMember($groupId, $authUser['id'])) {
            return $this->respond->forbidden('You are not a member of this group.');
        }

        $db        = \Config\Database::connect();
        $reactions = $this->getReactionSummary($msgId, $db);

        return $this->respond->success([
            'message_id' => $msgId,
            'total'      => array_sum(array_column($reactions, 'count')),
            'reactions'  => $reactions,
        ]);
    }

    // POST /api/groups/{id}/messages/mark-read
    public function markRead(int $groupId): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        if (!$this->isMember($groupId, $authUser['id'])) {
            return $this->respond->forbidden('You are not a member of this group.');
        }

        $body       = $this->request->getJSON(true) ?? [];
        $messageIds = $body['message_ids'] ?? [];

        if (empty($messageIds)) {
            return $this->respond->error('message_ids array is required.', 422);
        }

        $db     = \Config\Database::connect();
        $now    = date('Y-m-d H:i:s');
        $marked = 0;

        foreach ($messageIds as $msgId) {
            $msgId = (int) $msgId;
            $msg   = $db->table('group_messages')
                ->where('id', $msgId)->where('group_id', $groupId)->get()->getRowArray();
            if (!$msg || (int)$msg['sender_id'] === $authUser['id']) continue;

            $exists = $db->table('group_message_reads')
                ->where('message_id', $msgId)->where('user_id', $authUser['id'])
                ->get()->getRowArray();

            if (!$exists) {
                $db->table('group_message_reads')->insert([
                    'message_id' => $msgId,
                    'user_id'    => $authUser['id'],
                    'read_at'    => $now,
                ]);
                $marked++;
            }
        }

        return $this->respond->success(['marked_count' => $marked], 'Messages marked as read.');
    }

    // GET /api/groups/{id}/messages/{msg_id}/seen-by
    public function seenBy(int $groupId, int $msgId): \CodeIgniter\HTTP\Response
    {
        $authUser = $this->auth();
        if ($authUser instanceof \CodeIgniter\HTTP\Response) return $authUser;

        if (!$this->isMember($groupId, $authUser['id'])) {
            return $this->respond->forbidden('You are not a member of this group.');
        }

        $db  = \Config\Database::connect();
        $msg = $db->table('group_messages')
            ->where('id', $msgId)->where('group_id', $groupId)->get()->getRowArray();
        if (!$msg) return $this->respond->notFound('Message not found.');

        $totalMembers = $db->table('group_members')
            ->where('group_id', $groupId)->where('user_id !=', $msg['sender_id'])
            ->countAllResults();

        $seenBy = $db->query("
            SELECT u.id, u.name, u.username, u.photo, r.read_at
            FROM group_message_reads r
            INNER JOIN users u ON u.id = r.user_id
            WHERE r.message_id = ?
            ORDER BY r.read_at ASC
        ", [$msgId])->getResultArray();

        foreach ($seenBy as &$s) {
            $s['photo_url'] = $s['photo'] ? base_url('uploads/' . $s['photo']) : null;
            unset($s['photo']);
        }

        return $this->respond->success([
            'message_id'    => $msgId,
            'seen_count'    => count($seenBy),
            'total_members' => $totalMembers,
            'seen_by'       => $seenBy,
        ]);
    }

    private function getReactionSummary(int $msgId, $db): array
    {
        return $db->query("
            SELECT r.reaction, COUNT(*) as count,
                   GROUP_CONCAT(u.name SEPARATOR ', ') AS users
            FROM group_message_reactions r
            INNER JOIN users u ON u.id = r.user_id
            WHERE r.message_id = ?
            GROUP BY r.reaction
            ORDER BY count DESC
        ", [$msgId])->getResultArray();
    }
}
