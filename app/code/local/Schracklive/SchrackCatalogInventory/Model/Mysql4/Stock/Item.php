<?php

class Schracklive_SchrackCatalogInventory_Model_Mysql4_Stock_Item extends Mage_CatalogInventory_Model_Mysql4_Stock_Item {
    
    public function loadByStockIdAndProductId ( $stockId, $productId, $item ) {
        $item->setStockId($stockId);
        return $this->loadByProductId($item,$productId);
    }
}

?>
