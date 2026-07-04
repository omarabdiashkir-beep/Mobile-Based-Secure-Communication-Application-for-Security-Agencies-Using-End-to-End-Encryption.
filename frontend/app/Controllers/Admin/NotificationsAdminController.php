<?php

namespace App\Controllers\Admin;

use App\Models\NotificationModel;

class NotificationsAdminController extends BaseAdminController
{
    private NotificationModel $notif;

    public function __construct()
    {
        $this->notif = new NotificationModel();
    }

    public function index()
    {
        if ($r = $this->requireAuth()) return $r;

        $db     = $this->db();
        $users  = $db->table('users')->select('id, name, username')->orderBy('name')->get()->getResultArray();
        $groups = $db->table('`groups`')->select('id, name')->where('is_active', 1)->orderBy('name')->get()->getResultArray();
        $recent = $this->notif->recentSent(40);

        return view('admin/notifications/index', compact('users', 'groups', 'recent'));
    }

    public function show(int $id)
    {
        if ($r = $this->requireAuth()) return $r;

        $notif = $this->notif->getById($id);
        if (!$notif) {
            return redirect()->to('/admin/notifications')->with('send_error', 'Notification not found.');
        }

        $recipients = $this->notif->getRecipientsOf($id);

        return view('admin/notifications/show', compact('notif', 'recipients'));
    }

    public function send()
    {
        if ($r = $this->requireAuth()) return $r;

        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->to('/admin/notifications');
        }

        $db     = $this->db();
        $title  = trim($this->request->getPost('title') ?? '');
        $body   = trim($this->request->getPost('body')  ?? '');
        $type   = $this->request->getPost('type')   ?? 'general';
        $action = trim($this->request->getPost('action_url') ?? '');
        $target = $this->request->getPost('target') ?? '';

        if (!$title || !$body || !$target) {
            return redirect()->to('/admin/notifications')->with('send_error', 'Title, body and target are required.');
        }

        // Resolve recipients
        $recipientIds = [];
        switch ($target) {
            case 'user':
                $uid = (int)$this->request->getPost('user_id');
                if ($uid) $recipientIds = [$uid];
                break;
            case 'users':
                $ids = $this->request->getPost('user_ids') ?? [];
                $recipientIds = array_map('intval', (array)$ids);
                break;
            case 'group':
                $gid = (int)$this->request->getPost('group_id');
                if ($gid) $recipientIds = $this->notif->groupMemberIds($gid);
                break;
            case 'all':
                $recipientIds = $this->notif->allActiveUserIds();
                break;
        }

        $recipientIds = array_filter($recipientIds);

        if (empty($recipientIds)) {
            return redirect()->to('/admin/notifications')->with('send_error', 'No recipients found. Check your selection.');
        }

        $sent = $this->notif->sendToMany([
            'title'      => $title,
            'body'       => $body,
            'type'       => $type,
            'action_url' => $action ?: null,
        ], $recipientIds);

        return redirect()->to('/admin/notifications')->with('success', "Notification sent to {$sent} user(s).");
    }
}
