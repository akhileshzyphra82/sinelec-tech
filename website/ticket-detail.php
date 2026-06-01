<?php
require_once __DIR__ . '/account-helpers.php';
require_once __DIR__ . '/../controller/support_controller.php';

$user        = sinelec_require_login();
$userId      = (int)(float)($user['USER_ID'] ?? 0);
$currentPage = 'support';

$ctrl         = new SupportController();
$ticketNumber = trim($_GET['id'] ?? '');

if ($ticketNumber === '') {
    header('location:my-tickets');
    exit;
}

$ticket = $ctrl->getTicketByNumber($ticketNumber, $userId);
if (!$ticket) {
    sinelec_set_flash('error', 'Ticket not found.');
    header('location:my-tickets');
    exit;
}

$ticketId      = (int)(float)($ticket->TICKET_ID      ?? 0);
$currentStatus = (string)($ticket->CURRENT_STATUS     ?? 'Open');
$categoryType  = (string)($ticket->CATEGORY_TYPE      ?? 'Other');
$messages      = $ctrl->getTicketMessages($ticketId, false);
$returnItems   = [];
if (in_array($categoryType, ['Return & Refund', 'Return & Replacement'], true)) {
    $returnItems = $ctrl->getReturnItems($ticketId);
}

/* Build a root-relative base path for attachment URLs (works both locally and on production) */
$_docRoot  = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
$_appRoot  = rtrim(realpath(__DIR__ . '/..'), '/\\');
$_appWeb   = ($_docRoot !== '' && str_starts_with($_appRoot, $_docRoot))
             ? str_replace($_docRoot, '', $_appRoot)
             : rtrim((string)sinelec_env('PUBLIC_BASE_URL', ''), '/');
$BASE_URL  = $_appWeb; /* e.g. /Client/sinelec-tech  or https://doc.sinelec-tech.com */

$pageTitle = 'Ticket ' . htmlspecialchars($ticketNumber) . ' | Sinelec Technologies';
require_once __DIR__ . '/header.php';

/* helpers */
function tdStatusBadge(string $s): string {
    $map = [
        'Open'        => '#dbeafe;color:#1d4ed8',
        'In Progress' => '#fef3c7;color:#92400e',
        'Resolved'    => '#d1fae5;color:#065f46',
        'Closed'      => '#f1f5f9;color:#475569',
        'Reopened'    => '#fce7f3;color:#9d174d',
    ];
    $st = $map[$s] ?? '#f1f5f9;color:#475569';
    return '<span class="td-status-badge" style="background:' . $st . '">' . htmlspecialchars($s) . '</span>';
}
function tdPriBadge(string $p): string {
    $map = ['Urgent' => '#dc2626','High' => '#ea580c','Normal' => '#64748b','Low' => '#94a3b8'];
    $c = $map[$p] ?? '#64748b';
    return '<span style="color:' . $c . ';font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px">' . htmlspecialchars($p) . '</span>';
}
function tdCatIcon(string $type): string {
    if (str_starts_with($type, 'Return')) return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4"/></svg>';
    if ($type === 'Payment Issue')        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>';
    return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>';
}
?>

