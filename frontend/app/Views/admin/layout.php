<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?? 'Admin Panel' ?> — SecureGov.so</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#0f0f13;color:#e2e8f0;min-height:100vh;display:flex}
a{color:#60a5fa;text-decoration:none}
a:hover{text-decoration:underline}

/* Sidebar */
.sidebar{width:240px;background:#1a1a24;border-right:1px solid #2d2d3d;display:flex;flex-direction:column;min-height:100vh;flex-shrink:0}
.sidebar-logo{padding:20px 20px 16px;border-bottom:1px solid #2d2d3d}
.sidebar-logo h2{font-size:18px;font-weight:700;color:#60a5fa;letter-spacing:.5px}
.sidebar-logo small{color:#64748b;font-size:12px}
.sidebar-nav{flex:1;padding:12px 0}
.nav-section{padding:8px 16px 4px;font-size:10px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:1px}
.nav-item{display:flex;align-items:center;gap:10px;padding:9px 20px;color:#94a3b8;font-size:14px;transition:all .15s}
.nav-item:hover,.nav-item.active{background:#252535;color:#e2e8f0;text-decoration:none}
.nav-item.active{border-left:3px solid #60a5fa;color:#60a5fa}
.nav-item svg{width:16px;height:16px;flex-shrink:0}
.sidebar-footer{padding:16px;border-top:1px solid #2d2d3d}
.sidebar-footer a{display:block;padding:8px 12px;background:#c0392b;color:#fff;border-radius:6px;text-align:center;font-size:13px;font-weight:500}
.sidebar-footer a:hover{background:#e74c3c;text-decoration:none}

/* Main */
.main{flex:1;display:flex;flex-direction:column;overflow:hidden}
.topbar{background:#1a1a24;border-bottom:1px solid #2d2d3d;padding:14px 28px;display:flex;align-items:center;justify-content:space-between}
.topbar h1{font-size:18px;font-weight:600;color:#f1f5f9}
.topbar .admin-badge{font-size:13px;color:#64748b}
.content{flex:1;padding:28px;overflow-y:auto}

/* Cards */
.card{background:#1a1a24;border:1px solid #2d2d3d;border-radius:10px;padding:20px;margin-bottom:20px}
.card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #2d2d3d}
.card-header h3{font-size:16px;font-weight:600;color:#f1f5f9}

/* Stats grid */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;margin-bottom:24px}
.stat-card{background:#1a1a24;border:1px solid #2d2d3d;border-radius:10px;padding:18px}
.stat-card .label{font-size:12px;color:#64748b;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px}
.stat-card .value{font-size:28px;font-weight:700;color:#f1f5f9}
.stat-card .sub{font-size:12px;color:#475569;margin-top:4px}
.stat-card.blue{border-left:3px solid #3b82f6}.stat-card.green{border-left:3px solid #10b981}
.stat-card.yellow{border-left:3px solid #f59e0b}.stat-card.red{border-left:3px solid #ef4444}
.stat-card.purple{border-left:3px solid #8b5cf6}.stat-card.cyan{border-left:3px solid #06b6d4}

/* Table */
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:14px}
th{padding:10px 14px;text-align:left;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #2d2d3d}
td{padding:10px 14px;border-bottom:1px solid #1e1e2a;vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:#1e1e2e}

/* Badges */
.badge{display:inline-block;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:600}
.badge-green{background:#052e16;color:#4ade80;border:1px solid #166534}
.badge-red{background:#2d0f0f;color:#f87171;border:1px solid #7f1d1d}
.badge-yellow{background:#2d2300;color:#fbbf24;border:1px solid #92400e}
.badge-blue{background:#0c1a3d;color:#60a5fa;border:1px solid #1e3a8a}
.badge-gray{background:#1e1e2a;color:#94a3b8;border:1px solid #334155}

/* Buttons */
.btn{display:inline-block;padding:7px 16px;border-radius:6px;font-size:13px;font-weight:500;cursor:pointer;border:none;transition:all .15s}
.btn-primary{background:#3b82f6;color:#fff}.btn-primary:hover{background:#2563eb;text-decoration:none;color:#fff}
.btn-success{background:#10b981;color:#fff}.btn-success:hover{background:#059669;text-decoration:none;color:#fff}
.btn-danger{background:#ef4444;color:#fff}.btn-danger:hover{background:#dc2626;text-decoration:none;color:#fff}
.btn-warning{background:#f59e0b;color:#000}.btn-warning:hover{background:#d97706;text-decoration:none;color:#000}
.btn-ghost{background:transparent;color:#94a3b8;border:1px solid #334155}.btn-ghost:hover{background:#1e1e2a;color:#e2e8f0;text-decoration:none}
.btn-sm{padding:4px 10px;font-size:12px}

/* Forms */
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:13px;font-weight:500;color:#94a3b8;margin-bottom:6px}
.form-control{width:100%;padding:9px 12px;background:#0f0f13;border:1px solid #334155;border-radius:6px;color:#e2e8f0;font-size:14px;outline:none;transition:border-color .15s}
.form-control:focus{border-color:#3b82f6}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}

/* Alerts */
.alert{padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:14px}
.alert-success{background:#052e16;border:1px solid #166534;color:#4ade80}
.alert-error{background:#2d0f0f;border:1px solid #7f1d1d;color:#f87171}
.alert-warning{background:#2d2300;border:1px solid #92400e;color:#fbbf24}

/* Search bar */
.search-bar{display:flex;gap:10px;margin-bottom:20px}
.search-bar .form-control{max-width:280px}

/* User avatar */
.avatar{width:32px;height:32px;border-radius:50%;background:#2d2d3d;display:inline-flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;color:#94a3b8;flex-shrink:0;overflow:hidden}
.avatar img{width:100%;height:100%;object-fit:cover}
.online-dot{width:8px;height:8px;border-radius:50%;background:#10b981;display:inline-block;margin-right:4px}
.offline-dot{width:8px;height:8px;border-radius:50%;background:#475569;display:inline-block;margin-right:4px}

/* Pagination */
.pagination{display:flex;gap:4px;margin-top:20px}
.page-btn{padding:6px 12px;border-radius:6px;font-size:13px;border:1px solid #334155;color:#94a3b8;background:transparent}
.page-btn:hover,.page-btn.active{background:#3b82f6;border-color:#3b82f6;color:#fff;text-decoration:none}

/* Log method colors */
.method-get{color:#4ade80}.method-post{color:#60a5fa}.method-put{color:#fbbf24}.method-delete{color:#f87171}.method-patch{color:#c084fc}
.status-2xx{color:#4ade80}.status-3xx{color:#60a5fa}.status-4xx{color:#fbbf24}.status-5xx{color:#f87171}

/* Code */
pre,code{font-family:'Courier New',monospace;font-size:12px}
.code-block{background:#0f0f13;border:1px solid #2d2d3d;border-radius:6px;padding:12px;overflow-x:auto;white-space:pre-wrap;word-break:break-all;color:#a5f3fc;max-height:200px;overflow-y:auto}

/* Profile info rows */
.info-row{display:flex;gap:8px;padding:8px 0;border-bottom:1px solid #1e1e2a;font-size:14px}
.info-row .label{color:#64748b;width:140px;flex-shrink:0}
.info-row .value{color:#e2e8f0}
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-logo">
    <h2>SecureGov.so</h2>
    <small>Admin Panel</small>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Overview</div>
    <a href="/admin" class="nav-item <?= (current_url(true)->getPath() === '/admin') ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
      Dashboard
    </a>

    <div class="nav-section">Management</div>
    <a href="/admin/users" class="nav-item <?= str_starts_with(current_url(true)->getPath(), '/admin/users') ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Users
    </a>
    <a href="/admin/groups" class="nav-item <?= str_starts_with(current_url(true)->getPath(), '/admin/groups') ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      Groups
    </a>

    <div class="nav-section">Monitoring</div>
    <a href="/admin/logs" class="nav-item <?= str_starts_with(current_url(true)->getPath(), '/admin/logs') ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
      API Logs
    </a>
  </nav>
  <div class="sidebar-footer">
    <a href="/admin/logout">Logout</a>
  </div>
</aside>

<div class="main">
  <div class="topbar">
    <h1><?= $pageTitle ?? 'Admin Panel' ?></h1>
    <span class="admin-badge">admin</span>
  </div>
  <div class="content">
    <?php if (session()->getFlashdata('success')): ?>
      <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert alert-error"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?= $this->renderSection('content') ?>
  </div>
</div>

</body>
</html>

