<?php
ini_set('display_errors', 1);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

function adminRedirectWithFlash(string $target, string $type, string $message): void
{
    sinelec_set_flash($type, $message);
    header('location:' . $target);
    exit();
}

function adminRequireAuth(): void
{
    if (empty($_SESSION['sinelec_admin']['USER_ID'])) {
        adminRedirectWithFlash('index', 'warn', 'Please sign in to access the admin panel.');
    }
}

function adminUploadImage(string $inputName, string $uploadDir, int $maxMB = 5): string
{
    if (empty($_FILES[$inputName]['tmp_name'])) return '';
    $file   = $_FILES[$inputName];
    $ext    = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp'];
    if (!in_array($ext, $allowed)) return '';
    if ($file['size'] > $maxMB * 1024 * 1024) return '';
    return $ext;
}

function adminMoveUpload(string $inputName, string $destPath): bool
{
    if (empty($_FILES[$inputName]['tmp_name'])) return false;
    return move_uploaded_file($_FILES[$inputName]['tmp_name'], $destPath);
}

function adminUploadDoc(string $inputName, int $maxMB = 10): string
{
    if (empty($_FILES[$inputName]['tmp_name'])) return '';
    $file = $_FILES[$inputName];
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['pdf','doc','docx'];
    if (!in_array($ext, $allowed)) return '';
    if ($file['size'] > $maxMB * 1024 * 1024) return '';
    return $ext;
}

$paramsArray = GetQueryStringParameters();
$action      = isset($paramsArray['action'])
    ? htmlspecialchars($paramsArray['action'])
    : ($_GET['action'] ?? '');

$controller = new AdminController();

