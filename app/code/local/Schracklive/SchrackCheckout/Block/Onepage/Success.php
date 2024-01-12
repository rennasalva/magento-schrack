<?php

class Schracklive_SchrackCheckout_Block_Onepage_Success extends Mage_Checkout_Block_Onepage_Success {

	/**
	 * Retrieve identifier of created order
	 *
	 * @return string
	 */
	public function getOrderId() {
		$orderId = $this->getSchrackWwsOrderNumber();
		if ($orderId) {
			return $orderId;
		} else {
			return Mage::getSingleton('checkout/session')->getLastRealOrderId();
		}
	}

	public function getSchrackWwsOrderNumber() {
        if (!$this->_order) {
            $this->_order = Mage::getModel('sales/order')->load(Mage::getSingleton('checkout/session')->getLastOrderId());
        }
		return $this->_order->getSchrackWwsOrderNumber();
	}

	/**
	 * Check order print availability
	 *
	 * @return bool
	 */
	public function canPrint() {
		return false; // disabled
	}

}
