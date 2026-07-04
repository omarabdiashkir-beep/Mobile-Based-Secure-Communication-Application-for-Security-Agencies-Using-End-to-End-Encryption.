<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Group — SecureGov.so Admin</title>
<?php include dirname(__DIR__) . '/partials/style.php'; ?>
</head>
<body>
<?php include dirname(__DIR__) . '/partials/sidebar.php'; ?>
<div class="main">
<?php $pageTitle = 'Create Group'; include dirname(__DIR__) . '/partials/topbar.php'; ?>

<?php if ($error): ?><div class="alert alert-error"><?= esc($error) ?></div><?php endif; ?>

<div class="card" style="max-width:520px">
  <div class="card-header">
    <h3>New Group</h3>
    <a href="<?= base_url('admin/groups') ?>" class="btn btn-ghost btn-sm">← Back</a>
  </div>
  <form method="post" action="<?= base_url('admin/groups/create') ?>">
    
    <div class="form-group">
      <label>Group Name *</label>
      <input type="text" name="name" class="form-control" required>
    </div>
    <div class="form-group">
      <label>Description</label>
      <input type="text" name="description" class="form-control">
    </div>
    <div class="form-group">
      <label>Owner (Group Admin) *</label>
      <select name="created_by" class="form-control" required>
        <option value="">Select user...</option>
        <?php foreach ($users as $u): ?>
          <option value="<?= $u['id'] ?>"><?= esc($u['name']) ?> (@<?= esc($u['username']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="display:flex;gap:10px">
      <button type="submit" class="btn btn-orange">Create Group</button>
      <a href="<?= base_url('admin/groups') ?>" class="btn btn-ghost">Cancel</a>
    </div>
  </form>
</div>

</div></div>
</body>
</html>

