<?php

class Schracklive_SchrackCustomer_Block_Account_Dashboard_Hello extends Mage_Customer_Block_Account_Dashboard_Hello {

	public function getCustomer() {
		return Mage::getSingleton('customer/session')->getCustomer();
	}

	public function getCustomerName() {
		return $this->getCustomer()->getName();
	}

	public function getCustomerFullName() {
		return $this->getCustomer()->getSalutation().' '.$this->getCustomerName();
	}

}
