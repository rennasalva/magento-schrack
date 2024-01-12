<?php

class Schracklive_Wws_Block_Adminhtml_Signal_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form {

	protected function _prepareForm() {
		$form = new Varien_Data_Form();
		$this->setForm($form);
		$fieldset = $form->addFieldset('signal_form', array('legend' => Mage::helper('wws')->__('WWS Message Information')));

		$fieldset->addField('code', 'text', array(
			'label' => Mage::helper('wws')->__('Code'),
			'class' => 'required-entry',
			'required' => true,
			'name' => 'code',
		));

		$fieldset->addField('wws_message', 'text', array(
			'label' => Mage::helper('wws')->__('WWS Message'),
			'class' => 'required-entry',
			'required' => true,
			'name' => 'wws_message',
		));

		$fieldset->addField('message', 'text', array(
			'label' => Mage::helper('wws')->__('Message'),
			'required' => false,
			'name' => 'message',
		));

		if (Mage::getSingleton('adminhtml/session')->getWebData()) {
			$form->setValues(Mage::getSingleton('adminhtml/session')->getWebData());
			Mage::getSingleton('adminhtml/session')->setWebData(null);
		} elseif (Mage::registry('signal_data')) {
			$form->setValues(Mage::registry('signal_data')->getData());
		}
		return parent::_prepareForm();
	}

}
