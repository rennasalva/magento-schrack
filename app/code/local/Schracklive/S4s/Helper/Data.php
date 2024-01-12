<?php

class Schracklive_S4s_Helper_Data extends Mage_Core_Helper_Abstract {

    const ACCOUNT_TYPE_STUDENT = '44';

    private static $InUpdateMap = array();
    private $termsOfUseCount = false;
    private $termsOfUseAvail = false;
    private $currentTermsOfUseContent = false;
    private $currentTermsOfUseVersion = false;

    public function newTermsOfUseProvided () {
        self::log("START newTermsOfUseProvided()");
        if ( Mage::getStoreConfig('schrack/s4s/send_term_of_use_updates') ) {
            $this->loadTermsOfUseData();
            if ( $this->termsOfUseAvail ) {
                $url = Mage::getStoreConfig('schrack/s4s/terms_of_use_update_url');
                if ( ! $url ) {
                    self::log("Yet no terms_of_use_update_url defined in backend - aborting.");
                    return;
                }
                $shopCountry = strtoupper($this->getShopCountry());
                $data = [
                    'country' => $shopCountry,
                    'newTermsOfUseVersion' => $this->currentTermsOfUseVersion,
                    'newTermsOfUseContent' => $this->currentTermsOfUseContent
                ];
                $this->sendDataToS4sServer($data,$url);
            }
        }
        self::log("newTermsOfUseProvided() END");
    }

    public function notifyCountryChange ( $customer ) {
        self::log("START notifyCountryChange()");
        if ( Mage::getStoreConfig('schrack/s4s/send_country_changed') ) {
            self::log("Country change for customer {$customer->getEmail()} requested");
            $changedData = ['country' => strtoupper($this->getShopCountry())];
            $s4sId = $customer->getSchrackS4sId();
            $this->sendChangedUserDataToS4sServer($changedData, $s4sId);
        }
        self::log("notifyCountryChange() END");
    }

    public function sendUpdateToS4s ( $customer ) {
        self::log("START sendUpdateToS4s()");
        self::log("CRM or Shop update for customer {$customer->getEmail()} requested");
        /** @var Schracklive_SchrackCustomer_Model_Customer $customer */
        $s4sId = $customer->getData('schrack_s4s_id');
        if ( $s4sId == null || $s4sId == '' ) {
            self::log("Not an S4S user - aborting.");
            return;
        }
        if ( ! $customer->getOrigData('entity_id') ) {
            self::log("Newly created customer - aborting.");
            return;
        }
        if ( isset(self::$InUpdateMap[$s4sId]) && self::$InUpdateMap[$s4sId] === true ) {
            self::log("Customer in S4S update - aborting.");
            return;
        }
        $email = $customer->getData('email');
        $changedData = array();
        $isActive = explode('@',$email)[1] == 'live.schrack.com';
        if ( $email != $customer->getOrigData('email') ) { // means becoming inactive or active
            $changedData['userStateIsActive'] = $isActive;
            if ( $isActive ) { // we do not send inactive email addrs like "inactive+777777+203@live.schrack.com"
                $changedData['email'] = $email;
            }
        }
        if ( $customer->getData('firstname') != $customer->getOrigData('firstname') ) {
            $changedData['firstName'] = $customer->getData('firstname');
        }
        if ( $customer->getData('lastname') != $customer->getOrigData('lastname') ) {
            $changedData['lastName'] = $customer->getData('lastname');
        }
        if ( $customer->getData('gender') != $customer->getOrigData('gender') ) {
            $changedData['genderIsFemale'] = $customer->getData('gender') == 2;
        }
        if ( Mage::getStoreConfig('schrack/s4s/send_term_of_use_updates') ) {
            $this->loadTermsOfUseData();
            if ($this->termsOfUseAvail) {
                if ($customer->getData('schrack_last_terms_confirmed') != $customer->getOrigData('schrack_last_terms_confirmed')) {
                    // current customer update is confirmation of terms
                    $changedData['confirmedTermsOfUseVersion'] = $this->currentTermsOfUseVersion;
                }
            }
        }
        $this->sendChangedUserDataToS4sServer($changedData, $s4sId);
        self::log("sendUpdateToS4s() END");
    }

    public function commonRequestHandler ( $currentMethod, $jsonRequest ) {
        header('Content-Type: application/json',true);
        header('Accept: application/json',true);
        header('Allow: POST',true);
        try {
            self::logJson('REQUEST', $jsonRequest);
            $requestData = json_decode($jsonRequest);
            if ( !is_object($requestData) || count((array) $requestData) == 0 ) {
                throw new Schracklive_S4s_InvalidRequestDataException();
            }
            $responseData = $this->$currentMethod($requestData);
            if ( !is_array($responseData) ) {
                $responseData = array();
            }
            if ( !isset($responseData['status']) ) {
                $responseData['status'] = 'success';
            }
            if ( !isset($responseData['returncode']) ) {
                $responseData['returncode'] = 1;
            }
            if ( !isset($responseData['message']) ) {
                $responseData['message'] = '';
            }
            $responseDataJson = json_encode($responseData);
            $this->writeResponse($responseDataJson);
        } catch ( Schracklive_S4s_UserNotConnectedException $uncEx ) {
            // special case "user already existing in webshop, but not connected to s4s"
            // was first intended to be an error, but is now a regular flow for login and register
            self::log("Schracklive_S4s_UserNotConnectedException: {$uncEx->getMessage()} ({$uncEx->getCode()}) connectionToken = {$uncEx->getConnectionToken()}");
            $res = array(
                'profile'           => $this->getProfileDataForUser($uncEx->getCustomer()),
                'status'            => 'success',
                'returncode'        => $uncEx->getCode(),
                'message'           => $uncEx->getMessage(),
                'connectionToken'   => $uncEx->getConnectionToken()
            );
            $responseDataJson = json_encode($res);
            $this->writeResponse($responseDataJson);
        } catch ( Schracklive_S4s_Exception $s4sEx ) {
            self::log("S4s_Exception: {$s4sEx->getMessage()} ({$s4sEx->getCode()})");
            $errorDataJson = '{"status": "error", "returncode": ' . $s4sEx->getCode() . ',"message": "' . $s4sEx->getMessage() . '"';
            foreach ( $s4sEx->getAdditionalFields() as $name => $value ) {
                $errorDataJson .= ',"' . $name . '":"' . $value . '"';
            }
            $errorDataJson .= '}';
            $this->writeResponse($errorDataJson);
        } catch ( Exception $ex ) {
            self::log("Unexpected Exception:\n" . $ex->__toString());
            Mage::logException($ex);
            $errorDataJson = '{"status": "error", "returncode": -999,"message": "Unexpected error!"}';
            $this->writeResponse($errorDataJson);
        }
        die();
    }

