<?php

require_once 'shell.php';

class Schracklive_Shell_ForceUpdates extends Schracklive_Shell {

	public function run() {
        echo 'flushing cache...'.PHP_EOL;
		Mage::app()->getCacheInstance()->flush();
        echo 'cleaning cache...'.PHP_EOL;
		Mage::app()->cleanCache();
        $delay = 5;
        echo "sleeping for $delay seconds...".PHP_EOL;
        sleep($delay);
        echo "requesting module updates..";
        Mage_Core_Model_Resource_Setup::applyAllUpdates();
        echo '...'.PHP_EOL;
        Mage_Core_Model_Resource_Setup::applyAllDataUpdates();
        echo 'done.'.PHP_EOL;
	}

}

$shell = new Schracklive_Shell_ForceUpdates();
$shell->run();
