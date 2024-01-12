<?php

require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_ShowVPEs4SKU extends Mage_Shell_Abstract {

	var $_readConnection = null;
	var $_writeConnection = null;
    var $_storeId = null;

    function __construct() {
        parent::__construct();
	    $this->_readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $this->_writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $this->_storeId = Mage::app()->getStore('default')->getStoreId();
    }

	public function run() {
        $sku = $this->getArg('sku');
        $sql = "SELECT value FROM catalog_product_entity_text attr"
             . " JOIN catalog_product_entity prod ON prod.entity_id = attr.entity_id"
             . " WHERE prod.sku = ? AND attr.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'schrack_vpes');";
        $ser = $this->_readConnection->fetchOne($sql,$sku);
        $val = unserialize($ser);
        // print_r($val);
        // var_dump($val);
        $this->printArray($val);
		echo 'done.' . PHP_EOL;
	}

	private function printArray ( $ar, $indent = '' ) {
        echo PHP_EOL;
        foreach ( $ar as $k => $v ) {
            echo $indent . $k . ' : ';
            if ( is_array($v) ) {
                $this->printArray($v,$indent . '  ');
            } else {
                echo $v . PHP_EOL;
            }
        }
    }

    public function usageHelp ()
    {
        return <<<USAGE

       php ShowVPEs4SKU.php --sku <sku>



USAGE;
    }
}

$shell = new Schracklive_Shell_ShowVPEs4SKU();
$shell->run();
