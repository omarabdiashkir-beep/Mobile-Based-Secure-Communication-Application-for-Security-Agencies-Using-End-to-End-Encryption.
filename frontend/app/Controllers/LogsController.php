<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class LogsController extends Controller
{
    private string $password = 'securecomm2026';

    public function index(): string
    {
        // Simple password gate
        $pass = $this->request->getGet('pass') ?? '';
        if ($pass !== $this->password) {
            return '<!DOCTYPE html><html><body style="font-family:monospace;padding:40px">
                <h2>API Logs — Login</h2>
                <form>
                    <input type="password" name="pass" placeholder="Password" style="padding:8px;font-size:16px">
                    <button type="submit" style="padding:8px 16px">Enter</button>
                </form></body></html>';
        }

        $db     = \Config\Database::connect();
        $method = $this->request->getGet('method') ?? '';
        $status = $this->request->getGet('status') ?? '';
        $search = $this->request->getGet('search') ?? '';
        $page   = max(1, (int)($this->request->getGet('page') ?? 1));
        $limit  = 50;
        $offset = ($page - 1) * $limit;

        $builder = $db->table('api_logs')
            ->select('api_logs.*, users.name as user_name, users.username')
            ->join('users', 'users.id = api_logs.user_id', 'left')
            ->orderBy('api_logs.id', 'DESC');

        if ($method) $builder->where('method', strtoupper($method));
        if ($status) $builder->where('status_code', (int)$status);
        if ($search) $builder->like('uri', $search);

        $total = $builder->countAllResults(false);
        $logs  = $builder->limit($limit, $offset)->get()->getResultArray();

        $totalPages = ceil($total / $limit);
        $qs = "pass={$pass}" . ($method ? "&method={$method}" : '') . ($status ? "&status={$status}" : '') . ($search ? "&search={$search}" : '');

        ob_start(); ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>API Logs</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: monospace; background: #0f0f0f; color: #e0e0e0; font-size: 13px; }
.header { background: #1a1a2e; padding: 16px 24px; display: flex; align-items: center; gap: 16px; border-bottom: 1px solid #333; }
.header h1 { color: #00d4ff; font-size: 18px; }
.header .total { color: #888; font-size: 12px; }
.filters { background: #111; padding: 12px 24px; display: flex; gap: 10px; flex-wrap: wrap; border-bottom: 1px solid #222; }
.filters input, .filters select { background: #1e1e1e; border: 1px solid #333; color: #e0e0e0; padding: 6px 10px; border-radius: 4px; font-family: monospace; font-size: 12px; }
.filters button { background: #00d4ff; color: #000; border: none; padding: 6px 14px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 12px; }
.clear { background: #333 !important; color: #e0e0e0 !important; }
table { width: 100%; border-collapse: collapse; }
th { background: #1a1a2e; color: #00d4ff; padding: 10px 12px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; position: sticky; top: 0; }
td { padding: 8px 12px; border-bottom: 1px solid #1a1a1a; vertical-align: top; max-width: 300px; word-break: break-all; }
tr:hover td { background: #1a1a1a; }
.badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; }
.s200 { background: #1a3a1a; color: #4caf50; }
.s201 { background: #1a3a1a; color: #81c784; }
.s400, .s422 { background: #3a2a1a; color: #ff9800; }
.s401, .s403 { background: #3a1a1a; color: #f44336; }
.s404 { background: #2a2a3a; color: #9e9e9e; }
.s500 { background: #3a0000; color: #ff5252; }
.mGET  { color: #4fc3f7; }
.mPOST { color: #81c784; }
.mDELETE { color: #f44336; }
.mPUT  { color: #ffb74d; }
.uri { color: #ddd; }
.user { color: #ce93d8; }
.time { color: #888; font-size: 11px; }
.body-cell { font-size: 11px; color: #aaa; max-height: 80px; overflow: hidden; }
.pagination { padding: 16px 24px; display: flex; gap: 8px; align-items: center; }
.pagination a { background: #1e1e1e; color: #00d4ff; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; }
.pagination a.active { background: #00d4ff; color: #000; }
.pagination .info { color: #888; font-size: 12px; }
</style>
</head>
<body>

<div class="header">
    <h1>⚡ API Logs</h1>
    <span class="total"><?= number_format($total) ?> total requests</span>
</div>

<div class="filters">
    <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
        <input type="hidden" name="pass" value="<?= htmlspecialchars($pass) ?>">
        <input type="text" name="search" placeholder="Search URI..." value="<?= htmlspecialchars($search) ?>">
        <select name="method">
            <option value="">All Methods</option>
            <?php foreach(['GET','POST','PUT','DELETE'] as $m): ?>
            <option value="<?= $m ?>" <?= $method === $m ? 'selected' : '' ?>><?= $m ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status">
            <option value="">All Status</option>
            <?php foreach(['200','201','400','401','403','404','422','500'] as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Filter</button>
        <a href="?pass=<?= $pass ?>" style="text-decoration:none"><button type="button" class="clear">Clear</button></a>
    </form>
</div>

<table>
<thead>
<tr>
    <th>#</th>
    <th>Time</th>
    <th>Method</th>
    <th>URI</th>
    <th>Status</th>
    <th>User</th>
    <th>IP</th>
    <th>Request</th>
    <th>Response</th>
</tr>
</thead>
<tbody>
<?php foreach ($logs as $log): ?>
<?php
    $sc = $log['status_code'];
    $sc_class = "s{$sc}";
    $method_class = "m" . $log['method'];
    $uri = parse_url($log['uri'], PHP_URL_PATH);
    $reqBody = $log['request_body'] ? json_decode($log['request_body'], true) : [];
    $resBody = $log['response_body'] ? json_decode($log['response_body'], true) : null;
    $resMsg  = $resBody['message'] ?? ($resBody['title'] ?? '');
?>
<tr>
    <td style="color:#555"><?= $log['id'] ?></td>
    <td class="time"><?= date('H:i:s', strtotime($log['created_at'])) ?><br><span style="color:#444"><?= date('d/m', strtotime($log['created_at'])) ?></span></td>
    <td><span class="<?= $method_class ?>"><?= $log['method'] ?></span></td>
    <td class="uri"><?= htmlspecialchars($uri) ?></td>
    <td><span class="badge <?= $sc_class ?>"><?= $sc ?></span></td>
    <td class="user"><?= $log['user_name'] ? htmlspecialchars($log['user_name']) . '<br><span style="color:#888">' . htmlspecialchars($log['username']) . '</span>' : '<span style="color:#555">guest</span>' ?></td>
    <td style="color:#666;font-size:11px"><?= htmlspecialchars($log['ip_address'] ?? '') ?></td>
    <td class="body-cell"><?= $reqBody ? htmlspecialchars(json_encode($reqBody, JSON_PRETTY_PRINT)) : '<span style="color:#444">—</span>' ?></td>
    <td class="body-cell"><?= $resMsg ? htmlspecialchars($resMsg) : '<span style="color:#444">—</span>' ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<div class="pagination">
    <span class="info">Page <?= $page ?> of <?= $totalPages ?> (<?= $total ?> records)</span>
    <?php if ($page > 1): ?>
    <a href="?<?= $qs ?>&page=<?= $page - 1 ?>">← Prev</a>
    <?php endif; ?>
    <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
    <a href="?<?= $qs ?>&page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?>
    <a href="?<?= $qs ?>&page=<?= $page + 1 ?>">Next →</a>
    <?php endif; ?>
</div>

</body>
</html>
<?php
        return ob_get_clean();
    }
}
