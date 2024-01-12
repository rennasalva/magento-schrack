<?php

require_once 'MageDeveloper/TYPO3connect/Model/Customer/Api/V2.php';

class Schracklive_SchrackCustomer_Model_Customer_Api_V2 extends MageDeveloper_TYPO3connect_Model_Customer_Api_V2 {

    const ACCOUNT_TYPE_TOOL_USER = '18';

    private $_prospect = '';

    public function create($customerData) {
        if ($customerData->schrack_wws_customer_id ||
            $customerData->schrack_wws_contact_number ||
            $customerData->schrack_user_principal_name
        ) {
            $this->_fault('data_invalid', 'May not create employees or WWS contacts.');
        }
        return parent::create($customerData);
    }


    public function update($customerId, $customerData) {
        if ($customerData->schrack_wws_customer_id ||
            $customerData->schrack_wws_contact_number ||
            $customerData->schrack_user_principal_name
        ) {
            $this->_fault('data_invalid', 'May not update employees or WWS contacts.');
        }
        return parent::update($customerId, $customerData);
    }


    public function replaceContact($wwsCustomerid, $wwsContactNumber, $customerData) {
        return Mage::helper('schrackcustomer/api')->replaceContact($wwsCustomerid, $wwsContactNumber,
            get_object_vars($customerData));
    }


    public function deleteContact($wwsCustomerid, $wwsContactNumber) {
        return Mage::helper('schrackcustomer/api')->deleteContact($wwsCustomerid, $wwsContactNumber);
    }


    public function getWwsId ( $eMail ) {
        if ( ! isset($eMail) ) {
            $this->_fault('data_invalid', 'May not update employees or WWS contacts.');
        }
        $customer = Mage::getModel('customer/customer');
        $customer->loadByEmail($eMail);
        return $customer->getSchrackWwsCustomerId();
    }


    public function authenticateUser ( $eMail, $passWord, $requestType = null ) {
        $res = $this->authenticateUserV20($eMail,$passWord,$requestType);
        unset($res['pickupStockNo']);
        unset($res['deliveryStockNo']);
        return $res;
    }


    public function authenticateUserV20 ( $eMail, $passWord, $requestType = null ) {
        if ( (! isset($eMail) || empty($eMail)) && $requestType === 'get_datanorm' ) {
            $wwsId = Mage::getStoreConfig('schrack/datanorm/default_customer');
            if ( $wwsId && ! empty($wwsId) ) {
                $res = array(
                    'ok'    => true,
                    'acl'   => $wwsId."/*",
                    'wwsId' => $wwsId
                );
                return $res;
            }
        }
        $customer = Mage::getModel('customer/customer');
        $customer->loadByEmail($eMail);
        try {
            $ok = $customer->authenticate($eMail, $passWord);
        } catch (Exception $e) {
            Mage::logException($e);
            $ok = false;
        }
        $wwsId           = $ok ? $customer->getSchrackWwsCustomerId() : "";
        $acl             = $ok ? $wwsId."/*" : "";
        $pickupStockNo   = $ok ? $customer->getSchrackPickup() : 0;
        $deliveryStockNo = $ok ? Mage::helper('schrackcataloginventory/stock')->getLocalDeliveryStock()->getStockNumber() : 0;
        if ( $ok && $requestType && $requestType !== 'get_datanorm' ) {
            if ( preg_match('/@schrack\.com$/', $eMail) ) {
                $ok = true;
                $acl = '*/*';
            } else {
                $resource = Mage::getSingleton('core/resource');
                $readConnection = $resource->getConnection('core_read');
                $sql = 'select services_csv, other_customer_ids_csv from service_permissions where email = ?;';
                $rows = $readConnection->query($sql, array($customer->getEmail()));
                if ($rows->rowCount() === 0) {
                    $sql = 'select services_csv, other_customer_ids_csv from service_permissions where wws_customer_id = ? and email = \'\';';
                    $rows = $readConnection->query($sql, array($wwsId));
                }
                if ($rows->rowCount() === 0) {
                    $ok = false;
                    $acl = $wwsId = '';
                    $pickupStockNo = $deliveryStockNo = 0;
                } else {
                    $services = array();
                    $otherUsers = array();
                    foreach ($rows as $row) {
                        $services = explode(', ', $row['services_csv']);
                        $otherUsers = explode(', ', $row['other_customer_ids_csv']);
                        break;
                    }
                    if (!in_array($requestType, $services)) {
                        $ok = false;
                        $acl = $wwsId = '';
                    } else {
                        foreach ($otherUsers as $id) {
                            $acl .= ';' . $id . '/*';
                        }
                    }
                }
            }
        }
        $res = array(
            'ok'              => $ok,
            'acl'             => $acl,
            'wwsId'           => $wwsId,
            'pickupStockNo'   => $pickupStockNo,
            'deliveryStockNo' => $deliveryStockNo
        );
        return $res;
    }


    public function getLoginToken($email, $password) {
        $customer = Mage::getModel('customer/customer');
        $customer->loadByEmail($email);
        $ok = false;
        if ($customer && $customer->getId()) {
            $ok = $customer->authenticate($email, $password);
        }

        if (!$ok) {
            return array('ok' => false, 'token' => null);
        }

        $helper = Mage::helper('schrackcustomer/loginswitch');
        return array('ok' => true, 'token' => $helper->createToken($email));
    }


    public function findCountryByEmail($email) {
        $model = Mage::getModel('schrackcustomer/loginswitch');
        if ($model->findCountryByEmail($email)) {
            return $model->getCountryId();
        } else {
            return null;
        }
    }


