<?php

class Schracklive_SchrackCatalog_Model_Filedata extends Mage_Core_Model_Abstract {

	protected function _construct() {
		parent::_construct();
		$this->_init('schrackcatalog/filedata');
	}

	public function loadByUrl($url) {
		$this->_getResource()->loadByUrl($this, $url);

		return $this;
	}

}

?>