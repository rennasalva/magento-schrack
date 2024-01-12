<?php

class Schracklive_SchrackShipping_Helper_Inpost {

    public function getWarehouseId() {
        return $this->_getConfigData('id');
    }

    public function getShippingMethod() {
        return 'schrackinpost_warehouse'.$this->getWarehouseId();
    }

    protected function _getConfigData($key) {
        return Mage::getStoreConfig('carriers/schrackinpost/' . $key);
    }
}