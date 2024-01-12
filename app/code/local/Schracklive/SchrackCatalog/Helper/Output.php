<?php

class Schracklive_SchrackCatalog_Helper_Output extends Mage_Catalog_Helper_Output {
    /**
     * Prepare product attribute html output
     *
     * @param   Mage_Catalog_Model_Product $product
     * @param   string $attributeHtml
     * @param   string $attributeName
     * @return  string
     */
    public function productAttribute ( $product, $attributeHtml, $attributeName ) {
        $res = parent::productAttribute($product,$attributeHtml,$attributeName);
        if ( $attributeName == 'schrack_ean' ) {
            $res = str_replace(chr(31),', ',$res);
        }
        return $res;
    }
}

