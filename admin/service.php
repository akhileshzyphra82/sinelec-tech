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
       PRODUCT CATEGORIES
    ───────────────────────────────────────────────────────────── */
    case 'SaveProductCategory':
        adminRequireAuth();
        require_once __DIR__.'/../common/uploadFileCloudflare.php';
        $catId       = (int)($_POST['product_category_id'] ?? 0);
        $name        = trim($_POST['product_category_name'] ?? '');
        $existingKey = trim($_POST['existing_ext'] ?? '');
        if ($name === '') adminRedirectWithFlash('product-category', 'warn', 'Category name is required.');

        $imgKey = $existingKey;
        if (!empty($_FILES['category_image']['tmp_name'])) {
            /* Delete old R2 object only if key looks like an R2 path (contains '/') */
            $oldKey = ($catId > 0 && strpos($existingKey, '/') !== false) ? $existingKey : null;
            $r2 = $oldKey
                ? replaceR2File($_FILES['category_image'], 'categories', 'IMAGE', $oldKey, 5)
                : uploadToR2($_FILES['category_image'], 'categories', 'IMAGE');
            if (!$r2['success']) adminRedirectWithFlash('product-category', 'err', 'Image upload failed: '.$r2['error']);
            $imgKey = $r2['key'];
        }

        $savedId = $controller->saveProductCategory([
            'id'          => $catId,
            'name'        => $name,
            'parent_id'   => (int)($_POST['parent_category_id'] ?? 0),
            'priority'    => (int)($_POST['priority'] ?? 0),
            'description' => trim($_POST['description'] ?? ''),
            'ext'         => $imgKey,
        ]);

        if ($savedId) {
            $msg = $catId > 0 ? 'Category updated successfully.' : 'Category added successfully.';
            adminRedirectWithFlash('product-category', 'ok', $msg);
        }
        adminRedirectWithFlash('product-category', 'err', 'Failed to save category.');
    break;

    case 'DeleteProductCategory':
        adminRequireAuth();
        $catId = (int)($_POST['product_category_id'] ?? 0);
        if ($catId <= 0) adminRedirectWithFlash('product-category', 'warn', 'Invalid request.');
        $ok = $controller->deleteCategory($catId);
        if ($ok) adminRedirectWithFlash('product-category', 'ok', 'Category deleted.');
        adminRedirectWithFlash('product-category', 'err', 'Cannot delete — category is in use by products or sub-categories.');
    break;

    /* ─────────────────────────────────────────────────────────────
       PRODUCTS
    ───────────────────────────────────────────────────────────── */
    case 'SaveProduct':
        adminRequireAuth();
        $pid  = (int)($_POST['product_id'] ?? 0);
        $name = trim($_POST['product_name'] ?? '');
        if ($name === '') adminRedirectWithFlash('products', 'warn', 'Product name is required.');
        $savedId = $controller->saveProduct($_POST);
        if ($savedId) {
            /* Save sample codes (delete-and-reinsert) */
            $sLangs = (array)($_POST['sample_lang'] ?? []);
            $sIdes  = (array)($_POST['sample_ide']  ?? []);
            $sTypes = (array)($_POST['sample_type'] ?? []);
            $sOses  = (array)($_POST['sample_os']   ?? []);
            $sUrls  = (array)($_POST['sample_url']  ?? []);
            $codes  = [];
            foreach ($sLangs as $i => $lang) {
                $codes[] = [
                    'lang' => trim($lang),
                    'ide'  => trim($sIdes[$i]  ?? ''),
                    'type' => trim($sTypes[$i] ?? ''),
                    'os'   => trim($sOses[$i]  ?? ''),
                    'url'  => trim($sUrls[$i]  ?? ''),
                ];
            }
            $controller->saveSampleCodes((int)$savedId, $codes);
            $msg = $pid > 0 ? 'Product updated successfully.' : 'Product added successfully.';
            adminRedirectWithFlash('products', 'ok', $msg);
        }
        adminRedirectWithFlash('products', 'err', 'Failed to save product.');
    break;

    case 'AddProductImages':
        adminRequireAuth();
        require_once __DIR__.'/../common/uploadFileCloudflare.php';

        $isAjax = !empty($_POST['_ajax']);
        $pid    = (int)($_POST['product_id'] ?? 0);
        $imgFor = ($_POST['image_for'] ?? 'Product') === 'Product Mannual' ? 'Product Mannual' : 'Product';

        if ($pid <= 0) {
            if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success'=>false,'error'=>'Invalid product.']); exit; }
            adminRedirectWithFlash('products', 'warn', 'Invalid product.');
        }

        /* Accepted types: product images = JPG/PNG only; manuals = PDF + common images */
        $allowedType = ($imgFor === 'Product Mannual') ? 'pdf,jpg,jpeg,png,gif,webp' : 'jpg,jpeg,png';

        $files    = $_FILES['product_images'] ?? [];
        $uploaded = 0;
        $errors   = [];
        $savedRows = [];

        /* Build a normalised count whether PHP gave us arrays (multi-file) or scalars (single) */
        $count = 0;
        if (!empty($files['tmp_name'])) {
            $count = is_array($files['tmp_name']) ? count($files['tmp_name']) : 1;
        }

        for ($i = 0; $i < $count; $i++) {
            $f = [
                'name'     => is_array($files['name']     ?? '') ? ($files['name'][$i]     ?? '') : ($files['name']     ?? ''),
                'type'     => is_array($files['type']     ?? '') ? ($files['type'][$i]     ?? '') : ($files['type']     ?? ''),
                'tmp_name' => is_array($files['tmp_name'] ?? '') ? ($files['tmp_name'][$i] ?? '') : ($files['tmp_name'] ?? ''),
                'error'    => is_array($files['error']    ?? '') ? ($files['error'][$i]    ?? 4)  : ($files['error']    ?? 4),
                'size'     => is_array($files['size']     ?? '') ? ($files['size'][$i]     ?? 0)  : ($files['size']     ?? 0),
            ];
            $hasFile  = !empty($f['tmp_name']) && ((int)$f['error'] === UPLOAD_ERR_OK);
            $imgName  = trim((string)(($_POST['image_names'][$i]   ?? '')));
            $title    = trim((string)(($_POST['manual_titles'][$i] ?? '')));
            $hyperLnk = trim((string)(($_POST['hyper_links'][$i]   ?? '')));
            $dispFlag = in_array(($_POST['display_flags'][$i] ?? 'Yes'), ['Yes','No']) ? $_POST['display_flags'][$i] : 'Yes';
            $prioVal  = (int)(($_POST['priorities'][$i] ?? ($i + 1)));

            /* Product images require a file; manuals accept file OR direct link */
            if ($imgFor === 'Product' && !$hasFile) continue;
            if ($imgFor === 'Product Mannual' && !$hasFile && $hyperLnk === '') continue;

            $r2Key = '';
            if ($hasFile) {
                $r2 = uploadToR2($f, 'products', $allowedType);
                if ($r2['success']) {
                    $r2Key = $r2['key'];
                } else {
                    $errors[] = 'File '.($i + 1).': '.($r2['error'] ?? 'Upload failed.');
                    continue;
                }
            }

            if ($r2Key !== '' || $hyperLnk !== '') {
                try {
                    $newId = $controller->addProductImage($pid, $r2Key, $imgFor, $title, $prioVal, $imgName, $dispFlag, $hyperLnk);
                } catch (Exception $e) {
                    $errors[] = 'File '.($i + 1).': DB error — '.$e->getMessage();
                    continue;
                }
                if ($newId > 0) {
                    $uploaded++;
                    $pubBaseUrl = rtrim(sinelec_env('PUBLIC_BASE_URL'), '/');
                    $savedRows[] = [
                        'id'      => $newId,
                        'path'    => $r2Key,
                        'url'     => ($r2Key !== '' && strpos($r2Key, '/') !== false) ? $pubBaseUrl.'/'.$r2Key : '',
                        'for'     => $imgFor,
                        'name'    => $imgName,
                        'title'   => $title,
                        'hyper'   => $hyperLnk,
                        'prio'    => $prioVal,
                        'display' => $dispFlag,
                    ];
                } else {
                    $errors[] = 'File '.($i + 1).': Saved to R2 but DB insert returned 0.';
                }
            }
        }

        if ($isAjax) {
            header('Content-Type: application/json');
            if ($uploaded > 0) {
                echo json_encode(['success'=>true,'uploaded'=>$uploaded,'images'=>$savedRows,'skipped'=>$errors]);
            } else {
                $errMsg = !empty($errors) ? implode(' | ', $errors) : 'Please select a valid file or provide a direct URL.';
                echo json_encode(['success'=>false,'error'=>$errMsg,'images'=>[]]);
            }
            exit;
        }

        if ($uploaded > 0) {
            $msg = $uploaded.' file(s) saved successfully.';
            if (!empty($errors)) $msg .= ' — Skipped: '.implode('; ', $errors);
            adminRedirectWithFlash('products', 'ok', $msg);
        }

        $failMsg = !empty($errors)
            ? implode(' | ', $errors)
            : 'Please select a valid file or provide a direct URL.';
        adminRedirectWithFlash('products', 'err', $failMsg);
    break;

    case 'DeleteProductImage':
        adminRequireAuth();
        $imgId = (int)($_POST['image_id'] ?? 0);
        if ($imgId <= 0) adminRedirectWithFlash('products', 'warn', 'Invalid request.');
        $imgRow = $controller->getProductImageById($imgId);
        if ($imgRow) {
            $r2Key = (string)($imgRow->PRODUCT_IMAGE_PATH ?? '');
            if ($r2Key !== '' && strpos($r2Key, '/') !== false) {
                require_once __DIR__.'/../common/uploadFileCloudflare.php';
                deleteFromR2($r2Key);
            }
        }
        $controller->deleteProductImage($imgId);
        adminRedirectWithFlash('products', 'ok', 'Image deleted.');
    break;

    case 'DeleteProduct':
        adminRequireAuth();
        $pid = (int)($_POST['product_id'] ?? 0);
        if ($pid <= 0) adminRedirectWithFlash('products', 'warn', 'Invalid request.');
        $ok = $controller->deleteProduct($pid);
        if ($ok) adminRedirectWithFlash('products', 'ok', 'Product deleted successfully.');
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
        if ($pos === '') adminRedirectWithFlash('job-posting', 'warn', 'Job position is required.');
        $id = $controller->insertJob($_POST);
        if ($id > 0) {
            adminRedirectWithFlash('job-posting', 'ok', 'Job post added successfully.');
        }
        adminRedirectWithFlash('job-posting', 'err', 'Failed to add job post.');
    break;

    case 'UpdateJob':
        adminRequireAuth();
        $jobId = (int)($_POST['job_post_id'] ?? 0);
        if ($jobId <= 0) adminRedirectWithFlash('job-posting', 'warn', 'Invalid request.');
        $ok = $controller->updateJob($_POST);
        if ($ok) {
            adminRedirectWithFlash('job-posting', 'ok', 'Job post updated successfully.');
        }
        adminRedirectWithFlash('job-posting', 'err', 'Failed to update job post.');
    break;

    case 'DeleteJob':
        adminRequireAuth();
        $jobId = (int)($_POST['job_post_id'] ?? $_GET['id'] ?? 0);
        if ($jobId <= 0) adminRedirectWithFlash('job-posting', 'warn', 'Invalid request.');
        $ok = $controller->deleteJob($jobId);
        if ($ok) {
            adminRedirectWithFlash('job-posting', 'ok', 'Job post deleted.');
        }
        adminRedirectWithFlash('job-posting', 'err', 'Cannot delete — this post has applicants. Delete applicants first.');
    break;

    /* ─────────────────────────────────────────────────────────────
       APPLICANTS
    ───────────────────────────────────────────────────────────── */
    case 'DeleteApplicant':
        adminRequireAuth();
        $appId = (int)($_POST['candidate_applied_job_id'] ?? $_GET['id'] ?? 0);
        if ($appId <= 0) adminRedirectWithFlash('job-posting', 'warn', 'Invalid request.');
        /* Delete resume from R2 before removing DB record */
        $appRow = $controller->getApplicantById($appId);
        if ($appRow) {
            $resKey = (string)($appRow->RESUME_FILE_EXT ?? '');
            if ($resKey !== '' && strpos($resKey, '/') !== false) {
                require_once __DIR__.'/../common/uploadFileCloudflare.php';
                deleteFromR2($resKey);
            }
        }
        $controller->deleteApplicant($appId);
        adminRedirectWithFlash('job-posting', 'ok', 'Application deleted.');
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

    /* ─────────────────────────────────────────────────────────────
       MANUFACTURERS
    ───────────────────────────────────────────────────────────── */
    case 'SaveManufacturer':
        adminRequireAuth();
        require_once __DIR__.'/../common/uploadFileCloudflare.php';
        $mfrId       = (int)($_POST['manufacturer_id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $existingKey = trim($_POST['existing_logo'] ?? '');
        if ($name === '') adminRedirectWithFlash('manufacturers', 'warn', 'Manufacturer name is required.');

        $logoKey = $existingKey;
        if (!empty($_FILES['manufacturer_logo']['tmp_name'])) {
            $oldKey = ($mfrId > 0 && strpos($existingKey, '/') !== false) ? $existingKey : null;
            $r2 = $oldKey
                ? replaceR2File($_FILES['manufacturer_logo'], 'manufacturers', 'IMAGE', $oldKey, 5)
                : uploadToR2($_FILES['manufacturer_logo'], 'manufacturers', 'IMAGE');
            if (!$r2['success']) adminRedirectWithFlash('manufacturers', 'err', 'Logo upload failed: '.$r2['error']);
            $logoKey = $r2['key'];
        }

        $catIdsRaw = (array)($_POST['product_category_ids'] ?? []);
        $catIds    = implode(',', array_filter(array_map('intval', $catIdsRaw)));

        $savedId = $controller->saveManufacturer([
            'id'                   => $mfrId,
            'name'                 => $name,
            'logo'                 => $logoKey,
            'country_id'           => (int)($_POST['country_id'] ?? 0),
            'description'          => trim($_POST['description'] ?? ''),
            'product_category_ids' => $catIds,
            'status'               => (int)($_POST['status'] ?? 1),
        ]);

        if ($savedId) {
            $msg = $mfrId > 0 ? 'Manufacturer updated successfully.' : 'Manufacturer added successfully.';
            adminRedirectWithFlash('manufacturers', 'ok', $msg);
        }
        adminRedirectWithFlash('manufacturers', 'err', 'Failed to save manufacturer.');
    break;

    case 'DeleteManufacturer':
        adminRequireAuth();
        $mfrId = (int)($_POST['manufacturer_id'] ?? 0);
        if ($mfrId <= 0) adminRedirectWithFlash('manufacturers', 'warn', 'Invalid request.');
        if ($controller->deleteManufacturer($mfrId)) {
            adminRedirectWithFlash('manufacturers', 'ok', 'Manufacturer deleted successfully.');
        }
        adminRedirectWithFlash('manufacturers', 'err', 'Failed to delete manufacturer.');
    break;

    /* ─────────────────────────────────────────────────────────────
       QUOTATIONS
    ───────────────────────────────────────────────────────────── */
    case 'CreateQuoteCustomer':
        adminRequireAuth();
        header('Content-Type: application/json');
        $qcName  = trim($_POST['name']  ?? '');
        $qcEmail = trim($_POST['email'] ?? '');
        if ($qcName === '' || $qcEmail === '') {
            echo json_encode(['success'=>false,'msg'=>'Name and email are required.']); exit();
        }
        $qcResult = $controller->quickCreateCustomer([
            'name'                         => $qcName,
            'communication_email_id'       => $qcEmail,
            'communication_mobile_num'     => trim($_POST['phone']     ?? ''),
            'communication_mobile_num_isd' => (int)($_POST['phone_isd']?? 91),
            'company_name'                 => trim($_POST['company_name'] ?? ''),
            'designation'                  => trim($_POST['designation']  ?? ''),
        ]);
        if ($qcResult === -1) {
            echo json_encode(['success'=>false,'msg'=>'A customer with this email already exists.']); exit();
        } elseif ($qcResult) {
            echo json_encode([
                'success'    => true,
                'user_id'    => $qcResult,
                'user_name'  => $qcName,
                'user_email' => $qcEmail,
                'user_phone' => trim($_POST['phone']      ?? ''),
                'phone_isd'  => (int)($_POST['phone_isd'] ?? 91),
                'company'    => trim($_POST['company_name'] ?? ''),
            ]);
        } else {
            echo json_encode(['success'=>false,'msg'=>'Failed to create customer. Please try again.']);
        }
        exit();
    break;

    case 'GetUserAddresses':
        adminRequireAuth();
        header('Content-Type: application/json');
        $guaUid = (int)($_POST['user_id'] ?? 0);
        if ($guaUid <= 0) { echo json_encode([]); exit(); }
        $guaAddrs = $controller->getUserAddressesForQuote($guaUid);
        $guaResult = array_map(function($a) {
            $cn = (string)($a->COUNTRY_NAME ?? $a->COUNTRY ?? '');
            return [
                'id'        => (int)(float)($a->USER_ADDRESS_ID  ?? 0),
                'label'     => (string)($a->LABEL               ?? 'Home'),
                'name'      => (string)($a->USER_NAME            ?? ''),
                'company'   => (string)($a->COMPANY_NAME         ?? ''),
                'address'   => (string)($a->ADDRESS              ?? ''),
                'line1'     => (string)($a->ADDRESS_LINE_ONE     ?? ''),
                'line2'     => (string)($a->ADDRESS_LINE_TWO     ?? ''),
                'landmark'  => (string)($a->LANDMARK             ?? ''),
                'city'      => (string)($a->CITY                 ?? ''),
                'state'     => (string)($a->STATE                ?? ''),
                'zip'       => (string)($a->ZIP                  ?? ''),
                'country'   => $cn,
                'country_id'=> (int)(float)($a->COUNTRY_ID       ?? 0),
                'phone'     => (string)($a->DELIVERY_PHONE_NO    ?? ''),
                'mcc'       => (int)($a->MOBILE_COUNTRY_CODE     ?? 91),
                'eu_vat'    => (string)($a->EU_VAT               ?? ''),
                'rec_name'  => (string)($a->RECIPIENT_NAME       ?? ''),
                'rec_email' => (string)($a->RECIPIENT_EMAIL      ?? ''),
                'rec_phone' => (string)($a->RECIPIENT_CONTACT    ?? ''),
                'summary'   => implode(', ', array_filter([
                    (string)($a->ADDRESS ?? ''),
                    (string)($a->CITY    ?? ''),
                    (string)($a->STATE   ?? ''),
                    $cn,
                    (string)($a->ZIP     ?? ''),
                ])),
            ];
        }, $guaAddrs);
        echo json_encode($guaResult);
        exit();
    break;

    case 'SaveQuoteAddress':
        adminRequireAuth();
        header('Content-Type: application/json');
        $sqaUid = (int)($_POST['user_id'] ?? 0);
        if ($sqaUid <= 0) { echo json_encode(['success'=>false,'msg'=>'Invalid user.']); exit(); }
        $sqaId = $controller->saveUserAddress($_POST);
        if ($sqaId) {
            $sqaAddrs = $controller->getUserAddressesForQuote($sqaUid);
            $sqaSaved = null;
            foreach ($sqaAddrs as $sa) {
                if ((int)(float)($sa->USER_ADDRESS_ID ?? 0) === $sqaId) { $sqaSaved = $sa; break; }
            }
            $sqaCn = $sqaSaved ? (string)($sqaSaved->COUNTRY_NAME ?? $sqaSaved->COUNTRY ?? '') : '';
            echo json_encode([
                'success' => true,
                'id'      => $sqaId,
                'label'   => $sqaSaved ? (string)($sqaSaved->LABEL ?? 'Home') : 'Home',
                'name'    => $sqaSaved ? (string)($sqaSaved->USER_NAME   ?? '') : '',
                'company' => $sqaSaved ? (string)($sqaSaved->COMPANY_NAME?? '') : '',
                'address' => $sqaSaved ? (string)($sqaSaved->ADDRESS      ?? '') : '',
                'city'    => $sqaSaved ? (string)($sqaSaved->CITY         ?? '') : '',
                'state'   => $sqaSaved ? (string)($sqaSaved->STATE        ?? '') : '',
                'zip'     => $sqaSaved ? (string)($sqaSaved->ZIP          ?? '') : '',
                'country' => $sqaCn,
                'summary' => implode(', ', array_filter([
                    $sqaSaved ? (string)($sqaSaved->ADDRESS ?? '') : '',
                    $sqaSaved ? (string)($sqaSaved->CITY    ?? '') : '',
                    $sqaSaved ? (string)($sqaSaved->STATE   ?? '') : '',
                    $sqaCn,
                    $sqaSaved ? (string)($sqaSaved->ZIP     ?? '') : '',
                ])),
            ]);
        } else {
            echo json_encode(['success'=>false,'msg'=>'Failed to save address.']);
        }
        exit();
    break;

    case 'SaveQuotation':
        adminRequireAuth();
        $qid         = (int)($_POST['enquiry_quote_id'] ?? 0);
        $isDuplicate = !empty($_POST['is_duplicate']) && $_POST['is_duplicate'] === '1';
        $uid  = (int)($_POST['user_id']          ?? 0);
        if ($uid <= 0) adminRedirectWithFlash('quotation', 'warn', 'Please select a customer.');

        /* Build products array */
        $catIds  = (array)($_POST['prod_cat_id']  ?? []);
        $prodIds = (array)($_POST['prod_prod_id'] ?? []);
        $qtys    = (array)($_POST['prod_qty']     ?? []);
        $prices  = (array)($_POST['prod_price']   ?? []);
        $discs   = (array)($_POST['prod_disc']    ?? []);
        $products = [];
        foreach ($prodIds as $i => $pid2) {
            if ((int)$pid2 <= 0) continue;
            $products[] = [
                'cat_id'   => (int)($catIds[$i]  ?? 0),
                'prod_id'  => (int)$pid2,
                'qty'      => (int)($qtys[$i]    ?? 1),
                'price'    => (float)($prices[$i] ?? 0),
                'disc_pct' => (float)($discs[$i]  ?? 0),
            ];
        }
        if (empty($products)) adminRedirectWithFlash('quotation', 'warn', 'At least one product is required.');

        $savedId = $controller->saveQuotation($_POST);
        if ($savedId) {
            $controller->saveQuotationProducts((int)$savedId, $products);

            /* ── Resolve all data needed for email ── */
            $isNew      = $qid <= 0 || $isDuplicate;
            $savedQ     = $controller->getQuotationById((int)$savedId);
            $company    = $controller->getCompanyDetails();
            $quoteRef   = 'QT-' . str_pad((string)$savedId, 6, '0', STR_PAD_LEFT);
            $custEmail  = trim((string)($savedQ->USER_EMAIL_RESOLVED ?? $savedQ->USER_EMAIL ?? ''));
            $custName   = htmlspecialchars((string)($savedQ->USER_NAME_RESOLVED ?? $savedQ->USER_NAME ?? 'Customer'));
            $totalAmt   = number_format((float)($_POST['enquiry_total_amt'] ?? 0), 2);
            $pdfLink    = (isset($_SERVER['HTTP_HOST']) ? 'https://' . $_SERVER['HTTP_HOST'] : '')
                        . '/admin/quotation-pdf?id=' . $savedId . '&uid=' . $uid;
            $coName     = $company ? htmlspecialchars((string)($company->NAME ?? 'Our Company')) : 'Our Company';

            /* Recipient email from delivery address (if set) */
            $recEmail = '';
            $addrId2  = (int)($_POST['user_address_id'] ?? 0);
            if ($addrId2 > 0 && $uid > 0) {
                $addrs2 = $controller->getUserAddressesForQuote($uid);
                foreach ($addrs2 as $a2) {
                    if ((int)(float)($a2->USER_ADDRESS_ID ?? 0) === $addrId2) {
                        $recEmail = trim((string)($a2->RECIPIENT_EMAIL ?? ''));
                        break;
                    }
                }
            }

            /* Support emails from tbl_company (comma-separated) */
            $supportEmails = [];
            if ($company) {
                $raw = trim((string)($company->SUPPORT_MAIL_ID ?? ''));
                if ($raw !== '') {
                    $supportEmails = array_filter(array_map('trim', explode(',', $raw)));
                }
            }

            /* ── Build professional email bodies ── */
            $coEmailDisp  = $company ? htmlspecialchars((string)($company->EMAIL           ?? '')) : '';
            $coPhoneDisp  = $company ? htmlspecialchars((string)($company->CONTACT_NUMBER  ?? '')) : '';
            $coLogoAbsUrl = $company ? trim((string)($company->LOGO ?? '')) : '';
            $actionLabel  = $isNew ? 'created' : 'updated';
            $coContactHtml = '';
            if ($coEmailDisp) $coContactHtml .= '<a href="mailto:'.$coEmailDisp.'" style="color:#6366f1;text-decoration:none;">'.$coEmailDisp.'</a>';
            if ($coEmailDisp && $coPhoneDisp) $coContactHtml .= ' &nbsp;|&nbsp; ';
            if ($coPhoneDisp) $coContactHtml .= htmlspecialchars($coPhoneDisp);

            /* Customer-facing email */
            $bodyCustomer = '
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:\'Segoe UI\',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:32px 16px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

  <!-- Header -->
  <tr><td style="background:linear-gradient(135deg,#4f46e5 0%,#6366f1 100%);border-radius:12px 12px 0 0;padding:32px 40px;text-align:center;">
    '.($coLogoAbsUrl ? '<img src="'.$coLogoAbsUrl.'" alt="'.$coName.'" style="max-height:52px;max-width:180px;object-fit:contain;filter:brightness(0) invert(1);margin-bottom:12px;display:block;margin-left:auto;margin-right:auto;">' : '').'
    <div style="color:#fff;font-size:22px;font-weight:700;letter-spacing:.5px;">'.($coLogoAbsUrl ? '' : $coName).'</div>
    <div style="color:rgba(255,255,255,.75);font-size:13px;margin-top:4px;">Quotation '.($isNew ? 'Confirmation' : 'Update').'</div>
  </td></tr>

  <!-- Body -->
  <tr><td style="background:#ffffff;padding:40px 40px 32px;">
    <p style="margin:0 0 20px;font-size:16px;font-weight:700;color:#1e293b;">Dear '.$custName.',</p>
    <p style="margin:0 0 16px;font-size:14px;color:#475569;line-height:1.7;">
      '.($isNew
        ? 'We are pleased to share your quotation from <strong>'.$coName.'</strong>. Please find the details of your requested quotation below. We have carefully prepared this based on your requirements and look forward to your confirmation.'
        : 'This is to inform you that your quotation with <strong>'.$coName.'</strong> has been <strong>revised and updated</strong>. Please review the latest details below.').'
    </p>

    <!-- Quote Card -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;margin:24px 0;">
      <tr>
        <td style="padding:20px 24px;border-bottom:1px solid #e2e8f0;">
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:4px;">Quotation Reference</div>
          <div style="font-size:20px;font-weight:800;color:#4f46e5;">'.$quoteRef.'</div>
        </td>
        <td style="padding:20px 24px;border-bottom:1px solid #e2e8f0;text-align:right;">
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:4px;">Total Amount</div>
          <div style="font-size:22px;font-weight:800;color:#059669;">€'.$totalAmt.'</div>
        </td>
      </tr>
      <tr>
        <td colspan="2" style="padding:16px 24px;text-align:center;">
          <a href="'.$pdfLink.'" target="_blank"
             style="display:inline-block;background:#4f46e5;color:#ffffff;font-size:14px;font-weight:600;padding:12px 32px;border-radius:8px;text-decoration:none;letter-spacing:.3px;">
            &#128196; View &amp; Download Quotation
          </a>
        </td>
      </tr>
    </table>

    <p style="margin:0 0 12px;font-size:13px;color:#64748b;line-height:1.7;">
      If you have any questions about this quotation or wish to make changes, please do not hesitate to contact us.
      We are happy to assist you and ensure the best possible solution for your needs.
    </p>
    <p style="margin:0;font-size:13px;color:#64748b;line-height:1.7;">
      To accept this quotation, simply reply to this email or reach out to us directly. We look forward to the opportunity to work with you.
    </p>
  </td></tr>

  <!-- Sign-off -->
  <tr><td style="background:#f8fafc;padding:24px 40px;border-top:1px solid #e2e8f0;">
    <p style="margin:0 0 4px;font-size:13px;color:#475569;">Warm regards,</p>
    <p style="margin:0 0 4px;font-size:14px;font-weight:700;color:#1e293b;">'.$coName.'</p>
    '.($coContactHtml ? '<p style="margin:0;font-size:12px;color:#94a3b8;">'.$coContactHtml.'</p>' : '').'
  </td></tr>

  <!-- Footer -->
  <tr><td style="background:#e2e8f0;border-radius:0 0 12px 12px;padding:14px 40px;text-align:center;">
    <p style="margin:0;font-size:11px;color:#94a3b8;">
      This email was sent by '.$coName.' regarding quotation <strong>'.$quoteRef.'</strong>.<br>
      Please do not reply directly to this automated message.
    </p>
  </td></tr>

</table>
</td></tr></table>
</body></html>';

            /* Internal / support-team email */
            $bodyInternal = '
<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:\'Segoe UI\',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 16px;">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#fff;border-radius:10px;overflow:hidden;border:1px solid #e2e8f0;">
  <tr><td style="background:'.($isNew ? '#059669' : '#d97706').';padding:18px 28px;">
    <div style="color:#fff;font-size:15px;font-weight:700;">&#128276; Quotation '.($isNew ? 'Created' : 'Updated').': '.$quoteRef.'</div>
  </td></tr>
  <tr><td style="padding:24px 28px;">
    <table width="100%" cellpadding="0" cellspacing="0">
      <tr>
        <td style="font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;padding-bottom:4px;">Customer</td>
        <td style="font-size:13px;font-weight:700;color:#1e293b;padding-bottom:4px;">'.$custName.'</td>
      </tr>
      <tr>
        <td style="font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;padding-bottom:4px;">Reference</td>
        <td style="font-size:13px;color:#4f46e5;font-weight:700;padding-bottom:4px;">'.$quoteRef.'</td>
      </tr>
      <tr>
        <td style="font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;padding-bottom:4px;">Total Amount</td>
        <td style="font-size:13px;font-weight:700;color:#059669;padding-bottom:4px;">€'.$totalAmt.'</td>
      </tr>
      <tr>
        <td style="font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;padding-bottom:16px;">Action</td>
        <td style="font-size:13px;color:#475569;padding-bottom:16px;">'.($isNew ? 'New quotation has been created and sent to the customer.' : 'Existing quotation has been updated and customer has been notified.').'</td>
      </tr>
    </table>
    <a href="'.$pdfLink.'" target="_blank" style="display:inline-block;background:#4f46e5;color:#fff;font-size:13px;font-weight:600;padding:10px 24px;border-radius:7px;text-decoration:none;">View Quotation PDF &rarr;</a>
  </td></tr>
  <tr><td style="background:#f8fafc;padding:12px 28px;border-top:1px solid #e2e8f0;">
    <p style="margin:0;font-size:11px;color:#94a3b8;">Internal notification — '.$coName.' Quotation System</p>
  </td></tr>
</table>
</td></tr></table>
</body></html>';

            /* ── Collect recipients — deduplicate by email ── */
            $sentTo     = [];
            $recipients = [];

            // 1. Customer / user email
            if ($custEmail !== '' && !in_array(strtolower($custEmail), $sentTo)) {
                $sentTo[] = strtolower($custEmail);
                $recipients[] = ['to_mail_id'=>$custEmail, 'subject'=>($isNew ? 'Your Quotation is Ready' : 'Your Quotation Has Been Updated').' — '.$quoteRef.' | '.$coName, 'body'=>$bodyCustomer];
            }

            // 2. Recipient email from delivery address
            if ($recEmail !== '' && !in_array(strtolower($recEmail), $sentTo)) {
                $sentTo[] = strtolower($recEmail);
                $recipients[] = ['to_mail_id'=>$recEmail, 'subject'=>($isNew ? 'Your Quotation is Ready' : 'Your Quotation Has Been Updated').' — '.$quoteRef.' | '.$coName, 'body'=>$bodyCustomer];
            }

            // 3. Company support email(s)
            foreach ($supportEmails as $smail) {
                if ($smail !== '' && !in_array(strtolower($smail), $sentTo)) {
                    $sentTo[] = strtolower($smail);
                    $recipients[] = ['to_mail_id'=>$smail, 'subject'=>'['.($isNew ? 'New' : 'Updated').'] Quotation '.$quoteRef.' — '.$custName, 'body'=>$bodyInternal];
                }
            }

            if (!empty($recipients)) sinelec_send_mail($recipients);

            $flashMsg = $isDuplicate
                ? 'New quotation ' . $quoteRef . ' created from duplicate and email sent.'
                : ($qid > 0 ? 'Quotation updated successfully.' : 'Quotation created and email sent.');
            adminRedirectWithFlash('quotation', 'ok', $flashMsg);
        }
        adminRedirectWithFlash('quotation', 'err', 'Failed to save quotation.');
    break;

    case 'DeleteQuotation':
        adminRequireAuth();
        $qid = (int)($_POST['enquiry_quote_id'] ?? 0);
        if ($qid <= 0) adminRedirectWithFlash('quotation', 'warn', 'Invalid request.');
        if ($controller->deleteQuotation($qid)) {
            adminRedirectWithFlash('quotation', 'ok', 'Quotation deleted successfully.');
        }
        adminRedirectWithFlash('quotation', 'err', 'Failed to delete quotation.');
    break;

    case 'UpdateQuotationStatus':
        adminRequireAuth();
        $qid    = (int)($_POST['enquiry_quote_id'] ?? 0);
        $status = trim($_POST['enquiry_status'] ?? '');
        $remark = trim($_POST['remark'] ?? '');
        if ($qid <= 0 || $status === '') adminRedirectWithFlash('quotation', 'warn', 'Invalid request.');
        if ($status === 'Quotation Cancel' && $remark === '')
            adminRedirectWithFlash('quotation', 'warn', 'Please provide a reason for cancellation.');

        if (!$controller->updateQuotationStatus($qid, $status, $remark)) {
            adminRedirectWithFlash('quotation', 'err', 'Failed to update status.');
        }

        /* ── Resolve quotation & company data ─────────────────────── */
        $usQ        = $controller->getQuotationById($qid);
        $usComp     = $controller->getCompanyDetails();

        $usQuoteRef = 'QT-' . str_pad((string)$qid, 6, '0', STR_PAD_LEFT);
        $usCustName = htmlspecialchars((string)($usQ->USER_NAME_RESOLVED  ?? $usQ->USER_NAME  ?? 'Customer'));
        $usCustEmail= (string)($usQ->USER_EMAIL_RESOLVED ?? $usQ->USER_EMAIL ?? '');
        $usUid      = (int)(float)($usQ->USER_ID    ?? 0);
        $usAddrId   = (int)(float)($usQ->USER_ADDRESS_ID ?? 0);
        $usTotalAmt = number_format((float)($usQ->ENQUIRY_TOTAL_AMT ?? 0), 2);
        $usPdfLink  = (isset($_SERVER['HTTP_HOST']) ? 'https://' . $_SERVER['HTTP_HOST'] : '')
                    . '/admin/quotation-pdf?id=' . $qid . '&uid=' . $usUid;

        $usCoName      = $usComp ? htmlspecialchars((string)($usComp->NAME           ?? 'Our Company')) : 'Our Company';
        $usCoEmail     = $usComp ? htmlspecialchars((string)($usComp->EMAIL          ?? '')) : '';
        $usCoPhone     = $usComp ? htmlspecialchars((string)($usComp->CONTACT_NUMBER ?? '')) : '';
        $usCoLogoUrl   = $usComp ? trim((string)($usComp->LOGO ?? '')) : '';

        $usCoContactHtml = '';
        if ($usCoEmail) $usCoContactHtml .= '<a href="mailto:'.$usCoEmail.'" style="color:#6366f1;text-decoration:none;">'.$usCoEmail.'</a>';
        if ($usCoEmail && $usCoPhone) $usCoContactHtml .= ' &nbsp;|&nbsp; ';
        if ($usCoPhone) $usCoContactHtml .= $usCoPhone;

        /* ── Recipient email from delivery address ──────────────────── */
        $usRecEmail = '';
        if ($usAddrId > 0 && $usUid > 0) {
            $usAddrs = $controller->getUserAddressesForQuote($usUid);
            foreach ($usAddrs as $ua) {
                if ((int)(float)($ua->USER_ADDRESS_ID ?? 0) === $usAddrId) {
                    $usRecEmail = trim((string)($ua->RECIPIENT_EMAIL ?? ''));
                    break;
                }
            }
        }

        /* ── Support emails ─────────────────────────────────────────── */
        $usSupportEmails = [];
        if ($usComp) {
            $usRaw = trim((string)($usComp->SUPPORT_MAIL_ID ?? ''));
            if ($usRaw !== '') $usSupportEmails = array_filter(array_map('trim', explode(',', $usRaw)));
        }

        /* ── Status badge styling ───────────────────────────────────── */
        $usStatusLower = strtolower($status);
        $usStatusColor = match(true) {
            str_contains($usStatusLower, 'complet')  => ['bg'=>'#dcfce7','text'=>'#15803d','border'=>'#86efac'],
            str_contains($usStatusLower, 'sent')      => ['bg'=>'#dbeafe','text'=>'#1d4ed8','border'=>'#93c5fd'],
            str_contains($usStatusLower, 'generat')   => ['bg'=>'#ede9fe','text'=>'#6d28d9','border'=>'#c4b5fd'],
            str_contains($usStatusLower, 'cancel')    => ['bg'=>'#fee2e2','text'=>'#b91c1c','border'=>'#fca5a5'],
            str_contains($usStatusLower, 'approv')    => ['bg'=>'#dcfce7','text'=>'#15803d','border'=>'#86efac'],
            str_contains($usStatusLower, 'review')    => ['bg'=>'#fef3c7','text'=>'#92400e','border'=>'#fcd34d'],
            default                                    => ['bg'=>'#fef3c7','text'=>'#92400e','border'=>'#fcd34d'],
        };
        $usStatusBadge = '<span style="display:inline-block;background:'.$usStatusColor['bg'].';color:'.$usStatusColor['text'].
            ';border:1px solid '.$usStatusColor['border'].';font-size:13px;font-weight:700;padding:5px 16px;border-radius:20px;letter-spacing:.3px;">'.
            htmlspecialchars($status).'</span>';

        /* ── Cancellation flag & remark block ─────────────────────── */
        $usIsCancel    = ($status === 'Quotation Cancel');
        $usRemarkSafe  = htmlspecialchars($remark);
        $usHeaderBg    = $usIsCancel
                       ? 'background:linear-gradient(135deg,#b91c1c 0%,#dc2626 100%)'
                       : 'background:linear-gradient(135deg,#4f46e5 0%,#6366f1 100%)';
        $usHeaderSub   = $usIsCancel ? 'Quotation Cancellation Notice' : 'Quotation Status Update';

        /* Remark block for customer email */
        $usRemarkBlockCust = '';
        if ($usIsCancel && $usRemarkSafe !== '') {
            $usRemarkBlockCust = '
    <!-- Cancellation Reason -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#fff5f5;border:1px solid #fecaca;border-radius:10px;margin:0 0 20px;">
      <tr>
        <td style="padding:16px 20px;">
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#b91c1c;margin-bottom:8px;">
            &#9888; Reason for Cancellation
          </div>
          <div style="font-size:14px;color:#7f1d1d;line-height:1.7;">'.$usRemarkSafe.'</div>
        </td>
      </tr>
    </table>';
        }

        /* Remark row for internal email */
        $usRemarkRowInt = '';
        if ($usIsCancel && $usRemarkSafe !== '') {
            $usRemarkRowInt = '
      <tr>
        <td style="font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;padding-bottom:4px;vertical-align:top;">Cancellation Reason</td>
        <td style="font-size:13px;color:#7f1d1d;background:#fff5f5;border-radius:6px;padding:8px 10px;margin-bottom:16px;line-height:1.6;">'.$usRemarkSafe.'</td>
      </tr>';
        }

        $usCancelClosingCust = $usIsCancel
            ? '<p style="margin:0 0 12px;font-size:13px;color:#64748b;line-height:1.7;">We apologise for any inconvenience caused. If you believe this is an error or wish to discuss further, please contact us and we will be happy to assist you.</p>
               <p style="margin:0;font-size:13px;color:#64748b;line-height:1.7;">We hope to have the opportunity to serve you again in the future.</p>'
            : '<p style="margin:0 0 12px;font-size:13px;color:#64748b;line-height:1.7;">If you have any questions regarding this status change or need further assistance, please do not hesitate to reach out to us. Our team is always ready to help.</p>
               <p style="margin:0;font-size:13px;color:#64748b;line-height:1.7;">Thank you for your continued trust and business. We look forward to serving you.</p>';

        $usInternalHeaderBg = $usIsCancel ? 'background:#b91c1c' : 'background:#7c3aed';
        $usInternalIcon     = $usIsCancel ? '&#10060;' : '&#128204;';
        $usInternalTitle    = $usIsCancel ? 'Quotation Cancelled: '.$usQuoteRef : 'Status Updated: '.$usQuoteRef;

        /* ── Customer-facing email ──────────────────────────────────── */
        $usBodyCustomer = '
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:\'Segoe UI\',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:32px 16px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

  <!-- Header -->
  <tr><td style="'.$usHeaderBg.';border-radius:12px 12px 0 0;padding:32px 40px;text-align:center;">
    '.($usCoLogoUrl ? '<img src="'.$usCoLogoUrl.'" alt="'.$usCoName.'" style="max-height:52px;max-width:180px;object-fit:contain;filter:brightness(0) invert(1);margin-bottom:12px;display:block;margin-left:auto;margin-right:auto;">' : '').'
    <div style="color:#fff;font-size:22px;font-weight:700;letter-spacing:.5px;">'.($usCoLogoUrl ? '' : $usCoName).'</div>
    <div style="color:rgba(255,255,255,.75);font-size:13px;margin-top:4px;">'.$usHeaderSub.'</div>
  </td></tr>

  <!-- Body -->
  <tr><td style="background:#ffffff;padding:40px 40px 32px;">
    <p style="margin:0 0 20px;font-size:16px;font-weight:700;color:#1e293b;">Dear '.$usCustName.',</p>
    <p style="margin:0 0 16px;font-size:14px;color:#475569;line-height:1.7;">
      '.($usIsCancel
          ? 'We regret to inform you that your quotation with <strong>'.$usCoName.'</strong> has been <strong style="color:#dc2626;">cancelled</strong>. Please find the details below.'
          : 'We would like to inform you that the status of your quotation with <strong>'.$usCoName.'</strong> has been updated. Please find the latest details below.').'
    </p>

    <!-- Quote Card -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;margin:24px 0;">
      <tr>
        <td style="padding:20px 24px;border-bottom:1px solid #e2e8f0;">
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:4px;">Quotation Reference</div>
          <div style="font-size:20px;font-weight:800;color:#4f46e5;">'.$usQuoteRef.'</div>
        </td>
        <td style="padding:20px 24px;border-bottom:1px solid #e2e8f0;text-align:right;">
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:4px;">Total Amount</div>
          <div style="font-size:22px;font-weight:800;color:#059669;">€'.$usTotalAmt.'</div>
        </td>
      </tr>
      <tr>
        <td colspan="2" style="padding:16px 24px;border-bottom:1px solid #e2e8f0;">
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:8px;">Current Status</div>
          '.$usStatusBadge.'
        </td>
      </tr>
      '.($usIsCancel ? '' : '
      <tr>
        <td colspan="2" style="padding:16px 24px;text-align:center;">
          <a href="'.$usPdfLink.'" target="_blank"
             style="display:inline-block;background:#4f46e5;color:#ffffff;font-size:13px;font-weight:600;padding:10px 22px;border-radius:8px;text-decoration:none;letter-spacing:.3px;">
            &#128196; View Quotation
          </a>
        </td>
      </tr>').'
    </table>

    '.$usRemarkBlockCust.'
    '.$usCancelClosingCust.'
  </td></tr>

  <!-- Sign-off -->
  <tr><td style="background:#f8fafc;padding:24px 40px;border-top:1px solid #e2e8f0;">
    <p style="margin:0 0 4px;font-size:13px;color:#475569;">Warm regards,</p>
    <p style="margin:0 0 4px;font-size:14px;font-weight:700;color:#1e293b;">'.$usCoName.'</p>
    '.($usCoContactHtml ? '<p style="margin:0;font-size:12px;color:#94a3b8;">'.$usCoContactHtml.'</p>' : '').'
  </td></tr>

  <!-- Footer -->
  <tr><td style="background:#e2e8f0;border-radius:0 0 12px 12px;padding:14px 40px;text-align:center;">
    <p style="margin:0;font-size:11px;color:#94a3b8;">
      This is an automated notification from '.$usCoName.' regarding quotation <strong>'.$usQuoteRef.'</strong>.<br>
      Please do not reply directly to this message.
    </p>
  </td></tr>