    private function doLogin ( $requestData ) {
        self::log("START doLogin()");
        $eMailOrNickname = $this->getAndCheckMandatoryField($requestData,"emailOrNickname");
        $passWord        = $this->getAndCheckMandatoryField($requestData,"password");
        $customer        = $this->authenticateExtended($eMailOrNickname,$passWord);
        $res = array(
            "profile" => $this->getProfileDataForUser($customer)
        );
        self::log("doLogin() END");
        return $res;
    }

    private function doRegister ( $requestData ) {
        self::log("START doRegister()");
        $s4sId          = $this->getAndCheckMandatoryField($requestData,'s4suuid');
        $genderIsFemale = $this->getAndCheckOptionalBooleanField($requestData,'genderisfemale');
        $firstname      = $this->getAndCheckMandatoryField($requestData,'firstname');
        $lastname       = $this->getAndCheckMandatoryField($requestData,'lastname');
        $nickname       = $this->getAndCheckMandatoryField($requestData,'nickname');
        $email          = $this->getAndCheckMandatoryField($requestData,'email');
        $password       = $requestData->password;
        $school         = $requestData->school; // school not mandatory

        if ( ! $password || $password == '' ) {
            $password = $this->createRandomPassword();
        }

        // check first if email exists in this or another country
        $userCountry = strtoupper($this->getCommonDbUserCountry($email));
        $shopCountry = strtoupper($this->getShopCountry());
        self::log("shop country is '$shopCountry'");
        if ( $userCountry ) {
            self::log("country '$userCountry' for email '$email' detected");
            // same country?
            if ( $userCountry != $shopCountry ) {
                throw new Schracklive_S4s_Exception("Email already registered in (other) country $userCountry!", -113, array('originUserCountry' => $userCountry));
            }
            // load customer record
            $customer = Mage::getModel('customer/customer');
            $customer->loadByEmail($email);
            if ( ! $customer->getId() ) {
                // should not happen
                throw new Exception("SNH: User email '$email' found in common DB but not in local DB!");
            }
            $confirmation = $customer->getConfirmation();
            if ( $confirmation && $confirmation > '' ) {
                // confirmation missing
                throw new Schracklive_S4s_Exception("Email already registered but not confirmed!",-114);
            } else if ( ! $customer->getSchrackS4sId() ) {
                // not connected to S4S
                throw new Schracklive_S4s_UserNotConnectedException($this->createConnectionToken($email),$customer);
            } else {
                // already fit for use with S4S
                throw new Schracklive_S4s_Exception("Email already registered - please use login request!", -116);
            }
            // no way out when email already exists without exception...
        } else {
            self::log("$email not found in common db");
            $customer = Mage::getModel('customer/customer');
            $customer->loadByEmail($email);
            if ( $customer->getId() ) {
                // should not happen
                throw new Exception("SNH: User email '$email' NOT found in common DB but found in local DB!");
            }

            if ( Mage::getStoreConfig('schrack/email/do_check_s4s_registration_email') ) {
                $helper = Mage::helper('schrack/email');
                $ok = $helper->validateEmailAddress($email);
                if (!$ok) {
                    // email not valid by Eyepin
                    throw new Schracklive_S4s_Exception("Email address not valid!", -104);
                }
            }

            $advisorPrinzipal   = Mage::getStoreConfig('schrack/s4s/advisor_principal_name');
            $branch             = Mage::getStoreConfig('schrack/s4s/branch');
            $salesArea          = Mage::getStoreConfig('schrack/s4s/salesarea');

            $this->checkS4sId($s4sId);

            $prosli = 'PROSLI';
            try {
                self::$InUpdateMap[$s4sId] = true;
                $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
                $writeConnection->beginTransaction();

                $account = Mage::getModel('account/account');
                $account->setWwsCustomerId('');
                $account->setWwsBranchId($branch);
                $account->setName1($prosli);
                $account->setAdvisorPrincipalName($advisorPrinzipal);
                $account->setEmail($email);
                $account->setSalesArea($salesArea);
                $account->setAccountType(self::ACCOUNT_TYPE_STUDENT);
                $account->save();
                if ( ! $account->getId() ) {
                    throw new Exception("Account was not saved!");
                }
                self::log("new account {$account->getId()} created");

                $systemContact = Mage::getModel('customer/customer');
                Mage::helper('schrackcustomer')->setupSystemContact($systemContact, $account);
                $systemContact->setLastname($account->getName1());
                $systemContact->setMiddlename($account->getName2());
                $systemContact->setFirstname($account->getName3());
                $systemContact->save();
                if ( ! $systemContact->getId() ) {
                    throw new Exception("System contact was not saved!");
                }
                self::log("new system contact {$systemContact->getId()} created");

                $addressData['firstname']  = $firstname;
                $addressData['lastname']   = $lastname;
                $addressData['street']     = $prosli;
                $addressData['postcode']   = $prosli;
                $addressData['city']       = $prosli;
                $addressData['country_id'] = strtoupper(Mage::getStoreConfig('schrack/general/country'));
                $addressData['telephone']  = '';
                $addressData['fax']        = '';
                $address = Mage::getModel('customer/address');
                $address->setData($addressData);
                Mage::helper('schrackcustomer')->setupBillingAddress($address, $systemContact->getId());
                $address->setCustomerId($systemContact->getId());
                $address->setSchrackWwsAddressNumber(0);
                $address->setSchrackType(1);
                $address->save();
                if ( ! $address->getId() ) {
                    throw new Exception("Billing address was not saved!");
                }
                self::log("new (dummy) billing address {$address->getId()} created");

                $systemContact->setData('default_billing', $address->getId());
                $systemContact->setData('default_shipping', $address->getId());
                $systemContact->save();
                self::log("system contact saved again wit address");

                $customer = Mage::getModel('customer/customer');
                $customer->setSystemContact($systemContact);
                $customer->setSchrackWwsCustomerId('');
                $customer->setSchrackAccountId($account->getId());
                if ( $genderIsFemale !== null ) {
                    $customer->setGender($genderIsFemale ? 2 : 1);
                }
                $customer->setFirstname($firstname);
                $customer->setLastname($lastname);
                $customer->setEmail($email);
                $customer->setPassword($password);
                $customer->setConfirmation($this->createConfirmationKey());
                // schrack_telephone OR schrack_mobile_phone OR schrack_fax ???
                $customer->setSchrackS4sId($s4sId);
                $customer->setSchrackS4sNickname($nickname);
                $customer->setSchrackS4sSchool($school);
                $customer->setSchrackCustomerType(Schracklive_SchrackCustomer_Model_Prospect::PROSPECT_TYPE_LIGHT);
                $customer->setSchrackAclRoleId(Mage::helper('schrack/acl')->getDefaultRoleId()); // customer
                $customer->setGroupId(Mage::getStoreConfig('schrack/shop/prospect_group')); // prospect
                $customer->setSchrackAdvisorPrincipalName($advisorPrinzipal);
                $customer->save();
                if ( ! $customer->getId() ) {
                    throw new Exception("Customer (contact) was not saved!");
                }
                $query = "UPDATE customer_entity SET schrack_customer_type = '" . Schracklive_SchrackCustomer_Model_Prospect::PROSPECT_TYPE_LIGHT . "' WHERE entity_id = " . $customer->getId();
                $writeConnection->query($query);
                self::log("new customer {$customer->getId()} created");

                $this->sendProspectMessage($account,$customer,$shopCountry);
                self::log("prospect message sent");

                $templateID = Mage::getStoreConfig('schrack/s4s/confirmation_mail_template_id');
                $this->sendCustomerEmail($customer,$templateID);
                self::log("confirmation email sent");

                $writeConnection->commit();
            } catch ( Exception $ex ) {
                $writeConnection->rollback();
                throw $ex;
            }

            // Erst ab PHP v5.5+ verfügbar (Produktiv-Backend = PHP 5.4.45-0+deb7u14) !!
            /* finally {
                self::$InUpdateMap[$s4sId] = false;
            }*/

            $res = array(
                "profile" => $this->getProfileDataForUser($customer)
            );
            self::log("doRegister() END");
            return $res;
        }
    }

