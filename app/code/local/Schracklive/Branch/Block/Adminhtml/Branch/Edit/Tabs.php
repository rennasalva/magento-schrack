<?php

class Schracklive_Branch_Block_Adminhtml_Branch_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs {

	public function __construct() {
		parent::__construct();
		$this->setId('branch_tabs');
		$this->setDestElementId('edit_form');
		$this->setTitle(Mage::helper('branch')->__('Item Information'));
	}

	protected function _beforeToHtml() {
		$this->addTab('form_section', array(
			'label' => Mage::helper('branch')->__('Item Information'),
			'title' => Mage::helper('branch')->__('Item Information'),
			'content' => $this->getLayout()->createBlock('branch/adminhtml_branch_edit_tab_form')->toHtml(),
		));

		return parent::_beforeToHtml();
	}

}

?>