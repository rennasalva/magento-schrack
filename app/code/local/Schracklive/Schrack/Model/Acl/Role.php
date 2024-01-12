<?php

class Schracklive_Schrack_Model_Acl_Role extends Mage_Core_Model_Abstract {

	protected $_eventPrefix = 'schrack_acl_role';
	protected $_eventObject = 'schrack_acl_role';

	protected function _construct() {
		$this->_init('schrack/acl_role');
	}

}