</table>
</td></tr></table>
</body></html>';

        /* ── Internal notification email ────────────────────────────── */
        $usBodyInternal = '
<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:\'Segoe UI\',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 16px;">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#fff;border-radius:10px;overflow:hidden;border:1px solid #e2e8f0;">
  <tr><td style="'.$usInternalHeaderBg.';padding:18px 28px;">
    <div style="color:#fff;font-size:15px;font-weight:700;">'.$usInternalIcon.' '.$usInternalTitle.'</div>
  </td></tr>
  <tr><td style="padding:24px 28px;">
    <table width="100%" cellpadding="0" cellspacing="0">
      <tr>
        <td style="font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;padding-bottom:4px;width:40%;">Customer</td>
        <td style="font-size:13px;font-weight:700;color:#1e293b;padding-bottom:4px;">'.$usCustName.'</td>
      </tr>
      <tr>
        <td style="font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;padding-bottom:4px;">Reference</td>
        <td style="font-size:13px;color:#4f46e5;font-weight:700;padding-bottom:4px;">'.$usQuoteRef.'</td>
      </tr>
      <tr>
        <td style="font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;padding-bottom:4px;">Total Amount</td>
        <td style="font-size:13px;font-weight:700;color:#059669;padding-bottom:4px;">€'.$usTotalAmt.'</td>
      </tr>
      <tr>
        <td style="font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;padding-bottom:16px;">New Status</td>
        <td style="padding-bottom:16px;">'.$usStatusBadge.'</td>
      </tr>
      '.$usRemarkRowInt.'
    </table>
    <a href="'.$usPdfLink.'" target="_blank" style="display:inline-block;background:#4f46e5;color:#fff;font-size:13px;font-weight:600;padding:10px 24px;border-radius:7px;text-decoration:none;">View Quotation PDF &rarr;</a>
  </td></tr>
  <tr><td style="background:#f8fafc;padding:12px 28px;border-top:1px solid #e2e8f0;">
    <p style="margin:0;font-size:11px;color:#94a3b8;">Internal notification — '.$usCoName.' Quotation System</p>
  </td></tr>
