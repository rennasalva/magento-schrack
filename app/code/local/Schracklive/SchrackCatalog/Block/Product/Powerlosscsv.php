<?php

class Schracklive_SchrackCatalog_Block_Product_Powerlosscsv extends Mage_Core_Block_Template {

    public function getProductCollection () {
        $collection = Mage::getModel('catalog/product')->getCollection();
        $collection->addAttributeToSelect(array('schrack_verlustleistung','name'));
        $collection->addAttributeToFilter('schrack_verlustleistung',array('gt' => ' '));
        $collection->addAttributeToFilter('schrack_sts_statuslocal',array('nin' => array('tot','strategic_no','unsaleable')));
        return $collection;
    }
}
