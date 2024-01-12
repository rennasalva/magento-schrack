<?php

use DrSlump\Protobuf\Message;
use com\schrack\queue\protobuf\ProspectTransfer\ProspectMessage;

class Schracklive_SchrackCustomer_Model_Prospect_Protoimport extends Schracklive_Account_Model_Protoimport_Base {

    static $nameMap = array(
        //'state'           => null,                                // int32
        'type'            => 'schrack_prospect_type',             // enum: 'LIGHT' / 'FULL'
        'source'          => 'prospect_source',                   // enum: 'SHOP' / 'CRM'
        'advisor'         => 'schrack_advisor_principal_name',    // string
        'gender'          => 'gender',                            // string
        'last_name'       => 'lastname',                          // string
        'first_name'      => 'firstname',                         // string
        'title'           => 'prefix',                            // string
        'salutatory'      => 'schrack_salutatory',                // string
        'description'     => 'schrack_comments',                  // string
        'phone_office'    => 'schrack_telephone',                 // string
        'fax_office'      => 'schrack_fax',                       // string
        'mobile'          => 'schrack_mobile_phone',              // string
        'email'           => 'email',                             // string
        'emails'          => 'schrack_emails',                    // string
        'department'      => 'schrack_department',                // string
        'salutation'      => 'company_prefix',                    // string -> default S4Y = Firma
        'name1'           => 'name1',                             // string
        'name2'           => 'name2',                             // string
        'name3'           => 'name3',                             // string
        'street'          => 'street',                            // string
        'zip'             => 'postcode',                          // string
        'city'            => 'city',                              // string
        'country'         => 'country_id',                        // string
        'phone_company'   => 'telephone_company',                 // string
        'fax_company'     => 'fax_company',                       // string
        'homepage'        => 'homepage',                          // string
        'rating'          => 'rating',                            // string
        'enterprise_size' => 'enterprise_size',                   // string
        'currency'        => 'currency_code',                     // string
        'language'        => 'shop_language',                     // string
        'branch'          => 'wws_branch_id',                     // int32
        'sales_area'      => 'sales_area',                        // int32
        'vat_id'          => 'vat_identification_number',         // string
        'com_reg'         => 'company_registration_number',       // string
        'vat_number'      => 'vat_local_number',                  // string -> Account : vat_local_number
        'newsletter'      => 'newsletter',                        // bool -> EAV customer
        'user_confirmed'  => 'user_confirmed',                    // bool -> EAV customer
        'account_type'    => 'account_type',                      // string account_type (account)
        'contact_id'      => 'schrack_wws_contact_number',        // string schrack_wws_contact_number (customer)
        'acl_role'        => 'schrack_acl_role_id',               // int
        's4s_nickname'    => 'schrack_s4s_nickname',              // string
        's4s_school'      => 'schrack_s4s_school',                // string
        's4s_id'          => 'schrack_s4s_id'                     // string
    );

    private $companyPrefixMap = array(
        'at' => 'Firma',
        'ba' => 'Firma',
        'be' => 'Firma',
        'bg' => 'Фирма',
        'co' => 'Company',
        'cz' => 'Společnost',
        'de' => 'Firma',
        'hr' => 'Firma',
        'hu' => 'Cég',
        'pl' => 'Firma',
        'ro' => 'Compania',
        'rs' => 'Firma',
        'ru' => 'Company',
        'sa' => 'Company',
        'si' => 'Podjetje',
        'sk' => 'Spoločnosť'
    );

    protected function getMappingNamesProtobufToMagento() {
        return self::$nameMap;
    }

    protected function getType() {
        return Schracklive_Account_Helper_Protobuf::TYPE_PROSPECT;
    }

    protected function checkInstance(Message $message) {
        return $message instanceof ProspectMessage;
    }

    // This is a key that will be stored in database to prevent importing old data (old data deletes newer data!!):
    protected function getMessageKeyFromProtobuf ( Message $message ) {
        $protoProspect = $message->getProspect();
        $email = $protoProspect->getEmail();
        return 'prospect-' . $email;
    }

