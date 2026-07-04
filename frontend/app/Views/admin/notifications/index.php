<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications — SecureGov.so Admin</title>
<?php include dirname(__DIR__) . '/partials/style.php'; ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
.notif-grid{display:grid;grid-template-columns:420px 1fr;gap:22px;align-items:start}
@media(max-width:900px){.notif-grid{grid-template-columns:1fr}}
.target-section{display:none}.target-section.show{display:block}
.type-pill{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700}
.type-general{background:#e0e7ff;color:#4338ca}
.type-alert{background:#fee2e2;color:#b91c1c}
.type-message{background:#dcfce7;color:#15803d}
.type-announcement{background:#fef9c3;color:#854d0e}
.type-update{background:#e0f2fe;color:#0369a1}
.notif-card{border:1px solid #e8ecf4;border-radius:12px;padding:16px 18px;margin-bottom:10px;background:#fff;transition:box-shadow .15s}
.notif-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.06)}
.notif-card-head{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px}
.notif-card-title{font-weight:700;font-size:13.5px;color:#0f172a}
.notif-card-body{font-size:12.5px;color:#64748b;line-height:1.5;margin-bottom:8px}
.notif-card-meta{display:flex;align-items:center;gap:10px;font-size:11.5px;color:#94a3b8;flex-wrap:wrap}
.read-bar{height:4px;background:#f1f5f9;border-radius:4px;overflow:hidden;margin-top:8px}
.read-fill{height:100%;background:linear-gradient(90deg,#6366f1,#3b82f6);border-radius:4px;transition:width .4s}
/* Select2 overrides */
.select2-container--default .select2-selection--multiple{border:1.5px solid #e2e8f0!important;border-radius:9px!important;min-height:42px!important;padding:4px 8px!important}
.select2-container--default.select2-container--focus .select2-selection--multiple{border-color:#6366f1!important;box-shadow:0 0 0 3px rgba(99,102,241,.12)!important}
.select2-container--default .select2-selection--multiple .select2-selection__choice{background:#e0e7ff;border-color:#c7d2fe;color:#4338ca;border-radius:6px;padding:1px 8px;font-size:12px}
</style>
</head>
<body>
<?php include dirname(__DIR__) . '/partials/sidebar.php'; ?>
<div class="main">
<?php $pageTitle = 'Notifications'; include dirname(__DIR__) . '/partials/topbar.php'; ?>

<div class="page-header">
  <div>
    <h2>Push Notifications</h2>
    <p class="sub">Send notifications to users, groups, or everyone</p>
  </div>
</div>

<div class="notif-grid">

  <!-- ── Compose form ── -->
  <div>
    <div class="card">
      <div class="card-header"><h3>Compose Notification</h3></div>

      <?php if (session('send_error')): ?>
        <div class="alert alert-error" style="margin-bottom:14px">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
          <?= esc(session('send_error')) ?>
        </div>
      <?php endif; ?>

      <form method="post" action="<?= base_url('admin/notifications/send') ?>" id="notifForm">

        <div class="form-group">
          <label>Title *</label>
          <input type="text" name="title" class="form-control" required placeholder="e.g. System Maintenance" maxlength="255">
        </div>

        <div class="form-group">
          <label>Message *</label>
          <textarea name="body" class="form-control" rows="4" required placeholder="Notification message..." style="resize:vertical"></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Type</label>
            <select name="type" class="form-control">
              <option value="general">General</option>
              <option value="alert">Alert</option>
              <option value="message">Message</option>
              <option value="announcement">Announcement</option>
              <option value="update">Update</option>
            </select>
          </div>
          <div class="form-group">
            <label>Action URL <span style="color:#94a3b8;font-weight:400">(optional)</span></label>
            <input type="text" name="action_url" class="form-control" placeholder="e.g. /screen/profile">
          </div>
        </div>

        <!-- Target selector -->
        <div class="form-group">
          <label>Send To *</label>
          <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px" id="targetPicker">
            <label class="target-btn" data-val="user">
              <input type="radio" name="target" value="user" style="display:none">
              <div class="target-opt">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>
                <span>One User</span>
              </div>
            </label>
            <label class="target-btn" data-val="users">
              <input type="radio" name="target" value="users" style="display:none">
              <div class="target-opt">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span>Multiple</span>
              </div>
            </label>
            <label class="target-btn" data-val="group">
              <input type="radio" name="target" value="group" style="display:none">
              <div class="target-opt">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <span>Group</span>
              </div>
            </label>
            <label class="target-btn" data-val="all">
              <input type="radio" name="target" value="all" style="display:none">
              <div class="target-opt">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                <span>Everyone</span>
              </div>
            </label>
          </div>
        </div>

        <!-- Dynamic target inputs -->
        <div class="target-section" id="sec-user">
          <div class="form-group">
            <label>Select User</label>
            <select name="user_id" class="form-control" id="selUser">
              <option value="">Choose user...</option>
              <?php foreach ($users as $u): ?>
              <option value="<?= $u['id'] ?>"><?= esc($u['name']) ?> (@<?= esc($u['username']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="target-section" id="sec-users">
          <div class="form-group">
            <label>Select Users</label>
            <select name="user_ids[]" class="form-control" id="selUsers" multiple>
              <?php foreach ($users as $u): ?>
              <option value="<?= $u['id'] ?>"><?= esc($u['name']) ?> (@<?= esc($u['username']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="target-section" id="sec-group">
          <div class="form-group">
            <label>Select Group</label>
            <select name="group_id" class="form-control" id="selGroup">
              <option value="">Choose group...</option>
              <?php foreach ($groups as $g): ?>
              <option value="<?= $g['id'] ?>"><?= esc($g['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="target-section" id="sec-all">
          <div class="alert alert-warning">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:15px;height:15px;flex-shrink:0"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
            This will send to <strong>all active users</strong>. Make sure the message is relevant to everyone.
          </div>
        </div>

        <button type="submit" class="btn btn-indigo" style="width:100%;justify-content:center;padding:12px" id="sendBtn" disabled>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
          Send Notification
        </button>
      </form>
    </div>
  </div>

  <!-- ── Recent notifications ── -->
  <div>
    <div class="card">
      <div class="card-header">
        <h3>Recently Sent</h3>
        <span class="card-title-count"><?= count($recent) ?> notifications</span>
      </div>

      <?php if (empty($recent)): ?>
        <div class="empty-state">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          <p>No notifications sent yet</p>
          <small>Use the form to send your first notification</small>
        </div>
      <?php else: ?>
        <?php
        $typeColors = ['general'=>'type-general','alert'=>'type-alert','message'=>'type-message','announcement'=>'type-announcement','update'=>'type-update'];
        foreach ($recent as $n):
          $pct = $n['recipient_count'] > 0 ? round($n['read_count'] / $n['recipient_count'] * 100) : 0;
        ?>
        <a href="<?= base_url('admin/notifications/' . $n['id']) ?>" style="text-decoration:none;color:inherit">
        <div class="notif-card" style="cursor:pointer">
          <div class="notif-card-head">
            <div class="notif-card-title"><?= esc($n['title']) ?></div>
            <span class="type-pill <?= $typeColors[$n['type']] ?? 'type-general' ?>"><?= esc($n['type']) ?></span>
          </div>
          <div class="notif-card-body"><?= esc($n['body']) ?></div>
          <div class="notif-card-meta">
            <span>
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
              <?= number_format($n['recipient_count']) ?> recipients
            </span>
            <span>
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <?= number_format($n['read_count']) ?> read (<?= $pct ?>%)
            </span>
            <?php if ($n['sender_name']): ?>
            <span>By <?= esc($n['sender_name']) ?></span>
            <?php endif; ?>
            <span style="margin-left:auto"><?= date('M d, H:i', strtotime($n['created_at'])) ?></span>
          </div>
          <div class="read-bar"><div class="read-fill" style="width:<?= $pct ?>%"></div></div>
        </div>
        </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

</div>

</div></div>

<style>
.target-opt{display:flex;flex-direction:column;align-items:center;gap:6px;padding:12px 8px;border:2px solid #e2e8f0;border-radius:10px;cursor:pointer;transition:all .15s;background:#fff;font-size:12px;font-weight:600;color:#64748b}
.target-opt svg{color:#94a3b8;transition:color .15s}
.target-btn input:checked + .target-opt{border-color:#6366f1;background:#f5f3ff;color:#4338ca}
.target-btn input:checked + .target-opt svg{color:#6366f1}
.target-opt:hover{border-color:#a5b4fc;background:#f8f7ff}
</style>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$('#selUser').select2({ placeholder: 'Choose user...', width: '100%' });
$('#selUsers').select2({ placeholder: 'Select multiple users...', width: '100%' });
$('#selGroup').select2({ placeholder: 'Choose group...', width: '100%' });

const sections = { user: 'sec-user', users: 'sec-users', group: 'sec-group', all: 'sec-all' };
const sendBtn = document.getElementById('sendBtn');

document.querySelectorAll('[name="target"]').forEach(radio => {
  radio.addEventListener('change', () => {
    Object.values(sections).forEach(id => document.getElementById(id).classList.remove('show'));
    const sec = sections[radio.value];
    if (sec) document.getElementById(sec).classList.add('show');
    sendBtn.disabled = false;
  });
});
</script>
</body>
</html>