    public function changeProspectToCustomer($email, $wwsCustomerID, $wwsContactNumber, $debugLog = '') {
        if (!$wwsContactNumber || $wwsContactNumber == 0 || $wwsContactNumber == '0') {
            Mage::log(date('Y-m-d H:i:s') . ': changeProspectToCustomer: no wws customer id given', null, '/prospects/prospect_err.log');
            return 'Wws Customer Id' . ' = ' . $wwsCustomerID;
        }
        if ('' == $email) {
            Mage::log(date('Y-m-d H:i:s') . ': changeProspectToCustomer: no email given', null, '/prospects/prospect_err.log');
            return 'No Email Given';
        }
        if ('' == $wwsContactNumber) {
            Mage::log(date('Y-m-d H:i:s') . ': changeProspectToCustomer: no WWS Contact Number given', null, '/prospects/prospect_err.log');
            return 'No WWS Contact Number Given';
        }
        $resultMessage = $this->checkFullProspectByMail($email, 'changeProspectToCustomer');
        if ('success' == $resultMessage) {
            // Check, if an prospect (customer-) object is available:
            if ($this->_prospect) {
                try {
                    // Check first, if prospect already got WWS-Customer-ID:
                    $possibleWWSCustomerID = $this->_prospect->getSchrackWwsCustomerId();
                    if ($possibleWWSCustomerID && $possibleWWSCustomerID != 'PROS' && $possibleWWSCustomerID != 'PROSLI') {
                        Mage::log(date('Y-m-d H:i:s') . ': changeProspectToCustomer: prospect already has some WWS Customer-ID: ' . $possibleWWSCustomerID, null, '/prospects/prospect_err.log');
                        return 'Prospect Already Has Some Wws Customer Id' . ': ' . $possibleWWSCustomerID;
                    }

                    // Check, if account has a different assignment not matching WWW-Customer ID to Prospect:
                    $account = Mage::getModel('account/account')->loadByWwsCustomerId($wwsCustomerID);
                    if ($account && $account->getId()) {
                        if ($account->getId() != $this->_prospect->getSchrackAccountId()) {
                            Mage::log(date('Y-m-d H:i:s') . ': changeProspectToCustomer: WWS Customer-ID ' . $wwsCustomerID . ' is already assigned to different account that is not assigned to prospect: Account-ID' . $account->getId(), null, '/prospects/prospect_err.log');
                            return 'Wws Customer Id Is Already Assigned To Different Account That Is Not Assigned To Prospect' . ' Account-ID: ' . $account->getId() . ' -- ' . 'Wws Customer Id' . ': ' . $wwsCustomerID;
                        }
                    } else {
                        $account = Mage::getModel('account/account')->load($this->_prospect->getSchrackAccountId(), 'account_id');
                        if ($account && $account->getId() == $this->_prospect->getSchrackAccountId()) {
                            $account->setWwsCustomerId($wwsCustomerID);
                            $account->save();
                        } else {
                            Mage::log(date('Y-m-d H:i:s') . ': changeProspectToCustomer: account not available for WWS Customer ID: ' . $wwsCustomerID, null, '/prospects/prospect_err.log');
                            return 'Account Not Available';
                        }
                    }

                    $resource = Mage::getSingleton('core/resource');
                    $writeConnection = $resource->getConnection('core_write');

                    $prospectCustomerEntityId = $this->_prospect->getId();
                    if ($debugLog) Mage::log(date('Y-m-d H:i:s') . ': changeProspectToCustomer: prospect->getId(): '.$prospectCustomerEntityId, null, '/prospects/prospect_convert.log');

                    // Update person:
                    if ($prospectCustomerEntityId) {
                        $query  = "UPDATE customer_entity SET schrack_customer_type = 'converted',";
                        $query .= " schrack_wws_customer_id = '" . $wwsCustomerID . "',";
                        $query .= " schrack_wws_contact_number = " . intval($wwsContactNumber) . ",";
                        $query .= " group_id = " . intval(Mage::getStoreConfig('schrack/shop/contact_group'));
                        $query .= " WHERE entity_id = " . intval($prospectCustomerEntityId);
                        $writeConnection->query($query);
                        if ($debugLog) Mage::log(date('Y-m-d H:i:s') . ': changeProspectToCustomer: writeConnection->query: '.$query, null, '/prospects/prospect_convert.log');

                        // Deletes wws_order_number, if customer has an active quote :
                        $query  = "UPDATE sales_flat_quote SET schrack_wws_order_number = ''";
                        $query .= " WHERE customer_id = " . intval($prospectCustomerEntityId) . " AND is_active = 1";
                        $writeConnection->query($query);
                        Mage::log($query . ' -> case #1.1', null, '/prospects/prospect_convert_delete_invalid_ordernumber_in_quote.log');
                    }

                    $prospect = Mage::getModel('customer/customer');
                    $prospect->setWebsiteId(Mage::app()->getWebsite()->getId());
                    $prospect->loadByEmail($email);
                    $prospect->setSchrackAclRoleId(Mage::helper('schrack/acl')->getAdminRoleId());
                    $prospect->save();

                    // Check system contact:
                    $systemContact = $account->getSystemContact();
                    if (!$systemContact) {
                        Mage::log(date('Y-m-d H:i:s') . ': changeProspectToCustomer: systemContact not available', null, '/prospects/prospect_err.log');
                        return 'SystemContact Not Available';
                    }

                    // Update system contact:
                    if ($systemContact->getId() && $systemContact->getSchrackWwsContactNumber() == -1) {
                        $query  = "UPDATE customer_entity SET schrack_customer_type = 'converted',";
                        $query .= " schrack_wws_customer_id = '" . $wwsCustomerID . "'";
                        $query .= " WHERE entity_id = " . intval($systemContact->getId());
                        $writeConnection->query($query);
                        if ($debugLog) Mage::log(date('Y-m-d H:i:s') . ': changeProspectToCustomer: systemContact->save ' . $systemContact->getId(), null, '/prospects/prospect_convert.log');
                    }
                }  catch (Exception $e) {
                    Mage::log($e->getMessage(), null, '/prospects/prospect_err.log');
                }
            } else {
                Mage::log(date('Y-m-d H:i:s') . ': changeProspectToCustomer: prospect not instantiated', null, '/prospects/prospect_err.log');
                return 'Prospect Not Instantiated';
            }
        } else {
            return $resultMessage;
        }

        // Write success to log:
        Mage::log(date('Y-m-d H:i:s') . ': [SUCCESS] changeProspectToCustomer: email -> ' . $email . '  /  WWS-Customer-ID ->' . $wwsCustomerID . '  / WWS-Contactnumber -> ' . $wwsContactNumber, null, '/prospects/prospect_convert.log');

        // Finally return succes message, if nothing failed before:
        return 'success';
    }


