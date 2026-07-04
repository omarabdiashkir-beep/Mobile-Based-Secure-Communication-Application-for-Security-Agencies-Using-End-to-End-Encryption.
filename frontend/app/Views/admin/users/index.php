<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Users — SecureGov.so Admin</title>
<?php include dirname(__DIR__) . '/partials/style.php'; ?>
</head>
<body>
<?php include dirname(__DIR__) . '/partials/sidebar.php'; ?>
<div class="main">
<?php $pageTitle = 'Users'; include dirname(__DIR__) . '/partials/topbar.php'; ?>

<div class="page-header">
  <div>
    <h2>Users</h2>
    <p class="sub">Manage all registered accounts</p>
  </div>
  <button onclick="openModal('modalCreateUser')" class="btn btn-indigo">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    New User
  </button>
</div>

<div class="card">
  <div class="card-header">
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
      <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <input type="text" name="search" class="form-control" placeholder="Search name, username, email..." value="<?= esc($search) ?>" style="max-width:240px;margin:0">
        <select name="status" class="form-control" style="max-width:140px;margin:0">
          <option value="">All Status</option>
          <option value="active" <?= $status==='active'?'selected':'' ?>>Active</option>
          <option value="suspended" <?= $status==='suspended'?'selected':'' ?>>Suspended</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <a href="<?= base_url('admin/users') ?>" class="btn btn-ghost btn-sm">Reset</a>
      </form>
    </div>
    <h3 class="card-title-count"><?= number_format($total) ?> users</h3>
  </div>
  <div class="table-wrap">
    <table class="r-table">
      <thead>
        <tr>
          <th>User</th>
          <th>Email</th>
          <th>Role</th>
          <th>Status</th>
          <th>Online</th>
          <th>Joined</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($users as $u): ?>
      <tr>
        <td data-label="User">
          <div class="user-cell">
            <div class="av av-sm"><?php if ($u['photo']): ?><img src="<?= base_url('uploads/'.$u['photo']) ?>"><?php else: ?><?= strtoupper(substr($u['name'],0,1)) ?><?php endif; ?></div>
            <div class="user-cell-info">
              <div class="name"><?= esc($u['name']) ?></div>
              <div class="uname">@<?= esc($u['username']) ?></div>
            </div>
          </div>
        </td>
        <td data-label="Email" style="color:#64748b;font-size:13px"><?= esc($u['email']) ?></td>
        <td data-label="Role"><span class="badge badge-indigo"><?= esc($u['role_name'] ?? 'User') ?></span></td>
        <td data-label="Status">
          <?php if ($u['status']==='active'): ?>
            <span class="badge badge-green badge-dot">Active</span>
          <?php elseif ($u['status']==='suspended'): ?>
            <span class="badge badge-red badge-dot">Suspended</span>
          <?php else: ?>
            <span class="badge badge-yellow"><?= esc($u['status']) ?></span>
          <?php endif; ?>
        </td>
        <td data-label="Online">
          <?= $u['is_online']
            ? '<span class="online-dot online">Online</span>'
            : '<span class="online-dot offline">Offline</span>' ?>
        </td>
        <td data-label="Joined" style="color:#94a3b8;font-size:12.5px"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
        <td data-label="Actions">
          <div class="action-group">
            <a href="<?= base_url('admin/users/'.$u['id']) ?>" class="btn btn-ghost btn-sm"><span>View</span></a>
            <?php if ($u['status']==='active'): ?>
              <button onclick="confirmAction('<?= base_url('admin/users/'.$u['id'].'/suspend') ?>','Suspend <?= esc(addslashes($u['name'])) ?>?','They will be logged out immediately.')" class="btn btn-warning btn-sm"><span>Suspend</span></button>
            <?php else: ?>
              <button onclick="confirmAction('<?= base_url('admin/users/'.$u['id'].'/activate') ?>','Activate <?= esc(addslashes($u['name'])) ?>?','User will regain access.')" class="btn btn-success btn-sm"><span>Activate</span></button>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($users)): ?>
      <tr><td colspan="7">
        <div class="empty-state">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
          <p>No users found</p>
          <small>Try adjusting your search or filters</small>
        </div>
      </td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php $pages=ceil($total/$limit); if($pages>1): $qs=http_build_query(array_filter(['search'=>$search,'status'=>$status])); $qs=$qs?'&'.$qs:''; ?>
