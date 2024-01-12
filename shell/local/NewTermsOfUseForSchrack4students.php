<?php

require_once 'shell.php';

class Schracklive_Shell_NewTermsOfUseForSchrack4students extends Schracklive_Shell {

	public function run () {
	    // check availability of terms:
        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql = "SELECT count(*) FROM schrack_terms_of_use";
        $cnt = $readConnection->fetchOne($sql);
        if ( $cnt < 1 ) {
            throw new Exception("Table schrack_terms_of_use must contain at least one record!");
        }

        // reset customer flag if requested:
        if ( $this->getArg('reset_customer_flag') ) {
            $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $sql = "UPDATE customer_entity SET schrack_last_terms_confirmed = 0 WHERE schrack_s4s_id > ''";
            $writeConnection->query($sql);
        }

        // call notifications:
        $helper = Mage::helper('s4s');
        $helper->newTermsOfUseProvided();

        echo 'done.'.PHP_EOL;
	}

    public function usageHelp() {
        return <<<USAGE
Usage:  php -f NewTermsOfUseForSchrack4students.php [reset_customer_flag]

USAGE;
    }
}

$shell = new Schracklive_Shell_NewTermsOfUseForSchrack4students();
$shell->run();
