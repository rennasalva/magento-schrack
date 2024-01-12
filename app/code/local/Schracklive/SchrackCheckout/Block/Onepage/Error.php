<?php

class Schracklive_SchrackCheckout_Block_Onepage_Error extends Mage_Core_Block_Template {

	/**
	 * Continue shopping URL
	 *
	 *  @return	  string
	 */
	public function getContinueShoppingUrl() {
		return Mage::getUrl('checkout/cart');
	}

}

