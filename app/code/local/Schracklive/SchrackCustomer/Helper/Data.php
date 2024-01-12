<?php

class Schracklive_SchrackCustomer_Helper_Data extends Mage_Customer_Helper_Data {

	const LOCAL_SYSTEM_EMAIL_PREFIX = 'shop';
	const REMOTE_SYSTEM_EMAIL_PREFIX = 'wws';
	const INACTIVE_EMAIL_PREFIX = 'inactive';
	const DELETED_EMAIL_PREFIX = 'deleted';
	const EMAIL_SEPARATOR = '+';
	const EMAIL_DOMAIN = 'live.schrack.com';

	public function formatPhone($country, $area, $phone, $extension) {
		$phone = (($country ? '+' : '').$country).' '.$area.'/'.$phone.(($extension !== '' ? '-' : '').$extension);
		return trim($phone);
	}

    public function getWarehouseCount() {
        $whs = Mage::helper('schrackshipping/pickup')->getWarehouses();
        return count($whs);
    }
    
	public function getWarehouseRadioOptions($wrapBefore, $wrapAfter) {
		foreach (Mage::helper('schrackshipping/pickup')->getWarehouses() as $warehouse) {
			echo $wrapBefore.'<input type="radio" class="marginR5" name="schrack_pickup" value="'.$warehouse->getId().'"'.
			($warehouse->getId() == $this->getCustomer()->getSchrackPickup() ? ' checked' : '').
			'>'.$warehouse->getName() . $wrapAfter;
		}
	}

	/**
	 * @param Schracklive_SchrackCustomer_Model_Customer $customer
	 * @return int
	 */
	public function getPickupWarehouseId(Schracklive_SchrackCustomer_Model_Customer $customer) {
		$warehouseId = $customer->getSchrackPickup();
		if (!$warehouseId) {
			$warehouseId = $this->getDefaultWarehouseIdForCustomer($customer);
		}
		return $warehouseId;
	}

	public function getDefaultWarehouseIdForCustomer(Schracklive_SchrackCustomer_Model_Customer $customer) {
		$warehouseId = null;
        /* DLA: branch module seems not to be used any longer ?!?
		$account = $customer->getAccount();
		if ($account) {
			$branchId = $account->getWwsBranchId();
			if ($branchId) {
				$branch = Mage::getModel('branch/branch')->loadByBranchId($branchId);
				if ($branch->getId()) {
					$warehouseId = $branch->getWarehouseId();
				}
			}
		}
         */
		if (!$warehouseId) {
			$warehouseId = Mage::getStoreConfig('schrack/general/warehouse');
		}
		return $warehouseId;
	}

	public function setupSystemContact(Schracklive_SchrackCustomer_Model_Customer $systemContact, Schracklive_Account_Model_Account $account) {
		$systemContact->setForceConfirmed(true); // never confirm a system contact

		$systemContact->setGroupId(Mage::getStoreConfig('schrack/shop/system_group'));
		$systemContact->setWebsiteId(Mage::getStoreConfig('schrack/shop/website'));
		$systemContact->setStoreId(Mage::getStoreConfig('schrack/shop/store'));
		$systemContact->setSchrackAclRoleId(Mage::helper('schrack/acl')->getSystemContactRoleId());

		$systemContact->setLastname($account->getName1());
		$systemContact->setMiddlename($account->getName2());
		$systemContact->setFirstname($account->getName3());
		$systemContact->setAccount($account);
		$systemContact->setData('schrack_wws_contact_number', -1);
		$systemContact->setEmail($this->getEmailForSystemContact($account));
		$systemContact->setGender(1);
	}

	public function getEmailForSystemContact($account) {
		$wwsCustomerId = $account->getWwsCustomerId();
		if ($wwsCustomerId) {
			return self::REMOTE_SYSTEM_EMAIL_PREFIX.self::EMAIL_SEPARATOR.$account->getWwsCustomerId().'@'.self::EMAIL_DOMAIN;
		} else {
			return self::LOCAL_SYSTEM_EMAIL_PREFIX.self::EMAIL_SEPARATOR.$account->getId().'@'.self::EMAIL_DOMAIN;
		}
	}

