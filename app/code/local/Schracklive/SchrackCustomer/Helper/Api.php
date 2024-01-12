<?php

class Schracklive_SchrackCustomer_Helper_Api {

	const SEND_NO_MAIL = 0;
	const SEND_CONFIRMATION_MAIL = 1;
	const SEND_PASSWORD_MAIL = 2;

	protected $_attributes = array(
		'prefix',
		'firstname',
		'lastname',
		'email',
		'gender',
		'schrack_salutatory',
		'schrack_telephone',
		'schrack_fax',
		'schrack_mobile_phone',
		'schrack_crm_role_id',
		'schrack_department',
		'schrack_main_contact',
		'schrack_wws_address_number',
		'schrack_newsletter',
		'schrack_advisor_principal_name',
		'schrack_advisors_principal_names',
		'schrack_comments',
		'schrack_interests',
		'schrack_active',
		'schrack_emails',
		'schrack_confirmed',
		'schrack_mailinglist_types_csv',
        'schrack_s4y_id',
        'schrack_s4s_nickname',
        'schrack_s4s_school',
        'schrack_s4s_id'
	);

	public function replaceContact($wwsCustomerId, $wwsContactNumber, array $customerData) {
		// TODO: check for valid wws customer id

		$sendBackToS4Y = false;

		$customer = Mage::getModel('customer/customer')->loadByWwsContactNumber($wwsCustomerId, $wwsContactNumber);
        $contactIsActive    = $this->_isActive($customerData);

		// One-shot fix it
		if ( ! $customer->getId() ) {
		    // Only update customer, if customer is active:
            if ($contactIsActive) {
			    $customer = Mage::getModel('customer/customer')->loadByEmailAddress($wwsCustomerId, $customerData['email']);
			    $customer->setData('schrack_wws_contact_number', $wwsContactNumber);
            }
		}
		$this->_checkSchrackCustomer($customerData);
		$this->_checkContact($wwsCustomerId, $customerData);
		try {
			$mailAction = self::SEND_NO_MAIL;

			$this->_mapContactData($customer, $customerData);
			if ($customer->getId()) {
				// update
				foreach ($customerData as $key => $value) {
					$customer->setData($key, $value);
				}
				if ($this->_isActive($customerData)) {
					if ($customer->isInactiveContact() || $customer->isDeletedContact()) {
						$this->_activateCustomer($customer);
						if ( ! $this->_isConfirmed($customerData) ) {
							$mailAction = $this->_handleConfirmationAndSetPassword($customer, true);
						} else {
							$customer->setConfirmation('');
						}
					} else { // is active
						if ( $this->_isConfirmed($customerData) && $customer->getConfirmation() && $customer->getConfirmation() > '' ) {
							$customer->setConfirmation('');
						} else if ( ! $this->_isConfirmed($customerData) && ($customer->getConfirmation() == null || $customer->getConfirmation() == '') ) {
							Mage::log("Cannot change state from 'active' to 'invited'!");
							$sendBackToS4Y = true;
						}
					}
				} else {
					Mage::helper('schrackcustomer')->deactivateContact($customer);
				}
			} else {
				$this->_createCustomer($customer, $wwsCustomerId, $wwsContactNumber, $customerData);
				$mailAction = $this->_handleConfirmationAndSetPassword($customer, $contactIsActive);
			}

			if ( Mage::helper('schrackcore/model')->isModified($customer) ) {
				$customer->save();
				$this->_sendNewAccountEmail($customer, $mailAction);
				if ( $sendBackToS4Y ) {
					$account = $customer->getAccount();
					if ( ! $account ) {
						throw new Exception("No account for customer " . $customer->getEmail());
					}
					$account->setCrmStatus(Schracklive_Crm_Helper_Data::STATUS_ACCOUNT_PENDING);
					$account->save();
				}
			}
		} catch (Mage_Core_Exception $e) {
			if ($e->getCode() == Mage_Customer_Model_Customer::EXCEPTION_EMAIL_EXISTS) {
				throw new Mage_Api_Exception('exists', $e->getMessage());
			} else {
				Mage::logException($e);
				throw new Mage_Api_Exception('data_invalid', $e->getMessage());
			}
		}

		return $customer->getId();
	}

	public function deleteContact($wwsCustomerId, $wwsContactNumber) {
		$customer = Mage::getModel('customer/customer')->loadByWwsContactNumber($wwsCustomerId, $wwsContactNumber);

		if (!$customer->getId()) {
			// throw new Mage_Api_Exception('not_exists', "WWS customer id {$wwsCustomerId}, contact number {$wwsContactNumber} - cannot delete");
            // just create logfile entry intstead of exception:
            Mage::log("WWS customer id {$wwsCustomerId}, contact number {$wwsContactNumber} not existing - cannot delete contact");
		}

		try {
			$customer->delete();
		} catch (Mage_Core_Exception $e) {
			throw new Mage_Api_Exception('not_deleted', $e->getMessage());
		}

		return true;
	}