    public function assignProspectToCustomer($email, $wwsCustomerID, $wwsContactNumber, $debugLog = '', $notifyAdminsEmail = 'true') {
        $notifyAdminsEmailList = array();
        $companyname           = 'Companyname not found';
        $adminlist             = 'Admin Info Not Available';

        if ($notifyAdminsEmail == 'true' || $notifyAdminsEmail == ''  || $notifyAdminsEmail == '1'  || $notifyAdminsEmail == 1) {
            $notifyAdminsEmail = 'true';
        } else{
            $notifyAdminsEmail = 'false';
        }

        if ('' == $email) {
            Mage::log(date('Y-m-d H:i:s') . ': assignProspectToCustomer: no email given', null, '/prospects/prospect_err.log');
            return 'No Email Given';
        }
        if (!$wwsContactNumber || $wwsContactNumber == 0 || $wwsContactNumber == '0') {
            Mage::log(date('Y-m-d H:i:s') . ': changeProspectToCustomer: no wws customer id given', null, '/prospects/prospect_err.log');
            return 'Wws Customer Id' . ': ' . $wwsCustomerID;
        }
        if ('' == $wwsContactNumber) {
            Mage::log(date('Y-m-d H:i:s') . ': assignProspectToCustomer: no WWS Contact Number given', null, '/prospects/prospect_err.log');
            return 'No WWS Contact Number Given';
        }

        $resultMessage = $this->checkFullProspectByMail($email, 'assignProspectToCustomer');
        if ('success' == $resultMessage) {
            // Check, if an prospect (customer-) object is available:
            if ($this->_prospect) {
                try {
                    // Check first, if prospect already got WWS-Customer-ID:
                    $possibleWWSCustomerID = $this->_prospect->getSchrackWwsCustomerId();
                    if ($possibleWWSCustomerID && $possibleWWSCustomerID != 'PROS' && $possibleWWSCustomerID != 'PROSLI') {
                        Mage::log(date('Y-m-d H:i:s') . ': assignProspectToCustomer: prospect already has some WWS Customer-ID: ' . $possibleWWSCustomerID, null, '/prospects/prospect_err.log');
                        return 'Prospect Already Has Some Wws Customer Id' . ': ' . $possibleWWSCustomerID;
                    }

                    if ($debugLog) Mage::log('prospect->getSchrackAccountId() ---> ' . $this->_prospect->getSchrackAccountId(), null, '/prospects/prospect_convert.log');

                    if (!$this->_prospect->getSchrackAccountId() || !is_int(intval($this->_prospect->getSchrackAccountId())) || !intval($this->_prospect->getSchrackAccountId()) > 0) {
                        Mage::log(date('Y-m-d H:i:s') . ': assignProspectToCustomer: prospect has no schrack_account_id: ', null, '/prospects/prospect_err.log');
                        return 'Account Not Available' . ': ' . $email;
                    }

                    $resource = Mage::getSingleton('core/resource');
                    $writeConnection = $resource->getConnection('core_write');
                    $readConnection  = $resource->getConnection('core_read');

                    // Check, if account - loaded by wws customer id - is existing:
                    $account = Mage::getModel('account/account')->loadByWwsCustomerId($wwsCustomerID);

                    if (is_object($account) && $account->getId()) {
                    } else {
                        Mage::log(date('Y-m-d H:i:s') . ': assignProspectToCustomer: account not available for WWS Customer ID: ' . $wwsCustomerID, null, '/prospects/prospect_err.log');
                        return 'Account Not Available' . ': ' . $wwsCustomerID;
                    }

                    // This account is obsolete and must be removed:
                    $trashAccount = Mage::getModel('account/account')->load($this->_prospect->getSchrackAccountId(), 'account_id');

                    if (is_object($trashAccount) && $trashAccount->getId()) {
                        // This system contact is obsolete:
                        $trashSystemContact = $trashAccount->getSystemContact();
                        if (!$trashSystemContact) {
                            Mage::log(date('Y-m-d H:i:s') . ': assignProspectToCustomer: systemContact not available', null, '/prospects/prospect_err.log');
                            return 'SystemContact Not Available';
                        }

                        // Deleting account from former prospect:
                        $query  = "DELETE FROM account";
                        $query .= " WHERE account_id = " . intval($this->_prospect->getSchrackAccountId());
                        $writeConnection->query($query);

                        // This is the account, we need to use for the new contact (prospect):
                        $newAccountID = $account->getId();
                        $companyname = $account->getName1();
                    } else {
                        Mage::log(date('Y-m-d H:i:s') . ': assignProspectToCustomer: account not available for WWS Customer ID: ' . $wwsCustomerID, null, '/prospects/prospect_err.log');
                        return 'Account Not Available' . ': ' . $wwsCustomerID;
                    }

                    $prospectCustomerEntityId = $this->_prospect->getId();
                    if ($debugLog) Mage::log(date('Y-m-d H:i:s') . ': assignProspectToCustomer: prospect->getId(): '.$prospectCustomerEntityId, null, '/prospects/prospect_convert.log');

                    // Update person :
                    if ($prospectCustomerEntityId) {
                        $query  = "UPDATE customer_entity SET schrack_customer_type = 'converted',";
                        $query .= " schrack_wws_customer_id = '" . $wwsCustomerID . "',";
                        $query .= " schrack_wws_contact_number = " . intval($wwsContactNumber) . ",";
                        $query .= " schrack_account_id = " . intval($newAccountID) . ",";
                        $query .= " group_id = " . intval(Mage::getStoreConfig('schrack/shop/contact_group'));
                        $query .= " WHERE entity_id = " . intval($prospectCustomerEntityId);
                        $writeConnection->query($query);
                        Mage::log(date('Y-m-d H:i:s') . ': assignProspectToCustomer: writeConnection->query: '.$query, null, '/prospects/prospect_convert.log');

                        // Deletes wws_order_number, if customer has an active quote :
                        $query  = "UPDATE sales_flat_quote SET schrack_wws_order_number = ''";
                        $query .= " WHERE customer_id = " . intval($prospectCustomerEntityId) . " AND is_active = 1";
                        $writeConnection->query($query);
                        Mage::log($query . ' -> case #1.2', null, '/prospects/prospect_convert_delete_invalid_ordernumber_in_quote.log');
                    } else {
                        Mage::log(date('Y-m-d H:i:s') . ': assignProspectToCustomer: ' . $email . ' -- WWS-CID: ' . $wwsCustomerID . ' -- ContactNumber: ' . $wwsContactNumber, null, '/prospects/prospect_convert.log');
                    }

                    // Delete obsolete system contact:
                    if ($trashSystemContact->getId() && $trashSystemContact->getSchrackWwsContactNumber() == -1) {
                        $query  = "DELETE FROM customer_entity";
                        $query .= " WHERE entity_id = " . intval($trashSystemContact->getId());
                        $writeConnection->query($query);
                        if ($debugLog) Mage::log(date('Y-m-d H:i:s') . ': assignProspectToCustomer: systemContact->save ' . $trashSystemContact->getId(), null, '/prospects/prospect_convert.log');
                    } else {
                        if ($debugLog) Mage::log(date('Y-m-d H:i:s') . ': assignProspectToCustomer: account-ID: ' . $account->getId() . ' -> systemContact: ID -> (entity_id) -> ' . $trashSystemContact->getId(), null, '/prospects/prospect_convert.log');
                    }
                }  catch (Exception $e) {
                    Mage::log($e->getMessage(), null, '/prospects/prospect_err.log');
                }
            } else {
                Mage::log(date('Y-m-d H:i:s') . ': assignProspectToCustomer: prospect not instantiated', null, '/prospects/prospect_err.log');
                return 'Prospect Not Instantiated';
            }
        } else {
            return $resultMessage;
        }

        // Generate email list of previously existing shop-admins:
        $query  = "SELECT email FROM customer_entity";
        $query .= " WHERE schrack_wws_customer_id = '" . $wwsCustomerID . "'";
        $query .= " AND entity_id != ". $this->_prospect->getId();
        $query .= " AND schrack_wws_contact_number != -1";
        $query .= " AND is_active = 1";
        $query .= " AND email NOT LIKE 'inactive+%'";
        $query .= " AND email NOT LIKE 'deleted+%'";

        if ($debugLog) Mage::log(date('Y-m-d H:i:s') . ": assignProspectToCustomer: get admin list SQL: " . $query, null, "/prospects/prospect_convert.log");

        $results = $readConnection->fetchAll($query);

        foreach ($results as $recordset) {
            // Potential admin:
            $potentialAdminEmail = $recordset['email'];
            if ($debugLog) Mage::log(date('Y-m-d H:i:s') . ": assignProspectToCustomer: found admin : " . $recordset['email'], null, "/prospects/prospect_convert.log");
            // Check, if email belongs to admin of the affected account:
            $admin = Mage::getModel('customer/customer');
            $admin->setWebsiteId(Mage::app()->getWebsite()->getId());
            $admin->loadByEmail($potentialAdminEmail);
            if ($debugLog) Mage::log(date('Y-m-d H:i:s') . ": assignProspectToCustomer: found user role from potential admin : " . $admin->getSchrackAclRoleId() . ' -- default admin role from system: ' . Mage::helper('schrack/acl')->getAdminRoleId(), null, "/prospects/prospect_convert.log");
            if ($admin->getSchrackAclRoleId() == Mage::helper('schrack/acl')->getAdminRoleId()) {
                if ($debugLog) Mage::log(date('Y-m-d H:i:s') . ": assignProspectToCustomer: found user role from real admin : " . $admin->getSchrackAclRoleId() . ' -- default admin role from system: ' . Mage::helper('schrack/acl')->getAdminRoleId(), null, "/prospects/prospect_convert.log");
                $notifyAdminsEmailList[] = $potentialAdminEmail;
            }
        }

        // Send transactional email to all existing admins of this account:
        if ($notifyAdminsEmail == 'true' && !empty($notifyAdminsEmailList)) {
            $mageTranslation = Mage::getModel('core/translate')->setLocale(Mage::getStoreConfig('general/locale/code', Mage::getStoreConfig('schrack/shop/store')))
                                                               ->init('frontend', true);

            $adminlist = '';
            foreach ($notifyAdminsEmailList as $index => $adminEmail) {
                $admin = Mage::getModel('customer/customer');
                $admin->setWebsiteId(Mage::app()->getWebsite()->getId());
                $admin->loadByEmail($adminEmail);
                $adminName = $admin->getName();
                $prospectName = $this->_prospect->getName();
                $xmlPath = 'schrack/customer/notifyAdminConvertedProspectToContactEmailId';
                $transactionalMailVars = array('customer' => $admin, 'prospect' => $this->_prospect, 'prospectname' => $prospectName, 'prospectemail' => $email, 'back_url' => '');
                /** @var $singleMailApi Schracklive_SchrackSingleMail_Model_SingleMailApi */
                $singleMailApi = Mage::getModel('schracksinglemail/SingleMailApi');
                $singleMailApi->setMagentoTransactionalTemplateIDfromConfigPath($xmlPath);
                $singleMailApi->setMagentoTransactionalTemplateVariables($transactionalMailVars);
                $singleMailApi->addToEmailAddress($adminEmail,$adminName);
                $singleMailApi->setFromEmail('general');
                $singleMailApi->createAndSendMail();

                Mage::log(date('Y-m-d H:i:s') . ': notifyAdminEmail: mail successfully sent to EMAIL-ADDRESS = <<' . $adminEmail . '>> for converted prospect -> ' . $email, null, '/prospects/prospect_convert.log');

                // Buildin email list for converted prospect:
                $adminlist .= $mageTranslation->translate(array('Name')) . ': <b>' . $adminName . '</b><br>' . $mageTranslation->translate(array('Email')) . ': <b>' . $adminEmail . '</b><br><br>';
            }

            $adminlist = substr($adminlist, 0, -8);

            // Send transactional email to affected user (former prospect):
            $prospect = Mage::getModel('customer/customer');
            $prospect->setWebsiteId(Mage::app()->getWebsite()->getId());
            $prospect->loadByEmail($email);

            // Downgrade former prospect to projectant, because we found at least another admin:
            $prospect->setSchrackAclRoleId(Mage::helper('schrack/acl')->getProjectantRoleId());
            $prospect->save();

            $transactionalMailVars = array('customer' => $prospect, 'companyname' => $companyname, 'adminlist' => $adminlist, 'back_url' => '');
            $xmlPath = 'schrack/customer/notifyConvertedProspectRoleDowngradedEmailId';
            /** @var $singleMailApi Schracklive_SchrackSingleMail_Model_SingleMailApi */
            $singleMailApi = Mage::getModel('schracksinglemail/SingleMailApi');
            $singleMailApi->setMagentoTransactionalTemplateIDfromConfigPath($xmlPath);
            $singleMailApi->setMagentoTransactionalTemplateVariables($transactionalMailVars);
            $singleMailApi->addToEmailAddress($email);
            $singleMailApi->setFromEmail('general');
            $singleMailApi->createAndSendMail();

            Mage::log(date('Y-m-d H:i:s') . ': notifyEmailRoleDowngrade: mail successfully sent to EMAIL-ADDRESS <<' . $email . '>> (notifyAdminsEmail=' . $notifyAdminsEmail . ')', null, '/prospects/prospect_convert.log');
        } else {
            $prospect = Mage::getModel('customer/customer');
            $prospect->setWebsiteId(Mage::app()->getWebsite()->getId());
            $prospect->loadByEmail($email);
            $prospect->setSchrackAclRoleId(Mage::helper('schrack/acl')->getAdminRoleId());
            $prospect->save();

            Mage::log(date('Y-m-d H:i:s') . ': assignProspectToCustomer : Prosepect was converted to admin role with email <<' . $email . '>>', null, '/prospects/prospect_convert.log');
        }

        // Write success to log:
        Mage::log(date('Y-m-d H:i:s') . ': [SUCCESS] assignProspectToCustomer: email -> ' . $email . '  /  WWS-Customer-ID ->' . $wwsCustomerID . '  / WWS-Contactnumber -> ' . $wwsContactNumber . '  / notifyAdminsEmail-Param -> ' . $notifyAdminsEmail, null, '/prospects/prospect_convert.log');

        // Finally return succes message, if nothing failed before:
        return 'success';
    }


