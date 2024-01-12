<?php

// Singleton
class Schracklive_Ad_Model_Connector {

	const EXCEPTION_LDAP_FAILURE = 1;

	private $full_logging = 0;

	public function getLdapOptions($domainConfig = 'config2') {
        $connectionIsSSL = false;
        if ($domainConfig == 'config2') {
            $connectionHost         = Mage::getStoreConfig('schrack/ad/host2');
            $connectionPort         = Mage::getStoreConfig('schrack/ad/port2');
            $connectionIsSSL        = Mage::getStoreConfig('schrack/ad/use_ssl2');
            $connectionUsername     = Mage::getStoreConfig('schrack/ad/username2');
            $connectionPassword     = Mage::getStoreConfig('schrack/ad/password2');
        }

        $connectionFallbackhost = Mage::getStoreConfig('schrack/ad/fallback_host');
        $connectionQueryBaseDN = $this->getBaseDn($domainConfig);

		$options1 = array(
			'host' => $connectionHost,
			'port' => $connectionPort,
			'useSsl' => $connectionIsSSL,
			'accountFilterFormat' => str_replace('*', '%s', $this->getAccountFilter()),
			'optReferrals' => false, // required for AD
			'username' => $connectionUsername,
			'password' => $connectionPassword,
			'baseDn' => $connectionQueryBaseDN,
			'tryUsernameSplit' => FALSE, // use full username (not only user part before @)
		);

		$options['server1'] = $options1;
		if ($connectionFallbackhost) {
			$options['server2'] = $options1;
			$options['server2']['host'] = $connectionFallbackhost;
		}

		return $options;
	}

	public function getAccountFilter() {
		// all active human users (userAccountControl: see AD docs)
		return '(&(objectCategory=person)(objectClass=user)(userprincipalname=*)(!(userAccountControl:1.2.840.113556.1.4.803:=2)))';
	}

	public function getBaseDn($domainConfig = 'config2') {
        if ($domainConfig == 'config2') {
            return 'dc=schrack,dc=com';
        }
	}

	public function authenticate($login, $password, $config = 'config2') {
        // Set logging from backend setting:
        $this->full_logging = Mage::getStoreConfig('schrack/ad/logging');

		$adapter = new Zend_Auth_Adapter_Ldap($this->getLdapOptions($config), $login, $password);

		try {
			$result = $adapter->authenticate();

			if ($result && !$result->isValid() && $this->full_logging == 1 ) {
                if ($this->full_logging) Mage::log('LDAP-MESSAGE #101 :: Failed configuration > ' . $config . ' < for User: ' . $login . ':', null, 'ldap_authentication_message.log');
                if ($this->full_logging) Mage::log($adapter, null, 'ldap_authentication_message.log');
                Mage::log($result, null, 'ldap_authentication_message.log');
			}
		} catch (Exception $e) {
            if ($this->full_logging) Mage::log('LDAP-MESSAGE #102 :: Failed configuration (EXCEPTION) for User: ' . $login . ':', null, 'ldap_authentication_message.log');
            if ($this->full_logging) Mage::log($adapter, null, 'ldap_authentication_message.log');
            if ($this->full_logging) Mage::log('LDAP-MESSAGE #103 :: Zend_Auth_Adapter_Ldap Exception #001: '.$e->getMessage(), null, 'ldap_authentication_message.log');
			throw Mage::exception('Schracklive_Ad', 'Zend_Auth_Adapter_Ldap: '.$e->getMessage(), self::EXCEPTION_LDAP_FAILURE);
		}

        return array('success' => $result->isValid(), 'code' => $result->getCode(), 'messages' => $result->getMessages());
	}

}
