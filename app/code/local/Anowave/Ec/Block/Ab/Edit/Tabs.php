<?php
class Anowave_Ec_Block_Ab_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
	public function __construct() 
	{
		parent::__construct();
		
		$this->setId('ab_tabs');
		$this->setDestElementId('edit_form');
		$this->setTitle
		(
			Mage::helper('ec')->__('Edit A/B Experiment')
		);
	}
	
	protected function _beforeToHtml() 
	{
		$this->addTab('general', array
		(
			'label' 	=> Mage::helper('ec')->__('Experiment options'),
			'content' 	=> $this->getLayout()->createBlock('ec/ab_edit_tabs_general')->toHtml()
		));
		
		return parent::_beforeToHtml();
	}
}