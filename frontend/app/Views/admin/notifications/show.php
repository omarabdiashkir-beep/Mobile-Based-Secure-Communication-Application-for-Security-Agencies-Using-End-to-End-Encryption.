<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notification Detail — SecureGov.so Admin</title>
<?php include dirname(__DIR__) . '/partials/style.php'; ?>
<style>
.notif-hero{background:linear-gradient(135deg,#6366f1 0%,#4f46e5 100%);color:#fff;border-radius:14px;padding:28px 32px;margin-bottom:22px}
.notif-hero h2{margin:0 0 6px;font-size:20px;font-weight:800}
.notif-hero p{margin:0;opacity:.85;font-size:14px;line-height:1.6}
.notif-hero-meta{display:flex;gap:18px;margin-top:16px;flex-wrap:wrap}
.notif-hero-meta span{font-size:12px;opacity:.9;display:flex;align-items:center;gap:6px}
.type-pill{display:inline-flex;align-items:center;padding:3px 12px;border-radius:20px;font-size:11px;font-weight:700;text-transform:capitalize}
.type-general{background:rgba(255,255,255,.2);color:#fff}
.type-alert{background:rgba(255,255,255,.2);color:#fff}
.type-announcement{background:rgba(255,255,255,.2);color:#fff}
.type-message{background:rgba(255,255,255,.2);color:#fff}
.type-update{background:rgba(255,255,255,.2);color:#fff}

.stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:22px}
@media(max-width:600px){.stats-row{grid-template-columns:1fr 1fr}}
.stat-box{background:#fff;border:1px solid #e8ecf4;border-radius:12px;padding:18px 20px}
.stat-box .num{font-size:28px;font-weight:800;line-height:1;margin-bottom:4px}
.stat-box .lbl{font-size:12px;color:#64748b;font-weight:600}
.stat-box.green .num{color:#16a34a}
.stat-box.orange .num{color:#d97706}

.rec-list{display:flex;flex-direction:column;gap:0}
.rec-row{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid #f1f5f9}
.rec-row:last-child{border-bottom:none}
.rec-av{width:36px;height:36px;border-radius:50%;background:#e0e7ff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#4338ca;flex-shrink:0;overflow:hidden}
.rec-av img{width:100%;height:100%;object-fit:cover;border-radius:50%}
.rec-name{font-weight:600;font-size:13.5px;color:#0f172a}
.rec-username{font-size:12px;color:#94a3b8}
.rec-status{margin-left:auto;display:flex;align-items:center;gap:6px;flex-shrink:0}
.badge-read{background:#dcfce7;color:#15803d;padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:700}
.badge-unread{background:#fef3c7;color:#b45309;padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:700}
.read-time{font-size:11px;color:#94a3b8;margin-left:2px}

.search-bar{margin-bottom:16px}
.search-bar input{width:100%;padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:13.5px;outline:none;transition:border .15s}
.search-bar input:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.1)}
.filter-tabs{display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap}
.filter-tab{padding:5px 14px;border-radius:20px;border:1.5px solid #e2e8f0;font-size:12px;font-weight:600;cursor:pointer;background:#fff;color:#64748b;transition:all .15s}
.filter-tab.active{background:#6366f1;border-color:#6366f1;color:#fff}
</style>
</head>
<body>
<?php include dirname(__DIR__) . '/partials/sidebar.php'; ?>
<div class="main">
<?php $pageTitle = 'Notification Detail'; include dirname(__DIR__) . '/partials/topbar.php'; ?>

<?php
$total    = count($recipients);
$readRows = array_filter($recipients, fn($r) => $r['is_read']);
$readCnt  = count($readRows);
$unreadCnt= $total - $readCnt;
$pct      = $total > 0 ? round($readCnt / $total * 100) : 0;
?>

<div class="page-header">
  <div style="display:flex;align-items:center;gap:10px">
    <a href="<?= base_url('admin/notifications') ?>" class="btn btn-ghost" style="padding:7px 10px">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    <div>
      <h2>Notification Detail</h2>
      <p class="sub">See who received and read this notification</p>
    </div>
  </div>
</div>

<!-- Hero -->
<div class="notif-hero">
  <span class="type-pill type-<?= esc($notif['type']) ?>"><?= esc($notif['type']) ?></span>
  <h2 style="margin-top:10px"><?= esc($notif['title']) ?></h2>
  <p><?= esc($notif['body']) ?></p>
  <?php if ($notif['action_url']): ?>
  <p style="margin-top:8px;font-size:12px;opacity:.7">
    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
    <?= esc($notif['action_url']) ?>
  </p>
  <?php endif; ?>
  <div class="notif-hero-meta">
    <span>
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      <?= date('M d, Y  H:i', strtotime($notif['created_at'])) ?>
    </span>
    <span>
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
      <?= number_format($total) ?> recipient<?= $total !== 1 ? 's' : '' ?>
    </span>
  </div>
</div>

<!-- Stats -->
<div class="stats-row">
  <div class="stat-box">
    <div class="num"><?= number_format($total) ?></div>
    <div class="lbl">Total Sent</div>
  </div>
  <div class="stat-box green">
    <div class="num"><?= number_format($readCnt) ?></div>
    <div class="lbl">Read (<?= $pct ?>%)</div>
  </div>
  <div class="stat-box orange">
    <div class="num"><?= number_format($unreadCnt) ?></div>
    <div class="lbl">Unread</div>
  </div>
</div>

<!-- Read progress -->
<div style="background:#f1f5f9;border-radius:8px;height:8px;margin-bottom:22px;overflow:hidden">
  <div style="height:100%;width:<?= $pct ?>%;background:linear-gradient(90deg,#6366f1,#3b82f6);border-radius:8px;transition:width .4s"></div>
</div>

<!-- Recipients list -->
<div class="card">
  <div class="card-header">
    <h3>Recipients</h3>
    <span class="card-title-count"><?= $total ?> users</span>
  </div>

  <?php if (empty($recipients)): ?>
    <div class="empty-state">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
      <p>No recipients found</p>
    </div>
  <?php else: ?>

  <!-- Filter tabs + search -->
  <div class="filter-tabs">
    <button class="filter-tab active" data-filter="all">All (<?= $total ?>)</button>
    <button class="filter-tab" data-filter="read">Read (<?= $readCnt ?>)</button>
    <button class="filter-tab" data-filter="unread">Unread (<?= $unreadCnt ?>)</button>
  </div>
  <div class="search-bar">
    <input type="text" id="recSearch" placeholder="Search by name or username...">
  </div>

  <div class="rec-list" id="recList">
    <?php foreach ($recipients as $r): ?>
    <div class="rec-row" data-read="<?= $r['is_read'] ?>" data-name="<?= strtolower(esc($r['name'])) ?> <?= strtolower(esc($r['username'])) ?>">
      <div class="rec-av"><?= strtoupper(mb_substr($r['name'], 0, 1)) ?></div>
      <div>
        <div class="rec-name"><?= esc($r['name']) ?></div>
        <div class="rec-username">@<?= esc($r['username']) ?></div>
      </div>
      <div class="rec-status">
        <?php if ($r['is_read']): ?>
          <span class="badge-read">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle"><polyline points="20 6 9 17 4 12"/></svg>
            Read
          </span>
          <?php if ($r['read_at']): ?>
          <span class="read-time"><?= date('M d, H:i', strtotime($r['read_at'])) ?></span>
          <?php endif; ?>
        <?php else: ?>
          <span class="badge-unread">Unread</span>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php endif; ?>
</div>

</div></div>

<script>
// Filter tabs
document.querySelectorAll('.filter-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    filterRows();
  });
});

// Search
document.getElementById('recSearch')?.addEventListener('input', filterRows);

function filterRows() {
  const filter = document.querySelector('.filter-tab.active')?.dataset.filter ?? 'all';
  const q      = document.getElementById('recSearch').value.toLowerCase();
  document.querySelectorAll('.rec-row').forEach(row => {
    const matchFilter = filter === 'all'
      || (filter === 'read'   && row.dataset.read === '1')
      || (filter === 'unread' && row.dataset.read === '0');
    const matchSearch = !q || row.dataset.name.includes(q);
    row.style.display = (matchFilter && matchSearch) ? '' : 'none';
  });
}
</script>
</body>
</html>