    public function replaceContactWithProspect($email, $wwsCustomerID, $wwsContactNumber, $debugLog = '') {
        if ('' == $email) {
            Mage::log(date('Y-m-d H:i:s') . ': replaceContactWithProspect: no email given', null, '/prospects/prospect_err.log');
            return 'No Email Given';
        }
        if (!$wwsCustomerID || $wwsCustomerID == 0 || $wwsCustomerID == '0' || $wwsCustomerID == null) {
            Mage::log(date('Y-m-d H:i:s') . ': changeProspectToCustomer: no wws-customer-id given - ' . $email, null, '/prospects/prospect_err.log');
            return 'Wws Customer Id' . ': ' .  $wwsCustomerID;
        }
        if (!$wwsContactNumber || $wwsContactNumber == 0 || $wwsContactNumber == '0' || $wwsContactNumber == null) {
            Mage::log(date('Y-m-d H:i:s') . ': replaceContactWithProspect: no wws-contact-number given - ' . $email, null, '/prospects/prospect_err.log');
            return 'No WWS Contact Number Given';
        }
        $resultMessage = $this->checkFullProspectByMail($email, 'replaceContactWithProspect');
        if ('success' == $resultMessage) {
            // Check, if an prospect (customer-) object is available:
            if ($this->_prospect) {
                try {
                    // Check first, if prospect already got WWS-Customer-ID:
                    $previouslyAssignedWWSCustomerID = $this->_prospect->getSchrackWwsCustomerId();
                    if ($previouslyAssignedWWSCustomerID && $previouslyAssignedWWSCustomerID != 'PROS' && $previouslyAssignedWWSCustomerID != 'PROSLI') {
                        Mage::log(date('Y-m-d H:i:s') . ': replaceContactWithProspect: prospect already has some WWS Customer-ID: ' . $previouslyAssignedWWSCustomerID, null, '/prospects/prospect_err.log');
                        return 'Prospect already has some WWS Customer-ID: ' . $previouslyAssignedWWSCustomerID;
                    }

                    $resource = Mage::getSingleton('core/resource');
                    $writeConnection = $resource->getConnection('core_write');
                    $readConnection  = $resource->getConnection('core_read');

                    // Check overall, if we have a person with the given contact-id, who is active or invited:
                    $query  = "SELECT entity_id, group_id, email FROM customer_entity";
                    $query .= " WHERE schrack_wws_customer_id = '" . $wwsCustomerID . "'";
                    $query .= " AND schrack_wws_contact_number = " . $wwsContactNumber;

                    Mage::log(date('Y-m-d H:i:s') . ': replaceContactWithProspect ' . $email . '(eMail) - ' . $wwsCustomerID .  '(WWS-CID) - ' . $wwsContactNumber . '(ContactNumber) : query-#1: ' . $query, null, '/prospects/prospect_convert.log');

                    $results = $readConnection->fetchAll($query);

                    $foundInvalidContact = false;

                    if (!is_array($results) || empty($results)) {
                        Mage::log(date('Y-m-d H:i:s') . ': replaceContactWithProspect ' . $email . '(eMail) - ' . $wwsCustomerID .  '(WWS-CID) - ' . $wwsContactNumber . '(ContactNumber) : Contact cannot be replaced by prospect, because there is no replaceable contact available', '/prospects/prospect_err.log');
                        return 'Prospect Cannot Replace Contact, Because There Is No Contact Available With Given Data' . ': ' . $email;
                    }

                    foreach ($results as $recordset) {
                        // Check, if target contact is ACTIVE --> If YES, then throw Exception or prepare error for finish execution:
                        if ($recordset['group_id'] == intval(Mage::getStoreConfig('schrack/shop/inactive_contact_group')) || $recordset['group_id'] == intval(Mage::getStoreConfig('schrack/shop/deleted_contact_group'))) {
                            $foundInvalidContact = false;
                        } else {
                            $foundInvalidContact = true;
                        }

                        $collectMageCustomerIDs[] = $recordset['entity_id'];
                        $collectMageCustomerEmail[] = $recordset['email'];
                    }

                    if ($foundInvalidContact == true) {
                        Mage::log(date('Y-m-d H:i:s') . ': replaceContactWithProspect: contact is still active and not deleted -> cannot replaced by prospect: ' . $recordset['email'], null, '/prospects/prospect_err.log');
                        return 'Contact Is Still Active And Not Deleted -> Cannot Replaced By Prospect' . ': ' . $recordset['email'];
                    }

                    // Check, if we have more than one contact for the given customer, with same contact-id:
                    if (count($collectMageCustomerIDs) > 1) {
                        Mage::log(date('Y-m-d H:i:s') . ': replaceContactWithProspect: contact number ' . $wwsContactNumber . ' is not unique: wws-customer-id -> ' . $wwsCustomerID, null, '/prospects/prospect_err.log');
                        return 'Wws Contact Number Is Not Unique For Wws Customer Id' . ': ' . $wwsCustomerID . ' ' . 'WWS Contact Number' . ': ' . $wwsContactNumber;
                    }

                    // Check, if target contact is INVITED --> If YES, then throw Exception or prepare error for finish execution:
                    $targetContact = Mage::getModel('customer/customer');
                    $targetContact->setWebsiteId(Mage::app()->getWebsite()->getId());
                    $targetContact->loadByEmail($collectMageCustomerEmail[0]);

                    /*
                    if ($targetContact->getConfirmation() && $targetContact->getConfirmation() > "") {
                        Mage::log(date('Y-m-d H:i:s') . ': replaceContactWithProspect: contact number ' . $wwsContactNumber . ' is invited, but not confirmed: wws-customer-id -> ' . $wwsCustomerID, null, '/prospects/prospect_err.log');
                        return 'contact number ' . $wwsContactNumber . ' is invited, but not confirmed: wws-customer-id -> ' . $wwsCustomerID;
                    }
                    */

                    // Check, if account has a different assignment not matching WWW-Customer ID to Prospect:
                    $account = Mage::getModel('account/account')->loadByWwsCustomerId($wwsCustomerID);
                    if (is_object($account) && $account->getId()) {

                        $systemContact = $account->getSystemContact();
                        if (!$systemContact) {
                            Mage::log(date('Y-m-d H:i:s') . ': replaceContactWithProspect: systemContact for change account assignment is not available', null, '/prospects/prospect_err.log');
                            return 'systemContact is not available';
                        }

                        $query1get  = "SELECT account_id FROM account";
                        $query1get .= " WHERE account_id = " . intval($this->_prospect->getSchrackAccountId());
                        $results = $readConnection->fetchAll($query1get);
                        foreach ($results as $recordset) {
                            $deleteAccountID = $recordset['account_id'];
                        }

                        $newAccountID = $account->getId();
                    } else {
                        Mage::log(date('Y-m-d H:i:s') . ': replaceContactWithProspect: account is not available for WWS Customer ID: ' . $wwsCustomerID, null, '/prospects/prospect_err.log');
                        return 'Account Not Available';
                    }

                    $prospectCustomerEntityId = $this->_prospect->getId();
                    Mage::log(date('Y-m-d H:i:s') . ': replaceContactWithProspect: prospect->getId(): ' . $prospectCustomerEntityId, null, '/prospects/prospect_convert.log');

                    // Get system contact from prospect:
                    $trashAccount = Mage::getModel('account/account')->load($this->_prospect->getSchrackAccountId(), 'account_id');
                    $trashSystemContact = $trashAccount->getSystemContact();

                    // Delete useless system contact from prospect account:
                    if ($trashSystemContact->getId() && $trashSystemContact->getSchrackWwsContactNumber() == -1) {
                        $deleteSystemContactEntityId = $trashSystemContact->getId();
                    } else {
                        Mage::log(date('Y-m-d H:i:s') . ': replaceContactWithProspect: eMail-> '  . $email . 'with account-ID-> ' . $account->getId() . ' has no systemContact', null, '/prospects/prospect_convert.log');
                        return 'SystemContact Not Available';
                    }

                    // Do allactions, if success is 100% secure:
                    if ($deleteAccountID && $newAccountID && $collectMageCustomerIDs[0] && $prospectCustomerEntityId && $deleteSystemContactEntityId) {

                        // 1. Delete useless prospect account:
                        $query1  = "DELETE FROM account";
                        $query1 .= " WHERE account_id = " . intval($this->_prospect->getSchrackAccountId());

                        Mage::log(date('Y-m-d H:i:s') . ': replaceContactWithProspect ' . $email . '(eMail) - ' . $wwsCustomerID .  '(WWS-CID) - ' . $wwsContactNumber . '(ContactNumber) : query-#2: ' . $query1, null, '/prospects/prospect_convert.log');

                        $result1 = $writeConnection->query($query1);
                        if ($result1->rowCount() > 0) {
                            Mage::log(date('Y-m-d H:i:s') . ' --> ' . $query1 . ' --> Query #2 successfully executed', null, '/prospects/prospect_convert.log');
                        } else {
                            Mage::log(date('Y-m-d H:i:s') . ' --> ' . $query1 . ' --> Query #2 NOT executed', null, '/prospects/prospect_convert.log');
                        }

                        // 2. Delete old useless (inactive/uninvited) deprecated current contact:
                        $query2  = "DELETE FROM customer_entity";
                        $query2 .= " WHERE entity_id = " . $collectMageCustomerIDs[0];

                        Mage::log(date('Y-m-d H:i:s') . ': replaceContactWithProspect ' . $email . '(eMail) - ' . $wwsCustomerID .  '(WWS-CID) - ' . $wwsContactNumber . '(ContactNumber) : query-#3: ' . $query2, null, '/prospects/prospect_convert.log');

                        $result2 = $writeConnection->query($query2);
                        if ($result2->rowCount() > 0) {
                            Mage::log(date('Y-m-d H:i:s') . ' --> ' . $query2 . ' --> Query #3 successfully executed', null, '/prospects/prospect_convert.log');
                        } else {
                            Mage::log(date('Y-m-d H:i:s') . ' --> ' . $query2 . ' --> Query #3 NOT executed', null, '/prospects/prospect_convert.log');
                        }

                        // 3. Update person from prospect to contact:
                        $query3  = "UPDATE customer_entity SET schrack_customer_type = 'converted',";
                        $query3 .= " schrack_wws_customer_id = '" . $wwsCustomerID . "',";
                        $query3 .= " schrack_wws_contact_number = " . intval($wwsContactNumber) . ",";
                        $query3 .= " schrack_account_id = " . intval($newAccountID) . ",";
                        $query3 .= " updated_at = '" . date('Y-m-d H:i:s') . "',";
                        $query3 .= " group_id = " . intval(Mage::getStoreConfig('schrack/shop/contact_group'));
                        $query3 .= " WHERE entity_id = " . intval($prospectCustomerEntityId);

                        Mage::log(date('Y-m-d H:i:s') . ': replaceContactWithProspect ' . $email . '(eMail) - ' . $wwsCustomerID .  '(WWS-CID) - ' . $wwsContactNumber . '(ContactNumber) : query-#4: ' . $query3, null, '/prospects/prospect_convert.log');

                        $result3 = $writeConnection->query($query3);
                        if ($result3->rowCount() > 0) {
                            Mage::log(date('Y-m-d H:i:s') . ' --> ' . $query3 . ' --> Query #4 successfully executed', null, '/prospects/prospect_convert.log');
                        } else {
                            Mage::log(date('Y-m-d H:i:s') . ' --> ' . $query3 . ' --> Query #4 NOT executed', null, '/prospects/prospect_convert.log');
                        }

                        // Deletes wws_order_number, if customer has an active quote :
                        $query  = "UPDATE sales_flat_quote SET schrack_wws_order_number = ''";
                        $query .= " WHERE customer_id = " . intval($prospectCustomerEntityId) . " AND is_active = 1";
                        $writeConnection->query($query);
                        Mage::log($query . ' -> case #1.3', null, '/prospects/prospect_convert_delete_invalid_ordernumber_in_quote.log');

                        // 4. Set old role to (new) replaced contact:
                        $convertedCustomer = Mage::getModel('customer/customer')->load($prospectCustomerEntityId);
                        // Get ACL role from old contact (and assign to new contact):
                        $oldAclRoleID = $targetContact->getSchrackAclRoleId();
                        $convertedCustomer->setSchrackAclRoleId($oldAclRoleID);
                        $convertedCustomer->save();


                        // 5. Delete useless prospect system contact (ATTENTION: never delete system contact with WWS ID):
                        $query4  = "DELETE FROM customer_entity";
                        $query4 .= " WHERE entity_id = " . intval($deleteSystemContactEntityId);
                        $query4 .= " AND schrack_wws_contact_number = -1";
                        $query4 .= " AND (schrack_wws_customer_id IS NULL OR schrack_wws_customer_id LIKE '')";

                        Mage::log(date('Y-m-d H:i:s') . ': replaceContactWithProspect ' . $email . '(eMail) - ' . $wwsCustomerID .  '(WWS-CID) - ' . $wwsContactNumber . '(ContactNumber) : query-#5: ' . $query4, null, '/prospects/prospect_convert.log');

                        $result4 = $writeConnection->query($query4);
                        if ($result4->rowCount() > 0) {
                            Mage::log(date('Y-m-d H:i:s') . ' --> ' . $query4 . ' --> Query #5 successfully executed', null, '/prospects/prospect_convert.log');
                        } else {
                            Mage::log(date('Y-m-d H:i:s') . ' --> ' . $query4 . ' --> Query #5 NOT executed', null, '/prospects/prospect_convert.log');
                        }
                    }
                }  catch (Exception $e) {
                    Mage::log($e->getMessage(), null, '/prospects/prospect_err.log');
                }
            } else {
                Mage::log(date('Y-m-d H:i:s') . ': replaceContactWithProspect: prospect not instantiated', null, '/prospects/prospect_err.log');
                return 'Prospect Not Instantiated';
            }
        } else {
            return $resultMessage;
        }

        // Write success to log:
        Mage::log(date('Y-m-d H:i:s') . ': [SUCCESS] replaceContactWithProspect: email -> ' . $email . '  /  WWS-Customer-ID ->' . $wwsCustomerID . '  / WWS-Contactnumber -> ' . $wwsContactNumber, null, '/prospects/prospect_convert.log');

        // Finally return succes message, if nothing failed before:
        return 'success';
    }


