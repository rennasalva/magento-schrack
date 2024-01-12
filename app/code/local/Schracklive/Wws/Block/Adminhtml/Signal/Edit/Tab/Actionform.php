<?php

class Schracklive_Wws_Block_Adminhtml_Signal_Edit_Tab_Actionform extends Mage_Adminhtml_Block_Widget_Form {

	protected $_methodPrefix;
	protected $_tabLegend;

	public function setMethod($method) {
		$this->_methodPrefix = $method == 'ship_order' ? 'ship_' : 'change_';
		$this->_tabLegend = $method == 'ship_order' ? Mage::helper('wws')->__('Place Order') : Mage::helper('wws')->__('Order Review');
		return $this;
	}

	protected function _prepareForm() {
		$form = new Varien_Data_Form();
		$this->setForm($form);
		$fieldset = $form->addFieldset($this->_methodPrefix.'signal_form', array('legend' => $this->_tabLegend, 'class' => 'fieldset-wide'));

		$fieldset->addField($this->_methodPrefix.'recreate', 'select', array(
			'label' => Mage::helper('wws')->__('Get New Order Number'),
			'required' => false,
			'name' => $this->_methodPrefix.'recreate',
			'values' => array(0 => 'no', 1 => 'yes'),
		));

		$fieldset->addField($this->_methodPrefix.'drop', 'select', array(
			'label' => Mage::helper('wws')->__('Clear Cart'),
			'required' => false,
			'name' => $this->_methodPrefix.'drop',
			'values' => array(0 => 'no', 1 => 'yes'),
		));

		$fieldset->addField($this->_methodPrefix.'mail', 'select', array(
			'label' => Mage::helper('wws')->__('Send Mail'),
			'required' => false,
			'name' => $this->_methodPrefix.'mail',
			'values' => array(0 => 'no', 1 => 'yes'),
		));

		$fieldset->addField($this->_methodPrefix.'mail_subject', 'text', array(
			'label' => Mage::helper('wws')->__('Mail Subject'),
			'required' => false,
			'name' => $this->_methodPrefix.'mail_subject',
		));

		$fieldset->addField($this->_methodPrefix.'mail_body', 'textarea', array(
			'label' => Mage::helper('wws')->__('Mail Body'),
			'required' => false,
			'name' => $this->_methodPrefix.'mail_body',
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
