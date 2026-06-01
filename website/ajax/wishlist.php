<?php
/**
 * Sinelec Tech — Wishlist AJAX Endpoint
 * GET  ?action=get            → { ok, ids: [int,...] }
 * POST ?action=toggle         → { ok, state: 'added'|'removed', id: int }
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../../common/functions.php';
require_once __DIR__ . '/../../website/account-helpers.php';
require_once __DIR__ . '/../../controller/website_controller.php';

function jsonOut(array $d): void { echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG); exit; }

$user   = sinelec_get_signed_in_user();
$userId = (int)($user['USER_ID'] ?? 0);

if ($userId <= 0) {
    jsonOut(['ok' => false, 'msg' => 'Not authenticated']);
}

$ctrl   = new WebsiteController();
$action = trim($_GET['action'] ?? $_POST['action'] ?? '');

/* ── GET wishlist ids ──────────────────────────────────── */
if ($action === 'get') {
    jsonOut(['ok' => true, 'ids' => $ctrl->getWishlistIds($userId)]);
}

/* ── TOGGLE (add / remove) ─────────────────────────────── */
if ($action === 'toggle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int)($_POST['product_id'] ?? 0);
    if ($productId <= 0) jsonOut(['ok' => false, 'msg' => 'Invalid product']);
    $state = $ctrl->toggleWishlist($userId, $productId);
    if ($state === 'error') jsonOut(['ok' => false, 'msg' => 'DB error']);
    jsonOut(['ok' => true, 'state' => $state, 'id' => $productId]);
}

jsonOut(['ok' => false, 'msg' => 'Unknown action']);
