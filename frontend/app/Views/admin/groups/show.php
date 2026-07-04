<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= esc($group['name']) ?> — SecureGov.so Admin</title>
<?php include dirname(__DIR__) . '/partials/style.php'; ?>
<style>.detail-grid{display:grid;grid-template-columns:300px 1fr;gap:20px;align-items:start}</style>
</head>
<body>
<?php include dirname(__DIR__) . '/partials/sidebar.php'; ?>
<div class="main">
<?php $pageTitle = esc($group['name']); include dirname(__DIR__) . '/partials/topbar.php'; ?>

<div class="breadcrumb">
  <a href="<?= base_url('admin/groups') ?>">Groups</a>
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
  <?= esc($group['name']) ?>
</div>

<div class="page-header">
  <div>
    <h2><?= esc($group['name']) ?></h2>
    <p class="sub"><?= count($members) ?> members</p>
  </div>
  <div class="action-group">
    <a href="<?= base_url('admin/groups') ?>" class="btn btn-ghost btn-sm">← Back</a>
    <button onclick="openModal('modalAddMember')" class="btn btn-indigo btn-sm">+ Add Member</button>
    <button onclick="openModal('modalDeleteGroup')" class="btn btn-danger btn-sm">Delete Group</button>
  </div>
</div>

<?php
$initial = strtoupper(substr($group['name'], 0, 1));
$colors = ['#6366f1','#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4'];
$color = $colors[crc32($group['name']) % count($colors)];
?>

<div class="detail-grid">

  <div>
    <div class="card">
      <div style="text-align:center;padding:10px 0 18px">
        <div class="av av-xl" style="margin:0 auto 14px;background:<?= $color ?>20;color:<?= $color ?>;border:2px solid <?= $color ?>30;font-size:32px"><?= $initial ?></div>
        <div style="font-size:17px;font-weight:800;color:#0f172a"><?= esc($group['name']) ?></div>
        <?php if ($group['description']): ?>
          <div style="color:#64748b;font-size:13px;margin-top:5px;line-height:1.5;padding:0 8px"><?= esc($group['description']) ?></div>
        <?php endif; ?>
        <div style="margin-top:12px">
          <?= $group['is_active']
            ? '<span class="badge badge-green badge-dot">Active</span>'
            : '<span class="badge badge-red badge-dot">Inactive</span>' ?>
        </div>
      </div>

      <div class="info-list" style="margin-top:4px">
        <div class="info-row">
          <span class="lbl">Created by</span>
          <span class="val"><?= esc($group['creator_name'] ?? '—') ?></span>
        </div>
        <div class="info-row">
          <span class="lbl">Members</span>
          <span class="val"><span class="badge badge-blue"><?= count($members) ?></span></span>
        </div>
        <div class="info-row">
          <span class="lbl">Created</span>
          <span class="val" style="font-size:12.5px"><?= date('M d, Y', strtotime($group['created_at'])) ?></span>
        </div>
      </div>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card-header"><h3>Members <span class="card-title-count">(<?= count($members) ?>)</span></h3></div>
      <div class="table-wrap">
        <table class="r-table">
          <thead><tr><th>User</th><th>Role</th><th>Joined</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach ($members as $m): ?>
          <tr>
            <td data-label="User">
              <div class="user-cell">
                <div class="av av-sm"><?php if ($m['photo']): ?><img src="<?= base_url('uploads/'.$m['photo']) ?>"><?php else: ?><?= strtoupper(substr($m['name'],0,1)) ?><?php endif; ?></div>
                <div class="user-cell-info">
                  <a href="<?= base_url('admin/users/'.$m['user_id']) ?>" class="name" style="color:#1d4ed8"><?= esc($m['name']) ?></a>
                  <div class="uname">@<?= esc($m['username']) ?></div>
                </div>
              </div>
            </td>
            <td data-label="Role"><span class="badge <?= $m['role']==='admin'?'badge-orange':'badge-gray' ?>"><?= esc($m['role']) ?></span></td>
            <td data-label="Joined" style="color:#94a3b8;font-size:12px"><?= $m['joined_at'] ? date('M d, Y', strtotime($m['joined_at'])) : '—' ?></td>
            <td data-label="Actions">
              <button onclick="removeMember('<?= base_url('admin/groups/'.$group['id'].'/members/'.$m['user_id'].'/remove') ?>','<?= esc(addslashes($m['name'])) ?>')" class="btn btn-danger btn-sm">Remove</button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($members)): ?>
          <tr><td colspan="4">
            <div class="empty-state" style="padding:24px">
              <p>No members yet</p>
            </div>
          </td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php if ($messages): ?>
    <div class="card">
      <div class="card-header"><h3>Recent Messages</h3></div>
      <div class="table-wrap">
        <table class="r-table">
          <thead><tr><th>Sender</th><th>Type</th><th>Content</th><th>Time</th></tr></thead>
          <tbody>
          <?php foreach ($messages as $m): ?>
          <tr>
            <td data-label="Sender" style="font-weight:500;font-size:13px"><?= esc($m['sender_name'] ?? '—') ?></td>
            <td data-label="Type"><span class="badge badge-gray"><?= esc($m['type']) ?></span></td>
            <td data-label="Content" style="font-size:12px;color:#64748b;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= esc(substr($m['content'] ?? '', 0, 80)) ?></td>
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