	public function activateContact(Schracklive_SchrackCustomer_Model_Customer $customer) {
		$customer->setGroupId(Mage::getStoreConfig('schrack/shop/contact_group'));
		if ($this->_hasSystemEmail($customer)) {
			$emails = explode(',', $customer->getSchrackEmails());
			$email = array_shift($emails);
			$customer->setEmail($email)
					->setSchrackEmails(join(',', $emails));
		}
		if (!$customer->getSchrackAclRoleId()) {
			$customer->setSchrackAclRoleId(Mage::helper('schrack/acl')->getDefaultRoleId());
		}
	}

	protected function _hasSystemEmail($customer) {
		return preg_match('/@'.self::EMAIL_DOMAIN.'$/', $customer->getEmail());
	}

	public function deactivateContact(Schracklive_SchrackCustomer_Model_Customer $customer) {
		if (!$this->_hasSystemEmail($customer)) {
			$emails = explode(',', $customer->getSchrackEmails());
			array_unshift($emails, $customer->getEmail());
			$customer->setEmail($this->_buildEmailForInactiveContact($customer->getSchrackWwsCustomerId(), $customer->getSchrackWwsContactNumber()))
				->setSchrackEmails(join(',', $emails));
		}
		$customer->setGroupId(Mage::getStoreConfig('schrack/shop/inactive_contact_group'))
				->setPassword('')
				->setSchrackAclRoleId(Mage::helper('schrack/acl')->getDefaultRoleId());
	}

	protected function _buildEmailForInactiveContact($wwsCustomerId, $wwsContactNumber) {
		return self::INACTIVE_EMAIL_PREFIX.self::EMAIL_SEPARATOR
				.$wwsCustomerId.self::EMAIL_SEPARATOR
				.$wwsContactNumber.'@'
				.self::EMAIL_DOMAIN;
	}

	public function deleteContact(Schracklive_SchrackCustomer_Model_Customer $customer) {
		if (!$this->_hasSystemEmail($customer)) {
			$emails = explode(',', $customer->getSchrackEmails());
			array_unshift($emails, $customer->getEmail());
			$customer->setEmail($this->_buildEmailForDeletedContact($customer->getSchrackWwsCustomerId(), $customer->getSchrackWwsContactNumber()))
				->setSchrackEmails(join(',', $emails));
		}
		$customer->setGroupId(Mage::getStoreConfig('schrack/shop/deleted_contact_group'))
				->setPassword('')
				->setSchrackAclRoleId(Mage::helper('schrack/acl')->getDefaultRoleId());
	}

	protected function _buildEmailForDeletedContact($wwsCustomerId, $wwsContactNumber) {
		return self::DELETED_EMAIL_PREFIX.self::EMAIL_SEPARATOR
				.$wwsCustomerId.self::EMAIL_SEPARATOR
				.$wwsContactNumber.'@'
				.self::EMAIL_DOMAIN;
	}

	public function setupBillingAddress(Schracklive_SchrackCustomer_Model_Address $address, $customerId) {
		$address->setCustomerId($customerId);
		$address->setSchrackWwsAddressNumber(0);
		$address->setSchrackType(1);
	}

	public function getCustomer() {
            if (empty($this->_customer)) {  // Nagarro added new code from 1.9.x core
                $this->_customer = Mage::getSingleton('customer/session')->getCustomer();
            }
            return $this->_customer;
	}   
    
    public function getLoginUrl() {
         return $this->_getUrl('customer/account/login');
    }

