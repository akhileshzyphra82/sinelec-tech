<?php
/**
 * Sinelec Tech — Catalog AJAX Endpoint
 * GET ?action=init          → categories (with counts) + manufacturers
 * GET ?action=products&...  → paginated products + total
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../../common/functions.php';
require_once __DIR__ . '/../../controller/website_controller.php';

$ctrl   = new WebsiteController();
$action = trim($_GET['action'] ?? '');

function jsonOut(array $d): void {
    echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    exit;
}

/* ── INIT: categories + manufacturers ───────────────────────── */
if ($action === 'init') {
    /* Categories with product counts */
    $catRows = $ctrl->getCatalogCategories();
    $categories = [];
    foreach ($catRows as $r) {
        $categories[] = [
            'id'         => (int)(float)($r->PRODUCT_CATEGORY_ID    ?? 0),
            'name'       => (string)($r->PRODUCT_CATEGORY_NAME      ?? ''),
            'parent_id'  => (int)(float)($r->PARENT_CATEGORY_ID     ?? 0),
            'parent'     => (string)($r->PARENT_NAME                ?? ''),
            'count'      => (int)(float)($r->PRODUCT_COUNT          ?? 0),
        ];
    }

    /* Manufacturers list (no count) */
    $mfrRows = $ctrl->getCatalogManufacturers();
    $manufacturers = [];
    foreach ($mfrRows as $r) {
        $manufacturers[] = [
            'id'     => (int)($r->MANUFACTURER_ID        ?? 0),
            'name'   => (string)($r->NAME                ?? ''),
            'catIds' => trim((string)($r->PRODUCT_CATEGORY_IDS ?? '')),
        ];
    }

    jsonOut(['ok' => true, 'categories' => $categories, 'manufacturers' => $manufacturers]);
}

/* ── PRODUCTS: filtered + paginated ─────────────────────────── */
if ($action === 'products') {
    $f = [
        'q'          => trim($_GET['q']          ?? ''),
        'cat_id'     => (int)($_GET['cat_id']    ?? 0),
        'cat_ids'    => trim($_GET['cat_ids']    ?? ''),   /* comma-separated category IDs from mfr filter */
        'mfr'        => trim($_GET['mfr']        ?? ''),
        'min_price'  => (float)($_GET['min_price'] ?? 0),
        'max_price'  => (float)($_GET['max_price'] ?? 0),
        'min_rating' => (float)($_GET['min_rating'] ?? 0),
        'in_stock'   => !empty($_GET['in_stock']),
        'is_new'     => !empty($_GET['is_new']),
        'sort'       => trim($_GET['sort']       ?? 'featured'),
    ];
    $page    = max(1, (int)($_GET['page']     ?? 1));
    $perPage = min(48, max(8, (int)($_GET['per_page'] ?? 16)));

    $rows    = $ctrl->getCatalogProducts($f, $page, $perPage);
    $total   = $ctrl->getCatalogCount($f);
    $pages   = max(1, (int)ceil($total / $perPage));

    $BASE_URL = rtrim((string)sinelec_env('PUBLIC_BASE_URL', ''), '/');
    $products = [];
    foreach ($rows as $r) {
        $amt      = (float)($r->PRODUCT_AMT       ?? 0);
        $offer    = (float)($r->OFFER_PERCENTAGE  ?? 0);
        $price    = $amt;                                                          /* product_amt IS the final price */
        $origAmt  = $offer > 0 ? round($amt * (1 + $offer / 100), 2) : 0;        /* crossed-out = amt + offer% */
        $stock    = (int)(float)($r->TOTAL_REMAINING ?? 0);
        $label    = strtolower(trim((string)($r->LABEL ?? '')));
        $isNew    = ($label === 'new');
        $isFeat   = in_array($label, ['featured', 'bestseller', 'hot']);
        $thumbRaw = (string)($r->THUMB_PATH ?? '');
        $image    = $thumbRaw !== '' ? $BASE_URL . '/' . ltrim($thumbRaw, '/') : '';

        $products[] = [
            'id'            => (int)(float)($r->PRODUCT_ID               ?? 0),
            'sku'           => (string)($r->PRODUCT_CODE                  ?? ''),
            'name'          => (string)($r->PRODUCT_NAME                  ?? ''),
            'category'      => (string)($r->PRODUCT_CATEGORY_ID           ?? ''),
            'categoryName'  => (string)($r->PRODUCT_CATEGORY_NAME         ?? ''),
            'image'         => $image,
            'price'         => $price,
            'originalPrice' => $origAmt > 0 ? $origAmt : 0,
            'stock'         => $stock,
            'rating'        => (float)($r->RATING     ?? 0),
            'reviews'       => (int)(float)($r->TOTAL_SOLD ?? 0),
            'label'         => (string)($r->LABEL     ?? ''),
            'badge'         => $label ?: '',
            'isNew'         => $isNew,
            'isFeatured'    => $isFeat,
            'description'   => (string)($r->PRODUCT_DESCRIPTION ?? ''),
        ];
    }

    jsonOut([
        'ok'       => true,
        'products' => $products,
        'total'    => $total,
        'page'     => $page,
        'pages'    => $pages,
        'perPage'  => $perPage,
    ]);
}

jsonOut(['ok' => false, 'msg' => 'Unknown action']);
