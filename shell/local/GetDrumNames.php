<?php

require_once 'shell.php';

class Schracklive_Shell_GetDrumNames extends Schracklive_Shell {

    var $_readConnection = null;
    var $_cnt = 10;
    var $_requestSize = 100;
    var $_minItemCnt = 2;

    function __construct() {
        parent::__construct();
        $this->_readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
    }

    public function run() {
        $res = array();
        $productHelper = Mage::helper('schrackcatalog/product');
        $stockHelper = Mage::helper('schrackcataloginventory/stock');
        $stockNo = $stockHelper->getLocalDeliveryStock()->getStockNumber();
        $stockNos = array($stockNo);
        $sql = "SELECT sku FROM catalog_product_entity WHERE schrack_is_cable = 1";
        $skus = $this->_readConnection->fetchCol($sql);
        $found = 0;
        for ( $i = 0, $len = count($skus); $i < $len; $i += $this->_requestSize ) {
            $requestSkus = array();
            for ( $j = 0; $j < $this->_requestSize; $j++ ) {
                $requestSkus[] = $skus[$i + $j];
            }
            $allDrums = $productHelper->getDrumsBySkusAndStocks($requestSkus,$stockNos);
            sleep(1);
            $foundSkus = array();
            foreach ( $allDrums as $sku => $drums ) {
                foreach ( $drums['possible'][$stockNo] as $drum ) {
                    $res[$drum->getName()] = $drum->getDescription();
                }
            }
        }
        foreach ( $res as $name => $description ) {
            echo $name . '  ;  ' .  $description . PHP_EOL;
        }
    }
}

$shell = new Schracklive_Shell_GetDrumNames();
$shell->run();