    private function doUpdate ( $requestData ) {
        self::log("START doUpdate()");
        $s4sId          = $this->getAndCheckMandatoryField($requestData,'s4suuid');
        $nickname       = $this->getAndCheckMandatoryField($requestData,'nickname');
        $school         = $requestData->school; // school not mandatory

        self::$InUpdateMap[$s4sId] = true;

        $customer = Mage::getModel('customer/customer');
        $customer->loadByS4sId($s4sId);
        if ( ! $customer->getId() ) {
            $email      = $this->getAndCheckMandatoryField($requestData,'email');
            $token      = $this->getAndCheckMandatoryField($requestData,'token');
            $customer->loadByEmail($email);
            if ( ! $customer->getId() ) {
                throw new Schracklive_S4s_Exception("S4S ID '$s4sId' and email '$email' not found!", -121);
            }
            $this->checkConnectionToken($email,$token);
            if ( $customer->getSchrackS4sId() && $customer->getSchrackS4sId() >= '' && $customer->getSchrackS4sId() != $s4sId ) {
                throw new Schracklive_S4s_Exception("User already connected to another s4suuid '{$customer->getSchrackS4sId()}'!", -122);
            }
            $customer->setSchrackS4sId($s4sId);
        } else {
            $genderIsFemale = $this->getAndCheckOptionalBooleanField($requestData,'genderisfemale');
            $firstname      = $this->getAndCheckMandatoryField($requestData,'firstname');
            $lastname       = $this->getAndCheckMandatoryField($requestData,'lastname');

            if ( $genderIsFemale !== null ) {
                $customer->setGender($genderIsFemale ? 2 : 1);
            }
            $customer->setFirstname($firstname);
            $customer->setLastname($lastname);
        }

        $customer->setSchrackS4sNickname($nickname);
        $customer->setSchrackS4sSchool($school);

        try {
            $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $writeConnection->beginTransaction();

            $customer->save();
            self::log("customer saved");

            $type = $customer->getSchrackCustomerType();
            if (    $type == Schracklive_SchrackCustomer_Model_Prospect::PROSPECT_TYPE_LIGHT
                 || $type == Schracklive_SchrackCustomer_Model_Prospect::PROSPECT_TYPE_FULL  ) {
                $account = $customer->getAccount();
                // s4s prospects must always send account type "student" and defined branch and sales area to crm!
                $branch = Mage::getStoreConfig('schrack/s4s/branch');
                $salesArea = Mage::getStoreConfig('schrack/s4s/salesarea');
                if (    $account->getAccountType() != self::ACCOUNT_TYPE_STUDENT
                     || $account->getWwsBranchId() != $branch
                     || $account->getSalesArea() != $salesArea ) {
                    $account->setAccountType(self::ACCOUNT_TYPE_STUDENT);
                    $account->setWwsBranchId($branch);
                    $account->setSalesArea($salesArea);
                    $account->save();
                }
                $shopCountry = $this->getShopCountry();
                $this->sendProspectMessage($account,$customer,$shopCountry);
                self::log("prospect message sent");
            } // else case: contacts will be handled automatically in save by an observer

            $writeConnection->commit();
        } catch ( Exception $ex ) {
            $writeConnection->rollback();
            throw $ex;
        }

            // Erst ab PHP v5.5+ verfügbar (Produktiv-Backend = PHP 5.4.45-0+deb7u14) !!
        /*finally {
            self::$InUpdateMap[$s4sId] = false;
        }*/

        $res = array(
            "profile" => $this->getProfileDataForUser($customer)
        );
        self::log("doUpdate() END");
        return $res;
    }

