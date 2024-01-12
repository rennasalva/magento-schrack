<?php

require_once 'shell.php';

class Schracklive_Shell_Ldap extends Schracklive_Shell {

	protected $ldap;

	public function run() {
		if ($this->getArg('options')) {
			echo "LDAP Options:\n";
			print_r(Mage::getSingleton('ad/connector')->getLdapOptions());
		} elseif ($this->getArg('connect')) {
			if ($this->connect()) {
				echo "Connected.\n";
			}
		} elseif ($this->getArg('login')) {
			echo 'Password for '.$this->getArg('login').': ';
			$password = fgets(STDIN);
			$result = Mage::getSingleton('ad/connector')->authenticate($this->getArg('login'), trim($password));
			if ($result['success']) {
				echo "Authenticated.\n";
			} else {
				echo "Authentication errors: {$result['code']}\n";
				echo join ("\n", $result['messages']);
				echo "\n";
			}
		} elseif ($this->getArg('email')) {
			if (!$this->connect()) {
				die("Connection failed.\n");
			}
			$filter = '(&(email='.$this->getArg('email').')'.Mage::getSingleton('ad/connector')->getAccountFilter().')';
			$result = $this->ldap->search($filter, Mage::getSingleton('ad/connector')->getBaseDn());
			foreach ($result as $data) {
				print_r($data);
			}			
		} else {
			echo $this->usageHelp(),"\n";
		}
	}

	protected function connect() {
		$options = Mage::getSingleton('ad/connector')->getLdapOptions();
		$ldap = new Zend_Ldap($options['server1']);
		$ldap->bind(Mage::getStoreConfig('schrack/ad/username'), Mage::getStoreConfig('schrack/ad/password'));
		$this->ldap = $ldap;

		return TRUE;
	}

}

$shell = new Schracklive_Shell_Ldap();
$shell->run();

