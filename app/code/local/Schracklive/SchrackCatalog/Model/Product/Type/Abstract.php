<?php

abstract class Schracklive_SchrackCatalog_Model_Product_Type_Abstract extends Mage_Catalog_Model_Product_Type_Abstract {
    
    /**
     * Check is product available for sale
     *
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    public function isSalable($product = null)
    {
        $salable = $this->getProduct($product)->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_ENABLED;

        if ( $salable && $this->getProduct($product)->getData('is_salable') != null ) {
            $salable = $this->getProduct($product)->getData('is_salable');
        }
        elseif ($salable && $this->isComposite()) {
            $salable = null;
        }

        return (boolean) (int) $salable;
    }
}