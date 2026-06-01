<?php
/**
 * Sinelec Tech — Search Suggestions AJAX Endpoint
 * GET ?action=suggest&q=...&cat_id=...
 * GET ?action=categories
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../../common/functions.php';
require_once __DIR__ . '/../../controller/website_controller.php';

$ctrl   = new WebsiteController();
$action = trim($_GET['action'] ?? '');

function jsonOut(array $d): void { echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG); exit; }

/* ── GET categories (for dropdown reload if needed) ─────── */
if ($action === 'categories') {
    $rows = $ctrl->getSearchCategories();
    $cats = [];
    foreach ($rows as $r) {
        $cats[] = [
            'id'     => (int)(float)($r->PRODUCT_CATEGORY_ID ?? 0),
            'name'   => (string)($r->PRODUCT_CATEGORY_NAME ?? ''),
            'parent' => (int)(float)($r->PARENT_CATEGORY_ID ?? 0),
            'group'  => (string)($r->PARENT_NAME ?? ''),
        ];
    }
    jsonOut(['ok' => true, 'categories' => $cats]);
}

/* ── GET suggestions ─────────────────────────────────────── */
if ($action === 'suggest') {
    $q     = trim($_GET['q']      ?? '');
    $catId = (int)($_GET['cat_id'] ?? 0);

    if (strlen($q) < 2) jsonOut(['ok' => true, 'items' => [], 'total' => 0]);

    $rows  = $ctrl->getSearchSuggestions($q, $catId, 8);
    $total = $ctrl->getSearchCount($q, $catId);

    $BASE_URL = rtrim((string)sinelec_env('PUBLIC_BASE_URL', ''), '/');
    $items = [];
    foreach ($rows as $r) {
        $price     = (float)($r->PRODUCT_AMT       ?? 0);
        $offer     = (float)($r->OFFER_PERCENTAGE  ?? 0);
        $remaining = (int)(float)($r->TOTAL_REMAINING ?? 0);
        $salePrice = $price;                                              /* product_amt IS the final price */
        $orgPrice  = $offer > 0 ? round($price * (1 + $offer / 100), 2) : 0; /* crossed-out = amt + offer% */
        $pid       = (int)(float)($r->PRODUCT_ID ?? 0);

        /* Fetch primary image for this product */
        $imgRows   = $ctrl->getProductImages($pid);
        $thumbPath = !empty($imgRows) ? (string)($imgRows[0]->PRODUCT_IMAGE_PATH ?? '') : '';
        $image     = $thumbPath !== '' ? $BASE_URL . '/' . ltrim($thumbPath, '/') : '';

        $items[] = [
            'ID'        => $pid,
            'CODE'      => (string)($r->PRODUCT_CODE              ?? ''),
            'NAME'      => (string)($r->PRODUCT_NAME              ?? ''),
            'CATEGORY'  => (string)($r->PRODUCT_CATEGORY_NAME     ?? ''),
            'PRICE'     => $salePrice,
            'ORG_PRICE' => $orgPrice,
            'OFFER'     => $offer,
            'REMAINING' => $remaining,
            'LABEL'     => (string)($r->LABEL                     ?? ''),
            'RATING'    => (float)($r->RATING                     ?? 0),
            'IMAGE'     => $image,
        ];
    }

    jsonOut(['ok' => true, 'items' => $items, 'total' => $total, 'q' => $q]);
}

jsonOut(['ok' => false, 'msg' => 'Unknown action']);