    public function checkFullProspectByMail($email, $function = 'requestFromS4Y') {
        // 1. Check if email could be found in system:
        $customer = Mage::getModel('customer/customer');
        $customer->loadByEmail($email);
        if (!$customer || $customer->getId() < 1 || !$customer->getId()) {
            Mage::log(date('Y-m-d H:i:s') . ': ' . $function . ': prospect email ' . $email . ' not found', null, '/prospects/prospect_err.log');
            return 'Prospect Email Not Found';
        }

        // 2. Check if email belongs to full prospect:
        if (!in_array($customer->getSchrackCustomerType(), array('full-prospect'))) {
            Mage::log(date('Y-m-d H:i:s') . ': ' . $function . ': email: ' . $email . ' is not assigned to full prospect', null, '/prospects/prospect_err.log');
            return 'Email Is Not Assigned To Full Prospect' . ': ' . $email;
        }

        // 3. Check if prospect belongs to expected group:
        if ($customer->getGroupId() != intval(Mage::getStoreConfig('schrack/shop/prospect_group'))) {
            Mage::log(date('Y-m-d H:i:s') . ': ' . $function . ': email: ' . $email . ' is not assigned to prospect group (wrong group_id)', null, '/prospects/prospect_err.log');
            return 'Email Is Not Assigned To Prospect Group (Wrong Group Id)' . ': ' . $email;
        }

        // Fundamental checks passed, so it can be assigned for class access:
        $this->_prospect = $customer;

        // Finally return succes message, if nothing failed before:
        return 'success';
    }


