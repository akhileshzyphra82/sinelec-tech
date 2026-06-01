<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/support_controller.php';

$currentPage = 'ticket';
$pageTitle   = 'Ticket Detail';

$canView = sinelec_can('view');
$canEdit = sinelec_can('edit');
$canAdd  = sinelec_can('add');

if (!$canView) {
    sinelec_set_flash('err', 'No permission.');
    header('location:dashboard'); exit();
}

$ctrl     = new SupportController();
$ticketId = (int)($_GET['id'] ?? 0);
if ($ticketId <= 0) { header('location:ticket'); exit(); }

$ticket = $ctrl->getTicketById($ticketId);
if (!$ticket) { sinelec_set_flash('err', 'Ticket not found.'); header('location:ticket'); exit(); }

$currentStatus = (string)($ticket->CURRENT_STATUS ?? 'Open');
$categoryType  = (string)($ticket->CATEGORY_TYPE  ?? 'Other');
$ticketNumber  = (string)($ticket->TICKET_NUMBER  ?? '');
$messages      = $ctrl->getTicketMessages($ticketId, true); // include internal notes
$returnItems   = [];
if (in_array($categoryType, ['Return & Refund', 'Return & Replacement'], true)) {
    $returnItems = $ctrl->getReturnItems($ticketId);
}
$adminUsers = $ctrl->getAdminUsers();

/* Build a server-relative base URL so attachment links work on local dev and production */
$_docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
$_appRoot = rtrim((string)realpath(__DIR__ . '/..'), '/\\');
$BASE_URL = ($_docRoot !== '' && str_starts_with($_appRoot, $_docRoot))
            ? str_replace($_docRoot, '', $_appRoot)
            : rtrim((string)sinelec_env('PUBLIC_BASE_URL', ''), '/');

$admin       = $_SESSION['sinelec_admin'] ?? [];
$adminId     = (int)(float)($admin['USER_ID']      ?? 0);
$adminName   = (string)($admin['NAME']             ?? 'Admin');
$adminTypeId = (int)(float)($admin['USER_TYPE_ID'] ?? 1);

function tktdStatusBadge(string $s): string {
    $map = ['Open'=>'#dbeafe;color:#1d4ed8','In Progress'=>'#fef3c7;color:#92400e','Resolved'=>'#d1fae5;color:#065f46','Closed'=>'#f1f5f9;color:#475569','Reopened'=>'#fce7f3;color:#9d174d'];
    $st = $map[$s] ?? '#f1f5f9;color:#475569';
    return '<span class="tktd-status-badge" style="background:' . $st . '">' . htmlspecialchars($s) . '</span>';
}

ob_start();
?>
<style>
/* ── Support Ticket Detail (admin) ── */
.tktd-shell { display:grid; grid-template-columns:1fr 300px; gap:20px; align-items:start; }
@media(max-width:900px){ .tktd-shell{ grid-template-columns:1fr; } }

