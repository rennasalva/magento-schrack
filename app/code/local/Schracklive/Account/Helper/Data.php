<?php

class Schracklive_Account_Helper_Data extends Mage_Core_Helper_Abstract {

	protected $_accountAttributes = array(
		'wws_customer_id',
        'schrack_s4y_id',
		'wws_branch_id',
		'prefix',
		'name1',
		'name2',
		'name3',
		'email',
		'homepage',
		'advisor_principal_name',
		'advisors_principal_names',
		'match_code',
		'description',
		'information',
		'currency_code',
		'vat_identification_number',
		'company_registration_number',
		'gtc_accepted',
		'delivery_block',
		'schrack_pickup',
		'sales_area',
		'rating',
		'enterprise_size',
		'account_type',
        'street',
        'city',
        'postcode',
        'country_id',
        'limit_web'
	);
	protected $_customerAttributes = array(
		'prefix',
		'firstname',
		'lastname',
		'email',
		'gender',
		'schrack_acl_role_id',
		'schrack_salutatory',
		'schrack_telephone',
		'schrack_fax',
		'schrack_mobile_phone',
		'schrack_crm_role_id',
		'schrack_department',
		'schrack_main_contact',
		'schrack_wws_branch_id',
		'schrack_wws_address_number',
		'schrack_wws_contact_number',
		'schrack_newsletter',
		'schrack_advisor_principal_name',
		'schrack_advisors_principal_names',
		'schrack_comments',
		'schrack_interests',
		'schrack_active',
		'schrack_emails',
		'schrack_pickup',
		'password',
		'confirmation'
	);
	protected $_addressAttributes = array(
		'street',
		'postcode',
		'city',
		'country_id',
		'telephone',
		'fax',
		'default_billing',
		'default_shipping',
	);
	protected $_mapAttributes = array(
		'account_id' => 'entity_id'
	);

	/**
	 * Find system contact for WWS customer
	 *
	 * @param string $wwsCustomerId
	 * @return Mage_Customer_Model_Customer
	 */
	public function getSystemContactByWwsCustomerId($wwsCustomerId) {
		$account = Mage::getModel('account/account')->loadByWwsCustomerId($wwsCustomerId);
		if ($account->getId()) {
			return $account->getSystemContact();
		} else {
			return null;
		}
	}

	public function updateOrCreateAccount($wwsCustomerId, array $data, $alternateEmail = "") {
		list($accountData, $customerData, $addressData) = $this->splitModelData($data);

		if ($alternateEmail != "") {
			// @var $account Schracklive_Account_Model_Account
			$account = Mage::getModel('account/account')->loadByWwsCustomerId($wwsCustomerId, $alternateEmail);
		} else {
			$account = Mage::getModel('account/account')->loadByWwsCustomerId($wwsCustomerId);
		}

        //Mage::log($account->getId(), null, 'advisors_principal_names.log');
        //Mage::log($account, null, 'advisors_principal_names.log');
		if ($account->getId()) {
			// update
			foreach ($accountData as $key => $value) {
				$account->setData($key, $value);
			}
		} else {
			// create
			$account->setData($accountData);
			$account->setData('wws_customer_id', $wwsCustomerId);
			if ( ! $wwsCustomerId ) {
				$account->setCrmStatus(Schracklive_Crm_Helper_Data::STATUS_ACCOUNT_PENDING);
			}
		}
		$accountErrors = $account->validate();
		if (is_array($accountErrors)) {
			$e = Mage::exception('Schracklive_Account', implode('; ', $accountErrors), Schracklive_Account_Exception::VALIDATION_ERROR);
			foreach ($accountErrors as $accountError) {
				$e->addMessage(Mage::getSingleton('core/message')->error($accountError));
			}
			throw $e;
		}
		$account->save();

		return $account;
	}

	public function deleteAccount ( $wwsCustomerId ) {
		$collection = Mage::getResourceModel('customer/customer_collection');
		$collection->addFieldToFilter('schrack_wws_customer_id',$wwsCustomerId);
		foreach ( $collection as $customer ) {
			$customer->setData('avoidSendDelete',true);
			$customer->delete();
		}
		$account = Mage::getModel('account/account')->loadByWwsCustomerId($wwsCustomerId);
		$account->delete();
	}

	public function updateOrCreateSystemContact(Schracklive_Account_Model_Account $account, array $data) {

		list($accountData, $customerData, $addressData) = $this->splitModelData($data);

		$systemContact = $this->_getSystemContactReliably($account);
		$systemContact->setLastname($account->getName1());
		$systemContact->setMiddlename($account->getName2());
		$systemContact->setFirstname($account->getName3());
		$systemContact->save();

		$billingAddress = $this->_replaceBillingAddress($account, $systemContact, $addressData);
		if ($billingAddress->getNewAddressFlag()) {
			$systemContact->setData('default_billing', $billingAddress->getId());
			if ( ! $systemContact->getData('default_shipping') ) {
				$systemContact->setData('default_shipping', $billingAddress->getId());
			}
			$systemContact->save();
		}

		return $systemContact;
	}

	public function splitModelData($data) {

		$accountData = array();
		$customerData = array();
		$addressData = array();

		foreach ($data as $key => $value) {
			if (in_array($key, $this->_addressAttributes)) {
				$addressData[$key] = $value;
			}
			if (in_array($key, $this->_customerAttributes)) {
				$customerData[$key] = $value;
			}
			if (in_array($key, $this->_accountAttributes)) {
				$accountData[$key] = $value;
			}
		}

		return array($accountData, $customerData, $addressData);
	}

	protected function _getSystemContactReliably($account) {
		$systemContact = $account->getSystemContact();
		if (!$systemContact) {
			$systemContact = Mage::getModel('customer/customer');
			Mage::helper('schrackcustomer')->setupSystemContact($systemContact, $account);
		}
		return $systemContact;
	}

	/**
	 * @param Schracklive_Account_Model_Account          $account
	 * @param Schracklive_SchrackCustomer_Model_Customer $systemContact
	 * @param array                                      $addressData
	 * @return Schracklive_SchrackCustomer_Model_Address
	 */
	protected function _replaceBillingAddress(Schracklive_Account_Model_Account $account, Schracklive_SchrackCustomer_Model_Customer $systemContact, array $addressData) {
		$address = $account->getBillingAddress();
		/* @var $address Schracklive_SchrackCustomer_Model_Address */
		if ($address && $address->getId() && intval($address->getSchrackWwsAddressNumber(0)) === 0) {
			// update
			foreach ($addressData as $key => $value) {
				$address->setData($key, $value);
			}
		} else {
			// create
			$address = Mage::getModel('customer/address');
			$address->setData($addressData);
			Mage::helper('schrackcustomer')->setupBillingAddress($address, $systemContact->getId());
			$address->setNewAddressFlag(true);
		}
		$address->save();

		return $address;
	}

}
