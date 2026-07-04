<?php

namespace App\Controllers\Admin;

class MessagesAdminController extends BaseAdminController
{
    // ─────────────────────────────────────────
    //  GET  admin/messages
    //  Tab switcher: Direct Messages | Group Messages
    // ─────────────────────────────────────────
    public function index(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if ($r = $this->requireAuth()) return $r;

        $db     = $this->db();
        $users  = $db->table('users')->select('id, name, username, photo')->orderBy('name')->get()->getResultArray();
        $groups = $db->table('`groups`')->select('id, name')->orderBy('name')->get()->getResultArray();

        return view('admin/messages/index', [
            'users'  => $users,
            'groups' => $groups,
        ]);
    }

    // ─────────────────────────────────────────
    //  GET  admin/messages/conversation?user_a=&user_b=&page=
    //  Direct messages between two users
    // ─────────────────────────────────────────
    public function conversation(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if ($r = $this->requireAuth()) return $r;

        $db     = $this->db();
        $userA  = (int) ($this->request->getGet('user_a') ?? 0);
        $userB  = (int) ($this->request->getGet('user_b') ?? 0);
        $page   = max(1, (int) ($this->request->getGet('page') ?? 1));
        $limit  = 40;
        $offset = ($page - 1) * $limit;

        $users = $db->table('users')->select('id, name, username, photo')->orderBy('name')->get()->getResultArray();

        $messages = [];
        $total    = 0;
        $personA  = null;
        $personB  = null;

        if ($userA && $userB && $userA !== $userB) {
            $personA = $db->table('users')->select('id, name, username, photo')->where('id', $userA)->get()->getRowArray();
            $personB = $db->table('users')->select('id, name, username, photo')->where('id', $userB)->get()->getRowArray();

            $total = $db->table('messages')
                ->groupStart()
                    ->where('sender_id', $userA)->where('receiver_id', $userB)
                ->groupEnd()
                ->orGroupStart()
                    ->where('sender_id', $userB)->where('receiver_id', $userA)
                ->groupEnd()
                ->countAllResults();

            $messages = $db->query("
                SELECT
                    m.*,
                    s.name       AS sender_name,
                    s.username   AS sender_username,
                    s.photo      AS sender_photo,
                    r.name       AS receiver_name,
                    r.username   AS receiver_username,
                    COALESCE(ms.status,'sent') AS delivery_status,
                    ms.delivered_at,
                    ms.read_at,
                    rm.content   AS reply_content,
                    rs.name      AS reply_sender_name
                FROM messages m
                INNER JOIN users s  ON s.id = m.sender_id
                INNER JOIN users r  ON r.id = m.receiver_id
                LEFT  JOIN message_status ms ON ms.message_id = m.id AND ms.user_id = m.receiver_id
                LEFT  JOIN messages rm ON rm.id = m.reply_to_id
                LEFT  JOIN users    rs ON rs.id = rm.sender_id
                WHERE (
                    (m.sender_id = ? AND m.receiver_id = ?)
                    OR
                    (m.sender_id = ? AND m.receiver_id = ?)
                )
                ORDER BY m.created_at DESC
                LIMIT ? OFFSET ?
            ", [$userA, $userB, $userB, $userA, $limit, $offset])->getResultArray();
        }

        return view('admin/messages/index', [
            'users'    => $users,
            'messages' => $messages,
            'total'    => $total,
            'page'     => $page,
            'limit'    => $limit,
            'userA'    => $userA,
            'userB'    => $userB,
            'personA'  => $personA,
            'personB'  => $personB,
            'tab'      => 'direct',
        ]);
    }

    // ─────────────────────────────────────────
    //  GET  admin/messages/group?group_id=&page=
    //  Group messages for a selected group
    // ─────────────────────────────────────────
    public function group(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if ($r = $this->requireAuth()) return $r;

        $db      = $this->db();
        $groupId = (int) ($this->request->getGet('group_id') ?? 0);
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));
        $limit   = 40;
        $offset  = ($page - 1) * $limit;

        $users  = $db->table('users')->select('id, name, username, photo')->orderBy('name')->get()->getResultArray();
        $groups = $db->table('`groups`')->select('id, name')->orderBy('name')->get()->getResultArray();

        $messages    = [];
        $total       = 0;
        $activeGroup = null;

        if ($groupId) {
            $activeGroup = $db->table('`groups`')->select('id, name')->where('id', $groupId)->get()->getRowArray();

            $total = $db->table('group_messages')->where('group_id', $groupId)->countAllResults();

            $messages = $db->query("
                SELECT
                    gm.*,
                    u.name     AS sender_name,
                    u.username AS sender_username,
                    u.photo    AS sender_photo,
                    rm.content AS reply_content,
                    rs.name    AS reply_sender_name,
                    (
                        SELECT COUNT(*) FROM group_message_reads gmr
                        WHERE gmr.message_id = gm.id
                    ) AS seen_by_count
                FROM group_messages gm
                INNER JOIN users u  ON u.id  = gm.sender_id
                LEFT  JOIN group_messages rm ON rm.id = gm.reply_to_id
                LEFT  JOIN users          rs ON rs.id = rm.sender_id
                WHERE gm.group_id = ?
                ORDER BY gm.created_at DESC
                LIMIT ? OFFSET ?
            ", [$groupId, $limit, $offset])->getResultArray();
        }

        return view('admin/messages/index', [
            'users'       => $users,
            'groups'      => $groups,
            'messages'    => $messages,
            'total'       => $total,
            'page'        => $page,
            'limit'       => $limit,
            'groupId'     => $groupId,
            'activeGroup' => $activeGroup,
            'tab'         => 'group',
        ]);
    }
}
