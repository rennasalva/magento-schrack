<?php

class Schracklive_SchrackCustomer_Model_Address extends Mage_Customer_Model_Address {

	const NO_ADDRESS_NUMBER                = 888888;

	public function loadByWwsAddressNumber($wwsCustomerId, $wwsAddressNumber) {
		$this->_getResource()->loadByWwsAddressNumber($this, $wwsCustomerId, $wwsAddressNumber);
		return $this;
	}

	/*
	 * Set aliases for names (map from Magento's personal names to Sugar's location names)
	 */

	public function getName() {
		if ($this->belongsToAccount()) {
			return join(' ', array_filter(array($this->getName1(), $this->getName2(), $this->getName3())));
		} else {
			return parent::getName();
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

	/**
	 * Tells if the address is one of a Schrack account.
	 *
	 * return bool
	 */
	public function belongsToAccount() {
		return Mage::helper('schrackcustomer/address')->belongsToAccount($this);
	}

	/**
	 * Validate address attribute values.
	 *
	 * @return bool
	 */
	public function validate() {
		return Mage::helper('schrackcustomer/address')->validate($this);
	}

}