<main class="account-page">
  <div class="wrap">
    <div class="account-shell">
      <?php sinelec_render_account_nav($currentPage); ?>

      <section class="account-content td-page">

        <!-- Breadcrumb -->
        <nav class="td-breadcrumb">
          <a href="my-tickets">Support &amp; Help</a>
          <span class="td-bc-sep">›</span>
          <span><?= htmlspecialchars($ticketNumber) ?></span>
        </nav>

        <!-- Header -->
        <div class="td-header">
          <div class="td-header-left">
            <div class="td-cat-icon td-cat-<?= strtolower(str_replace([' ', '&'], ['-', ''], $categoryType)) ?>">
              <?= tdCatIcon($categoryType) ?>
            </div>
            <div>
              <h1 class="td-title"><?= htmlspecialchars((string)($ticket->SUBJECT ?? '')) ?></h1>
              <div class="td-meta">
                <span class="td-ticket-num"><?= htmlspecialchars($ticketNumber) ?></span>
                <span class="td-dot">·</span>
                <span><?= htmlspecialchars((string)($ticket->CATEGORY_NAME ?? '')) ?></span>
                <?php if (!empty($ticket->ORDER_NUMBER)): ?>
                  <span class="td-dot">·</span>
                  <span>Order <?= htmlspecialchars((string)$ticket->ORDER_NUMBER) ?></span>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <div class="td-header-right">
            <?= tdStatusBadge($currentStatus) ?>
            <?= tdPriBadge((string)($ticket->PRIORITY ?? 'Normal')) ?>
          </div>
        </div>

        <!-- Return items panel -->
        <?php if (!empty($returnItems)): ?>
        <div class="td-return-panel">
          <div class="td-return-title">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4"/></svg>
            Return Items
          </div>
          <div class="td-return-items">
            <?php foreach ($returnItems as $ri): ?>
            <div class="td-return-item">
              <span class="td-ri-name"><?= htmlspecialchars((string)($ri->PRODUCT_NAME ?? '')) ?></span>
              <span class="td-ri-code"><?= htmlspecialchars((string)($ri->PRODUCT_CODE ?? '')) ?></span>
              <span class="td-ri-qty">Qty: <?= (int)($ri->RETURN_QTY ?? 0) ?> / <?= (int)($ri->ORDERED_QTY ?? 0) ?></span>
              <?php if (!empty($ri->RETURN_REASON)): ?>
              <span class="td-ri-reason">"<?= htmlspecialchars((string)$ri->RETURN_REASON) ?>"</span>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Chat thread -->
        <div class="td-thread" id="tdThread">
          <?php
          $prevStatus = null;
          foreach ($messages as $msg):
            $msgStatus  = (string)($msg->LAST_STATUS ?? 'Open');
            $isAutoReply = (bool)($msg->IS_AUTO_REPLY ?? false);
            $senderType  = (int)(float)($msg->USER_TYPE_ID ?? 2);
            $isAdmin     = $senderType === 1 || $senderType === 3;
            $senderName  = htmlspecialchars((string)($msg->SENDER_NAME ?? 'Support'));
            $bodyHtml    = nl2br(htmlspecialchars((string)($msg->MESSAGE_BODY ?? '')));
            $timeAgo     = $ctrl->timeAgo((string)($msg->CREATED_AT ?? ''));
            $dateTime    = $ctrl->fmtDateTime((string)($msg->CREATED_AT ?? ''));
            $msgId       = (int)(float)($msg->MESSAGE_ID ?? 0);

            // Parse attachments
            $attachments = [];
            $attRaw = (string)($msg->ATTACHMENTS_RAW ?? '');
            if ($attRaw !== '') {
                foreach (explode(';;', $attRaw) as $part) {
                    $bits = explode('|', $part, 3);
                    if (count($bits) === 3) {
                        $attachments[] = ['id' => $bits[0], 'name' => $bits[1], 'path' => $bits[2]];
                    }
                }
            }

            // Status change pill
            if ($prevStatus !== null && $msgStatus !== $prevStatus && !$isAutoReply):
          ?>
          <div class="td-status-pill">
            <span>Status changed to <strong><?= htmlspecialchars($msgStatus) ?></strong></span>
          </div>
          <?php
            endif;
            $prevStatus = $msgStatus;
          ?>

          <div class="td-msg <?= $isAutoReply ? 'td-msg--auto' : ($isAdmin ? 'td-msg--admin' : 'td-msg--user') ?>">
            <?php if (!$isAutoReply): ?>
            <div class="td-msg-avatar <?= $isAdmin ? 'td-av--admin' : 'td-av--user' ?>">
              <?= strtoupper(mb_substr($senderName, 0, 1)) ?>
            </div>
            <?php endif; ?>
            <div class="td-msg-body <?= $isAutoReply ? 'td-msg-body--auto' : '' ?>">
              <?php if (!$isAutoReply): ?>
              <div class="td-msg-meta">
                <span class="td-msg-sender"><?= $senderName ?></span>
                <?php if ($isAdmin): ?>
                <span class="td-msg-role">Support</span>
                <?php endif; ?>
                <span class="td-msg-time" title="<?= htmlspecialchars($dateTime) ?>"><?= $timeAgo ?></span>
              </div>
              <?php endif; ?>
              <div class="td-msg-text <?= $isAutoReply ? 'td-msg-text--auto' : '' ?>">
                <?= $bodyHtml ?>
              </div>
              <?php if (!empty($attachments)): ?>
              <div class="td-msg-attachments">
                <?php foreach ($attachments as $att): ?>
                <?php
                  $ext = strtolower(pathinfo($att['name'], PATHINFO_EXTENSION));
                  $isImg = in_array($ext, ['jpg','jpeg','png','webp','gif'], true);
                  $attUrl = $BASE_URL . '/' . ltrim($att['path'], '/');
                ?>
                <?php if ($isImg): ?>
                <a href="<?= htmlspecialchars($attUrl) ?>" target="_blank" class="td-att-img-link">
                  <img src="<?= htmlspecialchars($attUrl) ?>" alt="<?= htmlspecialchars($att['name']) ?>" class="td-att-img">
                </a>
                <?php else: ?>
                <a href="<?= htmlspecialchars($attUrl) ?>" target="_blank" class="td-att-file">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                  <?= htmlspecialchars($att['name']) ?>
                </a>
                <?php endif; ?>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div><!-- /td-thread -->

        <!-- Reply Box or Status Actions -->
        <?php if ($currentStatus === 'Closed'): ?>
        <div class="td-closed-bar">
          <span>This ticket is closed.</span>
          <button class="td-action-btn td-action-btn--reopen" onclick="tdStatusAction('reopen')">Reopen Ticket</button>
        </div>
        <?php elseif ($currentStatus === 'Resolved'): ?>
        <div class="td-resolved-bar">
          <div class="td-resolved-msg">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            This ticket has been marked as resolved. Is your issue fixed?
          </div>
          <div class="td-resolved-actions">
            <button class="td-action-btn td-action-btn--confirm" onclick="tdStatusAction('confirm_resolved')">Yes, Close It</button>
            <button class="td-action-btn td-action-btn--reopen" onclick="tdStatusAction('reopen')">No, Reopen</button>
          </div>
        </div>
        <!-- Still allow reply after resolved -->
        <div class="td-reply-box">
          <?php include __DIR__ . '/partials/ticket-reply-form.php'; ?>
        </div>
        <?php else: ?>
        <div class="td-reply-box">
          <?php include __DIR__ . '/partials/ticket-reply-form.php'; ?>
        </div>
        <?php endif; ?>

      </section>
    </div><!-- /account-shell -->
  </div><!-- /wrap -->
