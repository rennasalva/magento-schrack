<?php

class Schracklive_Account_Block_Adminhtml_Account_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

	public function __construct()
	{
		parent::__construct();

		$this->setId('account_tabs');
		$this->setDestElementId('edit_form');
		$this->setTitle(Mage::helper('account')->__('Account Information'));
	}

	protected function _beforeToHtml()
	{
		$this->addTab('form_section', array(
			'label'     => Mage::helper('account')->__('Account Information'),
			'title'     => Mage::helper('account')->__('Account Information'),
			'content'   => $this->getLayout()->createBlock('account/adminhtml_account_edit_tab_form')->toHtml(),
		));

		return parent::_beforeToHtml();
	}

}
