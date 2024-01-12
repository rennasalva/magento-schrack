<?php

class Schracklive_Translation_Model_Mysql4_Translation_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {

	public function _construct() {
		parent::_construct();
		$this->_init('translation/translation');
	}

}

?>