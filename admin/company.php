<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'company';
$pageTitle   = 'Company Settings';

$controller = new AdminController();
$co = $controller->getCompanyDetails();

$f = [
    'name'             => (string)($co->NAME              ?? ''),
    'logo'             => (string)($co->LOGO              ?? ''),
    'description'      => (string)($co->DESCRIPTION       ?? ''),
    'contact_number'   => (string)($co->CONTACT_NUMBER    ?? ''),
    'email'            => (string)($co->EMAIL             ?? ''),
    'address'          => (string)($co->ADDRESS           ?? ''),
    'fax'              => (string)($co->FAX               ?? ''),
    'facebook_url'     => (string)($co->FACEBOOK_URL      ?? ''),
    'instagram_url'    => (string)($co->INSTAGRAM_URL     ?? ''),
    'linkedin_url'     => (string)($co->LINKEDIN_URL      ?? ''),
    'twitter_url'      => (string)($co->TWITTER_URL       ?? ''),
    'youtube_url'      => (string)($co->YOUTUBE_URL       ?? ''),
    'whatsapp_number'  => (string)($co->WHATSAPP_NUMBER   ?? ''),
    'support_mail_id'  => (string)($co->SUPPORT_MAIL_ID   ?? ''),
    'instructions'     => (string)($co->INSTRUCTIONS      ?? ''),
];

ob_start();
?>
<!-- Quill CSS -->
<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">

