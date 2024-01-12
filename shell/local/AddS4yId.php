<?php

require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_AddS4yId extends Mage_Shell_Abstract {

    function __construct() {
        parent::__construct();
    }

	public function run() {
        $shopCountry = strtoupper(Mage::getStoreConfig('schrack/general/country'));
	    $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $file = $this->getArg('file');
        if ( ! $file ) {
            die($this->usageHelp());
        }
	    $verbose = $this->getArg('verbose');
        $cols = null;
        $cntAdded = $cntNotAdded = 0;
        $fp = fopen($file,"r");
        while ( $csv = fgetcsv($fp,0,';','"') ) {
            if ( ! $cols ) {
                $cols = array();
                foreach ( $csv as $k => $v ) {
                    $cols[$v]= $k;
                }
            } else {
                // "contact_uuid";"email_address";"wws_cust_id";"www_contact_nr";"s4y_shop";"acct_deleted";"cont_deleted";"shop_status"
                $country = strtoupper($csv[$cols['s4y_shop']]);
                if ( $country == $shopCountry ) {
                    $s4yId = $csv[$cols['contact_uuid']];
                    $email = $csv[$cols['email_address']];
                    $sql = "SELECT count(entity_id) FROM customer_entity WHERE email = ?";
                    $cnt = $readConnection->fetchOne($sql,$email);
                    if ( $cnt != 1 ) {
                        if ( $verbose ) {
                            echo PHP_EOL . "Email '$email' not found in shop $country !" . PHP_EOL;
                        } else {
                            echo 'x';
                        }
                        ++$cntNotAdded;
                    } else {
                        $sql = "UPDATE customer_entity SET schrack_s4y_id = ? WHERE email = ?";
                        $writeConnection->query($sql,array($s4yId,$email));
                        echo '.';
                        ++$cntAdded;
                    }
                }
            }
        }
        fclose($fp);

        $cntAll = $cntAdded + $cntNotAdded;
		echo PHP_EOL . "$cntAll records got for shop $shopCountry, $cntAdded added, $cntNotAdded where not found." . PHP_EOL;
		echo 'done.' . PHP_EOL;
    }

    public function usageHelp()
    {
        return <<<USAGE
Usage:  sudo php -f AddS4yId.php --file <csv file> [--verbose]



USAGE;
    }
}

$shell = new Schracklive_Shell_AddS4yId();
$shell->run();