<div class="pagination">
  <?php
  $s=max(1,$page-4); $e=min($pages,$page+4);
  if($s>1){echo '<a href="?page=1'.$qs.'" class="page-btn">1</a><span class="page-dots">…</span>';}
  for($i=$s;$i<=$e;$i++) echo '<a href="?page='.$i.$qs.'" class="page-btn '.($i===$page?'active':'').'">'.$i.'</a>';
  if($e<$pages){echo '<span class="page-dots">…</span><a href="?page='.$pages.$qs.'" class="page-btn">'.$pages.'</a>';}
  ?>
</div>
<?php endif; ?>

</div></div>

<!-- Create User Modal -->
<?php $createErrors = session('create_errors') ?? []; $ci = session('create_input') ?? []; ?>
<div class="modal-overlay" id="modalCreateUser" onclick="if(event.target===this)closeModal('modalCreateUser')">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3>Create New User</h3>
      <button class="modal-close" onclick="closeModal('modalCreateUser')">&#x2715;</button>
    </div>
    <?php if(!empty($createErrors)): ?>
      <div class="alert alert-error">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        <div><?php foreach($createErrors as $e): ?><div>&bull; <?= esc($e) ?></div><?php endforeach ?></div>
      </div>
    <?php endif ?>
    <form method="post" action="<?= base_url('admin/users/create') ?>">
      <div class="form-row">
        <div class="form-group">
          <label>Full Name *</label>
          <input type="text" name="name" class="form-control" required placeholder="John Doe" value="<?= esc($ci['name'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Username *</label>
          <input type="text" name="username" class="form-control" required placeholder="johndoe" value="<?= esc($ci['username'] ?? '') ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Email *</label>
          <input type="email" name="email" class="form-control" required placeholder="john@example.com" value="<?= esc($ci['email'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Phone</label>
          <input type="text" name="phone" class="form-control" placeholder="+1 234 567 890" value="<?= esc($ci['phone'] ?? '') ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Password *</label>
          <input type="password" name="password" class="form-control" required minlength="6" placeholder="Min 6 characters">
          <div class="form-hint">At least 6 characters</div>
        </div>
        <div class="form-group">
          <label>Role</label>
          <select name="role_id" class="form-control">
            <option value="2" <?= ($ci['role_id']??2)==2?'selected':'' ?>>User</option>
            <option value="1" <?= ($ci['role_id']??2)==1?'selected':'' ?>>Admin</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="closeModal('modalCreateUser')" class="btn btn-ghost">Cancel</button>
        <button type="submit" class="btn btn-indigo">Create User</button>
      </div>
    </form>
  </div>
</div>
<?php if(!empty($createErrors)): ?>
<script>document.addEventListener('DOMContentLoaded',()=>openModal('modalCreateUser'));</script>
<?php endif ?>

<!-- Confirm Action Modal -->
<div class="modal-overlay" id="modalConfirm" onclick="if(event.target===this)closeModal('modalConfirm')">
  <div class="modal" style="max-width:400px">
    <div class="modal-header">
      <h3 id="confirmTitle">Confirm Action</h3>
      <button class="modal-close" onclick="closeModal('modalConfirm')">&#x2715;</button>
    </div>
    <p id="confirmMsg" style="color:#475569;font-size:14px;line-height:1.7;margin-bottom:4px"></p>
    <div class="modal-footer">
      <button onclick="closeModal('modalConfirm')" class="btn btn-ghost">Cancel</button>
      <form id="confirmForm" method="post" style="display:inline"><button type="submit" class="btn btn-primary">Confirm</button></form>
    </div>
  </div>
</div>

<script>
function confirmAction(url, title, msg) {
  document.getElementById('confirmTitle').textContent = title;
  document.getElementById('confirmMsg').textContent = msg;
  document.getElementById('confirmForm').action = url;
  openModal('modalConfirm');
}
</script>
</body>
</html>
