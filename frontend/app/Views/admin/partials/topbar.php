<div class="topbar">
  <div class="topbar-left">
    <button class="hamburger" onclick="toggleSidebar()" aria-label="Menu">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <span class="topbar-title"><?= esc($pageTitle ?? 'Admin') ?></span>
  </div>
  <div class="topbar-right">
    <div class="topbar-user">
      <div class="topbar-av">A</div>
      Admin
    </div>
    <span class="topbar-badge">SecureGov</span>
  </div>
</div>
<div class="content">
<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
    <?= esc(session()->getFlashdata('success')) ?>
  </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-error">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
    <?= esc(session()->getFlashdata('error')) ?>
  </div>
<?php endif; ?>