	protected function _isActive($customerData) {
		if (isset($customerData['schrack_active']) && $customerData['schrack_active']) {
			return true;
		}
		return false;
	}

	protected function _isConfirmed($customerData) {
		if (isset($customerData['schrack_confirmed']) && $customerData['schrack_confirmed']) {
			return true;
		}
		return false;
	}

	protected function _checkSchrackCustomer(array &$customerData) {
		// don't allow changes
		unset($customerData['website_id']);
		unset($customerData['store_id']);
		unset($customerData['group_id']);
	}

	protected function _checkContact($wwsCustomerId, &$customerData) {
	    if(is_string($wwsCustomerId)) {
            $account = Mage::getModel('account/account')->loadByWwsCustomerId($wwsCustomerId);
        } else {
		    $account = Mage::getModel('account/account')->loadByWwsCustomerId((string) $wwsCustomerId);
        }
		if (!$account->getId()) {
			throw new Mage_Api_Exception('data_invalid', 'Account not found by WWS customer id: '.$wwsCustomerId);
		}
		$customerData['schrack_account_id'] = $account->getId();

		// just for sanity
		unset($customerData['schrack_user_principal_name']);
		unset($customerData['schrack_wws_salesman_id']);
		unset($customerData['schrack_wws_branch_id']);
	}

	protected function _checkEmployee(array &$customerData) {
		// just for sanity
		unset($customerData['schrack_wws_customer_id']);
		unset($customerData['schrack_wws_contact_number']);
		unset($customerData['schrack_advisor_principal_name']);
		unset($customerData['schrack_advisors_principal_names']);
	}

	protected function _mapContactData(Schracklive_SchrackCustomer_Model_Customer $customer, array &$customerData) {
		foreach ($customerData as $key => $value) {
			if (!in_array($key, $this->_attributes)) {
				unset($customerData[$key]);
			}
		}
		if (isset($customerData['schrack_wws_address_number'])) {
			$address = $customer->getWwsAddress($customerData['schrack_wws_address_number']);
			if ($address) {
				$customerData['schrack_address_id'] = $address->getId();
			}
			unset($customerData['schrack_wws_address_number']);
		}
	}

	protected function _createCustomer($customer, $wwsCustomerId, $wwsContactNumber, array $customerData) {
		$customer->setData($customerData);
		$customer->setWebsiteId(Mage::getStoreConfig('schrack/shop/website'));
		$customer->setStoreId(Mage::getStoreConfig('schrack/shop/store'));
		$customer->setAccountByWwsCustomerId($wwsCustomerId);
		$customer->setSchrackWwsContactNumber($wwsContactNumber);

		if ($this->_isActive($customerData)) {
			$this->_activateCustomer($customer);
		} else {
			Mage::helper('schrackcustomer')->deactivateContact($customer);
		}
		if ( isset($customerData['schrack_s4s_id']) && $customerData['schrack_s4s_id'] > '' ) {
		    // a new customer with an existing s4s id must be a country change requested in s4s and executed in Dynos
            // so notify s4s server that it's done:
		    Mage::helper('s4s')->notifyCountryChange($customer);
        }
	}

	protected function _activateCustomer($customer) {
		Mage::helper('schrackcustomer')->activateContact($customer);
		$customer->setSchrackAclRoleId(Mage::helper('schrack/acl')->getAdminRoleId());
	}

	protected function _handleConfirmationAndSetPassword($customer, $isActive) {
		$action = self::SEND_NO_MAIL;
		if ($isActive) {
			if (Mage::getStoreConfig('schrack/customer/sendEmails') == 'link') {
				$action = self::SEND_CONFIRMATION_MAIL;
				$customer->setPassword($customer->generatePassword());
				$customer->setConfirmation($customer->getRandomConfirmationKey());
			} elseif (Mage::getStoreConfig('schrack/customer/sendEmails') == 'password') {
				$action = self::SEND_PASSWORD_MAIL;
				$customer->setPassword($customer->generatePassword());
				$customer->setConfirmation('');
			}
		}
		return $action;
	}

	protected function _sendNewAccountEmail($customer, $mailAction) {
		$backUrl = Mage::getUrl('customer/account/edit', array('_secure' => true));
		if ($mailAction == self::SEND_CONFIRMATION_MAIL) {
			$customer->sendNewAccountEmail('confirmation', $backUrl);
		} elseif ($mailAction == self::SEND_PASSWORD_MAIL) {
			$customer->sendNewAccountEmail('registered', $backUrl);
		}
	}

}
