<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — SecureGov.so</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',system-ui,sans-serif;background:#0b1120;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;position:relative;overflow:hidden}
body::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 80% 60% at 50% -10%,rgba(99,102,241,.22),transparent),radial-gradient(ellipse 60% 50% at 90% 80%,rgba(59,130,246,.12),transparent)}
.grid-bg{position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.03) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.03) 1px,transparent 1px);background-size:40px 40px}
.wrap{width:100%;max-width:420px;position:relative;z-index:1}

/* Logo section */
.logo{text-align:center;margin-bottom:28px}
.logo-mark{width:60px;height:60px;background:linear-gradient(135deg,#6366f1,#3b82f6);border-radius:16px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:26px;color:#fff;margin:0 auto 16px;box-shadow:0 8px 32px rgba(99,102,241,.35),0 0 0 1px rgba(255,255,255,.08)}
.logo h1{font-size:22px;font-weight:800;color:#f1f5f9;letter-spacing:-.3px}
.logo p{color:rgba(255,255,255,.35);font-size:13px;margin-top:5px}

/* Card */
.card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:20px;padding:36px 32px;backdrop-filter:blur(20px);box-shadow:0 24px 64px rgba(0,0,0,.4)}

/* Form */
.form-group{margin-bottom:20px}
label{display:block;font-size:11px;font-weight:700;color:rgba(255,255,255,.4);margin-bottom:8px;text-transform:uppercase;letter-spacing:.8px}
.input-wrap{position:relative}
input{width:100%;padding:12px 16px;background:rgba(255,255,255,.06);border:1.5px solid rgba(255,255,255,.1);border-radius:10px;color:#f1f5f9;font-size:14px;outline:none;transition:all .2s;font-family:inherit}
input:focus{border-color:rgba(99,102,241,.6);background:rgba(99,102,241,.08);box-shadow:0 0 0 4px rgba(99,102,241,.12)}
input::placeholder{color:rgba(255,255,255,.2)}

/* Button */
.btn-login{width:100%;padding:13px;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border:none;border-radius:10px;font-size:14.5px;font-weight:700;cursor:pointer;margin-top:8px;transition:all .2s;font-family:inherit;letter-spacing:.2px;box-shadow:0 4px 16px rgba(99,102,241,.35)}
.btn-login:hover{background:linear-gradient(135deg,#4f46e5,#4338ca);transform:translateY(-1px);box-shadow:0 8px 24px rgba(99,102,241,.4)}
.btn-login:active{transform:translateY(0)}

/* Error */
.error-box{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);border-radius:10px;color:#fca5a5;padding:12px 16px;font-size:13px;margin-bottom:22px;display:flex;align-items:center;gap:9px}
.error-box svg{flex-shrink:0;width:15px;height:15px}

/* Divider */
.div-line{display:flex;align-items:center;gap:12px;margin:22px 0;opacity:.3}
.div-line::before,.div-line::after{content:'';flex:1;height:1px;background:rgba(255,255,255,.25)}
.div-line span{font-size:11px;color:rgba(255,255,255,.5);white-space:nowrap}

.footer-note{text-align:center;font-size:11.5px;color:rgba(255,255,255,.2);margin-top:22px}
.footer-note strong{color:rgba(99,102,241,.6)}
</style>
</head>
<body>
<div class="grid-bg"></div>
<div class="wrap">
  <div class="logo">
    <div class="logo-mark">S</div>
    <h1>SecureGov.so</h1>
    <p>Admin Panel &mdash; Authorized Access Only</p>
  </div>
  <div class="card">
    <?php if ($error): ?>
    <div class="error-box">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
      <?= esc($error) ?>
    </div>
    <?php endif; ?>
    <form method="post" action="<?= base_url('admin/login') ?>">
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" autocomplete="username" required placeholder="Enter your username" autofocus>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" autocomplete="current-password" required placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
      </div>
      <button class="btn-login" type="submit">
        Sign in to Admin Panel
      </button>
    </form>
    <div class="div-line"><span>SECURE ACCESS</span></div>
  </div>
  <div class="footer-note">Restricted to authorized personnel &mdash; <strong>SecureGov.so &copy; 2026</strong></div>
</div>
</body>
</html>
