<?php

class Schracklive_SchrackCheckout_Block_Onepage_Login extends Mage_Checkout_Block_Onepage_Login {

	protected function _construct() {
		if (!$this->isCustomerLoggedIn()) {
			$this->getCheckout()->setStepData('login', array('label' => Mage::helper('checkout')->__('Login'), 'allow' => true));
		}
		if ($this->hasData('template')) {
			$this->setTemplate($this->getData('template'));
		}
	}

}

?>
