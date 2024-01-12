<?php

class Schracklive_SchrackCheckout_Helper_Offer extends Mage_Core_Helper_Abstract {
    
    public function doRequestOffer ( $eMailAddress, $schrackCustomOrderNumber = null, $printOffer = true ) {
        $session       = Mage::getSingleton('core/session');
        $requestHelper = Mage::helper('wws/request');
        $customer      = Mage::getSingleton('customer/session')->getCustomer();
        $quote         = Mage::getModel('sales/quote');
        
        if ( $eMailAddress && strcasecmp($customer->getEmailAddress(),$eMailAddress) == 0 ) {
            $eMailAddress = null;
        }

        if ($customer->isSystemContact()) {
            $loggedInCustomer = Mage::getSingleton('customer/session')->getLoggedInCustomer();
        } else {
            $loggedInCustomer = $customer;
        }


        $session->getMessages(true); // clearing messages...

        $quote->loadByCustomer($customer);
        
        $requestHelper->setupQuoteForOffer($quote);

        Mage::register('order_type', 'cart_offer');

        // ###############################################
        $isPickup = $quote->getIsPickup();
        $shippingMethodCode = '';
        if ( $isPickup ) {
            $id = Mage::helper('schrackcustomer')->getPickupWarehouseId($customer);
            $shippingHelper = Mage::helper('schrackshipping/pickup');
            $shippingMethodCode = $shippingHelper->getShippingMethodFromWarehouseId($id);
    		$quote->getShippingAddress()->setShippingMethod($shippingMethodCode);
    		$quote->collectTotals()->save();
        }
        // ###############################################
        
		// make sure we get a new WWS order number (in case the user got to the checkout/review)
		$quote->unsSchrackWwsOrderNumber();

		/* @var $connector Schracklive_Wws_Helper_Request */
		$messages = $requestHelper->fillInWwsQuoteDetails($quote, $loggedInCustomer);

        // TODO: check what to do with those messages...
		if ($messages->count(Mage_Core_Model_Message::ERROR) > 0) {
            $session->addError($this->__('A technical error occured while submitting the offert information. Please try again later or contact your Schrack contact person.') . ' Case #01');
            return false;
		}

		$messages = $requestHelper->finalizeWwsQuote($quote, $loggedInCustomer, $eMailAddress, $schrackCustomOrderNumber, $printOffer);
        // TODO: check what to do with those messages...
		if ($messages->count(Mage_Core_Model_Message::ERROR) > 0) {
            $session->addError($this->__('A technical error occured while submitting the offert information. Please try again later or contact your Schrack contact person.') . ' Case #02');
            return false;
		}
        
		$service = Mage::getModel('sales/service_quote', $quote);
		/* @var $service Mage_Sales_Model_Service_Quote */
		$order = $service->submit();

		if ($order->getId()) {
			// bypass setState() to avoid entry to order history
			$order->setData('state', 'schrack_offered');
			$order->setData('status', 'schrack_offered');
			$order->save();

			foreach ($quote->getItemsCollection() as $item) {
				$item->isDeleted(true);
			}
            // Mage::log('Schracklive_SchrackCheckout_Helper_Offer doRequestOffer: will set quote inactive', null, 'pupay.log'); PayUnitiy remove action
            $quote->setIsActive(false);
            $quote->delete(); 
		}

		if ( $printOffer ) {
            $session->addNotice($this->__('Your offer requst has been submitted.'));
        }
        return $order->getSchrackWwsOrderNumber();
    }
    
}


?>
