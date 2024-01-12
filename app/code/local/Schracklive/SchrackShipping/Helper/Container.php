<?php

class Schracklive_SchrackShipping_Helper_Container {

    public function getWarehouseId() {
        return $this->_getConfigData('id');
    }

    public function getShippingMethod() {
        return 'schrackcontainer_warehouse'.$this->getWarehouseId();
    }

    protected function _getConfigData($key) {
        return Mage::getStoreConfig('carriers/schrackcontainer/' . $key);
    }
}