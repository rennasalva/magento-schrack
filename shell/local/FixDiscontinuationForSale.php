<?php

require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_FixDiscontinuationForSale extends Mage_Shell_Abstract {

    private $_websiteIds;
    private $_writeConnection;
    private $_catalogCategoryProductTabName;


    function __construct() {
        parent::__construct();
        $this->_websiteIds = array(Mage::app()->getStore(true)->getWebsite()->getId());
        $this->_writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $this->_catalogCategoryProductTabName = Mage::getSingleton('core/resource')->getTableName('catalog_category_product');
    }
    
	public function run() {
        $productCollection = Mage::getModel('catalog/product')->getCollection()
            ->addFieldToFilter('schrack_sts_forsale',array('eq' => 1))
            ->addFieldToFilter('schrack_sts_statuslocal',array('neq' => 'tot'))
            ->load();
        $cntAll = count($productCollection);
        $cnt = 0;
        foreach ( $productCollection as $product ) {
            echo $product->getSku() . PHP_EOL;
            if ( $this->doHandleProduct($product) ) {
                ++$cnt;
            }
        }
        echo "$cnt/$cntAll products done." . PHP_EOL;
	}

    private function doHandleProduct ( Schracklive_SchrackCatalog_Model_Product $product ) {
        $ids = array();
        $cats = $product->getCategoryIds();
        foreach ( $cats as $category_id ) {
            $cat = Mage::getModel('catalog/category')->load($category_id) ;
            echo '    ' . $cat->getSchrackGroupId() . PHP_EOL;
            $id = $cat->getSchrackGroupId();
            $idParts = explode('-',$id);
            $mainId = $idParts[0];
            if ( ! isset($ids[$mainId]) ) {
                $ids[$mainId] = true;
            }
            if ( $idParts[1] == '999' ) {
                $ids[$mainId] = false;
            }
        }
        $done = false;
        foreach ( $ids as $mainId => $x ) {
            if ( $x ) {
                $cat = Mage::getModel('catalog/category')->loadByAttribute('schrack_group_id',$mainId . '-999');
                $this->addRef($cat,$product);
                $done = true;
            }
        }
        return $done;
    }

    private function addRef ( Schracklive_SchrackCatalog_Model_Category $cat, Schracklive_SchrackCatalog_Model_Product $product ) {
        $categoryId = $cat->getEntityId();
        $productId = $product->getEntityId();
        $position = 0;
        $insertQuery = "INSERT INTO `$this->_catalogCategoryProductTabName` VALUES ('$categoryId', '$productId', '$position')";
        echo '  ' . $insertQuery . PHP_EOL;
        $this->_writeConnection->query($insertQuery);
    }
}

$shell = new Schracklive_Shell_FixDiscontinuationForSale();
$shell->run();
