<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — SecureGov.so Admin</title>
<?php include __DIR__ . '/partials/style.php'; ?>
<style>
/* ── Dashboard: override content padding ── */
.content { padding: 0 !important; overflow-y: auto; }

/* ── Hero banner ── */
.dash-hero {
  background: linear-gradient(135deg, #0f2545 0%, #1a3a6b 40%, #2563eb 100%);
  padding: 28px 32px 80px;
  position: relative;
  overflow: hidden;
}
.dash-hero::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,.07) 1px, transparent 0);
  background-size: 32px 32px;
}
.dash-hero::after {
  content: '';
  position: absolute;
  bottom: -2px; left: 0; right: 0;
  height: 40px;
  background: #eef2f7;
  border-radius: 50% 50% 0 0 / 20px 20px 0 0;
}
.hero-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  position: relative;
  z-index: 1;
}
.hero-title { color: #fff; font-size: 22px; font-weight: 800; display: flex; align-items: center; gap: 10px; }
.hero-title svg { opacity: .85; }
.hero-sub { color: rgba(255,255,255,.55); font-size: 13px; margin-top: 4px; }
.hero-right { display: flex; align-items: center; gap: 12px; }
.live-badge {
  display: flex; align-items: center; gap: 6px;
  background: rgba(255,255,255,.12);
  border: 1px solid rgba(255,255,255,.18);
  padding: 5px 13px; border-radius: 20px;
  color: #fff; font-size: 12px; font-weight: 600;
}
.live-dot { width: 7px; height: 7px; border-radius: 50%; background: #4ade80; animation: pulse 2s infinite; }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
.date-badge {
  background: rgba(255,255,255,.12);
  border: 1px solid rgba(255,255,255,.18);
  padding: 5px 13px; border-radius: 20px;
  color: rgba(255,255,255,.85); font-size: 12px; font-weight: 500;
}

/* ── KPI Cards row (floating over hero) ── */
.kpi-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  padding: 0 32px;
  margin-top: -56px;
  position: relative;
  z-index: 10;
}
@media(max-width:1100px) { .kpi-row { grid-template-columns: repeat(2,1fr); } }
@media(max-width:600px)  { .kpi-row { grid-template-columns: 1fr; padding: 0 16px; } }

.kpi-card {
  background: #fff;
  border-radius: 18px;
  padding: 20px 22px 16px;
  box-shadow: 0 4px 24px rgba(15,37,69,.10), 0 1px 4px rgba(15,37,69,.06);
  border: 1px solid rgba(226,232,240,.8);
  display: flex; flex-direction: column; gap: 0;
  transition: transform .15s, box-shadow .15s;
}
.kpi-card:hover { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(15,37,69,.13); }

.kpi-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
.kpi-icon-wrap {
  width: 40px; height: 40px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
}
.kpi-icon-wrap svg { width: 20px; height: 20px; }
.kpi-badge {
  display: flex; align-items: center; gap: 5px;
  font-size: 11.5px; font-weight: 700;
  padding: 4px 10px; border-radius: 20px;
}

.kpi-value { font-size: 32px; font-weight: 800; line-height: 1; margin-bottom: 4px; }
.kpi-label { font-size: 10.5px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: #94a3b8; margin-bottom: 14px; }

.kpi-divider { height: 1px; background: #f1f5f9; margin-bottom: 12px; }
.kpi-footer { display: flex; align-items: center; justify-content: space-between; }
.kpi-footer-label { font-size: 12px; color: #94a3b8; }
.kpi-footer-val { font-size: 12.5px; font-weight: 700; }

/* Card accent colors */
.kc-blue  .kpi-icon-wrap { background: #eff6ff; color: #3b82f6; }
.kc-blue  .kpi-badge     { background: #eff6ff; color: #3b82f6; }
.kc-blue  .kpi-value     { color: #2563eb; }
.kc-blue  .kpi-footer-val{ color: #3b82f6; }

.kc-red   .kpi-icon-wrap { background: #fff1f2; color: #f43f5e; }
.kc-red   .kpi-badge     { background: #fff1f2; color: #f43f5e; }
.kc-red   .kpi-value     { color: #f43f5e; }
.kc-red   .kpi-footer-val{ color: #f43f5e; }

.kc-green .kpi-icon-wrap { background: #f0fdf4; color: #10b981; }
.kc-green .kpi-badge     { background: #f0fdf4; color: #10b981; }
.kc-green .kpi-value     { color: #10b981; }
.kc-green .kpi-footer-val{ color: #10b981; }

.kc-purple.kpi-icon-wrap { background: #faf5ff; color: #8b5cf6; }
.kc-purple .kpi-icon-wrap{ background: #faf5ff; color: #8b5cf6; }
.kc-purple .kpi-badge    { background: #faf5ff; color: #8b5cf6; }
.kc-purple .kpi-value    { color: #8b5cf6; }
.kc-purple .kpi-footer-val{ color: #8b5cf6; }

/* ── Dashboard body ── */
.dash-body { padding: 24px 32px 32px; background: #eef2f7; }
@media(max-width:600px) { .dash-body { padding: 16px; } }

/* ── Chart cards ── */
.chart-row { display: grid; grid-template-columns: 3fr 2fr; gap: 20px; margin-bottom: 20px; }
@media(max-width:960px) { .chart-row { grid-template-columns: 1fr; } }

.dash-card {
  background: #fff;
  border-radius: 16px;
  padding: 22px 24px;
  box-shadow: 0 1px 6px rgba(15,37,69,.06);
  border: 1px solid #e8edf4;
}
.dash-card-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 4px; }
.dash-card-title { font-size: 15px; font-weight: 700; color: #0f2545; }
.dash-card-sub { font-size: 12px; color: #94a3b8; margin-top: 2px; }
.chart-legend { display: flex; gap: 14px; flex-wrap: wrap; }
.legend-pill { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: #475569; }
.legend-dot { width: 10px; height: 10px; border-radius: 50%; }

.chart-area { height: 200px; margin-top: 18px; position: relative; }
.donut-area { display: flex; flex-direction: column; align-items: center; gap: 14px; margin-top: 10px; }
.donut-canvas { max-width: 170px; max-height: 170px; }

.zone-list { width: 100%; }
.zone-item { display: flex; align-items: center; gap: 10px; padding: 6px 0; font-size: 13px; color: #475569; }
.zone-dot  { width: 12px; height: 12px; border-radius: 3px; flex-shrink: 0; }
.zone-pct  { margin-left: auto; font-weight: 700; color: #0f2545; }

/* ── Bottom tables ── */
.table-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media(max-width:768px) { .table-row { grid-template-columns: 1fr; } }

.dash-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 16px; }
.dash-table th { padding: 8px 12px; text-align: left; font-size: 10.5px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .7px; border-bottom: 2px solid #f1f5f9; background: #fafbfc; }
.dash-table td { padding: 11px 12px; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
.dash-table tr:last-child td { border-bottom: none; }
.dash-table tbody tr:hover td { background: #f8fafc; }

/* status pills */
.spill { display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.spill-green  { background: #d1fae5; color: #065f46; }
.spill-blue   { background: #dbeafe; color: #1e40af; }
.spill-yellow { background: #fef3c7; color: #92400e; }
.spill-red    { background: #fee2e2; color: #991b1b; }

/* icon box for card header */
.card-icon { width: 36px; height: 36px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.card-icon svg { width: 18px; height: 18px; }
</style>
</head>
<body>
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="main">
<?php $pageTitle = 'Dashboard'; include __DIR__ . '/partials/topbar.php'; ?>

<?php
$online_pct = $total_users > 0 ? round($online_users / $total_users * 100) : 0;
$active_pct  = $total_users > 0 ? round($active_users / $total_users * 100) : 0;
$today_users = end($chart_reg) ?: 0;
$today_msgs  = end($chart_msg) ?: 0;
$today_api   = end($chart_api) ?: 0;
?>

<!-- ── Hero Banner ── -->
<div class="dash-hero">
  <div class="hero-top">
    <div>
      <div class="hero-title">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        SecureGov.so Admin
      </div>
      <div class="hero-sub"><?= date('l, F j, Y') ?> &nbsp;&middot;&nbsp; Live operational overview</div>
    </div>
    <div class="hero-right">
      <div class="live-badge"><span class="live-dot"></span> Live</div>
      <div class="date-badge"><?= date('M d, Y') ?></div>
    </div>
  </div>
</div>

<!-- ── KPI Cards ── -->
<div class="kpi-row">

  <!-- Total Users -->
  <div class="kpi-card kc-blue">
    <div class="kpi-top">
      <div class="kpi-icon-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      </div>
      <div class="kpi-badge">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
        This Month
      </div>
    </div>
    <div class="kpi-value"><?= number_format($total_users) ?></div>
    <div class="kpi-label">Total Users</div>
    <div class="kpi-divider"></div>
    <div class="kpi-footer">
      <span class="kpi-footer-label">New Today</span>
      <span class="kpi-footer-val"><?= $today_users ?> users</span>
    </div>
  </div>

  <!-- Suspended -->
  <div class="kpi-card kc-red">
    <div class="kpi-top">
      <div class="kpi-icon-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
      </div>
      <div class="kpi-badge">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <?= number_format($suspended) ?> suspended
      </div>
    </div>
    <div class="kpi-value"><?= number_format($errors_today) ?></div>
    <div class="kpi-label">Errors Today</div>
    <div class="kpi-divider"></div>
    <div class="kpi-footer">
      <span class="kpi-footer-label">Suspended Accounts</span>
      <span class="kpi-footer-val"><?= number_format($suspended) ?></span>
    </div>
  </div>

  <!-- Messages -->
  <div class="kpi-card kc-green">
    <div class="kpi-top">
      <div class="kpi-icon-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      </div>
      <div class="kpi-badge">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        msgs
      </div>
    </div>
    <div class="kpi-value"><?= number_format($total_messages) ?></div>
    <div class="kpi-label">Total Messages</div>
    <div class="kpi-divider"></div>
    <div class="kpi-footer">
      <span class="kpi-footer-label">Today's Messages</span>
      <span class="kpi-footer-val"><?= $today_msgs ?></span>
    </div>
  </div>

  <!-- Groups / Users Online -->
  <div class="kpi-card kc-purple">
    <div class="kpi-top">
      <div class="kpi-icon-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      </div>
      <div class="kpi-badge">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/></svg>
        Active
      </div>
    </div>
    <div class="kpi-value"><?= number_format($active_users) ?></div>
    <div class="kpi-label">Active Users</div>
    <div class="kpi-divider"></div>
    <div class="kpi-footer">
      <span class="kpi-footer-label">Online Now</span>
      <span class="kpi-footer-val"><?= number_format($online_users) ?></span>
    </div>
  </div>

</div>

<!-- ── Dashboard body ── -->
<div class="dash-body">

  <!-- Charts row -->
  <div class="chart-row">

    <!-- Line / Bar chart -->
    <div class="dash-card">
      <div class="dash-card-header">
        <div>
          <div class="dash-card-title">User Registrations vs Messages</div>
          <div class="dash-card-sub">Last 7 days comparison</div>
        </div>
        <div class="chart-legend">
          <div class="legend-pill"><span class="legend-dot" style="background:#3b82f6"></span> Users</div>
          <div class="legend-pill"><span class="legend-dot" style="background:#10b981"></span> Messages</div>
          <div class="legend-pill"><span class="legend-dot" style="background:#8b5cf6"></span> API</div>
        </div>
      </div>
      <div class="chart-area"><canvas id="barChart"></canvas></div>
    </div>

    <!-- Donut -->
    <div class="dash-card">
      <div class="dash-card-header">
        <div>
          <div class="dash-card-title">API Response Codes</div>
          <div class="dash-card-sub">Distribution overview</div>
        </div>
        <div class="card-icon" style="background:#eff6ff;color:#3b82f6">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
      </div>
      <div class="donut-area">
        <canvas id="donutChart" class="donut-canvas"></canvas>
        <div class="zone-list">
          <?php
          $sd = $status_dist;
          $total_req = max(1, ($sd['s2xx']??0)+($sd['s3xx']??0)+($sd['s4xx']??0)+($sd['s5xx']??0));
          $zones = [
            ['2xx Success',      $sd['s2xx']??0, '#4ade80'],
            ['3xx Redirect',     $sd['s3xx']??0, '#60a5fa'],
            ['4xx Client Error', $sd['s4xx']??0, '#f59e0b'],
            ['5xx Server Error', $sd['s5xx']??0, '#f87171'],
          ];
          foreach ($zones as [$lbl, $val, $clr]):
            $pct = $total_req > 0 ? round($val/$total_req*100,1) : 0;
          ?>
          <div class="zone-item">
            <span class="zone-dot" style="background:<?= $clr ?>"></span>
            <span><?= $lbl ?></span>
            <span class="zone-pct"><?= $pct ?>%</span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

  </div>

  <!-- Bottom tables -->
  <div class="table-row">

    <!-- Recent Users -->
    <div class="dash-card">
      <div class="dash-card-header">
        <div>
          <div class="dash-card-title">Recent Registrations</div>
          <div class="dash-card-sub">Latest 6 new accounts</div>
        </div>
        <a href="<?= base_url('admin/users') ?>" class="btn btn-ghost btn-sm">View All</a>
      </div>
      <table class="dash-table">
        <thead><tr><th>User</th><th>Status</th><th>Joined</th></tr></thead>
        <tbody>
        <?php foreach ($recent_users as $u): ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px">
              <div class="avatar" style="width:32px;height:32px;font-size:13px">
                <?php if ($u['photo']): ?><img src="<?= base_url('uploads/'.$u['photo']) ?>"><?php else: ?><?= strtoupper(substr($u['name'],0,1)) ?><?php endif; ?>
              </div>
              <div>
                <div style="font-weight:600;color:#0f2545;font-size:13px"><?= esc($u['name']) ?></div>
                <div style="color:#94a3b8;font-size:11px">@<?= esc($u['username']) ?></div>
              </div>
            </div>
          </td>
          <td>
            <?php if ($u['is_online']): ?>
              <span class="spill spill-green">&#9679; Online</span>
            <?php elseif ($u['status']==='active'): ?>
              <span class="spill spill-blue">Active</span>
            <?php else: ?>
              <span class="spill spill-red"><?= esc($u['status']) ?></span>
            <?php endif; ?>
          </td>
          <td style="color:#94a3b8;font-size:12px;white-space:nowrap"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Recent API Logs -->
    <div class="dash-card">
      <div class="dash-card-header">
        <div>
          <div class="dash-card-title">Recent API Activity</div>
          <div class="dash-card-sub">Latest requests</div>
        </div>
        <a href="<?= base_url('admin/logs') ?>" class="btn btn-ghost btn-sm">View All</a>
      </div>
      <table class="dash-table">
        <thead><tr><th>Method</th><th>Endpoint</th><th>Status</th><th>Time</th></tr></thead>
        <tbody>
        <?php foreach ($recent_logs as $log):
          $sc  = (int)$log['status_code'];
          $sc_cls = $sc>=500?'spill-red':($sc>=400?'spill-yellow':($sc>=300?'spill-blue':'spill-green'));
          $mc  = 'method-'.strtolower($log['method'] ?? 'get');
        ?>
        <tr>
          <td><span class="<?= $mc ?>"><?= esc($log['method']) ?></span></td>
          <td style="font-size:12px;color:#64748b;max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= esc($log['uri']) ?>"><?= esc($log['uri']) ?></td>
          <td><span class="spill <?= $sc_cls ?>"><?= $sc ?></span></td>
          <td style="font-size:11px;color:#94a3b8;white-space:nowrap"><?= date('H:i:s', strtotime($log['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($recent_logs)): ?>
          <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:28px">No logs yet.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>

  <!-- Top active users -->
  <div class="dash-card" style="margin-top:20px">
    <div class="dash-card-header">
      <div>
        <div class="dash-card-title">Most Active Users</div>
        <div class="dash-card-sub">Ranked by messages sent</div>
      </div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;margin-top:16px">
    <?php
    $max_msgs = max(1, max(array_column($top_users ?: [[0,'','','',0]], 'msg_count') ?: [1]));
    $rank_colors = ['#f59e0b','#94a3b8','#ea580c','#3b82f6','#8b5cf6'];
    foreach ($top_users as $i => $u):
      $barW = round($u['msg_count']/$max_msgs*100);
      $clr  = $rank_colors[$i] ?? '#3b82f6';
    ?>
    <div style="background:#f8fafc;border-radius:12px;padding:14px 16px;border:1px solid #f1f5f9">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
        <div style="width:28px;height:28px;border-radius:50%;background:<?= $clr ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;flex-shrink:0"><?= $i+1 ?></div>
        <div class="avatar" style="width:30px;height:30px;font-size:12px;flex-shrink:0"><?php if ($u['photo']): ?><img src="<?= base_url('uploads/'.$u['photo']) ?>"><?php else: ?><?= strtoupper(substr($u['name'],0,1)) ?><?php endif; ?></div>
        <div style="min-width:0">
          <div style="font-weight:700;font-size:13px;color:#0f2545;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= esc($u['name']) ?></div>
          <div style="color:#94a3b8;font-size:11px">@<?= esc($u['username']) ?></div>
        </div>
        <div style="margin-left:auto;font-size:15px;font-weight:800;color:<?= $clr ?>"><?= number_format($u['msg_count']) ?></div>
      </div>
      <div style="height:5px;background:#e2e8f0;border-radius:4px;overflow:hidden">
        <div style="height:100%;width:<?= $barW ?>%;background:<?= $clr ?>;border-radius:4px;transition:width .6s ease"></div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($top_users)): ?>
      <p style="color:#94a3b8;font-size:13px;grid-column:1/-1;text-align:center;padding:20px 0">No data yet.</p>
    <?php endif; ?>
    </div>
  </div>

</div><!-- /dash-body -->
</div></div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif";
Chart.defaults.color = '#94a3b8';

// Bar chart — 7-day activity
new Chart(document.getElementById('barChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($chart_labels) ?>,
    datasets: [
      {
        label: 'New Users',
        data: <?= json_encode($chart_reg) ?>,
        backgroundColor: 'rgba(59,130,246,.75)',
        borderRadius: 6, borderSkipped: false
      },
      {
        label: 'Messages',
        data: <?= json_encode($chart_msg) ?>,
        backgroundColor: 'rgba(16,185,129,.75)',
        borderRadius: 6, borderSkipped: false
      },
      {
        label: 'API Requests',
        data: <?= json_encode($chart_api) ?>,
        backgroundColor: 'rgba(139,92,246,.75)',
        borderRadius: 6, borderSkipped: false
      }
    ]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#0f2545', titleColor: '#93c5fd',
        bodyColor: '#e2e8f0', padding: 12, cornerRadius: 8
      }
    },
    scales: {
      x: { grid: { display: false }, ticks: { font: { size: 11 } } },
      y: {
        beginAtZero: true,
        grid: { color: 'rgba(226,232,240,.7)', drawBorder: false },
        ticks: { font: { size: 11 }, precision: 0 }
      }
    }
  }
});

// Donut chart
new Chart(document.getElementById('donutChart'), {
  type: 'doughnut',
  data: {
    labels: ['2xx','3xx','4xx','5xx'],
    datasets: [{
      data: [
        <?= (int)($status_dist['s2xx']??0) ?>,
        <?= (int)($status_dist['s3xx']??0) ?>,
        <?= (int)($status_dist['s4xx']??0) ?>,
        <?= (int)($status_dist['s5xx']??0) ?>
      ],
      backgroundColor: ['#4ade80','#60a5fa','#f59e0b','#f87171'],
      borderWidth: 3, borderColor: '#fff', hoverOffset: 8
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: true,
    cutout: '65%',
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#0f2545', titleColor: '#93c5fd',
        bodyColor: '#e2e8f0', padding: 10, cornerRadius: 8
      }
    }
  }
});
</script>
</body>
</html>
