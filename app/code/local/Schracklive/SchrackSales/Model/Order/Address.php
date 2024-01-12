<?php

class Schracklive_SchrackSales_Model_Order_Address extends Mage_Sales_Model_Order_Address {

	protected $_eventPrefix = 'sales_order_address';
	protected $_eventObject = 'address';	// [sic!] same as in Magento 1.4.2.0

	/**
	 * Validate address attribute values
	 *
	 * @return bool
	 */
	public function validate() {
		return Mage::helper('schrackcustomer/address')->validate($this);
	}

	/**
	 * Tells if the address is one of a Schrack account.
	 *
	 * return bool
	 */
	public function belongsToAccount() {
		// order addresses always belong to the ordering customer, so check the customer's address
		$customerAddress = Mage::getModel('customer/address')->load($this->getCustomerAddressId());
		if ($customerAddress->getId()) {
			return Mage::helper('schrackcustomer/address')->belongsToAccount($customerAddress);
		} else {
			return false;
		}
	}

	public function setName1($name) {
		$this->setLastname($name);
	}

	public function setName2($name) {
		$this->setMiddlename($name);
	}

	public function setName3($name) {
		$this->setFirstname($name);
	}

	public function getName1() {
		return $this->getLastname();
	}

	public function getName2() {
		return $this->getMiddlename();
	}

	public function getName3() {
		return $this->getFirstname();
	}

}
