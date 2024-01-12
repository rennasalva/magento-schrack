<?php

class Schracklive_SchrackWishlist_Block_Endcustomerpartslist_Catalogs extends Schracklive_SchrackWishlist_Block_Endcustomerpartslist_Abstract {
    private $_catalogCollection;
    private $_categoryCollection;

    public function _construct()
    {
        $rv = parent::_construct();
        $this->_catalogCollection = array();
        $this->_categoryCollection = null;
        return $rv;
    }

    public function getCatalogs($categoryId)
    {
        $model = Mage::getModel('schrackwishlist/endcustomerpartslist_catalog');
        if ( !isset($this->_catalogCollection[$categoryId]) ) {
            $this->_catalogCollection[$categoryId] = $model
                ->getCollection();
        }
        $this->_catalogCollection[$categoryId]->addFieldToFilter('category_id', array('=' => $categoryId));

        return $this->_catalogCollection[$categoryId];
    }
    public function getCategories()
    {
        $model = Mage::getModel('schrackwishlist/endcustomerpartslist_category');
        if (is_null($this->_categoryCollection)) {
            $this->_categoryCollection = $model
                ->getCollection();
        }

        return $this->_categoryCollection;
    }

    public function getPartslist() {
        return Mage::helper('schrackwishlist/endcustomerpartslist')->getPartslist();
    }
} 