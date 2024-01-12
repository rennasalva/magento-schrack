<?php

class Schracklive_Translation_Model_Translation extends Mage_Core_Model_Abstract {

	public function _construct() {
		parent::_construct();
		$this->_init('translation/translation');
	}

}

?>