<?php
require_once __DIR__ . '/../config/db_helper.php';
class WebsiteController
{
    private $dbHelper;

    public function __construct() {
        $this->dbHelper = new MySQLDB();
    }

    private function mapUserRow(object $user): array
    {
        return [
            "user_id" => (int)($user->USER_ID ?? 0),
            "name" => (string)($user->NAME ?? ''),
            "email" => (string)($user->COMMUNICATION_EMAIL_ID ?? ''),
            "user_type_id" => (int)($user->USER_TYPE_ID ?? 0),
            "communication_mobile_num_isd" => (int)($user->COMMUNICATION_MOBILE_NUM_ISD ?? 0),
            "communication_mobile_num" => (string)($user->COMMUNICATION_MOBILE_NUM ?? ''),
            "company_name" => (string)($user->COMPANY_NAME ?? ''),
            "designation" => (string)($user->DESIGNATION ?? ''),
            "is_pwd_updated" => (bool)($user->IS_PWD_UPDATED ?? false),
            "google_id" => (string)($user->GOOGLE_ID ?? ''),
        ];
    }


    public function InsertUserFromWebsite($arrUserData)
	{
		try
		{
                $strName = addslashes(trim($arrUserData['name']));
                $strMobileNum = addslashes(trim($arrUserData['communication_mobile_num']));
                $strEmail = addslashes(trim($arrUserData['communication_email_id']));
                $strPassword = trim($arrUserData['erp_password']);
                $passwordHash = password_hash($strPassword, PASSWORD_DEFAULT);

                if ($passwordHash === false)
                {
                    throw new Exception('Password hashing failed.');
                }

                $strQuery = "INSERT INTO tbl_user (
                user_type_id,
                `name`,
                communication_mobile_num_isd,
                communication_mobile_num,
                communication_email_id,
                erp_password,
                company_name,
                designation,
                account_activation_flag,
                random_activation_key,
                verified_flag
                ) VALUES (
                ".$arrUserData['user_type_id']." ,
                '".$strName."',
                ".$arrUserData['communication_mobile_num_isd']." ,
                '".$strMobileNum."',
                '".$strEmail."',
                '".addslashes($passwordHash)."',
                NULL,
                NULL,
                '1',
                'RANDOM123KEY',
                'Yes'
                )";
                $intUserId = $this->dbHelper->insert($strQuery);
			
			    return $intUserId;
			
		}
		catch (Exception $e) 
        {
            // Log the error (never show raw SQL errors to the user)
            error_log("Registration error: " . $e->getMessage());

            // Return a controlled error response
            return [
                "status" => false,
                "message" => "Something went wrong during registration. Please try again."
            ];
        }
	}


    public function isEmailRegistered($strEmailId)
    {
        try
        {
            $strEmailId = addslashes(trim($strEmailId));
            $query = "SELECT user_id FROM tbl_user WHERE communication_email_id='".$strEmailId."' LIMIT 1";
            $arrUserData = $this->dbHelper->select($query);
            return !empty($arrUserData);
        }
        catch (Exception $e)
        {
            error_log("Email check error: " . $e->getMessage());
            return false;
        }
    }


    public function loginUser($postData) 
    {
        try {
            $username = addslashes(strtolower(trim($postData['username'] ?? '')));
            $password = (string)($postData['password'] ?? '');

            if ($username === '' || $password === '') {
                return [];
            }

            $query = "SELECT * FROM tbl_user WHERE communication_email_id='".$username."' LIMIT 1"; 
            $arrUserData = $this->dbHelper->select($query);

            if (empty($arrUserData)) {
                return [];
            }

            $user = $arrUserData[0];
            $storedHash = (string)($user->ERP_PASSWORD ?? '');

            if ($storedHash !== '' && password_verify($password, $storedHash)) {
                return $this->mapUserRow($user);
            }

            return [];

        } catch (Exception $e) {
            // Log the error (never show raw SQL errors to the user)
            error_log("Login error: " . $e->getMessage());

            // Return a controlled error response
            return [
                "status" => false,
                "message" => "Something went wrong during login. Please try again."
            ];
        }
    }

    public function loginOrRegisterGoogleUser(string $googleId, string $email, string $name): array
    {
        try {
            $googleId = addslashes(trim($googleId));
            $email = addslashes(strtolower(trim($email)));
            $name = addslashes(trim($name));

            if ($googleId === '' || $email === '') {
                return [];
            }

            if ($name === '') {
                $name = 'Google User';
            }

            $findByGoogleQuery = "SELECT * FROM tbl_user WHERE google_id='" . $googleId . "' LIMIT 1";
            $googleUsers = $this->dbHelper->select($findByGoogleQuery);
            if (!empty($googleUsers)) {
                return $this->mapUserRow($googleUsers[0]);
            }

            $findByEmailQuery = "SELECT * FROM tbl_user WHERE communication_email_id='" . $email . "' LIMIT 1";
            $emailUsers = $this->dbHelper->select($findByEmailQuery);
            if (!empty($emailUsers)) {
                $existing = $emailUsers[0];
                $existingUserId = (int)($existing->USER_ID ?? 0);

                if ($existingUserId > 0) {
                    $updateGoogleQuery = "UPDATE tbl_user SET google_id='" . $googleId . "' WHERE user_id=" . $existingUserId . " LIMIT 1";
                    $this->dbHelper->update($updateGoogleQuery);
                }

                $existing->GOOGLE_ID = $googleId;
                return $this->mapUserRow($existing);
            }

            $randomPassword = bin2hex(random_bytes(16));
            $passwordHash = password_hash($randomPassword, PASSWORD_DEFAULT);
            if ($passwordHash === false) {
                return [];
            }

            $activationKey = 'GOOGLE_' . bin2hex(random_bytes(8));
            $insertQuery = "INSERT INTO tbl_user (
                user_type_id,
                `name`,
                communication_mobile_num_isd,
                communication_mobile_num,
                communication_email_id,
                erp_password,
                company_name,
                designation,
                account_activation_flag,
                random_activation_key,
                verified_flag,
                is_pwd_updated,
                google_id
            ) VALUES (
                2,
                '" . $name . "',
                0,
                NULL,
                '" . $email . "',
                '" . addslashes($passwordHash) . "',
                NULL,
                NULL,
                '1',
                '" . addslashes($activationKey) . "',
                'Yes',
                1,
                '" . $googleId . "'
            )";

            $newUserId = (int)$this->dbHelper->insert($insertQuery);
            if ($newUserId <= 0) {
                return [];
            }

            $findByIdQuery = "SELECT * FROM tbl_user WHERE user_id=" . $newUserId . " LIMIT 1";
            $newUsers = $this->dbHelper->select($findByIdQuery);
            if (empty($newUsers)) {
                return [];
            }

            return $this->mapUserRow($newUsers[0]);
        } catch (Exception $e) {
            error_log("Google auth error: " . $e->getMessage());
            return [];
        }
    }

    public function changeUserPassword(int $userId, string $currentPassword, string $newPassword, string &$errorCode = ''): bool
    {
        try {
            $errorCode = '';
            $query = "SELECT erp_password FROM tbl_user WHERE user_id=" . $userId . " LIMIT 1";
            $arrUserData = $this->dbHelper->select($query);

            if (empty($arrUserData)) {
                $errorCode = 'user_not_found';
                return false;
            }

            $storedHash = (string)($arrUserData[0]->ERP_PASSWORD ?? '');
            if ($storedHash === '' || !password_verify($currentPassword, $storedHash)) {
                $errorCode = 'current_password_invalid';
                return false;
            }

            if (password_verify($newPassword, $storedHash)) {
                $errorCode = 'same_as_current';
                return false;
            }

            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            if ($newHash === false) {
                $errorCode = 'hash_failed';
                return false;
            }

            $updateQuery = "UPDATE tbl_user SET erp_password='" . addslashes($newHash) . "', is_pwd_updated='1' WHERE user_id=" . $userId . " LIMIT 1";
            $rows = $this->dbHelper->update($updateQuery);
            if ($rows > 0) {
                return true;
            }

            $errorCode = 'update_failed';
            return false;
        } catch (Exception $e) {
            error_log("Password change error: " . $e->getMessage());
            $errorCode = 'exception';
            return false;
        }
    }

    public function updateUserProfile(array $arrUserData): bool
    {
        try {
            $userId = (int)($arrUserData['user_id'] ?? 0);
            if ($userId <= 0) {
                return false;
            }

            $name = addslashes(trim((string)($arrUserData['name'] ?? '')));
            $phoneCode = (int)($arrUserData['communication_mobile_num_isd'] ?? 0);
            $mobileNumber = addslashes(trim((string)($arrUserData['communication_mobile_num'] ?? '')));
            $companyName = addslashes(trim((string)($arrUserData['company_name'] ?? '')));
            $designation = addslashes(trim((string)($arrUserData['designation'] ?? '')));

            $query = "UPDATE tbl_user SET
                        name='" . $name . "',
                        communication_mobile_num_isd=" . $phoneCode . ",
                        communication_mobile_num='" . $mobileNumber . "',
                        company_name='" . $companyName . "',
                        designation='" . $designation . "'
                      WHERE user_id=" . $userId . "
                      LIMIT 1";

            $rows = $this->dbHelper->update($query);
            return $rows >= 0;
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            return false;
        }
    }


    // public function getActivity() {
    // $query = "SELECT * FROM tbl_activity_log";
    // $arrActicvity = $this->dbHelper->select($query);
    // return $arrActicvity;
    // }

   

    // public function loginUser($postData) 
    // {
    //     try {
    //         // Prepare SQL using placeholders
    //         $query = "SELECT * FROM admins WHERE 
    //         email='".$postData['username']."' AND pwd='".$postData['password']."'"; 
    //         $arrUserData = $this->dbHelper->select($query);
    //         return $arrUserData;

    //     } catch (Exception $e) {
    //         // Log the error (never show raw SQL errors to the user)
    //         error_log("Login error: " . $e->getMessage());

    //         // Return a controlled error response
    //         return [
    //             "status" => false,
    //             "message" => "Something went wrong during login. Please try again."
    //         ];
    //     }
    // }


    // public function updateMenuData($arrUpdateData) 
    // {
    //     try {
    //         $intRowsUpdated = 0;

    //         foreach ($arrUpdateData as $menuData) {
    //                 $query = "UPDATE tbl_menu SET 
    //                         menu_name = '".$menuData['MENU_NAME']."',
    //                         priority = '".$menuData['PRIORITY']."'
    //                         WHERE menu_id = '".$menuData['MENU_ID']."' 
    //                         AND language = '".$menuData['LANGUAGE']."'";

    //             $intRows = $this->dbHelper->update($query);
    //             $intRowsUpdated ++;
    //         }

    //         return $intRowsUpdated; 

    //     } catch (Exception $e) {
    //         // Log the error (never show raw SQL errors to the user)
    //         error_log("Update Menu error: " . $e->getMessage());

    //         // Return a controlled error response
    //         return [
    //             "status" => false,
    //             "message" => "Something went wrong during menu update. Please try again."
    //         ];
    //     }
    // }   




    

    // public function updateHeroSectionData($arrUpdateData)
    // {
    //     try {

    //         $intRowsUpdated = 0;

    //         foreach ($arrUpdateData as $homeData) {

    //             // ---------- Sanitize text values ----------
    //             foreach ($homeData as $key => $value) {
    //                 if (is_string($value)) {
    //                     // Replace ' and " with `
    //                     $homeData[$key] = str_replace(
    //                         ["'", '"'],
    //                         "`",
    //                         trim($value)
    //                     );
    //                 }
    //             }

    //             $homeId   = $homeData['HOME_ID'] ?? '';
    //             $language = $homeData['LANGUAGE'];

    //             /* ================= INSERT ================= */
    //             if (empty($homeId)) {

    //                 $query = "INSERT INTO tbl_home (
    //                             hero_label,
    //                             hero_title,
    //                             hero_description,
    //                             rooms_title,
    //                             rooms_description,
    //                             price_title,
    //                             price_description,
    //                             faq_title,
    //                             faq_description,
    //                             contact_title,
    //                             contact_description,
    //                             enquiry_button_name,
    //                             footer_left_heading,
    //                             footer_middle_heading,
    //                             footer_right_heading,
    //                             language
    //                         ) VALUES (
    //                             '".$homeData['HERO_LABEL']."',
    //                             '".$homeData['HERO_TITLE']."',
    //                             '".$homeData['HERO_DESCRIPTION']."',
    //                             '".$homeData['ROOMS_TITLE']."',
    //                             '".$homeData['ROOMS_DESCRIPTION']."',
    //                             '".$homeData['PRICE_TITLE']."',
    //                             '".$homeData['PRICE_DESCRIPTION']."',
    //                             '".$homeData['FAQ_TITLE']."',
    //                             '".$homeData['FAQ_DESCRIPTION']."',
    //                             '".$homeData['CONTACT_TITLE']."',
    //                             '".$homeData['CONTACT_DESCRIPTION']."',
    //                             '".$homeData['ENQUIRY_BUTTON_NAME']."',
    //                             '".$homeData['FOOTER_LEFT_HEADING']."',
    //                             '".$homeData['FOOTER_MIDDLE_HEADING']."',
    //                             '".$homeData['FOOTER_RIGHT_HEADING']."',
    //                             '".$language."'
    //                         )";

    //                 $intRows = $this->dbHelper->insert($query);
    //                 if ($intRows > 0) {
    //                     $intRowsUpdated++;
    //                 }

    //             } 
    //             /* ================= UPDATE ================= */
    //             else {

    //                 $query = "UPDATE tbl_home SET 
    //                             hero_label = '".$homeData['HERO_LABEL']."',
    //                             hero_title = '".$homeData['HERO_TITLE']."',
    //                             hero_description = '".$homeData['HERO_DESCRIPTION']."',
    //                             rooms_title = '".$homeData['ROOMS_TITLE']."',
    //                             rooms_description = '".$homeData['ROOMS_DESCRIPTION']."',
    //                             price_title = '".$homeData['PRICE_TITLE']."',
    //                             price_description = '".$homeData['PRICE_DESCRIPTION']."',
    //                             faq_title = '".$homeData['FAQ_TITLE']."',
    //                             faq_description = '".$homeData['FAQ_DESCRIPTION']."',
    //                             contact_title = '".$homeData['CONTACT_TITLE']."',
    //                             contact_description = '".$homeData['CONTACT_DESCRIPTION']."',
    //                             enquiry_button_name = '".$homeData['ENQUIRY_BUTTON_NAME']."',
    //                             footer_left_heading = '".$homeData['FOOTER_LEFT_HEADING']."',
    //                             footer_middle_heading = '".$homeData['FOOTER_MIDDLE_HEADING']."',
    //                             footer_right_heading = '".$homeData['FOOTER_RIGHT_HEADING']."'
    //                         WHERE home_id = '".$homeId."'
    //                         AND language = '".$language."'";

    //                 $this->dbHelper->update($query);
    //                 $intRowsUpdated++;
    //             }
    //         }

    //         return $intRowsUpdated;

    //     } catch (Exception $e) {

    //         error_log("Update Home error: " . $e->getMessage());

    //         return [
    //             "status"  => false,
    //             "message" => "Something went wrong during home update. Please try again."
    //         ];
    //     }
    // }







    public function resetUserPasswordByEmail(string $email, string $newPassword): bool
    {
        try {
            $email = addslashes(strtolower(trim($email)));
            if ($email === '') {
                return false;
            }

            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            if ($newHash === false) {
                error_log('resetUserPasswordByEmail: password hashing failed');
                return false;
            }

            $query = "UPDATE tbl_user SET erp_password='" . addslashes($newHash) . "', is_pwd_updated='1' WHERE communication_email_id='" . $email . "' LIMIT 1";
            $rows = $this->dbHelper->update($query);
            return $rows > 0;

        } catch (Exception $e) {
            error_log('resetUserPasswordByEmail error: ' . $e->getMessage());
            return false;
        }
    }

    // public function deleteFAQData($faqId)
    // {
    //     try 
    //     {
    //         $query = "DELETE FROM tbl_faq WHERE faq_id = '" . intval($faqId) . "'";
    //         $intRows = $this->dbHelper->update($query);
    //         return $intRows;

    //     } 
    //     catch (Exception $e) 
    //     {
    //         error_log("Delete FAQ error: " . $e->getMessage());
    //         return [
    //             "status" => false,
    //             "message" => "Something went wrong during FAQ deletion. Please try again."
    //         ];
    //     }   


    // }

    /* ── Careers ──────────────────────────────────────────────────── */

    public function getActiveJobs(): array
    {
        try {
            return $this->dbHelper->select(
                "SELECT * FROM tbl_job_career WHERE job_status='Active' ORDER BY job_priority DESC, job_post_id DESC"
            );
        } catch (Exception $e) {
            error_log('getActiveJobs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Returns new candidate_applied_job_id on success, 0 on failure.
     */
    public function insertApplicant(array $d): int
    {
        try {
            $jobId   = (int)(float)($d['job_post_id']            ?? 0);
            $name    = addslashes(trim($d['candidate_name']       ?? ''));
            $email   = addslashes(strtolower(trim($d['candidate_email'] ?? '')));
            $phone   = (int)(float)($d['candidate_phone']         ?? 0);
            $exp     = (int)($d['candidate_experience']           ?? 0);
            $resKey  = addslashes(trim($d['resume_file_ext']      ?? ''));
            $date    = date('Y-m-d');

            if ($jobId <= 0 || $name === '' || $email === '' || $resKey === '') return 0;

            $sql = "INSERT INTO tbl_candidate_applied_for_job
                    (job_post_id, candidate_name, candidate_email, candidate_phone,
                     candidate_experience, resume_file_ext, applied_date)
                    VALUES ($jobId, '$name', '$email', $phone, $exp, '$resKey', '$date')";

            return (int)$this->dbHelper->insert($sql);
        } catch (Exception $e) {
            error_log('insertApplicant: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Returns: 'subscribed' | 'already' | 'blocked' | 'error'
     */
    public function insertSubscriber(string $email): string
    {
        try {
            $email = strtolower(trim($email));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return 'error';
            }
            $esc = addslashes($email);

            $rows = $this->dbHelper->select(
                "SELECT subscriber_id, status FROM tbl_subscriber WHERE email='$esc' LIMIT 1"
            );

            if (!empty($rows)) {
                $s = (int)($rows[0]->STATUS ?? 1);
                if ($s === 2) return 'blocked';
                if ($s === 1) return 'already';
                /* status=0 (unsubscribed) — re-activate */
                $this->dbHelper->update(
                    "UPDATE tbl_subscriber SET status=1 WHERE email='$esc' LIMIT 1"
                );
                return 'subscribed';
            }

            $this->dbHelper->insert(
                "INSERT INTO tbl_subscriber (email, status) VALUES ('$esc', 1)"
            );
            return 'subscribed';

        } catch (Exception $e) {
            error_log('insertSubscriber: ' . $e->getMessage());
            return 'error';
        }
    }

    public function getCompanyInfo(): ?object
    {
        try {
            $r = $this->dbHelper->select("SELECT * FROM tbl_company ORDER BY company_id ASC LIMIT 1");
            return $r[0] ?? null;
        } catch (Exception $e) { error_log('getCompanyInfo: ' . $e->getMessage()); return null; }
    }

    /* ── Quote: categories (grouped by parent for optgroups) ── */
    public function getCountries(): array
    {
        try {
            return $this->dbHelper->select(
                "SELECT country_id, country FROM tbl_country ORDER BY country ASC"
            );
        } catch (Exception $e) { return []; }
    }

    /* ══════════════════════════════════════════════════════════
       CATALOG — Categories with product counts
    ══════════════════════════════════════════════════════════ */
    /* ── Product images from tbl_product_img ─────────────────── */
    public function getProductImages(int $productId): array
    {
        if ($productId <= 0) return [];
        try {
            return $this->dbHelper->select(
                "SELECT image_id, image_name, product_image_path, priorty
                 FROM tbl_product_img
                 WHERE product_id = $productId
                   AND image_for   = 'Product'
                   AND display_flag = 'Yes'
                 ORDER BY COALESCE(priorty, 999) ASC, image_id ASC"
            );
        } catch (Exception $e) { return []; }
    }

    /* ── Product manuals (PDF/docs) from tbl_product_img ─────── */
    public function getProductManuals(int $productId): array
    {
        if ($productId <= 0) return [];
        try {
            return $this->dbHelper->select(
                "SELECT image_id, image_name, product_image_path, priorty
                 FROM tbl_product_img
                 WHERE product_id = $productId
                   AND image_for   = 'Product Mannual'
                   AND display_flag = 'Yes'
                 ORDER BY COALESCE(priorty, 999) ASC, image_id ASC"
            );
        } catch (Exception $e) { return []; }
    }

    /* ── Product sample code ──────────────────────────────────── */
    public function getProductSampleCode(int $productId): array
    {
        if ($productId <= 0) return [];
        try {
            return $this->dbHelper->select(
                "SELECT product_sample_code_id, language_technology,
                        ide_compiler, type, os, ext, date
                 FROM tbl_product_sample_code
                 WHERE product_id = $productId
                 ORDER BY product_sample_code_id ASC"
            );
        } catch (Exception $e) { return []; }
    }

    /* ── Single product by DB ID ──────────────────────────────── */
    public function getProductById(int $id): ?object
    {
        if ($id <= 0) return null;
        try {
            $rows = $this->dbHelper->select(
                "SELECT p.product_id, p.product_name, p.product_code,
                        p.product_amt, p.offer_percentage, p.product_discount,
                        p.total_remaining, p.total_sold, p.rating,
                        p.label, p.product_entry_date,
                        p.product_description, p.product_details,
                        p.product_specification,
                        c.product_category_name, c.product_category_id,
                        pc.product_category_name AS parent_category_name
                 FROM tbl_product p
                 LEFT JOIN tbl_product_category c
                        ON c.product_category_id = p.product_category_id
                 LEFT JOIN tbl_product_category pc
                        ON pc.product_category_id = c.parent_category_id
                 WHERE p.product_id = $id
                 LIMIT 1"
            );
            return $rows[0] ?? null;
        } catch (Exception $e) { return null; }
    }

    /* ══════════════════════════════════════════════════════════
       WISHLIST
    ══════════════════════════════════════════════════════════ */

    /** Returns array of product_id integers for the user's wishlist. */
    public function getWishlistIds(int $userId): array
    {
        if ($userId <= 0) return [];
        try {
            $rows = $this->dbHelper->select(
                "SELECT product_id FROM tbl_wishlist WHERE user_id = $userId"
            );
            return array_map(fn($r) => (int)($r->PRODUCT_ID ?? 0), $rows);
        } catch (Exception $e) { return []; }
    }

    /** Adds product if not present, removes if already present. Returns 'added'|'removed'. */
    public function toggleWishlist(int $userId, int $productId): string
    {
        if ($userId <= 0 || $productId <= 0) return 'error';
        try {
            $exists = $this->dbHelper->select(
                "SELECT wishlist_id FROM tbl_wishlist
                 WHERE user_id = $userId AND product_id = $productId LIMIT 1"
            );
            if (!empty($exists)) {
                $this->dbHelper->update(
                    "DELETE FROM tbl_wishlist WHERE user_id = $userId AND product_id = $productId"
                );
                return 'removed';
            }
            $this->dbHelper->insert(
                "INSERT INTO tbl_wishlist (user_id, product_id) VALUES ($userId, $productId)"
            );
            return 'added';
        } catch (Exception $e) { return 'error'; }
    }

    /** Full product rows for the wishlist page, newest first. */
    public function getWishlistProducts(int $userId): array
    {
        if ($userId <= 0) return [];
        try {
            return $this->dbHelper->select(
                "SELECT p.product_id, p.product_name, p.product_code,
                        p.product_amt, p.offer_percentage,
                        p.total_remaining, p.rating, p.label,
                        c.product_category_name,
                        (SELECT pi.product_image_path
                         FROM tbl_product_img pi
                         WHERE pi.product_id  = p.product_id
                           AND pi.image_for   = 'Product'
                           AND pi.display_flag = 'Yes'
                         ORDER BY COALESCE(pi.priorty,999) ASC, pi.image_id ASC
                         LIMIT 1) AS thumb_path,
                        w.created_at AS wishlisted_at
                 FROM tbl_wishlist w
                 JOIN tbl_product p ON p.product_id = w.product_id
                 LEFT JOIN tbl_product_category c
                        ON c.product_category_id = p.product_category_id
                 WHERE w.user_id = $userId
                   AND p.product_status = 'Active'
                 ORDER BY w.created_at DESC"
            );
        } catch (Exception $e) { return []; }
    }

    /* ── Homepage Sections toggle ───────────────────────────── */
    public function getActiveSections(): array
    {
        try {
            $rows = $this->dbHelper->select(
                "SELECT section_id, name, type FROM tbl_section WHERE sts = 1"
            );
            $map = [];
            foreach ($rows as $r) {
                $map[(string)($r->TYPE ?? '')] = [
                    'id'   => (int)($r->SECTION_ID ?? 0),
                    'name' => (string)($r->NAME     ?? ''),
                ];
            }
            return $map;
        } catch (Exception $e) { return []; }
    }

    /* ── Hero Banners ───────────────────────────────────────── */
    public function getBanners(): array
    {
        try {
            return $this->dbHelper->select(
                "SELECT banner_id, banner_name, banner_img_ext, priority,
                        banner_description, hyperlink, tags, color,
                        btn_one, btn_one_link, btn_two, btn_two_link
                 FROM tbl_banner
                 WHERE display_flag = 'Yes'
                 ORDER BY COALESCE(priority, 999) ASC, banner_id ASC"
            );
        } catch (Exception $e) { return []; }
    }

    public function getCatalogCategories(): array
    {
        try {
            return $this->dbHelper->select(
                "SELECT c.product_category_id, c.product_category_name,
                        c.parent_category_id,
                        COALESCE(p.product_category_name,'') AS parent_name,
                        COUNT(tp.product_id) AS product_count
                 FROM tbl_product_category c
                 LEFT JOIN tbl_product_category p
                        ON p.product_category_id = c.parent_category_id
                 LEFT JOIN tbl_product tp
                        ON tp.product_category_id = c.product_category_id
                       AND tp.product_status = 'Active'
                 GROUP BY c.product_category_id, c.product_category_name,
                          c.parent_category_id, parent_name
                 HAVING product_count > 0
                 ORDER BY parent_name ASC, c.product_category_name ASC"
            );
        } catch (Exception $e) { return []; }
    }

    /* Manufacturers page — all active manufacturers */
    public function getAllManufacturers(): array
    {
        try {
            return $this->dbHelper->select(
                "SELECT manufacturer_id, name, logo, description,
                        product_category_ids, should_display_in_home
                 FROM tbl_manufacturers
                 WHERE status = 1
                 ORDER BY name ASC"
            );
        } catch (Exception $e) { return []; }
    }

    /* All product categories flat (for name lookup) */
    public function getAllCategoriesFlat(): array
    {
        try {
            return $this->dbHelper->select(
                "SELECT product_category_id, product_category_name, parent_category_id
                 FROM tbl_product_category
                 ORDER BY product_category_name ASC"
            );
        } catch (Exception $e) { return []; }
    }

    /* CATALOG — Manufacturers (from tbl_manufacturers, no count) */
    public function getCatalogManufacturers(): array
    {
        try {
            return $this->dbHelper->select(
                "SELECT manufacturer_id, name, product_category_ids
                 FROM tbl_manufacturers
                 WHERE status = 1
                 ORDER BY name ASC"
            );
        } catch (Exception $e) { return []; }
    }

    /* ── Home page: featured manufacturers (should_display_in_home=Yes) ── */
    public function getFeaturedManufacturers(): array
    {
        try {
            return $this->dbHelper->select(
                "SELECT manufacturer_id, name, logo, product_category_ids
                 FROM tbl_manufacturers
                 WHERE status = 1
                   AND should_display_in_home = 'Yes'
                 ORDER BY name ASC"
            );
        } catch (Exception $e) { return []; }
    }

    /* CATALOG — Build WHERE clause from filter array */
    private function catWhere(array $f): string
    {
        $parts = ["p.product_status = 'Active'"];
        if (!empty($f['q'])) {
            $q = addslashes(trim($f['q']));
            $parts[] = "(p.product_code LIKE '%$q%' OR p.product_name LIKE '%$q%' OR p.product_description LIKE '%$q%')";
        }
        if (!empty($f['cat_ids'])) {
            /* Multiple category IDs (comma-separated) from manufacturer filter */
            $rawIds = preg_replace('/[^0-9,]/', '', (string)$f['cat_ids']);
            $ids    = array_filter(array_map('intval', explode(',', $rawIds)));
            if (!empty($ids)) {
                $idList  = implode(',', $ids);
                $parts[] = "p.product_category_id IN (
                    SELECT product_category_id FROM tbl_product_category
                    WHERE product_category_id IN ($idList) OR parent_category_id IN ($idList)
                )";
            } else {
                /* Empty/invalid cat_ids → no products should match */
                $parts[] = "1 = 0";
            }
        } elseif (!empty($f['cat_id']) && (int)$f['cat_id'] > 0) {
            $cid = (int)$f['cat_id'];
            /* Include the selected category AND all its children so clicking
               a parent category returns products from all sub-categories too */
            $parts[] = "p.product_category_id IN (
                SELECT product_category_id FROM tbl_product_category
                WHERE product_category_id = $cid OR parent_category_id = $cid
            )";
        }
        if (!empty($f['mfr']) && empty($f['cat_ids'])) {
            /* mfr LIKE filter only when NOT coming from a manufacturer link.
               When cat_ids is set, filtering is already done by category IDs;
               mfr is only used for sidebar pre-selection in that case. */
            $mfr = addslashes(trim($f['mfr']));
            $parts[] = "(p.product_name LIKE '%$mfr%' OR p.product_code LIKE '%$mfr%')";
        }
        if (isset($f['min_price']) && (float)$f['min_price'] > 0) {
            $parts[] = "p.product_amt >= " . (float)$f['min_price'];
        }
        if (isset($f['max_price']) && (float)$f['max_price'] > 0) {
            $parts[] = "p.product_amt <= " . (float)$f['max_price'];
        }
        if (isset($f['min_rating']) && (float)$f['min_rating'] > 0) {
            $parts[] = "p.rating >= " . (float)$f['min_rating'];
        }
        if (!empty($f['in_stock'])) {
            $parts[] = "p.total_remaining > 0";
        }
        if (!empty($f['is_new'])) {
            $parts[] = "LOWER(p.label) = 'new'";
        }
        return implode(' AND ', $parts);
    }

    /* CATALOG — Sort clause */
    private function catOrder(string $sort): string
    {
        return match($sort) {
            'price-asc'  => "p.product_amt ASC",
            'price-desc' => "p.product_amt DESC",
            'rating'     => "p.rating DESC, p.total_sold DESC",
            'new'        => "p.product_entry_date DESC, p.product_id DESC",
            'name'       => "p.product_name ASC",
            default      => "COALESCE(p.priorty,999) ASC, p.total_sold DESC, p.product_id DESC",
        };
    }

    /* CATALOG — Paginated products */
    public function getCatalogProducts(array $f, int $page = 1, int $perPage = 16): array
    {
        try {
            $where  = $this->catWhere($f);
            $order  = $this->catOrder($f['sort'] ?? 'featured');
            $offset = max(0, ($page - 1) * $perPage);
            return $this->dbHelper->select(
                "SELECT p.product_id, p.product_name, p.product_code,
                        p.product_amt, p.offer_percentage, p.product_discount,
                        p.total_remaining, p.total_sold, p.rating,
                        p.label, p.product_entry_date,
                        p.product_description,
                        c.product_category_name, c.product_category_id,
                        (SELECT pi.product_image_path
                         FROM tbl_product_img pi
                         WHERE pi.product_id  = p.product_id
                           AND pi.image_for   = 'Product'
                           AND pi.display_flag = 'Yes'
                         ORDER BY COALESCE(pi.priorty,999) ASC, pi.image_id ASC
                         LIMIT 1) AS thumb_path
                 FROM tbl_product p
                 LEFT JOIN tbl_product_category c
                        ON c.product_category_id = p.product_category_id
                 WHERE $where
                 ORDER BY $order
                 LIMIT $perPage OFFSET $offset"
            );
        } catch (Exception $e) { return []; }
    }

    /* CATALOG — Total count for pagination */
    public function getCatalogCount(array $f): int
    {
        try {
            $where = $this->catWhere($f);
            $r = $this->dbHelper->select(
                "SELECT COUNT(*) AS CNT FROM tbl_product p WHERE $where"
            );
            return (int)(float)($r[0]->CNT ?? 0);
        } catch (Exception $e) { return 0; }
    }

    /* ══════════════════════════════════════════════════════════
       SEARCH — Categories for dropdown (grouped by parent)
    ══════════════════════════════════════════════════════════ */
    public function getSearchCategories(): array
    {
        try {
            return $this->dbHelper->select(
                "SELECT c.product_category_id, c.product_category_name,
                        c.parent_category_id,
                        COALESCE(p.product_category_name, '') AS parent_name
                 FROM tbl_product_category c
                 LEFT JOIN tbl_product_category p
                        ON p.product_category_id = c.parent_category_id
                 WHERE EXISTS (
                     SELECT 1 FROM tbl_product tp
                     WHERE tp.product_category_id = c.product_category_id
                       AND tp.product_status = 'Active'
                 )
                 ORDER BY
                     COALESCE(p.product_category_name, c.product_category_name) ASC,
                     c.product_category_name ASC"
            );
        } catch (Exception $e) { return []; }
    }

    /* ══════════════════════════════════════════════════════════
       SEARCH — Product suggestions (elastic-style)
       Searches: product_code, product_name, product_description
       Optionally filtered by category_id
    ══════════════════════════════════════════════════════════ */
    public function getSearchSuggestions(string $q, int $catId = 0, int $limit = 8): array
    {
        try {
            $safe    = addslashes(trim($q));
            if (strlen($safe) < 2) return [];
            $catWhere = $catId > 0
                ? " AND p.product_category_id IN (SELECT product_category_id FROM tbl_product_category WHERE product_category_id = $catId OR parent_category_id = $catId)"
                : '';
            $rows = $this->dbHelper->select(
                "SELECT p.product_id, p.product_name, p.product_code,
                        p.product_amt, p.label, p.total_remaining,
                        p.rating, p.offer_percentage,
                        c.product_category_name
                 FROM tbl_product p
                 LEFT JOIN tbl_product_category c
                        ON c.product_category_id = p.product_category_id
                 WHERE p.product_status = 'Active'
                   AND (
                         p.product_code        LIKE '%$safe%'
                      OR p.product_name        LIKE '%$safe%'
                      OR p.product_description LIKE '%$safe%'
                   )
                   $catWhere
                 ORDER BY
                   CASE
                     WHEN p.product_code  LIKE '$safe%' THEN 0
                     WHEN p.product_name  LIKE '$safe%' THEN 1
                     WHEN p.product_code  LIKE '%$safe%' THEN 2
                     ELSE 3
                   END,
                   p.total_sold DESC
                 LIMIT $limit"
            );
            return $rows;
        } catch (Exception $e) { return []; }
    }

    /* SEARCH — Total result count (for "See all N results") */
    public function getSearchCount(string $q, int $catId = 0): int
    {
        try {
            $safe    = addslashes(trim($q));
            if (strlen($safe) < 2) return 0;
            $catWhere = $catId > 0
                ? " AND product_category_id IN (SELECT product_category_id FROM tbl_product_category WHERE product_category_id = $catId OR parent_category_id = $catId)"
                : '';
            $r = $this->dbHelper->select(
                "SELECT COUNT(*) AS CNT
                 FROM tbl_product
                 WHERE product_status = 'Active'
                   AND (
                         product_code        LIKE '%$safe%'
                      OR product_name        LIKE '%$safe%'
                      OR product_description LIKE '%$safe%'
                   )
                   $catWhere"
            );
            return (int)(float)($r[0]->CNT ?? 0);
        } catch (Exception $e) { return 0; }
    }

    public function getQuoteCategories(): array
    {
        try {
            return $this->dbHelper->select(
                "SELECT c.product_category_id, c.product_category_name, c.parent_category_id,
                        COALESCE(p.product_category_name, c.product_category_name) AS group_label
                 FROM tbl_product_category c
                 LEFT JOIN tbl_product_category p ON c.parent_category_id = p.product_category_id
                 WHERE EXISTS (
                     SELECT 1 FROM tbl_product tp
                     WHERE tp.product_category_id = c.product_category_id
                       AND tp.product_status = 'Active'
                 )
                 ORDER BY group_label ASC, c.product_category_name ASC"
            );
        } catch (Exception $e) { return []; }
    }

    /* ── Quote: products by category ───────────────────────── */
    public function getProductsByCategory(int $catId): array
    {
        try {
            $w = $catId > 0 ? " AND p.product_category_id = $catId" : '';
            return $this->dbHelper->select(
                "SELECT p.product_id, p.product_name, p.product_code, p.product_amt,
                        p.total_product AS stock
                 FROM tbl_product p
                 WHERE p.product_id > 0 AND p.product_status = 'Active'$w
                 ORDER BY p.product_name ASC"
            );
        } catch (Exception $e) { return []; }
    }

    /* ── Address: list ──────────────────────────────────────── */
    public function getUserAddresses(int $userId): array
    {
        return $this->dbHelper->select(
            "SELECT user_address_id, label, user_name, company_name,
                    address AS address_notes,
                    address_line_one, address_line_two, landmark,
                    city, state, zip, country, country_id,
                    delivery_phone_no, mobile_country_code,
                    recipient_name, recipient_email, recipient_contact
             FROM tbl_user_address
             WHERE user_id = $userId
             ORDER BY user_address_id DESC"
        );
    }

    /* ── Address: save (insert) — returns new address ID ───── */
    public function saveDeliveryAddress(array $d, int $userId): int
    {
        if ($userId <= 0) throw new RuntimeException('Invalid user.');
        $label        = in_array($d['label'] ?? 'Other', ['Home','Office','Other']) ? $d['label'] : 'Other';
        $userName     = addslashes(trim($d['user_name']          ?? ''));
        $company      = addslashes(trim($d['company_name']       ?? ''));
        $phone        = addslashes(trim($d['delivery_phone_no']  ?? ''));
        $mcc          = (int)($d['mobile_country_code']          ?? 0);
        $countryId    = (int)($d['country_id']                   ?? 0);
        $line1        = addslashes(trim($d['address_line_one']   ?? ''));
        $line2        = addslashes(trim($d['address_line_two']   ?? ''));
        $lmk          = addslashes(trim($d['landmark']           ?? ''));
        $city         = addslashes(trim($d['city']               ?? ''));
        $state        = addslashes(trim($d['state']              ?? ''));
        $zip          = addslashes(trim($d['zip']                ?? ''));
        $country      = addslashes(trim($d['country']            ?? ''));
        $addrNotes    = addslashes(trim($d['address']            ?? ''));
        $recipName    = addslashes(trim($d['recipient_name']     ?? ''));
        $recipEmail   = addslashes(trim($d['recipient_email']    ?? ''));
        $recipContact = addslashes(trim($d['recipient_contact']  ?? ''));
        return (int)$this->dbHelper->insert(
            "INSERT INTO tbl_user_address
             (user_id, label, user_name, company_name, delivery_phone_no, mobile_country_code,
              address_line_one, address_line_two, landmark,
              city, state, zip, country, address, country_id,
              recipient_name, recipient_email, recipient_contact)
             VALUES($userId,'$label','$userName','$company','$phone',$mcc,
                    '$line1','$line2','$lmk','$city','$state','$zip','$country','$addrNotes',$countryId,
                    '$recipName','$recipEmail','$recipContact')"
        );
    }

    /* ── Payment modes (active, ordered by priority) ────────── */
    public function getPaymentModes(): array
    {
        try {
            return $this->dbHelper->select(
                "SELECT payment_mode_id, name, payment_type, description
                 FROM tbl_payment_mode
                 WHERE sts = 1
                 ORDER BY priority ASC, payment_mode_id ASC"
            );
        } catch (Exception $e) { error_log('getPaymentModes: ' . $e->getMessage()); return []; }
    }

    /* ── Country by ID (includes shipping_amt) ──────────────── */
    public function getCountryById(int $id): ?object
    {
        if ($id <= 0) return null;
        try {
            $r = $this->dbHelper->select(
                "SELECT country_id, country, shipping_amt FROM tbl_country WHERE country_id = $id LIMIT 1"
            );
            return $r[0] ?? null;
        } catch (Exception $e) { error_log('getCountryById: ' . $e->getMessage()); return null; }
    }

    /* ── Place order (server-side price calculation) ─────────── */
    /**
     * Validate cart + calculate totals without writing to DB.
     * Used by PayPal `create` step so no orphaned orders are created
     * if PayPal later fails.  Returns the same fields placeOrder uses,
     * minus order_id / order_number.
     */
    public function calculateOrderPreview(array $data, int $userId): array
    {
        try {
            if ($userId <= 0) return ['ok' => false, 'msg' => 'Authentication required.'];

            $deliveryAddrId = (int)($data['delivery_address_id'] ?? 0);
            if ($deliveryAddrId <= 0) return ['ok' => false, 'msg' => 'Please select a delivery address.'];

            $addrRows = $this->dbHelper->select(
                "SELECT ua.*, c.shipping_amt AS country_shipping_amt, c.country AS country_name
                 FROM tbl_user_address ua
                 LEFT JOIN tbl_country c ON c.country_id = ua.country_id
                 WHERE ua.user_address_id = $deliveryAddrId AND ua.user_id = $userId LIMIT 1"
            );
            if (empty($addrRows)) return ['ok' => false, 'msg' => 'Invalid delivery address.'];

            $paymentModeId = (int)($data['payment_mode_id'] ?? 0);
            $pmRows = $this->dbHelper->select(
                "SELECT payment_mode_id, name, payment_type FROM tbl_payment_mode
                 WHERE payment_mode_id = $paymentModeId AND sts = 1 LIMIT 1"
            );
            if (empty($pmRows)) return ['ok' => false, 'msg' => 'Invalid payment method.'];

            $items = $data['items'] ?? [];
            if (empty($items)) return ['ok' => false, 'msg' => 'No items in order.'];

            $subtotal = 0.0;
            $validItems = 0;
            foreach ($items as $item) {
                $prodId = (int)($item['product_id'] ?? 0);
                $qty    = max(1, min(9999, (int)($item['qty'] ?? 1)));
                if ($prodId <= 0) continue;
                $pRows = $this->dbHelper->select(
                    "SELECT product_amt FROM tbl_product
                     WHERE product_id = $prodId AND product_status = 'Active' LIMIT 1"
                );
                if (empty($pRows)) continue;
                $subtotal += round((float)($pRows[0]->PRODUCT_AMT ?? 0) * $qty, 2);
                $validItems++;
            }
            if ($validItems === 0) return ['ok' => false, 'msg' => 'No valid products found.'];

            $addr        = $addrRows[0];
            $subtotal    = round($subtotal, 2);
            $shippingAmt = round((float)($addr->COUNTRY_SHIPPING_AMT ?? 19.99), 2);
            $vatNumber   = trim(preg_replace('/\s+/', '', $data['vat_number'] ?? ''));
            $vatPct      = 19.0;
            $vatAmt      = 0.0;
            $vatExempt   = false;
            if ($vatNumber !== '' && preg_match('/^[A-Z]{2}[0-9A-Z]{2,13}$/i', $vatNumber)) {
                $vatExempt = true;
            } else {
                $vatAmt = round($subtotal * $vatPct / 100, 2);
            }
            $finalTotal = round($subtotal + $shippingAmt + $vatAmt, 2);

            return [
                'ok'          => true,
                'final_total' => $finalTotal,
                'subtotal'    => $subtotal,
                'shipping_amt'=> $shippingAmt,
                'vat_amt'     => $vatAmt,
                'vat_exempt'  => $vatExempt,
                'vat_number'  => $vatNumber,
            ];
        } catch (\Throwable $e) {
            error_log('calculateOrderPreview: ' . $e->getMessage());
            return ['ok' => false, 'msg' => 'Could not validate order. Please try again.'];
        }
    }

    public function placeOrder(array $data, int $userId): array
    {
        try {
            if ($userId <= 0) {
                error_log("placeOrder FAIL: userId=$userId is invalid");
                return ['ok' => false, 'msg' => 'Authentication required.'];
            }

            /* Validate delivery address belongs to user */
            $deliveryAddrId = (int)($data['delivery_address_id'] ?? 0);
            if ($deliveryAddrId <= 0) {
                error_log("placeOrder FAIL: delivery_address_id missing or zero for user=$userId. data_keys=" . implode(',', array_keys($data)));
                return ['ok' => false, 'msg' => 'Please select a delivery address.'];
            }

            $addrRows = $this->dbHelper->select(
                "SELECT ua.*, c.shipping_amt AS country_shipping_amt, c.country AS country_name
                 FROM tbl_user_address ua
                 LEFT JOIN tbl_country c ON c.country_id = ua.country_id
                 WHERE ua.user_address_id = $deliveryAddrId AND ua.user_id = $userId LIMIT 1"
            );
            if (empty($addrRows)) {
                error_log("placeOrder FAIL: delivery_address_id=$deliveryAddrId not found for user=$userId");
                return ['ok' => false, 'msg' => 'Invalid delivery address.'];
            }
            $addr = $addrRows[0];

            /* Billing address — default to delivery */
            $billingAddrId = $deliveryAddrId;
            if (empty($data['billing_same'])) {
                $billId = (int)($data['billing_address_id'] ?? 0);
                if ($billId > 0) {
                    $bc = $this->dbHelper->select(
                        "SELECT user_address_id FROM tbl_user_address WHERE user_address_id = $billId AND user_id = $userId LIMIT 1"
                    );
                    if (!empty($bc)) $billingAddrId = $billId;
                }
            }

            /* Validate payment mode */
            $paymentModeId = (int)($data['payment_mode_id'] ?? 0);
            $pmRows = $this->dbHelper->select(
                "SELECT payment_mode_id, name, payment_type FROM tbl_payment_mode WHERE payment_mode_id = $paymentModeId AND sts = 1 LIMIT 1"
            );
            if (empty($pmRows)) {
                error_log("placeOrder FAIL: payment_mode_id=$paymentModeId not found or inactive for user=$userId");
                return ['ok' => false, 'msg' => 'Invalid payment method.'];
            }
            $pm = $pmRows[0];

            /* Resolve items with SERVER-SIDE prices — never trust client */
            $items = $data['items'] ?? [];
            if (empty($items)) {
                error_log("placeOrder FAIL: no items in order data for user=$userId");
                return ['ok' => false, 'msg' => 'No items in order.'];
            }

            $orderItems = [];
            $subtotal   = 0.0;
            foreach ($items as $item) {
                $prodId = (int)($item['product_id'] ?? 0);
                $qty    = max(1, min(9999, (int)($item['qty'] ?? 1)));
                if ($prodId <= 0) continue;

                $pRows = $this->dbHelper->select(
                    "SELECT product_id, product_category_id, product_name, product_code, product_amt
                     FROM tbl_product WHERE product_id = $prodId AND product_status = 'Active' LIMIT 1"
                );
                if (empty($pRows)) {
                    error_log("placeOrder WARN: product_id=$prodId not found or inactive for user=$userId — skipping");
                    continue;
                }
                $p = $pRows[0];

                $unitPrice = round((float)($p->PRODUCT_AMT ?? 0), 2);
                $lineTotal = round($unitPrice * $qty, 2);
                $subtotal += $lineTotal;
                $orderItems[] = [
                    'product_id'          => $prodId,
                    'product_category_id' => (int)(float)($p->PRODUCT_CATEGORY_ID ?? 0),
                    'product_name'        => (string)($p->PRODUCT_NAME ?? ''),
                    'product_code'        => (string)($p->PRODUCT_CODE ?? ''),
                    'quantity'            => $qty,
                    'product_amt'         => $unitPrice,
                    'final_amt'           => $lineTotal,
                ];
            }
            if (empty($orderItems)) {
                error_log("placeOrder FAIL: all items invalid for user=$userId. items_sent=" . count($items));
                return ['ok' => false, 'msg' => 'No valid products found.'];
            }
            $subtotal = round($subtotal, 2);

            /* Shipping from delivery address country */
            $shippingAmt = round((float)($addr->COUNTRY_SHIPPING_AMT ?? 19.99), 2);

            /* VAT 19% — rebate if valid EU VAT number provided */
            $vatPct    = 19.0;
            $vatNumber = trim(preg_replace('/\s+/', '', $data['vat_number'] ?? ''));
            $vatAmt    = 0.0;
            $vatExempt = false;
            if ($vatNumber !== '' && preg_match('/^[A-Z]{2}[0-9A-Z]{2,13}$/i', $vatNumber)) {
                $vatExempt = true; /* Valid EU VAT format — exempt */
            } else {
                $vatAmt = round($subtotal * $vatPct / 100, 2);
            }
            $finalTotal = round($subtotal + $shippingAmt + $vatAmt, 2);

            /* Payment status by mode */
            $orderModeMap    = ['Paypal'=>'Payment Gateway','Bank Transfer'=>'Bank Transfer','Invoice'=>'Invoice'];
            $orderMode       = $orderModeMap[(string)($pm->PAYMENT_TYPE ?? '')] ?? 'Payment Gateway';
            $paymentStatus   = ($orderMode === 'Invoice') ? 'Not Required' : 'Payment Pending';

            $year    = (int)date('Y');
            $vatSafe = addslashes($vatNumber);
            $omSafe  = addslashes($orderMode);
            $psSafe  = addslashes($paymentStatus);

            /* Insert order with temporary placeholder; real number assigned after we have the PK */
            $orderId = (int)$this->dbHelper->insert(
                "INSERT INTO tbl_user_order
                 (user_id, order_number, order_year, order_mode, source_order, order_status, payment_status,
                  order_total_amt, shipping_amt, tax_total_amount, final_total_amt,
                  user_address_id, billing_user_address_id, vat_number, order_date, created_at, updated_at)
                 VALUES($userId,'PENDING',$year,'$omSafe','Website','Order Pending','$psSafe',
                  $subtotal,$shippingAmt,$vatAmt,$finalTotal,
                  $deliveryAddrId,$billingAddrId,'$vatSafe',NOW(),NOW(),NOW())"
            );
            if ($orderId <= 0) {
                error_log("placeOrder FAIL: DB INSERT returned 0 for user=$userId — check tbl_user_order constraints");
                return ['ok' => false, 'msg' => 'Failed to save order. Please try again.'];
            }

            /* Generate order number: YYYY-NN (min 2-digit padded PK) */
            $orderNumber = $year . str_pad((string)$orderId, 2, '0', STR_PAD_LEFT);
            $this->dbHelper->update("UPDATE tbl_user_order SET order_number='$orderNumber' WHERE user_order_id=$orderId");

            /* Insert order items */
            foreach ($orderItems as $oi) {
                $pid   = (int)$oi['product_id'];
                $catId = (int)$oi['product_category_id'];
                $qty   = (int)$oi['quantity'];
                $amt   = (float)$oi['product_amt'];
                $fin   = (float)$oi['final_amt'];
                $this->dbHelper->insert(
                    "INSERT INTO tbl_user_order_item
                     (user_order_id, product_category_id, product_id, quantity,
                      product_amt, discount_percentage, discount_amt,
                      tax_percentage, tax_amt, final_amt, item_status, order_type, created_at)
                     VALUES($orderId,$catId,$pid,$qty,$amt,0,0,$vatPct,0,$fin,'Active','Order',NOW())"
                );
            }

            /* Insert order history */
            $this->dbHelper->insert(
                "INSERT INTO tbl_user_order_history
                 (user_order_id, history_type, history_order_status, history_payment_status,
                  history_remarks, changed_by_user_id, created_at)
                 VALUES($orderId,'Order','Order Pending','$psSafe','Order placed by customer',$userId,NOW())"
            );

            /* Send confirmation emails — skip for Payment Gateway (PayPal).
               Email is sent after successful payment capture instead. */
            if ($orderMode !== 'Payment Gateway') {
                try {
                    $this->sendOrderConfirmationEmails($orderId, $userId, $orderItems, [
                        'subtotal'     => $subtotal,
                        'shipping_amt' => $shippingAmt,
                        'vat_amt'      => $vatAmt,
                        'final_total'  => $finalTotal,
                        'vat_exempt'   => $vatExempt,
                        'vat_number'   => $vatNumber,
                        'order_number' => $orderNumber,
                        'payment_mode' => (string)($pm->NAME ?? $orderMode),
                        'payment_type' => (string)($pm->PAYMENT_TYPE ?? $orderMode),
                        'addr'         => $addr,
                    ]);
                } catch (\Throwable $me) { error_log('placeOrder mail: ' . $me->getMessage()); }
            }

            $bankDetails = [];
            if ((string)($pm->PAYMENT_TYPE ?? '') === 'Bank Transfer') {
                $bankDetails = array_map(fn($b) => [
                    'account_holder_name' => (string)($b->ACCOUNT_HOLDER_NAME ?? ''),
                    'bank_name'           => (string)($b->BANK_NAME           ?? ''),
                    'branch_name'         => (string)($b->BRANCH_NAME         ?? ''),
                    'account_number'      => (string)($b->ACCOUNT_NUMBER      ?? ''),
                    'swift_code'          => (string)($b->SWIFT_CODE          ?? ''),
                    'iban_number'         => (string)($b->IBAN_NUMBER         ?? ''),
                    'currency'            => (string)($b->CURRENCY            ?? 'EURO'),
                ], $this->getBankDetails());
            }
            return ['ok' => true, 'order_id' => $orderId, 'order_number' => $orderNumber,
                    'payment_type' => (string)($pm->PAYMENT_TYPE ?? ''),
                    'bank_details' => $bankDetails,
                    'final_total'  => $finalTotal];
        } catch (\Throwable $e) {
            error_log('placeOrder EXCEPTION [' . get_class($e) . ']: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            return ['ok' => false, 'msg' => 'Something went wrong. Please try again.'];
        }
    }

    /* ── PayPal: update order payment status after capture/cancel ── */
    /**
     * @param string $status  'Payment Successful' | 'Payment Failed'  (matches tbl_user_order.payment_status enum)
     * @param string $ppTxnId PayPal capture transaction ID (empty string when not applicable)
     */
    public function updateOrderPaymentStatus(int $orderId, string $status, string $ppTxnId, int $userId): void
    {
        try {
            $sSafe  = addslashes($status);
            $txSafe = addslashes($ppTxnId);

            /* When payment succeeds, also confirm the order */
            $isSuccess    = ($status === 'Payment Successful');
            $newOrderStatus = $isSuccess ? 'Order Confirmed' : 'Order Pending';
            $orderStatusSql = $isSuccess ? ", order_status='Order Confirmed'" : '';

            /* Store PayPal transaction ID in the dedicated pay_pal_tx_id column */
            $txnSql = ($ppTxnId !== '') ? ", pay_pal_tx_id='$txSafe'" : '';

            $this->dbHelper->update(
                "UPDATE tbl_user_order
                 SET payment_status='$sSafe'$orderStatusSql$txnSql, updated_at=NOW()
                 WHERE user_order_id=$orderId"
            );

            /* Build a meaningful remark */
            if ($isSuccess && $ppTxnId !== '') {
                $remarks = "PayPal payment captured successfully. Transaction ID: $ppTxnId";
            } elseif ($status === 'Payment Failed') {
                $remarks = 'PayPal payment failed or was cancelled by user.';
            } else {
                $remarks = "Payment status updated to: $status";
            }
            $rSafe          = addslashes($remarks);
            $orderStatusSafe = addslashes($newOrderStatus);

            /* Payment history row */
            $this->dbHelper->insert(
                "INSERT INTO tbl_user_order_history
                 (user_order_id, history_type, history_order_status, history_payment_status,
                  history_remarks, changed_by_user_id, created_at)
                 VALUES($orderId,'Payment','$orderStatusSafe','$sSafe','$rSafe',$userId,NOW())"
            );

            /* On success, also log an Order-type history row for the status change */
            if ($isSuccess) {
                $this->dbHelper->insert(
                    "INSERT INTO tbl_user_order_history
                     (user_order_id, history_type, history_order_status, history_payment_status,
                      history_remarks, changed_by_user_id, created_at)
                     VALUES($orderId,'Order','Order Confirmed','Payment Successful',
                            'Order confirmed after successful PayPal payment.',$userId,NOW())"
                );
            }

            /* Send confirmation email only when payment actually succeeded */
            if ($isSuccess) {
                $this->triggerPaymentGatewayOrderEmail($orderId, $userId);
            }

        } catch (\Throwable $e) {
            error_log('updateOrderPaymentStatus [' . get_class($e) . ']: ' . $e->getMessage());
        }
    }

    /* ── Send order confirmation email after PayPal capture ─── */
    public function triggerPaymentGatewayOrderEmail(int $orderId, int $userId): void
    {
        try {
            $oRows = $this->dbHelper->select(
                "SELECT o.order_number, o.order_total_amt, o.shipping_amt,
                        o.tax_total_amount, o.final_total_amt, o.vat_number,
                        ua.recipient_name, ua.company_name, ua.address_line_one,
                        ua.address_line_two, ua.city, ua.state, ua.zip, ua.country,
                        ua.recipient_email, ua.delivery_phone_no
                 FROM tbl_user_order o
                 LEFT JOIN tbl_user_address ua ON ua.user_address_id = o.user_address_id
                 WHERE o.user_order_id = $orderId AND o.user_id = $userId LIMIT 1"
            );
            if (empty($oRows)) {
                error_log("triggerPaymentGatewayOrderEmail: order $orderId not found for user $userId");
                return;
            }
            $ord = $oRows[0];

            $itemRows = $this->dbHelper->select(
                "SELECT oi.quantity, oi.product_amt, oi.final_amt,
                        p.product_id, p.product_category_id, p.product_name, p.product_code
                 FROM tbl_user_order_item oi
                 LEFT JOIN tbl_product p ON p.product_id = oi.product_id
                 WHERE oi.user_order_id = $orderId AND oi.order_type = 'Order'
                 ORDER BY oi.user_order_item_id ASC"
            );

            $items = [];
            foreach ($itemRows as $it) {
                $items[] = [
                    'product_id'          => (int)(float)($it->PRODUCT_ID ?? 0),
                    'product_category_id' => (int)(float)($it->PRODUCT_CATEGORY_ID ?? 0),
                    'product_name'        => (string)($it->PRODUCT_NAME ?? ''),
                    'product_code'        => (string)($it->PRODUCT_CODE ?? ''),
                    'quantity'            => (int)(float)($it->QUANTITY ?? 1),
                    'product_amt'         => (float)($it->PRODUCT_AMT ?? 0),
                    'final_amt'           => (float)($it->FINAL_AMT ?? 0),
                ];
            }

            $vatNumber = trim((string)($ord->VAT_NUMBER ?? ''));
            $vatExempt = $vatNumber !== '' && preg_match('/^[A-Z]{2}[0-9A-Z]{2,13}$/i', $vatNumber);

            $this->sendOrderConfirmationEmails($orderId, $userId, $items, [
                'subtotal'     => (float)($ord->ORDER_TOTAL_AMT  ?? 0),
                'shipping_amt' => (float)($ord->SHIPPING_AMT     ?? 0),
                'vat_amt'      => (float)($ord->TAX_TOTAL_AMOUNT ?? 0),
                'final_total'  => (float)($ord->FINAL_TOTAL_AMT  ?? 0),
                'vat_exempt'   => $vatExempt,
                'vat_number'   => $vatNumber,
                'order_number' => (string)($ord->ORDER_NUMBER ?? ''),
                'payment_mode' => 'PayPal',
                'payment_type' => 'Paypal',
                'addr'         => $ord,
            ]);
        } catch (\Throwable $e) {
            error_log('triggerPaymentGatewayOrderEmail [' . get_class($e) . ']: ' . $e->getMessage());
        }
    }

    /* ── Fetch a failed Payment Gateway order for payment retry ── */
    public function getPaymentGatewayOrderForRetry(int $orderId, int $userId): ?object
    {
        try {
            $rows = $this->dbHelper->select(
                "SELECT user_order_id, order_number, final_total_amt, order_mode
                 FROM tbl_user_order
                 WHERE user_order_id  = $orderId
                   AND user_id        = $userId
                   AND order_mode     = 'Payment Gateway'
                   AND order_status   = 'Order Pending'
                   AND payment_status IN ('Payment Pending','Payment Failed')
                 LIMIT 1"
            );
            return $rows[0] ?? null;
        } catch (\Throwable $e) {
            error_log('getPaymentGatewayOrderForRetry: ' . $e->getMessage());
            return null;
        }
    }

    /* ── Reset a Payment Gateway order back to Payment Pending ── */
    public function resetOrderToPaymentPending(int $orderId, int $userId): void
    {
        try {
            $this->dbHelper->update(
                "UPDATE tbl_user_order
                 SET payment_status='Payment Pending', pay_pal_tx_id=NULL, updated_at=NOW()
                 WHERE user_order_id=$orderId AND user_id=$userId"
            );
        } catch (\Throwable $e) {
            error_log('resetOrderToPaymentPending: ' . $e->getMessage());
        }
    }

    /* ── Delete an unpaid Payment Gateway order (user self-service) ── */
    public function deleteUnpaidPaymentGatewayOrder(int $orderId, int $userId): array
    {
        try {
            if ($orderId <= 0 || $userId <= 0) {
                return ['ok' => false, 'msg' => 'Invalid request.'];
            }

            /* Only allow delete when order is still unprocessed */
            $rows = $this->dbHelper->select(
                "SELECT user_order_id FROM tbl_user_order
                 WHERE user_order_id = $orderId
                   AND user_id       = $userId
                   AND order_mode    = 'Payment Gateway'
                   AND order_status  = 'Order Pending'
                   AND payment_status IN ('Payment Pending','Payment Failed')
                 LIMIT 1"
            );
            if (empty($rows)) {
                return ['ok' => false, 'msg' => 'Order not found or cannot be deleted.'];
            }

            /* FK CASCADE removes order items + history automatically */
            $this->dbHelper->update(
                "DELETE FROM tbl_user_order WHERE user_order_id = $orderId AND user_id = $userId"
            );
            return ['ok' => true];
        } catch (\Throwable $e) {
            error_log('deleteUnpaidPaymentGatewayOrder [' . get_class($e) . ']: ' . $e->getMessage());
            return ['ok' => false, 'msg' => 'Could not delete order. Please try again.'];
        }
    }

    /* ── Order confirmation emails ───────────────────────────── */
    private function sendOrderConfirmationEmails(int $orderId, int $userId, array $items, array $o): void
    {
        $co           = $this->getCompanyInfo();
        $companyName  = trim((string)($co->COMPANY_NAME   ?? 'Sinelec Technologies'));
        $supportEmail = trim((string)($co->SUPPORT_MAIL_ID ?? ''));
        $coPhone      = trim((string)($co->CONTACT_NUMBER  ?? ''));
        $coEmail      = trim((string)($co->EMAIL           ?? ''));
        $coAddr       = trim((string)($co->ADDRESS         ?? ''));

        $siteProto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $siteBase  = $siteProto . '://' . ($_SERVER['HTTP_HOST'] ?? 'sinelec-tech.com');
        $rawLogo   = trim((string)($co->LOGO ?? ''));
        $logoUrl   = ($rawLogo !== '')
            ? ((strpos($rawLogo, 'http') === 0) ? $rawLogo : $siteBase . '/' . ltrim($rawLogo, '/'))
            : $siteBase . '/assets/logo.png';

        $uRows  = $this->dbHelper->select(
            "SELECT name, communication_email_id FROM tbl_user WHERE user_id = $userId LIMIT 1"
        );
        $uName  = !empty($uRows) ? trim((string)($uRows[0]->NAME                  ?? '')) : '';
        $uEmail = !empty($uRows) ? trim((string)($uRows[0]->COMMUNICATION_EMAIL_ID ?? '')) : '';

        $addr        = $o['addr'];
        $recipEmail  = trim((string)($addr->RECIPIENT_EMAIL ?? ''));

        $orderNum   = $o['order_number'];
        $payMode    = $o['payment_mode'];
        $subtotal   = (float)$o['subtotal'];
        $shipping   = (float)$o['shipping_amt'];
        $vatAmt     = (float)$o['vat_amt'];
        $total      = (float)$o['final_total'];

        $addrLine = implode(', ', array_filter([
            (string)($addr->ADDRESS_LINE_ONE ?? ''), (string)($addr->ADDRESS_LINE_TWO ?? ''),
            (string)($addr->CITY ?? ''), (string)($addr->STATE ?? ''),
            (string)($addr->ZIP ?? ''), (string)($addr->COUNTRY ?? ''),
        ]));

        $bankDetails = ($o['payment_type'] ?? '') === 'Bank Transfer' ? $this->getBankDetails() : [];

        $html = $this->buildOrderEmailHtml([
            'company_name' => $companyName, 'logo_url' => $logoUrl,
            'co_phone' => $coPhone, 'co_email' => $coEmail, 'co_addr' => $coAddr,
            'site_base' => $siteBase,
            'order_number' => $orderNum, 'payment_mode' => $payMode,
            'payment_type' => $o['payment_type'] ?? '',
            'user_name' => $uName, 'items' => $items,
            'subtotal' => $subtotal, 'shipping_amt' => $shipping,
            'vat_amt' => $vatAmt, 'final_total' => $total,
            'vat_number' => $o['vat_number'] ?? '',
            'delivery_addr' => $addrLine,
            'bank_details'  => $bankDetails,
        ]);

        $subject = 'Order Confirmation #' . $orderNum . ' — ' . $companyName;
        $batch   = [];
        if ($uEmail !== '' && filter_var($uEmail, FILTER_VALIDATE_EMAIL))
            $batch[] = ['to_mail_id' => $uEmail, 'subject' => $subject, 'body' => $html];
        if ($recipEmail !== '' && $recipEmail !== $uEmail && filter_var($recipEmail, FILTER_VALIDATE_EMAIL))
            $batch[] = ['to_mail_id' => $recipEmail, 'subject' => $subject, 'body' => $html];
        if ($supportEmail !== '' && filter_var($supportEmail, FILTER_VALIDATE_EMAIL))
            $batch[] = ['to_mail_id' => $supportEmail,
                        'subject'    => 'New Order #' . $orderNum . ' — ' . $companyName,
                        'body'       => $html];
        if (!empty($batch)) sinelec_send_mail($batch);
    }

    /* ── Build order confirmation email HTML ─────────────────── */
    private function buildOrderEmailHtml(array $o): string
    {
        $e           = 'htmlspecialchars';
        $companyName = $e($o['company_name'] ?? 'Sinelec Technologies');
        $logoUrl     = $o['logo_url'] ?? '';
        $siteBase    = rtrim($o['site_base'] ?? '', '/');
        $orderNum    = $e($o['order_number'] ?? '');
        $payMode     = $e($o['payment_mode'] ?? '');
        $payType     = $o['payment_type'] ?? '';
        $uName       = $e($o['user_name'] ?? '');
        $subtotal    = number_format((float)($o['subtotal'] ?? 0), 2);
        $shipping    = number_format((float)($o['shipping_amt'] ?? 0), 2);
        $vatAmt      = number_format((float)($o['vat_amt'] ?? 0), 2);
        $total       = number_format((float)($o['final_total'] ?? 0), 2);
        $vatNum      = $e($o['vat_number'] ?? '');
        $delivAddr   = $e($o['delivery_addr'] ?? '');
        $coPhone     = $e($o['co_phone'] ?? '');
        $coEmail     = $e($o['co_email'] ?? '');

        $rows = '';
        foreach (($o['items'] ?? []) as $idx => $item) {
            $bg = ($idx % 2 === 0) ? '#ffffff' : '#f8fafc';
            $rows .= '<tr style="background:' . $bg . ';">'
                . '<td style="padding:10px 14px;font-size:13px;color:#1e293b;border-bottom:1px solid #e2e8f0;">'
                    . $e((string)($item['product_name'] ?? ''))
                    . (($item['product_code'] ?? '') !== '' ? ' <span style="color:#94a3b8;font-size:11px;">[' . $e((string)$item['product_code']) . ']</span>' : '')
                . '</td>'
                . '<td style="padding:10px 14px;text-align:center;font-size:13px;border-bottom:1px solid #e2e8f0;">' . (int)($item['quantity'] ?? 0) . '</td>'
                . '<td style="padding:10px 14px;text-align:right;font-size:13px;border-bottom:1px solid #e2e8f0;">€' . number_format((float)($item['product_amt'] ?? 0), 2) . '</td>'
                . '<td style="padding:10px 14px;text-align:right;font-size:13px;font-weight:700;border-bottom:1px solid #e2e8f0;color:#1d4ed8;">€' . number_format((float)($item['final_amt'] ?? 0), 2) . '</td>'
                . '</tr>';
        }

        $payNote = '';
        if ($payType === 'Bank Transfer') {
            $bankRows = '';
            foreach (($o['bank_details'] ?? []) as $b) {
                $bHolder  = $e((string)($b->ACCOUNT_HOLDER_NAME ?? ''));
                $bBank    = $e((string)($b->BANK_NAME           ?? ''));
                $bBranch  = $e((string)($b->BRANCH_NAME         ?? ''));
                $bAcct    = $e((string)($b->ACCOUNT_NUMBER      ?? ''));
                $bSwift   = $e((string)($b->SWIFT_CODE          ?? ''));
                $bIban    = $e((string)($b->IBAN_NUMBER         ?? ''));
                $bCur     = $e((string)($b->CURRENCY            ?? 'EURO'));
                $bankRows .= '<table style="width:100%;border-collapse:collapse;margin-top:10px;">';
                if ($bHolder) $bankRows .= '<tr><td style="padding:4px 0;font-size:12px;color:#92400e;width:160px;">Account Holder</td><td style="padding:4px 0;font-size:12px;font-weight:700;color:#1e293b;">' . $bHolder . '</td></tr>';
                if ($bBank)   $bankRows .= '<tr><td style="padding:4px 0;font-size:12px;color:#92400e;">Bank</td><td style="padding:4px 0;font-size:12px;font-weight:700;color:#1e293b;">' . $bBank . ($bBranch ? ' — ' . $bBranch : '') . '</td></tr>';
                if ($bAcct)   $bankRows .= '<tr><td style="padding:4px 0;font-size:12px;color:#92400e;">Account Number</td><td style="padding:4px 0;font-size:12px;font-weight:700;color:#1e293b;letter-spacing:.5px;">' . $bAcct . '</td></tr>';
                if ($bIban)   $bankRows .= '<tr><td style="padding:4px 0;font-size:12px;color:#92400e;">IBAN</td><td style="padding:4px 0;font-size:12px;font-weight:700;color:#1e293b;letter-spacing:.5px;">' . $bIban . '</td></tr>';
                if ($bSwift)  $bankRows .= '<tr><td style="padding:4px 0;font-size:12px;color:#92400e;">SWIFT / BIC</td><td style="padding:4px 0;font-size:12px;font-weight:700;color:#1e293b;letter-spacing:.5px;">' . $bSwift . '</td></tr>';
                if ($bCur)    $bankRows .= '<tr><td style="padding:4px 0;font-size:12px;color:#92400e;">Currency</td><td style="padding:4px 0;font-size:12px;font-weight:700;color:#1e293b;">' . $bCur . '</td></tr>';
                $bankRows .= '</table>';
            }
            $payNote = '<div style="margin:18px 0;padding:16px 18px;background:#fffbeb;border-left:4px solid #f59e0b;border-radius:6px;color:#92400e;">'
                . '<strong style="font-size:13px;">Bank Transfer Instructions</strong>'
                . '<p style="font-size:12px;margin:6px 0 0;">Please transfer <strong>€' . $total . '</strong> using your order number <strong>' . $orderNum . '</strong> as the payment reference.</p>'
                . $bankRows
                . '</div>';
        } elseif ($payType === 'Invoice') {
            $payNote = '<div style="margin:18px 0;padding:14px 16px;background:#eff6ff;border-left:4px solid #3b82f6;border-radius:6px;font-size:13px;color:#1e40af;">'
                . '<strong>Invoice Payment:</strong><br>An invoice will be sent to you separately. Payment terms as per your corporate agreement.</div>';
        }

        return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,sans-serif;">'
            . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:32px 0;">'
            . '<tr><td align="center"><table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08);">'
            . '<tr><td style="background:linear-gradient(135deg,#0f172a,#1e3a5f);padding:28px 32px;">'
            . '<table width="100%"><tr>'
            . '<td>' . ($logoUrl ? '<img src="' . $logoUrl . '" height="40" alt="' . $companyName . '" style="display:block;background:#ffffff;padding:4px 8px;border-radius:6px;">' : '<span style="color:#fff;font-size:20px;font-weight:700;">' . $companyName . '</span>') . '</td>'
            . '<td align="right"><span style="color:#93c5fd;font-size:12px;">Order Confirmation</span></td>'
            . '</tr></table></td></tr>'
            . '<tr><td style="padding:28px 32px;">'
            . '<p style="font-size:16px;font-weight:700;color:#0f172a;margin:0 0 6px;">Hi ' . ($uName ?: 'there') . ',</p>'
            . '<p style="font-size:14px;color:#475569;margin:0 0 20px;">Thank you for your order! Here\'s a summary of what you ordered.</p>'
            . '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px 16px;margin-bottom:20px;">'
            . '<table width="100%"><tr>'
            . '<td style="font-size:12px;color:#64748b;">Order Number</td>'
            . '<td style="font-size:12px;color:#64748b;">Payment Method</td>'
            . '</tr><tr>'
            . '<td style="font-size:15px;font-weight:700;color:#0f172a;">' . $orderNum . '</td>'
            . '<td style="font-size:14px;font-weight:700;color:#0f172a;">' . $payMode . '</td>'
            . '</tr></table></div>'
            . $payNote
            . '<table width="100%" style="border-collapse:collapse;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;margin-bottom:20px;">'
            . '<thead><tr style="background:#f1f5f9;"><th style="padding:10px 14px;text-align:left;font-size:12px;color:#64748b;">Product</th><th style="padding:10px 14px;text-align:center;font-size:12px;color:#64748b;">Qty</th><th style="padding:10px 14px;text-align:right;font-size:12px;color:#64748b;">Price</th><th style="padding:10px 14px;text-align:right;font-size:12px;color:#64748b;">Total</th></tr></thead>'
            . '<tbody>' . $rows . '</tbody></table>'
            . '<table width="100%" style="margin-bottom:20px;">'
            . '<tr><td style="padding:4px 0;font-size:13px;color:#64748b;">Subtotal</td><td style="text-align:right;font-size:13px;color:#1e293b;">€' . $subtotal . '</td></tr>'
            . '<tr><td style="padding:4px 0;font-size:13px;color:#64748b;">Shipping</td><td style="text-align:right;font-size:13px;color:#1e293b;">€' . $shipping . '</td></tr>'
            . '<tr><td style="padding:4px 0;font-size:13px;color:#64748b;">VAT (19%)' . ($vatNum ? ' — Exempt (' . $vatNum . ')' : '') . '</td><td style="text-align:right;font-size:13px;color:#1e293b;">€' . $vatAmt . '</td></tr>'
            . '<tr style="border-top:2px solid #e2e8f0;"><td style="padding:10px 0 4px;font-size:15px;font-weight:700;color:#0f172a;">Order Total</td><td style="text-align:right;font-size:15px;font-weight:700;color:#1d4ed8;padding:10px 0 4px;">€' . $total . '</td></tr>'
            . '</table>'
            . '<div style="background:#f8fafc;border-radius:8px;padding:12px 14px;font-size:12px;color:#64748b;margin-bottom:20px;">'
            . '<strong style="color:#374151;">Delivery Address:</strong> ' . $delivAddr . '</div>'
            . '</td></tr>'
            . '<tr><td style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:18px 32px;text-align:center;">'
            . '<p style="font-size:12px;color:#94a3b8;margin:0;">' . $companyName
            . ($coPhone ? ' · ' . $coPhone : '') . ($coEmail ? ' · ' . $coEmail : '') . '</p>'
            . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }

    /* ── Customer orders list ────────────────────────────────── */
    public function getCustomerOrders(int $userId): array
    {
        try {
            if ($userId <= 0) return [];
            $orders = $this->dbHelper->select(
                "SELECT o.user_order_id, o.order_number, o.order_date, o.order_status,
                        o.payment_status, o.order_mode, o.source_order, o.final_total_amt,
                        o.shipping_amt, o.tax_total_amount, o.order_total_amt,
                        o.vat_number, o.dispatch_courier_tracking_id,
                        cc.courier_company_name AS courier_name,
                        cc.tracking_url AS courier_tracking_tpl,
                        ua.recipient_name, ua.address_line_one, ua.address_line_two,
                        ua.city, ua.state, ua.zip, ua.country, ua.company_name AS del_company,
                        ba.recipient_name AS bil_name, ba.company_name AS bil_company,
                        ba.address_line_one AS bil_line1, ba.address_line_two AS bil_line2,
                        ba.city AS bil_city, ba.state AS bil_state,
                        ba.zip AS bil_zip, ba.country AS bil_country
                 FROM tbl_user_order o
                 LEFT JOIN tbl_user_address ua ON ua.user_address_id = o.user_address_id
                 LEFT JOIN tbl_user_address ba ON ba.user_address_id = o.billing_user_address_id
                 LEFT JOIN tbl_courier_company cc ON cc.courier_company_id = o.courier_company_id
                 WHERE o.user_id = $userId
                   AND o.order_type = 'Order'
                   AND o.order_status != 'Cart'
                   AND o.order_number != 'PENDING'
                 ORDER BY o.user_order_id DESC"
            );
            foreach ($orders as $ord) {
                $oid = (int)(float)($ord->USER_ORDER_ID ?? 0);
                $ord->items = $this->dbHelper->select(
                    "SELECT oi.quantity, oi.product_amt, oi.final_amt, oi.item_status,
                            p.product_name, p.product_code,
                            (SELECT pi.product_image_path FROM tbl_product_img pi
                             WHERE pi.product_id = oi.product_id
                               AND pi.image_for = 'Product'
                               AND pi.display_flag = 'Yes'
                             ORDER BY pi.priorty ASC LIMIT 1) AS image_path
                     FROM tbl_user_order_item oi
                     LEFT JOIN tbl_product p ON p.product_id = oi.product_id
                     WHERE oi.user_order_id = $oid AND oi.order_type = 'Order'
                     ORDER BY oi.user_order_item_id ASC"
                );
                $ord->history = $this->dbHelper->select(
                    "SELECT history_order_status, history_payment_status, history_remarks, created_at
                     FROM tbl_user_order_history
                     WHERE user_order_id = $oid AND history_type = 'Order'
                     ORDER BY user_order_history_id ASC"
                );
            }
            return $orders;
        } catch (Exception $e) { error_log('getCustomerOrders: ' . $e->getMessage()); return []; }
    }

    /* ── Customer: fetch single order by number (ownership-checked) ─ */
    public function getCustomerOrderByNumber(string $orderNumber, int $userId): ?object
    {
        try {
            if ($userId <= 0 || $orderNumber === '') return null;
            $on = addslashes($orderNumber);
            $rows = $this->dbHelper->select(
                "SELECT o.*,
                        ua.recipient_name, ua.company_name AS del_company,
                        ua.address_line_one, ua.address_line_two,
                        ua.city, ua.state, ua.zip, ua.country,
                        ba.recipient_name AS bil_name, ba.company_name AS bil_company,
                        ba.address_line_one AS bil_line1, ba.address_line_two AS bil_line2,
                        ba.city AS bil_city, ba.state AS bil_state,
                        ba.zip AS bil_zip, ba.country AS bil_country,
                        cc.courier_company_name, cc.tracking_url AS courier_tracking_tpl,
                        u.name AS cust_name, u.communication_email_id AS cust_email
                 FROM tbl_user_order o
                 LEFT JOIN tbl_user_address ua ON ua.user_address_id  = o.user_address_id
                 LEFT JOIN tbl_user_address ba ON ba.user_address_id  = o.billing_user_address_id
                 LEFT JOIN tbl_courier_company cc ON cc.courier_company_id = o.courier_company_id
                 LEFT JOIN tbl_user u ON u.user_id = o.user_id
                 WHERE o.order_number = '$on' AND o.user_id = $userId LIMIT 1"
            );
            if (empty($rows)) return null;
            $ord = $rows[0];
            $oid = (int)(float)($ord->USER_ORDER_ID ?? 0);
            $ord->items = $this->dbHelper->select(
                "SELECT oi.quantity, oi.product_amt, oi.final_amt,
                        p.product_name, p.product_code,
                        (SELECT pi.product_image_path FROM tbl_product_img pi
                         WHERE pi.product_id = oi.product_id
                           AND pi.image_for = 'Product'
                           AND pi.display_flag = 'Yes'
                         ORDER BY pi.priorty ASC LIMIT 1) AS image_path
                 FROM tbl_user_order_item oi
                 LEFT JOIN tbl_product p ON p.product_id = oi.product_id
                 WHERE oi.user_order_id = $oid AND oi.order_type = 'Order'
                 ORDER BY oi.user_order_item_id ASC"
            );
            return $ord;
        } catch (Exception $e) { error_log('getCustomerOrderByNumber: ' . $e->getMessage()); return null; }
    }

    /* ── Address: update ────────────────────────────────────── */
    public function updateDeliveryAddress(int $addrId, array $d, int $userId): void
    {
        if ($addrId <= 0 || $userId <= 0) throw new RuntimeException('Invalid address or user.');
        $label        = in_array($d['label'] ?? 'Other', ['Home','Office','Other']) ? $d['label'] : 'Other';
        $userName     = addslashes(trim($d['user_name']          ?? ''));
        $company      = addslashes(trim($d['company_name']       ?? ''));
        $phone        = addslashes(trim($d['delivery_phone_no']  ?? ''));
        $mcc          = (int)($d['mobile_country_code']          ?? 0);
        $line1        = addslashes(trim($d['address_line_one']   ?? ''));
        $line2        = addslashes(trim($d['address_line_two']   ?? ''));
        $lmk          = addslashes(trim($d['landmark']           ?? ''));
        $city         = addslashes(trim($d['city']               ?? ''));
        $state        = addslashes(trim($d['state']              ?? ''));
        $zip          = addslashes(trim($d['zip']                ?? ''));
        $country      = addslashes(trim($d['country']            ?? ''));
        $addrNotes    = addslashes(trim($d['address']            ?? ''));
        $recipName    = addslashes(trim($d['recipient_name']     ?? ''));
        $recipEmail   = addslashes(trim($d['recipient_email']    ?? ''));
        $recipContact = addslashes(trim($d['recipient_contact']  ?? ''));
        $this->dbHelper->update(
            "UPDATE tbl_user_address SET
               label='$label', user_name='$userName', company_name='$company',
               delivery_phone_no='$phone', mobile_country_code=$mcc,
               address_line_one='$line1', address_line_two='$line2', landmark='$lmk',
               city='$city', state='$state', zip='$zip', country='$country', address='$addrNotes',
               recipient_name='$recipName', recipient_email='$recipEmail', recipient_contact='$recipContact'
             WHERE user_address_id=$addrId AND user_id=$userId"
        );
    }

    /* ── Address: delete ────────────────────────────────────── */
    public function deleteDeliveryAddress(int $addrId, int $userId): void
    {
        if ($addrId <= 0 || $userId <= 0) throw new RuntimeException('Invalid address or user.');
        $this->dbHelper->update(
            "DELETE FROM tbl_user_address WHERE user_address_id=$addrId AND user_id=$userId"
        );
    }

    /* ── Quote: submit (authenticated users only) ───────────── */
    public function submitCustomerQuote(array $d, int $userId): array
    {
        try {
            if ($userId <= 0) return ['ok' => false, 'msg' => 'Authentication required.'];

            $products = $d['products'] ?? [];
            if (empty($products)) return ['ok' => false, 'msg' => 'Please add at least one product.'];

            /* Save / resolve delivery address */
            $deliveryAddrId = 0;
            if (!empty($d['delivery_address_id'])) {
                $deliveryAddrId = (int)$d['delivery_address_id'];
            } elseif (!empty($d['new_delivery_address']) && is_array($d['new_delivery_address'])) {
                $deliveryAddrId = $this->saveDeliveryAddress($d['new_delivery_address'], $userId);
            }
            if ($deliveryAddrId <= 0) return ['ok' => false, 'msg' => 'Please select or enter a delivery address.'];

            /* Save / resolve billing address */
            $billingAddrId = $deliveryAddrId;
            if (empty($d['billing_same_as_delivery'])) {
                if (!empty($d['billing_address_id'])) {
                    $billingAddrId = (int)$d['billing_address_id'];
                } elseif (!empty($d['new_billing_address']) && is_array($d['new_billing_address'])) {
                    $billingAddrId = $this->saveDeliveryAddress($d['new_billing_address'], $userId);
                }
            }

            /* Calculate total */
            $totalAmt = 0;
            foreach ($products as $p) {
                $totalAmt += (float)($p['price'] ?? 0) * (int)($p['qty'] ?? 0);
            }
            $totalAmt = round($totalAmt, 2);

            $vatNum = addslashes(trim($d['vat_number'] ?? ''));
            $remark = addslashes(trim($d['notes']      ?? ''));

            $sql = "INSERT INTO tbl_enquiry_quote
                 (user_id, user_address_id, billing_address_id, vat_number,
                  enquiry_status, enquiry_vat_amt, enquiry_shipping_amt, enquiry_total_amt,
                  discount_percentage, discount_amt, customer_order_no, customer_supplier_no, remark)
                 VALUES($userId, $deliveryAddrId, $billingAddrId, '$vatNum',
                  'Quotation Pending', 0, 0, $totalAmt, 0, 0, '', '', '$remark')";
            $qid = (int)$this->dbHelper->insert($sql);
            if ($qid <= 0) return ['ok' => false, 'msg' => 'Failed to save quotation. Please try again.'];

            /* Insert products */
            foreach ($products as $p) {
                $catId  = (int)($p['cat_id']  ?? 0);
                $prodId = (int)($p['prod_id'] ?? 0);
                $qty    = (int)($p['qty']     ?? 0);
                $price  = (float)($p['price'] ?? 0);
                if ($prodId <= 0 || $qty <= 0) continue;
                $this->dbHelper->insert(
                    "INSERT INTO tbl_enquiry_quote_product
                     (enquiry_quote_id, product_category_id, product_id, product_quantity, product_amt, product_discount_pct)
                     VALUES($qid, $catId, $prodId, $qty, $price, 0)"
                );
            }

            /* ── Send confirmation emails (non-fatal) ─────────── */
            try {
                $co           = $this->getCompanyInfo();
                $supportEmail = trim((string)($co->SUPPORT_MAIL_ID ?? ''));
                $companyName  = trim((string)($co->COMPANY_NAME    ?? 'Sinelec Technologies'));
                $coPhone      = trim((string)($co->CONTACT_NUMBER  ?? ''));
                $coEmail      = trim((string)($co->EMAIL           ?? ''));
                $coAddr       = trim((string)($co->ADDRESS         ?? ''));

                /* Build absolute logo URL */
                $siteProto  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $siteHost   = $_SERVER['HTTP_HOST'] ?? 'sinelec-tech.com';
                $siteBase   = $siteProto . '://' . $siteHost;
                $rawLogo    = trim((string)($co->LOGO ?? ''));
                $logoUrl    = ($rawLogo !== '')
                    ? ((strpos($rawLogo, 'http') === 0) ? $rawLogo : $siteBase . '/' . ltrim($rawLogo, '/'))
                    : $siteBase . '/assets/logo.png';

                /* User info from DB */
                $uRow   = $this->dbHelper->select(
                    "SELECT name, communication_email_id, communication_mobile_num,
                            communication_mobile_num_isd, company_name
                     FROM tbl_user WHERE user_id=$userId LIMIT 1"
                );
                $uName  = !empty($uRow) ? trim((string)($uRow[0]->NAME                       ?? '')) : '';
                $uEmail = !empty($uRow) ? trim((string)($uRow[0]->COMMUNICATION_EMAIL_ID      ?? '')) : '';
                $uPhone = !empty($uRow) ? trim((string)($uRow[0]->COMMUNICATION_MOBILE_NUM    ?? '')) : '';
                $uIsd   = !empty($uRow) ? trim((string)($uRow[0]->COMMUNICATION_MOBILE_NUM_ISD?? '')) : '';
                $uComp  = !empty($uRow) ? trim((string)($uRow[0]->COMPANY_NAME               ?? '')) : '';
                $uPhoneFull = ($uIsd !== '' && $uPhone !== '') ? '+' . ltrim($uIsd, '+') . ' ' . $uPhone : $uPhone;

                /* Delivery address details */
                $dAddr = $this->dbHelper->select(
                    "SELECT label, user_name, company_name, delivery_phone_no, mobile_country_code,
                            address_line_one, address_line_two, landmark,
                            city, state, zip, country,
                            recipient_name, recipient_email, recipient_contact
                     FROM tbl_user_address WHERE user_address_id=$deliveryAddrId LIMIT 1"
                );
                $dA = !empty($dAddr) ? $dAddr[0] : null;
                $recipEmail = $dA ? trim((string)($dA->RECIPIENT_EMAIL ?? '')) : '';

                /* Product rows + pricing */
                $prodItems  = [];
                $grandTotal = 0;
                foreach ($products as $p) {
                    $pid   = (int)($p['prod_id'] ?? 0);
                    $pRow  = $this->dbHelper->select(
                        "SELECT product_name, product_code FROM tbl_product WHERE product_id=$pid LIMIT 1"
                    );
                    $pName = !empty($pRow) ? trim((string)($pRow[0]->PRODUCT_NAME ?? "Product #$pid")) : "Product #$pid";
                    $pCode = !empty($pRow) ? trim((string)($pRow[0]->PRODUCT_CODE ?? '')) : '';
                    $qty   = (int)($p['qty']   ?? 0);
                    $price = (float)($p['price'] ?? 0);
                    $lt    = round($price * $qty, 2);
                    $grandTotal += $lt;
                    $prodItems[] = compact('pName', 'pCode', 'qty', 'price', 'lt');
                }
                $grandTotal   = round($grandTotal, 2);
                $vatAmt       = round($grandTotal * 0.18, 2);
                $vatRebate    = (stripslashes($vatNum) !== '') ? $vatAmt : 0;
                $estimatedTotal = round($grandTotal + $vatAmt - $vatRebate, 2);

                /* Dispatch emails */
                $mailBatch = [];

                /* 1 ── Customer (logged-in user) */
                if ($uEmail !== '' && filter_var($uEmail, FILTER_VALIDATE_EMAIL)) {
                    $mailBatch[] = [
                        'to_mail_id' => $uEmail,
                        'subject'    => 'Quote Request #' . $qid . ' Received — ' . $companyName,
                        'body'       => $this->buildQuoteEmailHtml([
                            'type'           => 'customer',
                            'qid'            => $qid,
                            'greeting'       => 'Hi ' . ($uName ?: 'there') . ',',
                            'intro'          => 'Thank you for submitting your quote request. Our sales team will review it and get back to you within <strong>24 business hours</strong> with a detailed, revised quotation.',
                            'prod_items'     => $prodItems,
                            'grand_total'    => $grandTotal,
                            'vat_amt'        => $vatAmt,
                            'vat_rebate'     => $vatRebate,
                            'estimated_total'=> $estimatedTotal,
                            'vat_number'     => stripslashes($vatNum),
                            'notes'          => stripslashes($remark),
                            'delivery_addr'  => $dA,
                            'company_name'   => $companyName,
                            'logo_url'       => $logoUrl,
                            'co_phone'       => $coPhone,
                            'co_email'       => $coEmail,
                            'co_addr'        => $coAddr,
                            'site_base'      => $siteBase,
                        ]),
                    ];
                }

                /* 2 ── Delivery recipient (only if valid and different from customer) */
                if ($recipEmail !== ''
                    && filter_var($recipEmail, FILTER_VALIDATE_EMAIL)
                    && strtolower($recipEmail) !== strtolower($uEmail)
                ) {
                    $recipName = $dA ? trim((string)($dA->RECIPIENT_NAME ?? '')) : '';
                    $mailBatch[] = [
                        'to_mail_id' => $recipEmail,
                        'subject'    => 'An Order Has Been Arranged for Your Delivery — Quote #' . $qid,
                        'body'       => $this->buildQuoteEmailHtml([
                            'type'           => 'recipient',
                            'qid'            => $qid,
                            'greeting'       => 'Dear ' . ($recipName ?: 'Recipient') . ',',
                            'intro'          => 'Please be informed that a product order has been arranged and will be delivered to your address. Below is a summary of the items requested.',
                            'prod_items'     => $prodItems,
                            'grand_total'    => $grandTotal,
                            'vat_amt'        => $vatAmt,
                            'vat_rebate'     => $vatRebate,
                            'estimated_total'=> $estimatedTotal,
                            'vat_number'     => '',
                            'notes'          => '',
                            'delivery_addr'  => $dA,
                            'company_name'   => $companyName,
                            'logo_url'       => $logoUrl,
                            'co_phone'       => $coPhone,
                            'co_email'       => $coEmail,
                            'co_addr'        => $coAddr,
                            'site_base'      => $siteBase,
                        ]),
                    ];
                }

                /* 3 ── Sales team */
                if ($supportEmail !== '' && filter_var($supportEmail, FILTER_VALIDATE_EMAIL)) {
                    $mailBatch[] = [
                        'to_mail_id' => $supportEmail,
                        'subject'    => '[New Quote #' . $qid . '] ' . ($uName ?: $uEmail) . ' — ' . count($prodItems) . ' item(s) — €' . number_format($estimatedTotal, 2),
                        'body'       => $this->buildQuoteEmailHtml([
                            'type'           => 'admin',
                            'qid'            => $qid,
                            'greeting'       => 'New Quote Request — #' . $qid,
                            'intro'          => 'A new quotation has been submitted via the website. Full details are below.',
                            'prod_items'     => $prodItems,
                            'grand_total'    => $grandTotal,
                            'vat_amt'        => $vatAmt,
                            'vat_rebate'     => $vatRebate,
                            'estimated_total'=> $estimatedTotal,
                            'vat_number'     => stripslashes($vatNum),
                            'notes'          => stripslashes($remark),
                            'delivery_addr'  => $dA,
                            'customer'       => [
                                'name'    => $uName,
                                'email'   => $uEmail,
                                'phone'   => $uPhoneFull,
                                'company' => $uComp,
                            ],
                            'company_name'   => $companyName,
                            'logo_url'       => $logoUrl,
                            'co_phone'       => $coPhone,
                            'co_email'       => $coEmail,
                            'co_addr'        => $coAddr,
                            'site_base'      => $siteBase,
                        ]),
                    ];
                }

                if (!empty($mailBatch)) {
                    sinelec_send_mail($mailBatch);
                }

            } catch (\Throwable $mailErr) {
                error_log('submitCustomerQuote mail: ' . $mailErr->getMessage());
            }

            return ['ok' => true, 'quote_id' => $qid];
        } catch (Exception $e) {
            error_log('submitCustomerQuote: ' . $e->getMessage());
            return ['ok' => false, 'msg' => 'Something went wrong. Please try again.'];
        }
    }

    /* ── Update user profile details ────────────────────────── */
    public function updateUserDetails(int $userId, string $name, string $phone, string $isd, string $company): void
    {
        try {
            if ($userId <= 0) return;
            $n = addslashes($name);
            $p = addslashes($phone);
            $i = addslashes($isd);
            $c = addslashes($company);
            $this->dbHelper->update(
                "UPDATE tbl_user SET name='$n', communication_mobile_num='$p', communication_mobile_num_isd='$i', company_name='$c' WHERE user_id=$userId"
            );
        } catch (Exception $e) { error_log('updateUserDetails: ' . $e->getMessage()); }
    }

    /* ── Get all quotes for a customer (with products) ─────── */
    public function getCustomerQuotes(int $userId): array
    {
        try {
            if ($userId <= 0) return [];
            $quotes = $this->dbHelper->select(
                "SELECT enquiry_quote_id, enquiry_date, enquiry_status,
                        enquiry_total_amt, enquiry_vat_amt, enquiry_shipping_amt,
                        vat_number, remark
                 FROM tbl_enquiry_quote
                 WHERE user_id = $userId
                 ORDER BY enquiry_quote_id DESC"
            );
            foreach ($quotes as $q) {
                $qid = (int)(float)($q->ENQUIRY_QUOTE_ID ?? 0);
                $q->products = $this->dbHelper->select(
                    "SELECT eqp.product_quantity, eqp.product_amt, eqp.product_discount_pct,
                            p.product_name, p.product_code
                     FROM tbl_enquiry_quote_product eqp
                     LEFT JOIN tbl_product p ON p.product_id = eqp.product_id
                     WHERE eqp.enquiry_quote_id = $qid
                     ORDER BY eqp.enquiry_quote_product_id ASC"
                );
            }
            return $quotes;
        } catch (Exception $e) { error_log('getCustomerQuotes: ' . $e->getMessage()); return []; }
    }

    /* ── Build responsive HTML email for quote notifications ─── */
    private function buildQuoteEmailHtml(array $o): string
    {
        $e    = 'htmlspecialchars';   /* shorthand */
        $type = $o['type'] ?? 'customer';   /* customer | recipient | admin */
        $qid  = (int)($o['qid'] ?? 0);

        /* Header gradient by audience */
        $gradients = [
            'customer'  => 'linear-gradient(135deg,#0f172a 0%,#1e3a5f 100%)',
            'recipient' => 'linear-gradient(135deg,#064e3b 0%,#065f46 100%)',
            'admin'     => 'linear-gradient(135deg,#1e40af 0%,#2563eb 100%)',
        ];
        $headerGrad = $gradients[$type] ?? $gradients['customer'];

        $companyName = $e($o['company_name'] ?? 'Sinelec Technologies');
        $logoUrl     = $o['logo_url']  ?? '';
        $coPhone     = $e($o['co_phone']  ?? '');
        $coEmail     = $e($o['co_email']  ?? '');
        $coAddr      = $e($o['co_addr']   ?? '');
        $siteBase    = rtrim($o['site_base'] ?? '', '/');

        /* ── Product table ─────────────────────────────────── */
        $prodRows = '';
        foreach (($o['prod_items'] ?? []) as $idx => $p) {
            $bg = ($idx % 2 === 0) ? '#ffffff' : '#f8fafc';
            $prodRows .= '<tr style="background:' . $bg . ';">'
                . '<td style="padding:10px 14px;font-size:13px;color:#1e293b;border-bottom:1px solid #e2e8f0;">'
                    . $e($p['pName'])
                    . ($p['pCode'] !== '' ? ' <span style="color:#94a3b8;font-size:11px;">[' . $e($p['pCode']) . ']</span>' : '')
                . '</td>'
                . '<td style="padding:10px 14px;font-size:13px;text-align:center;border-bottom:1px solid #e2e8f0;color:#475569;">' . (int)$p['qty'] . '</td>'
                . '<td style="padding:10px 14px;font-size:13px;text-align:right;border-bottom:1px solid #e2e8f0;color:#475569;">€' . number_format((float)$p['price'], 2) . '</td>'
                . '<td style="padding:10px 14px;font-size:13px;font-weight:700;text-align:right;border-bottom:1px solid #e2e8f0;color:#1d4ed8;">€' . number_format((float)$p['lt'], 2) . '</td>'
                . '</tr>';
        }
        $grandTotal    = (float)($o['grand_total']     ?? 0);
        $vatAmt        = (float)($o['vat_amt']         ?? 0);
        $vatRebate     = (float)($o['vat_rebate']      ?? 0);
        $estimatedTotal= (float)($o['estimated_total'] ?? $grandTotal);
        $vatNumber     = trim($o['vat_number'] ?? '');
        $hasRebate     = ($vatRebate > 0 && $vatNumber !== '');

        $pricingRows = '<tr style="background:#f1f5f9;">'
            . '<td colspan="3" style="padding:9px 14px;font-size:12px;font-weight:700;text-align:right;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Subtotal</td>'
            . '<td style="padding:9px 14px;font-size:13px;font-weight:700;text-align:right;color:#374151;">€' . number_format($grandTotal, 2) . '</td>'
            . '</tr>'
            . '<tr style="background:#f1f5f9;">'
            . '<td colspan="3" style="padding:9px 14px;font-size:12px;font-weight:700;text-align:right;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">VAT (18%)</td>'
            . '<td style="padding:9px 14px;font-size:13px;font-weight:700;text-align:right;color:#374151;">€' . number_format($vatAmt, 2) . '</td>'
            . '</tr>';
        if ($hasRebate) {
            $pricingRows .= '<tr style="background:#f0fdf4;">'
                . '<td colspan="3" style="padding:9px 14px;font-size:12px;font-weight:700;text-align:right;color:#15803d;text-transform:uppercase;letter-spacing:.5px;">VAT Rebate (−18%)</td>'
                . '<td style="padding:9px 14px;font-size:13px;font-weight:700;text-align:right;color:#15803d;">−€' . number_format($vatRebate, 2) . '</td>'
                . '</tr>';
        }
        $pricingRows .= '<tr style="background:#eff6ff;">'
            . '<td colspan="3" style="padding:11px 14px;font-size:13px;font-weight:800;text-align:right;color:#1e40af;">ESTIMATED TOTAL</td>'
            . '<td style="padding:11px 14px;font-size:15px;font-weight:800;text-align:right;color:#1d4ed8;">€' . number_format($estimatedTotal, 2) . '</td>'
            . '</tr>';

        $prodTable = '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;">'
            . '<tr style="background:#1e293b;">'
            . '<th style="padding:10px 14px;font-size:11px;font-weight:700;text-align:left;color:#94a3b8;text-transform:uppercase;letter-spacing:.6px;">Product</th>'
            . '<th style="padding:10px 14px;font-size:11px;font-weight:700;text-align:center;color:#94a3b8;text-transform:uppercase;letter-spacing:.6px;">Qty</th>'
            . '<th style="padding:10px 14px;font-size:11px;font-weight:700;text-align:right;color:#94a3b8;text-transform:uppercase;letter-spacing:.6px;">Unit&nbsp;Price</th>'
            . '<th style="padding:10px 14px;font-size:11px;font-weight:700;text-align:right;color:#94a3b8;text-transform:uppercase;letter-spacing:.6px;">Line&nbsp;Total</th>'
            . '</tr>'
            . $prodRows
            . $pricingRows
            . '</table>';

        /* ── Delivery address block (all fields) ───────────── */
        $dA       = $o['delivery_addr'] ?? null;
        $addrHtml = '';
        if ($dA) {
            $dCompany  = trim((string)($dA->COMPANY_NAME    ?? ''));
            $dContact  = trim((string)($dA->USER_NAME       ?? ''));
            $dLine1    = trim((string)($dA->ADDRESS_LINE_ONE ?? ''));
            $dLine2    = trim((string)($dA->ADDRESS_LINE_TWO ?? ''));
            /* fall back to combined `address` field if line1 empty */
            if ($dLine1 === '') $dLine1 = trim((string)($dA->ADDRESS ?? ''));
            $dLandmark = trim((string)($dA->LANDMARK         ?? ''));
            $dCity     = trim((string)($dA->CITY             ?? ''));
            $dState    = trim((string)($dA->STATE            ?? ''));
            $dZip      = trim((string)($dA->ZIP              ?? ''));
            $dCountry  = trim((string)($dA->COUNTRY          ?? ''));
            $dMcc      = (int)($dA->MOBILE_COUNTRY_CODE      ?? 0);
            $dPhone    = trim((string)($dA->DELIVERY_PHONE_NO ?? ''));
            $dPhoneFmt = $dPhone !== '' ? ($dMcc > 0 ? '+' . $dMcc . ' ' . $dPhone : $dPhone) : '';
            $dRcptName = trim((string)($dA->RECIPIENT_NAME   ?? ''));
            $dRcptEmail= trim((string)($dA->RECIPIENT_EMAIL  ?? ''));
            $dRcptPhone= trim((string)($dA->RECIPIENT_CONTACT ?? ''));
            $dLabel    = trim((string)($dA->LABEL            ?? ''));
            $csz       = implode(', ', array_filter([$dCity, $dState, $dZip]));

            $addrHtml = '<div style="margin-top:24px;">'
                . '<h3 style="margin:0 0 12px;font-size:13px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #e2e8f0;padding-bottom:8px;">Delivery Address</h3>'
                . '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px 18px;">';
            if ($dLabel !== '') {
                $addrHtml .= '<div style="display:inline-block;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;background:#e2e8f0;color:#475569;padding:2px 8px;border-radius:3px;margin-bottom:8px;">' . $e($dLabel) . '</div>';
            }
            if ($dCompany !== '') $addrHtml .= '<div style="font-size:14px;font-weight:700;color:#0f172a;margin-bottom:2px;">' . $e($dCompany) . '</div>';
            if ($dContact !== '') $addrHtml .= '<div style="font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">' . $e($dContact) . '</div>';
            if ($dLine1   !== '') $addrHtml .= '<div style="font-size:13px;color:#475569;">' . $e($dLine1) . '</div>';
            if ($dLine2   !== '') $addrHtml .= '<div style="font-size:13px;color:#475569;">' . $e($dLine2) . '</div>';
            if ($dLandmark!== '') $addrHtml .= '<div style="font-size:12px;color:#64748b;font-style:italic;">Near: ' . $e($dLandmark) . '</div>';
            if ($csz      !== '') $addrHtml .= '<div style="font-size:13px;color:#475569;">' . $e($csz) . '</div>';
            if ($dCountry !== '') $addrHtml .= '<div style="font-size:13px;font-weight:600;color:#1e293b;">' . $e($dCountry) . '</div>';
            if ($dPhoneFmt!== '') $addrHtml .= '<div style="font-size:13px;color:#475569;margin-top:6px;">&#128222; ' . $e($dPhoneFmt) . '</div>';
            /* Recipient details */
            if ($dRcptName !== '' || $dRcptEmail !== '' || $dRcptPhone !== '') {
                $addrHtml .= '<div style="margin-top:10px;padding-top:10px;border-top:1px dashed #e2e8f0;">'
                    . '<div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:4px;">Recipient</div>';
                if ($dRcptName  !== '') $addrHtml .= '<div style="font-size:13px;font-weight:600;color:#1e293b;">'     . $e($dRcptName) . '</div>';
                if ($dRcptEmail !== '') $addrHtml .= '<div style="font-size:12px;color:#475569;">&#9993; '              . $e($dRcptEmail) . '</div>';
                if ($dRcptPhone !== '') $addrHtml .= '<div style="font-size:12px;color:#475569;">&#128222; '            . $e($dRcptPhone) . '</div>';
                $addrHtml .= '</div>';
            }
            $addrHtml .= '</div></div>';
        }

        /* ── Notes ─────────────────────────────────────────── */
        $notes    = trim($o['notes'] ?? '');
        $notesHtml = $notes !== '' ? '<div style="margin-top:20px;padding:14px 16px;background:#fffbeb;border-left:3px solid #f59e0b;border-radius:0 6px 6px 0;">'
            . '<p style="margin:0;font-size:13px;color:#92400e;"><strong>Additional Notes:</strong><br>' . $e($notes) . '</p>'
            . '</div>' : '';

        /* ── VAT number notice (customer / admin only) ──────── */
        $vatHtml = ($vatNumber !== '' && $type !== 'recipient')
            ? '<div style="margin-top:16px;padding:10px 14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;font-size:13px;color:#166534;">'
              . '&#10003; VAT / Tax Number provided: <strong>' . $e($vatNumber) . '</strong> — VAT rebate applied.'
              . '</div>'
            : '';

        /* ── Customer info panel (admin only) ──────────────── */
        $customerPanel = '';
        if ($type === 'admin' && !empty($o['customer'])) {
            $c = $o['customer'];
            $customerPanel = '<div style="margin-bottom:24px;">'
                . '<h3 style="margin:0 0 12px;font-size:13px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #e2e8f0;padding-bottom:8px;">Customer Details</h3>'
                . '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">';
            $rows = [
                ['Name',    $c['name']    ?? ''],
                ['Email',   $c['email']   ?? ''],
                ['Phone',   $c['phone']   ?? ''],
                ['Company', $c['company'] ?? ''],
            ];
            foreach ($rows as $idx => [$label, $val]) {
                if ($val === '') continue;
                $bg = ($idx % 2 === 0) ? '#f8fafc' : '#ffffff';
                $customerPanel .= '<tr style="background:' . $bg . ';">'
                    . '<td style="padding:9px 14px;font-size:13px;color:#64748b;width:100px;border-bottom:1px solid #e2e8f0;">' . $label . '</td>'
                    . '<td style="padding:9px 14px;font-size:13px;font-weight:600;color:#0f172a;border-bottom:1px solid #e2e8f0;">' . $e($val) . '</td>'
                    . '</tr>';
            }
            $customerPanel .= '</table></div>';
        }

        /* ── Disclaimer (customer + recipient only) ─────────── */
        $disclaimer = ($type !== 'admin')
            ? '<p style="margin:20px 0 0;font-size:11px;color:#94a3b8;line-height:1.6;border-top:1px solid #e2e8f0;padding-top:16px;">'
              . 'The pricing shown is indicative based on current listed rates. Final pricing, applicable taxes, '
              . 'shipping charges, and any negotiated discounts will be confirmed in your official revised quotation '
              . 'issued by our sales team.'
              . '</p>'
            : '';

        /* ── Email shell ────────────────────────────────────── */
        $html  = '<!DOCTYPE html>';
        $html .= '<html lang="en">';
        $html .= '<head>';
        $html .= '<meta charset="UTF-8">';
        $html .= '<meta name="viewport" content="width=device-width,initial-scale=1.0">';
        $html .= '<meta http-equiv="X-UA-Compatible" content="IE=edge">';
        $html .= '<title>Quote #' . $qid . '</title>';
        $html .= '<style>';
        $html .= 'body{margin:0;padding:0;background:#eef2f7;-webkit-text-size-adjust:100%;}';
        $html .= 'table{border-spacing:0;}';
        $html .= '.wrapper{width:100%;background:#eef2f7;}';
        $html .= '.card{max-width:600px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);}';
        $html .= '@media only screen and (max-width:620px){';
        $html .= '.card{border-radius:0!important;margin:0!important;}';
        $html .= '.pad{padding:20px 16px!important;}';
        $html .= '.hdr{padding:20px 16px!important;}';
        $html .= '.hdr-title{font-size:18px!important;}';
        $html .= '.prod-th,.prod-td{padding:8px 8px!important;font-size:12px!important;}';
        $html .= '.hide-mobile{display:none!important;}';
        $html .= '}';
        $html .= '</style>';
        $html .= '</head>';
        $html .= '<body style="margin:0;padding:0;background:#eef2f7;">';

        /* outer table */
        $html .= '<table class="wrapper" width="100%" cellpadding="0" cellspacing="0">';
        $html .= '<tr><td align="center" style="padding:32px 10px;">';
        $html .= '<table class="card" width="600" cellpadding="0" cellspacing="0">';

        /* — header — */
        $html .= '<tr><td class="hdr" style="background:' . $headerGrad . ';padding:28px 32px;">';
        $html .= '<table width="100%" cellpadding="0" cellspacing="0">';
        $html .= '<tr>';
        if ($logoUrl !== '') {
            $html .= '<td style="vertical-align:middle;width:60px;">';
            $html .= '<img src="' . $e($logoUrl) . '" alt="' . $companyName . '" width="52" height="52" style="display:block;border-radius:8px;object-fit:contain;background:#fff;padding:4px;">';
            $html .= '</td>';
            $html .= '<td style="vertical-align:middle;width:12px;"></td>';
        }
        $html .= '<td style="vertical-align:middle;">';
        $html .= '<div class="hdr-title" style="font-size:20px;font-weight:800;color:#ffffff;margin:0;line-height:1.2;">';
        $html .= ($type === 'admin') ? 'New Quote Request' : 'Quote Request Received';
        $html .= '</div>';
        $html .= '<div style="margin-top:5px;font-size:13px;color:rgba(255,255,255,.65);">';
        $html .= 'Reference: <strong style="color:rgba(255,255,255,.9);">Quote #' . $qid . '</strong>';
        $html .= ' &nbsp;&#183;&nbsp; ' . date('d M Y');
        $html .= '</div>';
        $html .= '</td>';
        $html .= '<td style="vertical-align:middle;text-align:right;" class="hide-mobile">';
        $html .= '<div style="display:inline-block;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);border-radius:20px;padding:5px 14px;">';
        $html .= '<span style="font-size:12px;color:#fff;font-weight:700;letter-spacing:.5px;">QUOTE #' . $qid . '</span>';
        $html .= '</div>';
        $html .= '</td>';
        $html .= '</tr></table>';
        $html .= '</td></tr>';

        /* — body — */
        $html .= '<tr><td class="pad" style="padding:28px 32px;">';
        $html .= '<p style="margin:0 0 6px;font-size:16px;font-weight:700;color:#0f172a;">' . $e($o['greeting'] ?? '') . '</p>';
        $html .= '<p style="margin:0 0 24px;font-size:14px;color:#475569;line-height:1.65;">' . ($o['intro'] ?? '') . '</p>';

        /* customer panel first (admin only) */
        $html .= $customerPanel;

        /* section label */
        $html .= '<h3 style="margin:0 0 12px;font-size:13px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #e2e8f0;padding-bottom:8px;">Order Summary</h3>';
        $html .= $prodTable;
        $html .= $vatHtml;
        $html .= $addrHtml;
        $html .= $notesHtml;
        $html .= $disclaimer;
        $html .= '</td></tr>';

        /* — footer — */
        $html .= '<tr><td style="background:#0f172a;padding:24px 32px;">';
        $html .= '<table width="100%" cellpadding="0" cellspacing="0">';
        $html .= '<tr>';
        if ($logoUrl !== '') {
            $html .= '<td style="vertical-align:top;width:50px;padding-right:14px;">';
            $html .= '<img src="' . $e($logoUrl) . '" alt="' . $companyName . '" width="40" height="40" style="display:block;border-radius:6px;object-fit:contain;background:rgba(255,255,255,.1);padding:3px;">';
            $html .= '</td>';
        }
        $html .= '<td style="vertical-align:top;">';
        $html .= '<div style="font-size:13px;font-weight:700;color:#f1f5f9;margin-bottom:6px;">' . $companyName . '</div>';
        if ($coAddr !== '') {
            $html .= '<div style="font-size:11px;color:#64748b;margin-bottom:4px;">&#128205; ' . $coAddr . '</div>';
        }
        if ($coPhone !== '') {
            $html .= '<div style="font-size:11px;color:#64748b;margin-bottom:4px;">&#128222; ' . $coPhone . '</div>';
        }
        if ($coEmail !== '') {
            $html .= '<div style="font-size:11px;color:#64748b;margin-bottom:4px;">&#9993; ' . $coEmail . '</div>';
        }
        $html .= '</td>';
        $html .= '<td style="vertical-align:top;text-align:right;white-space:nowrap;" class="hide-mobile">';
        $html .= '<div style="font-size:11px;color:#475569;">&#169; ' . date('Y') . ' ' . $companyName . '</div>';
        $html .= '<div style="margin-top:4px;font-size:11px;color:#334155;">All rights reserved.</div>';
        $html .= '</td>';
        $html .= '</tr></table>';
        $html .= '</td></tr>';

        /* close card + outer table */
        $html .= '</table>';
        $html .= '</td></tr></table>';
        $html .= '</body></html>';

        return $html;
    }

    /* ── Active bank details ─────────────────────────────────── */
    public function getBankDetails(): array
    {
        try {
            return $this->dbHelper->select(
                "SELECT bank_detail_id, account_holder_name, bank_name, branch_name,
                        account_number, ifsc_code, swift_code, iban_number,
                        bank_address, currency
                 FROM tbl_bank_details WHERE status = 1 ORDER BY bank_detail_id ASC"
            );
        } catch (Exception $e) { error_log('getBankDetails: ' . $e->getMessage()); return []; }
    }

    /* ── Address shipping info (public — used by order AJAX) ─── */
    public function getAddressShipping(int $addrId, int $userId): ?object
    {
        if ($addrId <= 0 || $userId <= 0) return null;
        try {
            $r = $this->dbHelper->select(
                "SELECT ua.country_id, ua.country,
                        COALESCE(c.shipping_amt, 19.99) AS shipping_amt,
                        COALESCE(c.country, ua.country, 'Unknown') AS country_name
                 FROM tbl_user_address ua
                 LEFT JOIN tbl_country c ON c.country_id = ua.country_id AND ua.country_id > 0
                 WHERE ua.user_address_id = $addrId AND ua.user_id = $userId LIMIT 1"
            );
            return $r[0] ?? null;
        } catch (Exception $e) { error_log('getAddressShipping: ' . $e->getMessage()); return null; }
    }
}
?>
