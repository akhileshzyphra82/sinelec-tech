<?php
/**
 * Admin Master Template
 * Expected vars from calling page:
 *   $pageTitle       string
 *   $currentPage     string  (sidebar key)
 *   $pageMainContent string  (HTML)
 */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';

if (empty($_SESSION['sinelec_admin']['USER_ID'])) {
    sinelec_set_flash('warn', 'Please sign in to access the admin panel.');
    header('location:index'); exit();
}

$pageTitle       = $pageTitle       ?? 'Dashboard';
$currentPage     = $currentPage     ?? 'dashboard';
$pageMainContent = $pageMainContent ?? '';

$adminName  = (string)($_SESSION['sinelec_admin']['NAME']  ?? 'Admin');
$adminEmail = (string)($_SESSION['sinelec_admin']['EMAIL'] ?? '');
$firstName  = explode(' ', trim($adminName))[0] ?: 'Admin';
$initials   = strtoupper(substr($firstName, 0, 1));

$flashToast = sinelec_consume_flash();
$flashMsg   = (string)($flashToast['message'] ?? '');
$flashType  = (string)($flashToast['type']    ?? 'ok');

$menu = [
    ['key'=>'dashboard','label'=>'Dashboard','href'=>'welcome',
     'icon'=>'<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>'],
    ['group'=>'Catalog','items'=>[
        ['key'=>'categories','label'=>'Product Categories','href'=>'categories',
         'icon'=>'<path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/>'],
        ['key'=>'products','label'=>'Products','href'=>'products',
         'icon'=>'<rect x="3" y="3" width="18" height="18" rx="3"/><rect x="8" y="8" width="8" height="8" rx="1.5"/>'],
    ]],
    ['group'=>'Inventory','items'=>[
        ['key'=>'purchase','label'=>'Purchase Records','href'=>'purchase',
         'icon'=>'<path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/>'],
        ['key'=>'stock','label'=>'Stock Records','href'=>'stock',
         'icon'=>'<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>'],
    ]],
    ['group'=>'Orders & Sales','items'=>[
        ['key'=>'orders','label'=>'Active Orders','href'=>'orders',
         'icon'=>'<path d="M21 8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>'],
        ['key'=>'orders-history','label'=>'Order History','href'=>'orders-history',
         'icon'=>'<path d="M3 12a9 9 0 105.195-8.195"/><polyline points="3 3 3 9 9 9"/><path d="M12 7v5l3 3"/>'],
        ['key'=>'enquiries','label'=>'Enquiries / RFQ','href'=>'enquiries',
         'icon'=>'<path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>'],
    ]],
    ['group'=>'Customers','items'=>[
        ['key'=>'customers','label'=>'Customer Details','href'=>'customers',
         'icon'=>'<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>'],
    ]],
    ['group'=>'Content','items'=>[
        ['key'=>'banners','label'=>'Banners','href'=>'banners',
         'icon'=>'<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>'],
        ['key'=>'news','label'=>'News & Events','href'=>'news',
         'icon'=>'<path d="M4 22h16a2 2 0 002-2V4a2 2 0 00-2-2H8a2 2 0 00-2 2v16a2 2 0 01-2 2zm0 0a2 2 0 01-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8z"/>'],
        ['key'=>'faq','label'=>'FAQ','href'=>'faq',
         'icon'=>'<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>'],
    ]],
    ['group'=>'Careers','items'=>[
        ['key'=>'jobs','label'=>'Job Posts','href'=>'jobs',
         'icon'=>'<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/>'],
        ['key'=>'applicants','label'=>'Applications','href'=>'applicants',
         'icon'=>'<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>'],
    ]],
];

function sbActive(string $key, string $cur): string { return $key === $cur ? ' is-active' : ''; }
function sbGroupOpen(array $grp, string $cur): bool {
    foreach ($grp['items'] as $i) { if ($i['key'] === $cur) return true; } return false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> — Sinelec Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --sb-w:230px;
  --hd-h:58px;
  --sb-bg:#ffffff;
  --sb-border:#e2e8f0;
  --sb-text:#4b5563;
  --sb-text-hi:#111827;
  --sb-group:#9ca3af;
  --sb-hover-bg:#f1f5f9;
  --sb-active-bg:#eff6ff;
  --sb-active-text:#1d4ed8;
  --sb-active-bar:#2563eb;
  --hd-bg:#ffffff;
  --hd-border:#e2e8f0;
  --bg:#f1f5f9;
  --surface:#ffffff;
  --border:#e2e8f0;
  --text:#111827;
  --text2:#374151;
  --muted:#6b7280;
  --accent:#2563eb;
  --accent-h:#1d4ed8;
  --radius:10px;
  --ok:#16a34a;--ok-bg:#f0fdf4;--ok-bd:#bbf7d0;
  --warn:#d97706;--warn-bg:#fffbeb;--warn-bd:#fde68a;
  --err:#dc2626;--err-bg:#fff5f5;--err-bd:#fecaca;
}
html,body{height:100%;font-family:'Inter',sans-serif;font-size:14px;line-height:1.5;color:var(--text);background:var(--bg)}
a{text-decoration:none;color:inherit}
button{font-family:inherit;cursor:pointer}
img{max-width:100%}

