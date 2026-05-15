<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

require_once __DIR__ . '/../../config/db_helper.php';
require_once __DIR__ . '/../../common/functions.php';

$action = trim((string)($_POST['action'] ?? $_GET['action'] ?? ''));

function jsonOut(array $data): void
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    exit();
}

/* ── 1. Check postal code ──────────────────────────────────────── */
if ($action === 'check_postal') {
    $postalCode = trim((string)($_POST['postal_code'] ?? ''));

    if ($postalCode === '') {
        jsonOut(['ok' => false, 'message' => 'Please enter a postal code.']);
    }

    if (!preg_match('/^[A-Za-z0-9\s\-]{2,12}$/', $postalCode)) {
        jsonOut(['ok' => false, 'message' => 'Invalid postal code format.']);
    }

    try {
        $db = new MySQLDB();
        $rows = $db->select(
            "SELECT COUNT(*) AS cnt FROM tbl_user_address WHERE zip = ? LIMIT 1",
            [$postalCode]
        );

        $count = (int)($rows[0]->CNT ?? 0);

        if ($count > 0) {
            $_SESSION['sinelec_delivery_postal']  = $postalCode;
            $_SESSION['sinelec_delivery_display'] = $postalCode;
            jsonOut([
                'ok'        => true,
                'available' => true,
                'message'   => 'Great! Delivery is available at postal code ' . htmlspecialchars($postalCode) . '.',
                'display'   => $postalCode,
            ]);
        } else {
            jsonOut([
                'ok'        => true,
                'available' => false,
                'message'   => 'Sorry, delivery is not available in this location.',
            ]);
        }
    } catch (Exception $e) {
        error_log('deliver-to check_postal: ' . $e->getMessage());
        jsonOut(['ok' => false, 'message' => 'Server error. Please try again.']);
    }
}

/* ── 2. Get user's saved addresses from DB ─────────────────────── */
if ($action === 'get_addresses') {
    $user   = $_SESSION['sinelec_user'] ?? [];
    $userId = (int)($user['USER_ID'] ?? 0);

    if ($userId <= 0) {
        jsonOut(['ok' => false, 'auth' => false, 'message' => 'Not logged in.']);
    }

    try {
        $db   = new MySQLDB();
        $rows = $db->select(
            "SELECT user_address_id, user_name, address, city, state, zip, country, delivery_phone_no
             FROM tbl_user_address
             WHERE user_id = ?
             ORDER BY user_address_id ASC",
            [(float)$userId]
        );

        $addresses = array_map(function ($row) {
            $parts = array_filter([
                (string)($row->ADDRESS ?? ''),
                (string)($row->CITY ?? ''),
                (string)($row->STATE ?? ''),
                (string)($row->ZIP ?? ''),
                (string)($row->COUNTRY ?? ''),
            ]);
            return [
                'id'    => (int)($row->USER_ADDRESS_ID ?? 0),
                'name'  => (string)($row->USER_NAME ?? ''),
                'phone' => (string)($row->DELIVERY_PHONE_NO ?? ''),
                'line'  => implode(', ', $parts),
                'zip'   => (string)($row->ZIP ?? ''),
            ];
        }, $rows);

        jsonOut(['ok' => true, 'addresses' => $addresses]);

    } catch (Exception $e) {
        error_log('deliver-to get_addresses: ' . $e->getMessage());
        jsonOut(['ok' => false, 'message' => 'Server error. Please try again.']);
    }
}

/* ── 3. Save geo-detected location to session ──────────────────── */
if ($action === 'set_location') {
    $display = trim((string)($_POST['display'] ?? ''));
    $postal  = trim((string)($_POST['postal_code'] ?? ''));
    $lat     = (float)($_POST['lat'] ?? 0);
    $lng     = (float)($_POST['lng'] ?? 0);

    if ($display === '' && $postal === '' && ($lat === 0.0 || $lng === 0.0)) {
        jsonOut(['ok' => false, 'message' => 'No location data provided.']);
    }

    $displayLabel = $display ?: ($postal ?: 'Current Location');
    $_SESSION['sinelec_delivery_display'] = $displayLabel;

    if ($postal !== '') {
        $_SESSION['sinelec_delivery_postal'] = $postal;
    }
    if ($lat !== 0.0 && $lng !== 0.0) {
        $_SESSION['sinelec_delivery_lat'] = $lat;
        $_SESSION['sinelec_delivery_lng'] = $lng;
    }

    jsonOut(['ok' => true, 'display' => $displayLabel]);
}

/* ── 4. Select a saved address ─────────────────────────────────── */
if ($action === 'set_address') {
    $user   = $_SESSION['sinelec_user'] ?? [];
    $userId = (int)($user['USER_ID'] ?? 0);

    if ($userId <= 0) {
        jsonOut(['ok' => false, 'auth' => false, 'message' => 'Not logged in.']);
    }

    $addressId = (int)($_POST['address_id'] ?? 0);
    if ($addressId <= 0) {
        jsonOut(['ok' => false, 'message' => 'Invalid address.']);
    }

    try {
        $db   = new MySQLDB();
        $rows = $db->select(
            "SELECT address, city, state, zip, country
             FROM tbl_user_address
             WHERE user_address_id = ? AND user_id = ?
             LIMIT 1",
            [(float)$addressId, (float)$userId]
        );

        if (empty($rows)) {
            jsonOut(['ok' => false, 'message' => 'Address not found.']);
        }

        $row  = $rows[0];
        $zip  = (string)($row->ZIP ?? '');
        $city = (string)($row->CITY ?? '');
        $display = trim(implode(', ', array_filter([$city, $zip])));
        if ($display === '') {
            $display = $zip ?: 'Selected Address';
        }

        $_SESSION['sinelec_delivery_display']    = $display;
        $_SESSION['sinelec_delivery_postal']     = $zip;
        $_SESSION['sinelec_delivery_address_id'] = $addressId;

        jsonOut(['ok' => true, 'display' => $display]);

    } catch (Exception $e) {
        error_log('deliver-to set_address: ' . $e->getMessage());
        jsonOut(['ok' => false, 'message' => 'Server error. Please try again.']);
    }
}

jsonOut(['ok' => false, 'message' => 'Unknown action.']);
