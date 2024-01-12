<?php

class Schracklive_SchrackCatalog_Block_Navigation extends Mage_Catalog_Block_Navigation {
    protected function _construct() {
        parent::_construct();
        $this->addData(array(
            'cache_lifetime'    => 3600,
        ));
        $this->setCacheKey('catalog_navigation_'.$this->getStoreId().'_' . Mage::app()->getRequest()->getParam('schrackStrategicPillar').'_'.Mage::app()->getRequest()->getParam('id') . '_' . Mage::getSingleton('customer/session')->getCustomer()->getId());
    }

    /**
     * Enter description here...
     *
     * @return Mage_Catalog_Model_Category
     */
    public function getCurrentCategory()
    {
        $cat = Mage::registry('current_category');
        if ( $cat ) {
            return $cat;
        }
        if (Mage::getSingleton('catalog/layer')) {
           // $x = Mage::getSingleton('catalog/layer')->getCurrentCategory(); // Nagarro no use of this line
            return Mage::getSingleton('catalog/layer')->getCurrentCategory();
        }
        return false;
    }

}

?>