	public function sendOrderingOfferManuallyEmail ( $isReorder, $customer, $order, $pickup, $pickupAddressId, $shippingAddressId, $wwsError, $shopError, $exceptionText, $pos2changedSkuQtyMap, $customerReference, $log = false ) {
	    if ( $log ) Mage::log('begin sendOrderingOfferManuallyEmail()',null,$log);
		$block = Mage::getBlockSingleton('core/template');
		$block->setTemplate('catalog/product/email_offerorderrequest.phtml');
		$block->assign('isReorder', $isReorder);
		$block->assign('customer', $customer);
		$block->assign('order', $order);
		$block->assign('wwsError', $wwsError);
		$block->assign('shopError', $shopError);
		$block->assign('exceptionText', $exceptionText);
		$block->assign('pos2changedSkuQtyMap', $pos2changedSkuQtyMap);
		$block->assign('pickup',$pickup);
		$block->assign('pickupAddressId',$pickupAddressId);
		$block->assign('pickupAddressName',$pickup ? $stockHelper = Mage::helper('schrackcataloginventory/stock')->getStockByNumber($pickupAddressId)->getStockName() : '');
		$block->assign('shippingAddressId',$shippingAddressId);
		$block->assign('customerReference',$customerReference);
	    if ( $log ) Mage::log('sendOrderingOfferManuallyEmail() before toHtml()',null,$log);
		$html = $block->toHtml();
	    if ( $log ) Mage::log('sendOrderingOfferManuallyEmail() after toHtml()',null,$log);
		// $res = file_put_contents('/media/sf_vmexchange/dl.html',$html); // ###

		if( defined('OVERRIDE_EMAIL_TO') ) {
			$toArray = array(OVERRIDE_EMAIL_TO);
		} else {
			$toAddress = Mage::getStoreConfig('schrack/shop/manual_offer_revision');
			if ( ! isset($toAddress) || strlen(trim($toAddress)) < 1 ) {
				$toAddress = '$ADVISOR';
			}
			if ( $log ) Mage::log('sendOrderingOfferManuallyEmail() before replacePlaceholders(1)',null,$log);
			$toArray = $this->replaceEmailAddressPlaceholdersAndExpand($toAddress,$customer,$order,$log);
			if ( $log ) Mage::log('sendOrderingOfferManuallyEmail() after replacePlaceholders(1)',null,$log);
		}

		$ccAddress = Mage::getStoreConfig('schrack/shop/manual_offer_revision_cc');
		if ( isset($ccAddress) && strlen(trim($ccAddress)) > 0 ) {
			if ( $log ) Mage::log('sendOrderingOfferManuallyEmail() before replacePlaceholders(2)',null,$log);
			$ccArray = $this->replaceEmailAddressPlaceholdersAndExpand($ccAddress,$customer,$order);
			if ( $log ) Mage::log('sendOrderingOfferManuallyEmail() after replacePlaceholders(2)',null,$log);
		}

		if ( isset($toAddress) ) {
			$args = array('subject' => $this->__('Manual intervention for ordering offer') . ' ' . $order->getSchrackOfferNumber(),
				'to' => $toArray,
				'cc' => $ccArray,
				'bcc' => null,
				'body' => $html,
				'templateVars' => array()
			);
			$mailHelper = Mage::helper('wws/mailer');
			if ( $log ) Mage::log('sendOrderingOfferManuallyEmail() before send()',null,$log);
			if (Mage::getStoreConfig('schrack/general/platform') == 'LIVE') {
			    $mailHelper->send($args);
            } else {
                Mage::log($args, null, 'send_ordering_offer_manually_email.log');
            }
    	    if ( $log ) Mage::log('regular end sendOrderingOfferManuallyEmail()',null,$log);
		} else {
    	    if ( $log ) Mage::log('exceptional end sendOrderingOfferManuallyEmail()',null,$log);
			throw new Exception('no receiver for order request email given');
		}
	}