    private function doChangePassword ( $requestData ) {
        self::log("START doChangePassword()");
        $eMailOrNickname = $this->getAndCheckMandatoryField($requestData,"emailOrNickname");
        $oldPassWord     = $this->getAndCheckMandatoryField($requestData,"oldpassword");
        $newPassWord     = $this->getAndCheckMandatoryField($requestData,"newpassword");

        $customer = $this->loadCustomer($eMailOrNickname);
        $this->authenticateCore($customer,$oldPassWord);
        $this->ensureConfirmation($customer);
        if ( ! $customer->getSchrackS4sId() ) {
            // not connected to S4S
            throw new Exception("User not connected.");
        }
        if ( $oldPassWord == $newPassWord ) {
            throw new Schracklive_S4s_Exception("Old password and new password are identical!",-131);
        }
        $customer->setPassword($newPassWord);
        $customer->save();
        self::log("doChangePassword() END");
    }

    private function doResetPassword ( $requestData ) {
        self::log("START doResetPassword()");
        $eMailOrNickname = $this->getAndCheckMandatoryField($requestData,"emailOrNickname");
        $customer = $this->loadCustomer($eMailOrNickname);
        if ( ! $customer ) {
            throw new Schracklive_S4s_UserNotFoundException($eMailOrNickname);
        } else if ( ! $customer->getSchrackS4sId() ) {
            // not connected to S4S
            throw new Exception("User not connected.");
        }
        $this->ensureConfirmation($customer);
        $templateID = Mage::getStoreConfig('schrack/s4s/reset_password_mail_template_id_new');
        try {
            $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $writeConnection->beginTransaction();

            $customer->setSchrackChangepwToken($customer->getRandomConfirmationKey());
            $customer->save();

            $this->sendCustomerEmail($customer,$templateID);
            self::log("reset password email sent");

            $writeConnection->commit();
        } catch ( Exception $ex ) {
            $writeConnection->rollback();
            throw $ex;
        }
        self::log("doResetPassword() END");
    }

    private function doResendConfirmationMail ( $requestData ) {
        self::log("START doResendConfirmationMail()");
        $eMailOrNickname = $this->getAndCheckMandatoryField($requestData,"emailOrNickname");
        $customer = $this->loadCustomer($eMailOrNickname);
        if ( ! $customer ) {
            throw new Schracklive_S4s_UserNotFoundException($eMailOrNickname);
        }
        $confirmation = $customer->getConfirmation();
        if ( ! $confirmation || trim($confirmation) == '' ) {
            throw new Schracklive_S4s_Exception("Email address is already confirmed!",-141);
        }
        $templateID = Mage::getStoreConfig('schrack/s4s/resend_confirmation_mail_template_id');
        try {
            $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $writeConnection->beginTransaction();

            $customer->setConfirmation($this->createConfirmationKey());
            $customer->save();
            $this->sendCustomerEmail($customer,$templateID);
            self::log("confirmation email sent again");

            $writeConnection->commit();
        } catch ( Exception $ex ) {
            $writeConnection->rollback();
            throw $ex;
        }
        // throw new Schracklive_S4s_Exception("Not implemented yet!", -88);
        self::log("doResendConfirmationMail() END");
    }

