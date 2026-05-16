<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'banners';
$pageTitle   = 'Banners';

$controller = new AdminController();

$canView   = sinelec_can('view');
$canAdd    = sinelec_can('add');
$canEdit   = sinelec_can('edit');
$canDelete = sinelec_can('delete');

if (!$canView) {
    sinelec_set_flash('err', 'You do not have permission to view this page.');
    header('location:dashboard'); exit();
}

$banners = $controller->getAllBanners();
$pubBase = rtrim(sinelec_env('PUBLIC_BASE_URL') ?: '', '/');

ob_start();
?>

<div class="pg-header">
  <div>
    <div class="pg-title">Banners</div>
    <div class="pg-sub">Manage homepage banners and promotional slides.</div>
  </div>
  <?php if ($canAdd): ?>
  <button class="btn btn--primary" onclick="openBannerModal(0)">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Banner
  </button>
  <?php endif; ?>
</div>

<!-- Search -->
<div style="display:flex;gap:10px;margin-bottom:18px;align-items:center;">
  <div style="position:relative;flex:1;min-width:220px;max-width:340px;">
    <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#9ca3af;" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="bnrSearch" class="form-control" placeholder="Search name or description…" style="padding-left:32px;height:36px;" oninput="bnrOnSearch()">
  </div>
</div>

