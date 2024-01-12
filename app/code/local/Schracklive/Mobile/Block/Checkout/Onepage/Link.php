<?php

class Schracklive_Mobile_Block_Checkout_Onepage_Link extends Mage_Checkout_Block_Onepage_Link {

	public function getCheckoutUrl() {
		return $this->getUrl('mobile/onepage', array('_secure' => true));
	}

}