	public function replaceEmailAddressPlaceholdersAndExpand ( $addresses, $customer, $order = null, $log = false ) {
        if ( $log ) Mage::log('replacePlaceholders() 010',null,$log);
		if ( ! $addresses || strlen($addresses) < 1 ) {
			return false;
		}
        if ( $log ) Mage::log('replacePlaceholders() 020',null,$log);
		// a@b.c; $ADVISOR; $ORIGINATOR; $BRANCH[1=office@wasined.at,2=innendienst@schrack.com,3=niemand@schrack.at,4...13...99=nirwana@schrack.com]; nochwer@schrack.com
		$arAddressesOut = array();
		$arAddresses = explode(';',$addresses);
        if ( $log ) Mage::log('replacePlaceholders() 030',null,$log);
		foreach ( $arAddresses as $oneAddress ) {
            if ( $log ) Mage::log('replacePlaceholders() 040',null,$log);
			$oneAddress = trim($oneAddress);
			$oneAddressUC = strtoupper($oneAddress);
            if ( $log ) Mage::log('replacePlaceholders() 050',null,$log);
			switch ( $oneAddressUC ) {
				case '$ADVISOR' :
                    if ( $log ) Mage::log('replacePlaceholders() 060',null,$log);
					$arAddressesOut[] = $this->getAdvisorEmail($customer,$log);
					break;
				case '$ORIGINATOR' :
				    if ( ! $order ) {
                        if ( $log ) Mage::log('replacePlaceholders() 085',null,$log);
				        throw new Exception('Need order for email variable $ORIGINATOR');
                    }
                    if ( $log ) Mage::log('replacePlaceholders() 090',null,$log);
					$arAddressesOut[] = $order->getSchrackWwsOperatorMail();
                    if ( $log ) Mage::log('replacePlaceholders() 100',null,$log);
					// not supported yet
					break;
				default :
                    if ( $log ) Mage::log('replacePlaceholders() 110',null,$log);
					if ( substr($oneAddressUC,0,8) == '$BRANCH[' ) {
                        if ( $log ) Mage::log('replacePlaceholders() 120',null,$log);
						$branchEmail = $this->extractBranchEmail($oneAddress,$customer);
                        if ( $log ) Mage::log('replacePlaceholders() 130',null,$log);
						if ( $branchEmail ) {
                            if ( strtoupper($branchEmail) == '$ADVISOR' ) {
                                if ( $log ) Mage::log('replacePlaceholders() 135',null,$log);
                                $branchEmail = $this->getAdvisorEmail($customer,$log);
                            }
                            if ( $log ) Mage::log('replacePlaceholders() 130',null,$log);
							$arAddressesOut[] = $branchEmail;
						}
                        if ( $log ) Mage::log('replacePlaceholders() 140',null,$log);
					} else {
						// let as it is...
                        if ( $log ) Mage::log('replacePlaceholders() 150',null,$log);
						$arAddressesOut[] = $oneAddress;
                        if ( $log ) Mage::log('replacePlaceholders() 160',null,$log);
					}
					break;
			}
		}
        if ( $log ) Mage::log('replacePlaceholders() 170',null,$log);
		return $arAddressesOut;
	}

    private function getAdvisorEmail ( $customer, $log ) {
        if ( $log ) Mage::log('getAdvisorEmail() 000',null,$log);
        $advisor = $customer->getAdvisor();
        if ( $log ) Mage::log('getAdvisorEmail() 050',null,$log);
        if( ! is_object($advisor) ) {
            if ( $log ) Mage::log('getAdvisorEmail() 010',null,$log);
            throw new Exception('no advisor assigned for order request email given');
        }
        if ( $log ) Mage::log('getAdvisorEmail() 020',null,$log);
        return $advisor->getEmail();
    }

	private function extractBranchEmail ( $placeHolder, $customer ) {
		$account = $customer->getAccount();
		if( ! is_object($account) ) {
			throw new Exception('no account assigned for order request email given');
		}
		$branchID = $account->getWwsBranchId();
		if( ! $branchID || !  is_numeric($branchID) || intval($branchID) < 1 ) {
			throw new Exception('no valid branch id assigned for order request email given');
		}
		$len = strlen($placeHolder);
		if ( $placeHolder[$len - 1] != ']' ) {
			throw new Exception('syntax error in $BRANCH[...] placeholder');
		}
		$pairsStr = substr($placeHolder,8,$len - 9);
		$pairs = explode(',',$pairsStr);
		foreach ( $pairs as $pair ) {
			$vals = explode('=',$pair);
			if ( trim($vals[0]) == $branchID ) {
				return trim($vals[1]);
			}
		}
		// throw new Exception("Branch ID $branchID not found in placeholder $placeHolder");
		return false;
	}

