<?php

class Schracklive_SchrackCheckout_Block_Success extends Mage_Checkout_Block_Success {

	/**
	 * Get last order ID from session, fetch it and check whether it can be viewed, printed etc
	 */
	protected function _prepareLastOrder() {
		$orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
		if ($orderId) {
			$order = Mage::getModel('sales/order')->load($orderId);
			if ($order->getId()) {
				$isVisible = !in_array($order->getState(), Mage::getSingleton('sales/order_config')->getInvisibleOnFrontStates());
				$this->addData(array(
					'is_order_visible' => $isVisible,
					'view_order_id' => $this->getUrl('sales/order/view/', array('order_id' => $orderId)),
					'print_url' => $this->getUrl('sales/order/print', array('order_id' => $orderId)),
					'can_print_order' => false, // Schracklive - disabled
					'can_view_order' => Mage::getSingleton('customer/session')->isLoggedIn() && $isVisible,
					'order_id' => $order->getIncrementId(),
					'wws_order_number' => $order->getSchrackWwsOrderNumber(), // Schracklive - added
				));
		}
	}
	}

    /**
     * Retrieve identifier of created order
     *
     * @return string
     * @deprecated after 1.4.0.1
     */
	public function getOrderId() {
		$wwsOrderNumber = $this->_getData('wws_order_number');
		if ($orderId) {
			return $wwsOrderNumber;
		} else {
			return $this->_getData('order_id');
		}
	}

	public function getRealOrderId() {
		return $this->_getData('order_id');
	}

	public function getSchrackWwsOrderNumber() {
		return $this->_getData('wws_order_number');
	}

	/**
	 * Check order print availability
	 *
	 * @return bool
     * @deprecated after 1.4.0.1
	 */
	public function canPrint() {
		return false; // disabled
	}

}
