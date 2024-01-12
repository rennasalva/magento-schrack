<?php

require_once 'shell.php';

class Schracklive_Shell_FindCableLeavings extends Schracklive_Shell {

    var $_readConnection = null;
    var $_cnt = 10;
    var $_requestSize = 100;
    var $_minItemCnt = 2;

    function __construct() {
        parent::__construct();
        $this->_readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
    }

    public function run() {
        $productHelper = Mage::helper('schrackcatalog/product');
        $stockHelper = Mage::helper('schrackcataloginventory/stock');
        $stockNo = $stockHelper->getLocalDeliveryStock()->getStockNumber();
        $stockNos = array($stockNo);
        $sql = "SELECT sku FROM catalog_product_entity WHERE schrack_is_cable = 1";
        $skus = $this->_readConnection->fetchCol($sql);
        $found = 0;
        for ( $i = 0, $len = count($skus); $i < $len && $found < $this->_cnt; $i += $this->_requestSize ) {
            $requestSkus = array();
            for ( $j = 0; $j < $this->_requestSize; $j++ ) {
                $requestSkus[] = $skus[$i + $j];
            }
            $allDrums = $productHelper->getDrumsBySkusAndStocks($requestSkus,$stockNos);
            $foundSkus = array();
            foreach ( $allDrums as $sku => $drums ) {
                foreach ( $drums['available'][$stockNo] as $drum ) {
                    if ( $drum->getStockQty() > 0 && $drum->getStockQty() < $drum->getSize() ) {
                        if ( ! isset($foundSkus[$sku]) ) {
                            $foundSkus[$sku] = array();
                        }
                        $foundSkus[$sku][] = $drum->getName() . '/' . $drum->getStockQty();
                    }
                }
            }
            foreach ( $foundSkus as $sku => $items ) {
                if ( count($items) >= $this->_minItemCnt ) {
                    echo $sku . '    ' . implode(' ', $items) . PHP_EOL;
                    $found++;
                    if ( $found >= $this->_cnt ) {
                        break;
                    }
                }
            }
        }
        echo 'done.' . PHP_EOL;
    }
}

$shell = new Schracklive_Shell_FindCableLeavings();
$shell->run();
