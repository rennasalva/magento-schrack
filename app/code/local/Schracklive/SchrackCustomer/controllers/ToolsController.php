<?php

class Schracklive_SchrackCustomer_ToolsController extends Mage_Core_Controller_Front_Action {

    public function getCustomerDSGVOConfirmedAction () {
        if (!$this->getRequest()->isAjax()) {
            // die('ajax missing'); // Should not be communicated to foreigners (only internal use)
            die('');
        }

        $sessionCustomer  = Mage::getSingleton('customer/session')->getCustomer();
        $customerLoggedIn = Mage::getSingleton('customer/session')->isLoggedIn();
        $resultMessage = 'Fetch DSGVO Status: customer not DSGVO confirmed';
        $check1 = false;
        $check2 = false;

        if ($sessionCustomer && $customerLoggedIn) {
            $email = $sessionCustomer->getEmail();
            $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');

            if ($email) {
                // Double Check, if everything is okay:
                $query = "SELECT * FROM customer_entity WHERE schrack_confirmed_dsgvo = 1 AND email LIKE '" . $email . "'";
                $queryResult = $readConnection->query($query);

                if ($queryResult->rowCount() > 0) {
                    $check1 = true;
                }

                $query = "SELECT * FROM customer_dsgvo WHERE schrack_confirmed_dsgvo = 1 AND email LIKE '" . $email . "'";
                $queryResult = $readConnection->query($query);

                if ($queryResult->rowCount() > 0) {
                    $check2 = true;
                }

                // Override checks for schrack employees:
                if (stristr($email, "live.schrack.com")) {
                    $check1 = true;
                    $check2 = true;
                }
            }
        }

        if ($check1 && $check2) {
            $resultMessage = 'okay';
        }

        if (!$customerLoggedIn) {
            $resultMessage = 'customer not logged in';
        }

        echo json_encode(array('msg' => $resultMessage));
        die();
    }


    public function getCustomerUserTermsConfirmedAction () {
        if (!$this->getRequest()->isAjax()) {
            // die('ajax missing'); // Should not be communicated to foreigners (only internal use)
            die('');
        }

        $sessionCustomer  = Mage::getSingleton('customer/session')->getCustomer();
        $customerLoggedIn = Mage::getSingleton('customer/session')->isLoggedIn();
        $resultMessage = 'Fetch User Terms Status: customer not User Terms confirmed';
        $check1 = false;
        $check2 = false;

        if ($sessionCustomer && $customerLoggedIn) {
            $email = $sessionCustomer->getEmail();
            $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');

            if ($email) {
                // Getting current version:
                $query = "SELECT * FROM schrack_terms_of_use ORDER BY entity_id DESC LIMIT 1";
                $queryResult = $readConnection->query($query);

                if ($queryResult->rowCount() > 0) {
                    foreach ($queryResult as $recordset) {
                        $currentVersionContentHash = $recordset['content_hash'];
                    }
                }

                // First Check:
                $query = "SELECT * FROM customer_entity WHERE schrack_last_terms_confirmed = 1 AND email LIKE '" . $email . "'";
                //Mage::log($query, null, "terms_of_use.err.log");
                $queryResult = $readConnection->query($query);

                if ($queryResult->rowCount() > 0) {
                    $check1 = true;
                } else {
                    //Mage::log("Check 1 : customer " . $email . " has no valid confirmation status", null, "terms_of_use.err.log");
                }

                // Second Check:
                $query  = "SELECT client_terms_content_hash FROM schrack_terms_of_use_confirmation";
                $query .= " WHERE user_email LIKE '" . $email . "' ORDER BY terms_id DESC LIMIT 1";
                // Mage::log($query, null, "terms_of_use.err.log");
                $queryResult = $readConnection->query($query);

                if ($queryResult->rowCount() > 0) {
                    foreach ($queryResult as $recordset) {
                        $currentVersionConfirmdeContentHash = $recordset['client_terms_content_hash'];
                    }
                    if ($currentVersionContentHash == $currentVersionConfirmdeContentHash) {
                        $check2 = true;
                    } else {
                        $hashes = $currentVersionContentHash . " - " . $currentVersionConfirmdeContentHash;
                        //Mage::log("Check 2 Version Mismatch #1 => " . $hashes, null, "terms_of_use.err.log");
                    }
                }

                // Override checks for schrack employees:
                if (stristr($email, "live.schrack.com")) {
                    $check1 = true;
                    $check2 = true;
                }
            }
        }

        if ($check1 && $check2) {
            $resultMessage = 'okay';
        }

        // Check of user terms results in "okay", because it is not needed, if the module ist not activated in this country:
        if (intval(Mage::getStoreConfig('schrack/dsgvo/activateRegistrationCheckboxUserTerms')) != 1) {
            $resultMessage = 'okay';
        }

        if (!$customerLoggedIn) {
            $resultMessage = 'customer not logged in';
        }

        echo json_encode(array('msg' => $resultMessage));
        die();
    }


