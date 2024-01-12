<?php

class Schracklive_Translation_Block_Adminhtml_Translation_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs {

	public function __construct() {
		parent::__construct();
		$this->setId('translation_tabs');
		$this->setDestElementId('edit_form');
		$this->setTitle(Mage::helper('translation')->__('Item Information'));
	}

	protected function _beforeToHtml() {
		$this->addTab('form_section', array(
			'label' => Mage::helper('translation')->__('Item Information'),
			'title' => Mage::helper('translation')->__('Item Information'),
			'content' => $this->getLayout()->createBlock('translation/adminhtml_translation_edit_tab_form')->toHtml(),
		));

		return parent::_beforeToHtml();
	}

}

?>