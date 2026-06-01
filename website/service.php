<?php
ini_set('display_errors', 1);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../common/functions.php';
function redirectWithFlash(string $target, string $type, string $message, string $extra = ''): void
{
    sinelec_set_flash($type, $message);
    $location = $target;
    if ($extra !== '') {
        $location .= '?' . ltrim($extra, '?&');
    }
    header("location:{$location}");
    exit();
}

function getGoogleRedirectUri(): string
{
    $fromEnv = trim((string)sinelec_env('GOOGLE_REDIRECT_URI', ''));
    if ($fromEnv !== '') {
        return $fromEnv;
    }

    if (($_SERVER['HTTP_HOST'] ?? '') === 'localhost') {
        return "http://localhost/Client/sinelec-tech/website/service?action=googleCallback";
    }

    return "https://new.sinelec-tech.com/website/service?action=googleCallback";
}

function httpPostForm(string $url, array $payload): array
{
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($payload),
            'timeout' => 15,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return [];
    }

    $decoded = json_decode($response, true);
    return is_array($decoded) ? $decoded : [];
}

function httpGetJsonWithBearer(string $url, string $accessToken): array
{
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Authorization: Bearer " . $accessToken . "\r\n",
            'timeout' => 15,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return [];
    }

    $decoded = json_decode($response, true);
    return is_array($decoded) ? $decoded : [];
}

function startSinelecSessionForUser(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['sinelec_user'] = [
        'USER_ID' => (int)($user['user_id'] ?? 0),
        'NAME' => (string)($user['name'] ?? ''),
        'EMAIL' => (string)($user['email'] ?? ''),
        'USER_TYPE_ID' => (int)($user['user_type_id'] ?? 0),
        'COMMUNICATION_MOBILE_NUM_ISD' => (int)($user['communication_mobile_num_isd'] ?? 0),
        'COMMUNICATION_MOBILE_NUM' => (string)($user['communication_mobile_num'] ?? ''),
        'COMPANY_NAME' => (string)($user['company_name'] ?? ''),
        'DESIGNATION' => (string)($user['designation'] ?? ''),
        'IS_PWD_UPDATED' => (bool)($user['is_pwd_updated'] ?? false),
    ];
}

$paramsArray = GetQueryStringParameters();


$action = isset($paramsArray['action']) ? htmlspecialchars($paramsArray['action']) : ($_GET['action'] ?? '');

require_once __DIR__ . '/../controller/website_controller.php';
$controller = new WebsiteController();


$GOOGLE_CLIENT_ID = trim((string)sinelec_env('GOOGLE_CLIENT_ID', ''));
$GOOGLE_CLIENT_SECRET = trim((string)sinelec_env('GOOGLE_CLIENT_SECRET', ''));
$GOOGLE_REDIRECT_URI = getGoogleRedirectUri();


