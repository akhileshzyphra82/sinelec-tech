<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

function jsonOut(array $data): void
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    exit();
}

/* ── Auth ────────────────────────────────────────────────────── */
$userId = (int)($_SESSION['sinelec_user']['USER_ID'] ?? 0);
if ($userId <= 0) {
    jsonOut(['ok' => false, 'msg' => 'Not authenticated.']);
}

/* ── DB connect ──────────────────────────────────────────────── */
require_once __DIR__ . '/../../config/db_helper.php';

try {
    $db = new MySQLDB();
} catch (Throwable $e) {
    error_log('address.php DB connect: ' . $e->getMessage());
    jsonOut(['ok' => false, 'msg' => 'Database connection failed.']);
}

$action = trim((string)($_POST['action'] ?? $_GET['action'] ?? ''));

/* ── Helpers ─────────────────────────────────────────────────── */
function addrRowToJs(object $row): array
{
    return [
        'id'                  => (int)($row->USER_ADDRESS_ID      ?? 0),
        'label'               => (string)($row->LABEL              ?? 'Home'),
        'company_name'        => (string)($row->COMPANY_NAME       ?? ''),
        'user_name'           => (string)($row->USER_NAME          ?? ''),
        'delivery_phone_no'   => (string)($row->DELIVERY_PHONE_NO  ?? ''),
        'mobile_country_code' => (int)($row->MOBILE_COUNTRY_CODE   ?? 0),
        'address_line_one'    => (string)($row->ADDRESS_LINE_ONE   ?? ''),
        'address_line_two'    => (string)($row->ADDRESS_LINE_TWO   ?? ''),
        'landmark'            => (string)($row->LANDMARK           ?? ''),
        'city'                => (string)($row->CITY               ?? ''),
        'state'               => (string)($row->STATE              ?? ''),
        'zip'                 => (string)($row->ZIP                ?? ''),
        'country'             => (string)($row->COUNTRY            ?? ''),
        'country_id'          => (float)($row->COUNTRY_ID          ?? 0),
        'address'             => (string)($row->ADDRESS_NOTES       ?? ''),
        'recipient_name'      => (string)($row->RECIPIENT_NAME     ?? ''),
        'recipient_email'     => (string)($row->RECIPIENT_EMAIL    ?? ''),
        'recipient_contact'   => (string)($row->RECIPIENT_CONTACT  ?? ''),
    ];
}

function getList(MySQLDB $db, float $uid): array
{
    $rows = $db->select(
        "SELECT user_address_id, label, user_name, company_name,
                address AS address_notes,
                address_line_one, address_line_two, landmark,
                city, state, zip, country, country_id,
                delivery_phone_no, mobile_country_code,
                recipient_name, recipient_email, recipient_contact
         FROM tbl_user_address
         WHERE user_id = ?
         ORDER BY user_address_id DESC",
        [$uid]
    );
    return array_map('addrRowToJs', $rows);
}

function p(string $k): string  { return trim((string)($_POST[$k] ?? '')); }
function pint(string $k): int  { return (int)($_POST[$k] ?? 0); }
function pflt(string $k): float { return (float)($_POST[$k] ?? 0); }

/* ── Router ──────────────────────────────────────────────────── */
try {

    switch ($action) {

        /* ── LIST ── */
        case 'get_list':
            jsonOut(['ok' => true, 'list' => getList($db, (float)$userId)]);
            break;

        /* ── SAVE ── */
        case 'save':
            $line1 = p('address_line_one');
            $city  = p('city');
            $zip   = p('zip');
            if ($line1 === '' || $city === '' || $zip === '') {
                jsonOut(['ok' => false, 'msg' => 'Address Line 1, City and Postal Code are required.']);
            }
            $label = in_array(p('label'), ['Home','Office','Other']) ? p('label') : 'Other';
            $db->insert(
                "INSERT INTO tbl_user_address
                 (user_id, label, user_name, company_name, delivery_phone_no, mobile_country_code,
                  address_line_one, address_line_two, landmark,
                  city, state, zip, country, country_id, address,
                  recipient_name, recipient_email, recipient_contact)
                 VALUES (?,?,?,?,?,?, ?,?,?, ?,?,?,?,?,?, ?,?,?)",
                [
                    (float)$userId, $label, p('user_name'), p('company_name'),
                    p('delivery_phone_no'), pint('mobile_country_code'),
                    $line1, p('address_line_two'), p('landmark'),
                    $city, p('state'), $zip, p('country'), pflt('country_id'), p('address'),
                    p('recipient_name'), p('recipient_email'), p('recipient_contact'),
                ]
            );
            jsonOut(['ok' => true, 'list' => getList($db, (float)$userId)]);
            break;

        /* ── UPDATE ── */
        case 'update':
            $addrId = pint('user_address_id');
            if ($addrId <= 0) jsonOut(['ok' => false, 'msg' => 'Invalid address ID.']);
            $line1 = p('address_line_one');
            $city  = p('city');
            $zip   = p('zip');
            if ($line1 === '' || $city === '' || $zip === '') {
                jsonOut(['ok' => false, 'msg' => 'Address Line 1, City and Postal Code are required.']);
            }
            $label = in_array(p('label'), ['Home','Office','Other']) ? p('label') : 'Other';
            $db->update(
                "UPDATE tbl_user_address SET
                   label=?, user_name=?, company_name=?,
                   delivery_phone_no=?, mobile_country_code=?,
                   address_line_one=?, address_line_two=?, landmark=?,
                   city=?, state=?, zip=?, country=?, country_id=?, address=?,
                   recipient_name=?, recipient_email=?, recipient_contact=?
                 WHERE user_address_id=? AND user_id=?",
                [
                    $label, p('user_name'), p('company_name'),
                    p('delivery_phone_no'), pint('mobile_country_code'),
                    $line1, p('address_line_two'), p('landmark'),
                    $city, p('state'), $zip, p('country'), pflt('country_id'), p('address'),
                    p('recipient_name'), p('recipient_email'), p('recipient_contact'),
                    (float)$addrId, (float)$userId,
                ]
            );
            jsonOut(['ok' => true, 'list' => getList($db, (float)$userId)]);
            break;

        /* ── DELETE ── */
        case 'delete':
            $addrId = pint('user_address_id');
            if ($addrId <= 0) jsonOut(['ok' => false, 'msg' => 'Invalid address ID.']);
            $db->update(
                "DELETE FROM tbl_user_address WHERE user_address_id=? AND user_id=?",
                [(float)$addrId, (float)$userId]
            );
            jsonOut(['ok' => true, 'list' => getList($db, (float)$userId)]);
            break;

        default:
            jsonOut(['ok' => false, 'msg' => 'Unknown action.']);
    }

} catch (Throwable $e) {
    error_log('address.php exception [' . $action . ']: ' . $e->getMessage());
    jsonOut(['ok' => false, 'msg' => 'Server error: ' . $e->getMessage()]);
}