	public function sendOrderWentWrongEmail ( $name, $email, $phone, $company, $customerNo, $country, $branch, $text, $advisor ) {
        $block = Mage::getBlockSingleton('core/template');
        $block->setTemplate('catalog/product/email_discontinuationinquiry.phtml');
        $block->assign('name', $name);
        $block->assign('email', $email);
        $block->assign('phone', $phone);
        $block->assign('text', $text);
        $block->assign('advisor', $advisor);
        $block->assign('company',$company);
        $block->assign('customerNo',$customerNo);
        $block->assign('country',$country);
        $block->assign('branch',$branch);
        $html = $block->toHtml();
        $mailHelper = Mage::helper('wws/mailer');
        $toAddress = $advisor->getEmail();
        if (isset($toAddress)) {
            $args = array('subject' => $this->__('Problem with ordering offer'),
                'to' => $toAddress,
                'cc' => null,
                'bcc' => null,
                'body' => $html,
                'templateVars' => array()
            );
            $mailHelper->send($args);
        } else {
            throw new Exception('no receiver for order request email given');
        }
    }

    /**
     * Retrieve confirmation URL for Email
     *
     * @param string $email
     * @return string
     */
    public function getEmailConfirmationUrl($email = null)
    {
        return $this->_getUrl('customer/account/confirmation', array('email' => htmlspecialchars($email)));
    }

    /**
     * we want the personalized default advisor, not shop-user@schrack* to appear in the request towards s4y,
     * but still the generic one to be shown towards typo
     * as a fallback, the normal default advisor is returned
     * @see ticket #2015021810000372
     */
    public function getPersonalizedDefaultAdvisor() {
        if ( Mage::getStoreConfig('schrack/shop/personalized_default_advisor') &&
             Mage::getStoreConfig('schrack/shop/personalized_default_advisor') != '') {
            return Mage::getStoreConfig('schrack/shop/personalized_default_advisor');
        }
        return Mage::getStoreConfig('schrack/shop/default_advisor');
    }


    /**
     * make sure that the loggedin customer is the advisor of the customer, probably needed for sudo again in the future...
     *
     * @param $loggedinCustomer
     * @param $customer
     * @return bool
     */
    public function checkIsAdvisor($loggedinCustomer, $customer) {
        $customers = Mage::getModel('schrackcustomer/customer')->loadByWwsCustomerId($customer->getSchrackWwsCustomerId());
        foreach ( $customers as $customer ) {
            if ( $loggedinCustomer->getSchrackUserPrincipalName() === $customer->getSchrackAdvisorPrincipalName() ) {
                return true;
            }
        }
        return false;
    }

    /**
     * is it possible, for this user, at this remote-addr, to act as a customer?
     *
     * @param $customer the logged-in-customer
     */
    public function mayActAsUser($customer) {
        return (int)$customer->getGroupId() === Schracklive_SchrackCustomer_Model_Customer::CUSTOMER_SUDO_GROUP_ID;
    }

    /**
     * Get the default url to which will be redirected after successfull login
     *
     * @return string
     */
    public function getDefaultAfterLoginUrl () {
        $fileName = 'default_page_after_login.txt';
        if ( file_exists($fileName) ) {
            $content = trim(file_get_contents($fileName));
            if ( strlen($content) > 0 ) {
                return $this->_getUrl($content);
            } else {
                $url = Mage::getBaseUrl();
                $urlParts = parse_url($url);
                return $urlParts['scheme'] . "://" . $urlParts['host'] . "/";
            }
        } else {
            return $this->_getUrl('customer/account');
        }
    }