switch($action)
{	
    case "GoogleLogin":
        if ($GOOGLE_CLIENT_ID === '' || $GOOGLE_CLIENT_SECRET === '' || $GOOGLE_REDIRECT_URI === '') {
            redirectWithFlash('index', 'err', 'Google login is not configured correctly.');
        }

        try {
            $googleState = bin2hex(random_bytes(24));
        } catch (Exception $e) {
            error_log('Google state generation error: ' . $e->getMessage());
            redirectWithFlash('index', 'err', 'Unable to start Google login. Please try again.');
        }

        $_SESSION['sinelec_google_oauth_state'] = $googleState;

        $query = http_build_query([
            'client_id' => $GOOGLE_CLIENT_ID,
            'redirect_uri' => $GOOGLE_REDIRECT_URI,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $googleState,
            'prompt' => 'select_account',
        ]);

        header('location:https://accounts.google.com/o/oauth2/v2/auth?' . $query);
        exit();
    break;

    case "googleCallback":
    case "GoogleCallback":
        if ($GOOGLE_CLIENT_ID === '' || $GOOGLE_CLIENT_SECRET === '' || $GOOGLE_REDIRECT_URI === '') {
            redirectWithFlash('index', 'err', 'Google login is not configured correctly.');
        }

        $returnedState = trim((string)($_GET['state'] ?? ''));
        $sessionState = trim((string)($_SESSION['sinelec_google_oauth_state'] ?? ''));
        unset($_SESSION['sinelec_google_oauth_state']);

        if ($returnedState === '' || $sessionState === '' || !hash_equals($sessionState, $returnedState)) {
            redirectWithFlash('index', 'err', 'Google login validation failed. Please try again.');
        }

        if (!empty($_GET['error'])) {
            redirectWithFlash('index', 'warn', 'Google login was cancelled.');
        }

        $authCode = trim((string)($_GET['code'] ?? ''));
        if ($authCode === '') {
            redirectWithFlash('index', 'err', 'Google login failed. Missing authorization code.');
        }

        $tokenResponse = httpPostForm('https://oauth2.googleapis.com/token', [
            'code' => $authCode,
            'client_id' => $GOOGLE_CLIENT_ID,
            'client_secret' => $GOOGLE_CLIENT_SECRET,
            'redirect_uri' => $GOOGLE_REDIRECT_URI,
            'grant_type' => 'authorization_code',
        ]);

        $accessToken = trim((string)($tokenResponse['access_token'] ?? ''));
        if ($accessToken === '') {
            error_log('Google token exchange failed: ' . json_encode($tokenResponse));
            redirectWithFlash('index', 'err', 'Unable to verify Google account. Please try again.');
        }

        $googleProfile = httpGetJsonWithBearer('https://openidconnect.googleapis.com/v1/userinfo', $accessToken);

        $googleId = trim((string)($googleProfile['sub'] ?? ''));
        $email = strtolower(trim((string)($googleProfile['email'] ?? '')));
        $fullName = trim((string)($googleProfile['name'] ?? ''));
        $emailVerified = (bool)($googleProfile['email_verified'] ?? false);

        if ($googleId === '' || $email === '') {
            error_log('Google profile missing required data: ' . json_encode($googleProfile));
            redirectWithFlash('index', 'err', 'Google account details are incomplete.');
        }

        if (!$emailVerified) {
            redirectWithFlash('index', 'warn', 'Google email is not verified. Please verify and try again.');
        }

        $user = $controller->loginOrRegisterGoogleUser($googleId, $email, $fullName);
        if (!empty($user) && isset($user['user_id']) && (int)$user['user_id'] > 0) {
            startSinelecSessionForUser($user);
            redirectWithFlash('index', 'ok', 'Signed in with Google successfully.');
        }

        redirectWithFlash('index', 'err', 'Google login failed. Please try again.');
    break;

	case "Insert":

        //echo "<pre>"; print_r($_POST); echo "</pre>"; die;
		$turnstileToken = trim((string)($_POST['cf-turnstile-response'] ?? ''));
		$turnstileResult = sinelec_validate_turnstile(
            $turnstileToken,
            $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null
        );

        if (empty($turnstileResult['success'])) {
            redirectWithFlash('index', 'err', 'Captcha verification failed. Please try again.');
        }

		$name = trim($_POST['authFullName'] ?? '');
        $email = strtolower(trim($_POST['authEmail'] ?? ''));
        $phone_code = trim($_POST['phone_code'] ?? '');
        $phone = trim($_POST['authPhone'] ?? '');
        $password = (string)($_POST['authPassCreate'] ?? '');
        $confirmPassword = (string)($_POST['authPassConfirm'] ?? '');

        if ($name === '' || $email === '' || $phone_code === '' || $phone === '' || $password === '' || $confirmPassword === '')
        {
            redirectWithFlash('index', 'warn', 'Please fill all required fields.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            redirectWithFlash('index', 'warn', 'Please enter a valid email address.');
        }

        if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $password))
        {
            redirectWithFlash('index', 'warn', 'Password must be at least 8 characters and include letters numbers and special characters.');
        }

        if ($password !== $confirmPassword)
        {
            redirectWithFlash('index', 'warn', 'Passwords do not match. Please try again.');
        }

        if ($controller->isEmailRegistered($email))
        {
            redirectWithFlash('index', 'warn', 'This email is already registered. Please sign in.');
        }

        $arrUserData = array(
            "user_type_id" => 2,
            "name" => $name,
            "communication_email_id" => $email,
            "communication_mobile_num_isd" => (int)$phone_code,
            "communication_mobile_num" => preg_replace('/[^0-9]/', '', $phone),
            "erp_password" => $password
        );

        $result = $controller->InsertUserFromWebsite($arrUserData);
        if ((int)$result > 0)
        {
            redirectWithFlash('index', 'ok', 'Registration successful. Please sign in.', 'userId=' . $result);
        }

        redirectWithFlash('index', 'err', 'Registration failed. Please try again.');
	break;

    case "Login":
        $turnstileToken = trim((string)($_POST['cf-turnstile-response'] ?? ''));
        $turnstileResult = sinelec_validate_turnstile(
            $turnstileToken,
            $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null
        );

        if (empty($turnstileResult['success'])) {
            redirectWithFlash('index', 'err', 'Captcha verification failed. Please try again.');
        }

        $username = strtolower(trim($_POST['authUserId'] ?? ''));
        $password = (string)($_POST['authPassword'] ?? '');

        if ($username === '' || $password === '')
        {
            redirectWithFlash('index', 'warn', 'Please enter your email and password.');
        }

        $user = $controller->loginUser([
            'username' => $username,
            'password' => $password,
        ]);

        if (!empty($user) && isset($user['user_id']))
        {
            startSinelecSessionForUser($user);

            $allowedRedirects = ['request-a-quote', 'my-list', 'delivery-address', 'account', 'checkout'];
            $postRedirect = trim((string)($_POST['auth_redirect'] ?? ''));
            $loginTarget = in_array($postRedirect, $allowedRedirects) ? $postRedirect : 'index';
            redirectWithFlash($loginTarget, 'ok', 'Signed in successfully.');
        }

        redirectWithFlash('index', 'err', 'Invalid email or password.');
    break;

    case "ChangePassword":
        $userId = (int)($_SESSION['sinelec_user']['USER_ID'] ?? 0);
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');
        $passwordRule = '/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/';

        if ($userId <= 0) {
            redirectWithFlash('index', 'warn', 'Please sign in to continue.');
        }

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            redirectWithFlash('change-password', 'warn', 'Please fill all password fields.');
        }

        if (!preg_match($passwordRule, $newPassword)) {
            redirectWithFlash('change-password', 'warn', 'New password must be at least 8 characters and include letters numbers and special characters.');
        }

        if ($newPassword !== $confirmPassword) {
            redirectWithFlash('change-password', 'warn', 'New password and confirm password do not match.');
        }

        if ($currentPassword === $newPassword) {
            redirectWithFlash('change-password', 'warn', 'New password must be different from current password.');
        }

        $changeErrorCode = '';
        if ($controller->changeUserPassword($userId, $currentPassword, $newPassword, $changeErrorCode)) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
            session_start();
            redirectWithFlash('index', 'ok', 'Password updated successfully. Please login again.');
        }

        switch ($changeErrorCode) {
            case 'current_password_invalid':
                redirectWithFlash('change-password', 'err', 'Current password is incorrect.');
                break;
            case 'same_as_current':
                redirectWithFlash('change-password', 'warn', 'New password must be different from current password.');
                break;
            default:
                redirectWithFlash('change-password', 'err', 'Unable to update password right now. Please try again.');
                break;
        }
    break;

    case "Logout":
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        session_start();
        redirectWithFlash('index', 'ok', 'Signed out successfully.');
    break;

    case "ForgotPassword":
        $turnstileToken = trim((string)($_POST['cf-turnstile-response'] ?? ''));
        $turnstileResult = sinelec_validate_turnstile(
            $turnstileToken,
            $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null
        );
        if (empty($turnstileResult['success'])) {
            redirectWithFlash('forgot-password', 'err', 'Captcha verification failed. Please try again.');
        }

        $fpEmail = strtolower(trim((string)($_POST['fp_email'] ?? '')));
        if ($fpEmail === '' || !filter_var($fpEmail, FILTER_VALIDATE_EMAIL)) {
            redirectWithFlash('forgot-password', 'warn', 'Please enter a valid email address.');
        }

        $otp = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['fp_step']        = 2;
        $_SESSION['fp_email']       = $fpEmail;
        $_SESSION['fp_otp']         = $otp;
        $_SESSION['fp_otp_expires'] = time() + 600;
        unset($_SESSION['fp_otp_verified']);

        // Send OTP only if email is registered (use same success message to prevent enumeration)
        if ($controller->isEmailRegistered($fpEmail)) {
            $fpYear = date('Y');
            $fpBody = sinelec_otp_email_html($fpEmail, $otp, $fpYear, 'Password Reset OTP', 'We received a request to reset your password');
            sinelec_send_mail([[
                'to_mail_id' => $fpEmail,
                'subject'    => 'Password Reset OTP — Sinelec Technologies',
                'body'       => $fpBody,
            ]]);
        }

        redirectWithFlash('forgot-password', 'ok', 'If that email is registered, an OTP has been sent. Please check your inbox.');
    break;

    case "ResendForgotOTP":
        $fpEmail = strtolower(trim((string)($_SESSION['fp_email'] ?? '')));
        $fpStep  = (int)($_SESSION['fp_step'] ?? 1);

        if ($fpStep !== 2 || $fpEmail === '') {
            redirectWithFlash('forgot-password', 'warn', 'Invalid session. Please start again.');
        }

        $otp = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['fp_otp']         = $otp;
        $_SESSION['fp_otp_expires'] = time() + 600;
        unset($_SESSION['fp_otp_verified']);

        if ($controller->isEmailRegistered($fpEmail)) {
            $fpYear = date('Y');
            $fpBody = sinelec_otp_email_html($fpEmail, $otp, $fpYear, 'New OTP (Resent)', 'Here is your new one-time password');
            sinelec_send_mail([[
                'to_mail_id' => $fpEmail,
                'subject'    => 'New OTP for Password Reset — Sinelec Technologies',
                'body'       => $fpBody,
            ]]);
        }

        redirectWithFlash('forgot-password', 'ok', 'A new OTP has been sent to your email.');
    break;

    case "VerifyForgotOTP":
        $fpStep    = (int)($_SESSION['fp_step'] ?? 1);
        $fpEmail   = (string)($_SESSION['fp_email'] ?? '');
        $fpOtp     = (string)($_SESSION['fp_otp'] ?? '');
        $fpExpires = (int)($_SESSION['fp_otp_expires'] ?? 0);

        if ($fpStep !== 2 || $fpEmail === '' || $fpOtp === '') {
            redirectWithFlash('forgot-password', 'warn', 'Invalid session. Please start over.');
        }

        if (time() > $fpExpires) {
            unset($_SESSION['fp_step'], $_SESSION['fp_email'], $_SESSION['fp_otp'], $_SESSION['fp_otp_expires'], $_SESSION['fp_otp_verified']);
            redirectWithFlash('forgot-password', 'warn', 'Your OTP has expired. Please request a new one.');
        }

        $enteredOtp = preg_replace('/\D/', '', (string)($_POST['fp_otp'] ?? ''));
        if (strlen($enteredOtp) !== 6) {
            redirectWithFlash('forgot-password', 'warn', 'Please enter the complete 6-digit OTP.');
        }

        if (!hash_equals($fpOtp, $enteredOtp)) {
            redirectWithFlash('forgot-password', 'err', 'Incorrect OTP. Please check and try again.');
        }

        $_SESSION['fp_step']         = 3;
        $_SESSION['fp_otp_verified'] = true;
        unset($_SESSION['fp_otp'], $_SESSION['fp_otp_expires']);
        redirectWithFlash('forgot-password', 'ok', 'OTP verified successfully. Please set your new password.');
    break;

    case "ResetForgotPassword":
        $fpStep     = (int)($_SESSION['fp_step'] ?? 1);
        $fpEmail    = (string)($_SESSION['fp_email'] ?? '');
        $fpVerified = (bool)($_SESSION['fp_otp_verified'] ?? false);

        if ($fpStep !== 3 || $fpEmail === '' || !$fpVerified) {
            redirectWithFlash('forgot-password', 'warn', 'Invalid session. Please start over.');
        }

        $newPassword     = (string)($_POST['fp_new_password'] ?? '');
        $confirmPassword = (string)($_POST['fp_confirm_password'] ?? '');
        $passwordRule    = '/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/';

        if ($newPassword === '' || $confirmPassword === '') {
            redirectWithFlash('forgot-password', 'warn', 'Please fill in both password fields.');
        }

        if (!preg_match($passwordRule, $newPassword)) {
            redirectWithFlash('forgot-password', 'warn', 'Password must be at least 8 characters and include letters, numbers, and a special character.');
        }

        if ($newPassword !== $confirmPassword) {
            redirectWithFlash('forgot-password', 'warn', 'Passwords do not match. Please try again.');
        }

        $reset = $controller->resetUserPasswordByEmail($fpEmail, $newPassword);

        unset($_SESSION['fp_step'], $_SESSION['fp_email'], $_SESSION['fp_otp'], $_SESSION['fp_otp_expires'], $_SESSION['fp_otp_verified']);

        if ($reset) {
            redirectWithFlash('index', 'ok', 'Password changed successfully. Now you can login with your updated password.');
        }

        redirectWithFlash('forgot-password', 'err', 'Unable to update password. Please try again.');
    break;

    case "UpdateProfile":
        $userId = (int)($_SESSION['sinelec_user']['USER_ID'] ?? 0);
        if ($userId <= 0) {
            redirectWithFlash('index', 'warn', 'Please sign in to continue.');
        }

        $name = trim((string)($_POST['profile_name'] ?? ''));
        $phoneCode = preg_replace('/[^0-9]/', '', (string)($_POST['profile_phone_code'] ?? ''));
        $number = preg_replace('/[^0-9]/', '', (string)($_POST['profile_number'] ?? ''));
        $company = trim((string)($_POST['profile_company'] ?? ''));
        $designation = trim((string)($_POST['profile_designation'] ?? ''));

        if ($name === '' || $phoneCode === '' || $number === '') {
            redirectWithFlash('profile', 'warn', 'Name, phone code, and number are required.');
        }

        if (strlen($number) < 6) {
            redirectWithFlash('profile', 'warn', 'Please enter a valid mobile number.');
        }

        $updated = $controller->updateUserProfile([
            'user_id' => $userId,
            'name' => $name,
            'communication_mobile_num_isd' => (int)$phoneCode,
            'communication_mobile_num' => $number,
            'company_name' => $company,
            'designation' => $designation,
        ]);

        if ($updated) {
            $_SESSION['sinelec_user']['NAME'] = $name;
            $_SESSION['sinelec_user']['COMMUNICATION_MOBILE_NUM_ISD'] = (int)$phoneCode;
            $_SESSION['sinelec_user']['COMMUNICATION_MOBILE_NUM'] = $number;
            $_SESSION['sinelec_user']['COMPANY_NAME'] = $company;
            $_SESSION['sinelec_user']['DESIGNATION'] = $designation;
            redirectWithFlash('profile', 'ok', 'Profile updated successfully.');
        }

        redirectWithFlash('profile', 'err', 'Unable to update profile right now. Please try again.');
    break;

    case 'ApplyJob':
        require_once __DIR__ . '/../common/uploadFileCloudflare.php';

        $jobId = (int)(float)($_POST['job_post_id'] ?? 0);
        $name  = trim($_POST['candidate_name']      ?? '');
        $email = trim($_POST['candidate_email']     ?? '');
        $phone = trim($_POST['candidate_phone']     ?? '');
        $exp   = (int)($_POST['candidate_experience'] ?? 0);

        if ($jobId <= 0 || $name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirectWithFlash('career', 'warn', 'Please fill all required fields correctly.');
        }

        /* Upload resume to R2 */
        if (empty($_FILES['resume']['tmp_name'])) {
            redirectWithFlash('career', 'warn', 'Please attach your resume (PDF, DOC, or DOCX).');
        }

        $upload = uploadToR2($_FILES['resume'], 'careers/resumes', 'pdf,doc,docx', 5);
        if (!$upload['success']) {
            redirectWithFlash('career', 'err', 'Resume upload failed: ' . $upload['error']);
        }

        $newId = $controller->insertApplicant([
            'job_post_id'          => $jobId,
            'candidate_name'       => $name,
            'candidate_email'      => $email,
            'candidate_phone'      => preg_replace('/\D/', '', $phone),
            'candidate_experience' => $exp,
            'resume_file_ext'      => $upload['key'],
        ]);

        if ($newId > 0) {
            redirectWithFlash('career', 'ok', 'Application submitted successfully! We\'ll be in touch soon.');
        }

        /* Rollback: delete uploaded resume if DB insert failed */
        deleteFromR2($upload['key']);
        redirectWithFlash('career', 'err', 'Could not save your application. Please try again.');
    break;

    case 'Subscribe':
        $email = strtolower(trim($_POST['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirectWithFlash('index', 'warn', 'Please enter a valid email address.');
        }
        $result = $controller->insertSubscriber($email);
        if ($result === 'subscribed') {
            redirectWithFlash('index', 'ok', 'You\'re subscribed! Thanks for joining.');
        }
        if ($result === 'already') {
            redirectWithFlash('index', 'warn', 'This email is already subscribed.');
        }
        if ($result === 'blocked') {
            redirectWithFlash('index', 'warn', 'This email cannot be subscribed.');
        }
        redirectWithFlash('index', 'err', 'Something went wrong. Please try again.');
    break;




}


?>
