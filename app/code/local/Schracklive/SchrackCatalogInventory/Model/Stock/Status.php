<?php

class Schracklive_SchrackCatalogInventory_Model_Stock_Status extends Mage_CatalogInventory_Model_Stock_Status
{
    
    /**
     * Add information about stock status to product collection
     *
     * @param   Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection $productCollection
     * @param   int|null $websiteId
     * @param   int|null $stockId
     * @return  Mage_CatalogInventory_Model_Stock_Status
     */
    public function addStockStatusToProducts($productCollection, $websiteId = null, $stockId = null)
    {
        if ($stockId === null) {
            $stockId = Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID;
        }
        if ($websiteId === null) {
            $websiteId = Mage::app()->getStore()->getWebsiteId();
            if ((int)$websiteId == 0 && $productCollection->getStoreId()) {
                $websiteId = Mage::app()->getStore($productCollection->getStoreId())->getWebsiteId();
            }
        }
        $productIds = array();
        foreach ($productCollection as $product) {
            $productIds[] = $product->getId();
        }

        if (!empty($productIds)) {
            $stockStatuses = $this->_getResource()->getProductStatus($productIds, $websiteId, $stockId);
            foreach ($stockStatuses as $productId => $status) {
                if ($product = $productCollection->getItemById($productId)) {
                    $product->setIsSalable($status);
                }
            }
        }

        foreach ($productCollection as $product) {
            // $object = new Varien_Object(array('is_in_stock' => $product->getData('is_salable')));
            $object = Mage::getModel('cataloginventory/stock_item');
            $object->setData('is_in_stock',0);
            $object->setIsInStock($product->getData('is_salable'));
            $product->setStockItem($object);
        }

        return $this;
    }
}

?>
