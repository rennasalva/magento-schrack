<?php

class Schracklive_Crm_Model_Connector {

	const EXCEPTION_ERROR = 1;
	const EXCEPTION_SOAP_FAULT = 2;
	const EXCEPTION_FAILURE = 3;
	const EXCEPTION_EMAIL_NOT_UNIQUE = 4;
	const FAULT_CODE_EMAIL_NOT_UNIQUE = 3;

	/**
	 * @var Zend_Soap_Client
	 */
	protected $_client;

	/**
	 * @param $wwsCustomerId
	 * @throws Schracklive_Crm_Exception|Mage_Core_Exception
	 * @return array the account's attributes
	 */
	public function getAccount($wwsCustomerId) {
		$client = $this->_getSoapClient();
		try {
			$response = $client->get_account(
					Mage::helper('schrack')->getWwsCountry(), $wwsCustomerId
			);
		} catch (SoapFault $e) {
			throw Mage::exception('Schracklive_Crm', "SOAP failure for {$wwsCustomerId}: {$e->faultstring}", self::EXCEPTION_SOAP_FAULT
			);
		}
		return $response;
	}

	/**
	 * @param Schracklive_Account_Model_Account $account
	 * @return boolean true on success
	 */
	public function putNewAccount(Schracklive_Account_Model_Account $account) {
		return $this->_putAccount($account, true);
	}

	/**
	 * @param Schracklive_Account_Model_Account $account
	 * @return boolean true on success
	 */
	public function putAccount(Schracklive_Account_Model_Account $account) {
		return $this->_putAccount($account, false);
	}

	/**
	 * @param Schracklive_Account_Model_Account $account
	 * @param                                   $forceCreate
	 * @throws Schracklive_Crm_Exception|Mage_Core_Exception
	 * @return boolean true on success
	 */
	public function _putAccount(Schracklive_Account_Model_Account $account, $forceCreate) {
		if (!$account->getWwsCustomerId()) {
			// New self registration process: don'r send message, if prospect (full register/light):
			if (stristr($account->getCreatedBy(), 'protoProspectImport')) {
				return true;
			} else {
				throw Mage::exception('Schracklive_Crm', 'Account must have WWS customer id.', self::EXCEPTION_ERROR);
			}
		}
		$this->fillinAddressVals($account);

        if ($account->getSchrackTelephone()) {
            $number = $account->getSchrackTelephone();
            $number = preg_replace("/[^0-9]+/" , '', $number);
            $number = ltrim($number, '0');
            $number = '+' . $number;
            if ($number == '+') $number = '';
            $account->setSchrackTelephone($number);
        }
        if ($account->getSchrackFax()) {
            $number = $account->getSchrackFax();
            $number = preg_replace("/[^0-9]+/" , '', $number);
            $number = ltrim($number, '0');
            $number = '+' . $number;
            if ($number == '+') $number = '';
            $account->setSchrackFax($number);
        }

        //Mage::log($account, null, 'account_message_data.log');

        $msg = Mage::getModel('account/protoimport')->createProtobufMessage($account, $forceCreate || $this->_mustCreateAccount($account));
		if ( $msg ) {
			Mage::helper('account/protobuf')->sendMessage(Schracklive_Account_Helper_Protobuf::TYPE_ACCOUNT, $msg);
		}
        return true; // TODO
	}

	private function fillinAddressVals ( Schracklive_Account_Model_Account $account ) {
		$systemContact = $account->getSystemContact();
		if ( ! $systemContact ) {
			return false;
		}
		$addr = $systemContact->getDefaultBillingAddress();
		if ( ! $addr ) {
			return false;
		}
		$fields2copy = array('country_id', 'city', 'postcode', 'street');
		foreach ( $fields2copy as $field ) {
			$val = $addr->getData($field);
			$account->setData($field, $val);
		}
		return true;
	}