</main>

<!-- Hidden data -->
<script>
var _tdTicketId     = <?= $ticketId ?>;
var _tdTicketNumber = <?= json_encode($ticketNumber) ?>;
var _tdStatus       = <?= json_encode($currentStatus) ?>;
</script>

<style>
/* ── Ticket Detail Page ── */
.td-page { min-width: 0; }

.td-breadcrumb { display:flex; align-items:center; gap:6px; font-size:13px; color:#64748b; margin-bottom:22px; }
.td-breadcrumb a { color:#3b82f6; text-decoration:none; }
.td-breadcrumb a:hover { text-decoration:underline; }
.td-bc-sep { color:#cbd5e1; }

.td-header { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:24px; flex-wrap:wrap; }
.td-header-left { display:flex; align-items:flex-start; gap:14px; }
.td-header-right { display:flex; align-items:center; gap:10px; flex-shrink:0; }

.td-cat-icon { width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.td-cat-return-refund, .td-cat-return-replacement { background:#f3e8ff; color:#7c3aed; }
.td-cat-payment-issue { background:#fef3c7; color:#92400e; }
.td-cat-other { background:#dbeafe; color:#1d4ed8; }

.td-title { font-size:20px; font-weight:700; color:#0a1a30; margin:0 0 6px; }
.td-meta { display:flex; align-items:center; gap:6px; font-size:13px; color:#64748b; flex-wrap:wrap; }
.td-ticket-num { font-family:monospace; font-weight:600; color:#374151; }
.td-dot { color:#cbd5e1; }

.td-status-badge { font-size:11px; font-weight:700; padding:4px 12px; border-radius:20px; white-space:nowrap; }

/* Return panel */
.td-return-panel { background:#faf5ff; border:1px solid #e9d5ff; border-radius:12px; padding:16px 20px; margin-bottom:24px; }
.td-return-title { display:flex; align-items:center; gap:6px; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#7c3aed; margin-bottom:12px; }
.td-return-items { display:flex; flex-direction:column; gap:8px; }
.td-return-item { display:flex; flex-wrap:wrap; align-items:center; gap:8px; background:#fff; border:1px solid #e9d5ff; border-radius:8px; padding:10px 14px; }
.td-ri-name { font-size:13px; font-weight:600; color:#1e293b; }
.td-ri-code { font-size:11px; color:#94a3b8; font-family:monospace; }
.td-ri-qty { font-size:12px; color:#7c3aed; font-weight:700; background:#f3e8ff; padding:2px 8px; border-radius:20px; }
.td-ri-reason { font-size:12px; color:#64748b; font-style:italic; }

/* Thread */
.td-thread { display:flex; flex-direction:column; gap:18px; margin-bottom:28px; }

.td-status-pill { display:flex; justify-content:center; }
.td-status-pill > span { font-size:12px; color:#64748b; background:#f1f5f9; border:1px solid #e2e8f0; padding:4px 14px; border-radius:20px; }

.td-msg { display:flex; gap:12px; }
.td-msg--user { flex-direction:row-reverse; }
.td-msg--auto { justify-content:center; }

.td-msg-avatar { width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:700; flex-shrink:0; }
.td-av--admin { background:#0a1a30; color:#fff; }
.td-av--user  { background:#3b82f6; color:#fff; }

.td-msg-body { max-width:72%; }
.td-msg-body--auto { max-width:80%; }
.td-msg--user .td-msg-body { align-items:flex-end; }

.td-msg-meta { display:flex; align-items:center; gap:8px; margin-bottom:6px; }
.td-msg--user .td-msg-meta { flex-direction:row-reverse; }
.td-msg-sender { font-size:13px; font-weight:600; color:#1e293b; }
.td-msg-role { font-size:11px; color:#fff; background:#0a1a30; padding:1px 7px; border-radius:20px; }
.td-msg-time { font-size:11px; color:#94a3b8; }

.td-msg-text { font-size:14px; color:#374151; line-height:1.65; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:12px 16px; }
.td-msg--user .td-msg-text { background:#eff6ff; border-color:#bfdbfe; border-bottom-right-radius:4px; }
.td-msg--admin .td-msg-text { border-bottom-left-radius:4px; }
.td-msg-text--auto { background:#f0fdf4; border-color:#bbf7d0; color:#166534; font-size:13px; font-style:italic; border-radius:20px; padding:8px 18px; text-align:center; }

.td-msg-attachments { display:flex; flex-wrap:wrap; gap:8px; margin-top:10px; }
.td-att-img { width:80px; height:80px; object-fit:cover; border-radius:8px; border:1px solid #e2e8f0; }
.td-att-img-link:hover .td-att-img { opacity:.85; }
.td-att-file { display:inline-flex; align-items:center; gap:5px; font-size:12px; color:#3b82f6; background:#eff6ff; border:1px solid #bfdbfe; padding:4px 10px; border-radius:6px; text-decoration:none; }
.td-att-file:hover { background:#dbeafe; }

/* Reply box */
.td-reply-box { margin-top:4px; }
.td-reply-form { background:#fff; border:1px solid #e2e8f0; border-radius:16px; padding:20px; }
.td-reply-label { font-size:13px; font-weight:700; color:#374151; margin-bottom:10px; display:block; }
.td-reply-textarea { width:100%; box-sizing:border-box; border:1px solid #e2e8f0; border-radius:10px; padding:12px 14px; font-size:14px; color:#1e293b; resize:vertical; min-height:100px; font-family:inherit; outline:none; transition:border .2s; }
.td-reply-textarea:focus { border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.1); }

.td-reply-footer { display:flex; align-items:center; justify-content:space-between; margin-top:12px; flex-wrap:wrap; gap:10px; }
.td-att-zone { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.td-att-btn { display:inline-flex; align-items:center; gap:5px; font-size:12px; color:#64748b; background:#f8fafc; border:1px dashed #cbd5e1; padding:6px 12px; border-radius:8px; cursor:pointer; transition:all .2s; }
.td-att-btn:hover { border-color:#3b82f6; color:#3b82f6; background:#eff6ff; }
.td-att-preview { display:flex; flex-wrap:wrap; gap:6px; }
.td-att-chip { display:inline-flex; align-items:center; gap:4px; font-size:11px; background:#f1f5f9; border:1px solid #e2e8f0; padding:3px 8px; border-radius:20px; color:#64748b; }
.td-att-chip button { background:none; border:none; cursor:pointer; padding:0; color:#94a3b8; line-height:1; font-size:13px; }
.td-att-chip button:hover { color:#dc2626; }

.td-send-btn { display:inline-flex; align-items:center; gap:6px; background:#0a1a30; color:#fff; border:none; padding:10px 22px; border-radius:10px; font-size:14px; font-weight:600; cursor:pointer; transition:background .2s; }
.td-send-btn:hover:not(:disabled) { background:#1e3a5f; }
.td-send-btn:disabled { opacity:.6; cursor:not-allowed; }

.td-reply-error { margin-top:10px; font-size:13px; color:#dc2626; display:none; }
.td-reply-success { margin-top:10px; font-size:13px; color:#16a34a; display:none; }

/* Status action bars */
.td-closed-bar { background:#f1f5f9; border:1px solid #e2e8f0; border-radius:12px; padding:16px 20px; display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:4px; flex-wrap:wrap; }
.td-closed-bar span { font-size:14px; color:#475569; }

.td-resolved-bar { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:18px 20px; margin-top:4px; margin-bottom:20px; }
.td-resolved-msg { display:flex; align-items:center; gap:8px; font-size:14px; color:#166534; margin-bottom:12px; }
.td-resolved-actions { display:flex; gap:10px; flex-wrap:wrap; }

.td-action-btn { border:none; padding:9px 20px; border-radius:10px; font-size:13px; font-weight:600; cursor:pointer; transition:background .2s; }
.td-action-btn--confirm { background:#16a34a; color:#fff; }
.td-action-btn--confirm:hover { background:#15803d; }
.td-action-btn--reopen { background:#fff; color:#374151; border:1px solid #d1d5db; }
.td-action-btn--reopen:hover { background:#f3f4f6; }

@media (max-width:600px) {
  .td-msg-body { max-width:90%; }
  .td-header { flex-direction:column; }
}
</style>

<script>
/* ── Reply Form ── */
(function () {
    var form        = document.getElementById('tdReplyForm');
    var textarea    = document.getElementById('tdReplyBody');
    var fileInput   = document.getElementById('tdAttInput');
    var preview     = document.getElementById('tdAttPreview');
    var sendBtn     = document.getElementById('tdSendBtn');
    var errEl       = document.getElementById('tdReplyError');
    var successEl   = document.getElementById('tdReplySuccess');
    var selectedFiles = [];

    if (!form) return;

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            Array.from(this.files).forEach(addFile);
            this.value = '';
        });
    }

    function addFile(f) {
        if (selectedFiles.length >= 5) return;
        if (f.size > 5 * 1024 * 1024) { showErr('File too large (max 5 MB): ' + f.name); return; }
        selectedFiles.push(f);
        renderChips();
    }

    function renderChips() {
        if (!preview) return;
        preview.innerHTML = '';
        selectedFiles.forEach(function (f, i) {
            var chip = document.createElement('span');
            chip.className = 'td-att-chip';
            chip.innerHTML = '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>';
            chip.innerHTML += '<span>' + f.name + '</span>';
            var rm = document.createElement('button');
            rm.type = 'button';
            rm.innerHTML = '×';
            rm.addEventListener('click', function () { selectedFiles.splice(i, 1); renderChips(); });
            chip.appendChild(rm);
            preview.appendChild(chip);
        });
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        var body = (textarea ? textarea.value.trim() : '');
        if (!body) { showErr('Please type a message.'); return; }

        setBusy(true);
        hideMsg();

        try {
            var res = await fetch('ajax/ticket?action=reply', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ticket_id: _tdTicketId, body: body })
            });
            var data = await res.json();
            if (!data.ok) { showErr(data.msg || 'Failed to send.'); setBusy(false); return; }

            var msgId = data.message_id || 0;

            // Upload attachments if any
            if (selectedFiles.length > 0) {
                var fd = new FormData();
                fd.append('ticket_id',     _tdTicketId);
                fd.append('ticket_number', _tdTicketNumber);
                fd.append('message_id',    msgId);
                selectedFiles.forEach(function (f) { fd.append('files[]', f); });
                await fetch('ajax/ticket?action=upload', { method: 'POST', body: fd }).catch(function () {});
            }

            // Reload page to show new message
            window.location.reload();

        } catch (err) {
            showErr('Network error. Please try again.');
            setBusy(false);
        }
    });

    function setBusy(b) {
        if (sendBtn) sendBtn.disabled = b;
        if (sendBtn) sendBtn.textContent = b ? 'Sending…' : 'Send Reply';
    }
    function showErr(m) { if (errEl) { errEl.textContent = m; errEl.style.display = ''; } }
    function hideMsg()  { if (errEl) errEl.style.display = 'none'; if (successEl) successEl.style.display = 'none'; }
})();

/* ── Status Actions ── */
async function tdStatusAction(action) {
    if (!confirm(action === 'reopen' ? 'Reopen this ticket?' : 'Mark this ticket as closed?')) return;
    try {
        var res = await fetch('ajax/ticket?action=status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ticket_id: _tdTicketId, user_action: action })
        });
        var data = await res.json();
        if (!data.ok) { alert(data.msg || 'Failed to update status.'); return; }
        window.location.reload();
    } catch (e) {
        alert('Network error. Please try again.');
    }
}

/* Scroll to bottom of thread */
(function () {
    var t = document.getElementById('tdThread');
    if (t) t.scrollTop = t.scrollHeight;
})();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
