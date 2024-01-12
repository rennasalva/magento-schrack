<?php

class Schracklive_SchrackCustomer_Model_Session_Observer {

	// event: "customer_session_init"
	public function sso($observer) {
        /*
		$session = Mage::getSingleton('customer/session');
		if ($session->isLoggedIn()) {
			return;
		}

		if (isset($_SERVER['REMOTE_USER'])) {
			$username = $_SERVER['REMOTE_USER'];
		} elseif (isset($_SERVER['HTTP_X_FORWARDED_USER']) && ($_SERVER['REMOTE_ADDR'] == Mage::getStoreConfig('schrack/sso/authProxyIP'))) {
			$username = $_SERVER['HTTP_X_FORWARDED_USER'];
		} else {
			return;
		}

		// user must belong to the same country as the shop
		$country = strtolower(Mage::helper('schrack')->getWwsCountry());
		if (preg_match('/@'.$country.'.schrack.lan$/i', $username)) {
			$customer = Mage::getModel('customer/customer')->loadByUserPrincipalName($username);
		} elseif (preg_match('/@schrack.(?:com|'.$country.')$/i', $username)) {
			$customer = Mage::getModel('customer/customer')->loadByEmail($username);
		}

		if (!is_object($customer) || !$customer->getId()) {
			return;
		}

		$session->setCustomerAsLoggedIn($customer);
         * 
         */
	}

}
