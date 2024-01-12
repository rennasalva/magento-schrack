<?php

class Schracklive_SchrackShipping_Helper_Delivery {

	public function getWarehouseId() {
		return $this->_getConfigData('id');
	}

	public function getShippingMethod(){
		return 'schrackdelivery_warehouse'.$this->getWarehouseId();
	}

	protected function _getConfigData($key) {
		return Mage::getStoreConfig('carriers/schrackdelivery/'.$key);
	}

}