</table>
</td></tr></table>
</body></html>';

        /* ── Deduplicate & dispatch ──────────────────────────────────── */
        $usSentTo = []; $usRecips = [];

        $usCustSubject = $usIsCancel
            ? 'Your Quotation '.$usQuoteRef.' Has Been Cancelled | '.$usCoName
            : 'Your Quotation Status Update — '.$usQuoteRef.' | '.$usCoName;
        $usIntSubject  = $usIsCancel
            ? '[Cancelled] Quotation '.$usQuoteRef.' — '.$usCustName
            : '[Status: '.htmlspecialchars($status).'] Quotation '.$usQuoteRef.' — '.$usCustName;

        /* 1. Customer email (customer-facing body) */
        if ($usCustEmail !== '' && !in_array(strtolower($usCustEmail), $usSentTo)) {
            $usSentTo[] = strtolower($usCustEmail);
            $usRecips[] = ['to_mail_id'=>$usCustEmail, 'subject'=>$usCustSubject, 'body'=>$usBodyCustomer];
        }
        /* 2. Recipient email (customer-facing body) */
        if ($usRecEmail !== '' && !in_array(strtolower($usRecEmail), $usSentTo)) {
            $usSentTo[] = strtolower($usRecEmail);
            $usRecips[] = ['to_mail_id'=>$usRecEmail, 'subject'=>$usCustSubject, 'body'=>$usBodyCustomer];
        }
        /* 3. Support / company emails (internal body) */
        foreach ($usSupportEmails as $usSmail) {
            if ($usSmail !== '' && !in_array(strtolower($usSmail), $usSentTo)) {
                $usSentTo[] = strtolower($usSmail);
                $usRecips[] = ['to_mail_id'=>$usSmail, 'subject'=>$usIntSubject, 'body'=>$usBodyInternal];
            }
        }

        if (!empty($usRecips)) sinelec_send_mail($usRecips);

        $usNotified = implode(', ', array_map('htmlspecialchars', array_slice($usSentTo, 0, 2)));
        adminRedirectWithFlash('quotation', 'ok',
            'Status updated to "' . htmlspecialchars($status) . '"'
            . ($usNotified ? ' &amp; notification sent to ' . $usNotified : '') . '.');
    break;

    case 'GenerateOrder':
        adminRequireAuth();
        $goQid      = (int)($_POST['enquiry_quote_id'] ?? 0);
        $goPayMode  = trim($_POST['order_mode'] ?? '');
        $goAdminUid = (int)($_SESSION['sinelec_admin']['USER_ID'] ?? 0);

        $validModes = ['Payment Gateway', 'Bank Transfer', 'Invoice'];
        if ($goQid <= 0)                           adminRedirectWithFlash('quotation', 'warn', 'Invalid request.');
        if (!in_array($goPayMode, $validModes))    adminRedirectWithFlash('quotation', 'warn', 'Please select a valid payment method.');

        /* Guard: no duplicate orders */
        $goExisting = $controller->getQuotationIdsWithOrders();
        if (in_array($goQid, $goExisting))         adminRedirectWithFlash('quotation', 'warn', 'An order already exists for this quotation.');

        /* Load quotation + products */
        $goQ = $controller->getQuotationById($goQid);
        if (!$goQ)                                 adminRedirectWithFlash('quotation', 'err', 'Quotation not found.');

        $goItems = $controller->getQuotationProducts($goQid);

        /* Build totals from quote row */
        $goTotalProd = 0;
        foreach ($goItems as $gi) {
            $qty     = (float)($gi->PRODUCT_QUANTITY    ?? 1);
            $unitAmt = (float)($gi->PRODUCT_AMT         ?? 0);
            $discPct = (float)($gi->PRODUCT_DISCOUNT_PCT ?? 0);
            $goTotalProd += round($unitAmt * (1 - $discPct / 100) * $qty, 2);
        }

        $goNewId = $controller->generateOrderFromQuotation([
            'enquiry_quote_id'     => $goQid,
            'user_id'              => (int)(float)($goQ->USER_ID             ?? 0),
            'user_address_id'      => (int)(float)($goQ->USER_ADDRESS_ID     ?? 0),
            'billing_address_id'   => (int)(float)($goQ->BILLING_ADDRESS_ID  ?? 0),
            'order_mode'           => $goPayMode,
            'customer_po_id'       => (string)($goQ->CUSTOMER_ORDER_NO      ?? ''),
            'customer_supplier_no' => (string)($goQ->CUSTOMER_SUPPLIER_NO   ?? ''),
            'order_total_amt'      => $goTotalProd,
            'shipping_amt'         => (float)($goQ->ENQUIRY_SHIPPING_AMT    ?? 0),
            'discount_amt'         => (float)($goQ->DISCOUNT_AMT            ?? 0),
            'tax_total_amount'     => (float)($goQ->ENQUIRY_VAT_AMT         ?? 0),
            'final_total_amt'      => (float)($goQ->ENQUIRY_TOTAL_AMT       ?? 0),
            'changed_by_user_id'   => $goAdminUid,
            'items'                => array_map(fn($gi) => [
                'product_category_id' => (int)(float)($gi->PRODUCT_CATEGORY_ID  ?? 0),
                'product_id'          => (int)(float)($gi->PRODUCT_ID           ?? 0),
                'product_quantity'    => (float)($gi->PRODUCT_QUANTITY           ?? 1),
                'product_amt'         => (float)($gi->PRODUCT_AMT               ?? 0),
                'product_discount_pct'=> (float)($gi->PRODUCT_DISCOUNT_PCT      ?? 0),
            ], $goItems),
        ]);

        if ($goNewId <= 0) adminRedirectWithFlash('quotation', 'err', 'Failed to generate order. Please try again.');

        $goOrderNo  = 'ORD-'.date('Y').'-'.str_pad((string)$goNewId, 6, '0', STR_PAD_LEFT);
        $goQuoteRef = 'QT-'.str_pad((string)$goQid, 6, '0', STR_PAD_LEFT);

        /* ── Resolve company + recipient email ─────────────────── */
        $goComp     = $controller->getCompanyDetails();
        $goUid      = (int)(float)($goQ->USER_ID         ?? 0);
        $goAddrId   = (int)(float)($goQ->USER_ADDRESS_ID ?? 0);
        $goCustName = htmlspecialchars((string)($goQ->USER_NAME_RESOLVED  ?? $goQ->USER_NAME  ?? 'Customer'));
        $goCustEmail= (string)($goQ->USER_EMAIL_RESOLVED ?? $goQ->USER_EMAIL ?? '');
        $goTotalFmt = '€'.number_format((float)($goQ->ENQUIRY_TOTAL_AMT ?? 0), 2);

        $goCoName    = $goComp ? htmlspecialchars((string)($goComp->NAME           ?? 'Our Company')) : 'Our Company';
        $goCoEmail   = $goComp ? (string)($goComp->EMAIL          ?? '') : '';
        $goCoPhone   = $goComp ? (string)($goComp->CONTACT_NUMBER ?? '') : '';
        $goCoLogoUrl = $goComp ? trim((string)($goComp->LOGO ?? '')) : '';

        $goCoContactHtml = '';
        if ($goCoEmail) $goCoContactHtml .= '<a href="mailto:'.htmlspecialchars($goCoEmail).'" style="color:#6366f1;text-decoration:none;">'.htmlspecialchars($goCoEmail).'</a>';
        if ($goCoEmail && $goCoPhone) $goCoContactHtml .= ' &nbsp;|&nbsp; ';
        if ($goCoPhone) $goCoContactHtml .= htmlspecialchars($goCoPhone);

        $goRecEmail = '';
        if ($goAddrId > 0 && $goUid > 0) {
            $goAddrs = $controller->getUserAddressesForQuote($goUid);
            foreach ($goAddrs as $ga) {
                if ((int)(float)($ga->USER_ADDRESS_ID ?? 0) === $goAddrId) {
                    $goRecEmail = trim((string)($ga->RECIPIENT_EMAIL ?? ''));
                    break;
                }
            }
        }

        $goSupportEmails = [];
        if ($goComp) {
            $goRaw = trim((string)($goComp->SUPPORT_MAIL_ID ?? ''));
            if ($goRaw !== '') $goSupportEmails = array_filter(array_map('trim', explode(',', $goRaw)));
        }

        $goOrderStatus  = ($goPayMode === 'Invoice') ? 'Order Confirmed' : 'Order Pending';
        $goPayStatus    = ($goPayMode === 'Invoice') ? 'Not Required'    : 'Payment Pending';

        /* ── Status badge colours ──────────────────────────────── */
        $goOsBg   = ($goPayMode === 'Invoice') ? '#dcfce7' : '#dbeafe';
        $goOsTxt  = ($goPayMode === 'Invoice') ? '#15803d' : '#1d4ed8';
        $goOsBdr  = ($goPayMode === 'Invoice') ? '#86efac' : '#93c5fd';
        $goPsBg   = ($goPayMode === 'Invoice') ? '#f3f4f6' : '#fef3c7';
        $goPsTxt  = ($goPayMode === 'Invoice') ? '#6b7280' : '#92400e';
        $goPsBdr  = ($goPayMode === 'Invoice') ? '#d1d5db' : '#fcd34d';

        $goOrderBadge = '<span style="display:inline-block;background:'.$goOsBg.';color:'.$goOsTxt.';border:1px solid '.$goOsBdr.';font-size:12px;font-weight:700;padding:4px 14px;border-radius:20px;">'.$goOrderStatus.'</span>';
        $goPayBadge   = '<span style="display:inline-block;background:'.$goPsBg.';color:'.$goPsTxt.';border:1px solid '.$goPsBdr.';font-size:12px;font-weight:700;padding:4px 14px;border-radius:20px;">'.$goPayStatus.'</span>';

        $goModeIcon = match($goPayMode) {
            'Invoice'         => '&#128196;',
            'Bank Transfer'   => '&#127968;',
            'Payment Gateway' => '&#128179;',
            default           => '&#9679;',
        };

        /* ── Customer-facing email ─────────────────────────────── */
        $goBodyCustomer = '
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:\'Segoe UI\',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:32px 16px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

  <!-- Header -->
  <tr><td style="background:linear-gradient(135deg,#059669 0%,#10b981 100%);border-radius:12px 12px 0 0;padding:32px 40px;text-align:center;">
    '.($goCoLogoUrl ? '<img src="'.$goCoLogoUrl.'" alt="'.$goCoName.'" style="max-height:52px;max-width:180px;object-fit:contain;filter:brightness(0) invert(1);margin-bottom:12px;display:block;margin-left:auto;margin-right:auto;">' : '').'
    <div style="color:#fff;font-size:22px;font-weight:700;letter-spacing:.5px;">'.($goCoLogoUrl ? '' : $goCoName).'</div>
    <div style="color:rgba(255,255,255,.8);font-size:13px;margin-top:4px;">&#9989; Order Confirmation</div>
  </td></tr>

  <!-- Body -->
  <tr><td style="background:#ffffff;padding:40px 40px 32px;">
    <p style="margin:0 0 20px;font-size:16px;font-weight:700;color:#1e293b;">Dear '.$goCustName.',</p>
    <p style="margin:0 0 20px;font-size:14px;color:#475569;line-height:1.7;">
      Great news! Your order has been successfully generated from quotation <strong style="color:#4f46e5;">'.$goQuoteRef.'</strong>.
      Please find your order details below.
    </p>

    <!-- Order Card -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;margin:0 0 24px;">
      <tr>
        <td style="padding:16px 24px;border-bottom:1px solid #e2e8f0;">
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:4px;">Order Number</div>
          <div style="font-size:20px;font-weight:800;color:#059669;">'.$goOrderNo.'</div>
        </td>
        <td style="padding:16px 24px;border-bottom:1px solid #e2e8f0;text-align:right;">
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:4px;">Total Amount</div>
          <div style="font-size:20px;font-weight:800;color:#059669;">'.$goTotalFmt.'</div>
        </td>
      </tr>
      <tr>
        <td style="padding:14px 24px;border-bottom:1px solid #e2e8f0;">
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:6px;">Order Status</div>
          '.$goOrderBadge.'
        </td>
        <td style="padding:14px 24px;border-bottom:1px solid #e2e8f0;">
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:6px;">Payment Status</div>
          '.$goPayBadge.'
        </td>
      </tr>
      <tr>
        <td colspan="2" style="padding:14px 24px;">
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:4px;">Payment Method</div>
          <div style="font-size:13px;font-weight:600;color:#1e293b;">'.$goModeIcon.' '.$goPayMode.'</div>
        </td>
      </tr>
    </table>

    <p style="margin:0 0 12px;font-size:13px;color:#64748b;line-height:1.7;">
      We will keep you updated as your order progresses. If you have any questions, please do not hesitate to contact us.
    </p>
    <p style="margin:0;font-size:13px;color:#64748b;line-height:1.7;">Thank you for your business. We look forward to serving you.</p>
  </td></tr>

  <!-- Sign-off -->
  <tr><td style="background:#f8fafc;padding:24px 40px;border-top:1px solid #e2e8f0;">
    <p style="margin:0 0 4px;font-size:13px;color:#475569;">Warm regards,</p>
    <p style="margin:0 0 4px;font-size:14px;font-weight:700;color:#1e293b;">'.$goCoName.'</p>
    '.($goCoContactHtml ? '<p style="margin:0;font-size:12px;color:#94a3b8;">'.$goCoContactHtml.'</p>' : '').'
  </td></tr>

  <!-- Footer -->
  <tr><td style="background:#e2e8f0;border-radius:0 0 12px 12px;padding:14px 40px;text-align:center;">
    <p style="margin:0;font-size:11px;color:#94a3b8;">
      This is an automated order confirmation from '.$goCoName.'.<br>
      Quotation reference: <strong>'.$goQuoteRef.'</strong> | Order: <strong>'.$goOrderNo.'</strong>
    </p>
  </td></tr>

