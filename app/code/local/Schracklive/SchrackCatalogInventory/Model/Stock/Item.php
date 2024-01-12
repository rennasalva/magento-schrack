<?php

class Schracklive_SchrackCatalogInventory_Model_Stock_Item extends Mage_CatalogInventory_Model_Stock_Item {
    
    function loadByStockIdAndProductId ( $stockId, $productId ) {
        $this->setData(array());
        $this->_getResource()->loadByStockIdAndProductId($stockId, $productId, $this);
        $id = $this->getId();
        $res = isset($id) && ! is_null($id);
        return $res;
    }
    
    public function getStockId () {
        $res = Mage_Core_Model_Abstract::getStockId();
        if ( !isset($res) )
            $res = parent::getStockId();
        return $res;
    }

    // TODO: find reason why this hack is neccessary!
    public function setProduct ( $product ) {
        parent::setProduct($product);
        $stockId = 1;
        $this->setStockId($stockId);
        return $this;
    }
    
}

?>