<!-- Add Member Modal -->
<div class="modal-overlay" id="modalAddMember" onclick="if(event.target===this)closeModal('modalAddMember')">
  <div class="modal" style="max-width:440px">
    <div class="modal-header">
      <h3>Add Member</h3>
      <button class="modal-close" onclick="closeModal('modalAddMember')">&#x2715;</button>
    </div>
    <form method="post" action="<?= base_url('admin/groups/'.$group['id'].'/members/add') ?>">
      <div class="form-group">
        <label>Select User</label>
        <select name="user_id" class="form-control" required>
          <option value="">Choose a user...</option>
          <?php
          $memberIds = array_column($members, 'user_id');
          $db = \Config\Database::connect();
          $allUsers = $db->table('users')->select('id, name, username')->orderBy('name')->get()->getResultArray();
          foreach ($allUsers as $u):
            if (in_array($u['id'], $memberIds)) continue;
          ?>
          <option value="<?= $u['id'] ?>"><?= esc($u['name']) ?> (@<?= esc($u['username']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="closeModal('modalAddMember')" class="btn btn-ghost">Cancel</button>
        <button type="submit" class="btn btn-indigo">Add Member</button>
      </div>
    </form>
  </div>
</div>

<!-- Remove Member Modal -->
<div class="modal-overlay" id="modalRemoveMember" onclick="if(event.target===this)closeModal('modalRemoveMember')">
  <div class="modal" style="max-width:400px">
    <div class="modal-header">
      <h3>Remove Member</h3>
      <button class="modal-close" onclick="closeModal('modalRemoveMember')">&#x2715;</button>
    </div>
    <p style="color:#475569;font-size:14px;line-height:1.7">Remove <strong id="removeMemberName"></strong> from this group?</p>
    <div class="modal-footer">
      <button onclick="closeModal('modalRemoveMember')" class="btn btn-ghost">Cancel</button>
      <form id="removeMemberForm" method="post" style="display:inline"><button type="submit" class="btn btn-danger">Remove</button></form>
    </div>
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
      This action is permanent and cannot be undone.
    </div>
    <p style="color:#475569;font-size:14px">Permanently delete <strong><?= esc($group['name']) ?></strong>? All members and messages will be removed.</p>
    <div class="modal-footer">
      <button onclick="closeModal('modalDeleteGroup')" class="btn btn-ghost">Cancel</button>
      <form method="post" action="<?= base_url('admin/groups/'.$group['id'].'/delete') ?>" style="display:inline"><button type="submit" class="btn btn-danger">Delete Group</button></form>
    </div>
  </div>
</div>

<script>
function removeMember(url, name) {
  document.getElementById('removeMemberName').textContent = name;
  document.getElementById('removeMemberForm').action = url;
  openModal('modalRemoveMember');
}
</script>
</body>
</html>
