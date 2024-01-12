<?php

class Schracklive_SchrackWishlist_Block_Partslist_Csv extends Schracklive_SchrackWishlist_Block_Partslist_Abstract {
    protected function getItems() {
        $partslist = Mage::helper('schrackwishlist/partslist')->getPartslist();
        $items = $this->getPartslistItems($partslist);
        $skus = array();
        $qtys = array();
        foreach ( $items as $item ) {
            $skus[] = $item->getProduct()->getSku();
            $qtys[] = $item->getQty();
        }
        $prices = Mage::helper('schrackcatalog/product')->getPriceProductInfo($skus,$qtys);
        foreach ( $items as $item ) {
            $sku = $item->getProduct()->getSku();
            $item->setSchrackBasicPrice($prices[$sku]['amount']);
        }
        return $items;
    }
}

?>
