<?php
/**
 * Created by IntelliJ IDEA.
 * User: e.ayvere
 * Date: 25.07.12
 * Time: 18:17
 * To change this template use File | Settings | File Templates.
 */

class Schracklive_SchrackCheckout_Model_Onepage_Observer {
	/**
	 * @event checkout_type_onepage_save_order
	 */
	public function adjustPrice($observer) {
		$order = $observer->getOrder();
		$quote = $observer->getQuote();
        $address = $quote->getShippingAddress();

		$order->setTaxAmount($quote->getSchrackTaxTotal());
		$order->setBaseTaxAmount($quote->getSchrackTaxTotal());
        // Shop is not allowed to calculate amounts!!! $order->getBaseSubtotal() should be correct anyhow
		// $order->setBaseGrandTotal($order->getBaseSubtotal() + $quote->getSchrackTaxTotal() + $address->getShippingAmount());
		$order->setShippingAmount($address->getShippingAmount());
		$order->setBaseShippingAmount($address->getBaseShippingAmount());
		$order->save();
	}
}
