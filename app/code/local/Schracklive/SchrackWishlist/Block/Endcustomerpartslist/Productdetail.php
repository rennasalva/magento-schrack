<?php

class Schracklive_SchrackWishlist_Block_Endcustomerpartslist_Productdetail extends Schracklive_SchrackWishlist_Block_Endcustomerpartslist_Abstract {
    protected $_product = null;

    public function _construct()
    {
        $rv = parent::_construct();
        return $rv;
    }

    protected function getProduct() {
        if (!$this->_product) {
            $this->_product = Mage::registry('product');
        }
        return $this->_product;
    }
} 