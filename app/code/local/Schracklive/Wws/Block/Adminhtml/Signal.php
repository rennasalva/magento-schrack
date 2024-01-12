<?php

class Schracklive_Wws_Block_Adminhtml_Signal extends Mage_Adminhtml_Block_Widget_Grid_Container {

	public function __construct() {
		$this->_controller = 'adminhtml_signal';
		$this->_blockGroup = 'wws';
		$this->_headerText = Mage::helper('wws')->__('Signal Manager');
		$this->_addButtonLabel = Mage::helper('wws')->__('Add Signal');
		parent::__construct();
	}

}

?>