	protected function _mustCreateAccount(Schracklive_Account_Model_Account $account) {
		if (!$account->getId()) {
			return true; // new model
		}
		if (!$account->getOrigData('wws_customer_id')) {
			return true; // newly set WWS customer id
		}
		return false;
	}

	protected function _findBranchId(Schracklive_Account_Model_Account $account) {
		if ($account->getWwsBranchId()) {
			return $account->getWwsBranchId();
		}
		$branchId = '';
		$customer = $account->getContacts()->getFirstItem(); // simply choose the first customer
		if ($customer) {
			$branch = Mage::helper('branch')->findBranch();
			if ($branch) {
				$branchId = $branch->getId();
			}
		}
		return $branchId;
	}

	/**
	 * @param Schracklive_SchrackCustomer_Model_Customer $origCustomer
	 * @return int the customer's contact number
	 * @throws Mage_Core_Exception
	 */
	public function putCustomer ( Schracklive_SchrackCustomer_Model_Customer $origCustomer, $delete = false, $force = false  ) {
		if (!$origCustomer->isRealContact() && !$origCustomer->isProspect()) {
			throw Mage::exception('Schracklive_Crm', 'Argument #1 must be a contact or prospect.', self::EXCEPTION_ERROR);
		}

		// load model with all eav attributes from disk and apply current attributes of passed in model
		/**
		 * @var $customer Schracklive_SchrackCustomer_Model_Customer
		 */
		$customer = Mage::getModel('customer/customer')->load($origCustomer->getId());
		$customer->addData($origCustomer->getData());

		$account = $customer->getAccount();
		if ( ! $account ) {
			throw new Exception("No account for customer");
		}
		if ( ! $account->getWwsCustomerId() || $account->getCrmStatus() == Schracklive_Crm_Helper_Data::STATUS_ACCOUNT_PENDING ) {
			return false; // should not be transferred before account.
		}
		$dirty = false;
		if ( ! $customer->getSchrackWwsCustomerId() ) {
			$customer->setSchrackWwsCustomerId($account->getWwsCustomerId());
			$dirty = true;
		}
		if ( $customer->getSchrackWwsContactNumber() == 0 ) {
			$customer->setSchrackWwsContactNumber(Schracklive_SchrackCustomer_Model_Customer::NO_CONTACT_NUMBER);
			$dirty = true;
		}
		if ( $customer->isProspect() ) {
			$customer->setGroupId(Mage::getStoreConfig('schrack/shop/contact_group'));
			$dirty = true;
		}
		if ( $dirty && $customer->getId() ) {
			// avoid recursion with save() and oberserver()!
			$conn = Mage::getSingleton('core/resource')->getConnection('core_write');
			$sql = "UPDATE customer_entity SET schrack_wws_customer_id = "     . $customer->getSchrackWwsCustomerId()
				 .                           ", schrack_wws_contact_number = " . $customer->getSchrackWwsContactNumber()
				 .                           ", group_id = "                   . $customer->getGroupId()
				 . " WHERE entity_id = " . $customer->getId() . ";";
			$conn->query($sql);
		}

        if ($customer->getSchrackTelephone()) {
            $number = $customer->getSchrackTelephone();
            $number = preg_replace("/[^0-9]+/" , '', $number);
            $number = ltrim($number, '0');
            $number = '+' . $number;
            if ($number == '+') $number = '';
            $customer->setSchrackTelephone($number);
        }
        if ($customer->getSchrackFax()) {
            $number = $customer->getSchrackFax();
            $number = preg_replace("/[^0-9]+/" , '', $number);
            $number = ltrim($number, '0');
            $number = '+' . $number;
            if ($number == '+') $number = '';
            $customer->setSchrackFax($number);
        }
        if ($customer->getSchrackMobilePhone()) {
            $number = $customer->getSchrackMobilePhone();
            $number = preg_replace("/[^0-9]+/" , '', $number);
            $number = ltrim($number, '0');
            $number = '+' . $number;
            if ($number == '+') $number = '';
            $customer->setSchrackMobilePhone($number);
        }
        if ($customer->getTelephoneCompany()) {
            $number = $customer->getTelephoneCompany();
            $number = preg_replace("/[^0-9]+/" , '', $number);
            $number = ltrim($number, '0');
            $number = '+' . $number;
            if ($number == '+') $number = '';
            $customer->setTelephoneCompany($number);
        }

        //Mage::log($customer, null, 'contact_message_data.log');

		$msg = Mage::getModel('schrackcustomer/protoimport')->createSystemContactProtobufMessage($customer, $delete, $force);
		if ( $msg ) {
			Mage::helper('account/protobuf')->sendMessage(Schracklive_Account_Helper_Protobuf::TYPE_CONTACT, $msg);
		}

		return $customer->getSchrackWwsContactNumber();
	}