<style>
.co-section {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 12px;
    margin-bottom: 20px;
    overflow: hidden;
}
.co-section-head {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 20px;
    background: linear-gradient(135deg, #f8faff 0%, #f3f4f6 100%);
    border-bottom: 1px solid var(--border);
}
.co-section-icon {
    width: 32px; height: 32px;
    border-radius: 8px;
    display: grid; place-items: center;
    flex-shrink: 0;
}
.co-section-title { font-size: 14px; font-weight: 700; color: #0f172a; }
.co-section-body { padding: 20px; }
.co-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
.co-grid-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 16px;
}
@media (max-width: 720px) {
    .co-grid-2, .co-grid-3 { grid-template-columns: 1fr; }
}
.co-logo-preview {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-top: 10px;
    padding: 12px 16px;
    background: #f8faff;
    border: 1px solid #e0e7ff;
    border-radius: 8px;
    min-height: 64px;
}
.co-logo-preview img {
    max-height: 50px;
    max-width: 180px;
    object-fit: contain;
    border-radius: 4px;
}
.co-logo-placeholder {
    width: 50px; height: 50px;
    border-radius: 8px;
    background: linear-gradient(135deg, #e0e7ff 0%, #ede9fe 100%);
    display: grid; place-items: center;
    font-size: 20px; font-weight: 700; color: #6366f1;
}
.co-logo-label { font-size: 12px; color: var(--text-muted); }
.co-hint { font-size: 11px; color: var(--text-muted); margin-top: 4px; }
.co-social-row {
    display: grid;
    grid-template-columns: 36px 1fr;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}
.co-social-icon {
    width: 32px; height: 32px;
    border-radius: 8px;
    display: grid; place-items: center;
    flex-shrink: 0;
}
</style>

<div class="pg-header">
    <div>
        <div class="pg-title">Company Settings</div>
        <div class="pg-subtitle">Manage your company profile, contact details, and integrations.</div>
    </div>
</div>

<form method="POST" action="service?urlstring=<?= EncryptURL('action=UpdateCompany') ?>" id="companyForm">

    <!-- ── Basic Info ───────────────────────────────────────────── -->
    <div class="co-section">
        <div class="co-section-head">
            <div class="co-section-icon" style="background:#eff6ff;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
            </div>
            <div class="co-section-title">Basic Information</div>
        </div>
        <div class="co-section-body">

            <!-- Company Name -->
            <div class="fg" style="margin-bottom:16px;">
                <label class="form-label">Company Name <span class="req">*</span></label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($f['name']) ?>" placeholder="e.g. Sinelec Technology GmbH" required>
            </div>

            <!-- Logo URL + preview -->
            <div class="fg" style="margin-bottom:8px;">
                <label class="form-label">Company Logo URL</label>
                <input type="url" name="logo" id="coLogoUrl" class="form-control"
                       value="<?= htmlspecialchars($f['logo']) ?>"
                       placeholder="https://example.com/logo.png"
                       oninput="previewLogo(this.value)">
                <div class="co-hint">Paste a full URL. The logo is used in emails and PDFs.</div>
            </div>
            <div class="co-logo-preview" id="coLogoPreviewBox">
                <?php if ($f['logo'] !== ''): ?>
                    <img id="coLogoImg" src="<?= htmlspecialchars($f['logo']) ?>" alt="Logo preview" onerror="logoError()">
                    <span class="co-logo-label" id="coLogoHint">Logo preview</span>
                <?php else: ?>
                    <div class="co-logo-placeholder" id="coLogoBadge"><?= htmlspecialchars(strtoupper(substr($f['name'] ?: 'C', 0, 1))) ?></div>
                    <span class="co-logo-label" id="coLogoHint" style="color:#9ca3af;">No logo URL entered — initials shown as placeholder.</span>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <div class="fg" style="margin-top:16px;">
                <label class="form-label">Company Description</label>
                <input type="hidden" name="description" id="coDescHidden">
                <div id="coDescEditor" style="min-height:120px;border-radius:0 0 6px 6px;font-size:13px;"></div>
            </div>

        </div>
    </div>

    <!-- ── Contact Details ──────────────────────────────────────── -->
    <div class="co-section">
        <div class="co-section-head">
            <div class="co-section-icon" style="background:#f0fdf4;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 11.51 19 19.43 19.43 0 0 1 4.36 12 19.79 19.79 0 0 1 2.07 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91A16 16 0 0 0 14.09 15.91l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            </div>
            <div class="co-section-title">Contact Details</div>
        </div>
        <div class="co-section-body">

            <div class="co-grid-2" style="margin-bottom:16px;">
                <div class="fg">
                    <label class="form-label">Phone / Contact Number</label>
                    <input type="text" name="contact_number" class="form-control" value="<?= htmlspecialchars($f['contact_number']) ?>" placeholder="+49 123 456 7890">
                </div>
                <div class="fg">
                    <label class="form-label">Fax Number</label>
                    <input type="text" name="fax" class="form-control" value="<?= htmlspecialchars($f['fax']) ?>" placeholder="+49 123 456 7891">
                </div>
            </div>

            <div class="co-grid-2" style="margin-bottom:16px;">
                <div class="fg">
                    <label class="form-label">Company Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($f['email']) ?>" placeholder="info@company.com">
                </div>
                <div class="fg">
                    <label class="form-label">WhatsApp Number</label>
                    <input type="text" name="whatsapp_number" class="form-control" value="<?= htmlspecialchars($f['whatsapp_number']) ?>" placeholder="+49 123 456 7890">
                </div>
            </div>

            <div class="fg">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="3" placeholder="Street, City, State, ZIP, Country"><?= htmlspecialchars($f['address']) ?></textarea>
            </div>

        </div>
    </div>

    <!-- ── Social Media ─────────────────────────────────────────── -->
    <div class="co-section">
        <div class="co-section-head">
            <div class="co-section-icon" style="background:#fdf4ff;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9333ea" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
            </div>
            <div class="co-section-title">Social Media</div>
        </div>
        <div class="co-section-body">

            <!-- Facebook -->
            <div class="co-social-row">
                <div class="co-social-icon" style="background:#eff6ff;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="#2563eb"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                </div>
                <div class="fg" style="margin:0;">
                    <input type="url" name="facebook_url" class="form-control" value="<?= htmlspecialchars($f['facebook_url']) ?>" placeholder="https://facebook.com/yourpage">
                </div>
            </div>

            <!-- Instagram -->
            <div class="co-social-row">
                <div class="co-social-icon" style="background:#fdf4ff;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#a21caf" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                </div>
                <div class="fg" style="margin:0;">
                    <input type="url" name="instagram_url" class="form-control" value="<?= htmlspecialchars($f['instagram_url']) ?>" placeholder="https://instagram.com/yourhandle">
                </div>
            </div>

            <!-- LinkedIn -->
            <div class="co-social-row">
                <div class="co-section-icon" style="background:#eff6ff;width:32px;height:32px;border-radius:8px;display:grid;place-items:center;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="#0a66c2"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
                </div>
                <div class="fg" style="margin:0;">
                    <input type="url" name="linkedin_url" class="form-control" value="<?= htmlspecialchars($f['linkedin_url']) ?>" placeholder="https://linkedin.com/company/yourcompany">
                </div>
            </div>

            <!-- Twitter / X -->
            <div class="co-social-row">
                <div class="co-social-icon" style="background:#f9fafb;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="#111827"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.746l7.73-8.835L1.254 2.25H8.08l4.264 5.633 5.9-5.633zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                </div>
                <div class="fg" style="margin:0;">
                    <input type="url" name="twitter_url" class="form-control" value="<?= htmlspecialchars($f['twitter_url']) ?>" placeholder="https://twitter.com/yourhandle">
                </div>
            </div>

            <!-- YouTube -->
            <div class="co-social-row" style="margin-bottom:0;">
                <div class="co-social-icon" style="background:#fff5f5;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="#dc2626"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.97C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 0 0-1.95 1.96A29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58A2.78 2.78 0 0 0 3.41 19.6C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.95A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02" fill="white"/></svg>
                </div>
                <div class="fg" style="margin:0;">
                    <input type="url" name="youtube_url" class="form-control" value="<?= htmlspecialchars($f['youtube_url']) ?>" placeholder="https://youtube.com/@yourchannel">
                </div>
            </div>

        </div>
    </div>

    <!-- ── Email Settings ───────────────────────────────────────── -->
    <div class="co-section">
        <div class="co-section-head">
            <div class="co-section-icon" style="background:#fff7ed;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </div>
            <div class="co-section-title">Email Settings</div>
        </div>
        <div class="co-section-body">
            <div class="fg">
                <label class="form-label">Support / Notification Emails</label>
                <input type="text" name="support_mail_id" class="form-control"
                       value="<?= htmlspecialchars($f['support_mail_id']) ?>"
                       placeholder="support@company.com, admin@company.com">
                <div class="co-hint">Separate multiple addresses with commas. These receive copies of quotation status emails.</div>
            </div>
        </div>
    </div>

    <!-- ── Terms & Instructions ─────────────────────────────────── -->
    <div class="co-section">
        <div class="co-section-head">
            <div class="co-section-icon" style="background:#f0fdf4;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            </div>
            <div class="co-section-title">Terms & Instructions</div>
        </div>
        <div class="co-section-body">
            <div class="fg">
                <label class="form-label">Terms, Conditions & Instructions</label>
                <input type="hidden" name="instructions" id="coInstrHidden">
                <div id="coInstrEditor" style="min-height:200px;border-radius:0 0 6px 6px;font-size:13px;"></div>
                <div class="co-hint">This text appears on quotation PDFs in the terms section.</div>
            </div>
        </div>
    </div>

    <!-- ── Actions ──────────────────────────────────────────────── -->
    <div style="display:flex;align-items:center;gap:12px;padding:4px 0 24px;">
        <button type="submit" class="btn btn--primary" style="min-width:160px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="20 6 9 17 4 12"/></svg>
            Save Company Details
        </button>
        <button type="reset" class="btn btn--outline">Reset</button>
    </div>

</form>

<!-- Quill JS -->
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
/* ── Quill editors ─────────────────────────────────────────────── */
var _qOpts = {
    theme: 'snow',
    modules: { toolbar: [
        [{ header: [1,2,3,false] }],
        ['bold','italic','underline','strike'],
        [{ list:'ordered' },{ list:'bullet' }],
        ['link'],
        ['clean']
    ]}
};
var qDesc  = new Quill('#coDescEditor',  Object.assign({}, _qOpts, { placeholder: 'Short description of your company…' }));
var qInstr = new Quill('#coInstrEditor', Object.assign({}, _qOpts, { placeholder: 'Enter terms, conditions, payment instructions…' }));

/* Pre-fill from saved HTML */
var _savedDesc  = <?= json_encode($f['description']) ?>;
var _savedInstr = <?= json_encode($f['instructions']) ?>;
if (_savedDesc)  qDesc.clipboard.dangerouslyPasteHTML(_savedDesc);
if (_savedInstr) qInstr.clipboard.dangerouslyPasteHTML(_savedInstr);

/* Sync to hidden inputs on submit */
document.getElementById('companyForm').addEventListener('submit', function() {
    var clean = function(q) { var h = q.root.innerHTML; return h === '<p><br></p>' ? '' : h; };
    document.getElementById('coDescHidden').value  = clean(qDesc);
    document.getElementById('coInstrHidden').value = clean(qInstr);
});

function previewLogo(url) {
    const img     = document.getElementById('coLogoImg');
    const badge   = document.getElementById('coLogoBadge');
    const hint    = document.getElementById('coLogoHint');
    const box     = document.getElementById('coLogoPreviewBox');

    if (!url.trim()) {
        if (img)   img.remove();
        if (!badge) {
            const b = document.createElement('div');
            b.className = 'co-logo-placeholder';
            b.id = 'coLogoBadge';
            b.textContent = '<?= htmlspecialchars(strtoupper(substr($f['name'] ?: 'C', 0, 1))) ?>';
            box.insertBefore(b, hint);
        }
        hint.textContent = 'No logo URL entered — initials shown as placeholder.';
        hint.style.color = '#9ca3af';
        return;
    }

    const newImg = img || document.createElement('img');
    if (!img) {
        newImg.id = 'coLogoImg';
        newImg.style.maxHeight = '50px';
        newImg.style.maxWidth  = '180px';
        newImg.style.objectFit = 'contain';
        newImg.style.borderRadius = '4px';
        newImg.onerror = logoError;
        box.insertBefore(newImg, hint);
        if (badge) badge.remove();
    }
    newImg.src = url;
    newImg.onerror = logoError;
    hint.textContent = 'Logo preview';
    hint.style.color = '';
}

function logoError() {
    const img   = document.getElementById('coLogoImg');
    const hint  = document.getElementById('coLogoHint');
    const box   = document.getElementById('coLogoPreviewBox');
    if (img) img.remove();
    if (hint) {
        hint.textContent = 'Could not load image from the provided URL.';
        hint.style.color = '#dc2626';
    }
    if (!document.getElementById('coLogoBadge')) {
        const b = document.createElement('div');
        b.className = 'co-logo-placeholder';
        b.id = 'coLogoBadge';
        b.textContent = '<?= htmlspecialchars(strtoupper(substr($f['name'] ?: 'C', 0, 1))) ?>';
        box.insertBefore(b, hint);
    }
}
</script>

<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
