<?php

class Schracklive_Wws_Model_Mysql4_Signal_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {

	protected function _construct() {
		$this->_init('wws/signal');
	}

}
