<?php

class Schracklive_SchrackAdmin_Model_User extends Mage_Admin_Model_User {

	const EXCEPTION_LDAP_FAILURE = 1;

	private $full_logging = 0;

	function _construct() {
		parent::_construct();
	}

	// 100% old domain:
	private function checkOldDomain($login) {
		if (preg_match('/@..\.schrack\.lan$/', $login)) {
			return true;
		} else{
			return false;
		}
	}

	// Maybe old or new domain (not sure if old or new domain):
	private function checkUnknownDomain($login) {
		if (preg_match('/@schrack\..{2,3}$/', $login)) {
			return true;
		} else{
			return false;
		}
	}


	public function authenticate($login, $password) {
		// Set logging from backend setting:
		$this->full_logging = Mage::getStoreConfig('schrack/ad/logging');

		if ($this->checkOldDomain($login) || $this->checkUnknownDomain($login)) {
			if ($this->full_logging) Mage::log('LDAP-MESSAGE #201 :: ' . $login, null, 'ldap_authentication_message.log');
			$this->loadByUsername($login);
			// Fallback, if user has old username in DB-table 'admin_user':
			if ($this->getIsActive() != '1' && $this->checkUnknownDomain($login)) {
				if ($this->full_logging) Mage::log('LDAP-MESSAGE #202 :: loginname=' . $login, null, 'ldap_authentication_message.log');
				$is_active = 0;
				$resource = Mage::getSingleton('core/resource');
				$readConnection  = $resource->getConnection('core_read');
                // Strange behaviour -> somtimes email is e.g.: user@new.schrack.com instead of correct form: user@schrack.com
                $loginWithNewStringInside = str_replace('@schrack', '@new.schrack', $login);
                list($loginWithSchracktechnikStringInsideName, $loginWithDomainStringInside) = explode('@', $login);
                $loginWithSchracktechnikStringInside = $loginWithSchracktechnikStringInsideName . '@schracktechnik.mail.onmicrosoft.com';
				$queryFindAdminUserIsActive = "SELECT is_active, username FROM admin_user WHERE email LIKE '" . $login . "' OR email like '" . $loginWithNewStringInside . "' OR email like '" . $loginWithSchracktechnikStringInside . "'";
				$result = $readConnection->fetchAll($queryFindAdminUserIsActive);
				if (is_array($result) && !empty($result)) {
					foreach ($result as $index => $recordset) {
						$is_active = intval($recordset['is_active']);
						if ($is_active == 1) {
							$this->loadByUsername($recordset['username']);
						}
					};
				}
			}
			// nothing to do
		} else {
			if ($this->full_logging) Mage::log('LDAP-MESSAGE #203 :: parent::authenticate(): ' . $login . ' --> ' . $password, null, 'ldap_authentication_message.log');
			return parent::authenticate($login, $password);
		}

		$connector = Mage::getSingleton('ad/connector');

		try {
			$result['success'] = false;

			if ($this->checkOldDomain($login)) {
				// Old AD configuration:
				if ($this->full_logging) Mage::log('LDAP-MESSAGE #204 :: connector->authenticate(login, password, config1)', null, 'ldap_authentication_message.log');
				$result = $connector->authenticate($login, $password, 'config1');
			}

			if ($result['success'] == false && $this->checkUnknownDomain($login)) {
				// New AD configuration:
				if ($this->full_logging) Mage::log('LDAP-MESSAGE #205 :: connector->authenticate(login, password, config2)', null, 'ldap_authentication_message.log');
				$result = $connector->authenticate($login, $password, 'config2');
			}
		} catch (Exception $e) {
			if ($this->full_logging) Mage::log('LDAP-MESSAGE #206 :: Zend_Auth_Adapter_Ldap: '.$e->getMessage(), null, 'ldap_authentication_message.log');
			throw Mage::exception('Mage_Core', 'Zend_Auth_Adapter_Ldap: '.$e->getMessage(),	self::EXCEPTION_LDAP_FAILURE);
		}
		if ($this->full_logging) Mage::log($result, null, 'ldap_authentication_message.log');
		if (!$result['success']) {
			switch ($result['code']) {
				case Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND: // missing username or user not found
					if ($this->full_logging) Mage::log('LDAP-MESSAGE #207 :: FAILURE_IDENTITY_NOT_FOUND', null, 'ldap_authentication_message.log');
				case Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID: // missing password or password invalid
					if ($this->full_logging) Mage::log('LDAP-MESSAGE #208 :: FAILURE_CREDENTIAL_INVALID', null, 'ldap_authentication_message.log');
					throw Mage::exception('Mage_Core', Mage::helper('adminhtml')->__('Access denied.'));
					break;

				default:
					if ($this->full_logging) Mage::log('LDAP-MESSAGE #209 :: LDAP failure:<br>'. join("<br/>\n", $result['messages']), null, 'ldap_authentication_message.log');
					throw Mage::exception('Mage_Core', 'LDAP failure:<br>'. join("<br/>\n", $result['messages']), self::EXCEPTION_LDAP_FAILURE);
			}
		} else {
			if ($this->getIsActive() != '1') {
				if ($this->full_logging) Mage::log('LDAP-MESSAGE #210 :: ' . Mage::helper('adminhtml')->__('This account is inactive.'), null, 'ldap_authentication_message.log');
				Mage::throwException(Mage::helper('adminhtml')->__('This account is inactive.'));
			}
			if (!$this->hasAssigned2Role($this->getId())) {
				if ($this->full_logging) Mage::log('LDAP-MESSAGE #211 :: ' . Mage::helper('adminhtml')->__('Access denied.'), null, 'ldap_authentication_message.log');
				Mage::throwException(Mage::helper('adminhtml')->__('Access denied.'));
			}
			$result = true;
		}

		Mage::dispatchEvent('admin_user_authenticate_after', array(
					'username' => $login,
					'password' => $password,
					'user' => $this,
					'result' => $result,
				));

		if (!$result) {
			$this->unsetData();
		}
		return $result;
	}

