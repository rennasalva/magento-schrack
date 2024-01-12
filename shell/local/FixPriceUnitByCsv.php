<?php

require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_FixPriceUnitByCsv extends Mage_Shell_Abstract {

    private $_websiteIds;
    private $_csvFile = null;

    function __construct() {
        parent::__construct();
        $this->_websiteIds = array(Mage::app()->getStore(true)->getWebsite()->getId());

        $x = $this->getArg('csv_file');
        if ( $x ) {
            $this->_csvFile = $x;
        }

        if ( $this->_csvFile == null ) die("usage: php  FixPriceUnitByCsv.php --csv_file <filename>");
    }
    
	public function run() {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        if (($handle = fopen($this->_csvFile, "r")) !== FALSE) {
            while ( ($data = fgetcsv($handle, 1000, ";")) !== FALSE ) {
                $sku = $data[0];
                $pu = $data[1];
                if ( intval($pu) > 1 ) {
                    echo $sku . " - " . intval($pu) . PHP_EOL;
                    $sql = "UPDATE catalog_product_entity SET schrack_priceunit = $pu WHERE sku = '$sku';";
                    echo  $sql . PHP_EOL;
                    $connection->query($sql);
                }
            }
            fclose($handle);
        }
	}

}

$shell = new Schracklive_Shell_FixPriceUnitByCsv();
$shell->run();
