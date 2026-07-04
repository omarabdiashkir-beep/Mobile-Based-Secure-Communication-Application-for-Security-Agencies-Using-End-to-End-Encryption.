<?php
$tab         = $tab         ?? 'direct';
$users       = $users       ?? [];
$groups      = $groups      ?? [];
$messages    = $messages    ?? [];
$total       = $total       ?? 0;
$page        = $page        ?? 1;
$limit       = $limit       ?? 40;
$userA       = $userA       ?? 0;
$userB       = $userB       ?? 0;
$personA     = $personA     ?? null;
$personB     = $personB     ?? null;
$groupId     = $groupId     ?? 0;
$activeGroup = $activeGroup ?? null;
$pages       = $total > 0 ? ceil($total / $limit) : 1;
?>
<?php include APPPATH . 'Views/admin/partials/style.php' ?>

<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
/* ── Page layout ── */
.msg-page{display:flex;flex-direction:column;height:calc(100vh - 60px);overflow:hidden}
.msg-toolbar{padding:20px 28px 0;flex-shrink:0}

/* ── Tab switch ── */
.tab-pills{display:flex;gap:0;background:#f1f5f9;border-radius:12px;padding:4px;width:fit-content;margin-bottom:20px}
.tab-pill{padding:9px 26px;border-radius:9px;border:none;cursor:pointer;font-size:13px;font-weight:600;color:#64748b;background:transparent;transition:all .18s;display:flex;align-items:center;gap:7px}
.tab-pill.active{background:#fff;color:#0f2545;box-shadow:0 2px 8px rgba(0,0,0,.1)}
.tab-pill svg{width:15px;height:15px}

/* ── Picker card ── */
.picker-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px 24px;margin-bottom:20px;box-shadow:0 1px 4px rgba(0,0,0,.04)}
.picker-card-inner{display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap}
.picker-card .form-group{margin:0;flex:1;min-width:220px}
.picker-card label{font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px;display:block}

/* ── Select2 custom ── */
.select2-container--default .select2-selection--single{height:42px;border:1.5px solid #e2e8f0;border-radius:9px;display:flex;align-items:center;padding:0 12px;font-size:13.5px;background:#fff}
.select2-container--default .select2-selection--single .select2-selection__rendered{line-height:1;color:#1e293b;padding:0}
.select2-container--default .select2-selection--single .select2-selection__arrow{height:42px;right:10px}
.select2-container--default.select2-container--focus .select2-selection--single{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.12)}
.select2-dropdown{border:1.5px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.1);overflow:hidden;margin-top:4px}
.select2-container--default .select2-search--dropdown .select2-search__field{border:1.5px solid #e2e8f0;border-radius:7px;padding:8px 12px;font-size:13px;outline:none}
.select2-container--default .select2-results__option--highlighted{background:#1e40af;color:#fff}
.select2-results__option{padding:9px 14px;font-size:13px}
.s2-user{display:flex;align-items:center;gap:10px}
.s2-avatar{width:28px;height:28px;border-radius:50%;background:#dbeafe;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;color:#1e40af;overflow:hidden;flex-shrink:0}
.s2-avatar img{width:100%;height:100%;object-fit:cover}

/* ── Chat container ── */
.chat-wrap{flex:1;overflow:hidden;display:flex;flex-direction:column;background:#fff;border:1px solid #e2e8f0;border-radius:14px;box-shadow:0 1px 4px rgba(0,0,0,.04)}
.chat-header{padding:16px 22px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:14px;flex-shrink:0;background:#fff;border-radius:14px 14px 0 0}
.chat-header-avatars{display:flex;align-items:center}
.ch-av{width:40px;height:40px;border-radius:50%;border:2.5px solid #fff;overflow:hidden;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:15px;flex-shrink:0}
.ch-av:nth-child(2){margin-left:-12px}
.ch-av img{width:100%;height:100%;object-fit:cover}
.ch-av-a{background:linear-gradient(135deg,#3b82f6,#1e40af);color:#fff}
.ch-av-b{background:linear-gradient(135deg,#8b5cf6,#6d28d9);color:#fff}
.ch-av-g{background:linear-gradient(135deg,#10b981,#065f46);color:#fff}
.chat-header-info{flex:1}
.chat-header-info h4{font-size:14px;font-weight:700;color:#0f2545;margin-bottom:2px}
.chat-header-info span{font-size:12px;color:#94a3b8}
.chat-stat{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:8px 16px;text-align:center;flex-shrink:0}
.chat-stat .cs-val{font-size:20px;font-weight:800;color:#0f2545;line-height:1}
.chat-stat .cs-lbl{font-size:10px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px}

/* ── Message scroll area ── */
.chat-body{flex:1;overflow-y:auto;padding:20px 24px;display:flex;flex-direction:column;gap:4px;background:#f8fafc;scroll-behavior:smooth}
.chat-body::-webkit-scrollbar{width:5px}
.chat-body::-webkit-scrollbar-thumb{background:#e2e8f0;border-radius:4px}

/* ── Date divider ── */
.date-divider{display:flex;align-items:center;gap:10px;margin:12px 0}
.date-divider span{font-size:11px;font-weight:600;color:#94a3b8;white-space:nowrap;background:#f8fafc;padding:0 8px}
.date-divider::before,.date-divider::after{content:'';flex:1;height:1px;background:#e2e8f0}

/* ── Bubble ── */
.bubble-row{display:flex;gap:10px;align-items:flex-end;margin-bottom:2px}
.bubble-row.right{flex-direction:row-reverse}
.bav{width:30px;height:30px;border-radius:50%;overflow:hidden;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;margin-bottom:2px}
.bav img{width:100%;height:100%;object-fit:cover}
.bav-a{background:linear-gradient(135deg,#3b82f6,#1e40af);color:#fff}
.bav-b{background:linear-gradient(135deg,#8b5cf6,#6d28d9);color:#fff}
.bav-g{background:#dbeafe;color:#1e40af}

.bubble-group{display:flex;flex-direction:column;gap:2px;max-width:62%}
.bubble-row.right .bubble-group{align-items:flex-end}

.bname{font-size:11px;font-weight:600;color:#64748b;margin-bottom:3px;padding:0 4px}

.bubble{position:relative;padding:10px 14px;border-radius:18px;font-size:13.5px;line-height:1.5;word-break:break-word;box-shadow:0 1px 2px rgba(0,0,0,.06)}
.bubble.left{background:#fff;color:#1e293b;border-bottom-left-radius:4px}
.bubble.right{background:linear-gradient(135deg,#1e40af,#3b82f6);color:#fff;border-bottom-right-radius:4px}
.bubble.deleted{background:#f1f5f9;color:#94a3b8;font-style:italic;font-size:12.5px}
.bubble.right.deleted{background:rgba(255,255,255,.15)}

/* ── Reply preview inside bubble ── */
.b-reply{background:rgba(0,0,0,.06);border-left:3px solid #3b82f6;border-radius:6px;padding:6px 10px;margin-bottom:8px;font-size:12px;color:#475569}
.bubble.right .b-reply{background:rgba(255,255,255,.15);border-left-color:rgba(255,255,255,.6);color:rgba(255,255,255,.85)}
.b-reply strong{display:block;font-size:11px;margin-bottom:2px}

/* ── Image bubble ── */
.bubble-img-wrap{padding:4px;border-radius:14px;overflow:hidden;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.08)}
.bubble-img-wrap.right{background:linear-gradient(135deg,#1e40af,#3b82f6)}
.b-img{width:100%;max-width:260px;border-radius:10px;display:block;cursor:pointer;transition:opacity .15s}
.b-img:hover{opacity:.9}
.b-caption{font-size:12.5px;padding:6px 4px 2px;color:#475569}
.bubble-img-wrap.right .b-caption{color:rgba(255,255,255,.85)}

/* ── File bubble ── */
.b-file{display:flex;align-items:center;gap:12px;padding:12px 16px;background:#fff;border-radius:14px;box-shadow:0 1px 3px rgba(0,0,0,.08);min-width:200px}
.b-file.right{background:rgba(255,255,255,.15)}
.b-file-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.b-file-icon.doc{background:#dbeafe}.b-file-icon.vid{background:#ede9fe}.b-file-icon.aud{background:#d1fae5}
.b-file-meta .fn{font-size:13px;font-weight:600;color:#0f2545;margin-bottom:2px}
.b-file-meta .fs{font-size:11px;color:#94a3b8}
.b-file.right .b-file-meta .fn{color:#fff}
.b-file.right .b-file-meta .fs{color:rgba(255,255,255,.6)}
.b-file-open{margin-left:auto;font-size:12px;font-weight:600;padding:5px 10px;border-radius:7px;background:#eff6ff;color:#1e40af;text-decoration:none;white-space:nowrap}
.b-file.right .b-file-open{background:rgba(255,255,255,.2);color:#fff}
.b-file-open:hover{text-decoration:none;opacity:.85}

/* ── Bubble meta ── */
.b-meta{display:flex;align-items:center;gap:5px;margin-top:4px;font-size:10.5px;color:#94a3b8;padding:0 4px}
.bubble-row.right .b-meta{justify-content:flex-end}
.tick-r{color:#3b82f6;font-size:12px}
.tick-g{color:#94a3b8;font-size:12px}
.ds-badge{font-size:10px;padding:1px 6px;border-radius:5px;font-weight:600}
.ds-sent{background:#f1f5f9;color:#94a3b8}
.ds-delivered{background:#fef3c7;color:#92400e}
.ds-read{background:#d1fae5;color:#065f46}

/* ── Group sidebar ── */
.group-layout{display:grid;grid-template-columns:260px 1fr;gap:0;height:100%}
.group-sidebar{border-right:1px solid #e2e8f0;overflow-y:auto;background:#fff;border-radius:14px 0 0 14px}
.group-sidebar-hd{padding:16px 18px 10px;font-size:10.5px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.8px;border-bottom:1px solid #f1f5f9}
.gitem{display:flex;align-items:center;gap:11px;padding:13px 18px;cursor:pointer;border-left:3px solid transparent;transition:all .12s}
.gitem:hover{background:#f8fafc}
.gitem.active{background:#eff6ff;border-left-color:#1e40af}
.gitem-av{width:38px;height:38px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:15px;color:#fff;flex-shrink:0}
.gitem-name{font-size:13.5px;font-weight:600;color:#0f2545}
.gitem-id{font-size:11px;color:#94a3b8}
.group-main{display:flex;flex-direction:column;overflow:hidden;border-radius:0 14px 14px 0}

/* ── Empty state ── */
.empty-chat{display:flex;flex-direction:column;align-items:center;justify-content:center;flex:1;gap:12px;color:#94a3b8;padding:40px}
.empty-chat svg{opacity:.25}
.empty-chat p{font-size:14px;font-weight:500}

/* ── Light box ── */
#lb-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:9999;align-items:center;justify-content:center;cursor:zoom-out}
#lb-overlay.open{display:flex}
#lb-overlay img{max-width:90vw;max-height:90vh;border-radius:10px;box-shadow:0 20px 60px rgba(0,0,0,.5)}

/* ── Pagination ── */
.pager{display:flex;gap:4px;padding:14px 20px;border-top:1px solid #f1f5f9;flex-wrap:wrap;flex-shrink:0}
.pager a{padding:6px 13px;border-radius:7px;font-size:12.5px;border:1.5px solid #e2e8f0;color:#475569;background:#fff;transition:all .12s;font-weight:500}
.pager a:hover{border-color:#3b82f6;color:#1e40af;text-decoration:none}
.pager a.active{background:#1e40af;border-color:#1e40af;color:#fff}

@media(max-width:900px){
  .group-layout{grid-template-columns:1fr}
  .group-sidebar{max-height:220px;border-right:none;border-bottom:1px solid #e2e8f0;border-radius:14px 14px 0 0}
  .group-main{border-radius:0 0 14px 14px}
  .picker-card-inner{flex-direction:column}
}
</style>

<div style="display:flex;min-height:100vh">
<?php include APPPATH . 'Views/admin/partials/sidebar.php' ?>
<div class="main">
<?php include APPPATH . 'Views/admin/partials/topbar.php' ?>

<div style="padding:20px 28px;display:flex;flex-direction:column;height:calc(100vh - 60px);overflow:hidden">

  <!-- Tab pills -->
  <div class="tab-pills">
    <button class="tab-pill <?= $tab==='direct'?'active':'' ?>" onclick="switchTab('direct')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      Direct Messages
    </button>
    <button class="tab-pill <?= $tab==='group'?'active':'' ?>" onclick="switchTab('group')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Group Messages
    </button>
  </div>

  <!-- ══ DIRECT ══════════════════════════════════════ -->
  <div id="tab-direct" style="display:<?= $tab==='direct'?'flex':'none' ?>;flex-direction:column;flex:1;overflow:hidden;gap:16px">

    <!-- User picker -->
    <div class="picker-card">
      <form method="get" action="<?= base_url('admin/messages/conversation') ?>" id="convForm">
        <div class="picker-card-inner">
          <div class="form-group" style="flex:1;min-width:200px">
            <label>User A</label>
            <select name="user_a" id="sel_a" required>
              <option value="">Search user...</option>
              <?php foreach($users as $u): ?>
                <option value="<?= $u['id'] ?>" data-photo="<?= $u['photo']?base_url('uploads/'.$u['photo']):'' ?>" <?= $userA==$u['id']?'selected':'' ?>>
                  <?= esc($u['name']) ?> — @<?= esc($u['username']) ?>
                </option>
              <?php endforeach ?>
            </select>
          </div>
          <div style="display:flex;align-items:center;padding-bottom:4px;color:#94a3b8">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>
          </div>
          <div class="form-group" style="flex:1;min-width:200px">
            <label>User B</label>
            <select name="user_b" id="sel_b" required>
              <option value="">Search user...</option>
              <?php foreach($users as $u): ?>
                <option value="<?= $u['id'] ?>" data-photo="<?= $u['photo']?base_url('uploads/'.$u['photo']):'' ?>" <?= $userB==$u['id']?'selected':'' ?>>
                  <?= esc($u['name']) ?> — @<?= esc($u['username']) ?>
                </option>
              <?php endforeach ?>
            </select>
          </div>
          <button type="submit" class="btn btn-primary" style="height:42px;padding:0 24px">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            View Conversation
          </button>
        </div>
      </form>
    </div>

    <?php if($personA && $personB): ?>
      <div class="chat-wrap" style="flex:1;overflow:hidden;display:flex;flex-direction:column">
        <!-- Chat header -->
        <div class="chat-header">
          <div class="chat-header-avatars">
            <div class="ch-av ch-av-a">
              <?php if($personA['photo']): ?><img src="<?= base_url('uploads/'.$personA['photo']) ?>"><?php else: echo strtoupper(substr($personA['name'],0,1)); endif ?>
            </div>
            <div class="ch-av ch-av-b">
              <?php if($personB['photo']): ?><img src="<?= base_url('uploads/'.$personB['photo']) ?>"><?php else: echo strtoupper(substr($personB['name'],0,1)); endif ?>
            </div>
          </div>
          <div class="chat-header-info">
            <h4><?= esc($personA['name']) ?> &amp; <?= esc($personB['name']) ?></h4>
            <span>@<?= esc($personA['username']) ?> &nbsp;·&nbsp; @<?= esc($personB['username']) ?></span>
          </div>
          <div class="chat-stat">
            <div class="cs-val"><?= $total ?></div>
            <div class="cs-lbl">Messages</div>
          </div>
        </div>

        <!-- Messages -->
        <?php if(empty($messages)): ?>
          <div class="empty-chat">
            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <p>No messages between these users.</p>
          </div>
        <?php else: ?>
          <div class="chat-body" id="chatBody">
            <?php
              $prevDate = '';
              foreach(array_reverse($messages) as $msg):
                $isMine   = ($msg['sender_id'] == $userA);
                $side     = $isMine ? 'right' : 'left';
                $sender   = $isMine ? $personA : $personB;
                $msgDate  = date('M j, Y', strtotime($msg['created_at']));
                $msgTime  = date('g:i a', strtotime($msg['created_at']));
            ?>
            <?php if($msgDate !== $prevDate): $prevDate = $msgDate; ?>
              <div class="date-divider"><span><?= $msgDate ?></span></div>
            <?php endif ?>

            <div class="bubble-row <?= $side ?>">
              <div class="bav <?= $isMine?'bav-a':'bav-b' ?>">
                <?php if($sender['photo']): ?><img src="<?= base_url('uploads/'.$sender['photo']) ?>"><?php else: echo strtoupper(substr($sender['name'],0,1)); endif ?>
              </div>
              <div class="bubble-group">
                <div class="bname"><?= esc($sender['name']) ?></div>

                <?php if(!empty($msg['reply_content'])): ?>
                  <?php if($msg['type']==='image' || ($msg['type']!=='text' && $msg['file_path'])): ?>
                  <div class="bubble-img-wrap <?= $side ?>" style="max-width:280px">
                    <div class="b-reply">
                      <strong><?= esc($msg['reply_sender_name']) ?></strong>
                      <?= esc(mb_strimwidth($msg['reply_content'],0,80,'...')) ?>
                    </div>
                  </div>
                  <?php else: ?>
                  <div class="bubble <?= $side ?>">
                    <div class="b-reply">
                      <strong><?= esc($msg['reply_sender_name']) ?></strong>
                      <?= esc(mb_strimwidth($msg['reply_content'],0,80,'...')) ?>
                    </div>
                  </div>
                  <?php endif ?>
                <?php endif ?>

                <?php if(!empty($msg['is_deleted'])): ?>
                  <div class="bubble <?= $side ?> deleted">
                    <svg width="13" height="13" style="vertical-align:middle;margin-right:5px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                    This message was deleted
                  </div>
                <?php elseif($msg['type']==='image' && $msg['file_path']): ?>
                  <div class="bubble-img-wrap <?= $side ?>">
                    <?php if(!empty($msg['reply_content'])): ?>
                    <div class="b-reply">
                      <strong><?= esc($msg['reply_sender_name']) ?></strong>
                      <?= esc(mb_strimwidth($msg['reply_content'],0,80,'...')) ?>
                    </div>
                    <?php endif ?>
                    <img src="<?= base_url('uploads/'.$msg['file_path']) ?>" class="b-img" onclick="openLb(this.src)" loading="lazy">
                    <?php if($msg['content']): ?><div class="b-caption"><?= esc($msg['content']) ?></div><?php endif ?>
                  </div>
                <?php elseif($msg['type']==='text'): ?>
                  <div class="bubble <?= $side ?>">
                    <?php if(!empty($msg['reply_content']) && empty($shown_reply)): ?>
                    <div class="b-reply">
                      <strong><?= esc($msg['reply_sender_name']) ?></strong>
                      <?= esc(mb_strimwidth($msg['reply_content'],0,80,'...')) ?>
                    </div>
                    <?php endif ?>
                    <?= nl2br(esc($msg['content'])) ?>
                  </div>
                <?php elseif(in_array($msg['type'],['voice','audio'])): ?>
                  <div class="b-file <?= $side ?>">
                    <div class="b-file-icon aud">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                    </div>
                    <div class="b-file-meta"><div class="fn"><?= esc($msg['file_name'] ?? 'Voice message') ?></div><?php if($msg['file_size']): ?><div class="fs"><?= round($msg['file_size']/1024,1) ?> KB</div><?php endif ?></div>
                    <?php if($msg['file_path']): ?><a href="<?= base_url('uploads/'.$msg['file_path']) ?>" target="_blank" class="b-file-open">Play</a><?php endif ?>
                  </div>
                <?php elseif($msg['type']==='video'): ?>
                  <div class="b-file <?= $side ?>">
                    <div class="b-file-icon vid">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                    </div>
                    <div class="b-file-meta"><div class="fn"><?= esc($msg['file_name'] ?? 'Video') ?></div><?php if($msg['file_size']): ?><div class="fs"><?= round($msg['file_size']/1024,1) ?> KB</div><?php endif ?></div>
                    <?php if($msg['file_path']): ?><a href="<?= base_url('uploads/'.$msg['file_path']) ?>" target="_blank" class="b-file-open">Open</a><?php endif ?>
                  </div>
                <?php else: ?>
                  <div class="b-file <?= $side ?>">
                    <div class="b-file-icon doc">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1e40af" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                    <div class="b-file-meta"><div class="fn"><?= esc($msg['file_name'] ?? strtoupper($msg['type'])) ?></div><?php if($msg['file_size']): ?><div class="fs"><?= round($msg['file_size']/1024,1) ?> KB</div><?php endif ?></div>
                    <?php if($msg['file_path']): ?><a href="<?= base_url('uploads/'.$msg['file_path']) ?>" target="_blank" class="b-file-open">Open</a><?php endif ?>
                  </div>
                <?php endif ?>

                <div class="b-meta">
                  <span><?= $msgTime ?></span>
                  <?php if($isMine): ?>
                    <?php if($msg['delivery_status']==='read'): ?>
                      <span class="tick-r">✓✓</span><span class="ds-badge ds-read">seen</span>
                    <?php elseif($msg['delivery_status']==='delivered'): ?>
                      <span class="tick-g">✓✓</span><span class="ds-badge ds-delivered">delivered</span>
                    <?php else: ?>
                      <span class="tick-g">✓</span><span class="ds-badge ds-sent">sent</span>
                    <?php endif ?>
                  <?php endif ?>
                </div>

              </div>
            </div>
            <?php endforeach ?>
          </div>
          <?php if($pages > 1): ?>
          <div class="pager">
            <?php for($i=1;$i<=$pages;$i++): ?>
              <a href="<?= base_url('admin/messages/conversation') ?>?user_a=<?= $userA ?>&user_b=<?= $userB ?>&page=<?= $i ?>" class="<?= $i==$page?'active':'' ?>"><?= $i ?></a>
            <?php endfor ?>
          </div>
          <?php endif ?>
        <?php endif ?>
      </div>

    <?php else: ?>
      <div class="chat-wrap" style="flex:1;display:flex">
        <div class="empty-chat">
          <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          <p>Select two users above to view their conversation.</p>
        </div>
      </div>
    <?php endif ?>
  </div>

  <!-- ══ GROUP ═══════════════════════════════════════ -->
  <div id="tab-group" style="display:<?= $tab==='group'?'flex':'none' ?>;flex:1;overflow:hidden">
    <div class="chat-wrap" style="flex:1">
      <div class="group-layout" style="flex:1;overflow:hidden">

        <!-- Sidebar -->
        <div class="group-sidebar">
          <div class="group-sidebar-hd">Groups</div>
          <?php foreach(($groups ?? []) as $idx => $g):
            $colors = ['#3b82f6','#8b5cf6','#10b981','#f97316','#ef4444','#06b6d4'];
            $color  = $colors[$g['id'] % count($colors)];
          ?>
          <a href="<?= base_url('admin/messages/group') ?>?group_id=<?= $g['id'] ?>" class="gitem <?= $groupId==$g['id']?'active':'' ?>" style="text-decoration:none">
            <div class="gitem-av" style="background:<?= $color ?>"><?= strtoupper(substr($g['name'],0,1)) ?></div>
            <div>
              <div class="gitem-name"><?= esc($g['name']) ?></div>
              <div class="gitem-id">Group #<?= $g['id'] ?></div>
            </div>
          </a>
          <?php endforeach ?>
        </div>

        <!-- Main -->
        <div class="group-main">
          <?php if($activeGroup): ?>
            <div class="chat-header">
              <?php
                $colors = ['#3b82f6','#8b5cf6','#10b981','#f97316','#ef4444','#06b6d4'];
                $gc = $colors[$activeGroup['id'] % count($colors)];
              ?>
              <div class="ch-av ch-av-g" style="background:<?= $gc ?>"><?= strtoupper(substr($activeGroup['name'],0,1)) ?></div>
              <div class="chat-header-info" style="margin-left:10px">
                <h4><?= esc($activeGroup['name']) ?></h4>
                <span>Group conversation</span>
              </div>
              <div class="chat-stat">
                <div class="cs-val"><?= $total ?></div>
                <div class="cs-lbl">Messages</div>
              </div>
            </div>

            <?php if(empty($messages)): ?>
              <div class="empty-chat">
                <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <p>No messages in this group.</p>
              </div>
            <?php else: ?>
              <div class="chat-body">
                <?php $prevDate = ''; foreach(array_reverse($messages) as $msg):
                  $msgDate = date('M j, Y', strtotime($msg['created_at']));
                  $msgTime = date('g:i a', strtotime($msg['created_at']));
                ?>
                <?php if($msgDate !== $prevDate): $prevDate = $msgDate; ?>
                  <div class="date-divider"><span><?= $msgDate ?></span></div>
                <?php endif ?>

                <div class="bubble-row left">
                  <div class="bav bav-g">
                    <?php if($msg['sender_photo']): ?><img src="<?= base_url('uploads/'.$msg['sender_photo']) ?>"><?php else: echo strtoupper(substr($msg['sender_name'],0,1)); endif ?>
                  </div>
                  <div class="bubble-group">
                    <div class="bname"><?= esc($msg['sender_name']) ?> <span style="color:#cbd5e1;font-weight:400">@<?= esc($msg['sender_username']) ?></span></div>

                    <?php if(!empty($msg['reply_content'])): ?>
                      <div class="bubble left">
                        <div class="b-reply">
                          <strong><?= esc($msg['reply_sender_name']) ?></strong>
                          <?= esc(mb_strimwidth($msg['reply_content'],0,80,'...')) ?>
                        </div>
                      </div>
                    <?php endif ?>

                    <?php if($msg['type']==='image' && !empty($msg['file_path'])): ?>
                      <div class="bubble-img-wrap left">
                        <img src="<?= base_url('uploads/'.$msg['file_path']) ?>" class="b-img" onclick="openLb(this.src)" loading="lazy">
                      </div>
                    <?php elseif($msg['type']==='text'): ?>
                      <div class="bubble left"><?= nl2br(esc($msg['content'])) ?></div>
                    <?php elseif(in_array($msg['type'],['voice','audio'])): ?>
                      <div class="b-file left">
                        <div class="b-file-icon aud"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg></div>
                        <div class="b-file-meta"><div class="fn"><?= esc($msg['file_name'] ?? 'Voice') ?></div><?php if(!empty($msg['file_size'])): ?><div class="fs"><?= round($msg['file_size']/1024,1) ?> KB</div><?php endif ?></div>
                        <?php if(!empty($msg['file_path'])): ?><a href="<?= base_url('uploads/'.$msg['file_path']) ?>" target="_blank" class="b-file-open">Play</a><?php endif ?>
                      </div>
                    <?php else: ?>
                      <div class="b-file left">
                        <div class="b-file-icon doc"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1e40af" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
                        <div class="b-file-meta"><div class="fn"><?= esc($msg['file_name'] ?? strtoupper($msg['type'])) ?></div><?php if(!empty($msg['file_size'])): ?><div class="fs"><?= round($msg['file_size']/1024,1) ?> KB</div><?php endif ?></div>
                        <?php if(!empty($msg['file_path'])): ?><a href="<?= base_url('uploads/'.$msg['file_path']) ?>" target="_blank" class="b-file-open">Open</a><?php endif ?>
                      </div>
                    <?php endif ?>

                    <div class="b-meta">
                      <span><?= $msgTime ?></span>
                      <?php if(!empty($msg['seen_by_count']) && $msg['seen_by_count'] > 0): ?>
                        <span class="tick-r">✓✓</span>
                        <span class="ds-badge ds-read">seen by <?= $msg['seen_by_count'] ?></span>
                      <?php endif ?>
                    </div>
                  </div>
                </div>
                <?php endforeach ?>
              </div>

              <?php if($pages > 1): ?>
              <div class="pager">
                <?php for($i=1;$i<=$pages;$i++): ?>
                  <a href="<?= base_url('admin/messages/group') ?>?group_id=<?= $groupId ?>&page=<?= $i ?>" class="<?= $i==$page?'active':'' ?>"><?= $i ?></a>
                <?php endfor ?>
              </div>
              <?php endif ?>
            <?php endif ?>

          <?php else: ?>
            <div class="empty-chat">
              <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
              <p>Select a group from the left.</p>
            </div>
          <?php endif ?>
        </div>

      </div>
    </div>
  </div>

</div><!-- /padding wrap -->
</div><!-- /main -->
</div><!-- /flex -->

<!-- Lightbox -->
<div id="lb-overlay" onclick="closeLb()">
  <img id="lb-img" src="">
</div>

<script>
$(function(){
  function userTemplate(opt) {
    if (!opt.id) return opt.text;
    var photo = $(opt.element).data('photo');
    var initial = opt.text.charAt(0).toUpperCase();
    var av = photo
      ? '<div class="s2-avatar"><img src="'+photo+'"></div>'
      : '<div class="s2-avatar">'+initial+'</div>';
    return $('<span class="s2-user">'+av+'<span>'+opt.text+'</span></span>');
  }
  $('#sel_a, #sel_b').select2({
    placeholder: 'Search user...',
    allowClear: true,
    templateResult: userTemplate,
    templateSelection: userTemplate,
    width: '100%',
  });
});

function switchTab(tab) {
  document.getElementById('tab-direct').style.display = tab==='direct'?'flex':'none';
  document.getElementById('tab-group').style.display  = tab==='group' ?'flex':'none';
  document.querySelectorAll('.tab-pill').forEach((b,i)=>{
    b.classList.toggle('active',(i===0&&tab==='direct')||(i===1&&tab==='group'));
  });
}

function openLb(src){ document.getElementById('lb-img').src=src; document.getElementById('lb-overlay').classList.add('open'); }
function closeLb(){ document.getElementById('lb-overlay').classList.remove('open'); }
document.addEventListener('keydown',e=>{ if(e.key==='Escape') closeLb(); });

// Scroll chat to bottom on load
window.addEventListener('load',()=>{
  var cb = document.getElementById('chatBody');
  if(cb) cb.scrollTop = cb.scrollHeight;
});
</script>