</table>
</td></tr></table>
</body></html>';

        /* ── Internal notification email ───────────────────────── */
        $goBodyInternal = '
<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:\'Segoe UI\',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 16px;">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#fff;border-radius:10px;overflow:hidden;border:1px solid #e2e8f0;">
  <tr><td style="background:#059669;padding:18px 28px;">
    <div style="color:#fff;font-size:15px;font-weight:700;">&#9989; New Order Generated — '.$goOrderNo.'</div>
  </td></tr>
  <tr><td style="padding:24px 28px;">
    <table width="100%" cellpadding="0" cellspacing="0">
      <tr>
        <td style="font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;padding-bottom:8px;width:40%;">Customer</td>
        <td style="font-size:13px;font-weight:700;color:#1e293b;padding-bottom:8px;">'.$goCustName.'</td>
      </tr>
      <tr>
        <td style="font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;padding-bottom:8px;">Quotation</td>
        <td style="font-size:13px;color:#4f46e5;font-weight:700;padding-bottom:8px;">'.$goQuoteRef.'</td>
      </tr>
      <tr>
        <td style="font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;padding-bottom:8px;">Order Number</td>
        <td style="font-size:13px;color:#059669;font-weight:700;padding-bottom:8px;">'.$goOrderNo.'</td>
      </tr>
      <tr>
        <td style="font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;padding-bottom:8px;">Payment Method</td>
        <td style="font-size:13px;font-weight:600;color:#1e293b;padding-bottom:8px;">'.$goModeIcon.' '.$goPayMode.'</td>
      </tr>
      <tr>
        <td style="font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;padding-bottom:8px;">Order Status</td>
        <td style="padding-bottom:8px;">'.$goOrderBadge.'</td>
      </tr>
      <tr>
        <td style="font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;padding-bottom:16px;">Total Amount</td>
        <td style="font-size:14px;font-weight:700;color:#059669;padding-bottom:16px;">'.$goTotalFmt.'</td>
      </tr>
    </table>
  </td></tr>
  <tr><td style="background:#f8fafc;padding:12px 28px;border-top:1px solid #e2e8f0;">
    <p style="margin:0;font-size:11px;color:#94a3b8;">Internal notification — '.$goCoName.' Order System</p>
  </td></tr>
