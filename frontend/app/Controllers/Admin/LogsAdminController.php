<?php

namespace App\Controllers\Admin;

class LogsAdminController extends BaseAdminController
{
    public function index()
    {
        if ($r = $this->requireAuth()) return $r;

        $db      = $this->db();
        $method  = $this->request->getGet('method') ?? '';
        $status  = $this->request->getGet('status') ?? '';
        $uri     = $this->request->getGet('uri') ?? '';
        $userId  = $this->request->getGet('user_id') ?? '';
        $page    = max(1, (int)($this->request->getGet('page') ?? 1));
        $limit   = 50;
        $offset  = ($page - 1) * $limit;

        $builder = $db->table('api_logs')
            ->select('api_logs.*, users.name as user_name')
            ->join('users', 'users.id = api_logs.user_id', 'left')
            ->orderBy('api_logs.id', 'DESC');

        if ($method)  $builder->where('api_logs.method', strtoupper($method));
        if ($uri)     $builder->like('api_logs.uri', $uri);
        if ($userId)  $builder->where('api_logs.user_id', (int)$userId);
        if ($status === '2xx') $builder->where('api_logs.status_code >=', 200)->where('api_logs.status_code <', 300);
        elseif ($status === '4xx') $builder->where('api_logs.status_code >=', 400)->where('api_logs.status_code <', 500);
        elseif ($status === '5xx') $builder->where('api_logs.status_code >=', 500);
        elseif (is_numeric($status)) $builder->where('api_logs.status_code', (int)$status);

        $total = $builder->countAllResults(false);
        $logs  = $builder->limit($limit, $offset)->get()->getResultArray();

        return view('admin/logs', compact('logs', 'total', 'page', 'limit', 'method', 'status', 'uri', 'userId'));
    }
}