    private function doConfirmTermsOfUse ( $requestData ) {
        self::log("START doConfirmTermsOfUse()");
        $s4sId          = $this->getAndCheckMandatoryField($requestData,'s4suuid');
        $timeStamp      = $this->getAndCheckMandatoryField($requestData,"timeStamp");
        $email          = $this->getAndCheckMandatoryField($requestData,"email");
        $ipAddress      = $this->getAndCheckMandatoryField($requestData,"ipAddress");
        $termsOfUseHash = $this->getAndCheckMandatoryField($requestData,"termsOfUseHash");

        self::$InUpdateMap[$s4sId] = true;

        $customer = Mage::getModel('customer/customer');
        $customer->loadByS4sId($s4sId);
        if ( ! $customer->getId() ) {
            throw new Schracklive_S4s_Exception("S4S ID '$s4sId' not found!", -121);
        }

        // get mentioned terms:
        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql = "SELECT * FROM schrack_terms_of_use WHERE content_hash = ?";
        $dbRes = $readConnection->fetchAll($sql,$termsOfUseHash);
        if ( count($dbRes) < 1 ) {
            throw new Schracklive_S4s_Exception("Invalid hash code!",-151);
        }
        $currentTermsOfUse = reset($dbRes);

        // check timestamp:
        $now = time();
        $maxAgeTs = $now - 300;
        $ts = strtotime($timeStamp);

        $tsTXT = date(DATE_RFC822,$ts);
        $maxAgeTsTXT = date(DATE_RFC822,$maxAgeTs);


        if ( $ts < $maxAgeTs ) {
            throw new Schracklive_S4s_Exception("UTC timestamp too old!",-152);
        } else if ( $ts > $now ) {
            throw new Schracklive_S4s_Exception("UTC timestamp in the future!",-153);
        }

        // check if still unconfirmed (else do nothing):
        $sql = "SELECT count(*) FROM schrack_terms_of_use_confirmation WHERE customer_id = ? AND terms_id = ?";
        $cnt = $readConnection->fetchOne($sql,[$customer->getId(), $currentTermsOfUse['entity_id']]);

        if ( $cnt == 0 ) {
            $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $writeConnection->beginTransaction();
            try {
                $sql = " INSERT INTO schrack_terms_of_use_confirmation"
                     . " (customer_id,terms_id,terms_version,client_terms_content_hash,client_ip,client_type,user_email,confirmed_at)"
                     . " VALUES(?,?,?,?,?,?,?,?)";
                $writeConnection->query($sql, [
                    $customer->getId(),
                    $currentTermsOfUse['entity_id'],
                    $currentTermsOfUse['version'],
                    $termsOfUseHash,
                    $ipAddress,
                    'sfs_app',
                    $email,
                    $timeStamp
                ]);
                $customer->setSchrackLastTermsConfirmed(1);
                Mage::log($email . ' -> Set user-term state = 1 : from S4S Helper', null, "terms_of_use_state.log");
                $customer->save();
                $writeConnection->commit();
            } catch ( Exception $ex ) {
                $writeConnection->rollback();
                throw $ex;
            }
        }

        self::log("doConfirmTermsOfUse() END");
    }

    private function doRequestCountryChange ( $requestData ) {
        self::log("START doRequestCountryChange()");
        $s4sId         = $this->getAndCheckMandatoryField($requestData,'s4suuid');
        $targetCountry = $this->getAndCheckMandatoryField($requestData,"targetCountryCode");

        self::$InUpdateMap[$s4sId] = true;

        $customer = Mage::getModel('customer/customer');
        $customer->loadByS4sId($s4sId);
        if ( ! $customer->getId() ) {
            throw new Schracklive_S4s_Exception("S4S ID '$s4sId' not found!", -121);
        }

        $sourceCountry = strtoupper($this->getShopCountry());
        $targetCountry = strtoupper($targetCountry);

        if (        $targetCountry != "COM"
              && (
                       strlen($targetCountry) != 2
                    || $targetCountry[0] < 'A' || $targetCountry[0] > 'Z'
                    || $targetCountry[1] < 'A' || $targetCountry[1] > 'Z'
                 )
            ) {
            throw new Schracklive_S4s_Exception("Invalid country code '$targetCountry'!", -161);
        }

        $otherShopCountryMap = $this->getOtherShopCountryMap();
        if ( ! isset($otherShopCountryMap[$targetCountry]) ) {
            throw new Schracklive_S4s_Exception("Unsupported country '$targetCountry'!", -162);
        }

        $recipient = Mage::getStoreConfig('schrack/s4s/change_country_request_recipient');
        if ( ! $recipient ) {
            $advisorPrinzipal = $customer->getSchrackAdvisorPrincipalName();
            $advisor = Schracklive_SchrackCustomer_Model_Customer::getAdvisorForPrincipalName($advisorPrinzipal);
            if ( is_object($advisor) ) {
                $recipient = $advisor->getEmail();
            }
        }
        if ( ! $recipient ) {
            $recipient = Mage::getStoreConfig('schrack/s4s/advisor_principal_name');
        }
        if ( ! $recipient ) {
            throw new Exception("No recipient could be detected for user {$customer->getEmail()} !");
        }

        $fromEmail  = Mage::getStoreConfig('schrack/s4s/from_email'); //fetch sender email
        $fromName   = Mage::getStoreConfig('schrack/s4s/from_name'); //fetch sender name

        $body = "Hello!\n\n"
              . "User " . $customer->getEmail() . " in country " . $sourceCountry . " has requested changing country to our "
              . $targetCountry . " webshop in Schrack4students.\nPlease arrange the necessary steps in Dynos.\n"
              . "The Schrack4students server will be notified automatically when the user is sent to the new webshop.\n\n"
              . "Best regards\n";

        $mail = new Zend_Mail('utf-8');
        $mail->setFrom($fromEmail,$fromName)
            ->setSubject('Country change requested')
            ->setBodyHtml($body)
            ->addTo($recipient)
            ->send();

        // Logs complete mailtext and recipient (DYNOS staff):
        self::log('Send doRequestCountryChange E-Mail to : ' . $recipient . ' :');
        self::log($body);

        self::log("doRequestCountryChange() END");
    }

    private function doLogtest ( $requestData ) {
        return [];
    }

    private function createConfirmationKey () {
        $s = "S4S_API_" . date(DATE_ATOM) . '_' . rand(0,100000);
        return md5($s);
    }

