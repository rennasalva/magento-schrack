<?php

class Schracklive_Account_Block_Template extends Mage_Core_Block_Template
{

	protected $_customer;
	protected $_account;

	// test
	public function getCustId()
	{
		return Mage::getSingleton('customer/session')->getCustomer()->getSchrackWwsCustomerId();
	}

	public function getCustomer()
	{
		if (empty($this->_customer)) {
			$this->_customer = Mage::getSingleton('customer/session')->getCustomer();
		}
		return $this->_customer;
	}

	public function getAccount()
	{
		if (empty($this->_account)) {
			$this->_account = $this->getCustomer()->getAccount();
		}
		return $this->_account;
	}

	/*
	public function getAccountUrl()
	{
		return Mage::getUrl('customer/account/edit', array('_secure'=>true));
	}
	*/

}