    // This function will be called from confirmation popup (-> Dynos Registrations or User Terms Registrations)
    public function setCustomerDSGVOConfirmedAction () {
        if (!$this->getRequest()->isAjax()) {
            // die('ajax missing'); // Should not be communicated to foreigners (only internal for use)
            die('');
        }

        $sessionCustomer  = Mage::getSingleton('customer/session')->getCustomer();
        $customerLoggedIn = Mage::getSingleton('customer/session')->isLoggedIn();
        $resultMessage = 'Set DSGVO Status : error';
        $check1 = false;
        $check2 = false;

        if ($sessionCustomer && $customerLoggedIn) {
            $email                                  = $sessionCustomer->getEmail();
            $confirmationText                       = $this->__("DSGVO Schrack Confirm Text");
            $confirmationAGBCheckboxText            = $this->__("Schrack AGB Checkbox Confirm Text"); // AGB Checkbox Text
            $confirmationDataProtectionCheckboxText = $this->__("Schrack DataProtection Checkbox Confirm Text");
            $confirmationDSGVOCheckboxText          = $this->__("Schrack DSGVO Checkbox Confirm Text");

            $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');

            $query  = "INSERT INTO customer_dsgvo SET email = '" . $email . "',";
            if (intval(Mage::getStoreConfig('schrack/dsgvo/activateRegistrationCheckboxDSGVO')) == 1) {
                $query .= " schrack_confirmed_dsgvo = 1,";
                $query .= " schrack_confirmed_dsgvo_confirm_text = '" . addslashes($confirmationText) . "',";
                $query .= " schrack_confirmed_dsgvo_confirm_checkboxtext = '" . addslashes($confirmationDataProtectionCheckboxText) . "',";
            } else {
                // PHU-2021-05-27 - Change: now DSGVO was merged into general data protection declaration confirmation:
                $query .= " schrack_confirmed_dsgvo = 1,";
                $query .= " schrack_confirmed_dsgvo_confirm_text = '" . 'n.a.' . "',";
                $query .= " schrack_confirmed_dsgvo_confirm_checkboxtext = '" . addslashes($confirmationDataProtectionCheckboxText) . "',";
            }
            // PHU-2021-05-27 - Change: AGB Confirmation only in checkout required:
            if (intval(Mage::getStoreConfig('schrack/dsgvo/activateRegistrationCheckboxAGB')) == 1) {
                $query .= " schrack_confirmed_agb = 1,";
            } else {
                $query .= " schrack_confirmed_agb = 0,";
            }
            $query .= " schrack_confirmed_agb_confirm_checkboxtext = '" . addslashes($confirmationAGBCheckboxText) . "',";
            if (intval(Mage::getStoreConfig('schrack/dsgvo/activateRegistrationCheckboxDataProtection')) == 1) {
                $query .= " schrack_confirmed_dataprotection = 1,";
            } else {
                $query .= " schrack_confirmed_dataprotection = 0,";
            }
            $query .= " schrack_confirmed_dataprotection_confirm_checkboxtext = '" . addslashes($confirmationDataProtectionCheckboxText) . "',";
            $query .= " schrack_confirmed_rightsinformation_notice = 'Layer Confirmation',";
            $query .= " schrack_confirmed_rightsinformation_date = '" . date('Y-m-d H:i:s') . "'";

            $writeConnection->query($query);

            $query = "UPDATE customer_entity SET schrack_confirmed_dsgvo = 1 WHERE email LIKE '" . $email . "'";

            $writeConnection->query($query);

            // Double Check, if everything is okay:
            $query = "SELECT * FROM customer_entity WHERE schrack_confirmed_dsgvo = 1 AND email LIKE '" . $email . "'";
            $queryResult = $readConnection->query($query);

            if ($queryResult->rowCount() > 0) {
                $check1 = true;
            }

            $query = "SELECT * FROM customer_dsgvo WHERE schrack_confirmed_dsgvo = 1 AND email LIKE '" . $email . "'";
            $queryResult = $readConnection->query($query);

            if ($queryResult->rowCount() > 0) {
                $check2 = true;
            }
        }

        if ($check1 && $check2) {
            $resultMessage = 'okay';
        }

        if (!$customerLoggedIn) {
            $resultMessage = 'customer not logged in';
        }

        echo json_encode(array('msg' => $resultMessage));
        die();
    }


