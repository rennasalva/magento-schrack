<?php

class Schracklive_Ad_Model_Customer_Observer {

    private $full_logging = 0;

    // Maybe old or new domain (not sure if old or new domain):
    private function checkUnknownDomain($loginToken) {
        if ($this->full_logging) Mage::log('LDAP-MESSAGE #992 :: checkUnknownDomain (loginToken = ' . $loginToken, null, 'ldap_authentication_message.log');

        if (preg_match('/@.{0,3}schrack\..{2,3}$/', $loginToken)) {
            if ($this->full_logging) Mage::log('LDAP-MESSAGE #992-1 :: checkUnknownDomain = true', null, 'ldap_authentication_message.log');
            return true;
        } else {
            if ($this->full_logging) Mage::log('LDAP-MESSAGE #992-2 :: checkUnknownDomain = false', null, 'ldap_authentication_message.log');
            return false;
        }
    }

	public function authenticate($observer) {
        // Set logging from backend setting:
        $this->full_logging = Mage::getStoreConfig('schrack/ad/logging');

		$customer = $observer->getModel();
		$loginToken = $observer->getLogin();
		$password = $observer->getPassword();

        $checkedLoginToken = $this->_checkUserPrincipalName($customer, $loginToken);

		if ($checkedLoginToken) {
		    $userPrincipalName = $checkedLoginToken;

            if ($this->full_logging) Mage::log('LDAP-MESSAGE #994 :: try to authenticate by userPrincipalName = ' . $userPrincipalName . ' and password = ' . $password, null, 'ldap_authentication_message.log');

			if ($this->_authenticate($userPrincipalName, $password)) {
				$customer->setIsAuthenticated(true);
			}
		}
	}

	protected function _checkUserPrincipalName($customerModel, $loginToken) {
        $userPrincipalName = '';
        // NOTE: $loginToken can be email or userPrincipalName  !!!

        if ($this->full_logging) Mage::log('LDAP-MESSAGE #993-1 :: _checkUserPrincipalName -> incoming loginToken = ' . $loginToken, null, 'ldap_authentication_message.log');

		if ($this->checkUnknownDomain($loginToken)) {
            $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');

            // Prevents double ship_order commands to WWS:
            $query  = "SELECT * FROM customer_entity";
            $query .= " WHERE email LIKE '" . $loginToken . "'";

            $queryResult = $readConnection->query($query);

            if ($queryResult->rowCount() > 0) {
                foreach ($queryResult as $recordset) {
                    $userPrincipalName = $recordset['schrack_user_principal_name'];
                }
            }
            if ($userPrincipalName) {
                if ($this->full_logging) Mage::log('LDAP-MESSAGE #993-2 :: _checkUserPrincipalName -> processing -> incoming loginToken = ' . $loginToken . ' >>> outcoming loginToken = ' . $userPrincipalName, null, 'ldap_authentication_message.log');
                // This observer needs some loaded observer model, otherwise Magento crashes :(
                $customerModel->loadByEmail($loginToken);

                // At the end of the loading observer customer model, we can assign and lastly return the correct userPrincipalName:
                $loginToken = $userPrincipalName;
            } else {
                $customerModel->loadByEmail($loginToken);
                if ($this->full_logging) Mage::log('LDAP-MESSAGE #993-3 :: _checkUserPrincipalName -> processing -> incoming loginToken = ' . $loginToken . ' >>> outcoming loginToken = ' . $customerModel->getSchrackUserPrincipalName(), null, 'ldap_authentication_message.log');
                $loginToken = $customerModel->getSchrackUserPrincipalName(); // WARNING : this value comes from EAV-Attribute ('schrack_user_principal_name'), NOT from customer_entity DB-Table !!!
            }
		} else {
            $loginToken = false;
		}
		if ($loginToken && !$customerModel->getId()) {
			throw Mage::exception('Schracklive_Ad', 'Employee not synced yet.');
		}

        if ($this->full_logging) Mage::log('LDAP-MESSAGE #993-4 :: _checkUserPrincipalName -> processed loginToken = ' . $loginToken, null, 'ldap_authentication_message.log');

		return $loginToken;
	}

	protected function _authenticate($loginToken, $password) {
	    // NOTE: $loginToken can be email or userPrincipalName  !!!

		$connector = Mage::getSingleton('ad/connector');
        $result['success'] = false;

        if ($this->checkUnknownDomain($loginToken)) {
            if ($this->full_logging) Mage::log('LDAP-MESSAGE #004 :: loginname=' . $loginToken . ' try to authenticate on new domain #1', null, 'ldap_authentication_message.log');
            $result = $connector->authenticate($loginToken, $password, 'config2');
        }

		if (!is_array($result) || !isset($result['success'])) {
			$message = 'Could not get LDAP authentication status.';
            if ($this->full_logging) Mage::log('LDAP-MESSAGE #005 :: ' . $message, null, 'ldap_authentication_message.log');
			Mage::log($message, Zend_Log::ERR);
			throw Mage::exception('Schracklive_Ad', $message);
		}
		if (!$result['success']) {
			switch ($result['code']) {
				case Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND:
                    if ($this->full_logging) Mage::log('LDAP-MESSAGE #006 :: Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND', null, 'ldap_authentication_message.log'); // missing username or user not found
				case Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID:
                    if ($this->full_logging) Mage::log('LDAP-MESSAGE #007 :: Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID', null, 'ldap_authentication_message.log');// missing password or password invalid
					break;
				default:
					$message = 'LDAP FAILURE'.(isset($result['messages']) && is_array($result['messages']) ? ': '.join('; ', $result['messages']) : '');
                    if ($this->full_logging) Mage::log('LDAP-MESSAGE #008 :: ' . $message, null, 'ldap_authentication_message.log');
					Mage::log($message, Zend_Log::ERR);
					throw Mage::exception('Schracklive_Ad', $message);
			}
            if ($this->full_logging) Mage::log('LDAP-MESSAGE #888 :: Some undefined login error', null, 'ldap_authentication_message.log');
			return false;
		}

        if ($this->full_logging) Mage::log('LDAP-MESSAGE #999 :: Employee Login succeeded for ' . $loginToken, null, 'ldap_authentication_message.log');
		return true;
	}

}
