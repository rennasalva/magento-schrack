<?php

class Schracklive_SchrackWishlist_Block_Endcustomerpartslist_View extends Schracklive_SchrackWishlist_Block_Endcustomerpartslist_Abstract {
    private $_collection;

    /*
     * Constructor of block
     */
    public function _construct()
    {
        $rv = parent::_construct();
        $this->_collection = null;
        return $rv;
    }

    public function getPartslistItems($partslist)
    {
        if (is_null($this->_collection)) {
            $this->_collection = $partslist
                ->getItemCollection()
                ->addStoreFilter();
        }

        return $this->_collection;
    }

    public function getPartslist() {
        return Mage::helper('schrackwishlist/endcustomerpartslist')->getPartslist();
    }

    public function getCatalogName($item) {
        $ref = $item->getReferrerUrl();
        $ref = preg_replace('#\?.+$#', '', trim($ref));
        $model = Mage::getModel('schrackwishlist/endcustomerpartslist_catalog')->loadByUrl($ref);
        return $model->getName();
    }
} 