    private function sendCustomerEmail ( $customer, $templateID ) {
        $fromEmail  = Mage::getStoreConfig('schrack/s4s/from_email'); //fetch sender email
        $fromName   = Mage::getStoreConfig('schrack/s4s/from_name'); //fetch sender name

        $this->_storeId = $customer->getSendemailStoreId();
        $url = Mage::helper('schrack/backend')->getFrontendUrl('customer/account/confirm/',
            ['id' => $customer->getId(), 'key' => $customer->getConfirmation()]);
        self::log("confirmation link: $url");
        $vars = [
            'customer' => $customer,
            'confirmation_url' => $url
        ]; //for replacing the variables in email with data
        $email = $customer->getEmail();
        $name = $customer->getName();

        $advisorPrinzipal = $customer->getSchrackAdvisorPrincipalName();
        $advisor = Schracklive_SchrackCustomer_Model_Customer::getAdvisorForPrincipalName($advisorPrinzipal);

        /** @var $singleMailApi Schracklive_SchrackSingleMail_Model_SingleMailApi */
        $singleMailApi = Mage::getModel('schracksinglemail/SingleMailApi');
        $singleMailApi->setStoreID($this->_storeId);
        $singleMailApi->setMagentoTransactionalTemplateID($templateID);
        $singleMailApi->setMagentoTransactionalTemplateVariables($vars);
        $singleMailApi->addToEmailAddress($email,$name);
        $singleMailApi->setFromEmail($fromEmail);
        $singleMailApi->setFromName($fromName);
        if ( $advisor && $advisor->getId() ) {
            $singleMailApi->setAdvisorEmail($advisor->getEmail());
        }
        $singleMailApi->createAndSendMail();

        $sendTransactionalMsg = "Email parameters:";
        $sendTransactionalMsg .= " | templateID = " . $templateID;
        $sendTransactionalMsg .= " | sender = " . $fromEmail;
        $sendTransactionalMsg .= " | email = " . $email;
        $sendTransactionalMsg .= " | name = " . $name;
        $sendTransactionalMsg .= " | advisor = " . ($advisor && $advisor->getId() ? $advisor->getEmail() : '-');
        $sendTransactionalMsg .= " | store_id = " . $this->_storeId;
        self::log($sendTransactionalMsg);
    }

    private function getProfileDataForUser ( $customer ) {
        $res = array(
            's4suuId'               => $customer->getSchrackS4sId(),
            'email'                 => $customer->getEmail(),
            'firstName'             => $customer->getFirstname(),
            'lastName'              => $customer->getLastname(),
            'nickName'              => $customer->getSchrackS4sNickname(),
            'school'                => $customer->getSchrackS4sSchool(),
            'isUserActive'          =>      $customer->getGroupId() == Mage::getStoreConfig('schrack/shop/contact_group')
                                        ||  $customer->getGroupId() == Mage::getStoreConfig('schrack/shop/prospect_group')
                                        ||  $customer->getGroupId() == Mage::getStoreConfig('schrack/shop/employee_group'),
            'isEmailConfirmed'      => ( ! $customer->getConfirmation() || $customer->getConfirmation() <= '' ),
            'isSchrackEmployee'     => $customer->getGroupId() == Mage::getStoreConfig('schrack/shop/employee_group'),
            'crmId'                 => $customer->getSchrackS4yId()
            // 'isPasswordChanged'     => false
        );
        if ( isset($customer->getData()['gender']) && is_numeric($customer->getGender()) ) {
            $res['genderIsFemale'] = $customer->getGender() == 2;
        }
        return $res;
    }

    private function getCommonDbUserCountry ( $email ) {
        $readConn = Mage::getSingleton('core/resource')->getConnection('commondb_read');
        $sql = "SELECT country_id FROM magento_common.login_token WHERE email = ?;";
        $res = $readConn->fetchOne($sql,$email);
        return $res;
    }

    private function getOtherShopCountryMap () {
        $readConn = Mage::getSingleton('core/resource')->getConnection('commondb_read');
        $sql = " SELECT DISTINCT IF(country_id LIKE 'co','COM',UPPER(country_id)) AS country"
             . " FROM magento_common.login_token "
             . " WHERE LENGTH(country_id) > 1 AND country_id <> ?"
             . " ORDER BY country";
        $res = $readConn->fetchCol($sql,strtolower($this->getShopCountry()));
        $res = array_fill_keys($res,true);
        return $res;
    }

    private function authenticateExtended ( $eMailOrNickname, $passWord ) {
        $customer = $this->loadCustomer($eMailOrNickname);
        if ( ! $customer ) {
            sleep(5);
            throw new Schracklive_S4s_WrongEmailOrPasswordException();
            return false;
        }
        $this->ensureConfirmation($customer);
        $this->authenticateCore($customer,$passWord);
        if ( ! $customer->getSchrackS4sId() ) {
            // not connected to S4S
            throw new Schracklive_S4s_UserNotConnectedException($this->createConnectionToken($customer->getEmail()), $customer);
        }
        return $customer;
    }

    private function ensureConfirmation ( $customer ) {
        $confirmation = $customer->getConfirmation();
        if ( $confirmation && $confirmation > '' ) {
            throw new Schracklive_S4s_UserNotConfirmedException();
        }
    }

    private function authenticateCore ( $customer, $passWord, $skipConfirmationCheck = false ) {
        $ok = false;
        try {
            if ( ! $customer ) {
                throw new Exception("no cutomer");
            }
            $customer->setSkipConfirmationIfEmail($customer->getEmail());
            $ok = $customer->authenticate($customer->getEmail(), $passWord);
        } catch ( Exception $e ) {
            sleep(5);
            throw new Schracklive_S4s_WrongEmailOrPasswordException();
            $ok = false;
        }
        if ( ! $ok ) {
            sleep(5);
            throw new Schracklive_S4s_WrongEmailOrPasswordException();
        }
    }