<!-- Table -->
<div class="card">
  <?php if (empty($banners)): ?>
  <div class="card-body">
    <div class="empty-state">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
      <h3>No banners yet</h3>
      <p>Add your first banner to display on the homepage.</p>
      <?php if ($canAdd): ?><button class="btn btn--primary" onclick="openBannerModal(0)">Add Banner</button><?php endif; ?>
    </div>
  </div>
  <?php else: ?>

  <!-- Pagination bar -->
  <div class="emp-pgbar" id="bnrPgBar">
    <div class="emp-pgbar-info" id="bnrPgInfo">Showing 1–10 of <?= count($banners) ?> records</div>
    <div class="emp-pgbar-right">
      <span class="emp-pgbar-rpp-label">Records per page</span>
      <select id="bnrRpp" class="emp-pgbar-rpp-sel">
        <option value="10" selected>10</option>
        <option value="20">20</option>
        <option value="50">50</option>
        <option value="100">100</option>
      </select>
      <button class="emp-pgbar-apply" onclick="bnrApplyRpp()">Apply</button>
      <div class="emp-pgbar-nav" id="bnrNav"></div>
    </div>
  </div>

  <div class="card-body card-body--flush">
    <table class="dt" id="bnrTable">
      <thead>
        <tr>
          <th style="width:40px;">#</th>
          <th style="width:110px;">Preview</th>
          <th>Banner Name</th>
          <th style="width:80px;text-align:center;">Priority</th>
          <th style="width:90px;text-align:center;">Display</th>
          <?php if ($canEdit || $canDelete): ?>
          <th style="width:60px;text-align:center;">Actions</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody id="bnrTbody">
        <?php foreach ($banners as $i => $b):
          $bid   = (int)($b->BANNER_ID ?? 0);
          $bname = (string)($b->BANNER_NAME ?? '');
          $bdesc = (string)($b->BANNER_DESCRIPTION ?? '');
          $prio  = (int)($b->PRIORITY ?? 0);
          $flag  = (string)($b->DISPLAY_FLAG ?? 'Yes');
          $link  = (string)($b->HYPERLINK ?? '');
          $imgKey= (string)($b->BANNER_IMG_EXT ?? '');
          $color = (string)($b->COLOR ?? '');
          $tags  = (string)($b->TAGS ?? '');
          $btnOne= (string)($b->BTN_ONE ?? '');
          $btnOneL=(string)($b->BTN_ONE_LINK ?? '');
          $btnTwo= (string)($b->BTN_TWO ?? '');
          $btnTwoL=(string)($b->BTN_TWO_LINK ?? '');
          $imgUrl= $imgKey !== '' ? $pubBase.'/'.$imgKey : '';
          $searchStr = strtolower($bname.' '.$bdesc.' '.$tags);
        ?>
        <tr data-search="<?= htmlspecialchars($searchStr) ?>" data-seq="<?= $i + 1 ?>">
          <td class="td-sm bnr-sno"><?= $i + 1 ?></td>
          <td>
            <?php if ($imgUrl !== ''): ?>
              <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($bname) ?>"
                   style="width:90px;height:52px;object-fit:cover;border-radius:6px;border:1px solid var(--border);"
                   onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
              <div style="display:none;width:90px;height:52px;border-radius:6px;border:1px solid var(--border);align-items:center;justify-content:center;<?= $color ? 'background:'.htmlspecialchars($color).';' : 'background:#f1f5f9;' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="<?= $color ? '#ffffff88' : '#94a3b8' ?>" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
              </div>
            <?php elseif ($color !== ''): ?>
              <div style="width:90px;height:52px;border-radius:6px;border:1px solid var(--border);background:<?= htmlspecialchars($color) ?>;display:flex;align-items:center;justify-content:center;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ffffff88" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
              </div>
            <?php else: ?>
              <div style="width:90px;height:52px;border-radius:6px;border:1px solid var(--border);background:#f1f5f9;display:flex;align-items:center;justify-content:center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
              </div>
            <?php endif; ?>
          </td>
          <td>
            <div style="font-weight:600;font-size:13px;color:var(--text);"><?= htmlspecialchars($bname) ?></div>
            <?php if ($bdesc): ?><div style="font-size:11px;color:var(--text-muted);margin-top:2px;max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($bdesc) ?></div><?php endif; ?>
            <?php if ($tags): ?>
            <div style="margin-top:4px;display:flex;flex-wrap:wrap;gap:4px;">
              <?php foreach (array_slice(explode(',', $tags), 0, 4) as $tag): $tag = trim($tag); if ($tag === '') continue; ?>
              <span style="background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;border-radius:20px;font-size:10px;padding:1px 7px;font-weight:500;"><?= htmlspecialchars($tag) ?></span>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if ($btnOne || $btnTwo): ?>
            <div style="margin-top:4px;font-size:11px;color:var(--text-muted);">
              <?php if ($btnOne): ?><span style="background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;border-radius:4px;padding:1px 6px;font-size:10px;">Btn: <?= htmlspecialchars($btnOne) ?></span><?php endif; ?>
              <?php if ($btnTwo): ?><span style="background:#faf5ff;color:#7c3aed;border:1px solid #ddd6fe;border-radius:4px;padding:1px 6px;font-size:10px;margin-left:4px;">Btn: <?= htmlspecialchars($btnTwo) ?></span><?php endif; ?>
            </div>
            <?php endif; ?>
          </td>
          <td style="text-align:center;font-size:13px;color:var(--text-muted);"><?= $prio ?></td>
          <td style="text-align:center;">
            <span class="badge <?= $flag === 'Yes' ? 'badge--green' : 'badge--amber' ?>">
              <?= $flag === 'Yes' ? 'Active' : 'Hidden' ?>
            </span>
          </td>
          <?php if ($canEdit || $canDelete): ?>
          <td>
            <div class="kbm-wrap">
              <button class="kbm-btn" onclick="toggleKbm(this)" title="Actions">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                  <circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/>
                </svg>
              </button>
              <div class="kbm-drop">
                <?php if ($canEdit): ?>
                <button class="kbm-item" onclick="closeKbm(this);openBannerModal(<?= $bid ?>)">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  Edit
                </button>
                <?php endif; ?>
                <?php if ($canDelete): ?>
                <?php if ($canEdit): ?><div class="kbm-divider"></div><?php endif; ?>
                <button class="kbm-item kbm-item--danger" onclick="closeKbm(this);confirmDeleteBanner(<?= $bid ?>,<?= htmlspecialchars(json_encode($bname), ENT_QUOTES) ?>)">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                  Delete
                </button>
                <?php endif; ?>
              </div>
            </div>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div id="bnrNoResults" style="display:none;padding:40px;text-align:center;color:var(--text-muted);font-size:13px;">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="display:block;margin:0 auto 10px;opacity:.4"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      No banners match your search.
    </div>
  </div>
  <?php endif; ?>
</div>


<!-- ════════════════════════════════════════════════════
     ADD / EDIT BANNER MODAL
