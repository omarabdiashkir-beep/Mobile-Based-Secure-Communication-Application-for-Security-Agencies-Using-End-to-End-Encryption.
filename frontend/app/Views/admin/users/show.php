<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= esc($user['name']) ?> — SecureGov.so Admin</title>
<?php include dirname(__DIR__) . '/partials/style.php'; ?>
<style>
.detail-grid{display:grid;grid-template-columns:300px 1fr;gap:20px;align-items:start}
</style>
</head>
<body>
<?php include dirname(__DIR__) . '/partials/sidebar.php'; ?>
<div class="main">
<?php $pageTitle = esc($user['name']); include dirname(__DIR__) . '/partials/topbar.php'; ?>

<div class="breadcrumb">
  <a href="<?= base_url('admin/users') ?>">Users</a>
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
  <?= esc($user['name']) ?>
</div>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-error"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="page-header">
  <div>
    <h2><?= esc($user['name']) ?></h2>
    <p class="sub">@<?= esc($user['username']) ?></p>
  </div>
  <div class="action-group">
    <a href="<?= base_url('admin/users') ?>" class="btn btn-ghost btn-sm">← Back</a>
    <?php if ($user['status']==='active'): ?>
      <button onclick="openModal('modalSuspend')" class="btn btn-warning btn-sm">Suspend</button>
    <?php else: ?>
      <button onclick="openModal('modalActivate')" class="btn btn-success btn-sm">Activate</button>
    <?php endif; ?>
    <button onclick="openModal('modalResetPw')" class="btn btn-blue btn-sm">Reset Password</button>
    <button onclick="openModal('modalDelete')" class="btn btn-danger btn-sm">Delete</button>
  </div>
</div>