</table>
</td></tr></table>
</body></html>';

        /* ── Deduplicate & dispatch ─────────────────────────────── */
        $goSentTo = []; $goRecips = [];
        $goCustSubject = 'Your Order '.$goOrderNo.' Has Been Confirmed | '.$goCoName;
        $goIntSubject  = '[New Order] '.$goOrderNo.' from '.$goCustName.' | '.$goCoName;

        if ($goCustEmail !== '' && !in_array(strtolower($goCustEmail), $goSentTo)) {
            $goSentTo[] = strtolower($goCustEmail);
            $goRecips[] = ['to_mail_id'=>$goCustEmail, 'subject'=>$goCustSubject, 'body'=>$goBodyCustomer];
        }
        if ($goRecEmail !== '' && !in_array(strtolower($goRecEmail), $goSentTo)) {
            $goSentTo[] = strtolower($goRecEmail);
            $goRecips[] = ['to_mail_id'=>$goRecEmail, 'subject'=>$goCustSubject, 'body'=>$goBodyCustomer];
        }
        foreach ($goSupportEmails as $goSmail) {
            if ($goSmail !== '' && !in_array(strtolower($goSmail), $goSentTo)) {
                $goSentTo[] = strtolower($goSmail);
                $goRecips[] = ['to_mail_id'=>$goSmail, 'subject'=>$goIntSubject, 'body'=>$goBodyInternal];
            }
        }
        if (!empty($goRecips)) sinelec_send_mail($goRecips);

        $goNotified = implode(', ', array_map('htmlspecialchars', array_slice($goSentTo, 0, 2)));
        adminRedirectWithFlash('quotation', 'ok',
            'Order <strong>'.$goOrderNo.'</strong> generated successfully'
            . ($goNotified ? ' &amp; notification sent to '.$goNotified : '') . '.');
    break;

    /* ─────────────────────────────────────────────────────────────
       USER ORDERS (tbl_user_order)
    ───────────────────────────────────────────────────────────── */
    case 'CreateDirectOrder':
        adminRequireAuth();
        $cdoUid     = (int)($_SESSION['sinelec_admin']['USER_ID'] ?? 0);
        $cdoCustId  = (int)($_POST['user_id']       ?? 0);
        $cdoAddrId  = (int)($_POST['user_address_id'] ?? 0);
        $cdoBilId   = (int)($_POST['billing_address_id'] ?? $cdoAddrId);
        $cdoMode    = trim($_POST['order_mode']      ?? '');
        $cdoPoId    = trim($_POST['customer_po_id']  ?? '');
        $cdoSupNo   = trim($_POST['customer_supplier_no'] ?? '');
        $cdoShip    = (float)($_POST['shipping_amt'] ?? 0);
        $cdoDisc    = (float)($_POST['discount_amt'] ?? 0);
        $cdoTax     = (float)($_POST['tax_amt']      ?? 0);

        $validModes = ['Payment Gateway','Bank Transfer','Invoice'];
        if ($cdoCustId <= 0)                          adminRedirectWithFlash('order-list', 'warn', 'Please select a customer.');
        if (!in_array($cdoMode, $validModes))         adminRedirectWithFlash('order-list', 'warn', 'Please select a valid payment method.');

        $cdoProdIds  = $_POST['prod_ids']   ?? [];
        $cdoCatIds   = $_POST['cat_ids']    ?? [];
        $cdoQtys     = $_POST['quantities'] ?? [];
        $cdoUnitAmts = $_POST['unit_amts']  ?? [];
        $cdoDiscPcts = $_POST['disc_pcts']  ?? [];
        $cdoTaxPcts  = $_POST['tax_pcts']   ?? [];

        $cdoItems = [];
        foreach ($cdoProdIds as $idx => $prodId) {
            $prodId  = (int)$prodId;
            $catId   = (int)($cdoCatIds[$idx]   ?? 0);
            $qty     = (float)($cdoQtys[$idx]     ?? 1);
            $unitAmt = (float)($cdoUnitAmts[$idx] ?? 0);
            $discPct = (float)($cdoDiscPcts[$idx] ?? 0);
            $taxPct  = (float)($cdoTaxPcts[$idx]  ?? 0);
            if ($prodId <= 0 || $catId <= 0 || $qty <= 0) continue;
            $cdoItems[] = ['prod_id'=>$prodId,'cat_id'=>$catId,'qty'=>$qty,'unit_amt'=>$unitAmt,'disc_pct'=>$discPct,'tax_pct'=>$taxPct];
        }
        if (empty($cdoItems)) adminRedirectWithFlash('order-list', 'warn', 'Please add at least one product.');

        $cdoNewId = $controller->createDirectOrder([
            'user_id'              => $cdoCustId,
            'user_address_id'      => $cdoAddrId,
            'billing_address_id'   => $cdoBilId,
            'order_mode'           => $cdoMode,
            'customer_po_id'       => $cdoPoId,
            'customer_supplier_no' => $cdoSupNo,
            'shipping_amt'         => $cdoShip,
            'discount_amt'         => $cdoDisc,
            'tax_amt'              => $cdoTax,
            'changed_by_user_id'   => $cdoUid,
            'items'                => $cdoItems,
        ]);
        if ($cdoNewId <= 0) adminRedirectWithFlash('order-list', 'err', 'Failed to create order. Please try again.');

        /* Send confirmation mail */
        $cdoOrderNo  = 'ORD-'.date('Y').'-'.str_pad((string)$cdoNewId, 6, '0', STR_PAD_LEFT);
        $cdoComp     = $controller->getCompanyDetails();
        $cdoQ        = $controller->getUserOrderById($cdoNewId);
        $cdoCustName = htmlspecialchars((string)($cdoQ->CUST_NAME  ?? 'Customer'));
        $cdoCustEmail= (string)($cdoQ->CUST_EMAIL ?? '');
        $cdoTotalFmt = '€'.number_format((float)($cdoQ->FINAL_TOTAL_AMT ?? 0), 2);
        $cdoPayMode  = (string)($cdoQ->ORDER_MODE ?? $cdoMode);
        $cdoOsText   = (string)($cdoQ->ORDER_STATUS ?? '');
        $cdoCoName   = $cdoComp ? htmlspecialchars((string)($cdoComp->NAME ?? 'Our Company')) : 'Our Company';
        $cdoCoEmail  = $cdoComp ? (string)($cdoComp->EMAIL ?? '') : '';
        $cdoCoPhone  = $cdoComp ? (string)($cdoComp->CONTACT_NUMBER ?? '') : '';
        $cdoSupEmails = [];
        if ($cdoComp) {
            $cdoRaw = trim((string)($cdoComp->SUPPORT_MAIL_ID ?? ''));
            if ($cdoRaw !== '') $cdoSupEmails = array_filter(array_map('trim', explode(',', $cdoRaw)));
        }
        $cdoRecEmail = (string)($cdoQ->RECIPIENT_EMAIL ?? '');
        $cdoModeIcon = match($cdoPayMode) { 'Invoice'=>'&#128196;', 'Bank Transfer'=>'&#127968;', default=>'&#128179;' };
        $cdoOsBadge  = '<span style="display:inline-block;background:#dbeafe;color:#1d4ed8;border:1px solid #93c5fd;font-size:12px;font-weight:700;padding:4px 14px;border-radius:20px;">'.$cdoOsText.'</span>';
        $cdoCoContactHtml = '';
        if ($cdoCoEmail) $cdoCoContactHtml .= '<a href="mailto:'.htmlspecialchars($cdoCoEmail).'" style="color:#6366f1;">'.htmlspecialchars($cdoCoEmail).'</a>';
        if ($cdoCoEmail && $cdoCoPhone) $cdoCoContactHtml .= ' | ';
        if ($cdoCoPhone) $cdoCoContactHtml .= htmlspecialchars($cdoCoPhone);
        $cdoBodyCust = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#f1f5f9;font-family:\'Segoe UI\',Arial,sans-serif;"><table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:32px 16px;"><tr><td align="center"><table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;"><tr><td style="background:linear-gradient(135deg,#059669 0%,#10b981 100%);border-radius:12px 12px 0 0;padding:32px 40px;text-align:center;"><div style="color:#fff;font-size:22px;font-weight:700;">'.$cdoCoName.'</div><div style="color:rgba(255,255,255,.8);font-size:13px;margin-top:4px;">&#9989; Order Confirmation</div></td></tr><tr><td style="background:#fff;padding:36px 40px 28px;"><p style="margin:0 0 20px;font-size:16px;font-weight:700;color:#1e293b;">Dear '.$cdoCustName.',</p><p style="margin:0 0 20px;font-size:14px;color:#475569;line-height:1.7;">Your order has been successfully created. Please find the details below.</p><table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;margin:0 0 20px;"><tr><td style="padding:16px 24px;border-bottom:1px solid #e2e8f0;"><div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:4px;">Order Number</div><div style="font-size:20px;font-weight:800;color:#059669;">'.$cdoOrderNo.'</div></td><td style="padding:16px 24px;border-bottom:1px solid #e2e8f0;text-align:right;"><div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:4px;">Total Amount</div><div style="font-size:20px;font-weight:800;color:#059669;">'.$cdoTotalFmt.'</div></td></tr><tr><td style="padding:14px 24px;border-bottom:1px solid #e2e8f0;"><div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:6px;">Order Status</div>'.$cdoOsBadge.'</td><td style="padding:14px 24px;border-bottom:1px solid #e2e8f0;"><div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:4px;">Payment Method</div><div style="font-size:13px;font-weight:600;color:#1e293b;">'.$cdoModeIcon.' '.$cdoPayMode.'</div></td></tr></table><p style="margin:0;font-size:13px;color:#64748b;line-height:1.7;">Thank you for your business. We will keep you updated as your order progresses.</p></td></tr><tr><td style="background:#f8fafc;padding:20px 40px;border-top:1px solid #e2e8f0;"><p style="margin:0 0 4px;font-size:13px;color:#475569;">Warm regards,</p><p style="margin:0 0 4px;font-size:14px;font-weight:700;color:#1e293b;">'.$cdoCoName.'</p>'.($cdoCoContactHtml ? '<p style="margin:0;font-size:12px;color:#94a3b8;">'.$cdoCoContactHtml.'</p>' : '').'</td></tr><tr><td style="background:#e2e8f0;border-radius:0 0 12px 12px;padding:12px 40px;text-align:center;"><p style="margin:0;font-size:11px;color:#94a3b8;">Automated notification from '.$cdoCoName.' | Order: <strong>'.$cdoOrderNo.'</strong></p></td></tr></table></td></tr></table></body></html>';
        $cdoBodyInt  = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#f1f5f9;font-family:\'Segoe UI\',Arial,sans-serif;"><table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 16px;"><tr><td align="center"><table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#fff;border-radius:10px;overflow:hidden;border:1px solid #e2e8f0;"><tr><td style="background:#059669;padding:16px 24px;"><div style="color:#fff;font-size:15px;font-weight:700;">&#9989; New Direct Order — '.$cdoOrderNo.'</div></td></tr><tr><td style="padding:20px 24px;"><table width="100%" cellpadding="0" cellspacing="0"><tr><td style="font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;padding-bottom:6px;width:40%;">Customer</td><td style="font-size:13px;font-weight:700;color:#1e293b;padding-bottom:6px;">'.$cdoCustName.'</td></tr><tr><td style="font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;padding-bottom:6px;">Order No</td><td style="font-size:13px;color:#059669;font-weight:700;padding-bottom:6px;">'.$cdoOrderNo.'</td></tr><tr><td style="font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;padding-bottom:6px;">Payment</td><td style="font-size:13px;font-weight:600;color:#1e293b;padding-bottom:6px;">'.$cdoModeIcon.' '.$cdoPayMode.'</td></tr><tr><td style="font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;padding-bottom:6px;">Total</td><td style="font-size:14px;font-weight:700;color:#059669;padding-bottom:6px;">'.$cdoTotalFmt.'</td></tr></table></td></tr><tr><td style="background:#f8fafc;padding:10px 24px;border-top:1px solid #e2e8f0;"><p style="margin:0;font-size:11px;color:#94a3b8;">Internal — '.$cdoCoName.' Order System</p></td></tr></table></td></tr></table></body></html>';

        $cdoSentTo = []; $cdoRecips = [];
        $cdoCustSubj = 'Your Order '.$cdoOrderNo.' | '.$cdoCoName;
        $cdoIntSubj  = '[New Order] '.$cdoOrderNo.' — '.$cdoCustName.' | '.$cdoCoName;
        if ($cdoCustEmail !== '' && !in_array(strtolower($cdoCustEmail), $cdoSentTo)) {
            $cdoSentTo[] = strtolower($cdoCustEmail);
            $cdoRecips[] = ['to_mail_id'=>$cdoCustEmail,'subject'=>$cdoCustSubj,'body'=>$cdoBodyCust];
        }
        if ($cdoRecEmail !== '' && !in_array(strtolower($cdoRecEmail), $cdoSentTo)) {
            $cdoSentTo[] = strtolower($cdoRecEmail);
            $cdoRecips[] = ['to_mail_id'=>$cdoRecEmail,'subject'=>$cdoCustSubj,'body'=>$cdoBodyCust];
        }
        foreach ($cdoSupEmails as $sm) {
            if ($sm !== '' && !in_array(strtolower($sm), $cdoSentTo)) {
                $cdoSentTo[] = strtolower($sm);
                $cdoRecips[] = ['to_mail_id'=>$sm,'subject'=>$cdoIntSubj,'body'=>$cdoBodyInt];
            }
        }
        if (!empty($cdoRecips)) sinelec_send_mail($cdoRecips);
        adminRedirectWithFlash('order-list', 'ok', 'Order <strong>'.$cdoOrderNo.'</strong> created successfully.');
    break;

    case 'UpdateUserOrderStatus':
        adminRequireAuth();
        $uosId       = (int)($_POST['user_order_id']   ?? 0);
        $uosStatus   = trim($_POST['order_status']      ?? '');
        $uosPayment  = trim($_POST['payment_status']    ?? '');
        $uosRemark   = trim($_POST['remark']            ?? '');
        $uosAdminUid = (int)($_SESSION['sinelec_admin']['USER_ID'] ?? 0);
        if ($uosId <= 0 || $uosStatus === '' || $uosPayment === '') adminRedirectWithFlash('order-list', 'warn', 'Invalid request.');

        if (!$controller->updateUserOrderStatus($uosId, $uosStatus, $uosPayment, $uosRemark, $uosAdminUid)) {
            adminRedirectWithFlash('order-list', 'err', 'Failed to update order status.');
        }

        /* Send status update mail */
        $uosQ       = $controller->getUserOrderById($uosId);
        $uosComp    = $controller->getCompanyDetails();
        $uosOrderNo = (string)($uosQ->ORDER_NUMBER ?? '');
        $uosCustN   = htmlspecialchars((string)($uosQ->CUST_NAME  ?? 'Customer'));
        $uosCustEm  = (string)($uosQ->CUST_EMAIL ?? '');
        $uosRecEm   = (string)($uosQ->RECIPIENT_EMAIL ?? '');
        $uosTotal   = '€'.number_format((float)($uosQ->FINAL_TOTAL_AMT ?? 0), 2);
        $uosCoName  = $uosComp ? htmlspecialchars((string)($uosComp->NAME ?? 'Our Company')) : 'Our Company';
        $uosCoEmail = $uosComp ? (string)($uosComp->EMAIL ?? '') : '';
        $uosCoPhone = $uosComp ? (string)($uosComp->CONTACT_NUMBER ?? '') : '';
        $uosCoContactHtml = '';
        if ($uosCoEmail) $uosCoContactHtml .= '<a href="mailto:'.htmlspecialchars($uosCoEmail).'" style="color:#6366f1;">'.htmlspecialchars($uosCoEmail).'</a>';
        if ($uosCoEmail && $uosCoPhone) $uosCoContactHtml .= ' | ';
        if ($uosCoPhone) $uosCoContactHtml .= htmlspecialchars($uosCoPhone);
        $uosSupEmails = [];
        if ($uosComp) {
            $uosRaw = trim((string)($uosComp->SUPPORT_MAIL_ID ?? ''));
            if ($uosRaw !== '') $uosSupEmails = array_filter(array_map('trim', explode(',', $uosRaw)));
        }
        $uosIsCancel = str_contains(strtolower($uosStatus), 'cancel');
        $uosIsDeliv  = str_contains(strtolower($uosStatus), 'deliver');
        $uosHdrBg = $uosIsCancel ? 'background:linear-gradient(135deg,#b91c1c 0%,#dc2626 100%)' : ($uosIsDeliv ? 'background:linear-gradient(135deg,#059669 0%,#10b981 100%)' : 'background:linear-gradient(135deg,#4f46e5 0%,#6366f1 100%)');
        $uosOsColor = match(true) {
            str_contains(strtolower($uosStatus),'deliver') => ['bg'=>'#dcfce7','t'=>'#15803d','b'=>'#86efac'],
            str_contains(strtolower($uosStatus),'cancel')  => ['bg'=>'#fee2e2','t'=>'#b91c1c','b'=>'#fca5a5'],
            str_contains(strtolower($uosStatus),'confirm') => ['bg'=>'#dcfce7','t'=>'#15803d','b'=>'#86efac'],
            str_contains(strtolower($uosStatus),'transit') => ['bg'=>'#dbeafe','t'=>'#1d4ed8','b'=>'#93c5fd'],
            str_contains(strtolower($uosStatus),'dispatch')=> ['bg'=>'#dbeafe','t'=>'#1d4ed8','b'=>'#93c5fd'],
            default                                         => ['bg'=>'#fef3c7','t'=>'#92400e','b'=>'#fcd34d'],
        };
        $uosBadge = '<span style="display:inline-block;background:'.$uosOsColor['bg'].';color:'.$uosOsColor['t'].';border:1px solid '.$uosOsColor['b'].';font-size:12px;font-weight:700;padding:4px 14px;border-radius:20px;">'.$uosStatus.'</span>';
        $uosRemarkHtml = $uosRemark !== '' ? '<div style="background:#f8fafc;border-left:3px solid #6366f1;padding:10px 14px;border-radius:0 6px 6px 0;font-size:13px;color:#475569;margin-top:16px;line-height:1.6;"><strong>Note:</strong> '.htmlspecialchars($uosRemark).'</div>' : '';
        $uosBodyCust = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#f1f5f9;font-family:\'Segoe UI\',Arial,sans-serif;"><table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:32px 16px;"><tr><td align="center"><table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;"><tr><td style="'.$uosHdrBg.';border-radius:12px 12px 0 0;padding:32px 40px;text-align:center;"><div style="color:#fff;font-size:22px;font-weight:700;">'.$uosCoName.'</div><div style="color:rgba(255,255,255,.8);font-size:13px;margin-top:4px;">Order Status Update</div></td></tr><tr><td style="background:#fff;padding:36px 40px 28px;"><p style="margin:0 0 20px;font-size:16px;font-weight:700;color:#1e293b;">Dear '.$uosCustN.',</p><p style="margin:0 0 20px;font-size:14px;color:#475569;line-height:1.7;">Your order status has been updated. Please find the details below.</p><table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;margin:0 0 16px;"><tr><td style="padding:16px 24px;border-bottom:1px solid #e2e8f0;"><div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:4px;">Order Number</div><div style="font-size:18px;font-weight:800;color:#4f46e5;">'.$uosOrderNo.'</div></td><td style="padding:16px 24px;border-bottom:1px solid #e2e8f0;text-align:right;"><div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:4px;">Total Amount</div><div style="font-size:18px;font-weight:800;color:#059669;">'.$uosTotal.'</div></td></tr><tr><td colspan="2" style="padding:16px 24px;"><div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:8px;">Current Order Status</div>'.$uosBadge.'</td></tr></table>'.$uosRemarkHtml.'<p style="margin:16px 0 0;font-size:13px;color:#64748b;line-height:1.7;">If you have any questions, please contact us at any time.</p></td></tr><tr><td style="background:#f8fafc;padding:20px 40px;border-top:1px solid #e2e8f0;"><p style="margin:0 0 4px;font-size:13px;color:#475569;">Warm regards,</p><p style="margin:0 0 4px;font-size:14px;font-weight:700;color:#1e293b;">'.$uosCoName.'</p>'.($uosCoContactHtml ? '<p style="margin:0;font-size:12px;color:#94a3b8;">'.$uosCoContactHtml.'</p>' : '').'</td></tr><tr><td style="background:#e2e8f0;border-radius:0 0 12px 12px;padding:12px 40px;text-align:center;"><p style="margin:0;font-size:11px;color:#94a3b8;">Order: <strong>'.$uosOrderNo.'</strong></p></td></tr></table></td></tr></table></body></html>';
        $uosBodyInt  = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#f1f5f9;font-family:\'Segoe UI\',Arial,sans-serif;"><table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 16px;"><tr><td align="center"><table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#fff;border-radius:10px;overflow:hidden;border:1px solid #e2e8f0;"><tr><td style="background:#4f46e5;padding:16px 24px;"><div style="color:#fff;font-size:15px;font-weight:700;">&#128204; Status Updated — '.$uosOrderNo.'</div></td></tr><tr><td style="padding:20px 24px;"><table width="100%" cellpadding="0" cellspacing="0"><tr><td style="font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;padding-bottom:6px;width:40%;">Customer</td><td style="font-size:13px;font-weight:700;color:#1e293b;padding-bottom:6px;">'.$uosCustN.'</td></tr><tr><td style="font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;padding-bottom:6px;">Order No</td><td style="font-size:13px;color:#4f46e5;font-weight:700;padding-bottom:6px;">'.$uosOrderNo.'</td></tr><tr><td style="font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;padding-bottom:6px;">New Status</td><td style="padding-bottom:6px;">'.$uosBadge.'</td></tr><tr><td style="font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;padding-bottom:6px;">Payment Status</td><td style="font-size:13px;color:#1e293b;padding-bottom:6px;">'.htmlspecialchars($uosPayment).'</td></tr>'.($uosRemark !== '' ? '<tr><td style="font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;padding-bottom:6px;">Remark</td><td style="font-size:13px;color:#475569;padding-bottom:6px;">'.htmlspecialchars($uosRemark).'</td></tr>' : '').'</table></td></tr><tr><td style="background:#f8fafc;padding:10px 24px;border-top:1px solid #e2e8f0;"><p style="margin:0;font-size:11px;color:#94a3b8;">Internal — '.$uosCoName.' Order System</p></td></tr></table></td></tr></table></body></html>';

        $uosSentTo = []; $uosRecips = [];
        $uosCustSubj = 'Order Status Update: '.$uosOrderNo.' | '.$uosCoName;
        $uosIntSubj  = '['.htmlspecialchars($uosStatus).'] '.$uosOrderNo.' — '.$uosCustN;
        if ($uosCustEm !== '' && !in_array(strtolower($uosCustEm), $uosSentTo)) {
            $uosSentTo[] = strtolower($uosCustEm);
            $uosRecips[] = ['to_mail_id'=>$uosCustEm,'subject'=>$uosCustSubj,'body'=>$uosBodyCust];
        }
        if ($uosRecEm !== '' && !in_array(strtolower($uosRecEm), $uosSentTo)) {
            $uosSentTo[] = strtolower($uosRecEm);
            $uosRecips[] = ['to_mail_id'=>$uosRecEm,'subject'=>$uosCustSubj,'body'=>$uosBodyCust];
        }
        foreach ($uosSupEmails as $sm) {
            if ($sm !== '' && !in_array(strtolower($sm), $uosSentTo)) {
                $uosSentTo[] = strtolower($sm);
                $uosRecips[] = ['to_mail_id'=>$sm,'subject'=>$uosIntSubj,'body'=>$uosBodyInt];
            }
        }
        if (!empty($uosRecips)) sinelec_send_mail($uosRecips);
        $uosNotified = implode(', ', array_map('htmlspecialchars', array_slice($uosSentTo, 0, 2)));
        adminRedirectWithFlash('order-list', 'ok',
            'Order status updated to "<strong>'.htmlspecialchars($uosStatus).'</strong>"'
            . ($uosNotified ? ' &amp; notification sent to '.$uosNotified : '') . '.');
    break;

    case 'DeleteUserOrder':
        adminRequireAuth();
        $duoId = (int)($_POST['user_order_id'] ?? 0);
        if ($duoId <= 0) adminRedirectWithFlash('order-list', 'warn', 'Invalid request.');
        if ($controller->deleteUserOrder($duoId)) {
            adminRedirectWithFlash('order-list', 'ok', 'Order deleted successfully.');
        }
        adminRedirectWithFlash('order-list', 'err', 'Failed to delete order.');
    break;

    case 'ResendQuotation':
        adminRequireAuth();
        $qid = (int)($_POST['enquiry_quote_id'] ?? 0);
        if ($qid <= 0) adminRedirectWithFlash('quotation', 'warn', 'Invalid request.');
        $q = $controller->getQuotationById($qid);
        if (!$q) adminRedirectWithFlash('quotation', 'err', 'Quotation not found.');
        $custEmail = (string)($q->USER_EMAIL_RESOLVED ?? $q->USER_EMAIL ?? '');
        if ($custEmail === '') adminRedirectWithFlash('quotation', 'warn', 'No email address on file for this customer.');

        $quoteRef  = 'QT-' . str_pad((string)$qid, 6, '0', STR_PAD_LEFT);
        $custName  = htmlspecialchars((string)($q->USER_NAME_RESOLVED ?? $q->USER_NAME ?? 'Customer'));
        $resendUid = (int)(float)($q->USER_ID ?? 0);
        $totalAmt  = number_format((float)($q->ENQUIRY_TOTAL_AMT ?? 0), 2);
        $pdfLink   = (isset($_SERVER['HTTP_HOST']) ? 'https://' . $_SERVER['HTTP_HOST'] : '') . '/admin/quotation-pdf?id=' . $qid . '&uid=' . $resendUid;
        $rComp     = $controller->getCompanyDetails();
        $rCoName   = $rComp ? htmlspecialchars((string)($rComp->NAME ?? 'Our Company')) : 'Our Company';

        /* Recipient email from saved delivery address */
        $rRecEmail = '';
        $rAddrId   = (int)(float)($q->USER_ADDRESS_ID ?? 0);
        if ($rAddrId > 0 && $resendUid > 0) {
            $rAddrs = $controller->getUserAddressesForQuote($resendUid);
            foreach ($rAddrs as $ra) {
                if ((int)(float)($ra->USER_ADDRESS_ID ?? 0) === $rAddrId) {
                    $rRecEmail = trim((string)($ra->RECIPIENT_EMAIL ?? ''));
                    break;
                }
            }
        }

        /* Support emails */
        $rSupportEmails = [];
        if ($rComp) {
            $rRaw = trim((string)($rComp->SUPPORT_MAIL_ID ?? ''));
            if ($rRaw !== '') $rSupportEmails = array_filter(array_map('trim', explode(',', $rRaw)));
        }

        $rCoEmailDisp  = $rComp ? htmlspecialchars((string)($rComp->EMAIL          ?? '')) : '';
        $rCoPhoneDisp  = $rComp ? htmlspecialchars((string)($rComp->CONTACT_NUMBER ?? '')) : '';
        $rCoLogoAbsUrl = $rComp ? trim((string)($rComp->LOGO ?? '')) : '';
        $rCoContactHtml = '';
        if ($rCoEmailDisp) $rCoContactHtml .= '<a href="mailto:'.$rCoEmailDisp.'" style="color:#6366f1;text-decoration:none;">'.$rCoEmailDisp.'</a>';
        if ($rCoEmailDisp && $rCoPhoneDisp) $rCoContactHtml .= ' &nbsp;|&nbsp; ';
        if ($rCoPhoneDisp) $rCoContactHtml .= $rCoPhoneDisp;

        $bodyResend = '
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:\'Segoe UI\',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:32px 16px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

  <!-- Header -->
  <tr><td style="background:linear-gradient(135deg,#4f46e5 0%,#6366f1 100%);border-radius:12px 12px 0 0;padding:32px 40px;text-align:center;">
    '.($rCoLogoAbsUrl ? '<img src="'.$rCoLogoAbsUrl.'" alt="'.$rCoName.'" style="max-height:52px;max-width:180px;object-fit:contain;filter:brightness(0) invert(1);margin-bottom:12px;display:block;margin-left:auto;margin-right:auto;">' : '').'
    <div style="color:#fff;font-size:22px;font-weight:700;letter-spacing:.5px;">'.($rCoLogoAbsUrl ? '' : $rCoName).'</div>
    <div style="color:rgba(255,255,255,.75);font-size:13px;margin-top:4px;">Quotation — As Requested</div>
  </td></tr>

  <!-- Body -->
  <tr><td style="background:#ffffff;padding:40px 40px 32px;">
    <p style="margin:0 0 20px;font-size:16px;font-weight:700;color:#1e293b;">Dear '.$custName.',</p>
    <p style="margin:0 0 16px;font-size:14px;color:#475569;line-height:1.7;">
      As requested, please find the re-issued copy of your quotation from <strong>'.$rCoName.'</strong> attached below.
      This quotation contains all the details of the products and services discussed. We hope this meets your expectations.
    </p>

    <!-- Quote Card -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;margin:24px 0;">
      <tr>
        <td style="padding:20px 24px;border-bottom:1px solid #e2e8f0;">
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:4px;">Quotation Reference</div>
          <div style="font-size:20px;font-weight:800;color:#4f46e5;">'.$quoteRef.'</div>
        </td>
        <td style="padding:20px 24px;border-bottom:1px solid #e2e8f0;text-align:right;">
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:4px;">Total Amount</div>
          <div style="font-size:22px;font-weight:800;color:#059669;">€'.$totalAmt.'</div>
        </td>
      </tr>
      <tr>
        <td colspan="2" style="padding:16px 24px;text-align:center;">
          <a href="'.$pdfLink.'" target="_blank"
             style="display:inline-block;background:#4f46e5;color:#ffffff;font-size:14px;font-weight:600;padding:12px 32px;border-radius:8px;text-decoration:none;letter-spacing:.3px;">
            &#128196; View &amp; Download Quotation
          </a>
        </td>
      </tr>
    </table>

    <p style="margin:0 0 12px;font-size:13px;color:#64748b;line-height:1.7;">
      Should you need any clarification, wish to negotiate terms, or require any modifications, our team is always available to assist you promptly.
    </p>
    <p style="margin:0;font-size:13px;color:#64748b;line-height:1.7;">
      We value your business and appreciate the opportunity to serve you.
    </p>
  </td></tr>

  <!-- Sign-off -->
  <tr><td style="background:#f8fafc;padding:24px 40px;border-top:1px solid #e2e8f0;">
    <p style="margin:0 0 4px;font-size:13px;color:#475569;">Warm regards,</p>
    <p style="margin:0 0 4px;font-size:14px;font-weight:700;color:#1e293b;">'.$rCoName.'</p>
    '.($rCoContactHtml ? '<p style="margin:0;font-size:12px;color:#94a3b8;">'.$rCoContactHtml.'</p>' : '').'
  </td></tr>

  <!-- Footer -->
  <tr><td style="background:#e2e8f0;border-radius:0 0 12px 12px;padding:14px 40px;text-align:center;">
    <p style="margin:0;font-size:11px;color:#94a3b8;">
      This email was sent by '.$rCoName.' regarding quotation <strong>'.$quoteRef.'</strong>.<br>
      Please do not reply directly to this automated message.
    </p>
  </td></tr>