    // This function will be called from confirmation popup (-> Dynos Registrations or User Terms Registrations)
    public function setCustomerUserTermsConfirmedAction () {
        if (!$this->getRequest()->isAjax()) {
            // die('ajax missing'); // Should not be communicated to foreigners (only internal for use)
            die('');
        }

        $sessionCustomer  = Mage::getSingleton('customer/session')->getCustomer();
        $customerLoggedIn = Mage::getSingleton('customer/session')->isLoggedIn();
        $resultMessage = 'Set User Terms Status : error';
        $check1 = false;
        $check2 = false;

        if ($sessionCustomer && $customerLoggedIn) {
            $email         = $sessionCustomer->getEmail();
            $requestIpAddressRemote = (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '');
            $requestIpAddress = ((isset($_SERVER['X_FORWARDED_FOR']) && $_SERVER['X_FORWARDED_FOR']) ? '/' . $_SERVER['X_FORWARDED_FOR'] : '');

            $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');

            // Getting current version:
            $query = "SELECT * FROM schrack_terms_of_use ORDER BY entity_id DESC LIMIT 1";
            $queryResult = $readConnection->query($query);

            if ($queryResult->rowCount() > 0) {
                foreach ($queryResult as $recordset) {
                    $termsId                   = $recordset['entity_id'];
                    $termsVersion              = $recordset['version'];
                    $currentVersionContentHash = $recordset['content_hash'];
                }
            }

            $query = "UPDATE customer_entity SET schrack_last_terms_confirmed = 1 WHERE email LIKE '" . $email . "'";
            $writeConnection->query($query);

            Mage::log($email . ' -> Set user-term state = 1 : from Header Popup', null, "terms_of_use_state.log");

            $query  = "SELECT client_terms_content_hash FROM schrack_terms_of_use_confirmation";
            $query .= " WHERE user_email LIKE '" . $email . "'";
            $query .= " AND terms_id = " . $termsId;
            //Mage::log('Gets customers latest terms of use confirmation: ' . $query, null, "terms_of_use_state.log");
            $queryResult = $readConnection->query($query);

            if ($queryResult->rowCount() === 0) {
                // Insert new entry -> schrack_terms_of_use_confirmation (history table)!!
                $query  = "INSERT INTO schrack_terms_of_use_confirmation SET user_email = '" . $email . "',";
                $query .= " customer_id = " . $sessionCustomer->getId() . ",";
                $query .= " terms_id = " . $termsId . ",";
                if ($termsVersion) {
                    $query .= " terms_version = '" . $termsVersion . "',";
                }
                $query .= " client_terms_content_hash = '" . $currentVersionContentHash . "',";
                $query .= " client_ip = '" . $requestIpAddress . "',";
                $query .= " client_ip_remote = '" . $requestIpAddressRemote . "',";
                $query .= " client_type = 'webshop',";
                $query .= " confirmed_at = '" . date("Y-m-d H:i:s") . "'";
                // Mage::log('Insert new terms of use confirmation: ' . $query, null, "terms_of_use_state.log");
                $writeConnection->query($query);
            }

            // First checkpoint:
            $query = "SELECT * FROM customer_entity WHERE schrack_last_terms_confirmed = 1 AND email LIKE '" . $email . "'";
            $queryResult = $readConnection->query($query);

            if ($queryResult->rowCount() > 0) {
                $check1 = true;
            }

            // Second Check:
            $query  = "SELECT client_terms_content_hash FROM schrack_terms_of_use_confirmation";
            $query .= " WHERE user_email LIKE '" . $email . "' ORDER BY terms_id DESC LIMIT 1";
            $queryResult = $readConnection->query($query);

            if ($queryResult->rowCount() > 0) {
                foreach ($queryResult as $recordset) {
                    $currentVersionConfirmdeContentHash = $recordset['client_terms_content_hash'];
                }
                if ($currentVersionContentHash == $currentVersionConfirmdeContentHash) {
                    $check2 = true;
                } else {
                    $hashes = $currentVersionContentHash . " - " . $currentVersionConfirmdeContentHash;
                    Mage::log("Check 2 Version Mismatch #2 => " . $hashes, null, "terms_of_use.err.log");
                }
            }
        }

        if ($check1 && $check2) {
            $resultMessage = 'okay';
        }

        if (!$customerLoggedIn) {
            $resultMessage = 'customer not logged in';
        }

        echo json_encode(array('msg' => $resultMessage));
        die();
    }


    public function getStatusIfCustomerLoggedInAction () {
        $resultMessage = 'getStatusIfCustomerLoggedIn: Customer not logged in';

        if (!$this->getRequest()->isAjax()) {
            // die('ajax missing'); // Should not be communicated to foreigners (only internal use)
            die('ajax missing');
        }

        $customerLoggedIn = Mage::getSingleton('customer/session')->isLoggedIn();

        if ($customerLoggedIn) {
            $resultMessage = 'okay&' . Mage::getSingleton('customer/session')->getCustomer()->getEmail();
        }

        echo json_encode(array('msg' => $resultMessage));
        die();
    }

}
