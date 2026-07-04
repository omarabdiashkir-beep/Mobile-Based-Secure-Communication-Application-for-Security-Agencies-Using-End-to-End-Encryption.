<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create User — SecureGov.so Admin</title>
<?php include dirname(__DIR__) . '/partials/style.php'; ?>
</head>
<body>
<?php include dirname(__DIR__) . '/partials/sidebar.php'; ?>
<div class="main">
<?php $pageTitle = 'Create User'; include dirname(__DIR__) . '/partials/topbar.php'; ?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-error"><?= implode('<br>', array_map('esc', $errors)) ?></div>
<?php endif; ?>

<div class="card" style="max-width:560px">
  <div class="card-header">
    <h3>New User Account</h3>
    <a href="<?= base_url('admin/users') ?>" class="btn btn-ghost btn-sm">← Back</a>
  </div>
  <form method="post" action="<?= base_url('admin/users/create') ?>">
    
    <div class="form-row">
      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="name" class="form-control" required>
      </div>
      <div class="form-group">
        <label>Username *</label>
        <input type="text" name="username" class="form-control" required>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Email *</label>
        <input type="email" name="email" class="form-control" required>
      </div>
      <div class="form-group">
        <label>Phone</label>
        <input type="text" name="phone" class="form-control">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Password *</label>
        <input type="password" name="password" class="form-control" required minlength="6">
      </div>
      <div class="form-group">
        <label>Role</label>
        <select name="role_id" class="form-control">
          <option value="2">User</option>
          <option value="1">Admin</option>
        </select>
      </div>
    </div>
    <div style="display:flex;gap:10px;margin-top:8px">
      <button type="submit" class="btn btn-orange">Create User</button>
      <a href="<?= base_url('admin/users') ?>" class="btn btn-ghost">Cancel</a>
    </div>
  </form>
</div>

</div></div>
</body>
</html>

