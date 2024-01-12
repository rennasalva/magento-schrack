<?php

/**
 * Product search result block
 *
 * @category   Schracklive
 * @package    Schracklive_SchrackCatalogSearch
 * @module     Catalog
 */
class Schracklive_SchrackCatalogSearch_Block_Result extends Mage_CatalogSearch_Block_Result {

    /**
     * Set search available list orders
     *
     * @return Mage_CatalogSearch_Block_Result
     */
    public function setListOrders() {
        $category = Mage::getSingleton('catalog/layer')
            ->getCurrentCategory();
        /* @var $category Mage_Catalog_Model_Category */
        $availableOrders = $category->getAvailableSortByOptions();
        unset($availableOrders['position']);

        $this->getListBlock()
            ->setAvailableOrders($availableOrders)
            ->setDefaultDirection('desc');

        return $this;
    }

}

?>