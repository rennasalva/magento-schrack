<?php

class Schracklive_Branch_Helper_Data extends Mage_Core_Helper_Abstract {

	/**
	 *
	 * @param int $warehouseId
	 * @return Schracklive_Branch_Model_Branch 
	 */
	public function findBranch($warehouseId) {
		return Mage::getModel('branch/branch')->getResourceCollection()
						->setWarehouseFilter($warehouseId)
						->getFirstItem();
	}

}
