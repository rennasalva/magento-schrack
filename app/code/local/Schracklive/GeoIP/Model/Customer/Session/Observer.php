<?php

class Schracklive_Geoip_Model_Customer_Session_Observer {

	/**
	 * Checks to see if the key generated before the user was redirected is valid, and if so logs them in.
	 *
	 * @event controller_action_predispatch
	 * @param                              $observer
	 * @param Mage_Customer_Model_Customer $testCustomer for unit-tests only
	 */
	public function transfer($observer, Mage_Customer_Model_Customer $testCustomer = null) {
		$remoteKey = $observer->getControllerAction()->getRequest()->getParam('k');
		if (!$remoteKey) {
			return;
		}
		$session = Mage::getSingleton('customer/session');
		if ($session->isLoggedIn()) {
			return;
		}

		// extract the login from the base64 encoded key and generates the key again locally
		list($login) = explode(':', base64_decode($remoteKey), 2);
		$localKey = Mage::helper('geoip')->generateKey($login, $_SERVER['REMOTE_ADDR']);
		if ($localKey === $remoteKey) {  // if the keys match, log the user on
			 // use external $customer if supplied for unit testing
			$customer = $testCustomer ? $testCustomer : Mage::getModel('customer/customer');
			$customer->loadByEmail($login);
			if ($customer->getId()) {
				$session->setCustomerAsLoggedIn($customer);
			}
		}
	}

}
