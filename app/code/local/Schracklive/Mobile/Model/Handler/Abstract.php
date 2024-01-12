<?php

/**
 * Abstract
 *
 * @author c.friedl
 */
class Schracklive_Mobile_Model_Handler_Abstract {

    private $_moduleName = "Schracklive_Mobile";

    /**
        * Retrieve helper module name
        *
        * @return string
        */
    private function _getModuleName()
    {
        if( !$this->_moduleName ) {
            $class = get_class($this);
            $this->_moduleName = substr($class, 0, strpos($class, '_Model'));
        }
        return $this->_moduleName;
    }


    /**
        * Translate
        *
        * @return string
        */
    protected function __()
    {
        $args = func_get_args();
        $expr = new Mage_Core_Model_Translate_Expr(array_shift($args), $this->_getModuleName());
        array_unshift($args, $expr);
        return Mage::app()->getTranslator()->translate($args);
    }

	protected function _formatPrice($value) {
		return number_format($value, 2, ',', '.');
	}

	protected function _formatNumber($value) {
		if (floor($value) == $value) { // whole number
			return number_format($value, 0, ',', '.');
		} else {
			return number_format($value, 2, ',', '.');
		}
	}
    
    /**
     * Find current customer (will be either customer or system contact)
     * @param string $customer_id
     * @return Mage_Customer_Model_Customer
     */
    protected function _getCustomer($customer_id = null) {
        if ($customer_id === null) {
            return Mage::getSingleton('customer/session')->getCustomer();
        } else {
            $contact = Mage::helper('account')->getSystemContactByWwsCustomerId($customer_id);
			return $contact;
        }
    }

	protected function getLocalizedFilename($filename) {
		$suffix = Mage::getStoreConfig('schrack/mobile/article_doc_suffix');
		$indexOfLastPoint = strrpos($filename, '.');
		if ($indexOfLastPoint === false) {
			return $filename;
		}
		$indexOfUnderscore = strrpos($filename, '_');
		if (($indexOfUnderscore !== false) && (($indexOfLastPoint - $indexOfUnderscore) == 3)) {
			$fileSuffix = substr($filename, $indexOfUnderscore, 3);
			if ($fileSuffix == $suffix) {
				return $filename;
			} else {
				return str_replace($fileSuffix, $suffix, $filename);
			}
		} else {
			return $filename;
		}
	}

    protected function _checkEndOfLiveAndPredecessor ( &$product, &$endOfLive, &$predecessor ) {
        $endOfLive = false;
        $predecessor = null;
        if ( $product->isDead() ) {
			/*
            $newProduct = $product->getLastReplacementProduct($product);
            if ( $newProduct ) {
                $predecessor = $product->getSku();
                $product = $newProduct;
            } else {
                $endOfLive = true;
            }
			*/
			$endOfLive = true;
        }
    }

	protected function getPictureLabel ( $productHelper, $product, $customer ) {
		$res = null;
		if ( $product->isDead() )
			$res = $this->__('NOT AVAILABLE');
		else if ( $productHelper->isSale($product,$customer) )
			$res = $this->__('SALE');
		else if ( $productHelper->isPromotion($product,$customer) ) {
			$res = $this->__('PROMOTION');
		}
		return $res;
	}

	protected function _getPreparedCart($customer) {
		if ($customer->getId()) {
			$cart = Mage::getModel('sales/quote');
			$cart->loadByCustomer($customer);

			if (!$cart->getId()) {
				$cart->assignCustomer($customer);
				$cart->setStore(Mage::app()->getStore());
				$cart->save();
			}
		} else {
			$cart = Mage::getModel('sales/quote');
		}
		return $cart;
	}

	/**
	 * @param Mage_Sales_Model_Quote $quote
	 * @param Mage_Customer_Model_Customer $loggedInCustomer
	 * @throws Schracklive_Mobile_Exception|Mage_Core_Exception
	 * @return Mage_Sales_Model_Order
	 */
	protected function _makeWwsOffer(Mage_Sales_Model_Quote $quote, $loggedInCustomer) {
		$requestHelper = Mage::helper('wws/request');
        
		$requestHelper->setupQuoteForOffer($quote);

		// make sure we get a new WWS order number (in case the user got to the checkout/review)
		$quote->unsSchrackWwsOrderNumber();

		/* @var $connector Schracklive_Wws_Helper_Request */
		$messages = $requestHelper->fillInWwsQuoteDetails($quote, $loggedInCustomer);
		if ($messages->count(Mage_Core_Model_Message::ERROR) > 0) {
			throw Mage::exception('Schracklive_Mobile', $messages->toString());
		}
		$messages = $requestHelper->finalizeWwsQuote($quote, $loggedInCustomer);
		if ($messages->count(Mage_Core_Model_Message::ERROR) > 0) {
			throw Mage::exception('Schracklive_Mobile', $messages->toString());
		}

		$service = Mage::getModel('sales/service_quote', $quote);
		/* @var $service Mage_Sales_Model_Service_Quote */
		$order = $service->submit();

		if ($order->getId()) {
			// bypass setState() to avoid entry to order history
			$order->setData('state', 'schrack_offered');
			$order->setData('status', 'schrack_offered');
			$order->save();

			return $order;
		} else {
			return null;
		}
	}

	protected function _setupQuoteForOffer($quote) {
		$customer = $quote->getCustomer();

		$shippingMethod = Mage::helper('schrackshipping/delivery')->getShippingMethod();
		$paymentMethod = 'schrackpo';

		/* set adresses for quote */
		$quote->getBillingAddress()->importCustomerAddress(Mage::getModel('customer/address')->load($customer->getDefaultBilling()));
		$quote->getBillingAddress()->implodeStreetAddress();
		$shippingAddress = $quote->getShippingAddress();
		$shippingAddress->importCustomerAddress(Mage::getModel('customer/address')->load($customer->getDefaultShipping()));
		$shippingAddress->implodeStreetAddress();

		/* set shipping and payment method */
		$shippingAddress->setShippingMethod($shippingMethod);
		$shippingAddress->setCollectShippingRates(true); // required for $quote->collectTotals()
		$shippingAddress->setPaymentMethod($paymentMethod);

		/* update order and persist changes */
		$quote->getPayment()->importData(array('method' => $paymentMethod)); // importData() calls $quote->collectTotals() for us
		$quote->save();
	}    

	protected function notNullStr ( $s ) {
		return is_null($s) ? '' : $s;
	}
}

?>
