<?php

class Schracklive_SchrackCheckout_Block_Onepage_Review extends Mage_Checkout_Block_Onepage_Review
{
	protected function _construct()
	{
		$this->getCheckout()->setStepData('review', array(
			'label'     => Mage::helper('checkout')->__('Order Review'),
			'is_show'   => $this->isShow()
		));
		// parent::_construct(); => copy of code from ancestors
		// Mage_Checkout_Block_Onepage_Abstract - NOTHING
		// Mage_Core_Block_Template
		if ($this->hasData('template')) {
			$this->setTemplate($this->getData('template'));
		}
		// Mage_Core_Block_Abstract - EMPTY
		// Varien_Object - EMPTY

		
		$quote = $this->getQuote();
		// $quote->set
		$quote->collectTotals()->save();
	}
}
