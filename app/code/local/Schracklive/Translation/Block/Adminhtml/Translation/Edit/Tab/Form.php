<?php

class Schracklive_Translation_Block_Adminhtml_Translation_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form {

	protected function _prepareForm() {
		$form = new Varien_Data_Form();
		$this->setForm($form);
		$fieldset = $form->addFieldset('translation_form', array('legend' => Mage::helper('translation')->__('Item information')));

		$fieldset->addField('string_translated', 'textarea', array(
			'label' => Mage::helper('translation')->__('Translated'),
			'class' => 'required-entry',
			'required' => true,
			'name' => 'string_translated',
		));

		if (Mage::getSingleton('adminhtml/session')->getTranslationData()) {
			$form->setValues(Mage::getSingleton('adminhtml/session')->getTranslationData());
			Mage::getSingleton('adminhtml/session')->setTranslationData(null);
		} elseif (Mage::registry('translation_data')) {
			$form->setValues(Mage::registry('translation_data')->getData());
		}
		return parent::_prepareForm();
	}

}

?>