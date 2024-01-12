<?php

class Schracklive_Branch_Model_Mysql4_Branch extends Mage_Core_Model_Mysql4_Abstract {

	public function _construct() {
		// Note that the branch_id refers to the key field in your database table.
		$this->_init('branch/branch', 'entity_id');
	}
	
	public function loadByBranchId(Schracklive_Branch_Model_Branch $branch, $branchId) {
		$this->load($branch, $branchId, 'branch_id');

		return $this;
	}

}

?>