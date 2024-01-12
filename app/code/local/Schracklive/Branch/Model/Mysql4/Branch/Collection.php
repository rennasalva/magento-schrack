<?php

class Schracklive_Branch_Model_Mysql4_Branch_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {

	public function _construct() {
		parent::_construct();
		$this->_init('branch/branch');
	}

	public function setWarehouseFilter($warehouseId) {
		$this->addFieldToFilter('warehouse_id', $warehouseId);
		return $this;
	}

}
