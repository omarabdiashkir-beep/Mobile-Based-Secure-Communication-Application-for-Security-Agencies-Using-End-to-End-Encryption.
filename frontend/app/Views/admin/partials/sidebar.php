<?php $curPath = current_url(true)->getPath(); ?>
<div id="sideOverlay" class="overlay" onclick="toggleSidebar()"></div>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-mark">SG</div>
    <div><h2>SecureGov.so</h2><small>Admin Panel</small></div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Overview</div>
    <a href="<?= base_url('admin') ?>" class="nav-item <?= $curPath==='/admin'||$curPath==='/admin/'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Dashboard
    </a>
    <div class="nav-section">Management</div>
    <a href="<?= base_url('admin/users') ?>" class="nav-item <?= str_starts_with($curPath,'/admin/users')?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Users
    </a>
    <a href="<?= base_url('admin/groups') ?>" class="nav-item <?= str_starts_with($curPath,'/admin/groups')?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      Groups
    </a>
    <a href="<?= base_url('admin/messages') ?>" class="nav-item <?= str_starts_with($curPath,'/admin/messages')?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 14a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 3.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 10.9a16 16 0 0 0 6 6l1.27-.95a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 18.18v-1.26z"/></svg>
      Messages
    </a>
    <a href="<?= base_url('admin/notifications') ?>" class="nav-item <?= str_starts_with($curPath,'/admin/notifications')?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
      Notifications
    </a>

    <div class="nav-section">Monitoring</div>
    <a href="<?= base_url('admin/logs') ?>" class="nav-item <?= str_starts_with($curPath,'/admin/logs')?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
      API Logs
    </a>
  </nav>
  <div class="sidebar-footer">
    <a href="<?= base_url('admin/logout') ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Logout
    </a>
  </div>
</aside>
