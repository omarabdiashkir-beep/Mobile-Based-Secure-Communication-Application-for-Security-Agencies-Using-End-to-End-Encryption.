<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>API Logs — SecureGov.so Admin</title>
<?php include __DIR__ . '/partials/style.php'; ?>
<style>
.log-row{cursor:pointer;transition:background .1s}
.log-row:hover td{background:#f5f7ff!important}
.log-row.expanded td{background:#f8f9ff!important}
.log-detail{display:none;background:#f8fafc}
.log-detail.open{display:table-row}
.log-detail>td{padding:16px 20px!important;border-bottom:2px solid #e8ecf4!important;border-top:none!important}
.expand-icon{width:20px;height:20px;border-radius:50%;background:#f0f2f8;display:inline-flex;align-items:center;justify-content:center;color:#64748b;flex-shrink:0;transition:all .2s}
.log-row.expanded .expand-icon{background:#6366f1;color:#fff;transform:rotate(180deg)}
</style>
</head>
<body>
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="main">
<?php $pageTitle = 'API Logs'; include __DIR__ . '/partials/topbar.php'; ?>

<div class="page-header">
  <div>
    <h2>API Logs</h2>
    <p class="sub">Monitor all API requests and responses</p>
  </div>
  <span class="badge badge-indigo" style="font-size:12px;padding:6px 12px"><?= number_format($total) ?> entries</span>
</div>

<div class="card" style="margin-bottom:16px">
  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <input type="text" name="uri" class="form-control" placeholder="Filter by URI..." value="<?= esc($uri) ?>" style="max-width:220px;margin:0">
    <select name="method" class="form-control" style="max-width:120px;margin:0">
      <option value="">All Methods</option>
      <?php foreach (['GET','POST','PUT','DELETE','PATCH'] as $m): ?>
        <option value="<?= $m ?>" <?= $method===$m?'selected':'' ?>><?= $m ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status" class="form-control" style="max-width:150px;margin:0">
      <option value="">All Statuses</option>
      <option value="2xx" <?= $status==='2xx'?'selected':'' ?>>2xx Success</option>
      <option value="4xx" <?= $status==='4xx'?'selected':'' ?>>4xx Errors</option>
      <option value="5xx" <?= $status==='5xx'?'selected':'' ?>>5xx Server Errors</option>
    </select>
    <?php if ($userId): ?><input type="hidden" name="user_id" value="<?= (int)$userId ?>"><?php endif; ?>
    <button type="submit" class="btn btn-primary">Apply</button>
    <a href="<?= base_url('admin/logs') ?>" class="btn btn-ghost">Reset</a>
  </form>
</div>

<?php if ($userId): ?>
<div class="alert alert-info" style="margin-bottom:16px">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
  Showing logs for user ID <?= (int)$userId ?>. <a href="<?= base_url('admin/logs') ?>">View all logs</a>
</div>
<?php endif; ?>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:36px"></th>
          <th>#</th>
          <th>Method</th>
          <th>URI</th>
          <th>Status</th>
          <th>User</th>
          <th>IP</th>
          <th>ms</th>
          <th>Time</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($logs as $log):
        $sc = (int)$log['status_code'];
        $sc_cls = $sc>=500?'badge-red':($sc>=400?'badge-yellow':($sc>=300?'badge-blue':'badge-green'));
        $m = strtolower($log['method'] ?? 'get');
        $hasDetail = ($log['request_body'] ?? null) || ($log['response_body'] ?? null);
      ?>
      <tr class="log-row <?= $hasDetail?'':'no-detail' ?>" id="r<?= $log['id'] ?>" <?= $hasDetail?'onclick="toggleLog('.$log['id'].')"':'' ?>>
        <td style="text-align:center;padding:13px 6px">
          <?php if ($hasDetail): ?>
          <span class="expand-icon" id="ei<?= $log['id'] ?>">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="6 9 12 15 18 9"/></svg>
          </span>
          <?php endif; ?>
        </td>
        <td style="color:#94a3b8;font-size:11.5px;font-weight:500"><?= $log['id'] ?></td>
        <td><span class="m-<?= $m ?>"><?= strtoupper($m) ?></span></td>
        <td style="font-size:12.5px;color:#475569;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= esc($log['uri']) ?>"><?= esc($log['uri']) ?></td>
        <td><span class="badge <?= $sc_cls ?>"><?= $sc ?></span></td>
        <td style="font-size:12.5px">
          <?php if ($log['user_name']): ?>
            <a href="<?= base_url('admin/users/'.$log['user_id']) ?>" style="font-weight:500"><?= esc($log['user_name']) ?></a>
          <?php else: ?><span style="color:#cbd5e1">—</span><?php endif; ?>
        </td>
        <td data-ip="<?= esc($log['ip_address'] ?? '') ?>" style="font-size:11.5px;color:#94a3b8;font-family:'Courier New',monospace;min-width:130px">
          <span><?= esc($log['ip_address'] ?? '—') ?></span>
          <span class="ip-geo" style="display:block;font-size:10.5px;color:#64748b;font-family:'Inter',sans-serif;margin-top:2px"></span>
        </td>
        <td style="font-size:12px;color:#64748b"><?= ($log['response_time'] ?? null) ? $log['response_time'].'ms' : '—' ?></td>
        <td style="font-size:11.5px;color:#94a3b8;white-space:nowrap"><?= date('M d H:i:s', strtotime($log['created_at'])) ?></td>
      </tr>
      <?php if ($hasDetail): ?>
      <tr class="log-detail" id="d<?= $log['id'] ?>">
        <td colspan="9">
          <div style="display:grid;grid-template-columns:<?= ($log['request_body']??null)&&($log['response_body']??null)?'1fr 1fr':'1fr' ?>;gap:16px">
            <?php if ($log['request_body'] ?? null): ?>
            <div>
              <div style="font-size:10.5px;color:#f59e0b;font-weight:700;margin-bottom:8px;text-transform:uppercase;letter-spacing:1.2px;display:flex;align-items:center;gap:6px">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="7 16 12 11 17 16"/><polyline points="7 8 12 13 17 8"/></svg>
                Request Body
              </div>
              <div class="code-block"><?= esc($log['request_body']) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($log['response_body'] ?? null): ?>
            <div>
              <div style="font-size:10.5px;color:#3b82f6;font-weight:700;margin-bottom:8px;text-transform:uppercase;letter-spacing:1.2px;display:flex;align-items:center;gap:6px">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="7 16 12 11 17 16"/><polyline points="7 8 12 13 17 8"/></svg>
                Response Body
              </div>
              <div class="code-block"><?= esc($log['response_body']) ?></div>
            </div>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endif; ?>
      <?php endforeach; ?>
      <?php if (empty($logs)): ?>
      <tr><td colspan="9">
        <div class="empty-state">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          <p>No logs found</p>
          <small>Try adjusting your filters</small>
        </div>
      </td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$pages = ceil($total / $limit);
if ($pages > 1):
  $qs = http_build_query(array_filter(['method'=>$method,'status'=>$status,'uri'=>$uri,'user_id'=>$userId]));
  $qs = $qs ? '&'.$qs : '';
?>
<div class="pagination">
  <?php
  $s=max(1,$page-4); $e=min($pages,$page+4);
  if($s>1) echo '<a href="?page=1'.$qs.'" class="page-btn">1</a><span class="page-dots">…</span>';
  for($i=$s;$i<=$e;$i++) echo '<a href="?page='.$i.$qs.'" class="page-btn '.($i===$page?'active':'').'">'.$i.'</a>';
  if($e<$pages) echo '<span class="page-dots">…</span><a href="?page='.$pages.$qs.'" class="page-btn">'.$pages.'</a>';
  ?>
</div>
<?php endif; ?>

</div></div>
<script>
function toggleLog(id) {
  var row = document.getElementById('r'+id);
  var detail = document.getElementById('d'+id);
  var isOpen = detail.classList.contains('open');
  detail.classList.toggle('open', !isOpen);
  row.classList.toggle('expanded', !isOpen);
}

// IP geolocation — batch fetch unique IPs, update cells lazily
(function() {
  const cells = document.querySelectorAll('[data-ip]');
  if (!cells.length) return;

  // collect unique non-empty IPs
  const ipMap = {}; // ip -> [geo span elements]
  cells.forEach(cell => {
    const ip = cell.dataset.ip;
    if (!ip || ip === '127.0.0.1' || ip === '::1') return;
    if (!ipMap[ip]) ipMap[ip] = [];
    ipMap[ip].push(cell.querySelector('.ip-geo'));
  });

  const uniqueIps = Object.keys(ipMap);
  if (!uniqueIps.length) return;

  // fetch all in parallel
  Promise.all(uniqueIps.map(ip =>
    fetch('https://ipwho.is/' + ip)
      .then(r => r.json())
      .then(d => ({ ip, d }))
      .catch(() => ({ ip, d: null }))
  )).then(results => {
    results.forEach(({ ip, d }) => {
      if (!d || !d.success) return;
      const flag   = d.flag?.emoji || '';
      const city   = d.city || '';
      const country = d.country || '';
      const label  = [flag, city, country].filter(Boolean).join(' ');
      ipMap[ip].forEach(span => { if (span) span.textContent = label; });
    });
  });
})();
</script>
</body>
</html>
