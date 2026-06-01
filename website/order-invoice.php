<?php
/**
 * website/order-invoice.php — Redirect shim
 *
 * Resolves an order number to its user_order_id (with ownership check)
 * and forwards the customer to the shared admin/order-invoice page.
 *
 * URL: website/order-invoice?order=202613
 */
require_once '../common/functions.php';
require_once __DIR__ . '/account-helpers.php';
require_once '../controller/website_controller.php';

$user   = sinelec_get_signed_in_user();
$userId = (int)($user['USER_ID'] ?? 0);
if ($userId <= 0) { header('location:index'); exit; }

$orderNumber = trim($_GET['order'] ?? '');
if ($orderNumber === '') { header('location:my-orders'); exit; }

$ctrl  = new WebsiteController();
$order = $ctrl->getCustomerOrderByNumber($orderNumber, $userId);
if (!$order) { header('location:my-orders'); exit; }

$oid = (int)(float)($order->USER_ORDER_ID ?? 0);
if ($oid <= 0) { header('location:my-orders'); exit; }

/* Forward to the admin invoice page — auth handled there via customer session */
header('location:../admin/order-invoice?id=' . $oid);
exit;
