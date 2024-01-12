<?php

class Schracklive_Mobile_Model_Customer_Session_Observer {

	/**
	 *
	 * @event controller_action_predispatch_<route>
	 * @see Mage_Core_Controller_Varien_Action
	 */
	public function loginUser($observer) {
		$appKey = $observer->getControllerAction()->getRequest()->getParam('appkey');
		if (!$appKey) {
			return;
		}
		
		$appKey = explode(':', base64_decode($appKey));
		$username = filter_var($appKey[0], FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_HIGH | FILTER_FLAG_ENCODE_LOW);
		$password = filter_var($appKey[1], FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_HIGH | FILTER_FLAG_ENCODE_LOW);

		$session = Mage::getSingleton('customer/session');
		switch (count($appKey)) {
			case 2:
				if ($this->_isSessionValid($session, 'email', $username)) {
					return;
				}
				$this->_loginCustomer($username, $password);
				break;
			case 3:
				$systemContactId = (int)$appKey[2];
				/*
				 * @todo test if this works now with renewSession()
				if ($this->_isSessionValid($session, 'id', $systemContactId)) {
					return;
				}
				 */
				$this->_loginSystemContactByEmployee($username, $password, $systemContactId);
				break;
		}
	}

	protected function _isSessionValid($session, $idAttribute, $id) {
		if ($session->isLoggedIn()) {
			if ($session->getCustomer()->getData($idAttribute) == $id) {
				return true;
			} else {
				$session->logout();
			}
		}
		return false;
	}

	protected function _loginCustomer($username, $password) {
		return Mage::getSingleton('customer/session')->login($username, $password);
	}

	protected function _loginSystemContactByEmployee($username, $password, $systemContactId) {
		$customer = Mage::getModel('customer/customer');
		if ($customer->authenticate($username, $password)) {
			if ($customer->isEmployee()) {
				$systemContact = Mage::getModel('customer/customer')->load($systemContactId);
				if ($systemContact->getId()) {
					$systemContact->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
					Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($systemContact);
					Mage::getSingleton('customer/session')->renewSession();
					Mage::getSingleton('customer/session')->setLoggedInCustomer($customer);
					return true;
				}
			}
		}
		return false;
	}

}
