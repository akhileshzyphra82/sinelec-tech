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

    /* ── Quote: categories ──────────────────────────────────── */
    public function getQuoteCategories(): array
    {
        try {
            return $this->dbHelper->select(
                "SELECT product_category_id, product_category_name
                 FROM tbl_product_category
                 ORDER BY product_category_name ASC"
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
                 WHERE p.product_id > 0$w
                 ORDER BY p.product_name ASC"
            );
        } catch (Exception $e) { return []; }
    }

    /* ── Quote: user addresses ──────────────────────────────── */
    public function getUserAddresses(int $userId): array
    {
        try {
            return $this->dbHelper->select(
                "SELECT user_address_id, label, user_name, company_name,
                        address_line_one, address_line_two, landmark,
                        city, state, zip, country, delivery_phone_no
                 FROM tbl_user_address
                 WHERE user_id = $userId
                 ORDER BY user_address_id ASC"
            );
        } catch (Exception $e) { return []; }
    }

    /* ── Quote: save address ────────────────────────────────── */
    public function saveDeliveryAddress(array $d): int
    {
        try {
            $uid   = (int)($d['user_id']          ?? 0);
            if ($uid <= 0) return 0;
            $label = in_array($d['label'] ?? 'Home', ['Home','Office','Other']) ? $d['label'] : 'Home';
            $uname = addslashes(trim($d['addr_name']    ?? ''));
            $comp  = addslashes(trim($d['company_name'] ?? ''));
            $phone = addslashes(trim($d['phone']        ?? ''));
            $mcc   = (int)($d['phone_code'] ?? 49);
            $line1 = addslashes(trim($d['address_line_one'] ?? ''));
            $line2 = addslashes(trim($d['address_line_two'] ?? ''));
            $lmk   = addslashes(trim($d['landmark']    ?? ''));
            $city  = addslashes(trim($d['city']        ?? ''));
            $state = addslashes(trim($d['state']       ?? ''));
            $zip   = addslashes(trim($d['zip']         ?? ''));
            $cntry = addslashes(trim($d['country']     ?? ''));
            $cntId = (int)($d['country_id'] ?? 0);
            $sql = "INSERT INTO tbl_user_address
                 (user_id, label, user_name, company_name, delivery_phone_no, mobile_country_code,
                  address_line_one, address_line_two, landmark, city, state, zip, country_id, country)
                 VALUES($uid,'$label','$uname','$comp','$phone',$mcc,
                        '$line1','$line2','$lmk','$city','$state','$zip',$cntId,'$cntry')";
            $newId = (int)$this->dbHelper->insert($sql);
            return $newId;
        } catch (Exception $e) { error_log('saveDeliveryAddress: '.$e->getMessage()); return 0; }
    }

    /* ── Quote: register new customer ──────────────────────── */
    public function registerQuoteCustomer(array $d): int|string
    {
        try {
            $name  = addslashes(trim($d['name']     ?? ''));
            $email = addslashes(strtolower(trim($d['email'] ?? '')));
            $phone = addslashes(trim($d['phone']    ?? ''));
            $mcc   = (int)($d['phone_code'] ?? 49);
            $comp  = addslashes(trim($d['company_name'] ?? ''));
            $pwd   = trim($d['password'] ?? '');
            if ($name === '' || $email === '') return 'missing_fields';
            $dup = $this->dbHelper->select(
                "SELECT user_id FROM tbl_user WHERE communication_email_id='$email' LIMIT 1"
            );
            if (!empty($dup)) return 'email_exists';
            if ($pwd === '') $pwd = bin2hex(random_bytes(8));
            $hash = addslashes(password_hash($pwd, PASSWORD_DEFAULT));
            $sql  = "INSERT INTO tbl_user
                 (user_type_id, name, communication_email_id, erp_password,
                  communication_mobile_num_isd, communication_mobile_num, company_name,
                  account_activation_flag, random_activation_key, verified_flag, is_pwd_updated)
                 VALUES(2,'$name','$email','$hash',$mcc,'$phone','$comp',
                        '1','".bin2hex(random_bytes(8))."','Yes',0)";
            $newId = (int)$this->dbHelper->insert($sql);
            return $newId > 0 ? $newId : 'insert_failed';
        } catch (Exception $e) { error_log('registerQuoteCustomer: '.$e->getMessage()); return 'error'; }
    }

    /* ── Quote: submit full quotation ───────────────────────── */
    public function submitCustomerQuote(array $d, int $loggedInUserId = 0): array
    {
        try {
            $products = $d['products'] ?? [];
            if (empty($products)) return ['ok' => false, 'error' => 'No products provided.'];

            /* Determine user */
            $userId = $loggedInUserId;
            if ($userId <= 0) {
                $result = $this->registerQuoteCustomer($d);
                if ($result === 'email_exists') {
                    return ['ok' => false, 'error' => 'email_exists', 'msg' => 'This email is already registered. Please sign in to submit your quote.'];
                }
                if (!is_int($result) || $result <= 0) {
                    return ['ok' => false, 'error' => 'register_failed', 'msg' => 'Could not create account. Please try again.'];
                }
                $userId = $result;
                /* Auto-sign-in the new user */
                $newUser = $this->dbHelper->select("SELECT * FROM tbl_user WHERE user_id=$userId LIMIT 1");
                if (!empty($newUser)) {
                    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
                    $_SESSION['sinelec_user'] = $this->mapUserRow($newUser[0]);
                }
            }

            /* Save delivery address or use existing */
            if (!empty($d['_use_existing_addr']) && (int)$d['_use_existing_addr'] > 0) {
                $addrId = (int)$d['_use_existing_addr'];
            } else {
                $addrData = array_merge($d, ['user_id' => $userId]);
                $addrId   = $this->saveDeliveryAddress($addrData);
            }

            /* Calculate totals */
            $subtotal = 0;
            foreach ($products as $p) {
                $subtotal += (float)($p['price'] ?? 0) * (int)($p['qty'] ?? 0);
            }
            $totalAmt = round($subtotal, 2);

            /* Insert quote header */
            $uname = addslashes(trim($d['name']         ?? ''));
            $uemail= addslashes(trim($d['email']        ?? ''));
            $uphone= addslashes(trim($d['phone']        ?? ''));
            $ucomp = addslashes(trim($d['company_name'] ?? ''));
            $notes = addslashes(trim($d['notes']        ?? ''));
            $sql = "INSERT INTO tbl_enquiry_quote
                 (user_id, user_address_id, billing_address_id, user_name, user_email, user_phone, company_name,
                  enquiry_status, enquiry_vat_amt, enquiry_shipping_amt, enquiry_total_amt,
                  discount_percentage, discount_amt, customer_order_no, customer_supplier_no,
                  vat_number, order_id, remark)
                 VALUES($userId, $addrId, $addrId, '$uname', '$uemail', '$uphone', '$ucomp',
                  'Quotation Pending', 0, 0, $totalAmt, 0, 0, '', '', '', 0, '$notes')";
            $qid = (int)$this->dbHelper->insert($sql);
            if ($qid <= 0) return ['ok' => false, 'error' => 'quote_insert_failed', 'msg' => 'Failed to save quotation.'];

            /* Insert quote products */
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

            /* Send confirmation emails */
            try {
                $company = $this->getCompanyInfo();
                $supportEmail = trim((string)($company->SUPPORT_MAIL_ID ?? ''));
                $companyName  = trim((string)($company->COMPANY_NAME    ?? 'Sinelec Tech'));

                /* Build product rows for email */
                $prodRows = '';
                $grandTotal = 0;
                foreach ($products as $p) {
                    $lineTotal   = (float)($p['price'] ?? 0) * (int)($p['qty'] ?? 0);
                    $grandTotal += $lineTotal;
                    $prodRows   .= '<tr style="border-bottom:1px solid #e2e8f0;">'
                                 . '<td style="padding:8px 12px;font-size:13px;color:#1e293b;">' . (int)($p['prod_id'] ?? 0) . '</td>'
                                 . '<td style="padding:8px 12px;font-size:13px;color:#1e293b;">' . (int)($p['qty'] ?? 0) . '</td>'
                                 . '<td style="padding:8px 12px;font-size:13px;color:#1e293b;">€' . number_format((float)($p['price'] ?? 0), 2) . '</td>'
                                 . '<td style="padding:8px 12px;font-size:13px;font-weight:700;color:#1d4ed8;">€' . number_format($lineTotal, 2) . '</td>'
                                 . '</tr>';
                }

                /* Fetch product names for better email body */
                $prodNameRows = '';
                foreach ($products as $p) {
                    $pid = (int)($p['prod_id'] ?? 0);
                    if ($pid <= 0) continue;
                    $row = $this->dbHelper->select("SELECT product_name, product_code FROM tbl_product WHERE product_id=$pid LIMIT 1");
                    $pname = !empty($row) ? (string)($row[0]->PRODUCT_NAME ?? "Product #$pid") : "Product #$pid";
                    $pcode = !empty($row) ? (string)($row[0]->PRODUCT_CODE ?? '') : '';
                    $lineTotal = (float)($p['price'] ?? 0) * (int)($p['qty'] ?? 0);
                    $prodNameRows .= '<tr style="border-bottom:1px solid #e2e8f0;">'
                                   . '<td style="padding:8px 12px;font-size:13px;color:#1e293b;">' . htmlspecialchars($pname) . ($pcode ? ' <span style="color:#64748b;">[' . htmlspecialchars($pcode) . ']</span>' : '') . '</td>'
                                   . '<td style="padding:8px 12px;font-size:13px;color:#1e293b;text-align:center;">' . (int)($p['qty'] ?? 0) . '</td>'
                                   . '<td style="padding:8px 12px;font-size:13px;color:#1e293b;text-align:right;">€' . number_format((float)($p['price'] ?? 0), 2) . '</td>'
                                   . '<td style="padding:8px 12px;font-size:13px;font-weight:700;color:#1d4ed8;text-align:right;">€' . number_format($lineTotal, 2) . '</td>'
                                   . '</tr>';
                }

                $tableHeader = '<tr style="background:#f1f5f9;">'
                             . '<th style="padding:8px 12px;font-size:11px;text-align:left;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Product</th>'
                             . '<th style="padding:8px 12px;font-size:11px;text-align:center;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Qty</th>'
                             . '<th style="padding:8px 12px;font-size:11px;text-align:right;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Unit Price</th>'
                             . '<th style="padding:8px 12px;font-size:11px;text-align:right;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Total</th>'
                             . '</tr>';

                $prodTable = '<table style="width:100%;border-collapse:collapse;margin:16px 0;">'
                           . $tableHeader . $prodNameRows
                           . '<tr><td colspan="3" style="padding:8px 12px;font-size:13px;font-weight:700;text-align:right;color:#0f172a;">Subtotal</td>'
                           . '<td style="padding:8px 12px;font-size:14px;font-weight:800;color:#1d4ed8;text-align:right;">€' . number_format($grandTotal, 2) . '</td></tr>'
                           . '</table>';

                $notesHtml = $d['notes'] ?? '' ? '<p style="margin:12px 0 0;font-size:13px;color:#374151;"><strong>Notes:</strong> ' . htmlspecialchars($d['notes'] ?? '') . '</p>' : '';

                /* ── Customer confirmation email ── */
                $custEmail = trim($d['email'] ?? '');
                $custName  = trim($d['name']  ?? '');
                if ($custEmail && filter_var($custEmail, FILTER_VALIDATE_EMAIL)) {
                    $custBody = '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#f0f4f8;font-family:Arial,sans-serif;">'
                              . '<div style="max-width:600px;margin:32px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);">'
                              . '<div style="background:linear-gradient(135deg,#0f172a,#1e3a5f);padding:28px 32px;">'
                              . '<h2 style="margin:0;color:#fff;font-size:20px;">Quote Request Received</h2>'
                              . '<p style="margin:6px 0 0;color:rgba(255,255,255,.7);font-size:13px;">Reference: <strong>#' . $qid . '</strong></p>'
                              . '</div>'
                              . '<div style="padding:28px 32px;">'
                              . '<p style="margin:0 0 16px;font-size:14px;color:#374151;">Hi <strong>' . htmlspecialchars($custName ?: 'there') . '</strong>,</p>'
                              . '<p style="margin:0 0 16px;font-size:14px;color:#374151;">Thank you for your quote request. We have received the following items and our team will respond within <strong>24 hours</strong>.</p>'
                              . $prodTable
                              . $notesHtml
                              . '<p style="margin:24px 0 0;font-size:13px;color:#64748b;">If you have any urgent requirements, feel free to reach out to us directly.</p>'
                              . '</div>'
                              . '<div style="padding:16px 32px;background:#f8fafc;border-top:1px solid #e2e8f0;">'
                              . '<p style="margin:0;font-size:12px;color:#94a3b8;">© ' . date('Y') . ' ' . htmlspecialchars($companyName) . '. All rights reserved.</p>'
                              . '</div></div></body></html>';

                    sinelec_send_mail([[
                        'to_mail_id' => $custEmail,
                        'subject'    => 'Quote Request Received — Reference #' . $qid . ' | ' . $companyName,
                        'body'       => $custBody,
                    ]]);
                }

                /* ── Support / admin notification email ── */
                if ($supportEmail && filter_var($supportEmail, FILTER_VALIDATE_EMAIL)) {
                    $adminBody = '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#f0f4f8;font-family:Arial,sans-serif;">'
                               . '<div style="max-width:600px;margin:32px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);">'
                               . '<div style="background:linear-gradient(135deg,#1d4ed8,#2563eb);padding:28px 32px;">'
                               . '<h2 style="margin:0;color:#fff;font-size:20px;">New Quote Request</h2>'
                               . '<p style="margin:6px 0 0;color:rgba(255,255,255,.8);font-size:13px;">Reference: <strong>#' . $qid . '</strong></p>'
                               . '</div>'
                               . '<div style="padding:28px 32px;">'
                               . '<table style="width:100%;border-collapse:collapse;margin-bottom:20px;">'
                               . '<tr><td style="padding:6px 0;font-size:13px;color:#64748b;width:120px;">Customer</td><td style="padding:6px 0;font-size:13px;font-weight:600;color:#0f172a;">' . htmlspecialchars($uname ?: 'N/A') . '</td></tr>'
                               . '<tr><td style="padding:6px 0;font-size:13px;color:#64748b;">Email</td><td style="padding:6px 0;font-size:13px;color:#2563eb;">' . htmlspecialchars($uemail ?: 'N/A') . '</td></tr>'
                               . '<tr><td style="padding:6px 0;font-size:13px;color:#64748b;">Phone</td><td style="padding:6px 0;font-size:13px;color:#0f172a;">' . htmlspecialchars($uphone ?: 'N/A') . '</td></tr>'
                               . ($ucomp ? '<tr><td style="padding:6px 0;font-size:13px;color:#64748b;">Company</td><td style="padding:6px 0;font-size:13px;color:#0f172a;">' . htmlspecialchars($ucomp) . '</td></tr>' : '')
                               . '</table>'
                               . $prodTable
                               . $notesHtml
                               . '</div>'
                               . '<div style="padding:16px 32px;background:#f8fafc;border-top:1px solid #e2e8f0;">'
                               . '<p style="margin:0;font-size:12px;color:#94a3b8;">Submitted on ' . date('d M Y, H:i') . '</p>'
                               . '</div></div></body></html>';

                    sinelec_send_mail([[
                        'to_mail_id' => $supportEmail,
                        'subject'    => 'New Quote Request #' . $qid . ' from ' . ($uname ?: $uemail),
                        'body'       => $adminBody,
                    ]]);
                }
            } catch (\Throwable $mailErr) {
                error_log('submitCustomerQuote mail error: ' . $mailErr->getMessage());
                /* Don't fail the whole request if mail errors */
            }

            return ['ok' => true, 'quote_id' => $qid, 'user_id' => $userId];
        } catch (Exception $e) {
            error_log('submitCustomerQuote: '.$e->getMessage());
            return ['ok' => false, 'error' => 'exception', 'msg' => 'Something went wrong. Please try again.'];
        }
    }
}
?>
