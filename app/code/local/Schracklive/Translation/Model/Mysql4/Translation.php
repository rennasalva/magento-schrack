<?php

class Schracklive_Translation_Model_Mysql4_Translation extends Mage_Core_Model_Mysql4_Abstract {

	public function _construct() {
		// Note that the translation_id refers to the key field in your database table.
		$this->_init('translation/translation', 'translation_id');
	}

}

?>