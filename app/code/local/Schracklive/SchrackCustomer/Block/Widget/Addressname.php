<?php

class Schracklive_SchrackCustomer_Block_Widget_Addressname extends Mage_Customer_Block_Widget_Name {

	public function _construct() {
		parent::_construct();

		// default template location
		$this->setTemplate('customer/widget/addressname.phtml');
	}

	public function getClassName() {
		if (!$this->hasData('class_name')) {
			$this->setData('class_name', 'customer-addressname');
		}
		return $this->getData('class_name');
	}

}