</table>
</td></tr></table>
</body></html>';

        $bodyIntR = '
<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:\'Segoe UI\',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 16px;">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#fff;border-radius:10px;overflow:hidden;border:1px solid #e2e8f0;">
  <tr><td style="background:#0891b2;padding:18px 28px;">
    <div style="color:#fff;font-size:15px;font-weight:700;">&#128260; Quotation Resent: '.$quoteRef.'</div>
  </td></tr>
  <tr><td style="padding:24px 28px;">
    <table width="100%" cellpadding="0" cellspacing="0">
      <tr>
        <td style="font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;padding-bottom:4px;">Customer</td>
        <td style="font-size:13px;font-weight:700;color:#1e293b;padding-bottom:4px;">'.$custName.'</td>
      </tr>
      <tr>
        <td style="font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;padding-bottom:4px;">Reference</td>
        <td style="font-size:13px;color:#4f46e5;font-weight:700;padding-bottom:4px;">'.$quoteRef.'</td>
      </tr>
      <tr>
        <td style="font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;padding-bottom:4px;">Total Amount</td>
        <td style="font-size:13px;font-weight:700;color:#059669;padding-bottom:4px;">€'.$totalAmt.'</td>
      </tr>
      <tr>
        <td style="font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;padding-bottom:16px;">Status</td>
        <td style="font-size:13px;color:#475569;padding-bottom:16px;">Quotation has been resent to the customer as requested.</td>
      </tr>
    </table>
    <a href="'.$pdfLink.'" target="_blank" style="display:inline-block;background:#4f46e5;color:#fff;font-size:13px;font-weight:600;padding:10px 24px;border-radius:7px;text-decoration:none;">View Quotation PDF &rarr;</a>
  </td></tr>
  <tr><td style="background:#f8fafc;padding:12px 28px;border-top:1px solid #e2e8f0;">
    <p style="margin:0;font-size:11px;color:#94a3b8;">Internal notification — '.$rCoName.' Quotation System</p>
  </td></tr>