    public function assignNewS4YIdToContact ($email, $wwsCustomerID, $wwsContactNumber, $newS4YId, $debugLog = '') {
        if ('' == $email) {
            Mage::log(date('Y-m-d H:i:s') . ': assignNewS4YIdToContact: no email given, WWS-ID = ' . $wwsCustomerID . ' WWS-Contactnumber = ' .$wwsContactNumber, null, 'assigned_s4y_id_err.log');
            return 'No Email Given';
        }
        if ('' == $newS4YId) {
            Mage::log(date('Y-m-d H:i:s') . ': assignNewS4YIdToContact: no S4Y-ID given, WWS-ID = ' . $wwsCustomerID . ' WWS-Contactnumber = ' .$wwsContactNumber, null, 'assigned_s4y_id_err.log');
            return 'No S4Y-ID Given';
        }
        if (!$wwsCustomerID || $wwsCustomerID == 0 || $wwsCustomerID == '0' || $wwsCustomerID == null) {
            Mage::log(date('Y-m-d H:i:s') . ': assignNewS4YIdToContact: no wws-customer-id given - ' . $email, null, 'assigned_s4y_id_err.log');
            return 'No Wws Customer-Id Given';
        }
        if (!$wwsContactNumber || $wwsContactNumber == 0 || $wwsContactNumber == '0' || $wwsContactNumber == null) {
            Mage::log(date('Y-m-d H:i:s') . ': assignNewS4YIdToContact: no wws-contact-number given - ' . $email, null, 'assigned_s4y_id_err.log');
            return 'No WWS Contact Number Given';
        }

        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $readConnection  = $resource->getConnection('core_read');

        $query  = "SELECT * FROM customer_entity";
        $query .= " WHERE schrack_wws_customer_id LIKE '" . $wwsCustomerID . "'";
        $query .= " AND schrack_wws_contact_number = " . $wwsContactNumber . "";
        $results = $readConnection->fetchAll($query);

        $oldS4YId = '';

        if (is_array($results) && !empty($results)) {
            foreach ($results as $recordset) {
                $oldS4YId = $recordset['schrack_s4y_id'];
            }

            $updateQuery  = "UPDATE customer_entity";
            $updateQuery .= " SET schrack_s4y_id = '" . $newS4YId . "'";
            $updateQuery .= " WHERE schrack_wws_customer_id LIKE '" . $wwsCustomerID . "'";
            $updateQuery .= " AND schrack_wws_contact_number = " . $wwsContactNumber . "";

            $result4 = $writeConnection->query($updateQuery);
        } else {
            Mage::log(date('Y-m-d H:i:s') . 'Contact not found: WWS-ID = ' . $wwsCustomerID . ' ContactNumber = ' . $wwsContactNumber . ' E-Mail = ' . $email . ' old s4y_id = ' . $oldS4YId . ' ---> new s4y_id = ' . $newS4YId, null, 'assigned_s4y_id_err.log');
            return 'Contact Not Found';
        }

        if ($oldS4YId == '') {
            $oldS4YId = 'Not Defined';
        };

        Mage::log(date('Y-m-d H:i:s') . ' SUCCESS: WWS-ID = ' . $wwsCustomerID . ' ContactNumber = ' . $wwsContactNumber . ' E-Mail = ' . $email . ' old s4y_id = ' . $oldS4YId . ' ---> new s4y_id = ' . $newS4YId, null, 'assigned_s4y_id.log');

        return 'success';
    }