    protected function insertOrUpdateOrDeleteImpl(Message $message) {
//////////////////////////////////////////////////////////////////////////////////
// Here you can define actions on incoming message from queue for the prospect! //
//////////////////////////////////////////////////////////////////////////////////

        $prospectAlreadyExists = false;
        $contactEmail          = "";
        $updatedBy             = 'protoProspectImport';
        $errors                = array();
        $prospectType          = '';
        $upgradeFlag           = false;
        $debug                 = false;
        $existingAccountID     = 0;
        $resource              = Mage::getSingleton('core/resource');
        $writeConnection       = $resource->getConnection('core_write');
        $readConnection        = $resource->getConnection('core_read');
        $prospectAccountID     = '';
        $accountUpdate         = false;
        $accountNew            = false;
        $crmCreatedProspect    = false;

        /** @var com\schrack\queue\protobuf\ProspectTransfer\ProspectMessage\Prospect $protoProspect */
        $protoProspect = $message->getProspect();
        $state = $protoProspect->getState();

        // NEVER (!) import prospects with state 4!
        if (    $state == Schracklive_SchrackCustomer_Model_Protoimport::EXTRANET_STATUS_DELETED_IN_SHOP
             && ! $protoProspect->getDeleted() ) {
            self::log("Prospect recieved with state $state - ignoring.");
            return null;
        }

        // First of all, check if prospect, is really stil prospect:
        $queryFindProspectCustomerData = "SELECT group_id, schrack_account_id FROM customer_entity WHERE email LIKE '" . $protoProspect->getEmail() . "'";
        // DLA 20170317: removed last part of query to make the check below happen.
        $result = $readConnection->fetchAll($queryFindProspectCustomerData);
        if (is_array($result) && !empty($result)) {
            foreach ($result as $index => $recordset) {
                $groupID = intval($recordset['group_id']);
            }
            if ($groupID > 0 && $groupID != intval(Mage::getStoreConfig('schrack/shop/prospect_group'))) {
                Mage::log('Prospect message import fault: Prospect is not in prospect group (no data change allowed) -> ' . $protoProspect->getEmail(), null, '/prospects/prospect_err.log');
                // Don't change data from magento customer, if customer status changed (prospect should have default prospect group as group_id):
                return;
            } else {
                if ($groupID > 0 && $groupID == intval(Mage::getStoreConfig('schrack/shop/prospect_group'))) {
                    $prospectAccountID = intval($recordset['schrack_account_id']);
                }
            }
        } else if ( $state == Schracklive_SchrackCustomer_Model_Protoimport::EXTRANET_STATUS_INACTIVE ) {
            // prospect inactive but not in shop DB: ignore!
            self::log("Unknown prospect recieved with state $state - ignoring.");
            return null;
        }
        // Hint: WWS-Customer-ID is not existent for a prospect, so we need placeholder for full register prospect & prospect light:

        // Check, if prospect should be deleted from the shop:
        if ($protoProspect->getDeleted() == true) {
            // Three Actions should be executed:
            // 1. Delete prospect
            // 2. Delete system_contact
            // 3. Delete account

            $queryFindProspectCustomerData = "SELECT schrack_account_id, schrack_customer_type FROM customer_entity WHERE email LIKE '" . $protoProspect->getEmail() . "'";
            $result = $readConnection->fetchAll($queryFindProspectCustomerData);

            if (is_array($result) && !empty($result)) {
                foreach ($result as $index => $recordset) {
                    $accountID = intval($recordset['schrack_account_id']);
                    $customerType = $recordset['schrack_customer_type'];
                    break;
                }

                if ( $customerType != 'light-prospect' && $customerType != 'full-prospect' ) {
                    Mage::log('Prospect deletion: ERROR: Customer is not a prospect: email -> ' . $protoProspect->getEmail(), null, '/prospects/prospect_err.log');
                    return;
                }

                if ($accountID && $accountID > 0) {
                    $queryDeleteAccount = "DELETE FROM account WHERE account_id = " . $accountID;
                    $writeConnection->query($queryDeleteAccount);
                    Mage::log('Prospect deletion: Account deleted: account-id -> ' . $accountID, null, '/prospects/prospects.log');

                    // Delete prospect and system_contact at the same time:
                    $queryDeleteSystemContactAndProspect = "DELETE FROM customer_entity WHERE schrack_account_id = " . $accountID;
                    $writeConnection->query($queryDeleteSystemContactAndProspect);
                    Mage::log('Prospect deletion: Prospect/System Contact deleted: email -> ' . $protoProspect->getEmail(), null, '/prospects/prospects.log');
                } else {
                    // Account not found:
                    Mage::log('Prospect deletion: ERROR: Account not found: email -> ' . $protoProspect->getEmail(), null, '/prospects/prospect_err.log');
                }
            } else {
                // Prospect not found:
                Mage::log('Prospect deletion: ERROR: Prospect not found: email -> ' . $protoProspect->getEmail(), null, '/prospects/prospect_err.log');
            }

            return;
        }


        // Light prospect:
        if ($protoProspect->getType() == 0) {
            // Placeholder for prospect light:
            $pseudoWwsCustomerID = 'PROSLI';
        }

        // Placeholder for full register prospect:
        if ($protoProspect->getType() == 1) {
            // Placeholder for prospect light:
            $pseudoWwsCustomerID = 'PROS';
        }

        // Placeholder light prospect upgrade to full prospect:
        if ($protoProspect->getType() == 2) {
            // Placeholder for prospect light:
            $pseudoWwsCustomerID = 'PROS';
            $upgradeFlag = true;
        }

        // Find out, who is the "creator" of data:
        if ($protoProspect->getSource() == 1) {
            // 1 => CRM:
            $crmCreatedProspect = true;
        }

///////////////////////////////////////////////////////
///  Building account data from prospect messsage:  ///
///////////////////////////////////////////////////////
        $accountData = array();

        $mageTranslation = Mage::getModel('core/translate')->setLocale(Mage::getStoreConfig('general/locale/code', Mage::getStoreConfig('schrack/shop/store')))
                                                            ->init('frontend', true);

        $genderArray = array(1 => $mageTranslation->translate(array('Male')), 2 => $mageTranslation->translate(array('Female')));

        if ($pseudoWwsCustomerID == 'PROS') {
            //$accountData['prefix'] = $mageTranslation->translate(array('Company'));
            $companyName = $protoProspect->getName1();
        } else {
            if ($upgradeFlag == true) {
                //$accountData['prefix'] = $mageTranslation->translate(array('Company'));
                $companyName = $protoProspect->getName1();
            } else {
                $companyName = 'PROSLI';
            }
        }

        if ($protoProspect->getSalesArea()) {
            $salesArea = $protoProspect->getSalesArea();
        } else {
            $salesArea = $protoProspect->getBranch();
        }

        $accountData['name1']                       = $companyName;
        $accountData['name2']                       = $protoProspect->getName2();
        $accountData['name3']                       = $protoProspect->getName3();
        $accountData['prefix']                      = $protoProspect->getSalutation();
        $accountData['wws_branch_id']               = $protoProspect->getBranch();
        $accountData['advisor_principal_name']      = $protoProspect->getAdvisor();
        $accountData['email']                       = $protoProspect->getEmail();
        $accountData['homepage']                    = $protoProspect->getHomepage();
        $accountData['description']                 = $protoProspect->getDescription();
        $accountData['vat_identification_number']   = $protoProspect->getVatId();
        $accountData['company_registration_number'] = $protoProspect->getComReg();
        $accountData['sales_area']                  = $salesArea;
        $accountData['rating']                      = $protoProspect->getRating();
        $accountData['enterprise_size']             = $protoProspect->getEnterpriseSize();
        $accountData['account_type']                = $protoProspect->getAccountType();
        $accountData['vat_local_number']            = $protoProspect->getVatNumber();

if ($debug) Mage::log($pseudoWwsCustomerID . ' -> ' . 'upgrade-flag: ' . $upgradeFlag, null, '/prospects/prospects_import.log');

        // Set creator/updater of the affected recordsets manually:
        //Mage::helper('schrack/mysql4')->setChangeIdentifier($updatedBy);

        if (method_exists($protoProspect, 'getContactEmail')) {
            $contactEmail = $protoProspect->getContactEmail();
        }

////////////////////////////////////////////
///  Building account from account data  ///  @var $account Schracklive_Account_Model_Account
////////////////////////////////////////////
        try {
            // Try to find old account or create new (empty) account object:
            if ($prospectAccountID) {
                $account = Mage::getModel('account/account')->load($prospectAccountID, 'account_id');
            } else {
                // Inititialising as empty account (= not found) to prevent PHP-Notice:
                $account = false;
            }
            if ($account && $account->getId()) {

if($debug) Mage::log('ACCOUNT FOUND: account-id -> ' . $account->getId(), null, '/prospects/prospects_import.log');
                $accountUpdate = true;
                $existingAccountID = $account->getId();
                foreach ($accountData as $key => $value) {
                    $account->setData($key, $value);
                }
            } else {
                // New Account:
                $account = Mage::getModel('account/account');
                $account->setData($accountData);
                $accountNew = true;
            }

            $account->save();

            // Save update timestamp:
            if ($accountNew == true) {
                // Createor timestamp:
                $newAccountTimestampQuery = "UPDATE account SET created_by = 'protoProspectImport', created_at = '" . date('Y-m-d H:i:s') . "' WHERE account_id = " . $account->getId();
                $writeConnection->query($newAccountTimestampQuery);
            }

            // Save update timestamp:
            if ($accountUpdate == true) {
                // Createor timestamp:
                $updateAcountTimestampQuery = "UPDATE account SET updated_by = 'protoProspectImport', updated_at = '" . date('Y-m-d H:i:s') . "' WHERE account_id = " . $account->getId();
                $writeConnection->query($updateAcountTimestampQuery);
            }

        } catch (Schracklive_Account_Exception $e) {
            Mage::logException($e);
            if ($e->getCode() == Schracklive_Account_Exception::VALIDATION_ERROR) {
                foreach ($e->getMessages() as $eMsg) {
                    $errors[] = $eMsg->getText();
                }
            } else {
                throw $e;
            }
        }

        if (is_object($account) && !$account->getId()) {
            throw new Exception('Error saving account (Schracklive_SchrackCustomer_Model_Prospect_Protoimport): ' . $protoProspect->getEmail());
        }

        if (empty($errors)) {
            $prospect = Mage::getModel('schrackcustomer/prospect');
            $prospectType = $prospect->getSpecificProspectType($pseudoWwsCustomerID);

            if ($prospectType) {
                if ($contactEmail != '') {
                    // Load existing cstomer, if contact-email is available:
                    $existingContact = Mage::getModel('customer/customer');
                    $existingContact->setWebsiteId(Mage::app()->getWebsite()->getId());
                    $existingContact->loadByEmail($contactEmail);
                    if (is_object($existingContact) && $existingContact->getId()) {
                        $prospectAlreadyExists = true;
                    }
                    $email = $contactEmail;
                } else {
                    // Load existing customer, if contact-email is not available:
                    $existingContact = Mage::getModel('customer/customer');
                    $existingContact->setWebsiteId(Mage::app()->getWebsite()->getId());
                    $existingContact->loadByEmail($accountData['email']);
                    if (is_object($existingContact) && $existingContact->getId()) {
                        $prospectAlreadyExists = true;
                    }
                    $email = $accountData['email'];
                }


//////////////////////////////////
////  Building customer data:  ///   @var $customer Schracklive_SchrackCustomer_Model_Customer
//////////////////////////////////
                if ($prospectAlreadyExists == true) {
                    $customer = $existingContact;
                } else {
                    $customer = Mage::getModel('customer/customer');
                }

                $statusOfDSGVO = 0;

                $customer->setSchrackWwsCustomerId('');
                if ($prospectAlreadyExists == false) {
                    $customer->setEmail($email);
                    // Status about Data Protection Confirmation (DSGVO):
                    $query = "SELECT schrack_confirmed_dsgvo FROM customer_dsgvo WHERE email LIKE '" . $email . "'";
                    $statusOfDSGVO = $readConnection->fetchOne($query);
                    // Status of User Terms Confirmed:
                    $query  = "SELECT client_terms_content_hash FROM schrack_terms_of_use_confirmation";
                    $query .= " WHERE user_email LIKE '" . $email . "' ORDER BY terms_id DESC LIMIT 1";
                    $currentUserTermsConfirmedVersion = $readConnection->fetchOne($query);
                    $query  = "SELECT content_hash FROM schrack_terms_of_use";
                    $query .= " ORDER BY entity_id DESC LIMIT 1";
                    $currentUserTermsVersion = $readConnection->fetchOne($query);
                    if ($currentUserTermsVersion && $currentUserTermsConfirmedVersion == $currentUserTermsVersion) {
                        $customer->setSchrackLastTermsConfirmed(1);
                        Mage::log($email . ' -> automatically set user-term state = 1 : from DYNOS Import', null, "terms_of_use_state.log");
                    } else {
                        $customer->setSchrackLastTermsConfirmed(0);
                    }
                }
                $customer->setStoreId(Mage::getStoreConfig('schrack/shop/store'));
                $customer->setWebsiteId(Mage::getStoreConfig('schrack/shop/website'));
                $customer->setIsActive(1);
                $customer->setGender($protoProspect->getGender());
                // $customer->setForceConfirmed(true); Customer should confirm himself!
                $customer->setSchrackAccountId($account->getId());
                $customer->setSchrackUserPrincipalName();
                $customer->setSchrackMainContact(true);
                $customer->setSchrackCustomerType($prospectType);
                $customer->setSchrackNewsletter( (int) $protoProspect->getNewsletter() );
                $customer->setGroupId(Mage::getStoreConfig('schrack/shop/prospect_group'));   // 9, 10 = Prospect -> different -> depends from country database
                $customer->setSchrackWwsContactNumber(0);
                $customer->setSchrackS4yId($protoProspect->getS4yId());
                if ($prospectAlreadyExists == false) {
                    $customer->setPassword($customer->generatePassword());
                }
                $customer->setFirstname($protoProspect->getFirstName());
                $customer->setLastname($protoProspect->getLastName());
                $customer->setPrefix($protoProspect->getTitle());
                $customer->setSchrackDepartment($protoProspect->getDepartment());
                $customer->setSchrackTelephone($protoProspect->getPhoneOffice());
                $customer->setSchrackFax($protoProspect->getFaxOffice());
                $customer->setSchrackMobilePhone($protoProspect->getMobile());
                $customer->setSchrackSalutatory($protoProspect->getSalutatory());
                $customer->setSchrackMailinglistTypesCsv(implode(",",$protoProspect->getMailingListTypesList()));
                // we do not take empty/unset values for Schrack 4 Students
                if ( $protoProspect->hasS4sId() && $protoProspect->getS4sId() > '' )
                    $customer->setSchrackS4sId($protoProspect->getS4sId());
                if ( $protoProspect->hasS4sNickname() && $protoProspect->getS4sNickname() > '' )
                    $customer->setSchrackS4sNickname($protoProspect->getS4sNickname());
                if ( $protoProspect->hasS4sSchool() && $protoProspect->getS4sSchool() > '' )
                    $customer->setSchrackS4sSchool($protoProspect->getS4sSchool());
                $customerErrors = $customer->validate();
                if (is_array($customerErrors) && is_array($errors)) {
                    $errors = array_merge($customerErrors, $errors);
                }
                //$customerErrors = $customer->validateExtra();
                if (is_array($customerErrors) && is_array($errors)) {
                    $errors = array_merge($customerErrors, $errors);
                }

                // Reset recordset creator/updater:
                //Mage::helper('schrack/mysql4')->setChangeIdentifier("");

//////////////////////////////////////////
////  Building customer address data:  ///
//////////////////////////////////////////
                if ($pseudoWwsCustomerID == 'PROS') {
                    $addressData['firstname']  = $protoProspect->getFirstName();
                    $addressData['lastname']   = $protoProspect->getLastName();
                    //$addressData['name1']      = $accountData['name1'];
                    //$addressData['name2']      = $accountData['name2'];
                    //$addressData['name3']      = $accountData['name3'];
                    $addressData['street']     = $protoProspect->getStreet();
                    $addressData['postcode']   = $protoProspect->getZip();
                    $addressData['city']       = $protoProspect->getCity();
                    $addressData['country_id'] = $protoProspect->getCountry();
                    $addressData['fax']        = $protoProspect->getFaxCompany();
                }

                if ($pseudoWwsCustomerID == 'PROSLI') {
                    $addressData['firstname']  = $protoProspect->getFirstName();
                    $addressData['lastname']   = $protoProspect->getLastName();
                    //$addressData['name1']      = $pseudoWwsCustomerID; // possible values -> 'PROS' / 'PROSLI'
                    //$addressData['name2']      = $pseudoWwsCustomerID; // possible values -> 'PROS' / 'PROSLI'
                    //$addressData['name3']      = $pseudoWwsCustomerID; // possible values -> 'PROS' / 'PROSLI'
                    $addressData['street']     = $pseudoWwsCustomerID; // possible values -> 'PROS' / 'PROSLI'
                    $addressData['postcode']   = $pseudoWwsCustomerID;
                    $addressData['city']       = $pseudoWwsCustomerID; // possible values -> 'PROS' / 'PROSLI'
                    $addressData['country_id'] = strtoupper(Mage::getStoreConfig('schrack/general/country'));
                    $addressData['telephone']  = '';
                    $addressData['fax']        = '';
                }

                // Old system contact available....?
                //$systemContact = $account->getSystemContact();
                if ($existingAccountID) {
                    $query = "SELECT entity_id FROM customer_entity WHERE schrack_wws_contact_number = -1 AND schrack_account_id = " . $existingAccountID;

if ($debug) Mage::log($query, null, '/prospects/prospects_import.log');

                    $entityId = $readConnection->fetchAll($query);

if ($debug) Mage::log($entityId, null, '/prospects/prospects_import.log');

                    $systemContact = Mage::getModel('customer/customer');
                    $systemContact->setWebsiteId(Mage::app()->getWebsite()->getId());
                    $systemContact->load($entityId[0], 'entity_id');

if ($debug) Mage::log('System-Contact Customer ID: ' . $systemContact->getID(), null, '/prospects/prospects_import.log');
if ($debug) Mage::log('Customer ID: ' . $customer->getID(), null, '/prospects/prospects_import.log');
if ($debug) Mage::log($customer, null, '/prospects/prospects_import.log');

                    $customer->save();

                    if ($customer->getId()) {
                        $query = "UPDATE customer_entity SET schrack_newsletter = " . (int) $protoProspect->getNewsletter() . " WHERE entity_id = " . $customer->getId();
                        $writeConnection->query($query);
if ($debug) Mage::log($query, null, '/prospects/prospects_import.log');
                    }

                    // Don't save system contact at customer. Just assign to object property for further checks (observer, etc.)
                    $customer->setSystemContact($systemContact);
                }

                if (!isset($systemContact) || !$systemContact) {

if ($debug) Mage::log('NO System Contact found', null, '/prospects/prospects_import.log');

                    // Creating new system contact (before saving customer):
                    $systemContact = Mage::getModel('customer/customer');
                    Mage::helper('schrackcustomer')->setupSystemContact($systemContact, $account);
                    $systemContact->setLastname($account->getName1());
                    $systemContact->setMiddlename($account->getName2());
                    $systemContact->setFirstname($account->getName3());

                    // ATTENTION: System contact must be saved before customer!
                    $systemContact->save();

                    // ATTENTION: Finally save customer, because there is a check implemented, that real contact is saved after system contact!
                    $customer->setSystemContact($systemContact);
                    $customer->save();
                } else {

if ($debug) Mage::log('System Contact was successfully found', null, '/prospects/prospects_import.log');
if ($debug) Mage::log($systemContact->getId(), null, '/prospects/prospects_import.log');

                }

                if ($customer->getId()) {
                    $query = "UPDATE customer_entity SET schrack_customer_type = '" . $prospectType . "' WHERE entity_id = " . $customer->getId();
                    $writeConnection->query($query);

if ($debug) Mage::log($query, null, '/prospects/prospects_import.log');

                }

                if ($systemContact->getId()) {
                    $query = "UPDATE customer_entity SET schrack_customer_type = '" . $prospectType . "' WHERE entity_id = " . $systemContact->getId();
                    $writeConnection->query($query);

if ($debug) Mage::log($query, null, '/prospects/prospects_import.log');

                }

                if ($customer->getId() && $statusOfDSGVO > 0) {
                    $query = "UPDATE customer_entity SET schrack_confirmed_dsgvo = " . $statusOfDSGVO . " WHERE entity_id = " . $customer->getId();
                    $writeConnection->query($query);

if ($debug) Mage::log($query, null, '/prospects/prospects_import.log');

                }

                // -------- CREATE NEW PROSPECT -------:
                // Sending account confirm (incl. password generation) email to customer after successful saving of all data:
                if ($prospectAlreadyExists == false) {

if ($debug) Mage::log('New Prospect', null, '/prospects/prospects_import.log');

                    $address = $account->getBillingAddress();
                    // Schracklive_SchrackCustomer_Model_Address
                    if ($address && $address->getId()) {
                        // Updates old address data:
                        foreach ($addressData as $key => $value) {
                            $address->setData($key, $value);
                        }
                    } else {
                        // Create new multi default address (billing + shipping):
                        $address = Mage::getModel('customer/address');
                        $address->setData($addressData);
                        Mage::helper('schrackcustomer')->setupBillingAddress($address, $systemContact->getId());
                    }

                    $address->setCustomerId($systemContact->getId());
                    $address->setSchrackWwsAddressNumber(0);
                    $address->setSchrackType(1);
                    //$address->setIsDefaultBilling(true);
                    //$address->setIsDefaultShipping(true);
                    $address->save();

                    $systemContact->setData('default_billing', $address->getId());
                    $systemContact->setData('default_shipping', $address->getId());
                    $systemContact->save();

                    // Checking WWS Address Number:
                    if ($address->getSchrackWwsAddressNumber() != 0) {
                        $query = "UPDATE customer_address_entity SET schrack_wws_address_number = 0 WHERE entity_id = " . $address->getId();
                        $writeConnection->query($query);
                    }

                    // -- Full prospect --
                    if ($pseudoWwsCustomerID == 'PROS' && $upgradeFlag == false) {

                        // Send mail to customer support for check customer and possible acceptance as customer:
                        $mailSubject = $mageTranslation->translate(array('New Full Prospect Registration Online Shop'));

                        if ($protoProspect->getNewsletter() == true) {
                            $schrackNewsletter = 'Newsletter: ' . $mageTranslation->translate(array('Yes')) . '<br>';
                        } else {
                            $schrackNewsletter = '';
                        }

                        $mailText  = $mageTranslation->translate(array('New Full Prospect Registration Online Shop Headline')) . '<br><br>';
                        $mailText .= '<b>' . $mageTranslation->translate(array('Personal Data')) . '</b>:<br>';
                        $mailText .= $schrackNewsletter;
                        $mailText .= $mageTranslation->translate(array('Gender')) . ': ' . $genderArray[$protoProspect->getGender()] . '<br>';
                        $mailText .= $mageTranslation->translate(array('First Name')) . ': ' . $protoProspect->getFirstName() . '<br>';
                        $mailText .= $mageTranslation->translate(array('Last Name')) . ': ' . $protoProspect->getLastName() . '<br>';
                        $mailText .= $mageTranslation->translate(array('Email')) . ': ' . $protoProspect->getEmail() . '<br>';
                        $mailText .= $mageTranslation->translate(array('Phone')) . ': ' . $protoProspect->getPhoneOffice() . '<br><br>';
                        $mailText .= '<b>' . $mageTranslation->translate(array('Company Information')) . '</b>:<br>';
                        $mailText .= $mageTranslation->translate(array('Companyname')) . ': ' . $protoProspect->getName1() . '<br>';
                        $mailText .= $mageTranslation->translate(array('Companyname')) . ' #2: ' . $protoProspect->getName2() . '<br>';
                        $mailText .= $mageTranslation->translate(array('Contact')) . ': ' . $protoProspect->getName3() . '<br>';
                        if ($protoProspect->getVatNumber()) {
                            $mailText .= $mageTranslation->translate(array('VAT Identification Number')) . ': ' . $protoProspect->getVatNumber() . '<br>';
                        } else {
                            $mailText .= $mageTranslation->translate(array('VAT Identification Number')) . ': ' . substr_replace($protoProspect->getVatId(), ' ', 2, 0) . '<br>';
                        }
                        $mailText .= $mageTranslation->translate(array('Phone')) . ': ' . $protoProspect->getPhoneCompany() . '<br>';
                        $mailText .= $mageTranslation->translate(array('Fax')) . ': ' . $protoProspect->getFaxCompany() . '<br><br>';
                        $mailText .= '<b>' .  $mageTranslation->translate(array('billing address')) . '</b>:<br>';
                        $mailText .= $mageTranslation->translate(array('street')) . ': ' . $protoProspect->getStreet() . '<br>';
                        $mailText .= $mageTranslation->translate(array('Zip')) . ': ' . $protoProspect->getZip() . '<br>';
                        $mailText .= $mageTranslation->translate(array('City')) . ': ' . $protoProspect->getCity() . '<br>';
                        $mailText .= $mageTranslation->translate(array('Country')) . ': ' . $protoProspect->getCountry() . '<br>';
                        $mailText .= $mageTranslation->translate(array('Website')) . ': ' . $protoProspect->getHomepage() . '<br>';
                        $mailText .= $mageTranslation->translate(array('Webshop Country')) . ': ' . strtoupper(Mage::getStoreConfig('schrack/general/country')) . '<br>';
                        $mailText .= $mageTranslation->translate(array('Date')) . ': ' . date('Y-m-d H:i:s') . '<br>';

                        $mail = new Zend_Mail('utf-8');
                        $mail->setFrom(Mage::getStoreConfig('trans_email/ident_sales/email'), Mage::getStoreConfig('general/store_information/name'))
                            ->setSubject($mailSubject)
                            ->setBodyHtml($mailText);

                        // Send mail schrack support employee(s):
                        $checkoutEmailDestinationProspects = Mage::getStoreConfig('schrack/new_self_registration/checkoutEmailDestinationProspects');
                        if ($checkoutEmailDestinationProspects) {
                            if (stristr($checkoutEmailDestinationProspects, ';')) {
                                // Send mail to multiple recipients, if seperated by semicolon:
                                $emailRecipients = explode(';', preg_replace('/\s+/', '', $checkoutEmailDestinationProspects));
                                foreach ($emailRecipients as $index => $emailRecipient) {
                                    $mail->addTo($emailRecipient);
                                }
                            } else {
                                $mail->addTo($checkoutEmailDestinationProspects);
                            }
                            if ($crmCreatedProspect == false) {
                                $mail->send();
                                Mage::log('Reg-Mail Receiver -> ' . $checkoutEmailDestinationProspects, null, 'prospect_mail_send.log');
                                Mage::log('Reg-Mail Text -> ' . $mailText, null, 'prospect_mail_send.log');
                            }
                        }

                        // Send mail to full prospect:
                        Mage::helper('schrackcustomer')->restoreRememberedPasswordHashIfExists($customer->getEmail());
                        $xmlPath = $this->getEmailTemplateXmlPath($crmCreatedProspect);
                        $customer->sendNewAccountEmail('confirmation','','0',NULL,$xmlPath);
                    }

                    // -- Light prospect --
                    if ($pseudoWwsCustomerID == 'PROSLI' && $upgradeFlag == false) {
                        // $storeId = Mage::app()->getStore()->getId();  // --> WRONG!!
                        // $storeId = 1; --> CORRECT !!
                        $storeId = $customer->getStoreId();
                        $xmlPath = $this->getEmailTemplateXmlPath($crmCreatedProspect);
                        $customer->setPassword($customer->generatePassword());
                        $customer->setConfirmation($customer->getRandomConfirmationKey());
                        $customer->save();
                        Mage::helper('schrackcustomer')->restoreRememberedPasswordHashIfExists($customer->getEmail());
                        $customerEmailAddress = $customer->getEmail();
                        // ------------

                        // DEVELOPER-EMAIL:
                        // Check and re-map all eMails to change the recipient in certain cases:
                        if (preg_match('/testuser[0-9]{0,3}_.{2}@schrack.com$/', $customerEmailAddress)) {
                            $customerEmailAddress = Mage::getStoreConfig('schrackdev/customer/mappingDevelopmentMails');
                        }

                        /** @var $singleMailApi Schracklive_SchrackSingleMail_Model_SingleMailApi */
                        $singleMailApi = Mage::getModel('schracksinglemail/SingleMailApi');
                        $singleMailApi->setStoreID($storeId);
                        $singleMailApi->setMagentoTransactionalTemplateIDfromConfigPath($xmlPath);
                        $singleMailApi->setMagentoTransactionalTemplateVariables([
                            'customer' => $customer,
                            'back_url' => ''
                        ]);
                        $singleMailApi->addToEmailAddress($customerEmailAddress);
                        $singleMailApi->setFromEmail('general');
                        $singleMailApi->createAndSendMail();
                    }

                    if ( $customer->getSchrackS4sId() && $customer->getSchrackS4sId() > '' ) {
                        // a new customer with an existing s4s id must be a country change requested in s4s and executed in Dynos
                        // so notify s4s server that it's done:
                        Mage::helper('s4s')->notifyCountryChange($customer);
                    }
                } else {
                    // -------- UDATE OLD PROSPECT -------:

if ($debug) Mage::log('Address Update (customer found)', null, '/prospects/prospects_import.log');

                    $addressID = $systemContact->getDefaultBilling();
                    $address = Mage::getModel('customer/address')->load($addressID);

if ($debug) Mage::log($address, null, '/prospects/prospects_import.log');

                    if (is_object($address) && $address->getId()) {
                        // -------- UPDATE DEFAULT BILLING ADDRESS -------:
                        foreach ($addressData as $key => $value) {
                            $address->setData($key, $value);
                        }
                        $address->save();
                    } else {
                        // -------- CREATE DEFAULT BILLING ADDRESS (with dummy data or real data) -------:
                        if ($pseudoWwsCustomerID == 'PROS') {
                            $addressData['firstname']  = $protoProspect->getFirstName();
                            $addressData['lastname']   = $protoProspect->getLastName();
                            $addressData['street']     = $protoProspect->getStreet();
                            $addressData['postcode']   = $protoProspect->getZip();
                            $addressData['city']       = $protoProspect->getCity();
                            $addressData['country_id'] = $protoProspect->getCountry();
                            $addressData['fax']        = $protoProspect->getFaxCompany();
                            $addressData['telephone']  = $protoProspect->getPhoneCompany();
                        }

                        if ($pseudoWwsCustomerID == 'PROSLI') {
                            $addressData['firstname'] = $protoProspect->getFirstName();
                            $addressData['lastname']  = $protoProspect->getLastName();
                            if ($protoProspect->getStreet()) $addressData['street']          = $protoProspect->getStreet(); else $addressData['street']          = $pseudoWwsCustomerID;
                            if ($protoProspect->getCity()) $addressData['postcode']          = $protoProspect->getZip(); else $addressData['postcode']           = $pseudoWwsCustomerID;
                            if ($protoProspect->getCity()) $addressData['city']              = $protoProspect->getCity(); else $addressData['city']              = $pseudoWwsCustomerID;
                            if ($protoProspect->getCountry()) $addressData['country_id']     = $protoProspect->getCountry(); else $addressData['country_id']     = strtoupper(Mage::getStoreConfig('schrack/general/country'));
                            if ($protoProspect->getFaxCompany()) $addressData['fax']         = $protoProspect->getFaxCompany(); else $addressData['fax']         = $pseudoWwsCustomerID;
                            if ($protoProspect->getPhoneCompany()) $addressData['telephone'] = $protoProspect->getPhoneCompany(); else $addressData['telephone'] = $pseudoWwsCustomerID;
                        }

                        $address = Mage::getModel('customer/address');
                        $address->setData($addressData);
                        Mage::helper('schrackcustomer')->setupBillingAddress($address, $systemContact->getId());

                        $address->setCustomerId($systemContact->getId());
                        $address->setSchrackWwsAddressNumber(0);
                        $address->setSchrackType(1);

                        $address->save();

                        $systemContact->setData('default_billing', $address->getId());
                        $systemContact->setData('default_shipping', $address->getId());
                        $systemContact->save();
                    }
                }
            }
        } else {
            // Write error log:
            Mage::log($errors, null, '/prospects/prospect_err.log');
        }
    }