/* Header */
.tktd-header { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:22px; flex-wrap:wrap; }
.tktd-header-left h1 { font-size:20px; font-weight:700; color:#0a1a30; margin:0 0 6px; }
.tktd-header-meta { display:flex; align-items:center; gap:8px; font-size:13px; color:#64748b; flex-wrap:wrap; }
.tktd-tn { font-family:monospace; font-weight:700; color:#374151; }

.tktd-status-badge { font-size:11px; font-weight:700; padding:4px 12px; border-radius:20px; }

/* Thread */
.tktd-thread { display:flex; flex-direction:column; gap:16px; margin-bottom:20px; }
.tktd-msg { display:flex; gap:12px; }
.tktd-msg--customer { flex-direction:row; }
.tktd-msg--admin    { flex-direction:row-reverse; }
.tktd-msg--internal { flex-direction:row; opacity:.9; }
.tktd-msg--auto     { justify-content:center; }

.tktd-avatar { width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; flex-shrink:0; }
.tktd-av--customer { background:#3b82f6; color:#fff; }
.tktd-av--admin    { background:#0a1a30; color:#fff; }
.tktd-av--internal { background:#f59e0b; color:#fff; }

.tktd-bubble { max-width:70%; }
.tktd-msg--admin .tktd-bubble { align-items:flex-end; }

.tktd-bubble-meta { display:flex; align-items:center; gap:7px; margin-bottom:5px; }
.tktd-msg--admin .tktd-bubble-meta { flex-direction:row-reverse; }
.tktd-bubble-sender { font-size:13px; font-weight:600; color:#1e293b; }
.tktd-bubble-role { font-size:11px; padding:1px 7px; border-radius:20px; }
.tktd-role--admin { background:#0a1a30; color:#fff; }
.tktd-role--internal { background:#f59e0b; color:#fff; }
.tktd-bubble-time { font-size:11px; color:#94a3b8; }

.tktd-bubble-text { font-size:14px; color:#374151; line-height:1.65; border-radius:12px; padding:11px 15px; border:1px solid; }
.tktd-msg--customer .tktd-bubble-text { background:#f8fafc; border-color:#e2e8f0; border-bottom-left-radius:4px; }
.tktd-msg--admin .tktd-bubble-text    { background:#eff6ff; border-color:#bfdbfe; border-bottom-right-radius:4px; }
.tktd-msg--internal .tktd-bubble-text { background:#fffbeb; border-color:#fde68a; border-left:3px solid #f59e0b; border-bottom-left-radius:4px; }
.tktd-bubble-auto { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; font-size:13px; font-style:italic; border-radius:20px; padding:7px 16px; text-align:center; }

.tktd-status-pill { display:flex; justify-content:center; }
.tktd-status-pill > span { font-size:12px; color:#64748b; background:#f1f5f9; border:1px solid #e2e8f0; padding:3px 14px; border-radius:20px; }

.tktd-att-list { display:flex; flex-wrap:wrap; gap:7px; margin-top:9px; }
.tktd-att-img  { width:72px; height:72px; object-fit:cover; border-radius:8px; border:1px solid #e2e8f0; }
.tktd-att-file { display:inline-flex; align-items:center; gap:4px; font-size:12px; color:#3b82f6; background:#eff6ff; border:1px solid #bfdbfe; padding:4px 9px; border-radius:6px; text-decoration:none; }

/* Reply box */
.tktd-reply { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:18px; }
.tktd-reply-tabs { display:flex; gap:2px; margin-bottom:12px; }
.tktd-rtab { border:none; background:none; padding:7px 16px; border-radius:8px; font-size:13px; font-weight:600; color:#64748b; cursor:pointer; transition:all .15s; }
.tktd-rtab.is-active { background:#0a1a30; color:#fff; }
.tktd-rtab--note.is-active { background:#f59e0b; color:#fff; }

.tktd-reply-textarea { width:100%; box-sizing:border-box; border:1px solid #e2e8f0; border-radius:10px; padding:11px 14px; font-size:14px; color:#1e293b; resize:vertical; min-height:90px; font-family:inherit; outline:none; transition:border .2s; }
.tktd-reply-textarea:focus { border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.1); }
.tktd-reply-textarea--note:focus { border-color:#f59e0b; box-shadow:0 0 0 3px rgba(245,158,11,.1); }

.tktd-reply-footer { display:flex; align-items:center; justify-content:space-between; margin-top:10px; flex-wrap:wrap; gap:8px; }
.tktd-att-zone { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
.tktd-att-btn { display:inline-flex; align-items:center; gap:5px; font-size:12px; color:#64748b; background:#f8fafc; border:1px dashed #cbd5e1; padding:6px 11px; border-radius:8px; cursor:pointer; }
.tktd-att-btn:hover { border-color:#3b82f6; color:#3b82f6; }
.tktd-att-chip { display:inline-flex; align-items:center; gap:4px; font-size:11px; background:#f1f5f9; border:1px solid #e2e8f0; padding:3px 8px; border-radius:20px; color:#64748b; }
.tktd-att-chip button { background:none; border:none; cursor:pointer; padding:0; color:#94a3b8; font-size:13px; }

.tktd-send-btn { display:inline-flex; align-items:center; gap:6px; background:#0a1a30; color:#fff; border:none; padding:9px 20px; border-radius:10px; font-size:14px; font-weight:600; cursor:pointer; transition:background .15s,opacity .15s; min-width:130px; justify-content:center; }
.tktd-send-btn:hover:not(:disabled) { background:#1e3a5f; }
.tktd-send-btn--note { background:#f59e0b; }
.tktd-send-btn--note:hover:not(:disabled) { background:#d97706; }
.tktd-send-btn:disabled { opacity:.65; cursor:not-allowed; }
.tktd-reply-err { margin-top:8px; font-size:13px; color:#dc2626; display:none; }
/* spinner */
@keyframes tktd-spin { to { transform:rotate(360deg); } }
.tktd-spinner { width:15px; height:15px; border:2.5px solid rgba(255,255,255,.35); border-top-color:#fff; border-radius:50%; animation:tktd-spin .65s linear infinite; flex-shrink:0; }

/* Sidebar */
.tktd-sidebar { display:flex; flex-direction:column; gap:14px; }
.tktd-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:18px; }
.tktd-card-title { font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#94a3b8; margin-bottom:14px; }
.tktd-info-row { display:flex; justify-content:space-between; align-items:flex-start; gap:8px; font-size:13px; margin-bottom:10px; }
.tktd-info-row:last-child { margin-bottom:0; }
.tktd-info-lbl { color:#64748b; flex-shrink:0; }
.tktd-info-val { font-weight:600; color:#1e293b; text-align:right; }

.tktd-ctrl-group { margin-bottom:12px; }
.tktd-ctrl-label { font-size:12px; color:#64748b; margin-bottom:5px; display:block; }
.tktd-ctrl-select { width:100%; border:1px solid #e2e8f0; border-radius:8px; padding:7px 10px; font-size:13px; font-weight:600; background:#fff; cursor:pointer; outline:none; }
.tktd-ctrl-select:focus { border-color:#3b82f6; }

/* Return items panel */
.tktd-return-panel { background:#faf5ff; border:1px solid #e9d5ff; border-radius:12px; padding:14px 16px; margin-bottom:16px; }
.tktd-return-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#7c3aed; margin-bottom:10px; }
.tktd-ri { display:flex; flex-wrap:wrap; gap:6px; align-items:center; padding:8px 12px; background:#fff; border:1px solid #e9d5ff; border-radius:8px; margin-bottom:6px; font-size:13px; }
.tktd-ri-name { font-weight:600; color:#1e293b; }
.tktd-ri-qty { font-size:12px; color:#7c3aed; font-weight:700; background:#f3e8ff; padding:2px 8px; border-radius:20px; }
</style>

<div class="pg-header">
  <div>
    <a href="ticket" style="font-size:13px;color:#64748b;text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:6px">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
      All Tickets
    </a>
    <div class="pg-title"><?= htmlspecialchars($ticketNumber) ?></div>
  </div>
  <div style="display:flex;gap:8px;align-items:center">
    <?= tktdStatusBadge($currentStatus) ?>
  </div>
</div>

<div class="tktd-shell">

  <!-- LEFT: Thread + Reply -->
  <div>
    <div class="tktd-header-left" style="margin-bottom:18px">
      <h1><?= htmlspecialchars((string)($ticket->SUBJECT ?? '')) ?></h1>
      <div class="tktd-header-meta">
        <span class="tktd-tn"><?= htmlspecialchars($ticketNumber) ?></span>
        <span style="color:#cbd5e1">·</span>
        <span><?= htmlspecialchars((string)($ticket->CATEGORY_NAME ?? '')) ?></span>
        <?php if (!empty($ticket->ORDER_NUMBER)): ?>
        <span style="color:#cbd5e1">·</span>
        <span>Order <?= htmlspecialchars((string)$ticket->ORDER_NUMBER) ?></span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Return items -->
    <?php if (!empty($returnItems)): ?>
    <div class="tktd-return-panel">
      <div class="tktd-return-title">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4"/></svg>
        Return Items
      </div>
      <?php foreach ($returnItems as $ri): ?>
      <div class="tktd-ri">
        <span class="tktd-ri-name"><?= htmlspecialchars((string)($ri->PRODUCT_NAME ?? '')) ?></span>
        <span style="font-size:11px;color:#94a3b8"><?= htmlspecialchars((string)($ri->PRODUCT_CODE ?? '')) ?></span>
        <span class="tktd-ri-qty">Return <?= (int)($ri->RETURN_QTY ?? 0) ?> / <?= (int)($ri->ORDERED_QTY ?? 0) ?></span>
        <?php if (!empty($ri->RETURN_REASON)): ?>
        <span style="font-size:12px;color:#64748b;font-style:italic">"<?= htmlspecialchars((string)$ri->RETURN_REASON) ?>"</span>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Thread -->
    <div class="tktd-thread" id="tktdThread">
      <?php
      $prevStatus = null;
      foreach ($messages as $msg):
        $msgStatus  = (string)($msg->LAST_STATUS ?? 'Open');
        $isAutoReply = (bool)($msg->IS_AUTO_REPLY ?? false);
        $isInternal  = (bool)($msg->IS_INTERNAL_NOTE ?? false);
        $senderType  = (int)(float)($msg->USER_TYPE_ID ?? 2);
        $isAdminMsg  = $senderType === 1 || $senderType === 3;
        $senderName  = htmlspecialchars((string)($msg->SENDER_NAME ?? 'User'));
        $bodyHtml    = nl2br(htmlspecialchars((string)($msg->MESSAGE_BODY ?? '')));
        $timeAgo     = $ctrl->timeAgo((string)($msg->CREATED_AT ?? ''));
        $dateTime    = $ctrl->fmtDateTime((string)($msg->CREATED_AT ?? ''));
        $msgId       = (int)(float)($msg->MESSAGE_ID ?? 0);

        $attachments = [];
        $attRaw = (string)($msg->ATTACHMENTS_RAW ?? '');
        if ($attRaw !== '') {
            foreach (explode(';;', $attRaw) as $part) {
                $bits = explode('|', $part, 3);
                if (count($bits) === 3) $attachments[] = ['id'=>$bits[0],'name'=>$bits[1],'path'=>$bits[2]];
            }
        }

        if ($prevStatus !== null && $msgStatus !== $prevStatus && !$isAutoReply):
      ?>
      <div class="tktd-status-pill">
        <span>Status changed to <strong><?= htmlspecialchars($msgStatus) ?></strong></span>
      </div>
      <?php
        endif;
        $prevStatus = $msgStatus;

        if ($isAutoReply):
      ?>
      <div class="tktd-msg tktd-msg--auto">
        <div class="tktd-bubble-auto"><?= $bodyHtml ?></div>
      </div>
      <?php elseif ($isInternal): ?>
      <div class="tktd-msg tktd-msg--internal">
        <div class="tktd-avatar tktd-av--internal"><?= strtoupper(mb_substr($senderName, 0, 1)) ?></div>
        <div class="tktd-bubble">
          <div class="tktd-bubble-meta">
            <span class="tktd-bubble-sender"><?= $senderName ?></span>
            <span class="tktd-bubble-role tktd-role--internal">Internal Note</span>
            <span class="tktd-bubble-time" title="<?= htmlspecialchars($dateTime) ?>"><?= $timeAgo ?></span>
          </div>
          <div class="tktd-bubble-text"><?= $bodyHtml ?></div>
          <?php if (!empty($attachments)): ?><div class="tktd-att-list"><?php foreach ($attachments as $att): ?><?php $ext=strtolower(pathinfo($att['name'],PATHINFO_EXTENSION)); $isImg=in_array($ext,['jpg','jpeg','png','webp','gif'],true); $url=$BASE_URL.'/'.ltrim($att['path'],'/'); ?><?php if($isImg):?><a href="<?=htmlspecialchars($url)?>" target="_blank"><img src="<?=htmlspecialchars($url)?>" class="tktd-att-img"></a><?php else:?><a href="<?=htmlspecialchars($url)?>" class="tktd-att-file" target="_blank"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><?=htmlspecialchars($att['name'])?></a><?php endif?><?php endforeach;?></div><?php endif; ?>
        </div>
      </div>
      <?php else: $msgClass = $isAdminMsg ? 'tktd-msg--admin' : 'tktd-msg--customer'; $avClass = $isAdminMsg ? 'tktd-av--admin' : 'tktd-av--customer'; ?>
      <div class="tktd-msg <?= $msgClass ?>">
        <div class="tktd-avatar <?= $avClass ?>"><?= strtoupper(mb_substr($senderName, 0, 1)) ?></div>
        <div class="tktd-bubble">
          <div class="tktd-bubble-meta">
            <span class="tktd-bubble-sender"><?= $senderName ?></span>
            <?php if ($isAdminMsg): ?><span class="tktd-bubble-role tktd-role--admin">Support</span><?php endif; ?>
            <span class="tktd-bubble-time" title="<?= htmlspecialchars($dateTime) ?>"><?= $timeAgo ?></span>
          </div>
          <div class="tktd-bubble-text"><?= $bodyHtml ?></div>
          <?php if (!empty($attachments)): ?><div class="tktd-att-list"><?php foreach ($attachments as $att): ?><?php $ext=strtolower(pathinfo($att['name'],PATHINFO_EXTENSION)); $isImg=in_array($ext,['jpg','jpeg','png','webp','gif'],true); $url=$BASE_URL.'/'.ltrim($att['path'],'/'); ?><?php if($isImg):?><a href="<?=htmlspecialchars($url)?>" target="_blank"><img src="<?=htmlspecialchars($url)?>" class="tktd-att-img"></a><?php else:?><a href="<?=htmlspecialchars($url)?>" class="tktd-att-file" target="_blank"><?=htmlspecialchars($att['name'])?></a><?php endif?><?php endforeach;?></div><?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
      <?php endforeach; ?>
    </div><!-- /thread -->

    <!-- Reply / Internal Note box -->
    <?php if ($canAdd): ?>
    <div class="tktd-reply">
      <div class="tktd-reply-tabs">
        <button type="button" class="tktd-rtab is-active" id="tktdTabReply" onclick="tktdSwitchTab('reply')">Reply to Customer</button>
        <button type="button" class="tktd-rtab tktd-rtab--note" id="tktdTabNote"  onclick="tktdSwitchTab('note')">Internal Note</button>
      </div>
      <textarea class="tktd-reply-textarea" id="tktdReplyBody" rows="4"
                placeholder="Type your reply to the customer…"></textarea>
      <div class="tktd-reply-footer">
        <div class="tktd-att-zone">
          <label class="tktd-att-btn" for="tktdAttInput">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
            Attach
          </label>
          <input type="file" id="tktdAttInput" style="display:none" multiple accept=".jpg,.jpeg,.png,.webp,.gif,.pdf">
          <div id="tktdAttPreview" class="tktd-att-zone"></div>
        </div>
        <button type="button" class="tktd-send-btn" id="tktdSendBtn" onclick="tktdSend()">
          <span class="tktd-spinner" id="tktdSpinner" style="display:none;"></span>
          <svg id="tktdSendIcon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
          <span id="tktdSendLabel">Send Reply</span>
        </button>
      </div>
      <div class="tktd-reply-err" id="tktdReplyErr"></div>
    </div>
    <?php endif; ?>
  </div>

  <!-- RIGHT: Sidebar -->
  <div class="tktd-sidebar">

    <!-- Status / Priority / Assign Controls -->
    <?php if ($canEdit): ?>
    <div class="tktd-card">
      <div class="tktd-card-title">Ticket Controls</div>

      <div class="tktd-ctrl-group">
        <label class="tktd-ctrl-label">Status</label>
        <select class="tktd-ctrl-select" id="tktdStatusSel" onchange="tktdChangeStatus(this.value)">
          <?php foreach (['Open','In Progress','Resolved','Closed','Reopened'] as $s): ?>
          <option value="<?= $s ?>" <?= $currentStatus === $s ? 'selected' : '' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="tktd-ctrl-group">
        <label class="tktd-ctrl-label">Priority</label>
        <select class="tktd-ctrl-select" id="tktdPriSel" onchange="tktdChangePriority(this.value)">
          <?php foreach (['Low','Normal','High','Urgent'] as $p): ?>
          <option value="<?= $p ?>" <?= ((string)($ticket->PRIORITY ?? 'Normal')) === $p ? 'selected' : '' ?>><?= $p ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="tktd-ctrl-group" style="margin-bottom:0">
        <label class="tktd-ctrl-label">Assigned To</label>
        <select class="tktd-ctrl-select" id="tktdAssignSel" onchange="tktdAssign(this.value)">
          <option value="0">— Unassigned —</option>
          <?php foreach ($adminUsers as $au):
            $auId  = (int)(float)($au->USER_ID ?? 0);
            $auName = htmlspecialchars((string)($au->NAME ?? ''));
            $assigned = (int)(float)($ticket->ASSIGNED_ADMIN_ID ?? 0);
          ?>
          <option value="<?= $auId ?>" <?= $assigned === $auId ? 'selected' : '' ?>><?= $auName ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <?php endif; ?>

    <!-- Ticket Info -->
    <div class="tktd-card">
      <div class="tktd-card-title">Ticket Info</div>
      <div class="tktd-info-row">
        <span class="tktd-info-lbl">Ticket #</span>
        <span class="tktd-info-val" style="font-family:monospace"><?= htmlspecialchars($ticketNumber) ?></span>
      </div>
      <div class="tktd-info-row">
        <span class="tktd-info-lbl">Category</span>
        <span class="tktd-info-val"><?= htmlspecialchars((string)($ticket->CATEGORY_NAME ?? '—')) ?></span>
      </div>
      <div class="tktd-info-row">
        <span class="tktd-info-lbl">Type</span>
        <span class="tktd-info-val"><?= htmlspecialchars($categoryType) ?></span>
      </div>
      <?php if (!empty($ticket->ORDER_NUMBER)): ?>
      <div class="tktd-info-row">
        <span class="tktd-info-lbl">Order</span>
        <span class="tktd-info-val"><?= htmlspecialchars((string)$ticket->ORDER_NUMBER) ?></span>
      </div>
      <?php if (!empty($ticket->FINAL_TOTAL_AMT)): ?>
      <div class="tktd-info-row">
        <span class="tktd-info-lbl">Order Total</span>
        <span class="tktd-info-val">€<?= number_format((float)$ticket->FINAL_TOTAL_AMT, 2) ?></span>
      </div>
      <?php endif; ?>
      <?php endif; ?>
      <div class="tktd-info-row">
        <span class="tktd-info-lbl">Created</span>
        <span class="tktd-info-val"><?= $ctrl->fmtDate((string)($ticket->CREATED_AT ?? '')) ?></span>
      </div>
      <?php if (!empty($ticket->CLOSED_AT)): ?>
      <div class="tktd-info-row">
        <span class="tktd-info-lbl">Closed</span>
        <span class="tktd-info-val"><?= $ctrl->fmtDate((string)$ticket->CLOSED_AT) ?></span>
      </div>
      <?php endif; ?>
    </div>

    <!-- Customer Info -->
    <div class="tktd-card">
      <div class="tktd-card-title">Customer</div>
      <div class="tktd-info-row">
        <span class="tktd-info-lbl">Name</span>
        <span class="tktd-info-val"><?= htmlspecialchars((string)($ticket->USER_NAME ?? '—')) ?></span>
      </div>
      <?php if (!empty($ticket->USER_EMAIL)): ?>
      <div class="tktd-info-row">
        <span class="tktd-info-lbl">Email</span>
        <span class="tktd-info-val" style="word-break:break-all"><?= htmlspecialchars((string)$ticket->USER_EMAIL) ?></span>
      </div>
      <?php endif; ?>
      <?php if (!empty($ticket->USER_MOBILE)): ?>
      <div class="tktd-info-row">
        <span class="tktd-info-lbl">Mobile</span>
        <span class="tktd-info-val"><?= htmlspecialchars((string)$ticket->USER_MOBILE) ?></span>
      </div>
      <?php endif; ?>
    </div>

  </div><!-- /sidebar -->

</div><!-- /tktd-shell -->

<div id="tktdCtrlMsg" style="display:none;position:fixed;bottom:20px;right:20px;background:#1e293b;color:#fff;padding:10px 18px;border-radius:10px;font-size:13px;z-index:9999;pointer-events:none"></div>

<script>
var _tktdTicketId     = <?= $ticketId ?>;
var _tktdTicketNumber = <?= json_encode($ticketNumber) ?>;
var _tktdIsInternal   = false;
var _tktdFiles        = [];

/* ── Tab switching ── */
function tktdSwitchTab(tab) {
    _tktdIsInternal = tab === 'note';
    document.getElementById('tktdTabReply').classList.toggle('is-active', !_tktdIsInternal);
    document.getElementById('tktdTabNote').classList.toggle('is-active',  _tktdIsInternal);
    var ta    = document.getElementById('tktdReplyBody');
    var btn   = document.getElementById('tktdSendBtn');
    var icon  = document.getElementById('tktdSendIcon');
    var label = document.getElementById('tktdSendLabel');
    if (_tktdIsInternal) {
        ta.placeholder = 'Add an internal note (not visible to the customer)…';
        ta.classList.add('tktd-reply-textarea--note');
        btn.classList.add('tktd-send-btn--note');
        icon.innerHTML = '<path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>';
        label.textContent = 'Add Note';
    } else {
        ta.placeholder = 'Type your reply to the customer…';
        ta.classList.remove('tktd-reply-textarea--note');
        btn.classList.remove('tktd-send-btn--note');
        icon.innerHTML = '<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>';
        label.textContent = 'Send Reply';
    }
}

/* ── File attachment ── */
document.getElementById('tktdAttInput') && document.getElementById('tktdAttInput').addEventListener('change', function(){
    Array.from(this.files).forEach(function(f){
        if (_tktdFiles.length >= 5) return;
        if (f.size > 5*1024*1024) return;
        _tktdFiles.push(f);
    });
    this.value = '';
    tktdRenderChips();
});

function tktdRenderChips() {
    var p = document.getElementById('tktdAttPreview');
    if (!p) return;
    p.innerHTML = '';
    _tktdFiles.forEach(function(f, i){
        var c = document.createElement('span');
        c.className = 'tktd-att-chip';
        c.innerHTML = '<span>' + f.name + '</span>';
        var r = document.createElement('button');
        r.type = 'button'; r.innerHTML = '×';
        r.onclick = function(){ _tktdFiles.splice(i,1); tktdRenderChips(); };
        c.appendChild(r);
        p.appendChild(c);
    });
}

/* ── Send button loading helpers ── */
function _tktdSetLoading(loading) {
    var btn     = document.getElementById('tktdSendBtn');
    var spinner = document.getElementById('tktdSpinner');
    var icon    = document.getElementById('tktdSendIcon');
    var label   = document.getElementById('tktdSendLabel');
    var ta      = document.getElementById('tktdReplyBody');
    var att     = document.getElementById('tktdAttInput');

    btn.disabled = loading;
    ta.disabled  = loading;
    att.disabled = loading;

    if (loading) {
        spinner.style.display = '';
        icon.style.display    = 'none';
        label.textContent     = 'Sending…';
    } else {
        spinner.style.display = 'none';
        icon.style.display    = '';
        label.textContent     = _tktdIsInternal ? 'Save Note' : 'Send Reply';
    }
}

/* ── Send message / note ── */
async function tktdSend() {
    var errEl = document.getElementById('tktdReplyErr');
    errEl.style.display = 'none';
    var body = (document.getElementById('tktdReplyBody').value || '').trim();
    if (!body) { errEl.textContent = 'Message body is required.'; errEl.style.display = ''; return; }

    _tktdSetLoading(true);

    try {
        var res  = await fetch('ajax/support?action=add_note', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ticket_id: _tktdTicketId, body: body, is_internal: _tktdIsInternal })
        });
        var data = await res.json();
        if (!data.ok) {
            errEl.textContent = data.msg || 'Failed.';
            errEl.style.display = '';
            _tktdSetLoading(false);
            return;
        }

        var msgId = data.message_id || 0;

        if (_tktdFiles.length > 0) {
            var fd = new FormData();
            fd.append('ticket_id',     _tktdTicketId);
            fd.append('ticket_number', _tktdTicketNumber);
            fd.append('message_id',    msgId);
            _tktdFiles.forEach(function(f){ fd.append('files[]', f); });
            await fetch('ajax/support?action=upload_attachment', { method:'POST', body:fd }).catch(function(){});
        }

        window.location.reload();
    } catch(e) {
        errEl.textContent = 'Network error. Please try again.';
        errEl.style.display = '';
        _tktdSetLoading(false);
    }
}

/* ── Control helpers ── */
function tktdToast(msg) {
    var el = document.getElementById('tktdCtrlMsg');
    el.textContent = msg;
    el.style.display = '';
    clearTimeout(el._t);
    el._t = setTimeout(function(){ el.style.display = 'none'; }, 2800);
}

async function tktdChangeStatus(val) {
    try {
        var r = await fetch('ajax/support?action=change_status', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ ticket_id: _tktdTicketId, status: val })
        });
        var d = await r.json();
        if (d.ok) { tktdToast('Status updated to ' + val); setTimeout(function(){ window.location.reload(); }, 1200); }
        else tktdToast('Error: ' + (d.msg || 'Failed'));
    } catch(e){ tktdToast('Network error'); }
}

async function tktdChangePriority(val) {
    try {
        var r = await fetch('ajax/support?action=change_priority', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ ticket_id: _tktdTicketId, priority: val })
        });
        var d = await r.json();
        tktdToast(d.ok ? 'Priority updated to ' + val : 'Error: ' + (d.msg || 'Failed'));
    } catch(e){ tktdToast('Network error'); }
}

async function tktdAssign(val) {
    try {
        var r = await fetch('ajax/support?action=assign_ticket', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ ticket_id: _tktdTicketId, admin_id: parseInt(val) })
        });
        var d = await r.json();
        tktdToast(d.ok ? 'Assignment saved.' : 'Error: ' + (d.msg || 'Failed'));
    } catch(e){ tktdToast('Network error'); }
}
</script>

<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
