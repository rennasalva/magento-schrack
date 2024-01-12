<?php

class Schracklive_Branch_Model_Branch extends Mage_Core_Model_Abstract {

	public function _construct() {
		parent::_construct();
		$this->_init('branch/branch');
	}
	
	public function loadByBranchId($branchId) {
		$this->_getResource()->loadByBranchId($this, $branchId);
		return $this;
	}

}

?>