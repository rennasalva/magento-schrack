<?php

class Schracklive_Schrack_Model_Mysql4_Acl_Role extends Mage_Core_Model_Mysql4_Abstract {

	protected function _construct() {
		$this->_init('schrack/acl_role', 'id');
	}

}
