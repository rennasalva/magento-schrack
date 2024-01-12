<?php

class Schracklive_Schrack_Helper_Mysql4 {

	static private $_session = NULL;
	private $overideUpdatedByValue = "";

	public function getChangeIdentifier() {
		if ($this->overideUpdatedByValue != "") {
			return $this->overideUpdatedByValue;
		}

		$session = $this->getSession();
		$id = '';

		if ($session instanceof Mage_Customer_Model_Session) {
			$customer = $session->getCustomer();
			$id = 'customer/'.$customer->getId();
			if ($session->isLoggedIn()) {
				if ($customer->isContact()) {
					$id .= '/'.$customer->getSchrackWwsCustomerId().'-'.$customer->getSchrackWwsContactNumber();
				} elseif ($customer->isEmployee()) {
					$id .= '/'.$customer->getSchrackUserPrincipalName();
				}
			}
		} elseif ($session instanceof Mage_Admin_Model_Session) {
			$user = $session->getUser();
			$id = 'admin/'.$user->getId();
			if ($session->isLoggedIn()) {
				$id .= '/'.$user->getUsername();
			}
		} elseif ($session instanceof Mage_Api_Model_Session) {
			$user = $session->getUser();
			$id = 'api/'.$user->getId();
			if ($session->isLoggedIn()) {
				$id .= '/'.$user->getUsername();
			}
		} elseif ( Mage::registry(Schracklive_Schrack_Helper_Stomp::MQ_IMPORT_MARKER) ) {
			$id = Schracklive_Schrack_Helper_Stomp::MQ_IMPORT_MARKER;
		}

		return $id;
	}

	protected function getSession() {
		if (is_null(self::$_session)) {
			self::$_session = Mage::registry('_singleton/api/session');
		}
		if (is_null(self::$_session)) {
			self::$_session = Mage::registry('_singleton/admin/session');
		}
		if (is_null(self::$_session)) {
			self::$_session = Mage::registry('_singleton/customer/session');
		}

		return self::$_session;
	}


	public function setChangeIdentifier($updatedBy) {
		$this->overideUpdatedByValue = $updatedBy;
	}
}