    public function checkNewPasswordReturningErrorMessage ( $newPass, $confPass ) {
        if ( empty($newPass) || empty($confPass) ) {
            return $this->__('Password fields can\'t be empty.');
        }

        if ( $newPass != $confPass ) {
            return $this->__('Please make sure your passwords match.');
        }

        if ( strlen($newPass) < 8 || preg_match('~[0-9]~',$newPass) === 0 || preg_match('~[A-Za-z]~',$newPass) === 0 ) {
            return $this->__('The new password must have at least 8 characters and must contain at least one digit and one letter.');
        }

        return false;
    }

    public function rememberPasswordHash ( $email, $password ) {
        $hash = Mage::helper('core')->getHash(trim($password),Mage_Admin_Model_User::HASH_SALT_LENGTH);
        $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $sql = " INSERT INTO customer_temp_pwhash (email,pw_hash) VALUES (?,?)"
             . " ON DUPLICATE KEY UPDATE pw_hash = ?";
        $writeConnection->query($sql,array($email,$hash,$hash));
    }

    public function restoreRememberedPasswordHashIfExists ( $email ) {
        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql = "SELECT pw_hash FROM customer_temp_pwhash WHERE email = ?";
        $hash = $readConnection->fetchOne($sql,$email);
        if ( is_string($hash) && $hash > '' ) {
            $sql = " SELECT value_id FROM customer_entity_varchar attr"
                 . " INNER JOIN customer_entity cust ON attr.entity_id = cust.entity_id"
                 . " WHERE email = ?"
                 . " AND attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'password_hash');";
            $valueId = $readConnection->fetchOne($sql,$email);
            if ( ! $valueId ) {
                throw new Exception("No Password EAV field for User $email!");
            }
            $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $writeConnection->beginTransaction();
            try {
                $sql = "UPDATE customer_entity_varchar SET value = ? WHERE value_id = ?";
                $writeConnection->query($sql,array($hash,$valueId));
                $sql = "DELETE FROM customer_temp_pwhash WHERE email = ?";
                $writeConnection->query($sql,$email);
                $writeConnection->commit();
            } catch ( Exception $ex ) {
                $writeConnection->rollback();
                throw $ex;
            }
        }
    }