    public function resetPassword ( $email ) {
        $customer = Mage::getModel('customer/customer');
        $customer->loadByEmail($email);
        if ( ! $customer->getId() ) {
            return false;
        }
        $confirmation = $customer->getConfirmation();
        if ( $confirmation && $confirmation > '' ) {
            return false;
        }
        $templateID = Mage::getStoreConfig(Schracklive_SchrackCustomer_Model_Customer::XML_PATH_RESET_EMAIL_TEMPLATE);
        $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $writeConnection->beginTransaction();
        try {
            $customer->setSchrackChangepwToken($customer->getRandomConfirmationKey());
            $customer->save();
            $this->sendCustomerEmail($customer,$templateID);
            $writeConnection->commit();
            return true;
        } catch ( Exception $ex ) {
            $writeConnection->rollback();
            Mage::logException($ex);
            return false;
        }
    }

    public function registerToolUser ( $genderIsFemale, $firstName, $lastName, $email, $password ) {
        $userCountry = strtoupper($this->getCommonDbUserCountry($email));
        $shopCountry = strtoupper(Mage::getStoreConfig('schrack/general/country'));
        try {
            if ($userCountry) {
                // same country?
                if ($userCountry != $shopCountry) {
                    throw new Exception("Email already registered in (other) country $userCountry!",203);
                }
                // load customer record
                $prospect = Mage::getModel('customer/customer');
                $prospect->loadByEmail($email);
                if (!$prospect->getId()) {
                    // should not happen
                    throw new Exception("SNH: User email '$email' found in common DB but not in local DB!",901);
                }
                $confirmation = $prospect->getConfirmation();
                if ($confirmation && $confirmation > '') {
                    // confirmation missing
                    throw new Exception("Email already registered but not confirmed!",202);
                } else {
                    throw new Exception("Email already registered!",201);
                }
                // no way out when email already exists without exception...
            } else {
                $prospect = Mage::getModel('customer/customer');
                $prospect->loadByEmail($email);
                if ( $prospect->getId() ) {
                    // should not happen
                    throw new Exception("SNH: User email '$email' NOT found in common DB but found in local DB!",902);
                }

                if ( Mage::getStoreConfig('schrack/email/do_check_SD_registration_email') ) {
                    $helper = Mage::helper('schrack/email');
                    $ok = $helper->validateEmailAddress($email);
                    if (!$ok) {
                        // email not valid by Eyepin
                        throw new Exception("Email address not valid!", 102);
                    }
                }

                if ( ! is_string($password) || strlen($password) < 8 ) {
                    throw new Exception("Invalid password!",204);
                }

                // we use now the s4s settings, they must be good enought
                $advisorPrinzipal   = Mage::getStoreConfig('schrack/s4s/advisor_principal_name');
                $branch             = Mage::getStoreConfig('schrack/s4s/branch');
                $salesArea          = Mage::getStoreConfig('schrack/s4s/salesarea');

                if ( ! $advisorPrinzipal || $advisorPrinzipal == '' ) {
                    $advisorPrinzipal = Mage::helper('schrackcustomer')->getPersonalizedDefaultAdvisor();
                }

                $prosli = 'PROSLI';
                $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
                $writeConnection->beginTransaction();
                try {
                    $account = $this->createAccount($branch, $prosli, $advisorPrinzipal, $email, $salesArea);
                    $systemContact = $this->createSystemContact($account);
                    $this->createAddresses($firstName, $lastName, $prosli, $systemContact);
                    $prospect = $this->createProspect($systemContact, $account, $genderIsFemale, $firstName, $lastName,
                                                      $email, $password, $advisorPrinzipal, $writeConnection);
                    $this->sendProspectMessage($account,$prospect,$shopCountry);
                    $templateID = Mage::getStoreConfig('schrack/customer/create_account/email_confirmation_template_prospect_selfreg');
                    $this->sendCustomerEmail($prospect,$templateID);
                    $writeConnection->commit();
                } catch ( Exception $ex ) {
                    $writeConnection->rollback();
                    throw $ex;
                }
            }
        } catch ( Exception $ex ) {
            switch ( $ex->getCode() ) {
                case 101 :
                case 102 :
                case 201 :
                case 202 :
                case 203 :
                case 204 :
                    return [ 'success' => false, 'errorNumber' => $ex->getCode(), 'errorMessage' => $ex->getMessage() ];
                default :
                    Mage::logException($ex);
                    throw $ex;
            }
        }

        return [ 'success' => true, 'errorNumber' => 0, 'errorMessage' => '' ];
    }