<div class="detail-grid">

  <!-- Left column: profile card -->
  <div>
    <div class="card">
      <div style="text-align:center;padding:12px 0 18px">
        <div class="av av-xl" style="margin:0 auto 14px">
          <?php if ($user['photo']): ?><img src="<?= base_url('uploads/'.$user['photo']) ?>"><?php else: ?><?= strtoupper(substr($user['name'],0,1)) ?><?php endif; ?>
        </div>
        <div style="font-size:18px;font-weight:800;color:#0f172a"><?= esc($user['name']) ?></div>
        <div style="color:#64748b;font-size:13px;margin-top:3px">@<?= esc($user['username']) ?></div>
        <div style="margin-top:12px;display:flex;gap:6px;justify-content:center;flex-wrap:wrap">
          <?= $user['is_online']
            ? '<span class="badge badge-green badge-dot">Online</span>'
            : '<span class="badge badge-gray badge-dot">Offline</span>' ?>
          <?php if ($user['status']==='suspended'): ?>
            <span class="badge badge-red">Suspended</span>
          <?php elseif ($user['status']==='active'): ?>
            <span class="badge badge-green">Active</span>
          <?php endif; ?>
          <span class="badge badge-indigo"><?= esc($user['role_name'] ?? 'User') ?></span>
        </div>
      </div>

      <div class="info-list" style="margin-top:4px">
        <div class="info-row">
          <span class="lbl">Email</span>
          <span class="val" style="font-size:13px;word-break:break-all"><?= esc($user['email']) ?></span>
        </div>
        <div class="info-row">
          <span class="lbl">Phone</span>
          <span class="val"><?= esc($user['phone'] ?? '—') ?></span>
        </div>
        <div class="info-row">
          <span class="lbl">Occupation</span>
          <span class="val"><?= esc($user['occupation'] ?? '—') ?></span>
        </div>
        <div class="info-row">
          <span class="lbl">Bio</span>
          <span class="val" style="font-size:13px;line-height:1.5"><?= esc($user['bio'] ?? '—') ?></span>
        </div>
        <div class="info-row">
          <span class="lbl">Last seen</span>
          <span class="val" style="font-size:12.5px"><?= $user['last_seen'] ? date('M d, Y H:i', strtotime($user['last_seen'])) : '—' ?></span>
        </div>
        <div class="info-row">
          <span class="lbl">Joined</span>
          <span class="val" style="font-size:12.5px"><?= date('M d, Y', strtotime($user['created_at'])) ?></span>
        </div>
      </div>
    </div>
  </div>

  <!-- Right column -->
  <div>
    <!-- Stats -->
    <div class="stat-row" style="margin-bottom:18px">
      <div class="stat-mini">
        <div class="sv" style="color:#3b82f6"><?= number_format($messages_sent) ?></div>
        <div class="sl">Sent</div>
      </div>
      <div class="stat-mini">
        <div class="sv" style="color:#10b981"><?= number_format($messages_recv) ?></div>
        <div class="sl">Received</div>
      </div>
      <div class="stat-mini">
        <div class="sv" style="color:#8b5cf6"><?= count($groups) ?></div>
        <div class="sl">Groups</div>
      </div>
    </div>

    <?php if ($groups): ?>
    <div class="card">
      <div class="card-header"><h3>Groups</h3></div>
      <div class="table-wrap">
        <table class="r-table">
          <thead><tr><th>Group</th><th>Role</th><th>Joined</th></tr></thead>
          <tbody>
          <?php foreach ($groups as $g): ?>
          <tr>
            <td data-label="Group"><a href="<?= base_url('admin/groups/'.$g['id']) ?>"><?= esc($g['name']) ?></a></td>
            <td data-label="Role"><span class="badge <?= $g['role']==='admin'?'badge-orange':'badge-gray' ?>"><?= esc($g['role']) ?></span></td>
            <td data-label="Joined" style="color:#94a3b8;font-size:12px"><?= $g['joined_at'] ? date('M d, Y', strtotime($g['joined_at'])) : '—' ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header">
        <h3>Recent API Activity</h3>
        <a href="<?= base_url('admin/logs?user_id='.$user['id']) ?>" class="btn btn-ghost btn-sm">All Logs</a>
      </div>
      <?php if ($activity): ?>
      <div class="table-wrap">
        <table class="r-table">
          <thead><tr><th>Method</th><th>URI</th><th>Status</th><th>Time</th></tr></thead>
          <tbody>
          <?php foreach ($activity as $log):
            $sc = (int)$log['status_code'];
            $sc_cls = $sc>=500?'badge-red':($sc>=400?'badge-yellow':($sc>=300?'badge-blue':'badge-green'));
            $m = strtolower($log['method'] ?? 'get');
          ?>
          <tr>
            <td data-label="Method"><span class="m-<?= $m ?>"><?= strtoupper($m) ?></span></td>
            <td data-label="URI" style="font-size:12px;color:#64748b;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= esc($log['uri']) ?>"><?= esc($log['uri']) ?></td>
            <td data-label="Status"><span class="badge <?= $sc_cls ?>"><?= $log['status_code'] ?></span></td>
            <td data-label="Time" style="color:#94a3b8;font-size:12px;white-space:nowrap"><?= date('M d H:i', strtotime($log['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <div class="empty-state" style="padding:28px 20px">
          <p>No activity logged yet.</p>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($recent_messages): ?>
    <div class="card">
      <div class="card-header"><h3>Recent Messages Sent</h3></div>
      <div class="table-wrap">
        <table class="r-table">
          <thead><tr><th>To</th><th>Type</th><th>Content</th><th>Time</th></tr></thead>
          <tbody>
          <?php foreach ($recent_messages as $m): ?>
          <tr>
            <td data-label="To" style="font-size:13px;font-weight:500"><?= esc($m['receiver_name'] ?? '—') ?></td>
            <td data-label="Type"><span class="badge badge-gray"><?= esc($m['type']) ?></span></td>
            <td data-label="Content" style="font-size:12px;color:#64748b;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= esc(substr($m['content'] ?? '', 0, 60)) ?></td>
            <td data-label="Time" style="color:#94a3b8;font-size:12px;white-space:nowrap"><?= date('M d H:i', strtotime($m['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

</div></div>

<!-- Modals -->
<div class="modal-overlay" id="modalSuspend" onclick="if(event.target===this)closeModal('modalSuspend')">
  <div class="modal" style="max-width:400px">
    <div class="modal-header"><h3>Suspend User</h3><button class="modal-close" onclick="closeModal('modalSuspend')">&#x2715;</button></div>
    <p style="color:#475569;font-size:14px;line-height:1.7">Suspend <strong><?= esc($user['name']) ?></strong>? They will be logged out immediately and cannot access the app.</p>
    <div class="modal-footer">
      <button onclick="closeModal('modalSuspend')" class="btn btn-ghost">Cancel</button>
      <form method="post" action="<?= base_url('admin/users/'.$user['id'].'/suspend') ?>" style="display:inline"><button type="submit" class="btn btn-warning">Suspend</button></form>
    </div>
  </div>
</div>

<div class="modal-overlay" id="modalActivate" onclick="if(event.target===this)closeModal('modalActivate')">
  <div class="modal" style="max-width:400px">
    <div class="modal-header"><h3>Activate User</h3><button class="modal-close" onclick="closeModal('modalActivate')">&#x2715;</button></div>
    <p style="color:#475569;font-size:14px;line-height:1.7">Activate <strong><?= esc($user['name']) ?></strong>? They will regain full access to the application.</p>
    <div class="modal-footer">
      <button onclick="closeModal('modalActivate')" class="btn btn-ghost">Cancel</button>
      <form method="post" action="<?= base_url('admin/users/'.$user['id'].'/activate') ?>" style="display:inline"><button type="submit" class="btn btn-success">Activate</button></form>
    </div>
  </div>
</div>

<div class="modal-overlay" id="modalResetPw" onclick="if(event.target===this)closeModal('modalResetPw')">
  <div class="modal" style="max-width:420px">
    <div class="modal-header"><h3>Reset Password</h3><button class="modal-close" onclick="closeModal('modalResetPw')">&#x2715;</button></div>
    <form method="post" action="<?= base_url('admin/users/'.$user['id'].'/reset-password') ?>" onsubmit="return validateResetPw()">
      <div class="form-group">
        <label>New Password</label>
        <div style="position:relative">
          <input type="password" id="rpNewPw" name="new_password" class="form-control" placeholder="Min 6 characters" required minlength="6" style="padding-right:44px">
          <button type="button" onclick="togglePw('rpNewPw','rpEye1')" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#64748b;padding:0;line-height:1">
            <svg id="rpEye1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>
      <div class="form-group">
        <label>Confirm Password</label>
        <div style="position:relative">
          <input type="password" id="rpConfirm" class="form-control" placeholder="Repeat password" required minlength="6" style="padding-right:44px">
          <button type="button" onclick="togglePw('rpConfirm','rpEye2')" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#64748b;padding:0;line-height:1">
            <svg id="rpEye2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <div id="rpMismatch" style="color:#ef4444;font-size:12px;margin-top:4px;display:none">Passwords do not match.</div>
      </div>
      <div class="form-hint" style="margin-bottom:14px">User will be logged out immediately and must log in with the new password.</div>
      <div class="modal-footer">
        <button type="button" onclick="closeModal('modalResetPw')" class="btn btn-ghost">Cancel</button>
        <button type="submit" class="btn btn-blue">Reset Password</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="modalDelete" onclick="if(event.target===this)closeModal('modalDelete')">
  <div class="modal" style="max-width:400px">
    <div class="modal-header"><h3>Delete User</h3><button class="modal-close" onclick="closeModal('modalDelete')">&#x2715;</button></div>
    <div class="alert alert-error" style="margin-bottom:14px">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      This action cannot be undone.
    </div>
    <p style="color:#475569;font-size:14px">Permanently delete <strong><?= esc($user['name']) ?></strong> and all their data?</p>
    <div class="modal-footer">
      <button onclick="closeModal('modalDelete')" class="btn btn-ghost">Cancel</button>
      <form method="post" action="<?= base_url('admin/users/'.$user['id'].'/delete') ?>" style="display:inline"><button type="submit" class="btn btn-danger">Delete Forever</button></form>
    </div>
  </div>
</div>

<script>
function togglePw(inputId, eyeId) {
  var inp = document.getElementById(inputId);
  var eye = document.getElementById(eyeId);
  if (inp.type === 'password') {
    inp.type = 'text';
    eye.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';
  } else {
    inp.type = 'password';
    eye.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
  }
}
function validateResetPw() {
  var pw  = document.getElementById('rpNewPw').value;
  var cfm = document.getElementById('rpConfirm').value;
  var msg = document.getElementById('rpMismatch');
  if (pw !== cfm) { msg.style.display = 'block'; return false; }
  msg.style.display = 'none';
  return true;
}
</script>
</body>
</html>
