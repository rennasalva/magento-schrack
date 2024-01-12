<?php

class Schracklive_SchrackApi_Model_Session extends Mage_Api_Model_Session {

	protected $sessionPrefix; // fix prefix for auto-login (<prefix>/<username>)

	public function __construct() {
		$this->sessionPrefix = Mage::getStoreConfig('schrack/api/session_prefix');
	}

	public function init($namespace, $sessionName=null) {
		// set signal that we're in an API session
		Mage::register('schrack_api_session', true);

		return parent::init($namespace, $sessionName);
	}

	public function setSessionId($sessId = null) {
		if (!is_null($sessId)) {
			list($sessionPrefix) = explode('/', $sessId, 2);
			if ($sessionPrefix != $this->sessionPrefix) {
				$this->_currentSessId = $sessId;
			}
		}
		return $this;
	}

	public function isLoggedIn($sessId = false) {
        $sessionIdArray = explode('/', $sessId, 3);
        if ( ! is_array($sessionIdArray) || count($sessionIdArray) < 1 ) {
            return false;
        }
        $sessionPrefix = $sessionIdArray[0];
        if ( count($sessionIdArray) < 2 ) {
			return parent::isLoggedIn($sessId);
        }
		if ($sessionPrefix != $this->sessionPrefix) {
			return parent::isLoggedIn($sessId);
		}
        $sessionUser = $sessionIdArray[1];

		// auto login for simple schrack authentication
		$loginSuccessful = $this->_login($sessionUser);

		if ($loginSuccessful) {
			Mage::register('isSecureArea', true, true);
		}

		return $loginSuccessful;
	}

	protected function _login($userName) {
		$user = Mage::getModel('api/user')
						->setSessid($this->getSessionId())
						->loadByUsername($userName);
		if ($user->getId()) {
			Mage::dispatchEvent('api_user_authenticated', array(
						'model' => $user,
						'api_key' => '',
					));
		} else {
			return false;
		}

		if ($user->getIsActive() != '1') {
			return false;
		} elseif (!Mage::getModel('api/user')->hasAssigned2Role($user->getId())) {
			return false;
		} else {
			$this->setUser($user);
			$this->setAcl(Mage::getResourceModel('api/acl')->loadAcl());
		}

		return true;
	}

}
