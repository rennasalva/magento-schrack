<?php

class Schracklive_Branch_Block_Adminhtml_Branch extends Mage_Adminhtml_Block_Widget_Grid_Container {

	public function __construct() {
		$this->_controller = 'adminhtml_branch';
		$this->_blockGroup = 'branch';
		$this->_headerText = Mage::helper('branch')->__('Branch to Warehouse Mapping');
		parent::__construct();
	}

}

?>