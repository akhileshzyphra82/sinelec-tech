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

    /* ── Quote: user addresses ──────────────────────────────── */
    public function getUserAddresses(int $userId): array
    {
        try {
            return $this->dbHelper->select(
                "SELECT user_address_id, label, user_name, company_name,
                        address AS address_extra,
                        address_line_one, address_line_two, landmark,
                        city, state, zip, country, country_id,
                        delivery_phone_no, mobile_country_code,
                        recipient_name, recipient_email, recipient_contact
                 FROM tbl_user_address
                 WHERE user_id = $userId
                 ORDER BY user_address_id DESC"
            );
        } catch (Exception $e) { return []; }
    }

    /* ── Address: save (insert) ─────────────────────────────── */
    public function saveDeliveryAddress(array $d, int $userId): int
    {
        try {
            if ($userId <= 0) return 0;
            $label        = in_array($d['label'] ?? 'Home', ['Home','Office','Other']) ? ($d['label'] ?? 'Home') : 'Other';
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
            $countryId    = (float)($d['country_id']                 ?? 0);
            $addrExtra    = addslashes(trim($d['address']            ?? ''));
            $recipName    = addslashes(trim($d['recipient_name']     ?? ''));
            $recipEmail   = addslashes(trim($d['recipient_email']    ?? ''));
            $recipContact = addslashes(trim($d['recipient_contact']  ?? ''));
            $sql = "INSERT INTO tbl_user_address
                 (user_id, label, user_name, company_name, delivery_phone_no, mobile_country_code,
                  address_line_one, address_line_two, landmark,
                  city, state, zip, country, country_id, address,
                  recipient_name, recipient_email, recipient_contact)
                 VALUES($userId,'$label','$userName','$company','$phone',$mcc,
                        '$line1','$line2','$lmk','$city','$state','$zip','$country',$countryId,'$addrExtra',
                        '$recipName','$recipEmail','$recipContact')";
            return (int)$this->dbHelper->insert($sql);
        } catch (Exception $e) { error_log('saveDeliveryAddress: '.$e->getMessage()); return 0; }
    }

    /* ── Address: update ────────────────────────────────────── */
    public function updateDeliveryAddress(int $addrId, array $d, int $userId): bool
    {
        try {
            if ($addrId <= 0 || $userId <= 0) return false;
            $label        = in_array($d['label'] ?? 'Home', ['Home','Office','Other']) ? ($d['label'] ?? 'Home') : 'Other';
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
            $countryId    = (float)($d['country_id']                 ?? 0);
            $addrExtra    = addslashes(trim($d['address']            ?? ''));
            $recipName    = addslashes(trim($d['recipient_name']     ?? ''));
            $recipEmail   = addslashes(trim($d['recipient_email']    ?? ''));
            $recipContact = addslashes(trim($d['recipient_contact']  ?? ''));
            $sql = "UPDATE tbl_user_address SET
                     label='$label', user_name='$userName', company_name='$company',
                     delivery_phone_no='$phone', mobile_country_code=$mcc,
                     address_line_one='$line1', address_line_two='$line2', landmark='$lmk',
                     city='$city', state='$state', zip='$zip',
                     country='$country', country_id=$countryId, address='$addrExtra',
                     recipient_name='$recipName', recipient_email='$recipEmail', recipient_contact='$recipContact'
                    WHERE user_address_id=$addrId AND user_id=$userId";
            $this->dbHelper->update($sql);
            return true;
        } catch (Exception $e) { error_log('updateDeliveryAddress: '.$e->getMessage()); return false; }
    }

    /* ── Address: delete ────────────────────────────────────── */
    public function deleteDeliveryAddress(int $addrId, int $userId): bool
    {
        try {
            if ($addrId <= 0 || $userId <= 0) return false;
            $this->dbHelper->update(
                "DELETE FROM tbl_user_address WHERE user_address_id=$addrId AND user_id=$userId"
            );
            return true;
        } catch (Exception $e) { error_log('deleteDeliveryAddress: '.$e->getMessage()); return false; }
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
}
?>