    public function getAccountOtherAdvisors() {
        $strMainAdvisorPrinicipalName = '';
        $strAdvisorsPrinicipalNames   = '';
        $arrAllAdvisorData = array();
        $principal = '';
        $roleDescription = '';


        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $wwsId = $this->getCustomer()->getSchrackWwsCustomerId();

        if ($wwsId) {
            $query = "SELECT advisor_principal_name FROM account WHERE wws_customer_id LIKE ?";
            $advisorRow = $readConnection->fetchOne($query, $wwsId);
        } else {
            $accountId = $this->getCustomer()->getSchrackAccountId();
            $query = "SELECT advisor_principal_name FROM account WHERE account_id = ?";
            $advisorRow = $readConnection->fetchOne($query, $accountId);
        }

        if ($advisorRow) {
            if(stristr($advisorRow, '/')) {
                $arrPrincipalUUID = explode('/', $advisorRow);
                $strMainAdvisorPrinicipalName = $arrPrincipalUUID[0];
            } else {
                $strMainAdvisorPrinicipalName = $advisorRow;
            }
            if ($strMainAdvisorPrinicipalName) {
                $mainAdvisor = Mage::getModel('customer/customer')->loadByUserPrincipalName($strMainAdvisorPrinicipalName);
                if ($mainAdvisor) {
                    $fullname = $mainAdvisor->getFirstname() . ' ' . $mainAdvisor->getLastname();
                    $arrAllAdvisorData['main_advisor_data']['fullname']      = $fullname;
                    $arrAllAdvisorData['main_advisor_data']['schrack_title'] = $mainAdvisor->getSchrackTitle();
                    $arrAllAdvisorData['main_advisor_data']['email']         = $mainAdvisor->getEmail();
                    $arrAllAdvisorData['main_advisor_data']['phone']         = $mainAdvisor->getSchrackTelephone();
                    $arrAllAdvisorData['main_advisor_data']['mobile']        = $mainAdvisor->getSchrackMobilePhone();
                    $arrAllAdvisorData['main_advisor_data']['fax']           = $mainAdvisor->getSchrackFax();
                    $arrAllAdvisorData['main_advisor_data']['foto']          = $mainAdvisor->getPhotoUrl('medium');
                }
            }
        }

        $query = "SELECT advisors_principal_names FROM account WHERE wws_customer_id LIKE ?";
        $strAdvisorsPrinicipalNames = $readConnection->fetchOne($query, $wwsId);
        $cleanedAdvisorRow = "";

        $falsePositive = false;
        if ($strAdvisorsPrinicipalNames) {
            $tmpArrAdvisors = explode(',', $strAdvisorsPrinicipalNames);
            Mage::log($tmpArrAdvisors, null, 'all_advisors_data.log');
            if (is_array($tmpArrAdvisors) && !empty($tmpArrAdvisors)) {
                foreach($tmpArrAdvisors as $index => $advisorRow) {
                    if($advisorRow) {
                        if(stristr($advisorRow, '|')) {
                            list($principal, $roleId, $roleDescription) = explode('|', $advisorRow);
                        } else {
                            $arrPrincipalUUID = explode('/', $advisorRow);
                            $principal = $arrPrincipalUUID[0];
                        }
                    }
                    if($principal) {
                        $advisor = Mage::getModel('customer/customer')
                                   ->loadByUserPrincipalName($principal);
                        if ($advisor && $advisor->getEmail()) {
                            // Do nothing!
                            if ($falsePositive == false) {
                                $cleanedAdvisorRow .= $advisorRow . ',';
                            } else {
                                $cleanedAdvisorRow .= ',' . $advisorRow . ',';
                                $falsePositive = false;
                            }
                        } else {
                            $cleanedAdvisorRow .= ' /' . $advisorRow;
                            $falsePositive = true;
                        }
                    }
                }

                $cleanedAdvisorRow = str_replace(',,', '', $cleanedAdvisorRow);
                $cleanedAdvisorRow = str_replace(', /', '/', $cleanedAdvisorRow);
                //Mage::log($cleanedAdvisorRow, null, 'all_advisors_data.log');

                $arrAdvisors = explode(',', $cleanedAdvisorRow);

                foreach($arrAdvisors as $index => $advisorRow) {
                    if($advisorRow) {
                        if(stristr($advisorRow, '|')) {
                            list($principal, $roleId, $roleDescription) = explode('|', $advisorRow);
                        } else {
                            $arrPrincipalUUID = explode('/', $advisorRow);
                            $principal = $arrPrincipalUUID[0];
                        }
                        if($principal) {
                            $advisor = Mage::getModel('customer/customer')
                                          ->loadByUserPrincipalName($principal);
                            if ($advisor && $advisor->getEmail()) {
                                $fullname = $advisor->getFirstname() . ' ' . $advisor->getLastname();
                                $arrAllAdvisorData['other_advisors_data'][$index]['fullname'] = $fullname;
                                $arrAllAdvisorData['other_advisors_data'][$index]['email']    = $advisor->getEmail();
                                $arrAllAdvisorData['other_advisors_data'][$index]['phone']    = $advisor->getSchrackTelephone();
                                $arrAllAdvisorData['other_advisors_data'][$index]['mobile']   = $advisor->getSchrackMobilePhone();
                                $arrAllAdvisorData['other_advisors_data'][$index]['fax']      = $advisor->getSchrackFax();
                                $arrAllAdvisorData['other_advisors_data'][$index]['foto']     = $advisor->getPhotoUrl('medium');
                            } else {
                                continue;
                            }
                        }
                        if($roleDescription) {
                            $arrAllAdvisorData['other_advisors_data'][$index]['schrack_title'] = $roleDescription;
                        } else {
                            if ($advisor && $advisor->getEmail()) {
                                $arrAllAdvisorData['other_advisors_data'][$index]['schrack_title'] = $advisor->getSchrackTitle();
                            }
                        }
                    }
                }
            }
        }

        //Mage::log($arrAllAdvisorData, null, 'all_advisors_data.log');

        return json_encode($arrAllAdvisorData);
    }
}
