<?php

class Schracklive_Account_Block_Adminhtml_Account_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
	protected function _prepareForm()
	{
		$form = new Varien_Data_Form();
		$this->setForm($form);
		$fieldset = $form->addFieldset('account_form', array('legend'=>Mage::helper('account')->__('Account information')));

		$fieldset->addField('wws_customer_id', 'text', array(
				'label'     => Mage::helper('account')->__('WWS Customer Id'),
				'required'  => true,
				'name'      => 'wws_customer_id',
			));
		$fieldset->addField('wws_branch_id', 'text', array(
				'label'     => Mage::helper('account')->__('WWS Branch Id'),
				'required'  => true,
				'name'      => 'wws_branch_id',
			));
		$fieldset->addField('prefix', 'text', array(
				'label'     => Mage::helper('account')->__('Prefix'),
				'required'  => true,
				'name'      => 'prefix',
			));
		$fieldset->addField('name1', 'text', array(
				'label'     => Mage::helper('account')->__('Name 1'),
				'required'  => true,
				'name'      => 'name1',
			));
		$fieldset->addField('name2', 'text', array(
				'label'     => Mage::helper('account')->__('Name 2'),
				'required'  => false,
				'name'      => 'name2',
			));
		$fieldset->addField('name3', 'text', array(
				'label'     => Mage::helper('account')->__('Name 3'),
				'required'  => false,
				'name'      => 'name3',
			));
		$fieldset->addField('street', 'text', array(
				'label'     => Mage::helper('account')->__('Street'),
				'required'  => false,
				'name'      => 'street',
			));
		$fieldset->addField('postcode', 'text', array(
				'label'     => Mage::helper('account')->__('Postcode'),
				'required'  => false,
				'name'      => 'postcode',
			));
		$fieldset->addField('city', 'text', array(
				'label'     => Mage::helper('account')->__('City'),
				'required'  => true,
				'name'      => 'city',
			));
		$fieldset->addField('country_id', 'text', array(
				'label'     => Mage::helper('account')->__('Country'),
				'required'  => true,
				'name'      => 'country_id',
			));
		$fieldset->addField('advisor_principal_name', 'text', array(
				'label'     => Mage::helper('account')->__('Advisor'),
				'required'  => true,
				'name'      => 'advisor_principal_name',
			));
		$fieldset->addField('advisors_principal_names', 'textarea', array(
				'label'     => Mage::helper('account')->__('Additional Advisors'),
				'required'  => false,
				'name'      => 'advisors_principal_names',
			));
		$fieldset->addField('gtc_accepted', 'checkbox', array(
				'label'     => Mage::helper('account')->__('General Terms and Conditions Accepted?'),
				'required'  => false,
				'name'      => 'gtc_accepted',
			));
		$data = array();
		if (Mage::getSingleton('adminhtml/session')->getAccountData()) {
			$data = Mage::getSingleton('adminhtml/session')->getAccountData();
			Mage::getSingleton('adminhtml/session')->setAccountData(null);
		} elseif (Mage::registry('account_account_data')) {
			$data = Mage::registry('account_account_data')->getData();
		}
		$form->setValues($data);
		/*
		$fieldset->addField('gtc_accepted_false', 'hidden', array(
				'name'      => 'gtc_accepted',
				'value'     => 0,
			));
		*/
		if (isset($data['gtc_accepted']) && isset($data['gtc_accepted'])) {
			$form->getElement('gtc_accepted')->setIsChecked(true);
		}

		return parent::_prepareForm();
	}

}