/* ── Shell ── */
.shell{display:flex;min-height:100vh}

/* ── Sidebar ── */
.sidebar{
  width:var(--sb-w);position:fixed;top:0;left:0;bottom:0;z-index:200;
  background:var(--sb-bg);border-right:1px solid var(--sb-border);
  display:flex;flex-direction:column;overflow:hidden;
  transition:width .22s ease,transform .22s ease;
}
.sb-logo{
  display:flex;align-items:center;gap:10px;
  padding:0 16px;height:var(--hd-h);
  border-bottom:1px solid var(--sb-border);flex-shrink:0;
}
.sb-logo-img{height:26px;width:auto;object-fit:contain}
.sb-logo-badge{
  font-size:9px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;
  background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;
  border-radius:4px;padding:2px 6px;white-space:nowrap;
}
.sb-nav{flex:1;overflow-y:auto;overflow-x:hidden;padding:10px 0 16px;scrollbar-width:thin;scrollbar-color:#e2e8f0 transparent}
.sb-nav::-webkit-scrollbar{width:3px}
.sb-nav::-webkit-scrollbar-thumb{background:#e2e8f0;border-radius:3px}

.sb-link{
  display:flex;align-items:center;gap:9px;
  padding:8px 14px;
  color:var(--sb-text);font-size:13px;font-weight:500;
  border-radius:0;position:relative;white-space:nowrap;
  transition:background .12s,color .12s;
}
.sb-link svg{width:16px;height:16px;flex-shrink:0;opacity:.7}
.sb-link:hover{background:var(--sb-hover-bg);color:var(--sb-text-hi)}
.sb-link:hover svg{opacity:1}
.sb-link.is-active{background:var(--sb-active-bg);color:var(--sb-active-text);font-weight:600}
.sb-link.is-active svg{opacity:1}
.sb-link.is-active::before{content:'';position:absolute;left:0;top:5px;bottom:5px;width:3px;background:var(--sb-active-bar);border-radius:0 3px 3px 0}

.sb-group{margin-top:6px}
.sb-group-label{padding:5px 14px 3px;font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--sb-group);user-select:none}

.sb-bottom{padding:10px 14px;border-top:1px solid var(--sb-border);flex-shrink:0}
.sb-collapse-btn{
  display:flex;align-items:center;gap:8px;width:100%;
  background:none;border:none;color:var(--muted);font-size:12px;font-weight:500;
  padding:7px 0;transition:color .12s;
}
.sb-collapse-btn:hover{color:var(--text)}

/* collapsed */
.shell.sb-col .sidebar{width:52px}
.shell.sb-col .main{margin-left:52px}
.shell.sb-col .sb-logo-badge,.shell.sb-col .sb-link-label,.shell.sb-col .sb-group-label,.shell.sb-col .sb-col-label{display:none}
.shell.sb-col .sb-link{padding:9px;justify-content:center}
.shell.sb-col .sb-link.is-active::before{top:7px;bottom:7px}
.shell.sb-col .sb-logo{justify-content:center;padding:0}
.shell.sb-col .sb-collapse-btn{justify-content:center;padding:7px 0}
.shell.sb-col .sb-bottom{padding:10px 0;display:flex;justify-content:center}

/* ── Main column ── */
.main{flex:1;display:flex;flex-direction:column;margin-left:var(--sb-w);min-width:0;transition:margin-left .22s ease}

/* ── Header ── */
.hd{
  position:sticky;top:0;z-index:100;height:var(--hd-h);
  background:var(--hd-bg);border-bottom:1px solid var(--hd-border);
  display:flex;align-items:center;padding:0 22px;gap:14px;
  box-shadow:0 1px 3px rgba(0,0,0,.04);
}
.hd-toggle{
  width:32px;height:32px;display:flex;flex-direction:column;align-items:center;
  justify-content:center;gap:5px;background:none;border:none;border-radius:6px;
  color:var(--muted);flex-shrink:0;transition:background .12s,color .12s;
}
.hd-toggle:hover{background:var(--bg);color:var(--text)}
.hd-toggle span{display:block;width:17px;height:1.5px;background:currentColor;border-radius:2px}

.hd-crumb{flex:1;display:flex;align-items:center;gap:5px;font-size:13px;color:var(--muted);min-width:0}
.hd-crumb a{color:var(--accent);transition:opacity .12s}.hd-crumb a:hover{opacity:.75}
.hd-crumb-sep{color:#d1d5db}
.hd-crumb-cur{color:var(--text);font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

.hd-right{display:flex;align-items:center;gap:6px;flex-shrink:0}

.hd-user-wrap{position:relative}
.hd-user-btn{
  display:flex;align-items:center;gap:8px;padding:5px 10px 5px 5px;
  border-radius:8px;background:none;border:1px solid transparent;
  transition:background .12s,border-color .12s;cursor:pointer;
}
.hd-user-btn:hover{background:var(--bg);border-color:var(--border)}
.hd-avatar{
  width:28px;height:28px;background:var(--accent);border-radius:50%;
  display:grid;place-items:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0;
}
.hd-uname{font-size:13px;font-weight:600;color:var(--text)}
.hd-urole{font-size:11px;color:var(--muted)}
.hd-chevron{color:var(--muted);transition:transform .18s}
.hd-user-btn[aria-expanded=true] .hd-chevron{transform:rotate(180deg)}

.hd-drop{
  position:absolute;top:calc(100% + 7px);right:0;width:200px;
  background:var(--surface);border:1px solid var(--border);border-radius:11px;
  box-shadow:0 8px 28px rgba(0,0,0,.1);overflow:hidden;
  opacity:0;transform:translateY(-5px);pointer-events:none;transition:opacity .14s,transform .14s;z-index:300;
}
.hd-drop.open{opacity:1;transform:translateY(0);pointer-events:auto}
.hd-drop-head{padding:12px 14px 10px;border-bottom:1px solid var(--border)}
.hd-drop-name{font-size:13px;font-weight:600}
.hd-drop-email{font-size:11px;color:var(--muted);word-break:break-all;margin-top:2px}
.hd-drop-items{padding:5px 0}
.hd-drop-item{display:flex;align-items:center;gap:9px;padding:8px 14px;font-size:13px;color:var(--text2);transition:background .1s}
.hd-drop-item:hover{background:#f8fafc}
.hd-drop-item svg{color:var(--muted);flex-shrink:0}
.hd-drop-div{height:1px;background:var(--border);margin:4px 0}
.hd-drop-item.danger{color:var(--err)}.hd-drop-item.danger svg{color:var(--err)}.hd-drop-item.danger:hover{background:var(--err-bg)}

/* ── Page area ── */
.page-content{flex:1;padding:26px 26px 40px}

/* ── Footer ── */
.site-footer{
  padding:13px 26px;border-top:1px solid var(--border);
  font-size:12px;color:var(--muted);display:flex;align-items:center;
  justify-content:space-between;background:var(--surface);
}

/* ── Mobile overlay ── */
.sb-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:150}

/* ── Toast ── */
.toast-stack{position:fixed;top:calc(var(--hd-h)+14px);right:18px;z-index:500;display:flex;flex-direction:column;gap:9px;pointer-events:none}
.toast{
  display:flex;align-items:flex-start;gap:9px;padding:12px 14px;
  border-radius:9px;border:1px solid transparent;font-size:13px;line-height:1.4;
  max-width:350px;box-shadow:0 4px 14px rgba(0,0,0,.09);pointer-events:auto;
  animation:tIn .18s ease;
}
@keyframes tIn{from{opacity:0;transform:translateX(14px)}to{opacity:1;transform:translateX(0)}}
.toast--ok{background:var(--ok-bg);border-color:var(--ok-bd);color:var(--ok)}
.toast--warn{background:var(--warn-bg);border-color:var(--warn-bd);color:var(--warn)}
.toast--err{background:var(--err-bg);border-color:var(--err-bd);color:var(--err)}
.toast-close{margin-left:auto;background:none;border:none;opacity:.5;font-size:15px;padding:0 0 0 6px;cursor:pointer;color:inherit;transition:opacity .12s}
.toast-close:hover{opacity:1}

/* ═══ SHARED COMPONENTS (all pages) ═══════════════════════════ */
.pg-hd{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:22px}
.pg-title{font-size:19px;font-weight:700}
.pg-sub{font-size:13px;color:var(--muted);margin-top:2px}

.card{background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden}
.card-hd{padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.card-title{font-size:14px;font-weight:600}
.card-body{padding:18px}

.stats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px;margin-bottom:22px}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:18px}
.stat-ico{width:38px;height:38px;border-radius:9px;display:grid;place-items:center;margin-bottom:12px}
.stat-ico--blue{background:#eff6ff;color:#1d4ed8}.stat-ico--green{background:#f0fdf4;color:#15803d}
.stat-ico--amber{background:#fffbeb;color:#b45309}.stat-ico--violet{background:#f5f3ff;color:#6d28d9}
.stat-ico--red{background:#fff5f5;color:#dc2626}
.stat-label{font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px}
.stat-val{font-size:24px;font-weight:700}
.stat-note{font-size:12px;color:var(--muted);margin-top:3px}

.tbl-wrap{overflow-x:auto}
table.dt{width:100%;border-collapse:collapse;font-size:13px}
.dt th{text-align:left;padding:10px 13px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);background:#f8fafc;border-bottom:1px solid var(--border);white-space:nowrap}
.dt td{padding:11px 13px;border-bottom:1px solid #f1f5f9;color:var(--text2);vertical-align:middle}
.dt tbody tr:last-child td{border-bottom:none}
.dt tbody tr:hover td{background:#fafbfd}
.dt-empty{text-align:center;color:var(--muted);padding:40px 0 !important;font-style:italic}

.badge{display:inline-flex;align-items:center;padding:3px 8px;border-radius:999px;font-size:11px;font-weight:600;border:1px solid transparent;white-space:nowrap}
.badge--green{background:#f0fdf4;color:#15803d;border-color:#bbf7d0}
.badge--amber{background:#fffbeb;color:#b45309;border-color:#fde68a}
.badge--red{background:#fff5f5;color:#dc2626;border-color:#fecaca}
.badge--blue{background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe}
.badge--grey{background:#f8fafc;color:#475569;border-color:#e2e8f0}
.badge--violet{background:#f5f3ff;color:#6d28d9;border-color:#ddd6fe}

.btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;font-size:13px;font-weight:500;border:1px solid transparent;transition:background .12s,border-color .12s,opacity .12s;cursor:pointer;white-space:nowrap;font-family:inherit}
.btn--primary{background:var(--accent);color:#fff}.btn--primary:hover{background:var(--accent-h)}
.btn--outline{background:transparent;border-color:var(--border);color:var(--text2)}.btn--outline:hover{background:#f8fafc}
.btn--danger{background:var(--err-bg);color:var(--err);border-color:var(--err-bd)}.btn--danger:hover{background:#fee2e2}
.btn--sm{padding:4px 10px;font-size:12px}
.btn--icon{padding:6px}

.fg{display:flex;flex-direction:column;gap:5px}
.fg label{font-size:13px;font-weight:500;color:var(--text)}
.fg .req{color:var(--err);margin-left:2px}
.fg-hint{font-size:11px;color:var(--muted)}
.form-row{display:grid;gap:16px}.form-row.cols-2{grid-template-columns:1fr 1fr}.form-row.cols-3{grid-template-columns:1fr 1fr 1fr}
.fc{height:38px;background:#f9fafb;border:1px solid var(--border);border-radius:8px;padding:0 11px;font-family:inherit;font-size:13px;color:var(--text);outline:none;transition:border-color .12s,box-shadow .12s;width:100%}
.fc:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(37,99,235,.1);background:#fff}
.fc::placeholder{color:#9ca3af}
textarea.fc{height:auto;padding:9px 11px;resize:vertical}
select.fc{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 9px center;padding-right:30px}

.empty-state{display:flex;flex-direction:column;align-items:center;padding:56px 24px;text-align:center;gap:9px;color:var(--muted)}
.empty-state svg{opacity:.3;margin-bottom:3px}
.empty-state h3{font-size:14px;font-weight:600;color:var(--text)}
.empty-state p{font-size:13px;max-width:300px;line-height:1.65}

.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:400;display:none;align-items:center;justify-content:center;padding:20px}
.modal-overlay.open{display:flex}
.modal{background:var(--surface);border-radius:14px;width:100%;max-width:480px;box-shadow:0 12px 40px rgba(0,0,0,.15);overflow:hidden}
.modal-hd{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.modal-title{font-size:15px;font-weight:600}
.modal-close{background:none;border:none;font-size:20px;color:var(--muted);cursor:pointer;padding:0 2px;transition:color .12s}.modal-close:hover{color:var(--text)}
.modal-body{padding:20px}
.modal-footer{padding:14px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:flex-end;gap:8px}

.filter-bar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:14px 18px;border-bottom:1px solid var(--border);background:#fafbfc}
.filter-bar .fc{height:34px;width:auto}
.search-fc{width:220px !important}

@media(max-width:768px){
  .sidebar{transform:translateX(-100%);width:var(--sb-w) !important}
  .main{margin-left:0 !important}
  .shell.mob-open .sidebar{transform:translateX(0)}
  .shell.mob-open .sb-overlay{display:block}
  .sb-collapse-btn{display:none}
  .hd-uname,.hd-urole,.hd-chevron{display:none}
  .form-row.cols-2,.form-row.cols-3{grid-template-columns:1fr}
}

/* ── Compatibility aliases for page files ── */
.form-control{height:38px;background:#f9fafb;border:1px solid var(--border);border-radius:8px;padding:0 11px;font-family:inherit;font-size:13px;color:var(--text);outline:none;transition:border-color .12s,box-shadow .12s;width:100%}
.form-control:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(37,99,235,.1);background:#fff}
.form-control::placeholder{color:#9ca3af}
textarea.form-control{height:auto;padding:9px 11px;resize:vertical}
select.form-control{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 9px center;padding-right:30px}
input[type="file"].form-control{height:auto;padding:6px 11px}
input[type="date"].form-control{height:38px}
.form-grid{display:flex;flex-direction:column;gap:14px}
.pg-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:22px}
.pg-title{font-size:19px;font-weight:700}
.pg-subtitle{font-size:13px;color:var(--muted);margin-top:2px}
.card-header{padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.stat-icon{width:38px;height:38px;border-radius:9px;display:grid;place-items:center;margin-bottom:12px}
.stat-icon--blue{background:#eff6ff;color:#1d4ed8}.stat-icon--green{background:#f0fdf4;color:#15803d}
.stat-icon--amber{background:#fffbeb;color:#b45309}.stat-icon--violet{background:#f5f3ff;color:#6d28d9}
.stat-label{font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px}
.stat-value{font-size:24px;font-weight:700}
.stat-note{font-size:12px;color:var(--muted);margin-top:3px}
.modal-header{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.modal-close{background:none;border:none;font-size:20px;color:var(--muted);cursor:pointer;padding:0 2px;transition:color .12s}.modal-close:hover{color:var(--text)}
.badge--gray{background:#f8fafc;color:#475569;border-color:#e2e8f0}
.filter-bar .form-control{height:34px}
</style>
</head>
<body>

<?php if ($flashMsg !== ''): ?>
<div class="toast-stack" id="toastStack">
  <div class="toast toast--<?= htmlspecialchars(in_array($flashType,['ok','warn','err'])?$flashType:'ok') ?>">
    <span style="flex-shrink:0;margin-top:1px">
      <?php if ($flashType==='ok'): ?>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
      <?php elseif ($flashType==='warn'): ?>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      <?php else: ?>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <?php endif; ?>
    </span>
    <span><?= htmlspecialchars($flashMsg) ?></span>
    <button class="toast-close" onclick="this.closest('.toast').remove()">×</button>
  </div>
</div>
<?php endif; ?>

<div class="shell" id="shell">
  <!-- SIDEBAR -->
  <aside class="sidebar" id="sidebar">
    <div class="sb-logo">
      <img src="../assets/logo.png" alt="Sinelec" class="sb-logo-img">
      <span class="sb-logo-badge">Admin</span>
    </div>
    <nav class="sb-nav">
      <?php foreach ($menu as $entry): ?>
        <?php if (isset($entry['key'])): ?>
          <a href="<?= $entry['href'] ?>" class="sb-link<?= sbActive($entry['key'],$currentPage) ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85"><?= $entry['icon'] ?></svg>
            <span class="sb-link-label"><?= htmlspecialchars($entry['label']) ?></span>
          </a>
        <?php else: ?>
          <div class="sb-group">
            <div class="sb-group-label"><?= htmlspecialchars($entry['group']) ?></div>
            <?php foreach ($entry['items'] as $item): ?>
              <a href="<?= $item['href'] ?>" class="sb-link<?= sbActive($item['key'],$currentPage) ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85"><?= $item['icon'] ?></svg>
                <span class="sb-link-label"><?= htmlspecialchars($item['label']) ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>
    <div class="sb-bottom">
      <button class="sb-collapse-btn" id="sbColBtn">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" id="sbColIco"><path d="M11 19l-7-7 7-7"/><path d="M21 12H4"/></svg>
        <span class="sb-col-label">Collapse</span>
      </button>
    </div>
  </aside>
  <div class="sb-overlay" id="sbOverlay" onclick="closeMob()"></div>

  <!-- MAIN COLUMN -->
  <div class="main">
    <!-- HEADER -->
    <header class="hd">
      <button class="hd-toggle" id="hdToggle"><span></span><span></span><span></span></button>
      <div class="hd-crumb">
        <a href="welcome">Dashboard</a>
        <?php if ($currentPage !== 'dashboard'): ?>
          <span class="hd-crumb-sep">›</span>
          <span class="hd-crumb-cur"><?= htmlspecialchars($pageTitle) ?></span>
        <?php endif; ?>
      </div>
      <div class="hd-right">
        <div class="hd-user-wrap">
          <button class="hd-user-btn" id="userBtn" aria-haspopup="true" aria-expanded="false">
            <div class="hd-avatar"><?= htmlspecialchars($initials) ?></div>
            <div>
              <div class="hd-uname"><?= htmlspecialchars($firstName) ?></div>
              <div class="hd-urole">Administrator</div>
            </div>
            <svg class="hd-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
          <div class="hd-drop" id="userDrop">
            <div class="hd-drop-head">
              <div class="hd-drop-name"><?= htmlspecialchars($adminName) ?></div>
              <div class="hd-drop-email"><?= htmlspecialchars($adminEmail) ?></div>
            </div>
            <div class="hd-drop-items">
              <a href="change-password" class="hd-drop-item">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V8a5 5 0 0110 0v3"/></svg>
                Change Password
              </a>
              <div class="hd-drop-div"></div>
              <a href="service?urlstring=<?= EncryptURL('action=AdminLogout') ?>" class="hd-drop-item danger">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Sign Out
              </a>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- CONTENT -->
    <main class="page-content"><?= $pageMainContent ?></main>

    <!-- FOOTER -->
    <footer class="site-footer">
      <span>&copy; <?= date('Y') ?> Sinelec Technologies Pvt. Ltd.</span>
      <span style="color:#d1d5db">Admin Panel v1.0</span>
    </footer>
  </div>
</div>

<script>
(function(){
  var shell=document.getElementById('shell');
  var KEY='sn_sb_col';

  // restore collapse
  if(localStorage.getItem(KEY)==='1') shell.classList.add('sb-col');

  function toggleCollapse(){
    var c=shell.classList.toggle('sb-col');
    localStorage.setItem(KEY,c?'1':'0');
  }

  document.getElementById('hdToggle').addEventListener('click',function(){
    if(window.innerWidth<=768){ shell.classList.toggle('mob-open'); }
    else { toggleCollapse(); }
  });
  var sbBtn=document.getElementById('sbColBtn');
  if(sbBtn) sbBtn.addEventListener('click',toggleCollapse);

  window.closeMob=function(){ shell.classList.remove('mob-open'); };

  // user dropdown
  var uBtn=document.getElementById('userBtn');
  var uDrop=document.getElementById('userDrop');
  uBtn.addEventListener('click',function(e){
    e.stopPropagation();
    var o=uDrop.classList.toggle('open');
    uBtn.setAttribute('aria-expanded',o);
  });
  document.addEventListener('click',function(){ uDrop.classList.remove('open'); uBtn.setAttribute('aria-expanded','false'); });
  uDrop.addEventListener('click',function(e){ e.stopPropagation(); });

  // auto-dismiss toast
  var ts=document.getElementById('toastStack');
  if(ts) setTimeout(function(){ ts.style.transition='opacity .35s'; ts.style.opacity='0'; setTimeout(function(){ ts.remove(); },360); },4200);

  // generic modal open/close
  window.openModal=function(id){ document.getElementById(id).classList.add('open'); };
  window.closeModal=function(id){ document.getElementById(id).classList.remove('open'); };
  document.querySelectorAll('.modal-overlay').forEach(function(o){
    o.addEventListener('click',function(e){ if(e.target===o) o.classList.remove('open'); });
  });
})();
</script>
</body>
</html>
