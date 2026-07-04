<?php

namespace App\Controllers\Api\Notification;

use App\Libraries\JWTLibrary;
use App\Libraries\ResponseLibrary;
use App\Models\NotificationModel;
use CodeIgniter\Controller;

/**
 * ─────────────────────────────────────────────────────────
 *  Notifications API
 * ─────────────────────────────────────────────────────────
 *  GET    /api/notifications                — list my notifications
 *  GET    /api/notifications/unread-count   — unread badge count
 *  POST   /api/notifications/:id/read       — mark one as read
 *  POST   /api/notifications/read-all       — mark all as read
 *  POST   /api/notifications/send           — send (admin role only)
 * ─────────────────────────────────────────────────────────
 */
class NotificationController extends Controller
{
    private NotificationModel $model;
    private ResponseLibrary   $respond;
    private JWTLibrary        $jwt;

    public function __construct()
    {
        $this->model   = new NotificationModel();
        $this->respond = new ResponseLibrary();
        $this->jwt     = new JWTLibrary();
    }

    // ── Auth helper ──────────────────────────────────────

    private function auth(): array|false
    {
        $header = $this->request->getHeaderLine('Authorization');
        if (!$header || !str_starts_with($header, 'Bearer ')) return false;

        $result = $this->jwt->validate(trim(substr($header, 7)));
        if (!$result['valid']) return false;

        $user = \Config\Database::connect()
            ->table('users')
            ->where('id', $result['payload']['user_id'])
            ->where('status', 'active')
            ->get()->getRowArray();

        return $user ?: false;
    }

    // ═══════════════════════════════════════════════════════
    //  GET /api/notifications
    //  Query: page=1, limit=20
    //
    //  Returns paginated list of my notifications.
    //  Each item:
    //    id, title, body, type, action_url, is_read, read_at, created_at
    // ═══════════════════════════════════════════════════════

    public function index(): \CodeIgniter\HTTP\Response
    {
        $user = $this->auth();
        if (!$user) return $this->respond->unauthorized('Token required.');

        $limit  = min(50, max(1, (int)($this->request->getGet('limit') ?? 20)));
        $page   = max(1, (int)($this->request->getGet('page') ?? 1));
        $offset = ($page - 1) * $limit;

        $items = $this->model->getForUser($user['id'], $limit, $offset);
        $total = $this->model->getTotalForUser($user['id']);

        // Parse JSON data field
        foreach ($items as &$n) {
            $n['data']    = $n['data'] ? json_decode($n['data'], true) : null;
            $n['is_read'] = (bool)$n['is_read'];
        }

        return $this->respond->success([
            'notifications' => $items,
            'unread_count'  => $this->model->getUnreadCount($user['id']),
            'pagination'    => [
                'total'        => $total,
                'page'         => $page,
                'limit'        => $limit,
                'total_pages'  => (int)ceil($total / $limit),
            ],
        ]);
    }

    // ═══════════════════════════════════════════════════════
    //  GET /api/notifications/unread-count
    //
    //  Lightweight endpoint for the badge counter.
    //  Returns: { unread_count: 5 }
    // ═══════════════════════════════════════════════════════

    public function unreadCount(): \CodeIgniter\HTTP\Response
    {
        $user = $this->auth();
        if (!$user) return $this->respond->unauthorized('Token required.');

        return $this->respond->success([
            'unread_count' => $this->model->getUnreadCount($user['id']),
        ]);
    }

    // ═══════════════════════════════════════════════════════
    //  POST /api/notifications/:id/read
    //
    //  Mark a single notification as read.
    // ═══════════════════════════════════════════════════════

    public function markRead(int $id): \CodeIgniter\HTTP\Response
    {
        $user = $this->auth();
        if (!$user) return $this->respond->unauthorized('Token required.');

        $this->model->markRead($id, $user['id']);

        return $this->respond->success([
            'unread_count' => $this->model->getUnreadCount($user['id']),
        ], 'Notification marked as read.');
    }

    // ═══════════════════════════════════════════════════════
    //  POST /api/notifications/read-all
    //
    //  Mark all my notifications as read.
    // ═══════════════════════════════════════════════════════

    public function readAll(): \CodeIgniter\HTTP\Response
    {
        $user = $this->auth();
        if (!$user) return $this->respond->unauthorized('Token required.');

        $this->model->markAllRead($user['id']);

        return $this->respond->success(['unread_count' => 0], 'All notifications marked as read.');
    }

    // ═══════════════════════════════════════════════════════
    //  POST /api/notifications/send
    //  Requires: admin role
    //
    //  Body (JSON):
    //    title       string  required
    //    body        string  required
    //    type        string  optional  default: general
    //    action_url  string  optional
    //    target      string  required  "user" | "users" | "group" | "all"
    //    user_id     int     when target=user
    //    user_ids    int[]   when target=users
    //    group_id    int     when target=group
    //
    //  Returns:
    //    sent_to  — number of users notified
    // ═══════════════════════════════════════════════════════

    public function send(): \CodeIgniter\HTTP\Response
    {
        $user = $this->auth();
        if (!$user) return $this->respond->unauthorized('Token required.');

        // Admin-only
        if (($user['role_slug'] ?? '') !== 'admin') {
            return $this->respond->error('Forbidden. Admin role required.', 403);
        }

        $body = $this->request->getJSON(true) ?? [];

        $title  = trim($body['title']      ?? '');
        $text   = trim($body['body']       ?? '');
        $type   = trim($body['type']       ?? 'general');
        $action = trim($body['action_url'] ?? '');
        $target = trim($body['target']     ?? '');

        if (!$title) return $this->respond->error('title is required.', 422);
        if (!$text)  return $this->respond->error('body is required.', 422);
        if (!$target) return $this->respond->error('target is required: user | users | group | all', 422);

        // Resolve recipients
        $recipientIds = match ($target) {
            'user'  => [(int)($body['user_id'] ?? 0)],
            'users' => array_map('intval', (array)($body['user_ids'] ?? [])),
            'group' => $this->model->groupMemberIds((int)($body['group_id'] ?? 0)),
            'all'   => $this->model->allActiveUserIds(),
            default => [],
        };

        $recipientIds = array_filter($recipientIds); // remove zeros

        if (empty($recipientIds)) {
            return $this->respond->error('No valid recipients found for the given target.', 422);
        }

        $sent = $this->model->sendToMany([
            'sender_id'  => $user['id'],
            'title'      => $title,
            'body'       => $text,
            'type'       => $type,
            'action_url' => $action ?: null,
        ], $recipientIds);

        return $this->respond->success([
            'sent_to' => $sent,
        ], "Notification sent to {$sent} user(s).");
    }
}
