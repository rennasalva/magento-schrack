<?php

require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_AddQtyUnitId extends Mage_Shell_Abstract {

    function __construct() {
        parent::__construct();
    }

	public function run() {
	    $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $file = $this->getArg('file');
        if ( ! $file ) {
            die($this->usageHelp());
        }
        $fp = fopen($file,"r");
        while ( $csv = fgetcsv($fp,0,'	') ) {
            $sql = "UPDATE catalog_product_entity SET schrack_qtyunit_id = ? WHERE sku = ?";
            $writeConnection->query($sql,array($csv[1],$csv[0]));
            echo '.';
        }
        fclose($fp);

		echo PHP_EOL . 'done.' . PHP_EOL;
    }

    public function usageHelp()
    {
        return <<<USAGE
Usage:  sudo php -f AddQtyUnitId.php --file <csv file>



USAGE;
    }
}

$shell = new Schracklive_Shell_AddQtyUnitId();
$shell->run();