    public function createProspectProtobufMessage (Schracklive_SchrackCustomer_Model_Prospect $prospect, $delete = false) {
        $protoProspect = new ProspectMessage\Prospect();
        $this->mapMagentoToProtobuf($prospect, $protoProspect);
        $protoProspect->setDeleted($delete);

        if ($prospect->getName1() || $protoProspect->getSalutation()) {
            $companyPrefix = $this->companyPrefixMap[strtolower(Mage::getStoreConfig('schrack/general/country'))];
            $protoProspect->setSalutation($companyPrefix);
        }

        $sessionCustomerS4YId = '';
        if (Mage::getSingleton('customer/session')->getCustomer()) {
            $sessionCustomerS4YId  = Mage::getSingleton('customer/session')->getCustomer()->getSchrackS4yId();
        }
        if ($sessionCustomerS4YId) {
            $protoProspect->setModifiedBy($sessionCustomerS4YId);

            $strModificationAction = Mage::getSingleton('core/session')->getUserModificationAction();
            if ($strModificationAction == 'prospect checkout data completion') {
                $protoProspect->setModificationAction($strModificationAction);
                Mage::getSingleton('core/session')->setUserModificationAction('');
            }
        }

        $msg = new ProspectMessage();
        $msg->setProspect($protoProspect);

        $codec = new \DrSlump\Protobuf\Codec\Binary();
        $data = $msg->serialize($codec);
        return $data;
    }

    private function getEmailTemplateXmlPath ( $crmCreatedProspect ) {
        if ( $crmCreatedProspect ) {
            return 'schrack/customer/create_account/email_confirmation_template_prospect_from_crm';
        } else {
            return 'schrack/customer/create_account/email_confirmation_template_prospect_selfreg';
        }
    }

} 