	// Removed first name from validation
	public function validate() {
		$errors = array();

		if (!Zend_Validate::is($this->getUsername(), 'NotEmpty')) {
			$errors[] = Mage::helper('adminhtml')->__('User Name is required field.');
		}

		/* if (!Zend_Validate::is($this->getFirstname(), 'NotEmpty')) {
		  $errors[] = Mage::helper('adminhtml')->__('First Name is required field.');
		  } */

		if (!Zend_Validate::is($this->getLastname(), 'NotEmpty')) {
			$errors[] = Mage::helper('adminhtml')->__('Last Name is required field.');
		}

		if (!Zend_Validate::is($this->getEmail(), 'EmailAddress')) {
			$errors[] = Mage::helper('adminhtml')->__('Please enter a valid email.');
		}

		if ($this->hasNewPassword()) {
			if (Mage::helper('core/string')->strlen($this->getNewPassword()) < self::MIN_PASSWORD_LENGTH) {
				$errors[] = Mage::helper('adminhtml')->__('Password must be at least of %d characters.', self::MIN_PASSWORD_LENGTH);
			}

			if (!preg_match('/[a-z]/iu', $this->getNewPassword()) || !preg_match('/[0-9]/u', $this->getNewPassword())) {
				$errors[] = Mage::helper('adminhtml')->__('Password must include both numeric and alphabetic characters.');
			}

			if ($this->hasPasswordConfirmation() && $this->getNewPassword() != $this->getPasswordConfirmation()) {
				$errors[] = Mage::helper('adminhtml')->__('Password confirmation must be same as password.');
			}
                        Mage::dispatchEvent('admin_user_validate', array(
                            'user' => $this,
                            'errors' => $errors,
                        )); //added by nagarro
		}

		if ($this->userExists()) {
			$errors[] = Mage::helper('adminhtml')->__('A user with the same user name or email aleady exists.');
		}

		if (count($errors) === 0) { //modified by nagarro
                    return true;
                }
                return (array)$errors; //modified by nagarro
	}

}

?>