════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="bnrModal">
  <div class="modal" style="max-width:640px;max-height:92vh;display:flex;flex-direction:column;">

    <div class="modal-hd" style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid var(--border);flex-shrink:0;">
      <div>
        <div class="modal-title" id="bnrModalTitle">Add Banner</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">Fill in the banner details below.</div>
      </div>
      <button class="modal-close" onclick="closeModal('bnrModal')" style="font-size:22px;line-height:1;">×</button>
    </div>

    <div style="overflow-y:auto;flex:1;padding:22px;">
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=InsertBanner') ?>" id="bnrForm" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="banner_id"        id="fBnrId"      value="0">
        <input type="hidden" name="existing_img_key" id="fBnrImgKey"  value="">
        <input type="hidden" name="banner_bg_color"  id="fBnrColor"   value="">

        <!-- ── Banner Name ── -->
        <div class="fg" style="margin-bottom:16px;">
          <label>Banner Name <span class="req">*</span></label>
          <input type="text" name="banner_name" id="fBnrName" class="form-control" placeholder="e.g. Summer Sale 2025" required>
        </div>

        <!-- ── Custom File Upload ── -->
        <div class="fg" style="margin-bottom:16px;">
          <label>Banner Image <span style="font-size:11px;color:var(--text-muted);font-weight:400;">JPG, PNG, WebP · max 20 MB · recommended 1920×600</span></label>
          <input type="file" name="banner_image" id="fBnrImg" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none;" onchange="bnrOnFileSelect(this)">

          <!-- Drop zone (empty state) -->
          <div class="bnr-drop-zone" id="bnrDropZone" onclick="document.getElementById('fBnrImg').click()">
            <div class="bnr-drop-inner" id="bnrDropEmpty">
              <div class="bnr-drop-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              </div>
              <div class="bnr-drop-text">
                <span>Drag &amp; drop image here or <em>browse</em></span>
                <small>JPG, PNG, WebP, GIF up to 20 MB</small>
              </div>
            </div>

            <!-- Selected state -->
            <div class="bnr-file-selected" id="bnrFileSelected" style="display:none;">
              <div class="bnr-file-thumb-wrap">
                <img id="bnrImgPreview" src="" alt="preview" class="bnr-file-thumb">
                <div class="bnr-file-overlay">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  <span>Change</span>
                </div>
              </div>
              <div class="bnr-file-info">
                <span class="bnr-file-name" id="bnrFileName">filename.jpg</span>
                <span class="bnr-file-size" id="bnrFileSize">0 KB</span>
                <span class="bnr-file-badge">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                  Ready to upload
                </span>
              </div>
              <button type="button" class="bnr-file-remove" onclick="event.stopPropagation();bnrRemoveImg()" title="Remove image">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
          </div>
        </div>

        <!-- ── Background Color (gradient picker) ── -->
        <div class="fg" style="margin-bottom:16px;">
          <label style="display:flex;align-items:center;gap:6px;">
            Background Color
            <span style="font-size:11px;color:var(--text-muted);font-weight:400;">— fallback when no image</span>
          </label>
          <div class="grad-row" id="gradSwatches">
            <?php
            $presets = [
              ''                                                 => ['label'=>'None',        'style'=>'background:#f1f5f9;border:1.5px dashed #cbd5e1;'],
              'linear-gradient(135deg,#2563eb,#7c3aed)'         => ['label'=>'Blue–Purple', 'style'=>'background:linear-gradient(135deg,#2563eb,#7c3aed);'],
              'linear-gradient(135deg,#0ea5e9,#06b6d4)'         => ['label'=>'Sky–Cyan',    'style'=>'background:linear-gradient(135deg,#0ea5e9,#06b6d4);'],
              'linear-gradient(135deg,#f59e0b,#ef4444)'         => ['label'=>'Amber–Red',   'style'=>'background:linear-gradient(135deg,#f59e0b,#ef4444);'],
              'linear-gradient(135deg,#10b981,#0ea5e9)'         => ['label'=>'Green–Blue',  'style'=>'background:linear-gradient(135deg,#10b981,#0ea5e9);'],
              'linear-gradient(135deg,#1e293b,#334155)'         => ['label'=>'Dark Slate',  'style'=>'background:linear-gradient(135deg,#1e293b,#334155);'],
              'linear-gradient(135deg,#f97316,#ec4899)'         => ['label'=>'Orange–Pink', 'style'=>'background:linear-gradient(135deg,#f97316,#ec4899);'],
              'linear-gradient(135deg,#6366f1,#a855f7,#ec4899)' => ['label'=>'Tri-color',   'style'=>'background:linear-gradient(135deg,#6366f1,#a855f7,#ec4899);'],
            ];
            foreach ($presets as $val => $meta):
            ?>
            <button type="button" class="grad-swatch" data-val="<?= htmlspecialchars($val) ?>"
              style="<?= $meta['style'] ?>" onclick="selectGrad(this)" title="<?= htmlspecialchars($meta['label']) ?>"></button>
            <?php endforeach; ?>
          </div>
          <div class="grad-custom-row">
            <label>Custom:</label>
            <div class="grad-custom-field">
              <span>Start</span><input type="color" id="gradStart" value="#2563eb" oninput="buildCustomGrad()">
            </div>
            <div class="grad-custom-field">
              <span>End</span><input type="color" id="gradEnd" value="#7c3aed" oninput="buildCustomGrad()">
            </div>
            <button type="button" onclick="applyCustomGrad()" class="btn btn--outline" style="height:30px;font-size:12px;padding:0 12px;">Apply</button>
            <div id="gradPreviewBox" class="grad-preview-box"></div>
          </div>
        </div>

        <!-- ── Description ── -->
        <div class="fg" style="margin-bottom:16px;">
          <label>Description</label>
          <textarea name="banner_description" id="fBnrDesc" class="form-control" rows="2" placeholder="Short promotional text shown on the banner…"></textarea>
        </div>

        <!-- ── Tags ── -->
        <div class="fg" style="margin-bottom:16px;">
          <label>Tags <span style="font-size:11px;color:var(--text-muted);font-weight:400;">comma-separated</span></label>
          <input type="text" name="tags" id="fBnrTags" class="form-control" placeholder="e.g. sale, summer, featured">
        </div>

        <!-- ── Priority + Display ── -->
        <div class="form-row cols-2" style="margin-bottom:16px;">
          <div class="fg">
            <label>Priority</label>
            <input type="number" name="priority" id="fBnrPrio" class="form-control" value="0" min="0">
          </div>
          <div class="fg">
            <label>Display Status</label>
            <select name="display_flag" id="fBnrFlag" class="form-control">
              <option value="Yes">Active (Visible)</option>
              <option value="No">Hidden</option>
            </select>
          </div>
        </div>

        <!-- ── Hyperlink ── -->
        <div class="fg" style="margin-bottom:16px;">
          <label>Banner Hyperlink</label>
          <input type="text" name="hyperlink" id="fBnrLink" class="form-control" placeholder="https://…">
        </div>

        <!-- ── Buttons section ── -->
        <div style="border:1px solid var(--border);border-radius:10px;padding:14px 16px;margin-bottom:20px;background:#fafbfc;">
          <div style="font-size:12px;font-weight:700;color:var(--text);margin-bottom:12px;display:flex;align-items:center;gap:6px;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="9" width="18" height="6" rx="2"/></svg>
            Call-to-Action Buttons
          </div>
          <div class="form-row cols-2" style="margin-bottom:10px;">
            <div class="fg">
              <label style="font-size:11px;">Button 1 Label</label>
              <input type="text" name="btn_one" id="fBnrBtnOne" class="form-control" placeholder="e.g. Shop Now">
            </div>
            <div class="fg">
              <label style="font-size:11px;">Button 1 Link</label>
              <input type="text" name="btn_one_link" id="fBnrBtnOneL" class="form-control" placeholder="https://…">
            </div>
          </div>
          <div class="form-row cols-2">
            <div class="fg">
              <label style="font-size:11px;">Button 2 Label</label>
              <input type="text" name="btn_two" id="fBnrBtnTwo" class="form-control" placeholder="e.g. Learn More">
            </div>
            <div class="fg">
              <label style="font-size:11px;">Button 2 Link</label>
              <input type="text" name="btn_two_link" id="fBnrBtnTwoL" class="form-control" placeholder="https://…">
            </div>
          </div>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;padding-top:14px;border-top:1px solid var(--border);">
          <button type="button" class="btn btn--outline" onclick="closeModal('bnrModal')">Cancel</button>
          <button type="submit" class="btn btn--primary" id="bnrSubmitBtn">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v14a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Save Banner
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ════════════════════════════════════════════════════
     DELETE CONFIRM MODAL
