<?php
/**
 * AJAX endpoint: admin/ajax/shipping-vat.php
 * Called via extensionless URL:  ajax/shipping-vat?action=save
 *
 * POST  action=save
 *   Body (JSON array):
 *   [
 *     {
 *       "country_id":       1,
 *       "shipping_amt":     19.99,
 *       "standard_b2c_vat": 22,
 *       "standard_b2b_vat": null,
 *       "oss_b2c_vat":      null,
 *       "oss_b2b_vat":      null,
 *       "applied_vat":      "Standard"
 *     }, ...
 *   ]
 *
 * Returns: { "ok": true|false, "updated_count": N, "msg": "..." }
 */

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../../common/functions.php';
require_once __DIR__ . '/../../config/db_helper.php';

header('Content-Type: application/json; charset=UTF-8');

/* ── Auth ── */
if (!sinelec_can('edit')) {
    echo json_encode(['ok' => false, 'msg' => 'Permission denied.']);
    exit();
}

$action = $_GET['action'] ?? '';

/* ── save action ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save') {
    $raw     = file_get_contents('php://input');
    $payload = json_decode($raw, true);

    if (!is_array($payload) || count($payload) === 0) {
        echo json_encode(['ok' => false, 'msg' => 'No data received.']);
        exit();
    }

    $db           = new MySQLDB();
    $updatedCount = 0;
    $errors       = [];

    foreach ($payload as $row) {
        /* Validate country_id */
        $cid = isset($row['country_id']) ? (int)(float)$row['country_id'] : 0;
        if ($cid <= 0) {
            $errors[] = "Invalid country_id: " . json_encode($row['country_id'] ?? null);
            continue;
        }

        /* Sanitise numeric fields — NULL stays NULL, empty string → NULL */
        $ship = _svFloat($row['shipping_amt']  ?? null);
        $sb2c = _svFloat($row['standard_b2c_vat'] ?? null);
        $sb2b = _svFloat($row['standard_b2b_vat'] ?? null);
        $ob2c = _svFloat($row['oss_b2c_vat']   ?? null);
        $ob2b = _svFloat($row['oss_b2b_vat']   ?? null);

        /* applied_vat must be 'Standard' or 'OSS' */
        $av = (string)($row['applied_vat'] ?? 'Standard');
        if (!in_array($av, ['Standard', 'OSS'], true)) {
            $av = 'Standard';
        }

        /* Build SET clause using parameterised values */
        $shipSql = $ship !== null ? (float)$ship      : 19.99;
        $sb2cSql = $sb2c !== null ? (float)$sb2c      : 'NULL';
        $sb2bSql = $sb2b !== null ? (float)$sb2b      : 'NULL';
        $ob2cSql = $ob2c !== null ? (float)$ob2c      : 'NULL';
        $ob2bSql = $ob2b !== null ? (float)$ob2b      : 'NULL';
        /* $av is already validated to be 'Standard' or 'OSS' above — safe to inline */
        $avSql   = $av;

        $setShip = is_numeric($shipSql) ? "shipping_amt      = $shipSql"   : "shipping_amt = 19.99";
        $setSB2C = is_numeric($sb2cSql) ? "standard_b2c_vat  = $sb2cSql"  : "standard_b2c_vat = NULL";
        $setSB2B = is_numeric($sb2bSql) ? "standard_b2b_vat  = $sb2bSql"  : "standard_b2b_vat = NULL";
        $setOB2C = is_numeric($ob2cSql) ? "oss_b2c_vat       = $ob2cSql"  : "oss_b2c_vat = NULL";
        $setOB2B = is_numeric($ob2bSql) ? "oss_b2b_vat       = $ob2bSql"  : "oss_b2b_vat = NULL";

        $sql = "UPDATE tbl_country
                SET $setShip, $setSB2C, $setSB2B, $setOB2C, $setOB2B,
                    applied_vat = '$avSql'
                WHERE country_id = $cid
                LIMIT 1";

        try {
            $db->update($sql);
            /* affected_rows = 0 can mean "no values changed" — still counts as processed */
            $updatedCount++;
        } catch (\Throwable $e) {
            error_log('shipping-vat AJAX save error: ' . $e->getMessage() . " | country_id=$cid");
            $errors[] = "DB error for country_id $cid: " . $e->getMessage();
        }
    }

    $ok  = $updatedCount > 0;
    $msg = $ok
        ? "Saved $updatedCount countr" . ($updatedCount === 1 ? 'y' : 'ies') . ' successfully.'
        : 'No rows were updated.';

    if (count($errors) > 0) {
        $msg .= ' Errors: ' . implode('; ', array_slice($errors, 0, 3));
    }

    echo json_encode([
        'ok'            => $ok,
        'updated_count' => $updatedCount,
        'msg'           => $msg,
        'errors'        => $errors,
    ]);
    exit();
}

/* ── Unknown action ── */
http_response_code(400);
echo json_encode(['ok' => false, 'msg' => 'Unknown action: ' . htmlspecialchars($action)]);
exit();

/* ── Helpers ── */

/**
 * Convert a value to float or null.
 * Returns null for empty string, 'null', null, or non-numeric.
 */
function _svFloat($val): ?float
{
    if ($val === null || $val === '' || strtolower((string)$val) === 'null') {
        return null;
    }
    return is_numeric($val) ? (float)$val : null;
}