	/**
	 * @param array $prospectData
	 * @return int the customer's contact number
	 * @throws Mage_Core_Exception
	 */
	public function putProspect (array $prospectData, $delete = false) {
		$prospect = Mage::getModel('customer/prospect');

		if (isset($prospectData['schrack_telephone']) && $prospectData['schrack_telephone']) {
            $number = $prospectData['schrack_telephone'];
            $number = preg_replace("/[^0-9]+/" , '', $number);
            $number = ltrim($number, '0');
            $number = '+' . $number;
            if ($number == '+') $number = '';
            $prospectData['schrack_telephone'] = $number;
		}
        if (isset($prospectData['schrack_fax']) && $prospectData['schrack_fax']) {
            $number = $prospectData['schrack_fax'];
            $number = preg_replace("/[^0-9]+/" , '', $number);
            $number = ltrim($number, '0');
            $number = '+' . $number;
            if ($number == '+') $number = '';
            $prospectData['schrack_fax'] = $number;
        }
        if (isset($prospectData['schrack_mobile_phone']) && $prospectData['schrack_mobile_phone']) {
            $number = $prospectData['schrack_mobile_phone'];
            $number = preg_replace("/[^0-9]+/" , '', $number);
            $number = ltrim($number, '0');
            $number = '+' . $number;
            if ($number == '+') $number = '';
            $prospectData['schrack_mobile_phone'] = $number;
        }
        if (isset($prospectData['telephone_company']) && $prospectData['telephone_company']) {
            $number = $prospectData['telephone_company'];
            $number = preg_replace("/[^0-9]+/" , '', $number);
            $number = ltrim($number, '0');
            $number = '+' . $number;
            if ($number == '+') $number = '';
            $prospectData['telephone_company'] = $number;
        }

		$prospect->addData($prospectData);

        //Mage::log($prospectData, null, 'prospect_message_data.log');

		$msg = Mage::getModel('schrackcustomer/prospect_protoimport')->createProspectProtobufMessage($prospect, $delete);
		if ( $msg ) {
			Mage::helper('account/protobuf')->sendMessage(Schracklive_Account_Helper_Protobuf::TYPE_PROSPECT, $msg);
		}

		return $prospect;
	}


	protected function _mapGender($gender) {
		$crmGender = 0;
		if ($gender == 1) {
			$crmGender = 'M';
		} elseif ($gender == 2) {
			$crmGender = 'W';
		}
		return $crmGender;
	}

	protected function _getState(Schracklive_SchrackCustomer_Model_Customer $customer) {
		if ($customer->isContact() || $customer->isProspect()) {
			return 'active';
		} elseif ($customer->isInactiveContact()) {
			// creating exception to get and log stacktrace for finding out why customer gets inactive
			try {
				throw new Schracklive_Crm_Exception("Customer $customer->getSchrackWwsCustomerId() Contact $customer->getSchrackWwsContactNumber() is set inactive");
			} catch ( Schracklive_Crm_Exception $ex ) {
				Mage::logException($ex);
			}
			return 'inactive';
		} elseif ($customer->isDeletedContact()) {
			return 'deleted';
		}
		throw Mage::exception('Schracklive_Crm', 'Unexpected contact type.');
	}

