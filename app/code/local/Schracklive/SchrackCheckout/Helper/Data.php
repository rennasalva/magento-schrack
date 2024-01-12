<?php

class Schracklive_SchrackCheckout_Helper_Data extends Mage_Core_Helper_Abstract {

	public function formatPrice($product, $price) {
		if ($product->getSchrackInvalidity()) {
			return $this->__('not available');
		} else {
			return Mage::helper('checkout')->formatPrice($price);
		}
	}
    
    public function isPriceAvailable($product, $price) {
        if (!$product->getSchrackInvalidity())
            return true;
        else
            return false;
    }

}
