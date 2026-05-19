<?php
require_once '../data/store_data.php';
require_once '../controller/website_controller.php';

$currentPage = 'career';
$pageTitle   = 'Careers – Sinelec Tech';

$controller = new WebsiteController();
$jobs       = $controller->getActiveJobs();

require_once 'header.php';
?>

<style>
/* ── Hero ───────────────────────────────────────────────────────── */
.cr-hero {
  background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 60%, #1a56a0 100%);
  color: #fff;
  padding: 72px 0 56px;
  text-align: center;
  position: relative;
  overflow: hidden;
}
.cr-hero::before {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse 70% 60% at 50% 0%, rgba(59,130,246,.18) 0%, transparent 70%);
  pointer-events: none;
}
.cr-hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: rgba(59,130,246,.18);
  border: 1px solid rgba(59,130,246,.35);
  color: #93c5fd;
  font-size: 12px;
  font-weight: 600;
  letter-spacing: .5px;
  text-transform: uppercase;
  padding: 5px 14px;
  border-radius: 20px;
  margin-bottom: 18px;
}
.cr-hero-title {
  font-size: clamp(28px, 5vw, 48px);
  font-weight: 800;
  line-height: 1.15;
  margin-bottom: 14px;
  letter-spacing: -.5px;
}
.cr-hero-title span { color: #60a5fa; }
.cr-hero-sub {
  font-size: 16px;
  color: #94a3b8;
  max-width: 520px;
  margin: 0 auto 30px;
  line-height: 1.65;
}
.cr-hero-stats {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 32px;
  flex-wrap: wrap;
}
.cr-stat { text-align: center; }
.cr-stat-val { font-size: 28px; font-weight: 800; color: #fff; line-height: 1; }
.cr-stat-lbl { font-size: 12px; color: #94a3b8; margin-top: 3px; }
.cr-stat-sep { width: 1px; height: 36px; background: rgba(255,255,255,.12); }

/* ── Section ────────────────────────────────────────────────────── */
.cr-section { padding: 56px 0; }
.cr-section-hd {
  text-align: center;
  margin-bottom: 40px;
}
.cr-section-label {
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: var(--blue-pri);
  margin-bottom: 8px;
}
.cr-section-title {
  font-size: clamp(22px, 3.5vw, 32px);
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 10px;
}
.cr-section-sub { font-size: 15px; color: #64748b; }

/* ── Values ─────────────────────────────────────────────────────── */
.cr-values {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 56px;
}
.cr-val-card {
  background: #fff;
  border: 1.5px solid #e8edf4;
  border-radius: 16px;
  padding: 24px 20px;
  text-align: center;
  transition: box-shadow .2s, transform .2s;
}
.cr-val-card:hover { box-shadow: 0 8px 28px rgba(0,0,0,.07); transform: translateY(-3px); }
.cr-val-icon {
  width: 48px; height: 48px;
  border-radius: 14px;
  display: grid; place-items: center;
  margin: 0 auto 14px;
}
.cr-val-title { font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 6px; }
.cr-val-desc  { font-size: 13px; color: #64748b; line-height: 1.55; }

/* ── Job listing ────────────────────────────────────────────────── */
.cr-jobs { display: flex; flex-direction: column; gap: 16px; }
.cr-job-card {
  background: #fff;
  border: 1.5px solid #e8edf4;
  border-radius: 16px;
  padding: 24px 28px;
  display: flex;
  align-items: flex-start;
  gap: 20px;
  transition: box-shadow .2s, border-color .2s;
}
.cr-job-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.07); border-color: #bfdbfe; }
.cr-job-icon {
  width: 48px; height: 48px;
  background: #eff6ff;
  border-radius: 14px;
  display: grid; place-items: center;
  color: var(--blue-pri);
  flex-shrink: 0;
}
.cr-job-body { flex: 1; min-width: 0; }
.cr-job-title { font-size: 17px; font-weight: 700; color: #0f172a; margin-bottom: 8px; }
.cr-job-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 12px;
}
.cr-job-tag {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  font-weight: 600;
  color: #475569;
  background: #f1f5f9;
  border-radius: 8px;
  padding: 3px 10px;
}
.cr-job-tag svg { width: 11px; height: 11px; flex-shrink: 0; }
.cr-job-tag.tag-prio-high { background: #fff7ed; color: #c2410c; }
.cr-job-tag.tag-prio-med  { background: #fffbeb; color: #b45309; }
.cr-job-desc {
  font-size: 13px;
  color: #64748b;
  line-height: 1.6;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.cr-job-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
@media (max-width: 600px) {
  .cr-job-card { flex-direction: column; }
  .cr-job-actions { flex-direction: row; width: 100%; }
  .cr-job-actions .btn { flex: 1; justify-content: center; }
}

/* ── Empty state ────────────────────────────────────────────────── */
.cr-empty {
  text-align: center;
  padding: 60px 20px;
  background: #f8fafc;
  border-radius: 16px;
  border: 1.5px dashed #e2e8f0;
  color: #64748b;
}
.cr-empty svg { margin: 0 auto 16px; display: block; opacity: .4; }
.cr-empty h3 { font-size: 18px; font-weight: 700; color: #374151; margin-bottom: 8px; }

/* ── Apply modal ────────────────────────────────────────────────── */
.cr-modal-backdrop {
  position: fixed; inset: 0;
  background: rgba(15,23,42,.5);
  backdrop-filter: blur(4px);
  z-index: 9800;
  display: none;
  align-items: center;
  justify-content: center;
  padding: 16px;
}
.cr-modal-backdrop.open { display: flex; }
.cr-modal {
  background: #fff;
  border-radius: 20px;
  width: 100%;
  max-width: 560px;
  max-height: 92vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 24px 64px rgba(0,0,0,.18);
  animation: crModalIn .22s ease;
}
@keyframes crModalIn {
  from { opacity: 0; transform: translateY(14px) scale(.97); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}
.cr-modal-hd {
  padding: 22px 26px 18px;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  flex-shrink: 0;
}
.cr-modal-title { font-size: 18px; font-weight: 800; color: #0f172a; }
.cr-modal-pos   { font-size: 13px; color: #2563eb; font-weight: 600; margin-top: 2px; }
.cr-modal-close {
  width: 34px; height: 34px;
  border-radius: 10px;
  border: none;
  background: #f1f5f9;
  color: #64748b;
  cursor: pointer;
  display: grid; place-items: center;
  flex-shrink: 0;
  transition: background .15s;
  font-size: 18px; line-height: 1;
}
.cr-modal-close:hover { background: #e2e8f0; }
.cr-modal-body { padding: 22px 26px; overflow-y: auto; flex: 1; }
.cr-modal-ft {
  padding: 14px 26px 20px;
  border-top: 1px solid #f1f5f9;
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  flex-shrink: 0;
}

/* ── Form fields ────────────────────────────────────────────────── */
.cr-fg { margin-bottom: 16px; }
.cr-label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 6px;
}
.cr-label .req { color: #dc2626; margin-left: 2px; }
.cr-input, .cr-select {
  width: 100%;
  height: 42px;
  padding: 0 13px;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  font-size: 14px;
  color: #0f172a;
  background: #fff;
  transition: border-color .15s;
  font-family: inherit;
  box-sizing: border-box;
}
.cr-input:focus, .cr-select:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37,99,235,.08);
}
.cr-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 480px) { .cr-row { grid-template-columns: 1fr; } }

/* Resume upload zone */
.cr-upload-zone {
  border: 2px dashed #e2e8f0;
  border-radius: 12px;
  padding: 22px;
  text-align: center;
  cursor: pointer;
  transition: border-color .15s, background .15s;
  position: relative;
}
.cr-upload-zone:hover, .cr-upload-zone.drag { border-color: #2563eb; background: #eff6ff; }
.cr-upload-zone.has-file { border-color: #22c55e; background: #f0fdf4; }
.cr-upload-zone input[type="file"] {
  position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
}
.cr-upload-icon { margin: 0 auto 8px; display: block; color: #94a3b8; }
.cr-upload-zone.has-file .cr-upload-icon { color: #22c55e; }
.cr-upload-label { font-size: 13px; font-weight: 600; color: #374151; }
.cr-upload-zone.has-file .cr-upload-label { color: #15803d; }
.cr-upload-hint { font-size: 12px; color: #94a3b8; margin-top: 3px; }

/* Progress / success feedback */
.cr-submit-btn { min-width: 130px; position: relative; }
.cr-submit-btn.loading { pointer-events: none; opacity: .7; }
</style>

<!-- ── Hero ─────────────────────────────────────────────────────── -->
<section class="cr-hero">
  <div class="wrap">
    <div class="cr-hero-badge">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
      We're Hiring
    </div>
    <h1 class="cr-hero-title">Build the Future of<br><span>Electronics with Us</span></h1>
    <p class="cr-hero-sub">Join a team that powers electronics manufacturing across India. Work on real engineering challenges, grow fast, and make an impact.</p>
    <div class="cr-hero-stats">
      <div class="cr-stat">
        <div class="cr-stat-val"><?= count($jobs) ?></div>
        <div class="cr-stat-lbl">Open Positions</div>
      </div>
      <div class="cr-stat-sep"></div>
      <div class="cr-stat">
        <div class="cr-stat-val">10+</div>
        <div class="cr-stat-lbl">Years in Industry</div>
      </div>
      <div class="cr-stat-sep"></div>
      <div class="cr-stat">
        <div class="cr-stat-val">50+</div>
        <div class="cr-stat-lbl">Team Members</div>
      </div>
    </div>
  </div>
</section>

<!-- ── Main Content ──────────────────────────────────────────────── -->
<div class="wrap">
  <section class="cr-section">

    <!-- Values -->
    <div class="cr-section-hd">
      <div class="cr-section-label">Why Sinelec</div>
      <div class="cr-section-title">Why you'll love working here</div>
    </div>
    <div class="cr-values">
      <div class="cr-val-card">
        <div class="cr-val-icon" style="background:#eff6ff;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
        </div>
        <div class="cr-val-title">Real Engineering Work</div>
        <div class="cr-val-desc">Work on production-grade electronics, not toy projects. Your code and designs ship to real customers.</div>
      </div>
      <div class="cr-val-card">
        <div class="cr-val-icon" style="background:#f0fdf4;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div class="cr-val-title">Growth & Learning</div>
        <div class="cr-val-desc">Structured mentorship, certifications, and fast career progression for high performers.</div>
      </div>
      <div class="cr-val-card">
        <div class="cr-val-icon" style="background:#fdf4ff;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#9333ea" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="cr-val-title">Collaborative Culture</div>
        <div class="cr-val-desc">Small teams, high ownership. No bureaucracy — just smart people solving hard problems together.</div>
      </div>
      <div class="cr-val-card">
        <div class="cr-val-icon" style="background:#fff7ed;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="cr-val-title">Flexible & Hybrid</div>
        <div class="cr-val-desc">Work-life balance matters. Flexible hours, hybrid arrangements, and generous leave policies.</div>
      </div>
    </div>

    <!-- Job Listings -->
    <div class="cr-section-hd">
      <div class="cr-section-label">Open Roles</div>
      <div class="cr-section-title">Current Openings</div>
      <div class="cr-section-sub">Find the role that matches your skills and ambition.</div>
    </div>

    <?php if (empty($jobs)): ?>
    <div class="cr-empty">
      <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
      <h3>No openings right now</h3>
      <p>We're not actively hiring at the moment, but we love meeting great people.<br>Send your profile to <a href="mailto:careers@sinelec-tech.com" style="color:var(--blue-pri);">careers@sinelec-tech.com</a></p>
    </div>
    <?php else: ?>
    <div class="cr-jobs">
      <?php foreach ($jobs as $j):
        $jid   = (int)(float)($j->JOB_POST_ID    ?? 0);
        $pos   = htmlspecialchars($j->JOB_POSITION  ?? '');
        $loc   = htmlspecialchars($j->JOB_LOCATION  ?? 'India');
        $prio  = (int)(float)($j->JOB_PRIORITY      ?? 1);
        $desc  = strip_tags((string)($j->JOB_DISCRIPTION ?? ''));
        $prioLabel = $prio >= 3 ? 'Urgent' : ($prio === 2 ? 'High Priority' : 'Open');
        $prioClass = $prio >= 3 ? 'tag-prio-high' : ($prio === 2 ? 'tag-prio-med' : '');
      ?>
      <div class="cr-job-card">
        <div class="cr-job-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
        </div>
        <div class="cr-job-body">
          <div class="cr-job-title"><?= $pos ?></div>
          <div class="cr-job-meta">
            <?php if ($loc): ?>
            <span class="cr-job-tag">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              <?= $loc ?>
            </span>
            <?php endif; ?>
            <span class="cr-job-tag <?= $prioClass ?>"><?= $prioLabel ?></span>
            <span class="cr-job-tag">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              Full-time
            </span>
          </div>
          <?php if ($desc): ?>
          <div class="cr-job-desc"><?= htmlspecialchars($desc) ?></div>
          <?php endif; ?>
        </div>
        <div class="cr-job-actions">
          <?php if ($desc): ?>
          <button class="btn btn-outline btn-sm" onclick="viewJobDesc(<?= $jid ?>)">Details</button>
          <?php endif; ?>
          <button class="btn btn-blue btn-sm" onclick="openApply(<?= $jid ?>, <?= htmlspecialchars(json_encode($pos), ENT_QUOTES) ?>)">Apply Now</button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- General application CTA -->
    <div style="text-align:center;margin-top:40px;padding:32px;background:linear-gradient(135deg,#eff6ff,#f0fdf4);border-radius:20px;border:1.5px solid #e0f2fe;">
      <div style="font-size:22px;font-weight:800;color:#0f172a;margin-bottom:8px;">Don't see your role?</div>
      <div style="font-size:14px;color:#64748b;margin-bottom:20px;max-width:420px;margin-left:auto;margin-right:auto;">We're always looking for talented people. Send us your resume and we'll reach out when a match opens up.</div>
      <a href="mailto:careers@sinelec-tech.com" class="btn btn-blue">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,12 2,6"/></svg>
        Email Us Your Resume
      </a>
    </div>

  </section>
</div>


<!-- ══════════════════════════════════════════════════════════════
     APPLY MODAL
══════════════════════════════════════════════════════════════ -->
<div class="cr-modal-backdrop" id="applyModal">
  <div class="cr-modal">
    <div class="cr-modal-hd">
      <div>
        <div class="cr-modal-title">Apply for this Position</div>
        <div class="cr-modal-pos" id="applyModalPos"></div>
      </div>
      <button class="cr-modal-close" onclick="closeApply()" aria-label="Close">×</button>
    </div>
    <div class="cr-modal-body">
      <form id="applyForm" method="POST" action="service?action=ApplyJob" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="job_post_id" id="applyJobId">

        <div class="cr-row">
          <div class="cr-fg">
            <label class="cr-label">Full Name <span class="req">*</span></label>
            <input type="text" name="candidate_name" class="cr-input" placeholder="Your full name" required>
          </div>
          <div class="cr-fg">
            <label class="cr-label">Email Address <span class="req">*</span></label>
            <input type="email" name="candidate_email" class="cr-input" placeholder="you@email.com" required>
          </div>
        </div>

        <div class="cr-row">
          <div class="cr-fg">
            <label class="cr-label">Phone Number</label>
            <input type="tel" name="candidate_phone" class="cr-input" placeholder="10-digit mobile number" maxlength="15">
          </div>
          <div class="cr-fg">
            <label class="cr-label">Years of Experience <span class="req">*</span></label>
            <select name="candidate_experience" class="cr-select" required>
              <option value="" disabled selected>Select experience</option>
              <option value="0">Fresher (0 years)</option>
              <option value="1">1 year</option>
              <option value="2">2 years</option>
              <option value="3">3 years</option>
              <option value="4">4 years</option>
              <option value="5">5 years</option>
              <option value="6">6 years</option>
              <option value="7">7 years</option>
              <option value="8">8+ years</option>
            </select>
          </div>
        </div>

        <!-- Resume Upload -->
        <div class="cr-fg">
          <label class="cr-label">Resume / CV <span class="req">*</span></label>
          <div class="cr-upload-zone" id="resumeZone">
            <input type="file" name="resume" id="resumeInput" accept=".pdf,.doc,.docx" required onchange="onResumeChange(this)">
            <svg class="cr-upload-icon" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            <div class="cr-upload-label" id="resumeLabel">Click to upload or drag &amp; drop</div>
            <div class="cr-upload-hint">PDF, DOC, DOCX — max 5 MB</div>
          </div>
        </div>
      </form>
    </div>
    <div class="cr-modal-ft">
      <button type="button" class="btn btn-outline" onclick="closeApply()">Cancel</button>
      <button type="button" class="btn btn-blue cr-submit-btn" id="applySubmitBtn" onclick="submitApply()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 2 11 13"/><path d="m22 2-7 20-4-9-9-4 20-7z"/></svg>
        Submit Application
      </button>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     JOB DESCRIPTION MODAL
══════════════════════════════════════════════════════════════ -->
<div class="cr-modal-backdrop" id="descModal">
  <div class="cr-modal" style="max-width:640px;">
    <div class="cr-modal-hd">
      <div>
        <div class="cr-modal-title" id="descModalTitle">Job Description</div>
      </div>
      <button class="cr-modal-close" onclick="document.getElementById('descModal').classList.remove('open')" aria-label="Close">×</button>
    </div>
    <div class="cr-modal-body">
      <div id="descModalBody" style="font-size:14px;line-height:1.75;color:#374151;"></div>
    </div>
    <div class="cr-modal-ft">
      <button type="button" class="btn btn-outline" onclick="document.getElementById('descModal').classList.remove('open')">Close</button>
      <button type="button" class="btn btn-blue" id="descApplyBtn">Apply Now</button>
    </div>
  </div>
</div>

<?php
/* Bake job data for JS */
$jobsForJs = array_map(fn($j) => [
    'id'   => (int)(float)($j->JOB_POST_ID    ?? 0),
    'pos'  => (string)($j->JOB_POSITION       ?? ''),
    'desc' => (string)($j->JOB_DISCRIPTION    ?? ''),
], $jobs);
?>

<script>
var CAREER_JOBS = <?= json_encode(array_values($jobsForJs), JSON_HEX_TAG | JSON_HEX_AMP) ?>;

/* ── Modal helpers ──────────────────────────────────────────────── */
function openApply(jobId, pos) {
    document.getElementById('applyJobId').value   = jobId;
    document.getElementById('applyModalPos').textContent = pos;
    document.getElementById('applyForm').reset();
    resetUploadZone();
    document.getElementById('applyModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeApply() {
    document.getElementById('applyModal').classList.remove('open');
    document.body.style.overflow = '';
}

function viewJobDesc(jobId) {
    var d = CAREER_JOBS.find(function(j){ return j.id === jobId; });
    if (!d) return;
    document.getElementById('descModalTitle').textContent = d.pos;
    document.getElementById('descModalBody').innerHTML    = d.desc || '<p style="color:#94a3b8;">No description provided.</p>';
    document.getElementById('descApplyBtn').onclick = function() {
        document.getElementById('descModal').classList.remove('open');
        openApply(d.id, d.pos);
    };
    document.getElementById('descModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

/* Close modals on backdrop click */
document.getElementById('applyModal').addEventListener('click', function(e){ if(e.target===this) closeApply(); });
document.getElementById('descModal').addEventListener('click', function(e){
    if(e.target===this){ this.classList.remove('open'); document.body.style.overflow=''; }
});

/* ── Resume upload zone ─────────────────────────────────────────── */
function resetUploadZone() {
    var zone = document.getElementById('resumeZone');
    var lbl  = document.getElementById('resumeLabel');
    zone.classList.remove('has-file');
    lbl.textContent = 'Click to upload or drag & drop';
}
function onResumeChange(input) {
    var zone = document.getElementById('resumeZone');
    var lbl  = document.getElementById('resumeLabel');
    if (input.files && input.files[0]) {
        zone.classList.add('has-file');
        lbl.textContent = input.files[0].name;
    } else {
        resetUploadZone();
    }
}

/* Drag & drop */
var zone = document.getElementById('resumeZone');
zone.addEventListener('dragover', function(e){ e.preventDefault(); this.classList.add('drag'); });
zone.addEventListener('dragleave', function(){ this.classList.remove('drag'); });
zone.addEventListener('drop', function(e){
    e.preventDefault(); this.classList.remove('drag');
    var dt = e.dataTransfer;
    if (dt && dt.files.length) {
        document.getElementById('resumeInput').files = dt.files;
        onResumeChange(document.getElementById('resumeInput'));
    }
});

/* ── Form submit ────────────────────────────────────────────────── */
function submitApply() {
    var form = document.getElementById('applyForm');

    var name  = form.querySelector('[name="candidate_name"]').value.trim();
    var email = form.querySelector('[name="candidate_email"]').value.trim();
    var exp   = form.querySelector('[name="candidate_experience"]').value;
    var file  = document.getElementById('resumeInput').files[0];

    if (!name)  { alert('Please enter your full name.'); return; }
    if (!email || !email.includes('@')) { alert('Please enter a valid email.'); return; }
    if (!exp)   { alert('Please select your experience level.'); return; }
    if (!file)  { alert('Please upload your resume.'); return; }

    var allowed = ['pdf','doc','docx'];
    var ext = file.name.split('.').pop().toLowerCase();
    if (!allowed.includes(ext)) { alert('Only PDF, DOC, and DOCX files are accepted.'); return; }
    if (file.size > 5 * 1024 * 1024) { alert('Resume must be under 5 MB.'); return; }

    var btn = document.getElementById('applySubmitBtn');
    btn.disabled = true;
    btn.textContent = 'Submitting…';
    form.submit();
}
</script>

<?php require_once 'footer.php'; ?>