    public function confirmTermsOfUse ( $email, $timeStamp, $ipAddress, $termsOfUseHash ) {
        try {
            $customer = Mage::getModel('customer/customer');
            $customer->loadByEmail($email);
            if ( ! $customer->getId() ) {
                throw new Exception("Email '$email' not found!",121);
            }

            // get mentioned terms:
            $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $sql = "SELECT * FROM schrack_terms_of_use WHERE content_hash = ?";
            $dbRes = $readConnection->fetchAll($sql,$termsOfUseHash);
            if ( count($dbRes) < 1 ) {
                throw new Exception("Invalid hash code!",151);
            }
            $currentTermsOfUse = reset($dbRes);

            // check timestamp:
            $now = time();
            $maxAgeTs = $now - 300;
            $ts = strtotime($timeStamp);

            $tsTXT = date(DATE_RFC822,$ts);
            $maxAgeTsTXT = date(DATE_RFC822,$maxAgeTs);

            if ( $ts < $maxAgeTs ) {
                throw new Exception("UTC timestamp too old!",152);
            } else if ( $ts > $now ) {
                throw new Exception("UTC timestamp in the future!",153);
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
                        'sd_client',
                        $email,
                        $timeStamp
                    ]);
                    $customer->setSchrackLastTermsConfirmed(1);
                    Mage::log($email . ' -> Set user-term state = 1 : from S4S API', null, "terms_of_use_state.log");
                    $customer->save();
                    $writeConnection->commit();
                } catch ( Exception $ex ) {
                    $writeConnection->rollback();
                    throw $ex;
                }
            }
        } catch ( Exception $ex ) {
            switch ( $ex->getCode() ) {
                case 121 :
                case 151 :
                case 152 :
                case 153 :
                    return [ 'success' => false, 'errorNumber' => $ex->getCode(), 'errorMessage' => $ex->getMessage() ];
                default :
                    Mage::logException($ex);
                    throw $ex;
            }
        }
        return [ 'success' => true, 'errorNumber' => 0, 'errorMessage' => '' ];
    }

    private function getCommonDbUserCountry ( $email ) {
        $readConn = Mage::getSingleton('core/resource')->getConnection('commondb_read');
        $sql = "SELECT country_id FROM magento_common.login_token WHERE email = ?;";
        $res = $readConn->fetchOne($sql,$email);
        return $res;
    }

    private function createConfirmationKey () {
        $s = "TOOLS_API_" . date(DATE_ATOM) . '_' . rand(0,100000);
        return md5($s);
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

    private function sendCustomerEmail ( $customer, $templateID ) {
        $fromEmail = Mage::getStoreConfig('trans_email/ident_general/email'); //fetch sender email
        $fromName = Mage::getStoreConfig('trans_email/ident_general/name'); //fetch sender name

        $this->_storeId = $customer->getSendemailStoreId();
        $url = Mage::helper('schrack/backend')->getFrontendUrl('customer/account/confirm/',
            ['id' => $customer->getId(), 'key' => $customer->getConfirmation()]);
        $vars = [
            'customer' => $customer,
            'confirmation_url' => $url
        ]; // for replacing the variables in email with data - only needed 4 register new email...
        $email = $customer->getEmail();
        $name = $customer->getName();

        $advisorPrinzipal = $customer->getSchrackAdvisorPrincipalName();
        $advisor = Schracklive_SchrackCustomer_Model_Customer::getAdvisorForPrincipalName($advisorPrinzipal);

        /** @var $singleMailApi Schracklive_SchrackSingleMail_Model_SingleMailApi */
        $singleMailApi = Mage::getModel('schracksinglemail/SingleMailApi');
        $singleMailApi->setStoreID($customer->getSendemailStoreId());
        $singleMailApi->setMagentoTransactionalTemplateID($templateID);
        $singleMailApi->setMagentoTransactionalTemplateVariables($vars);
        $singleMailApi->addToEmailAddress($email, $name);
        $singleMailApi->setFromEmail($fromEmail);
        $singleMailApi->setFromName($fromName);
        if ($advisor && $advisor->getId()) {
            $singleMailApi->setAdvisorEmail($advisor->getEmail());
        }
        $singleMailApi->createAndSendMail();
    }

    private function createAccount ( $branch, $prosli, $advisorPrinzipal, $email, $salesArea ) {
        $account = Mage::getModel('account/account');
        $account->setWwsCustomerId('');
        $account->setWwsBranchId($branch);
        $account->setName1($prosli);
        $account->setAdvisorPrincipalName($advisorPrinzipal);
        $account->setEmail($email);
        $account->setSalesArea($salesArea);
        $account->setAccountType(self::ACCOUNT_TYPE_TOOL_USER);
        $account->save();
        if (!$account->getId()) {
            throw new Exception("Account was not saved!");
        }
        return $account;
    }

    private function createSystemContact ( $account ) {
        $systemContact = Mage::getModel('customer/customer');
        Mage::helper('schrackcustomer')->setupSystemContact($systemContact, $account);
        $systemContact->setLastname($account->getName1());
        $systemContact->setMiddlename($account->getName2());
        $systemContact->setFirstname($account->getName3());
        $systemContact->save();
        if (!$systemContact->getId()) {
            throw new Exception("System contact was not saved!");
        }
        return $systemContact;
    }

    private function createAddresses ( $firstName, $lastName, $prosli, &$systemContact ) {
        $billingAddress = $this->createAddress($firstName, $lastName, $prosli, $systemContact, 0);
        $shippingAddress = $this->createAddress($firstName, $lastName, $prosli, $systemContact, 1);

        $systemContact->setData('default_billing', $billingAddress->getId());
        $systemContact->setData('default_shipping', $shippingAddress->getId());
        $systemContact->save();
    }

    private function createAddress ( $firstName, $lastName, $prosli, $systemContact, $addressNumber ) {
        $addressData = [];
        $addressData['firstname'] = $firstName;
        $addressData['lastname'] = $lastName;
        $addressData['street'] = $prosli;
        $addressData['postcode'] = $prosli;
        $addressData['city'] = $prosli;
        $addressData['country_id'] = strtoupper(Mage::getStoreConfig('schrack/general/country'));
        $addressData['telephone'] = '';
        $addressData['fax'] = '';
        $address = Mage::getModel('customer/address');
        $address->setData($addressData);
        Mage::helper('schrackcustomer')->setupBillingAddress($address, $systemContact->getId());
        $address->setCustomerId($systemContact->getId());
        $address->setSchrackWwsAddressNumber($addressNumber);
        $address->setSchrackType(1);
        $address->save();
        if (!$address->getId()) {
            throw new Exception("Billing address was not saved!");
        }
        return $address;
    }

    private function createProspect ( $systemContact, $account, $genderIsFemale, $firstName, $lastName,
                                      $email, $password, $advisorPrinzipal ) {
        $customer = Mage::getModel('customer/customer');
        $customer->setSystemContact($systemContact);
        $customer->setSchrackWwsCustomerId('');
        $customer->setSchrackAccountId($account->getId());
        if ($genderIsFemale !== null) {
            $customer->setGender($genderIsFemale ? 2 : 1);
        }
        $customer->setFirstname($firstName);
        $customer->setLastname($lastName);
        $customer->setEmail($email);
        $customer->setPassword($password);
        $customer->setConfirmation($this->createConfirmationKey());
        $customer->setSchrackCustomerType(Schracklive_SchrackCustomer_Model_Prospect::PROSPECT_TYPE_LIGHT);
        $customer->setSchrackAclRoleId(Mage::helper('schrack/acl')->getDefaultRoleId()); // customer
        $customer->setGroupId(Mage::getStoreConfig('schrack/shop/prospect_group')); // prospect
        $customer->setSchrackAdvisorPrincipalName($advisorPrinzipal);
        $customer->save();
        if (!$customer->getId()) {
            throw new Exception("Customer (contact) was not saved!");
        }
        $query = "UPDATE customer_entity SET schrack_customer_type = ? WHERE entity_id = ?";
        $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $writeConnection->query($query,[Schracklive_SchrackCustomer_Model_Prospect::PROSPECT_TYPE_LIGHT,$customer->getId()]);
        return $customer;
    }

    private function sendNotificationMailAboutProspectConversionRole ($senderEmailAddress = '', $receiverEmailAddress = '', $notificationIdentifier = 0) {
        if ($senderEmailAddress == '') {
            // Set default sender email address for notification mail:
            $senderEmailAddress = 'TODO';
        }

        if ($receiverEmailAddress == '') {
            // Set default receiver email address for notification mail:
            $receiverEmailAddress = 'TODO';
        }

        if ($notificationIdentifier == 0) {
            // Set default content for notification mail:
            $notificationIdentifier = 'TODO';
        }
    }

}
