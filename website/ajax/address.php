<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

require_once __DIR__ . '/../../controller/website_controller.php';

function jsonOut(array $data): void
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    exit();
}

/* ── Auth ── */
$userId = (int)($_SESSION['sinelec_user']['USER_ID'] ?? 0);
if ($userId <= 0) {
    jsonOut(['ok' => false, 'msg' => 'Not authenticated.']);
}

/* ── Helper: map DB row to JS-friendly array ── */
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

/* ── POST field helpers ── */
function p(string $k): string { return trim((string)($_POST[$k] ?? '')); }
function postInt(string $k): int { return (int)($_POST[$k] ?? 0); }
function postFlt(string $k): float { return (float)($_POST[$k] ?? 0); }

/* ── Build address data array from POST ── */
function postAddr(): array
{
    return [
        'label'               => p('label'),
        'company_name'        => p('company_name'),
        'user_name'           => p('user_name'),
        'delivery_phone_no'   => p('delivery_phone_no'),
        'mobile_country_code' => postInt('mobile_country_code'),
        'address_line_one'    => p('address_line_one'),
        'address_line_two'    => p('address_line_two'),
        'landmark'            => p('landmark'),
        'city'                => p('city'),
        'state'               => p('state'),
        'zip'                 => p('zip'),
        'country'             => p('country'),
        'country_id'          => postFlt('country_id'),
        'address'             => p('address'),
        'recipient_name'      => p('recipient_name'),
        'recipient_email'     => p('recipient_email'),
        'recipient_contact'   => p('recipient_contact'),
    ];
}

function validateAddr(array $d): string
{
    if (empty($d['address_line_one'])) return 'Address Line 1 is required.';
    if (empty($d['city']))             return 'City is required.';
    if (empty($d['zip']))              return 'Postal Code is required.';
    return '';
}

/* ── Router ── */
try {
    $ctrl   = new WebsiteController();
    $action = trim((string)($_POST['action'] ?? $_GET['action'] ?? ''));

    switch ($action) {

        case 'get_list':
            $rows = $ctrl->getUserAddresses($userId);
            jsonOut(['ok' => true, 'list' => array_map('addrRowToJs', $rows)]);
            break;

        case 'save':
            $d  = postAddr();
            $er = validateAddr($d);
            if ($er) jsonOut(['ok' => false, 'msg' => $er]);
            $ctrl->saveDeliveryAddress($d, $userId);
            jsonOut(['ok' => true]);
            break;

        case 'update':
            $addrId = postInt('user_address_id');
            if ($addrId <= 0) jsonOut(['ok' => false, 'msg' => 'Invalid address ID.']);
            $d  = postAddr();
            $er = validateAddr($d);
            if ($er) jsonOut(['ok' => false, 'msg' => $er]);
            $ctrl->updateDeliveryAddress($addrId, $d, $userId);
            jsonOut(['ok' => true]);
            break;

        case 'delete':
            $addrId = postInt('user_address_id');
            if ($addrId <= 0) jsonOut(['ok' => false, 'msg' => 'Invalid address ID.']);
            $ctrl->deleteDeliveryAddress($addrId, $userId);
            jsonOut(['ok' => true]);
            break;

        default:
            jsonOut(['ok' => false, 'msg' => 'Unknown action.']);
    }

} catch (Throwable $e) {
    error_log('address.php [' . ($action ?? '') . ']: ' . $e->getMessage());
    jsonOut(['ok' => false, 'msg' => $e->getMessage()]);
}
