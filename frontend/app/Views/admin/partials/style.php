<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',system-ui,sans-serif;background:#f0f2f8;color:#1e293b;min-height:100vh;display:flex}
a{color:#2563eb;text-decoration:none}
a:hover{text-decoration:underline}

/* ── Sidebar ── */
.sidebar{width:256px;background:#0f172a;display:flex;flex-direction:column;height:100vh;flex-shrink:0;position:sticky;top:0;overflow-y:auto;transition:transform .28s cubic-bezier(.4,0,.2,1);z-index:200}
.sidebar::-webkit-scrollbar{width:0}
.sidebar-logo{padding:20px 18px 16px;display:flex;align-items:center;gap:11px;border-bottom:1px solid rgba(255,255,255,.06)}
.logo-mark{width:38px;height:38px;background:linear-gradient(135deg,#3b82f6,#6366f1);border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:16px;color:#fff;flex-shrink:0;box-shadow:0 4px 12px rgba(99,102,241,.4)}
.sidebar-logo h2{font-size:15px;font-weight:700;color:#f1f5f9;line-height:1.2}
.sidebar-logo small{color:rgba(255,255,255,.3);font-size:10.5px;display:block;margin-top:1px}
.sidebar-nav{flex:1;padding:12px 10px}
.nav-section{padding:14px 10px 5px;font-size:9.5px;font-weight:700;color:rgba(255,255,255,.25);text-transform:uppercase;letter-spacing:1.8px}
.nav-item{display:flex;align-items:center;gap:10px;padding:9px 12px;color:rgba(255,255,255,.5);font-size:13px;font-weight:500;transition:all .15s;border-radius:8px;margin-bottom:2px;cursor:pointer}
.nav-item:hover{background:rgba(255,255,255,.07);color:#f1f5f9;text-decoration:none}
.nav-item.active{background:rgba(99,102,241,.2);color:#a5b4fc;border:1px solid rgba(99,102,241,.25)}
.nav-item svg{width:16px;height:16px;flex-shrink:0}
.nav-item .nav-badge{margin-left:auto;background:rgba(99,102,241,.3);color:#a5b4fc;font-size:10px;font-weight:700;padding:1px 7px;border-radius:10px}
.sidebar-footer{padding:12px 10px;border-top:1px solid rgba(255,255,255,.06)}
.sidebar-footer a{display:flex;align-items:center;gap:9px;padding:10px 12px;background:rgba(239,68,68,.1);color:#fca5a5;border-radius:8px;font-size:13px;font-weight:600;border:1px solid rgba(239,68,68,.15);transition:all .15s}
.sidebar-footer a:hover{background:rgba(239,68,68,.2);color:#fff;text-decoration:none}
.sidebar-footer a svg{width:15px;height:15px}

/* ── Main ── */
.main{flex:1;display:flex;flex-direction:column;min-width:0;overflow:hidden}

/* ── Topbar ── */
.topbar{background:#fff;border-bottom:1px solid #e8ecf4;padding:0 24px;display:flex;align-items:center;justify-content:space-between;height:58px;position:sticky;top:0;z-index:100;box-shadow:0 1px 0 #e8ecf4}
.topbar-left{display:flex;align-items:center;gap:14px}
.hamburger{display:none;background:none;border:none;cursor:pointer;padding:7px;border-radius:8px;color:#64748b;transition:background .15s}
.hamburger:hover{background:#f1f5f9}
.topbar-title{font-size:17px;font-weight:700;color:#0f172a}
.topbar-right{display:flex;align-items:center;gap:10px}
.topbar-user{display:flex;align-items:center;gap:8px;padding:5px 12px 5px 5px;background:#f8faff;border:1px solid #e2e8f0;border-radius:20px;font-size:12.5px;font-weight:600;color:#1e293b}
.topbar-av{width:28px;height:28px;background:linear-gradient(135deg,#3b82f6,#6366f1);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:11px;font-weight:700}
.topbar-badge{background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;font-size:11px;font-weight:700;padding:3px 10px;border-radius:12px}

/* ── Content ── */
.content{flex:1;padding:24px;overflow-y:auto}
.content::-webkit-scrollbar{width:5px}
.content::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:4px}

/* ── Page header ── */
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:12px}
.page-header h2{font-size:20px;font-weight:800;color:#0f172a}
.page-header .sub{font-size:13px;color:#94a3b8;margin-top:2px;font-weight:400}
.breadcrumb{display:flex;align-items:center;gap:6px;font-size:12px;color:#94a3b8;margin-bottom:18px}
.breadcrumb a{color:#64748b;font-weight:500}
.breadcrumb a:hover{color:#2563eb}
.breadcrumb svg{width:12px;height:12px}

/* ── Cards ── */
.card{background:#fff;border:1px solid #e8ecf4;border-radius:14px;padding:20px;margin-bottom:18px;box-shadow:0 1px 3px rgba(0,0,0,.04),0 4px 12px rgba(0,0,0,.02)}
.card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid #f1f5f9}
.card-header h3{font-size:14.5px;font-weight:700;color:#0f172a}
.card-title-count{font-size:12.5px;color:#94a3b8;font-weight:500;margin-left:6px}

/* ── KPI cards ── */
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px;margin-bottom:22px}
.kpi-card{background:#fff;border:1px solid #e8ecf4;border-radius:14px;padding:18px 20px;position:relative;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.kpi-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px}
.kpi-card.blue::before{background:linear-gradient(90deg,#3b82f6,#6366f1)}
.kpi-card.green::before{background:linear-gradient(90deg,#10b981,#059669)}
.kpi-card.red::before{background:linear-gradient(90deg,#ef4444,#dc2626)}
.kpi-card.orange::before{background:linear-gradient(90deg,#f97316,#ea580c)}
.kpi-card.purple::before{background:linear-gradient(90deg,#8b5cf6,#7c3aed)}
.kpi-card.cyan::before{background:linear-gradient(90deg,#06b6d4,#0891b2)}
.kpi-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:12px}
.kpi-icon.blue{background:#eff6ff}.kpi-icon.green{background:#f0fdf4}.kpi-icon.red{background:#fef2f2}.kpi-icon.orange{background:#fff7ed}.kpi-icon.purple{background:#faf5ff}.kpi-icon.cyan{background:#ecfeff}
.kpi-icon svg{width:20px;height:20px}
.kpi-icon.blue svg{color:#3b82f6}.kpi-icon.green svg{color:#10b981}.kpi-icon.red svg{color:#ef4444}.kpi-icon.orange svg{color:#f97316}.kpi-icon.purple svg{color:#8b5cf6}.kpi-icon.cyan svg{color:#06b6d4}
.kpi-val{font-size:28px;font-weight:800;color:#0f172a;line-height:1;margin-bottom:4px}
.kpi-label{font-size:12px;color:#64748b;font-weight:500}

/* ── Responsive table ── */
.table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
table{width:100%;border-collapse:collapse;font-size:13.5px}
thead tr{background:#fafbfd}
th{padding:10px 16px;text-align:left;font-size:10.5px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.8px;border-bottom:2px solid #f0f2f8;white-space:nowrap}
td{padding:13px 16px;border-bottom:1px solid #f0f2f8;vertical-align:middle}
tr:last-child td{border-bottom:none}
tbody tr{transition:background .1s}
tbody tr:hover td{background:#fafbff}

/* Mobile table → card collapse */
@media(max-width:640px){
  .r-table thead{display:none}
  .r-table tbody tr{display:block;background:#fff;border:1px solid #e8ecf4;border-radius:12px;margin-bottom:10px;padding:4px 0;box-shadow:0 1px 3px rgba(0,0,0,.04)}
  .r-table tbody tr:hover td{background:transparent}
  .r-table tbody td{display:flex;justify-content:space-between;align-items:center;padding:10px 16px;border-bottom:1px solid #f8fafc;font-size:13px}
  .r-table tbody td:last-child{border-bottom:none}
  .r-table tbody td::before{content:attr(data-label);font-size:10.5px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;flex-shrink:0;margin-right:12px}
  .r-table tbody tr:last-child td{border-bottom:none}
  .table-wrap{overflow-x:visible}
}

/* ── Badges ── */
.badge{display:inline-flex;align-items:center;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:600;white-space:nowrap;gap:4px}
.badge-green{background:#dcfce7;color:#15803d}
.badge-red{background:#fee2e2;color:#b91c1c}
.badge-yellow{background:#fef9c3;color:#854d0e}
.badge-blue{background:#dbeafe;color:#1d4ed8}
.badge-indigo{background:#e0e7ff;color:#4338ca}
.badge-orange{background:#ffedd5;color:#c2410c}
.badge-gray{background:#f1f5f9;color:#475569}
.badge-purple{background:#f3e8ff;color:#7e22ce}
.badge-cyan{background:#cffafe;color:#0e7490}
.badge-dot::before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor;flex-shrink:0}

/* ── Buttons ── */
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;border:none;transition:all .15s;line-height:1.4;white-space:nowrap;text-decoration:none}
.btn:hover{text-decoration:none;transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,.12)}
.btn:active{transform:translateY(0);box-shadow:none}
.btn-primary{background:#1d4ed8;color:#fff}
.btn-primary:hover{background:#1e40af;color:#fff}
.btn-blue{background:#3b82f6;color:#fff}
.btn-blue:hover{background:#2563eb;color:#fff}
.btn-indigo{background:#6366f1;color:#fff}
.btn-indigo:hover{background:#4f46e5;color:#fff}
.btn-success{background:#16a34a;color:#fff}
.btn-success:hover{background:#15803d;color:#fff}
.btn-danger{background:#dc2626;color:#fff}
.btn-danger:hover{background:#b91c1c;color:#fff}
.btn-warning{background:#d97706;color:#fff}
.btn-warning:hover{background:#b45309;color:#fff}
.btn-ghost{background:#fff;color:#475569;border:1px solid #e2e8f0}
.btn-ghost:hover{background:#f8fafc;color:#1e293b;border-color:#cbd5e1}
.btn-sm{padding:5px 12px;font-size:12px;border-radius:7px}
.btn-sm:hover{transform:none;box-shadow:none}
.btn-icon{padding:7px;border-radius:8px;aspect-ratio:1}
.action-group{display:flex;gap:6px;flex-wrap:wrap}

/* ── Forms ── */
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:11.5px;font-weight:700;color:#475569;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px}
.form-control{width:100%;padding:9px 13px;background:#fff;border:1.5px solid #e2e8f0;border-radius:9px;color:#1e293b;font-size:13.5px;outline:none;transition:all .15s;font-family:inherit}
.form-control:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.12)}
select.form-control{cursor:pointer;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;appearance:none;padding-right:34px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.form-hint{font-size:11.5px;color:#94a3b8;margin-top:4px}

/* ── Search bar ── */
.search-bar{display:flex;gap:10px;margin-bottom:18px;align-items:center;flex-wrap:wrap}
.search-bar .form-control{max-width:260px;margin:0}

/* ── Alerts ── */
.alert{padding:11px 16px;border-radius:10px;margin-bottom:16px;font-size:13px;font-weight:500;display:flex;align-items:flex-start;gap:9px;border:1px solid transparent}
.alert svg{flex-shrink:0;margin-top:1px;width:15px;height:15px}
.alert-success{background:#f0fdf4;border-color:#bbf7d0;color:#15803d}
.alert-error{background:#fef2f2;border-color:#fecaca;color:#b91c1c}
.alert-warning{background:#fffbeb;border-color:#fde68a;color:#92400e}
.alert-info{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}

/* ── Avatar ── */
.av{border-radius:50%;background:#e0e7ff;display:inline-flex;align-items:center;justify-content:center;font-weight:700;color:#4338ca;flex-shrink:0;overflow:hidden}
.av img{width:100%;height:100%;object-fit:cover}
.av-sm{width:32px;height:32px;font-size:12px}
.av-md{width:40px;height:40px;font-size:15px}
.av-lg{width:64px;height:64px;font-size:24px}
.av-xl{width:80px;height:80px;font-size:30px}
.avatar{border-radius:50%;background:#e0e7ff;display:inline-flex;align-items:center;justify-content:center;font-weight:700;color:#4338ca;flex-shrink:0;overflow:hidden}
.avatar img{width:100%;height:100%;object-fit:cover}
.avatar-lg{width:64px;height:64px;font-size:24px}

/* ── Info rows ── */
.info-list{display:flex;flex-direction:column;gap:0}
.info-row{display:flex;gap:10px;padding:10px 0;border-bottom:1px solid #f8fafc;font-size:13px;align-items:flex-start}
.info-row:last-child{border-bottom:none}
.info-row .lbl{color:#94a3b8;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;width:120px;flex-shrink:0;padding-top:2px}
.info-row .val{color:#1e293b;font-weight:500;flex:1;word-break:break-word}

/* ── Pagination ── */
.pagination{display:flex;gap:4px;margin-top:18px;flex-wrap:wrap;align-items:center}
.page-btn{padding:6px 13px;border-radius:8px;font-size:13px;border:1.5px solid #e2e8f0;color:#475569;background:#fff;display:inline-flex;align-items:center;justify-content:center;transition:all .12s;font-weight:500;min-width:36px}
.page-btn:hover{border-color:#6366f1;color:#4338ca;text-decoration:none;background:#f5f3ff}
.page-btn.active{background:#6366f1;border-color:#6366f1;color:#fff}
.page-dots{color:#94a3b8;padding:0 4px;font-size:14px}

/* ── Code block ── */
.code-block{background:#0f172a;border-radius:9px;padding:12px;overflow-x:auto;white-space:pre-wrap;word-break:break-all;color:#7dd3fc;font-family:'Courier New',monospace;font-size:11.5px;max-height:200px;overflow-y:auto}
.code-block::-webkit-scrollbar{width:4px;height:4px}
.code-block::-webkit-scrollbar-thumb{background:#334155;border-radius:4px}

/* ── Method / status colors ── */
.m-get{color:#10b981;font-weight:700;font-size:11px;background:#f0fdf4;padding:2px 7px;border-radius:5px}
.m-post{color:#3b82f6;font-weight:700;font-size:11px;background:#eff6ff;padding:2px 7px;border-radius:5px}
.m-put{color:#f59e0b;font-weight:700;font-size:11px;background:#fffbeb;padding:2px 7px;border-radius:5px}
.m-delete{color:#ef4444;font-weight:700;font-size:11px;background:#fef2f2;padding:2px 7px;border-radius:5px}
.m-patch{color:#8b5cf6;font-weight:700;font-size:11px;background:#faf5ff;padding:2px 7px;border-radius:5px}
.s-2xx{color:#10b981;font-weight:700}.s-3xx{color:#3b82f6;font-weight:700}.s-4xx{color:#f59e0b;font-weight:700}.s-5xx{color:#ef4444;font-weight:700}

/* ── Modal ── */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:1000;align-items:center;justify-content:center;backdrop-filter:blur(3px);padding:16px}
.modal-overlay.open{display:flex}
.modal{background:#fff;border-radius:16px;padding:26px;width:100%;max-width:500px;max-height:92vh;overflow-y:auto;box-shadow:0 24px 64px rgba(0,0,0,.2);animation:mIn .2s ease}
.modal-lg{max-width:620px}
@keyframes mIn{from{opacity:0;transform:translateY(-16px) scale(.97)}to{opacity:1;transform:none}}
.modal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid #f1f5f9}
.modal-header h3{font-size:16px;font-weight:700;color:#0f172a}
.modal-close{background:none;border:none;cursor:pointer;padding:5px;color:#94a3b8;font-size:18px;line-height:1;border-radius:6px;transition:all .1s;width:30px;height:30px;display:flex;align-items:center;justify-content:center}
.modal-close:hover{background:#f1f5f9;color:#475569}
.modal-footer{display:flex;gap:10px;justify-content:flex-end;margin-top:20px;padding-top:16px;border-top:1px solid #f1f5f9}

/* ── User card in table ── */
.user-cell{display:flex;align-items:center;gap:10px}
.user-cell-info .name{font-weight:600;font-size:13.5px;color:#0f172a}
.user-cell-info .uname{color:#94a3b8;font-size:11.5px;margin-top:1px}

/* ── Online dot ── */
.online-dot{display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600}
.online-dot::before{content:'';width:7px;height:7px;border-radius:50%;flex-shrink:0}
.online-dot.online{color:#16a34a}.online-dot.online::before{background:#22c55e;box-shadow:0 0 0 2px rgba(34,197,94,.2)}
.online-dot.offline{color:#94a3b8}.online-dot.offline::before{background:#cbd5e1}

/* ── Empty state ── */
.empty-state{text-align:center;padding:56px 20px;color:#94a3b8}
.empty-state svg{margin:0 auto 14px;display:block;opacity:.2}
.empty-state p{font-size:14px;font-weight:500;margin-bottom:4px;color:#64748b}
.empty-state small{font-size:12.5px}

/* ── Stat comparison ── */
.stat-row{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:18px}
.stat-mini{background:#fafbff;border:1px solid #e8ecf4;border-radius:10px;padding:14px;text-align:center}
.stat-mini .sv{font-size:24px;font-weight:800;color:#0f172a;margin-bottom:2px}
.stat-mini .sl{font-size:11px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px}

/* ── Responsive layout ── */
@media(max-width:768px){
  .sidebar{position:fixed;top:0;left:0;height:100vh;transform:translateX(-256px)}
  .sidebar.open{transform:translateX(0);box-shadow:8px 0 24px rgba(0,0,0,.2)}
  .main{width:100%}
  .hamburger{display:flex!important}
  .form-row{grid-template-columns:1fr}
  .kpi-grid{grid-template-columns:repeat(2,1fr)}
  .content{padding:16px}
  .topbar{padding:0 16px}
  .overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:190}
  .overlay.open{display:block}
  .page-header{flex-direction:column;align-items:flex-start}
  .stat-row{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:480px){
  .kpi-grid{grid-template-columns:1fr 1fr}
  .modal{padding:18px}
  .action-group .btn-sm span{display:none}
}
@media(max-width:900px){
  .detail-grid{grid-template-columns:1fr!important}
}
</style>
<script>
function openModal(id){document.getElementById(id).classList.add('open')}
function closeModal(id){document.getElementById(id).classList.remove('open')}
document.addEventListener('keydown',e=>{if(e.key==='Escape')document.querySelectorAll('.modal-overlay.open').forEach(m=>m.classList.remove('open'))})
function toggleSidebar(){
  document.querySelector('.sidebar').classList.toggle('open');
  document.getElementById('sideOverlay').classList.toggle('open');
}
</script>
