<?php
class Customweb_PayUnityCw_Block_Adminhtml_System_Config_Link extends Mage_Adminhtml_Block_System_Config_Form_Field
{
	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
	{
		$url = Mage::helper('adminhtml')->getUrl('*/configpayunitycw/index');
		
		return '<a href="' . $url . '">' . Mage::helper('PayUnityCw')->__('Show Further Settings and Information') . '</a>';
	}
}
