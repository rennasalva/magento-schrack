<?php

class Schracklive_Wws_Block_Adminhtml_Signal_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs {

	public function __construct() {
		parent::__construct();
		$this->setId('signal_tabs');
		$this->setDestElementId('edit_form');
		$this->setTitle(Mage::helper('wws')->__('WWS Message Information'));
	}

	protected function _beforeToHtml() {
		$this->addTab('form_section', array(
			'label' => Mage::helper('wws')->__('WWS Message Information'),
			'content' => $this->getLayout()->createBlock('wws/adminhtml_signal_edit_tab_form')->toHtml(),
		));
		$this->addTab('change_form_section', array(
			'label' => Mage::helper('wws')->__('Order Review Actions'),
			'content' => $this->getLayout()->createBlock('wws/adminhtml_signal_edit_tab_actionform')->setMethod('insert_update_response')->toHtml(),
		));
		$this->addTab('ship_form_section', array(
			'label' => Mage::helper('wws')->__('Place Order Actions'),
			'content' => $this->getLayout()->createBlock('wws/adminhtml_signal_edit_tab_actionform')->setMethod('ship_order')->toHtml(),
		));

		return parent::_beforeToHtml();
	}

}
