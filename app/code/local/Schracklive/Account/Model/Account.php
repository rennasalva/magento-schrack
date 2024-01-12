<?php

class Schracklive_Account_Model_Account extends Mage_Core_Model_Abstract {
	
	protected $_eventPrefix = 'account_account';
	protected $_eventObject = 'account';
	protected $_advisor = null;
	protected $_systemContact = null;
	protected $_billingAddress = null;

	protected function _construct() {
		$this->_init('account/account');
	}

	// @todo use "model_save_commit_after" event
	protected function _afterSaveCommit() {
		if ($this->dataHasChangedFor('wws_customer_id')) {
			$systemContact = $this->getSystemContact();
			if ($systemContact) {
				$systemContact->setEmail(Mage::helper('schrackcustomer')->getEmailForSystemContact($this))
						->save();
				$systemContact->updateWwsCustomerId();
			}
		}
		return parent::_afterSaveCommit();
	}

	protected function _afterDeleteCommit() {
		if ($addresses = $this->getAddresses()) {
			$addresses->delete();
		}
		$this->getAllContacts()->delete();
		return parent::_afterDeleteCommit();
	}

	public function loadByWwsCustomerId($wwsCustomerId, $alternateEmail = "") {
		if($alternateEmail != '') {
			// New self registration process:
			return $this->load($alternateEmail, 'email');
		}
		return $this->load($wwsCustomerId, 'wws_customer_id');
	}

	public function getName($singleLine=false) {
		$name = array_filter(array($this->getName1(), $this->getName2(), $this->getName3()));
		if ($singleLine) {
			return join(' ', $name);
		} else {
			return join(chr(10), $name);
		}
	}

	public function getStreet1() {
		return $this->getBillingAddress() ? $this->getBillingAddress()->getStreet(1) : '';
	}

	public function getPostcode() {
		return $this->getBillingAddress() ? $this->getBillingAddress()->getPostcode() : '';
	}

	public function getCity() {
		return $this->getBillingAddress() ? $this->getBillingAddress()->getCity() : '';
	}

	public function getCountryId() {
		return $this->getBillingAddress() ? $this->getBillingAddress()->getCountryId() : '';
	}

	public function getTelephone() {
		return $this->getBillingAddress() ? $this->getBillingAddress()->getTelephone() : '';
	}

	public function getFax() {
		return $this->getBillingAddress() ? $this->getBillingAddress()->getFax() : '';
	}

	/**
	 * Get the customer model of the primary advisor.
	 *
	 * @return Schracklive_SchrackCustomer_Model_Customer	false if no principal name is set, null if the customer model couln't be loaded
	 */
	public function getAdvisor() {
		if ( is_null($this->_advisor) ) {
            $principalName = $this->getAdvisorPrincipalName();
            if (!$principalName) {
                return false;
            }
            $this->_advisor = Schracklive_SchrackCustomer_Model_Customer::getAdvisorForPrincipalName($principalName);
		}
		return $this->_advisor;
	}

	/**
	 *
	 * @return Schracklive_SchrackCustomer_Model_Customer
	 */
	public function getSystemContact() {
		if (!is_null($this->_systemContact)) {
			return $this->_systemContact;
		}

		$customer = Mage::getModel('customer/customer');
		/* @var $customer Schracklive_SchrackCustomer_Model_Customer */
		$customer->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
				->loadByAccountContactNumber($this->getId(), -1);
		if (!$customer->getId()) {
			return null;
		}
		$this->_systemContact = $customer;
		return $customer;
	}

	/**
	 * Get account's billing address.
	 *
	 * @return Mage_Customer_Model_Entity_Address
	 */
	public function getBillingAddress() {
		if (!is_null($this->_billingAddress)) {
			return $this->_billingAddress;
		}

		$address = null;
		$systemContact = $this->getSystemContact();
		if ($systemContact) {
			$address = $systemContact->getPrimaryBillingAddress();
			if ($address && $address->getId()) {
				$this->_billingAddress = $address;
			}
		}
		return $address;
	}

	/**
	 * Get account's addresses collection.
	 *
	 * @return Mage_Customer_Model_Entity_Address_Collection
	 */
	public function getAddresses() {
		if ($this->getSystemContact()) {
			return $this->getSystemContact()->getAddressesCollection();
		} else {
			return null;
		}
	}

	/**
	 * Get account's collection of active contacts 
	 *
	 * @return Mage_Customer_Model_Entity_Customer_Collection
	 */
	public function getContacts() {
		return Mage::getResourceModel('customer/customer_collection')->setAccountIdFilter($this->getId())->setContactFilter();
	}

	/**
	 * Get account's contacts collection
	 *
	 * @return Mage_Customer_Model_Entity_Customer_Collection
	 */
	public function getAllContacts() {
		return Mage::getResourceModel('customer/customer_collection')->setAccountIdFilter($this->getId())->setAnyContactFilter();
	}

	public function validate() {
		$errors = array();

		if (!Zend_Validate::is(trim($this->getName1()), 'NotEmpty')) {
			$errors[] = Mage::helper('account')->__('First line of name can\'t be empty');
		}

		if (empty($errors)) {
			return true;
		}
		return $errors;
	}

    public function countByVatIdentificationNumber($vatno) {
        $coll = $this->getCollection()
            ->addFieldToFilter('vat_identification_number', $vatno);
         return $coll->count();
    }
}
