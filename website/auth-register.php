<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../controller/website_controller.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => false,
        'message' => 'Method not allowed.'
    ]);
    exit;
}

$fullName = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$email = isset($_POST['email']) ? strtolower(trim($_POST['email'])) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$password = isset($_POST['password']) ? (string)$_POST['password'] : '';
$confirmPassword = isset($_POST['confirm_password']) ? (string)$_POST['confirm_password'] : '';

if ($fullName === '' || $email === '' || $phone === '' || $password === '' || $confirmPassword === '') {
    echo json_encode([
        'status' => false,
        'message' => 'Please fill all required fields.'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'status' => false,
        'message' => 'Please enter a valid email address.'
    ]);
    exit;
}

if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $password)) {
    echo json_encode([
        'status' => false,
        'message' => 'Password must be at least 8 characters and include letters, numbers, and special characters.'
    ]);
    exit;
}

if ($password !== $confirmPassword) {
    echo json_encode([
        'status' => false,
        'message' => 'Password and Confirm Password do not match.'
    ]);
    exit;
}

$mobileDigits = preg_replace('/[^0-9]/', '', $phone);
if ($mobileDigits === '' || strlen($mobileDigits) < 6) {
    echo json_encode([
        'status' => false,
        'message' => 'Please enter a valid phone number.'
    ]);
    exit;
}

try {
    $objWebsiteController = new WebsiteController();

    if ($objWebsiteController->isEmailRegistered($email)) {
        echo json_encode([
            'status' => false,
            'message' => 'This email is already registered. Please sign in.'
        ]);
        exit;
    }

    $arrUserData = [
        'user_type_id' => 2,
        'name' => $fullName,
        'communication_mobile_num_isd' => 0,
        'communication_mobile_num' => $mobileDigits,
        'communication_email_id' => $email,
        'erp_password' => $password
    ];

    $intUserId = $objWebsiteController->InsertUserFromWebsite($arrUserData);

    if (is_array($intUserId) && isset($intUserId['status']) && $intUserId['status'] === false) {
        echo json_encode($intUserId);
        exit;
    }

    if ((int)$intUserId > 0) {
        echo json_encode([
            'status' => true,
            'message' => 'Account created successfully.',
            'user_id' => (int)$intUserId
        ]);
        exit;
    }

    echo json_encode([
        'status' => false,
        'message' => 'Unable to create account right now. Please try again.'
    ]);
} catch (Throwable $e) {
    error_log('Auth register error: ' . $e->getMessage());
    echo json_encode([
        'status' => false,
        'message' => 'Something went wrong during registration. Please try again.'
    ]);
}