	protected function _isInactive(Schracklive_SchrackCustomer_Model_Customer $customer) {
		return $customer->isInactiveContact();
	}

	/**
	 * @param Schracklive_SchrackCustomer_Model_Address $address
	 * @throws Schracklive_Crm_Exception|Mage_Core_Exception
	 * @return int the addresses's address number
	 */
	public function putAddress(Schracklive_SchrackCustomer_Model_Address $address, $delete = false) {
		$addressNumber = $address->getSchrackWwsAddressNumber();
		if ( isset($addressNumber) && intval($addressNumber) === 0 ) { // never send billing address
			return;
		}
		if ( ! isset($addressNumber) ) {
			$addressNumber = Schracklive_SchrackCustomer_Model_Address::NO_ADDRESS_NUMBER;
			$address->setSchrackWwsAddressNumber($addressNumber);
		}

		$customer = $address->getCustomer();
		if (!$customer) {
			throw Mage::exception('Schracklive_Crm', $this->_buildBeginOfExceptionMessage('Address', $address).' has no customer');
		}

		// New self registration process: don'r send message, if prospect (full register/light):
		if (stristr($customer->getSchrackWwsCustomerId(), 'PROS')) {
			return false;
		}

        if ($address->getTelephone()) {
            $number = $address->getTelephone();
            $number = preg_replace("/[^0-9]+/" , '', $number);
            $number = ltrim($number, '0');
            $number = '+' . $number;
            if ($number == '+') $number = '';
            $address->setTelephone($number);
        }
        if ($address->getFax()) {
            $number = $address->getFax();
            $number = preg_replace("/[^0-9]+/" , '', $number);
            $number = ltrim($number, '0');
            $number = '+' . $number;
            if ($number == '+') $number = '';
            $address->setFax($number);
        }
        if ($address->getSchrackAdditionalPhone()) {
            $number = $address->getSchrackAdditionalPhone();
            $number = preg_replace("/[^0-9]+/" , '', $number);
            $number = ltrim($number, '0');
            $number = '+' . $number;
            if ($number == '+') $number = '';
            $address->setSchrackAdditionalPhone($number);
        }

        //Mage::log($address, null, 'address_message_data.log');

        $msg = Mage::getModel('schrackcustomer/address_protoimport')->createAddressProtobufMessage($customer,$address,$delete);
		if ( $msg ) {
			Mage::helper('account/protobuf')->sendMessage(Schracklive_Account_Helper_Protobuf::TYPE_ADDRESS, $msg);
		}
		return $addressNumber;
	}

	public function deleteCustomer(Schracklive_SchrackCustomer_Model_Customer $customer) {
		if (!$customer->isRealContact()) {
			throw Mage::exception('Schracklive_Crm', 'Argument #1 must be a contact.', self::EXCEPTION_ERROR);
		}
		if ( $customer->getData('avoidSendDelete') ) {
			$email = $customer->getEmail();
			Mage::log("Avoiding send delete for contact $email");
			return true;
		}
		return $this->putCustomer($customer,true);
	}

	public function deleteAddress(Schracklive_SchrackCustomer_Model_Address $address) {
		return $this->putAddress($address,true);
	}

	/**
	 * @return Zend_Soap_Client
	 */
	protected function _getSoapClient() {
		if (!$this->_client) {
			$options = array(
				'schrack_system' => 'crm',
			);
			if (Mage::getStoreConfig('schrackdev/crm/log')) {
				$options['schrack_log_transfer'] = true;
			}
			$this->_client = Mage::helper('schrack/soap')->createClient(Mage::getStoreConfig('schrack/crm/wsdl'), $options);
		}

		return $this->_client;
	}

	protected function _buildBeginOfExceptionMessage($type, Varien_Object $object) {
		if ($object->getId()) {
			return $type.' #'.$object->getId();
		} else {
			return 'New '.strtolower($type);
		}
	}

}
