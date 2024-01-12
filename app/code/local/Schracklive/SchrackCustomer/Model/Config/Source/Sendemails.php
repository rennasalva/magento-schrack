<?php

class Schracklive_SchrackCustomer_Model_Config_Source_Sendemails {

	/**
	 * Options getter
	 *
	 * @return array
	 */
	public function toOptionArray() {
		return array(
			array('value' => 'no', 'label' => Mage::helper('schrackcustomer')->__('no')),
			array('value' => 'link', 'label' => Mage::helper('schrackcustomer')->__('confirmation link')),
			array('value' => 'password', 'label' => Mage::helper('schrackcustomer')->__('generated password')),
		);
	}

}