switch ($action) {

    /* ─────────────────────────────────────────────────────────────
       AUTH
    ───────────────────────────────────────────────────────────── */
    case 'Login':
        $turnstileToken  = trim((string)($_POST['cf-turnstile-response'] ?? ''));
        $turnstileResult = sinelec_validate_turnstile(
            $turnstileToken,
            $_SERVER['HTTP_CF_CONNECTING_IP']
                ?? $_SERVER['HTTP_X_FORWARDED_FOR']
                ?? $_SERVER['REMOTE_ADDR']
                ?? null
        );

        if (empty($turnstileResult['success'])) {
            adminRedirectWithFlash('index', 'err', 'Captcha verification failed. Please try again.');
        }

        $username = strtolower(trim($_POST['adminUserId'] ?? ''));
        $password = (string)($_POST['adminPassword'] ?? '');

        if ($username === '' || $password === '') {
            adminRedirectWithFlash('index', 'warn', 'Please enter your email and password.');
        }

        $admin = $controller->loginAdmin(['username' => $username, 'password' => $password]);

        if (!empty($admin) && isset($admin['user_id'])) {
            $typeId = (int)$admin['user_type_id'];
            $roleId = (int)($admin['role_id'] ?? 0);

            /* Only user_type_id 1 (admin) and 3 (employee) can access */
            if (!in_array($typeId, [1, 3], true)) {
                adminRedirectWithFlash('index', 'err', 'Access denied. You are not authorised to access this panel.');
            }

            /* Employees must have a role assigned */
            if ($typeId === 3 && $roleId === 0) {
                adminRedirectWithFlash('index', 'warn', 'Your account has no role assigned. Please contact the administrator.');
            }

            session_regenerate_id(true);
            $_SESSION['sinelec_admin'] = [
                'USER_ID'      => (int)$admin['user_id'],
                'NAME'         => (string)$admin['name'],
                'EMAIL'        => (string)$admin['email'],
                'USER_TYPE_ID' => $typeId,
                'ROLE_ID'      => $roleId,
            ];

            /* ── Build and cache menu + flat permission lookup in session ── */
            $menuData = $controller->getAdminMenu();
            $_SESSION['sinelec_admin']['MENU_DATA'] = $menuData;

            $perms = [];
            foreach ($menuData as $grp) {
                foreach ($grp['items'] as $item) {
                    $perms[(int)$item['menu_id']] = [
                        'can_view'   => (bool)($item['can_view']   ?? false),
                        'can_add'    => (bool)($item['can_add']    ?? false),
                        'can_edit'   => (bool)($item['can_edit']   ?? false),
                        'can_delete' => (bool)($item['can_delete'] ?? false),
                    ];
                }
            }
            $_SESSION['sinelec_admin']['PERMISSIONS'] = $perms;

            adminRedirectWithFlash('dashboard', 'ok', 'Welcome back, ' . $admin['name'] . '!');
        }

        adminRedirectWithFlash('index', 'err', 'Invalid credentials. Please try again.');
    break;

    case 'Logout':
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        session_start();
        sinelec_set_flash('ok', 'Signed out successfully.');
        header('location:index');
        exit();
    break;

    /* ─────────────────────────────────────────────────────────────
       ADMIN PROFILE
    ───────────────────────────────────────────────────────────── */
    case 'UpdateAdminProfile':
        adminRequireAuth();
        $userId = (int)($_SESSION['sinelec_admin']['USER_ID'] ?? 0);
        $name   = trim($_POST['name'] ?? '');
        if ($userId <= 0) adminRedirectWithFlash('profile', 'warn', 'Session error. Please sign in again.');
        if ($name === '') adminRedirectWithFlash('profile', 'warn', 'Name cannot be empty.');
        $ok = $controller->updateAdminProfile([
            'user_id'                    => $userId,
            'name'                       => $name,
            'communication_mobile_num_isd'  => (int)($_POST['communication_mobile_num_isd'] ?? 91),
            'communication_mobile_num'   => trim($_POST['communication_mobile_num'] ?? ''),
            'company_name'               => trim($_POST['company_name'] ?? ''),
            'designation'                => trim($_POST['designation'] ?? ''),
        ]);
        if ($ok) {
            adminRedirectWithFlash('profile', 'ok', 'Profile updated successfully.');
        }
        adminRedirectWithFlash('profile', 'err', 'Failed to update profile. Please try again.');
    break;

    /* ─────────────────────────────────────────────────────────────
       CATEGORIES
    ───────────────────────────────────────────────────────────── */
    case 'InsertCategory':
        adminRequireAuth();
        $name = trim($_POST['product_category_name'] ?? '');
        if ($name === '') {
            adminRedirectWithFlash('categories', 'warn', 'Category name is required.');
        }
        $ext = adminUploadImage('category_image', __DIR__.'/../assets/uploads/categories/');
        $id  = $controller->insertCategory([
            'name'        => $name,
            'parent_id'   => (int)($_POST['parent_category_id'] ?? 0),
            'priority'    => (int)($_POST['priority'] ?? 0),
            'description' => trim($_POST['description'] ?? ''),
            'ext'         => $ext,
        ]);
        if ($id > 0 && $ext !== '') {
            adminMoveUpload('category_image', __DIR__.'/../assets/uploads/categories/'.$id.'.'.$ext);
        }
        if ($id > 0) {
            adminRedirectWithFlash('categories', 'ok', 'Category added successfully.');
        }
        adminRedirectWithFlash('categories', 'err', 'Failed to add category.');
    break;

    case 'UpdateCategory':
        adminRequireAuth();
        $catId = (int)($_POST['product_category_id'] ?? 0);
        $name  = trim($_POST['product_category_name'] ?? '');
        if ($catId <= 0 || $name === '') {
            adminRedirectWithFlash('categories', 'warn', 'Invalid request.');
        }
        $ext = adminUploadImage('category_image', __DIR__.'/../assets/uploads/categories/');
        if ($ext === '') $ext = trim($_POST['existing_ext'] ?? '');
        $ok = $controller->updateCategory([
            'id'          => $catId,
            'name'        => $name,
            'parent_id'   => (int)($_POST['parent_category_id'] ?? 0),
            'priority'    => (int)($_POST['priority'] ?? 0),
            'description' => trim($_POST['description'] ?? ''),
            'ext'         => $ext,
        ]);
        if ($ok && !empty($_FILES['category_image']['tmp_name'])) {
            adminMoveUpload('category_image', __DIR__.'/../assets/uploads/categories/'.$catId.'.'.$ext);
        }
        if ($ok) {
            adminRedirectWithFlash('categories', 'ok', 'Category updated successfully.');
        }
        adminRedirectWithFlash('categories', 'err', 'Failed to update category.');
    break;

    case 'DeleteCategory':
        adminRequireAuth();
        $catId = (int)($_POST['product_category_id'] ?? $_GET['id'] ?? 0);
        if ($catId <= 0) adminRedirectWithFlash('categories', 'warn', 'Invalid request.');
        $ok = $controller->deleteCategory($catId);
        if ($ok) {
            adminRedirectWithFlash('categories', 'ok', 'Category deleted.');
        }
        adminRedirectWithFlash('categories', 'err', 'Cannot delete — category has products or sub-categories.');
    break;

    /* ─────────────────────────────────────────────────────────────
       PRODUCTS
    ───────────────────────────────────────────────────────────── */
    case 'InsertProduct':
        adminRequireAuth();
        $name = trim($_POST['product_name'] ?? '');
        if ($name === '') adminRedirectWithFlash('products', 'warn', 'Product name is required.');

        $id = $controller->insertProduct($_POST);
        if ($id > 0) {
            // main product image
            if (!empty($_FILES['product_image']['tmp_name'])) {
                $ext = adminUploadImage('product_image', '');
                if ($ext !== '') {
                    $imgId = $controller->addProductImage($id, $ext, 'Product', '', (int)($_POST['img_priority'] ?? 0));
                    if ($imgId > 0) {
                        adminMoveUpload('product_image', __DIR__.'/../assets/uploads/products/'.$imgId.'.'.$ext);
                    }
                }
            }
            adminRedirectWithFlash('products', 'ok', 'Product added successfully.');
        }
        adminRedirectWithFlash('products', 'err', 'Failed to add product.');
    break;

    case 'UpdateProduct':
        adminRequireAuth();
        $pid = (int)($_POST['product_id'] ?? 0);
        if ($pid <= 0) adminRedirectWithFlash('products', 'warn', 'Invalid request.');

        $ok = $controller->updateProduct($_POST);
        if ($ok) {
            adminRedirectWithFlash('products?id='.$pid, 'ok', 'Product updated successfully.');
        }
        adminRedirectWithFlash('products?id='.$pid, 'err', 'Failed to update product.');
    break;

    case 'AddProductImage':
        adminRequireAuth();
        $pid = (int)($_POST['product_id'] ?? 0);
        if ($pid <= 0 || empty($_FILES['product_image']['tmp_name'])) {
            adminRedirectWithFlash('products?id='.$pid, 'warn', 'No image provided.');
        }
        $ext = adminUploadImage('product_image', '');
        if ($ext === '') adminRedirectWithFlash('products?id='.$pid, 'warn', 'Invalid image format (jpg/png/webp allowed).');
        $imgId = $controller->addProductImage($pid, $ext, (string)($_POST['image_for'] ?? 'Product'), (string)($_POST['product_manual_title'] ?? ''), (int)($_POST['img_priority'] ?? 0));
        if ($imgId > 0) {
            adminMoveUpload('product_image', __DIR__.'/../assets/uploads/products/'.$imgId.'.'.$ext);
            adminRedirectWithFlash('products?id='.$pid, 'ok', 'Image uploaded.');
        }
        adminRedirectWithFlash('products?id='.$pid, 'err', 'Failed to upload image.');
    break;

    case 'DeleteProductImage':
        adminRequireAuth();
        $imgId = (int)($_POST['image_id'] ?? $_GET['image_id'] ?? 0);
        $pid   = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
        $controller->deleteProductImage($imgId);
        adminRedirectWithFlash('products?id='.$pid, 'ok', 'Image removed.');
    break;

    case 'DeleteProduct':
        adminRequireAuth();
        $pid = (int)($_POST['product_id'] ?? $_GET['id'] ?? 0);
        if ($pid <= 0) adminRedirectWithFlash('products', 'warn', 'Invalid request.');
        $ok = $controller->deleteProduct($pid);
        if ($ok) {
            adminRedirectWithFlash('products', 'ok', 'Product deleted.');
        }
        adminRedirectWithFlash('products', 'err', 'Cannot delete — product has enquiries or purchase records.');
    break;

    /* ─────────────────────────────────────────────────────────────
       PURCHASE
    ───────────────────────────────────────────────────────────── */
    case 'InsertPurchase':
        adminRequireAuth();
        $pid = (int)($_POST['product_id'] ?? 0);
        $qty = (int)($_POST['quantity_purchased'] ?? 0);
        if ($pid <= 0 || $qty <= 0) adminRedirectWithFlash('purchase', 'warn', 'Product and quantity are required.');
        $ok = $controller->insertPurchase($_POST);
        if ($ok) {
            adminRedirectWithFlash('purchase', 'ok', 'Purchase record added and stock updated.');
        }
        adminRedirectWithFlash('purchase', 'err', 'Failed to add purchase record.');
    break;

    case 'DeletePurchase':
        adminRequireAuth();
        $ppId = (int)($_POST['product_purchase_id'] ?? $_GET['id'] ?? 0);
        if ($ppId <= 0) adminRedirectWithFlash('purchase', 'warn', 'Invalid request.');
        $ok = $controller->deletePurchase($ppId);
        if ($ok) {
            adminRedirectWithFlash('purchase', 'ok', 'Purchase record deleted and stock reversed.');
        }
        adminRedirectWithFlash('purchase', 'err', 'Failed to delete purchase record.');
    break;

    /* ─────────────────────────────────────────────────────────────
       ORDERS
    ───────────────────────────────────────────────────────────── */
    case 'UpdateOrderStatus':
        adminRequireAuth();
        $orderId = (int)($_POST['order_id'] ?? 0);
        $status  = trim($_POST['order_status'] ?? '');
        if ($orderId <= 0 || $status === '') adminRedirectWithFlash('orders', 'warn', 'Invalid request.');
        $extra = [
            'courier_company' => trim($_POST['dispatch_courier_company'] ?? ''),
            'tracking_id'     => trim($_POST['dispatch_courier_tracking_id'] ?? ''),
            'tracking_url'    => trim($_POST['dispatch_courier_tracking_url'] ?? ''),
        ];
        $ok = $controller->updateOrderStatus($orderId, $status, $extra);
        if ($ok) {
            adminRedirectWithFlash('orders', 'ok', 'Order status updated to "' . htmlspecialchars($status) . '".');
        }
        adminRedirectWithFlash('orders', 'err', 'Failed to update order status.');
    break;

    /* ─────────────────────────────────────────────────────────────
       ENQUIRIES
    ───────────────────────────────────────────────────────────── */
    case 'UpdateEnquiryStatus':
        adminRequireAuth();
        $eqId   = (int)($_POST['enquiry_quote_id'] ?? 0);
        $status = trim($_POST['enquiry_status'] ?? '');
        if ($eqId <= 0 || $status === '') adminRedirectWithFlash('enquiries', 'warn', 'Invalid request.');
        $ok = $controller->updateEnquiryStatus($eqId, $status);
        if ($ok) {
            adminRedirectWithFlash('enquiries', 'ok', 'Enquiry status updated.');
        }
        adminRedirectWithFlash('enquiries', 'err', 'Failed to update enquiry status.');
    break;

    /* ─────────────────────────────────────────────────────────────
       BANNERS
    ───────────────────────────────────────────────────────────── */
    case 'InsertBanner':
        adminRequireAuth();
        require_once __DIR__.'/../common/uploadFileCloudflare.php';
        $bannerName = trim($_POST['banner_name'] ?? '');
        if ($bannerName === '') adminRedirectWithFlash('banners', 'warn', 'Banner name is required.');
        $imgKey = '';
        if (!empty($_FILES['banner_image']['tmp_name'])) {
            $r2 = uploadToR2($_FILES['banner_image'], 'banners', 'IMAGE');
            if (!$r2['success']) adminRedirectWithFlash('banners', 'err', 'Image upload failed: '.$r2['error']);
            $imgKey = $r2['key'];
        }
        $id = $controller->insertBanner([
            'banner_name'        => $bannerName,
            'priority'           => (int)($_POST['priority'] ?? 0),
            'banner_description' => trim($_POST['banner_description'] ?? ''),
            'hyperlink'          => trim($_POST['hyperlink'] ?? ''),
            'display_flag'       => in_array($_POST['display_flag'] ?? 'Yes', ['Yes','No']) ? $_POST['display_flag'] : 'Yes',
            'banner_bg_color'    => trim($_POST['banner_bg_color'] ?? ''),
            'tags'               => trim($_POST['tags']         ?? ''),
            'btn_one'            => trim($_POST['btn_one']      ?? ''),
            'btn_one_link'       => trim($_POST['btn_one_link'] ?? ''),
            'btn_two'            => trim($_POST['btn_two']      ?? ''),
            'btn_two_link'       => trim($_POST['btn_two_link'] ?? ''),
            'ext'                => $imgKey,
        ]);
        if ($id > 0) adminRedirectWithFlash('banners', 'ok', 'Banner added successfully.');
        adminRedirectWithFlash('banners', 'err', 'Failed to add banner.');
    break;

    case 'UpdateBanner':
        adminRequireAuth();
        require_once __DIR__.'/../common/uploadFileCloudflare.php';
        $bannerId   = (int)($_POST['banner_id'] ?? 0);
        $bannerName = trim($_POST['banner_name'] ?? '');
        if ($bannerId <= 0) adminRedirectWithFlash('banners', 'warn', 'Invalid request.');
        if ($bannerName === '') adminRedirectWithFlash('banners', 'warn', 'Banner name is required.');
        $existingKey = trim($_POST['existing_img_key'] ?? '');
        $newKey      = $existingKey;
        if (!empty($_FILES['banner_image']['tmp_name'])) {
            $r2 = replaceR2File($_FILES['banner_image'], 'banners', 'IMAGE', $existingKey ?: null, 20);
            if (!$r2['success']) adminRedirectWithFlash('banners', 'err', 'Image upload failed: '.$r2['error']);
            $newKey = $r2['key'];
        }
        $ok = $controller->updateBanner([
            'banner_id'          => $bannerId,
            'banner_name'        => $bannerName,
            'priority'           => (int)($_POST['priority'] ?? 0),
            'banner_description' => trim($_POST['banner_description'] ?? ''),
            'hyperlink'          => trim($_POST['hyperlink'] ?? ''),
            'display_flag'       => in_array($_POST['display_flag'] ?? 'Yes', ['Yes','No']) ? $_POST['display_flag'] : 'Yes',
            'banner_bg_color'    => trim($_POST['banner_bg_color'] ?? ''),
            'tags'               => trim($_POST['tags']         ?? ''),
            'btn_one'            => trim($_POST['btn_one']      ?? ''),
            'btn_one_link'       => trim($_POST['btn_one_link'] ?? ''),
            'btn_two'            => trim($_POST['btn_two']      ?? ''),
            'btn_two_link'       => trim($_POST['btn_two_link'] ?? ''),
            'ext'                => $newKey,
        ]);
        if ($ok) adminRedirectWithFlash('banners', 'ok', 'Banner updated successfully.');
        adminRedirectWithFlash('banners', 'err', 'Failed to update banner.');
    break;

    case 'DeleteBanner':
        adminRequireAuth();
        $bannerId = (int)($_POST['banner_id'] ?? $_GET['id'] ?? 0);
        if ($bannerId <= 0) adminRedirectWithFlash('banners', 'warn', 'Invalid request.');
        require_once __DIR__.'/../common/uploadFileCloudflare.php';
        $bRow = $controller->getBannerById($bannerId);
        if ($bRow && !empty($bRow->BANNER_IMG_EXT)) {
            deleteFromR2((string)$bRow->BANNER_IMG_EXT);
        }
        $controller->deleteBanner($bannerId);
        adminRedirectWithFlash('banners', 'ok', 'Banner deleted.');
    break;

    /* ─────────────────────────────────────────────────────────────
       NEWS & EVENTS
    ───────────────────────────────────────────────────────────── */
    case 'InsertNews':
        adminRequireAuth();
        $title = trim($_POST['title'] ?? '');
        if ($title === '') adminRedirectWithFlash('news', 'warn', 'Title is required.');
        $imgExt = '';
        $docExt = '';
        if (!empty($_FILES['news_image']['tmp_name'])) {
            $imgExt = adminUploadImage('news_image', '');
        }
        if (!empty($_FILES['news_doc']['tmp_name'])) {
            $docExt = adminUploadDoc('news_doc');
        }
        $d = array_merge($_POST, ['img_ext' => $imgExt, 'doc_ext' => $docExt]);
        $id = $controller->insertNews($d);
        if ($id > 0) {
            if ($imgExt !== '') adminMoveUpload('news_image', __DIR__.'/../assets/uploads/news/'.$id.'.'.$imgExt);
            if ($docExt !== '') adminMoveUpload('news_doc',   __DIR__.'/../assets/uploads/news/'.$id.'_doc.'.$docExt);
            adminRedirectWithFlash('news', 'ok', 'News/Event added successfully.');
        }
        adminRedirectWithFlash('news', 'err', 'Failed to add news/event.');
    break;

    case 'UpdateNews':
        adminRequireAuth();
        $newsId = (int)($_POST['news_event_id'] ?? 0);
        if ($newsId <= 0) adminRedirectWithFlash('news', 'warn', 'Invalid request.');
        $imgExt = trim($_POST['existing_img_ext'] ?? '');
        $docExt = trim($_POST['existing_doc_ext'] ?? '');
        if (!empty($_FILES['news_image']['tmp_name'])) {
            $newExt = adminUploadImage('news_image', '');
            if ($newExt !== '') $imgExt = $newExt;
        }
        if (!empty($_FILES['news_doc']['tmp_name'])) {
            $newDoc = adminUploadDoc('news_doc');
            if ($newDoc !== '') $docExt = $newDoc;
        }
        $d = array_merge($_POST, ['img_ext' => $imgExt, 'doc_ext' => $docExt]);
        $ok = $controller->updateNews($d);
        if ($ok) {
            if (!empty($_FILES['news_image']['tmp_name']) && $imgExt !== '') adminMoveUpload('news_image', __DIR__.'/../assets/uploads/news/'.$newsId.'.'.$imgExt);
            if (!empty($_FILES['news_doc']['tmp_name'])   && $docExt !== '') adminMoveUpload('news_doc',   __DIR__.'/../assets/uploads/news/'.$newsId.'_doc.'.$docExt);
            adminRedirectWithFlash('news', 'ok', 'News/Event updated successfully.');
        }
        adminRedirectWithFlash('news', 'err', 'Failed to update news/event.');
    break;

    case 'DeleteNews':
        adminRequireAuth();
        $newsId = (int)($_POST['news_event_id'] ?? $_GET['id'] ?? 0);
        if ($newsId <= 0) adminRedirectWithFlash('news', 'warn', 'Invalid request.');
        $controller->deleteNews($newsId);
        adminRedirectWithFlash('news', 'ok', 'News/Event deleted.');
    break;

    /* ─────────────────────────────────────────────────────────────
       FAQ
    ───────────────────────────────────────────────────────────── */
    case 'InsertFAQ':
        adminRequireAuth();
        $q = trim($_POST['faq_question'] ?? '');
        $a = trim($_POST['faq_answer'] ?? '');
        if ($q === '' || $a === '') adminRedirectWithFlash('faq', 'warn', 'Question and answer are required.');
        $id = $controller->insertFAQ($_POST);
        if ($id > 0) {
            adminRedirectWithFlash('faq', 'ok', 'FAQ added successfully.');
        }
        adminRedirectWithFlash('faq', 'err', 'Failed to add FAQ.');
    break;

    case 'UpdateFAQ':
        adminRequireAuth();
        $faqId = (int)($_POST['faq_id'] ?? 0);
        if ($faqId <= 0) adminRedirectWithFlash('faq', 'warn', 'Invalid request.');
        $ok = $controller->updateFAQ($_POST);
        if ($ok) {
            adminRedirectWithFlash('faq', 'ok', 'FAQ updated successfully.');
        }
        adminRedirectWithFlash('faq', 'err', 'Failed to update FAQ.');
    break;

    case 'DeleteFAQ':
        adminRequireAuth();
        $faqId = (int)($_POST['faq_id'] ?? $_GET['id'] ?? 0);
        if ($faqId <= 0) adminRedirectWithFlash('faq', 'warn', 'Invalid request.');
        $controller->deleteFAQ($faqId);
        adminRedirectWithFlash('faq', 'ok', 'FAQ deleted.');
    break;

    /* ─────────────────────────────────────────────────────────────
       JOBS
    ───────────────────────────────────────────────────────────── */
    case 'InsertJob':
        adminRequireAuth();
        $pos = trim($_POST['job_position'] ?? '');
        if ($pos === '') adminRedirectWithFlash('jobs', 'warn', 'Job position is required.');
        $id = $controller->insertJob($_POST);
        if ($id > 0) {
            adminRedirectWithFlash('jobs', 'ok', 'Job post added successfully.');
        }
        adminRedirectWithFlash('jobs', 'err', 'Failed to add job post.');
    break;

    case 'UpdateJob':
        adminRequireAuth();
        $jobId = (int)($_POST['job_post_id'] ?? 0);
        if ($jobId <= 0) adminRedirectWithFlash('jobs', 'warn', 'Invalid request.');
        $ok = $controller->updateJob($_POST);
        if ($ok) {
            adminRedirectWithFlash('jobs', 'ok', 'Job post updated successfully.');
        }
        adminRedirectWithFlash('jobs', 'err', 'Failed to update job post.');
    break;

    case 'DeleteJob':
        adminRequireAuth();
        $jobId = (int)($_POST['job_post_id'] ?? $_GET['id'] ?? 0);
        if ($jobId <= 0) adminRedirectWithFlash('jobs', 'warn', 'Invalid request.');
        $ok = $controller->deleteJob($jobId);
        if ($ok) {
            adminRedirectWithFlash('jobs', 'ok', 'Job post deleted.');
        }
        adminRedirectWithFlash('jobs', 'err', 'Cannot delete — this post has applicants. Delete applicants first.');
    break;

    /* ─────────────────────────────────────────────────────────────
       APPLICANTS
    ───────────────────────────────────────────────────────────── */
    case 'DeleteApplicant':
        adminRequireAuth();
        $appId = (int)($_POST['candidate_applied_job_id'] ?? $_GET['id'] ?? 0);
        if ($appId <= 0) adminRedirectWithFlash('applicants', 'warn', 'Invalid request.');
        $controller->deleteApplicant($appId);
        adminRedirectWithFlash('applicants', 'ok', 'Application deleted.');
    break;

    /* ─────────────────────────────────────────────────────────────
       ROLES
    ───────────────────────────────────────────────────────────── */
    case 'SaveRole':
        adminRequireAuth();
        $roleName = trim($_POST['role_name'] ?? '');
        if ($roleName === '') adminRedirectWithFlash('roles', 'warn', 'Role name is required.');
        $perms  = (isset($_POST['perms']) && is_array($_POST['perms'])) ? $_POST['perms'] : [];
        $roleId = $_POST['role_id'] ?? 0;
        $result = $controller->saveRole($_POST, $perms);
        if ($result !== false && $result > 0) {
            $msg = ((int)$roleId > 0) ? 'Role updated successfully.' : 'Role created successfully.';
            adminRedirectWithFlash('roles', 'ok', $msg);
        }
        adminRedirectWithFlash('roles', 'err', 'Failed to save role. Please try again.');
    break;

    case 'DeleteRole':
        adminRequireAuth();
        $roleId = (int)($_POST['role_id'] ?? 0);
        if ($roleId <= 0) adminRedirectWithFlash('roles', 'warn', 'Invalid role.');
        if ($controller->deleteRole($roleId)) {
            adminRedirectWithFlash('roles', 'ok', 'Role deleted successfully.');
        }
        adminRedirectWithFlash('roles', 'err', 'Cannot delete — this role is assigned to one or more users.');
    break;

    /* ─────────────────────────────────────────────────────────────
       EMPLOYEES
    ───────────────────────────────────────────────────────────── */
    case 'SaveEmployee':
        adminRequireAuth();
        $name   = trim($_POST['name'] ?? '');
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($name === '') adminRedirectWithFlash('employee-list', 'warn', 'Full name is required.');
        if ($userId <= 0 && trim($_POST['communication_email_id'] ?? '') === '') {
            adminRedirectWithFlash('employee-list', 'warn', 'Email address is required.');
        }
        if ($userId <= 0 && trim($_POST['password'] ?? '') === '') {
            adminRedirectWithFlash('employee-list', 'warn', 'Password is required for new employees.');
        }
        $result = $controller->saveEmployee($_POST);
        if ($result === -1) {
            adminRedirectWithFlash('employee-list', 'warn', 'This email address is already registered.');
        }
        if ($result !== false && $result > 0) {
            $msg = $userId > 0 ? 'Employee updated successfully.' : 'Employee added successfully.';
            adminRedirectWithFlash('employee-list', 'ok', $msg);
        }
        adminRedirectWithFlash('employee-list', 'err', 'Failed to save employee. Please try again.');
    break;

    case 'DeleteEmployee':
        adminRequireAuth();
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId <= 0) adminRedirectWithFlash('employee-list', 'warn', 'Invalid request.');
        if ($controller->deleteEmployee($userId)) {
            adminRedirectWithFlash('employee-list', 'ok', 'Employee deleted successfully.');
        }
        adminRedirectWithFlash('employee-list', 'err', 'Failed to delete employee.');
    break;

    /* ─────────────────────────────────────────────────────────────
       CUSTOMERS
    ───────────────────────────────────────────────────────────── */
    case 'SaveCustomer':
        adminRequireAuth();
        $name   = trim($_POST['name'] ?? '');
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($name === '') adminRedirectWithFlash('customers', 'warn', 'Full name is required.');
        if ($userId <= 0 && trim($_POST['communication_email_id'] ?? '') === '') {
            adminRedirectWithFlash('customers', 'warn', 'Email address is required.');
        }
        if ($userId <= 0 && trim($_POST['password'] ?? '') === '') {
            adminRedirectWithFlash('customers', 'warn', 'Password is required for new customers.');
        }
        $result = $controller->saveCustomer($_POST);
        if ($result === -1) {
            adminRedirectWithFlash('customers', 'warn', 'This email address is already registered.');
        }
        if ($result !== false && $result > 0) {
            $msg = $userId > 0 ? 'Customer updated successfully.' : 'Customer added successfully.';
            adminRedirectWithFlash('customers', 'ok', $msg);
        }
        adminRedirectWithFlash('customers', 'err', 'Failed to save customer. Please try again.');
    break;

    case 'DeleteCustomer':
        adminRequireAuth();
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId <= 0) adminRedirectWithFlash('customers', 'warn', 'Invalid request.');
        if ($controller->deleteCustomer($userId)) {
            adminRedirectWithFlash('customers', 'ok', 'Customer deleted successfully.');
        }
        adminRedirectWithFlash('customers', 'err', 'Failed to delete customer.');
    break;

    case 'ResetCustomerPassword':
        adminRequireAuth();
        $userId   = (int)($_POST['user_id'] ?? 0);
        $password = trim($_POST['new_password'] ?? '');
        $confirm  = trim($_POST['confirm_password'] ?? '');
        if ($userId <= 0) adminRedirectWithFlash('customers', 'warn', 'Invalid request.');
        if ($password === '') adminRedirectWithFlash('customers', 'warn', 'New password is required.');
        if (strlen($password) < 6) adminRedirectWithFlash('customers', 'warn', 'Password must be at least 6 characters.');
        if ($password !== $confirm) adminRedirectWithFlash('customers', 'warn', 'Passwords do not match.');
        if ($controller->resetCustomerPassword($userId, $password)) {
            adminRedirectWithFlash('customers', 'ok', 'Password reset successfully.');
        }
        adminRedirectWithFlash('customers', 'err', 'Failed to reset password.');
    break;

    case 'ResetEmployeePassword':
        adminRequireAuth();
        $userId   = (int)($_POST['user_id'] ?? 0);
        $password = trim($_POST['new_password'] ?? '');
        $confirm  = trim($_POST['confirm_password'] ?? '');
        if ($userId <= 0) adminRedirectWithFlash('employee-list', 'warn', 'Invalid request.');
        if ($password === '') adminRedirectWithFlash('employee-list', 'warn', 'New password is required.');
        if (strlen($password) < 6) adminRedirectWithFlash('employee-list', 'warn', 'Password must be at least 6 characters.');
        if ($password !== $confirm) adminRedirectWithFlash('employee-list', 'warn', 'Passwords do not match.');
        if ($controller->resetEmployeePassword($userId, $password)) {
            adminRedirectWithFlash('employee-list', 'ok', 'Password reset successfully.');
        }
        adminRedirectWithFlash('employee-list', 'err', 'Failed to reset password.');
    break;

    default:
        header('location:welcome');
        exit();
}
?>
