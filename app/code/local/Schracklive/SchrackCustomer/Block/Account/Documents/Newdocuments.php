<?php

class Schracklive_SchrackCustomer_Block_Account_Documents_Newdocuments extends Mage_Core_Block_Template {
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