    private function loadCustomer ( $eMailOrNickname ) {
        $customer = Mage::getModel('customer/customer');
        $customer->loadByEmail($eMailOrNickname);
        if ( ! $customer->getId() ) {
            $customer->loadByS4sNickname($eMailOrNickname);
        }
        return $customer->getId() ? $customer : false;
    }

    private function getAndCheckMandatoryField ( $requestData, $fieldName, $ensureStringContent = true, $altFieldName = false ) {
        if ( ! isset($requestData->$fieldName) || ($ensureStringContent && $requestData->$fieldName <= '') ) {
            if ( ! $altFieldName || ! isset($requestData->$altFieldName) || ($ensureStringContent && $requestData->$altFieldName <= '') ) {
                throw new Schracklive_S4s_MissingFieldException($fieldName);
            } else {
                return $requestData->$altFieldName;
            }
        }
        return $requestData->$fieldName;
    }

    private function getAndCheckMandatoryBooleanField ( $requestData, $fieldName ) {
        $val = $this->getAndCheckMandatoryField($requestData,$fieldName,false);
        return $this->parseBooleanVal($fieldName,$val);
    }

    private function getAndCheckOptionalBooleanField ( $requestData, $fieldName ) {
        if ( ! isset($requestData->$fieldName) ) {
            return null;
        }
        return $this->parseBooleanVal($fieldName,$requestData->$fieldName);
    }

    private function parseBooleanVal ( $fieldName, $val ) {
        if ( $val === true || $val === 1 || $val === "1" ) {
            return true;
        } else if ( $val === false || $val === 0 || $val === "0" ) {
            return false;
        } else {
            throw new Schracklive_S4s_Exception("Invalid boolean value '$val' submitted for field '$fieldName' in request!",-103);
        }
    }

    private function sendProspectMessage ( $account, $customer, $shopCountry ) {
        $prospectMessageContent['schrack_prospect_type']      = $customer->getSchrackCustomerType() == Schracklive_SchrackCustomer_Model_Prospect::PROSPECT_TYPE_LIGHT ? 0 : 1;
        $prospectMessageContent['email']                      = $customer->getEmail();
        $prospectMessageContent['prefix']                     = $customer->getPrefix();
        $prospectMessageContent['lastname']                   = $customer->getLastname();
        $prospectMessageContent['firstname']                  = $customer->getFirstname();
        $prospectMessageContent['schrack_newsletter']         = $customer->getSchrackNewsletter();
        $prospectMessageContent['schrack_wws_contact_number'] = $customer->getSchrackWwwsContactNumber();
        $prospectMessageContent['salutatory']                 = $customer->getSchrackSalutatory();

        if ( isset($customer->getData()['gender']) && is_numeric($customer->getGender()) ) {
            $prospectMessageContent['gender'] = $customer->getGender();
        } else {
            $prospectMessageContent['gender'] = '';
        }
        $prospectMessageContent['schrack_mobile_phone']       = $customer->getSchrackMobilePhone();
        $prospectMessageContent['schrack_fax']                = $customer->getSchrackFax();
        $prospectMessageContent['schrack_telephone']          = $customer->getSchrackTelephone();
        $prospectMessageContent['schrack_s4s_nickname']       = $customer->getSchrackS4sNickname();
        $prospectMessageContent['schrack_s4s_school']         = $customer->getSchrackS4sSchool();
        $prospectMessageContent['schrack_s4s_id']             = $customer->getSchrackS4sId();

        $prospectMessageContent['vat_identification_number']      = $account->getVatIdentificationNumber();
        $prospectMessageContent['vat_local_number']               = $account->getVatLocalNumber();
        $prospectMessageContent['company_registration_number']    = $account->getCompanyRegistrationNumber();
        $prospectMessageContent['schrack_advisor_principal_name'] = $account->getAdvisorPrincipalName();
        $prospectMessageContent['name2']                          = $account->getName2();
        $prospectMessageContent['name3']                          = $account->getName3();

        if ($account->getName1() != 'PROSLI') {
            $billingAddress                                       = $account->getBillingAddress();
            $street                                               = $billingAddress->getStreet();
            $prospectMessageContent['name1']                      = $account->getName1();
            $prospectMessageContent['street']                     = $street[0];
            $prospectMessageContent['postcode']                   = $billingAddress->getPostcode();
            $prospectMessageContent['city']                       = $billingAddress->getCity();
        } else {
            $prospectMessageContent['name1']    = '';
            $prospectMessageContent['street']   = '';
            $prospectMessageContent['postcode'] = '';
            $prospectMessageContent['city']     = '';
        }

        $prospectMessageContent['country_id']                     = $shopCountry;
        $prospectMessageContent['homepage']                       = $account->getHomepage();
        $prospectMessageContent['newsletter']                     = $account->getNewsletter();
        $prospectMessageContent['user_confirmed']                 = 0;
        $prospectMessageContent['wws_customer_id']                = $account->getWwsCustomerId();
        $prospectMessageContent['shop_language']                  = strtoupper(substr(Mage::app()->getLocale()->getLocaleCode(), 0 , 2));
        // Fix for Saudi Arabia:
        if (stristr($prospectMessageContent['shop_language'], 'AR')) $prospectMessageContent['shop_language'] = 'EN';
        $prospectMessageContent['account_type']                   = $account->getAccountType();
        $prospectMessageContent['enterprise_size']                = $account->getEnterpriseSize();
        $prospectMessageContent['rating']                         = $account->getRating();
        $prospectMessageContent['wws_branch_id']                  = $account->getWwwsBranchId();
        $prospectMessageContent['sales_area']                     = $account->getSalesArea();
        $prospectMessageContent['homepage']                       = $account->getHomapage();
        $prospectMessageContent['description']                    = $account->getDescription();
        $prospectMessageContent['schrack_department']             = $account->getInformation();
        $prospectMessageContent['company_prefix']                 = $account->getCompanyPrefix();
        $prospectMessageContent['currency_code']                  = Mage::app()->getStore()->getBaseCurrencyCode();

        // Send message to S4Y about successful confirmation of the customer:
        $prospectMessageContent['prospect_source'] = 0;  // SHOP sends always 0 as source
        $prospect = Mage::getSingleton('crm/connector')->putProspect($prospectMessageContent);
    }

