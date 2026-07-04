<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Groups — SecureGov.so Admin</title>
<?php include dirname(__DIR__) . '/partials/style.php'; ?>
</head>
<body>
<?php include dirname(__DIR__) . '/partials/sidebar.php'; ?>
<div class="main">
<?php $pageTitle = 'Groups'; include dirname(__DIR__) . '/partials/topbar.php'; ?>

<div class="page-header">
  <div>
    <h2>Groups</h2>
    <p class="sub">Manage all community groups</p>
  </div>
  <button onclick="openModal('modalCreateGroup')" class="btn btn-indigo">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    New Group
  </button>
</div>

<div class="card">
  <div class="card-header">
    <div>
      <form method="get" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <input type="text" name="search" class="form-control" placeholder="Search group name..." value="<?= esc($search) ?>" style="max-width:240px;margin:0">
        <button type="submit" class="btn btn-primary btn-sm">Search</button>
        <a href="<?= base_url('admin/groups') ?>" class="btn btn-ghost btn-sm">Reset</a>
      </form>
    </div>
    <h3 class="card-title-count"><?= number_format($total) ?> groups</h3>
  </div>

  <div class="table-wrap">
    <table class="r-table">
      <thead>
        <tr>
          <th>Group</th>
          <th>Created By</th>
          <th>Members</th>
          <th>Status</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($groups as $g):
        $initial = strtoupper(substr($g['name'], 0, 1));
        $colors = ['#6366f1','#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4'];
        $color = $colors[crc32($g['name']) % count($colors)];
      ?>
      <tr>
        <td data-label="Group">
          <div class="user-cell">
            <div class="av av-sm" style="background:<?= $color ?>20;color:<?= $color ?>;border:1.5px solid <?= $color ?>30"><?= $initial ?></div>
            <div class="user-cell-info">
              <div class="name"><?= esc($g['name']) ?></div>
              <?php if ($g['description']): ?><div class="uname"><?= esc(substr($g['description'],0,50)) ?></div><?php endif; ?>
            </div>
          </div>
        </td>
        <td data-label="Created By" style="color:#64748b;font-size:13px"><?= esc($g['creator_name'] ?? '—') ?></td>
        <td data-label="Members"><span class="badge badge-blue"><?= $g['member_count'] ?></span></td>
        <td data-label="Status">
          <?= $g['is_active']
            ? '<span class="badge badge-green badge-dot">Active</span>'
            : '<span class="badge badge-red badge-dot">Inactive</span>' ?>
        </td>
        <td data-label="Created" style="color:#94a3b8;font-size:12.5px"><?= date('M d, Y', strtotime($g['created_at'])) ?></td>
        <td data-label="Actions">
          <div class="action-group">
            <a href="<?= base_url('admin/groups/'.$g['id']) ?>" class="btn btn-ghost btn-sm"><span>View</span></a>
            <button onclick="deleteGroup('<?= base_url('admin/groups/'.$g['id'].'/delete') ?>','<?= esc(addslashes($g['name'])) ?>')" class="btn btn-danger btn-sm"><span>Delete</span></button>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($groups)): ?>
      <tr><td colspan="6">
        <div class="empty-state">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          <p>No groups found</p>
          <small>Create the first group to get started</small>
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
  $qs = $search ? '&search='.urlencode($search) : '';
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

<?php $createError = session('create_error') ?? null; ?>
<!-- Create Group Modal -->
<div class="modal-overlay" id="modalCreateGroup" onclick="if(event.target===this)closeModal('modalCreateGroup')">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3>Create New Group</h3>
      <button class="modal-close" onclick="closeModal('modalCreateGroup')">&#x2715;</button>
    </div>
    <?php if ($createError): ?>
      <div class="alert alert-error" style="margin-bottom:14px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        <?= esc($createError) ?>
      </div>
    <?php endif; ?>
    <form method="post" action="<?= base_url('admin/groups/create') ?>">
      <div class="form-row">
        <div class="form-group">
          <label>Group Name *</label>
          <input type="text" name="name" class="form-control" required placeholder="e.g. General Discussion">
        </div>
        <div class="form-group">
          <label>Group Owner *</label>
          <select name="created_by" class="form-control" required>
            <option value="">Select owner...</option>
            <?php foreach ($users as $u): ?>
            <option value="<?= $u['id'] ?>"><?= esc($u['name']) ?> (@<?= esc($u['username']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" class="form-control" rows="3" style="resize:vertical" placeholder="Optional description..."></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="closeModal('modalCreateGroup')" class="btn btn-ghost">Cancel</button>
        <button type="submit" class="btn btn-indigo">Create Group</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Group Modal -->
<div class="modal-overlay" id="modalDeleteGroup" onclick="if(event.target===this)closeModal('modalDeleteGroup')">
  <div class="modal" style="max-width:400px">
    <div class="modal-header">
      <h3>Delete Group</h3>
      <button class="modal-close" onclick="closeModal('modalDeleteGroup')">&#x2715;</button>
    </div>
    <div class="alert alert-error" style="margin-bottom:14px">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      This cannot be undone.
    </div>
    <p style="color:#475569;font-size:14px">Delete <strong id="delGroupName"></strong>? All members and messages will be lost.</p>
    <div class="modal-footer">
      <button onclick="closeModal('modalDeleteGroup')" class="btn btn-ghost">Cancel</button>
      <form id="delGroupForm" method="post" style="display:inline"><button type="submit" class="btn btn-danger">Delete Group</button></form>
    </div>
  </div>
</div>

<script>
function deleteGroup(url, name) {
  document.getElementById('delGroupName').textContent = name;
  document.getElementById('delGroupForm').action = url;
  openModal('modalDeleteGroup');
}
<?php if ($createError): ?>
document.addEventListener('DOMContentLoaded', () => openModal('modalCreateGroup'));
<?php endif; ?>
</script>
</body>
</html>
