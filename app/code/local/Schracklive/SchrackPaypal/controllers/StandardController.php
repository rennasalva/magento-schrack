<?php

require_once('app/code/core/Mage/Paypal/controllers/StandardController.php');

class Schracklive_SchrackPaypal_StandardController extends Mage_Paypal_StandardController {

	/**
	 * When a customer cancel payment from paypal.
	 */
	public function cancelAction() {
		$session = Mage::getSingleton('checkout/session');
        $quoteId = $session->getPaypalStandardQuoteId(true);
		$session->setQuoteId($quoteId);
        Mage::log('cancelAction - ' . ($this->getRequest()->isPost() ? 'post' : 'get') . ' - serverarray: ' . print_r($_SERVER, true)  . ' - params: ' . print_r($this->getRequest()->getParams(), true)  . '- quoteId: ' . $quoteId, null, '/payment/paypal.log');
		if ($session->getLastRealOrderId()) {
			$order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
			if ($order->getId()) {
				$order->cancel()->save();
			}
                        Mage::helper('paypal/checkout')->restoreQuote();    // Nagarro added: From Magento Core 1.9 to restore cancelled order quote
		}
		$this->_redirect('checkout/cart', array('_query' => 'utm_nooverride=1'));
	}

	/**
	 * when paypal returns
	 * The order information at this point is in POST
	 * variables.  However, you don't want to "process" the order until you
	 * get validation from the IPN.
	 */
	public function successAction() {
		$session = Mage::getSingleton('checkout/session');
		$quoteId = $session->getPaypalStandardQuoteId(true);
		$session->setQuoteId($quoteId);
        Mage::log('successAction - ' . ($this->getRequest()->isPost() ? 'post' : 'get') . ' - serverarray: ' . print_r($_SERVER, true) . ' - params: ' . print_r($this->getRequest()->getParams(), true)  . '- quoteId: ' . $quoteId, null, '/payment/paypal.log');
        // Mage::log('successAction, setting quote inactive: ' . Mage::getSingleton('checkout/session')->getQuote()->getId(), null, 'pupay.log'); PayUnitiy remove action
		Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
		$this->_redirect('checkout/onepage/success', array('_secure' => true, '_query' => 'utm_nooverride=1'));
	}
}
