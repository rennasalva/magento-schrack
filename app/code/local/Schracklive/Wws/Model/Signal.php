<?php

class Schracklive_Wws_Model_Signal extends Mage_Core_Model_Abstract {

	protected $_eventPrefix = 'wws_signal';
	protected $_eventObject = 'signal';

	protected function _construct() {
		$this->_init('wws/signal');
	}

}