════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="deleteBnrModal">
  <div class="modal modal--sm">
    <div class="modal-hd" style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
      <span class="modal-title">Delete Banner</span>
      <button class="modal-close" onclick="closeModal('deleteBnrModal')">×</button>
    </div>
    <div class="modal-body">
      <div style="display:flex;gap:14px;align-items:flex-start;margin-bottom:20px;">
        <div style="width:40px;height:40px;border-radius:50%;background:#fff5f5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2">
            <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
          </svg>
        </div>
        <div>
          <div style="font-weight:600;margin-bottom:5px;font-size:14px;">Are you sure?</div>
          <div style="font-size:13px;color:var(--text-muted);">You are about to permanently delete banner <strong id="delBnrName"></strong>. The image will also be removed from storage.</div>
        </div>
      </div>
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=DeleteBanner') ?>">
        <input type="hidden" name="banner_id" id="delBnrId">
        <div style="display:flex;gap:10px;justify-content:flex-end;">
          <button type="button" class="btn btn--outline" onclick="closeModal('deleteBnrModal')">Cancel</button>
          <button type="submit" class="btn btn--danger-solid">Delete Banner</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ── Scoped CSS ── -->
<style>

/* ── Pagination bar ── */
.emp-pgbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 10px;
  padding: 12px 18px;
  border-bottom: 1px solid var(--border);
  background: #fafbfc;
  border-radius: var(--radius) var(--radius) 0 0;
}
.emp-pgbar-info { font-size: 13px; color: var(--text-muted); white-space: nowrap; }
.emp-pgbar-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.emp-pgbar-rpp-label { font-size: 13px; font-weight: 600; color: var(--text); white-space: nowrap; }
.emp-pgbar-rpp-sel {
  height: 34px;
  padding: 0 28px 0 10px;
  border: 1.5px solid var(--border);
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
  background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 10px center;
  -webkit-appearance: none;
  appearance: none;
  cursor: pointer;
  color: var(--text);
  min-width: 70px;
}
.emp-pgbar-rpp-sel:focus { outline: none; border-color: var(--primary); }
.emp-pgbar-apply {
  height: 34px;
  padding: 0 16px;
  border: 1.5px solid var(--border);
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
  background: #fff;
  color: var(--text);
  cursor: pointer;
  transition: border-color .15s, background .15s;
}
.emp-pgbar-apply:hover { border-color: var(--primary); background: #f0f4ff; color: var(--primary); }
.emp-pgbar-nav { display: flex; align-items: center; gap: 4px; }
.pg-btn {
  min-width: 34px;
  height: 34px;
  padding: 0 10px;
  border: 1.5px solid var(--border);
  border-radius: 20px;
  font-size: 13px;
  font-weight: 500;
  background: #fff;
  color: var(--text);
  cursor: pointer;
  transition: border-color .15s, background .15s, color .15s;
  white-space: nowrap;
  display: flex;
  align-items: center;
  justify-content: center;
}
.pg-btn:hover:not(:disabled) { border-color: var(--primary); color: var(--primary); background: #f0f4ff; }
.pg-btn.pg-active { background: var(--primary); border-color: var(--primary); color: #fff; font-weight: 700; pointer-events: none; }
.pg-btn:disabled { opacity: .38; cursor: not-allowed; }
.pg-dots { font-size: 13px; color: var(--text-muted); padding: 0 4px; line-height: 34px; }

/* ── Custom upload drop zone ── */
.bnr-drop-zone {
  border: 2px dashed #cbd5e1;
  border-radius: 12px;
  background: #f8fafc;
  cursor: pointer;
  transition: border-color .2s, background .2s;
  overflow: hidden;
  min-height: 110px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.bnr-drop-zone:hover,
.bnr-drop-zone.drag-over {
  border-color: var(--primary);
  background: #eff6ff;
}
.bnr-drop-inner {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 22px 16px;
  pointer-events: none;
  width: 100%;
}
.bnr-drop-icon {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: #e0e7ff;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #4f46e5;
  transition: background .2s;
}
.bnr-drop-zone:hover .bnr-drop-icon,
.bnr-drop-zone.drag-over .bnr-drop-icon {
  background: #c7d2fe;
}
.bnr-drop-text { text-align: center; }
.bnr-drop-text span { font-size: 13px; font-weight: 500; color: var(--text); }
.bnr-drop-text em { color: var(--primary); font-style: normal; font-weight: 600; text-decoration: underline; }
.bnr-drop-text small { display: block; font-size: 11px; color: var(--text-muted); margin-top: 3px; }

/* ── File selected state ── */
.bnr-file-selected {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 16px;
  width: 100%;
  pointer-events: none;
}
.bnr-file-thumb-wrap {
  position: relative;
  flex-shrink: 0;
  border-radius: 8px;
  overflow: hidden;
  width: 80px;
  height: 52px;
  border: 1px solid var(--border);
}
.bnr-file-thumb { width: 100%; height: 100%; object-fit: cover; display: block; }
.bnr-file-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,.45);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 2px;
  opacity: 0;
  transition: opacity .18s;
  color: #fff;
  font-size: 10px;
  font-weight: 600;
}
.bnr-drop-zone:hover .bnr-file-overlay { opacity: 1; }
.bnr-file-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.bnr-file-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--text);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.bnr-file-size { font-size: 11px; color: var(--text-muted); }
.bnr-file-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 10px;
  font-weight: 600;
  color: #16a34a;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 20px;
  padding: 1px 8px;
  width: fit-content;
}
.bnr-file-remove {
  flex-shrink: 0;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: 1.5px solid #fecaca;
  background: #fff5f5;
  color: #dc2626;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: all;
  transition: background .15s, border-color .15s;
}
.bnr-file-remove:hover { background: #fef2f2; border-color: #f87171; }

/* ── Gradient picker ── */
.grad-row {
  display: flex;
  flex-wrap: wrap;
  gap: 7px;
  margin-bottom: 10px;
}
.grad-swatch {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  cursor: pointer;
  border: 2px solid transparent;
  transition: border-color .15s, transform .12s, box-shadow .15s;
}
.grad-swatch:hover { transform: scale(1.12); }
.grad-swatch.grad-selected {
  border-color: var(--primary) !important;
  box-shadow: 0 0 0 3px rgba(37,99,235,.2);
}
.grad-custom-row {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.grad-custom-row > label { font-size: 12px; font-weight: 600; color: var(--text); white-space: nowrap; }
.grad-custom-field {
  display: flex;
  align-items: center;
  gap: 5px;
  background: #f8fafc;
  border: 1.5px solid var(--border);
  border-radius: 8px;
  padding: 3px 8px;
}
.grad-custom-field span { font-size: 11px; color: var(--text-muted); font-weight: 500; }
.grad-custom-field input[type="color"] {
  width: 30px;
  height: 24px;
  padding: 1px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  background: transparent;
}
.grad-preview-box {
  flex: 1;
  min-width: 50px;
  height: 30px;
  border-radius: 8px;
  border: 1.5px solid var(--border);
  background: #f1f5f9;
  transition: background .2s;
}
</style>


<!-- ── Embedded data + JS ── -->
<script>
const BNR_DATA = <?= json_encode(array_map(function ($b) {
    return [
        'id'      => (int)($b->BANNER_ID ?? 0),
        'name'    => (string)($b->BANNER_NAME ?? ''),
        'desc'    => (string)($b->BANNER_DESCRIPTION ?? ''),
        'prio'    => (int)($b->PRIORITY ?? 0),
        'flag'    => (string)($b->DISPLAY_FLAG ?? 'Yes'),
        'link'    => (string)($b->HYPERLINK ?? ''),
        'key'     => (string)($b->BANNER_IMG_EXT ?? ''),
        'color'   => (string)($b->COLOR ?? ''),
        'tags'    => (string)($b->TAGS ?? ''),
        'btn_one' => (string)($b->BTN_ONE ?? ''),
        'btn_one_link' => (string)($b->BTN_ONE_LINK ?? ''),
        'btn_two' => (string)($b->BTN_TWO ?? ''),
        'btn_two_link' => (string)($b->BTN_TWO_LINK ?? ''),
    ];
}, $banners), JSON_FORCE_OBJECT) ?>;

const BNR_PUB_BASE = <?= json_encode(rtrim(sinelec_env('PUBLIC_BASE_URL') ?: '', '/')) ?>;

/* ═══════════════════════════════════════════════════════
   PAGINATION ENGINE
   ═══════════════════════════════════════════════════════ */
(function () {
  var allRows  = [];
  var filtered = [];
  var curPage  = 1;
  var rpp      = 10;

  function init() {
    allRows  = Array.from(document.querySelectorAll('#bnrTbody tr'));
    filtered = allRows.slice();
    render();
  }

  window.bnrOnSearch = function () {
    var q = document.getElementById('bnrSearch').value.toLowerCase().trim();
    filtered = q ? allRows.filter(function (r) { return r.dataset.search.includes(q); }) : allRows.slice();
    curPage = 1;
    render();
  };

  window.bnrApplyRpp = function () {
    rpp = parseInt(document.getElementById('bnrRpp').value, 10) || 10;
    curPage = 1;
    render();
  };

  window.bnrGoPage = function (p) { curPage = p; render(); };

  function render() {
    var total = filtered.length;
    var pages = Math.max(1, Math.ceil(total / rpp));
    curPage   = Math.min(curPage, pages);
    var start = (curPage - 1) * rpp;
    var end   = Math.min(start + rpp, total);

    allRows.forEach(function (r) { r.style.display = 'none'; });
    filtered.slice(start, end).forEach(function (r, idx) {
      r.style.display = '';
      var sno = r.querySelector('.bnr-sno');
      if (sno) sno.textContent = start + idx + 1;
    });

    var noRes = document.getElementById('bnrNoResults');
    if (noRes) noRes.style.display = total === 0 ? 'block' : 'none';

    var info = document.getElementById('bnrPgInfo');
    if (info) {
      info.textContent = total === 0 ? 'No records found'
        : 'Showing ' + (start + 1) + '–' + end + ' of ' + total + ' records';
    }
    renderBnrNav(curPage, pages);
  }

  function renderBnrNav(cur, pages) {
    var nav = document.getElementById('bnrNav');
    if (!nav) return;
    var html = '';
    html += '<button class="pg-btn" onclick="bnrGoPage(' + (cur-1) + ')"' + (cur<=1?' disabled':'') + '>Prev</button>';
    buildBnrNums(cur, pages).forEach(function (p) {
      html += p === '...'
        ? '<span class="pg-dots">…</span>'
        : '<button class="pg-btn' + (p===cur?' pg-active':'') + '" onclick="bnrGoPage('+p+')">' + p + '</button>';
    });
    html += '<button class="pg-btn" onclick="bnrGoPage(' + (cur+1) + ')"' + (cur>=pages?' disabled':'') + '>Next</button>';
    nav.innerHTML = html;
  }

  function buildBnrNums(cur, pages) {
    if (pages <= 7) { var a=[]; for (var i=1;i<=pages;i++) a.push(i); return a; }
    if (cur <= 4)         return [1,2,3,4,5,'...',pages];
    if (cur >= pages - 3) return [1,'...',pages-4,pages-3,pages-2,pages-1,pages];
    return [1,'...',cur-1,cur,cur+1,'...',pages];
  }

  document.readyState === 'loading'
    ? document.addEventListener('DOMContentLoaded', init)
    : init();
})();


/* ═══════════════════════════════════════════════════════
   CUSTOM FILE UPLOADER
   ═══════════════════════════════════════════════════════ */
(function () {
  var zone = null;

  function getZone() { return zone || (zone = document.getElementById('bnrDropZone')); }

  function formatBytes(bytes) {
    if (bytes < 1024)       return bytes + ' B';
    if (bytes < 1048576)    return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
  }

  window.bnrOnFileSelect = function (input) {
    if (!input.files || !input.files[0]) return;
    var file = input.files[0];
    var reader = new FileReader();
    reader.onload = function (e) {
      document.getElementById('bnrImgPreview').src = e.target.result;
      document.getElementById('bnrFileName').textContent = file.name;
      document.getElementById('bnrFileSize').textContent = formatBytes(file.size);
      document.getElementById('bnrDropEmpty').style.display   = 'none';
      document.getElementById('bnrFileSelected').style.display = 'flex';
    };
    reader.readAsDataURL(file);
  };

  window.bnrRemoveImg = function () {
    var input = document.getElementById('fBnrImg');
    input.value = '';
    document.getElementById('bnrImgPreview').src = '';
    document.getElementById('bnrDropEmpty').style.display    = 'flex';
    document.getElementById('bnrFileSelected').style.display = 'none';
    /* Clear existing key so old image is not kept if user explicitly removes */
    document.getElementById('fBnrImgKey').value = '';
  };

  window.bnrSetExistingImg = function (url, filename) {
    document.getElementById('bnrImgPreview').src = url;
    document.getElementById('bnrFileName').textContent = filename || 'Current image';
    document.getElementById('bnrFileSize').textContent = 'Saved';
    document.getElementById('bnrDropEmpty').style.display    = 'none';
    document.getElementById('bnrFileSelected').style.display = 'flex';
    /* Change badge text */
    var badge = document.querySelector('.bnr-file-badge');
    if (badge) badge.innerHTML = '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Saved';
  };

  /* Drag-and-drop wiring (runs after DOM ready) */
  function wireDrop() {
    var z = getZone();
    if (!z) return;
    z.addEventListener('dragover', function (e) { e.preventDefault(); z.classList.add('drag-over'); });
    z.addEventListener('dragleave', function () { z.classList.remove('drag-over'); });
    z.addEventListener('drop', function (e) {
      e.preventDefault();
      z.classList.remove('drag-over');
      var files = e.dataTransfer.files;
      if (files && files[0]) {
        var input = document.getElementById('fBnrImg');
        /* Create a DataTransfer to assign files to the input */
        try {
          var dt = new DataTransfer();
          dt.items.add(files[0]);
          input.files = dt.files;
        } catch (ex) { /* Safari fallback — just trigger manually */ }
        window.bnrOnFileSelect(input.files.length ? input : { files: files });
      }
    });
  }

  document.readyState === 'loading'
    ? document.addEventListener('DOMContentLoaded', wireDrop)
    : wireDrop();
})();


/* ═══════════════════════════════════════════════════════
   GRADIENT PICKER
   ═══════════════════════════════════════════════════════ */
function selectGrad(el) {
  document.querySelectorAll('.grad-swatch').forEach(function (s) { s.classList.remove('grad-selected'); });
  el.classList.add('grad-selected');
  var val = el.dataset.val || '';
  document.getElementById('fBnrColor').value = val;
  document.getElementById('gradPreviewBox').style.background = val || '#f1f5f9';
}

function buildCustomGrad() {
  var s = document.getElementById('gradStart').value;
  var e = document.getElementById('gradEnd').value;
  document.getElementById('gradPreviewBox').style.background = 'linear-gradient(135deg,' + s + ',' + e + ')';
}

function applyCustomGrad() {
  var s    = document.getElementById('gradStart').value;
  var e    = document.getElementById('gradEnd').value;
  var grad = 'linear-gradient(135deg,' + s + ',' + e + ')';
  document.getElementById('fBnrColor').value = grad;
  document.getElementById('gradPreviewBox').style.background = grad;
  document.querySelectorAll('.grad-swatch').forEach(function (sw) { sw.classList.remove('grad-selected'); });
}

function resetGradPicker(colorVal) {
  var matched = false;
  document.querySelectorAll('.grad-swatch').forEach(function (sw) {
    sw.classList.remove('grad-selected');
    if (sw.dataset.val === colorVal) { sw.classList.add('grad-selected'); matched = true; }
  });
  if (!matched) {
    var none = document.querySelector('.grad-swatch[data-val=""]');
    if (none) none.classList.add('grad-selected');
  }
  document.getElementById('fBnrColor').value = colorVal;
  document.getElementById('gradPreviewBox').style.background = colorVal || '#f1f5f9';
}


/* ═══════════════════════════════════════════════════════
   ADD / EDIT MODAL
   ═══════════════════════════════════════════════════════ */
function openBannerModal(bannerId) {
  var form    = document.getElementById('bnrForm');
  var isEdit  = bannerId > 0;
  var addUrl  = 'service?urlstring=<?= EncryptURL('action=InsertBanner') ?>';
  var editUrl = 'service?urlstring=<?= EncryptURL('action=UpdateBanner') ?>';

  /* Reset everything */
  form.reset();
  bnrRemoveImg();
  resetGradPicker('');

  /* Reset badge text back to "Ready to upload" */
  var badge = document.querySelector('.bnr-file-badge');
  if (badge) badge.innerHTML = '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Ready to upload';

  document.getElementById('fBnrId').value     = bannerId;
  document.getElementById('fBnrImgKey').value = '';
  document.getElementById('bnrModalTitle').textContent = isEdit ? 'Edit Banner' : 'Add Banner';
  document.getElementById('bnrSubmitBtn').innerHTML =
    '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v14a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> '
    + (isEdit ? 'Update Banner' : 'Save Banner');
  form.action = isEdit ? editUrl : addUrl;

  if (isEdit) {
    var d = Object.values(BNR_DATA).find(function (b) { return b.id === bannerId; });
    if (d) {
      document.getElementById('fBnrName').value     = d.name;
      document.getElementById('fBnrDesc').value     = d.desc;
      document.getElementById('fBnrPrio').value     = d.prio;
      document.getElementById('fBnrLink').value     = d.link;
      document.getElementById('fBnrTags').value     = d.tags;
      document.getElementById('fBnrBtnOne').value   = d.btn_one;
      document.getElementById('fBnrBtnOneL').value  = d.btn_one_link;
      document.getElementById('fBnrBtnTwo').value   = d.btn_two;
      document.getElementById('fBnrBtnTwoL').value  = d.btn_two_link;
      document.getElementById('fBnrFlag').value     = d.flag;
      document.getElementById('fBnrImgKey').value   = d.key;
      resetGradPicker(d.color);
      if (d.key) {
        var filename = d.key.split('/').pop();
        bnrSetExistingImg(BNR_PUB_BASE + '/' + d.key, filename);
      }
    }
  }

  openModal('bnrModal');
}


/* ═══════════════════════════════════════════════════════
   DELETE MODAL
   ═══════════════════════════════════════════════════════ */
function confirmDeleteBanner(id, name) {
  document.getElementById('delBnrId').value        = id;
  document.getElementById('delBnrName').textContent = name;
  openModal('deleteBnrModal');
}
</script>

<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
