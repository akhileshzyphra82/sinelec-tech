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



    
}   
?>
