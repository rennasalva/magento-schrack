<?php

class Schracklive_SchrackUniversalAnalytics_Helper_Data extends BlueAcorn_UniversalAnalytics_Helper_Data {

	public function getCollectionListName($collectionObject) {
		$listName = explode('_', get_class($collectionObject));
		return $listName[1];
	}

	public function generateUserId() {
		$userId = '';
		/** @var Schracklive_SchrackCustomer_Model_Customer $customer */
		$customer = $this->getCustomer();
		if ($customer) {
			$userId = "'userId': '".strtoupper(Mage::helper('schrack')->getCountryTld())."/".$customer->getAccount()->getWwsCustomerId()."/".$customer->getSchrackWwsContactNumber()."'";
		}
		return $userId;
	}

	public function getCustomer() {
		$customer = null;
		$session = Mage::getSingleton('customer/session');
		if ($session && is_object($session->getCustomer())) {
			$customer = $session->getCustomer();
		}
		return $customer;
	}
}