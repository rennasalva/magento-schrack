<?php

require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_FixNewProducts extends Mage_Shell_Abstract {

    private $_websiteIds;
    
    function __construct() {
        parent::__construct();
        $this->_websiteIds = array(Mage::app()->getStore(true)->getWebsite()->getId());
    }
    
	public function run() {
        $productCollection = Mage::getModel('catalog/product')->getCollection()->load();
        $cnt = 0;
        foreach ( $productCollection as $product ) {
            if ( $this->doHandleProduct($product) ) {
                ++$cnt;
            }
        }
        echo "$cnt products done." . PHP_EOL;
	}

    private function doHandleProduct ( Schracklive_SchrackCatalog_Model_Product $product ) {
        $oldIds = $product->getWebsiteIds();
        $stockChanged = $this->ensureDefaultStock($product);
        $changeWsIDs = $this->arraysNotEqual($oldIds,$this->_websiteIds);
        if ( $changeWsIDs ) {
            $product->setWebsiteIds($this->_websiteIds);
        }
        if ( $changeWsIDs || $stockChanged ) {
            $product->save();
            echo $product->getSku() . ' - changed' . PHP_EOL;
            return true;
        }
        return false;
    }
    
    private function ensureDefaultStock ( Schracklive_SchrackCatalog_Model_Product $product ) {
        $stockId = 1;
        $productId = $product->getId();
        $stockItemModel = Mage::getModel('cataloginventory/stock_item');
        $itemExists = $stockItemModel->loadByStockIdAndProductId($stockId,$productId);
        if ( ! $itemExists ) {
            $stockItemModel->setTypeId('simple');
            $stockItemModel->setStockId($stockId);
            $stockItemModel->setProductId($productId);
            $stockItemModel->setIsInStock(1);
            $stockItemModel->setQty(99999.0);
            $stockItemModel->save();
            return true;
        }
        return false;
    }

    private function arraysNotEqual ( $a, $b ) {
        $cnt = count($a);
        if ( $cnt != count($b) ) {
            return true;
        }
        if ( $cnt === 0 ) {
            return false;
        }
        for ( $i = 0; $i < $cnt; ++$i ) {
            if ( $a[$i] !== $b[$i] ) {
                return true;
            }
        }
        return false;
    }
}

$shell = new Schracklive_Shell_FixNewProducts();
$shell->run();
