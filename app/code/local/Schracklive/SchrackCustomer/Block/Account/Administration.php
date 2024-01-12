<?php

class Schracklive_SchrackCustomer_Block_Account_Administration extends Mage_Core_Block_Template {

	public function getCustomer() {
		return Mage::getSingleton('customer/session')->getCustomer();
	}

	public function getVisibleContacts() {
		return $this->getCustomer()->getCollection()
						->setAccountIdFilter($this->getCustomer()->getSchrackAccountId())
						->setRealContactAndProspectFilter(false)
						->addAttributeToSort('lastname', 'ASC');
	}

}