    private function getShopCountry () {
        return strtolower(Mage::getStoreConfig('schrack/general/country'));
    }

    private function checkS4sId ( $s4sId ) {
        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql = "SELECT count(schrack_s4s_id) FROM customer_entity WHERE schrack_s4s_id = ?";
        $cnt = $readConnection->fetchOne($sql, $s4sId);
        if ( $cnt > 0 ) {
            throw new Schracklive_S4s_Exception("s4suuid '$s4sId' already in use for another user/email!", -118);
        }
    }

    private function loadTermsOfUseData () {
        if ( $this->termsOfUseCount === false ) {
            $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $sql = "SELECT count(*) FROM schrack_terms_of_use";
            $this->termsOfUseCount = (int) $readConnection->fetchOne($sql);
            $this->termsOfUseAvail = $this->termsOfUseCount > 0;
            if ( $this->termsOfUseAvail ) {
                $sql = "SELECT entity_id, content FROM schrack_terms_of_use ORDER BY entity_id DESC LIMIT 1";
                $dbRes = $readConnection->fetchAll($sql);
                $row = reset($dbRes);
                $this->currentTermsOfUseVersion = $row['entity_id'];
                $this->currentTermsOfUseContent = $row['content'];
            }
        }
    }

    private function createConnectionToken ( $email ) {
        $helper = Mage::helper('schrackcustomer/loginswitch');
        $token = $helper->createToken($email);
        return $token;
    }

    private function checkConnectionToken ( $email, $token ) {
        $helper = Mage::helper('schrackcustomer/loginswitch');
        $tokenEmail = $helper->validateToken($token,900);
        if ( $tokenEmail == $email ) {
            return true;
        }
        throw new Schracklive_S4s_Exception("Connection token is invalid or expired.", -123);
    }

    private function writeResponse ( $jsonResponse ) {
        self::logJson('RESPONSE',$jsonResponse);
        echo $jsonResponse;
    }

    private static function log ( $msg ) {
        // TODO: make controllable in backend...
        Mage::log($msg,null,'s4s_api.log');
    }

    private static function logJson ($headLine, $jsonString ) {
        // TODO: make controllable in backend...
        $arr = json_decode($jsonString);
        if ( isset($arr->password) ) {
            $arr->password = 'XXXXXXXXXXXXXXXX';
        }
        $jsonString = json_encode($arr, JSON_PRETTY_PRINT);
        self::log("$headLine:\n$jsonString\n");
    }

    private function sendChangedUserDataToS4sServer (array $changedData, $s4sId ) {
        $url = Mage::getStoreConfig('schrack/s4s/user_record_update_url');
        if ( ! $url ) {
            self::log("Yet no user_record_update_url defined in backend - aborting.");
            return;
        }
        if ( $url && count($changedData) > 0 ) {
            $changedData['s4suuid'] = $s4sId; // this is the key
            self::log("Changed data detected for the field(s) " . implode(array_keys($changedData)));
            $this->sendDataToS4sServer($changedData, $url);
        } else if (count($changedData) == 0) {
            self::log("no relevant data change found");
        }
    }

    private function sendDataToS4sServer ( array $data, $url ) {
        if ( ! $url ) {
            throw new Exception("No URL passed!");
        }
        if ($url && count($data) > 0) {
            $json = json_encode($data);
            self::logJson("Sending request to URL $url", $json);

            $headers = array(
                "Content-type: application/json",
                "Content-length: " . strlen($json),
                "Connection: close"
            );

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $responseData = curl_exec($ch);
            if (curl_errno($ch)) {
                $curlErrorMsg = curl_error($ch);
                curl_close($ch);
                self::log("ERROR on sending request: $curlErrorMsg");
            } else {
                curl_close($ch);
                self::logJson("Response got:", $responseData);
                $response = json_decode($responseData);
                $code = -999999;
                if (isset($response->returncode) && is_numeric(isset($response->returncode))) {
                    $code = intval($response->returncode);
                    if ($code != 1) {
                        $msg = isset($response->message) ? $response->message : '(no error message)';
                        $status = isset($response->status) ? $response->status : '(no status)';
                        self::log("ERROR: Remote server returned code $code, status $status and message $msg");
                    }
                }
            }
        }
    }

    private function createRandomPassword () {
        $ucLetters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $lcLetters = strtolower($ucLetters);
        $digits = "0123456789";
        $punctuationMarks = ",.-;:_#*+~!?";
        $all = $ucLetters . $lcLetters . $digits . $punctuationMarks;
        $pw = $this->randChar($ucLetters)
            . $this->randChar($lcLetters)
            . $this->randChar($digits)
            . $this->randChar($punctuationMarks);
        for ( $i = 0; $i < 16; ++$i ) {
            $pw .= $this->randChar($all);
        }
        return $pw;
    }

    private function randChar ( $chars ) {
        $l = strlen($chars);
        $rnd = random_int(0,$l-1);
        return $chars[$rnd];
    }
}
