<?php

class Schracklive_Schrack_Model_Mysql4_Acl_Role_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {

	protected function _construct() {
		$this->_init('schrack/acl_role');
	}

	public function toOptionArray() {
		return $this->_toOptionArray('id', 'name');
	}

	public function setVisibleOnWebsiteFilter() {
		$this->addFieldToFilter('is_visible', 1);
		return $this;
	}

}
