<?php

class Schracklive_Wws_Model_Mysql4_Signal extends Mage_Core_Model_Mysql4_Abstract {

	protected function _construct() {
		$this->_init('wws/signal', 'signal_id');
	}

}
