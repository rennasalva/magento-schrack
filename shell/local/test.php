<?php

require_once dirname(dirname(__FILE__)) . '/abstract.php';

/**
 * Test Shell Script
 *
 * @author      mk@plan2.net
 */
class Schracklife_Shell_Test extends Mage_Shell_Abstract {

	public function run() {
		echo 'Magento version: ', Mage::getVersion(), "\n";
	}

}

$shell = new Schracklife_Shell_Test();
$shell->run();
