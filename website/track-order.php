<?php
$order = trim((string)($_GET['order'] ?? ''));
$target = 'order-details';
if ($order !== '') {
    $target .= '?order=' . urlencode($order);
}
header('location:' . $target);
exit();
