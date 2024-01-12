<?php

class Schracklive_Account_Block_Adminhtml_Account_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

	public function __construct()
	{
		parent::__construct();
		$this->setId('accountGrid');
		$this->setUseAjax(true);
		$this->setDefaultSort('account_id');
		$this->setSaveParametersInSession(true);
	}

	protected function _prepareCollection()
	{
/*
		$collection = Mage::getResourceModel('customer/customer_collection')
			->addNameToSelect()
			->addAttributeToSelect('email')
			->addAttributeToSelect('created_at')
			->addAttributeToSelect('group_id')
			->joinAttribute('billing_postcode', 'customer_address/postcode', 'default_billing', null, 'left')
			->joinAttribute('billing_city', 'customer_address/city', 'default_billing', null, 'left')
			->joinAttribute('billing_telephone', 'customer_address/telephone', 'default_billing', null, 'left')
			->joinAttribute('billing_region', 'customer_address/region', 'default_billing', null, 'left')
			->joinAttribute('billing_country_id', 'customer_address/country_id', 'default_billing', null, 'left');
*/
		$collection = Mage::getModel('account/account')->getCollection();
		$this->setCollection($collection);

		return parent::_prepareCollection();
	}

	protected function _prepareColumns()
	{
		$this->addColumn('account_id', array(
			'header'    => Mage::helper('account')->__('ID'),
			'width'     => '50px',
			'index'     => 'account_id',
			'type'  => 'number',
		));
		$this->addColumn('wws_customer_id', array(
			'header'    => Mage::helper('account')->__('WWS Customer Id'),
			'width'     => '50px',
			'index'     => 'wws_customer_id',
		));
		$this->addColumn('name1', array(
			'header'    => Mage::helper('account')->__('Name 1'),
			'width'     => '50px',
			'index'     => 'name1',
		));
		$this->addColumn('postcode', array(
			'header'    => Mage::helper('account')->__('Postcode'),
			'width'     => '30px',
			'index'     => 'postcode',
		));
		$this->addColumn('city', array(
			'header'    => Mage::helper('account')->__('City'),
			'width'     => '50px',
			'index'     => 'city',
		));
		$this->addColumn('country_id', array(
			'header'    => Mage::helper('account')->__('Country'),
			'width'     => '50px',
			'index'     => 'country_id',
		));
		$this->addColumn('advisor_principal_name', array(
			'header'    => Mage::helper('account')->__('Advisor'),
			'width'     => '50px',
			'index'     => 'advisor_principal_name',
		));
	}

	public function getGridUrl()
	{
		return $this->getUrl('*/*/grid', array('_current'=> true));
	}

	public function getRowUrl($row)
	{
		return $this->getUrl('*/*/edit', array('id'=>$row->getId()));
	}
}
