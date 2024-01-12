<?php

class Schracklive_Branch_Block_Adminhtml_Branch_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form {

	protected function _prepareForm() {
		$form = new Varien_Data_Form();
		$this->setForm($form);
		$fieldset = $form->addFieldset('branch_form', array('legend' => Mage::helper('branch')->__('Item information')));

		$fieldset->addField('branch_id', 'text', array(
			'label' => Mage::helper('branch')->__('Branch ID'),
			'class' => 'required-entry',
			'required' => true,
			'name' => 'branch_id',
		));
		
		$fieldset->addField('warehouse_id', 'text', array(
			'label' => Mage::helper('branch')->__('Warehouse ID'),
			'class' => 'required-entry',
			'required' => true,
			'name' => 'warehouse_id',
		));

		if (Mage::getSingleton('adminhtml/session')->getBranchData()) {
			$form->setValues(Mage::getSingleton('adminhtml/session')->getBranchData());
			Mage::getSingleton('adminhtml/session')->setBranchData(null);
		} elseif (Mage::registry('branch_data')) {
			$form->setValues(Mage::registry('branch_data')->getData());
		}
		return parent::_prepareForm();
	}

}

?>