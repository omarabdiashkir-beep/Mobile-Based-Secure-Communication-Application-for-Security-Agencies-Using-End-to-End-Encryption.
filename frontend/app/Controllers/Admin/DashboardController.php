<?php

namespace App\Controllers\Admin;

class DashboardController extends BaseAdminController
{
    public function index()
    {
        if ($r = $this->requireAuth()) return $r;

        $db = $this->db();

        // Users registered per day — last 7 days
        $registrations = $db->query("
            SELECT DATE(created_at) as day, COUNT(*) as count
            FROM users
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ")->getResultArray();

        // Messages per day — last 7 days
        $msg_activity = $db->query("
            SELECT DATE(created_at) as day, COUNT(*) as count
            FROM messages
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ")->getResultArray();

        // API requests per day — last 7 days
        $api_activity = $db->query("
            SELECT DATE(created_at) as day, COUNT(*) as count
            FROM api_logs
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ")->getResultArray();

        // Status code distribution
        $status_dist = $db->query("
            SELECT
                SUM(CASE WHEN status_code BETWEEN 200 AND 299 THEN 1 ELSE 0 END) as s2xx,
                SUM(CASE WHEN status_code BETWEEN 300 AND 399 THEN 1 ELSE 0 END) as s3xx,
                SUM(CASE WHEN status_code BETWEEN 400 AND 499 THEN 1 ELSE 0 END) as s4xx,
                SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END) as s5xx
            FROM api_logs
        ")->getRowArray();

        // Top 5 most active users (by messages sent)
        $top_users = $db->query("
            SELECT u.id, u.name, u.username, u.photo, COUNT(m.id) as msg_count
            FROM users u
            LEFT JOIN messages m ON m.sender_id = u.id
            GROUP BY u.id
            ORDER BY msg_count DESC
            LIMIT 5
        ")->getResultArray();

        // Fill missing days with 0 for charts
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $days[] = date('Y-m-d', strtotime("-$i days"));
        }
        $reg_map  = array_column($registrations, 'count', 'day');
        $msg_map  = array_column($msg_activity,  'count', 'day');
        $api_map  = array_column($api_activity,  'count', 'day');

        $chart_labels = array_map(fn($d) => date('M d', strtotime($d)), $days);
        $chart_reg    = array_map(fn($d) => (int)($reg_map[$d]  ?? 0), $days);
        $chart_msg    = array_map(fn($d) => (int)($msg_map[$d]  ?? 0), $days);
        $chart_api    = array_map(fn($d) => (int)($api_map[$d]  ?? 0), $days);

        $data = [
            'total_users'    => $db->table('users')->countAllResults(),
            'active_users'   => $db->table('users')->where('status', 'active')->countAllResults(),
            'online_users'   => $db->table('users')->where('is_online', 1)->countAllResults(),
            'suspended'      => $db->table('users')->where('status', 'suspended')->countAllResults(),
            'total_messages' => $db->table('messages')->countAllResults(),
            'total_groups'   => $db->table('`groups`')->where('is_active', 1)->countAllResults(),
            'total_logs'     => $db->table('api_logs')->countAllResults(),
            'errors_today'   => $db->table('api_logs')
                ->where('status_code >=', 400)
                ->where('DATE(created_at)', date('Y-m-d'))
                ->countAllResults(),
            'recent_users'   => $db->table('users')
                ->select('id, name, username, email, status, is_online, created_at, photo')
                ->orderBy('created_at', 'DESC')->limit(6)->get()->getResultArray(),
            'recent_logs'    => $db->table('api_logs')
                ->select('api_logs.*, users.name as user_name')
                ->join('users', 'users.id = api_logs.user_id', 'left')
                ->orderBy('api_logs.id', 'DESC')->limit(8)->get()->getResultArray(),
            'top_users'      => $top_users,
            'status_dist'    => $status_dist ?? ['s2xx'=>0,'s3xx'=>0,'s4xx'=>0,'s5xx'=>0],
            'chart_labels'   => $chart_labels,
            'chart_reg'      => $chart_reg,
            'chart_msg'      => $chart_msg,
            'chart_api'      => $chart_api,
        ];

        return view('admin/dashboard', $data);
    }
}
