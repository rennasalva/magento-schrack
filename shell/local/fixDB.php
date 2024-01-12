<?php

require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_fixDB extends Mage_Shell_Abstract {

	var $deletedOrderIDs = array();
	var $counts = array();
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
        $countryCode = strtolower(Mage::getStoreConfig('schrack/general/country'));
        echo $countryCode . PHP_EOL;

        $sql = "DELETE FROM catalog_category_product_index;";
        $this->_writeConnection->query($sql);
        $sql = " INSERT INTO catalog_category_product_index (category_id, product_id, position, is_parent, store_id, visibility)"
             . " SELECT category_id, product_id, position, 1, ?, 4 FROM catalog_category_product;";
        $this->_writeConnection->query($sql,array($this->_storeId));
        $sql = " SELECT ccp.category_id, ccp.product_id, cat.path FROM catalog_category_product ccp"
             . " JOIN catalog_category_entity cat ON cat.entity_id = ccp.category_id;";
        $res = $this->_readConnection->fetchAll($sql);
        foreach ( $res as $row ) {
            $categoryID = $row['category_id'];
            if ( $categoryID == 54850 ) {
                echo '';
            }
            $productID = $row['product_id'];
            $parentCatIDs = substr($row['path'],2);
            $parentCatIDs = substr($parentCatIDs,0,strlen($parentCatIDs) - (strlen($categoryID) + 1));
            $parentCatIDs = str_replace('/',',',$parentCatIDs);
            $position = $productID;
            $sql = " INSERT INTO catalog_category_product_index (category_id, product_id, position, is_parent, store_id, visibility)"
                 . " SELECT entity_id, ?, ?, 0, ?, 4 FROM catalog_category_entity WHERE entity_id in ($parentCatIDs) AND entity_id NOT IN (SELECT category_id FROM catalog_category_product_index WHERE category_id = entity_id AND product_id = ?);";
            $this->_writeConnection->query($sql,array($productID,$position,$this->_storeId,$productID ));
        }

		echo 'done.' . PHP_EOL;
	}

}

$shell = new Schracklive_Shell_fixDB();
$shell->run();