</table>
</td></tr></table>
</body></html>';

        $rSentTo = []; $rRecips = [];
        if ($custEmail !== '' && !in_array(strtolower($custEmail), $rSentTo)) {
            $rSentTo[] = strtolower($custEmail);
            $rRecips[] = ['to_mail_id'=>$custEmail, 'subject'=>'Your Quotation '.$quoteRef.' — '.$rCoName, 'body'=>$bodyResend];
        }
        if ($rRecEmail !== '' && !in_array(strtolower($rRecEmail), $rSentTo)) {
            $rSentTo[] = strtolower($rRecEmail);
            $rRecips[] = ['to_mail_id'=>$rRecEmail, 'subject'=>'Your Quotation '.$quoteRef.' — '.$rCoName, 'body'=>$bodyResend];
        }
        foreach ($rSupportEmails as $rSmail) {
            if ($rSmail !== '' && !in_array(strtolower($rSmail), $rSentTo)) {
                $rSentTo[] = strtolower($rSmail);
                $rRecips[] = ['to_mail_id'=>$rSmail, 'subject'=>'[Resent] Quotation '.$quoteRef.' — '.$custName, 'body'=>$bodyIntR];
            }
        }
        if (!empty($rRecips)) sinelec_send_mail($rRecips);
        $notified = implode(', ', array_map('htmlspecialchars', array_slice($rSentTo, 0, 2)));
        adminRedirectWithFlash('quotation', 'ok', 'Quotation re-sent' . ($notified ? ' to ' . $notified : '') . '.');
    break;

    case 'UpdateCompany':
        adminRequireAuth();
        if (!$controller->updateCompanyDetails($_POST)) {
            adminRedirectWithFlash('company', 'err', 'Failed to save company details.');
        }
        adminRedirectWithFlash('company', 'ok', 'Company details updated successfully.');
    break;

    default:
        header('location:welcome');
        exit();
}
?>
