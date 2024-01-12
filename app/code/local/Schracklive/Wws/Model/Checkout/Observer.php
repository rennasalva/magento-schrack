<?php

class Schracklive_Wws_Model_Checkout_Observer {

	protected $_soapClient = null;

	/**
	 * @event schrack_checkout_get_quote_after
	 */
	public function checkForWwsOrderState($observer) {
		$quote = $observer->getQuote();

        $isCheckWwsOrder = $quote->getSchrackCheckWwsOrder();
        $wwsOrderNumber = $quote->getSchrackWwsOrderNumber();
        if ( (! isset($wwsOrderNumber) || strlen($wwsOrderNumber) < 1) && $isCheckWwsOrder ) {
            $isCheckWwsOrder = 0;
            $quote->setSchrackCheckWwsOrder($isCheckWwsOrder);
            $quote->save();
        }
        if ( ! isset($isCheckWwsOrder) || ! isset($wwsOrderNumber) || strlen($wwsOrderNumber) < 1 ) {
                return;
        }
        
        $isOrderFinalized = true;
        try {
            $isOrderFinalized = Mage::helper('wws/request')->isOrderFinalized($quote->getSchrackWwsOrderNumber());
        }
        catch ( Schracklive_Wws_Exception  $wwsEx ) {
            if ( preg_match('/^SOAP failure/i', $wwsEx->getMessage()) ) {
                throw $wwsEx; // we do nothing on SOAP problems
            }
            else { // this case must be the one when the order was deleted in WWS meanwhile
                Mage::logException($wwsEx);
                Mage::log('Non \'SOAP failure...\' Schracklive_Wws_Exception caught, try to deavtivate quote...',Zend_Log::ERR);
                $isOrderFinalized = true; // set that flag to force the following code to get rid of the shop order
            }
        }
                     
		if ($quote->getSchrackWwsOrderNumber() && $isOrderFinalized ) {
            try {
                $payment_check = $quote->getPayment();
                $payment_method = $payment_check->getMethod();

                $externalPayment = Mage::helper('schrackpayment/method')->isExternalMethod($payment_method);
            } catch (Exception $e) {
                Mage::logException($e);
                $externalPayment = false;
            }
            if( !$externalPayment ) {
                // Mage::log('Schracklive_Wws_Model_Checkout_Observer checkForWwsOrderState: will set quote inactive', null, 'pupay.log'); PayUnitiy remove action
                $quote->setIsActive(0);
                $quote->setClearQuote(true); // signal dispatching code to remove quote from session
                $quote->setSchrackWwsShipMemo('[check order status]');
            }
		}
		$quote->setSchrackCheckWwsOrder(0);
		$quote->save();
	}

	/**
	 *
	 * @event schrack_checkout_controller_onepage_save_payment
	 */
	public function fillInOrderDetailsFromWws($observer) {

		$onepage = $observer->getOnepage();
		$quote = $onepage->getQuote();
		$checkout = $onepage->getCheckout();

		$loggedInCustomer = $checkout->getLoggedInCustomer();

		try {
			$orderNum = $quote->getSchrackWwsOrderNumber();
			if ( $orderNum > '' ) {
				$orderNumCreatedAt = strtotime($quote->getSchrackWwsOrderNumberCreatedAt());
				$now = time();
				$weekAgo = $now - (7 * 24 * 60 * 60);
				if ( $orderNumCreatedAt < $weekAgo ) {
					$quote->setSchrackWwsOrderNumberCreatedAt(null);
					$orderNum = '';
					$quote->setSchrackWwsOrderNumber($orderNum);
				}
			}
			$messages = null;
			$account = $quote->getCustomer()->getAccount();

			if ($account) {
				if (!$account->getWwsCustomerId() || in_array($quote->getSchrackCustomertype(), array('oldLightProspect', 'oldFullProspect', 'newProspect', 'guest'))) {
					// $reallyLoggedInCustomer = $loggedInCustomer ? $loggedInCustomer : $quote->getCustomer();
					// $messages = $this->_fetchAndStoreWwsCustomerId($account, $reallyLoggedInCustomer, $observer);
				}
			} else {
				// TODO : Check if customer is part of the new checkout process:
				$loggedInCustomer = $quote->getCustomer();
			}

			if (!($messages instanceof Mage_Core_Model_Message_Collection)) {
				$customShippingAddr = $quote->getShippingAddress()->getSchrackIsCustomAddr() ? true : false;
				// SOURCE: app/code/local/Schracklive/Wws/Helper/Request.php -> fillInWwsOrderDetails($quote, $loggedInCustomer, $customShippingAddr) :
				$messages = Mage::helper('wws/request')->fillInWwsOrderDetails($quote, $loggedInCustomer, $customShippingAddr);

				$this->_removeInactiveQuoteFromSession($quote, $checkout);
				$quote->save();
			}
		} catch (Schracklive_Wws_Exception $e) {
			throw $e; // pass on
		} catch (Exception $e) {
			Mage::logException($e);
			throw $e;
		}

		// must NOT set a popup message (otherwise messages are not shown)
		$this->_prepareCheckoutMessagesForSection($checkout, $messages, 'payment', 'payment-method', null);
	}

	protected function _fetchAndStoreWwsCustomerId($account, $customer, $observer = '') {
		$messages = null;
		try {
			$arguments = array(
				'soapClient' => $this->_getSoapClient(),
				'account' => $account,
				'creator' => $customer,
				'memo' => '',
			);

			// Try to find out if we have new checkout process, with new prospects:
			$prospectOrGuestFound = false;
			if ($observer) {
				$onepage = $observer->getOnepage();
				$quote = $onepage->getQuote();
				$quoteCustomerType = $quote->getSchrackCustomertype();
//Mage::log('_fetchAndStoreWwsCustomerId' . $quoteCustomerType, null, '/prospects/prospects.log');
				if (in_array($quoteCustomerType, array('oldLightProspect', 'oldFullProspect', 'newProspect', 'guest'))) {
					$prospectOrGuestFound = true;
				}
			}

			if ($prospectOrGuestFound == false) {
				// $action = Mage::getModel('wws/action_getnewwwscustomerid', $arguments);
				// $wwsCustomerId = $action->execute();
				// $account->setWwsCustomerId($wwsCustomerId);
				// $account->save();
				// $customer->setAccount($account);
				// $customer->save();
			}
		} catch (Schracklive_Wws_RequestErrorException $e) {
			// $messages = $action->getMessages();
			Mage::log($e->getMessage().' in '.$e->getFile().' on line '.$e->getLine(),Zend_Log::ERR);
		}
		return $messages;
	}

	/**
	 *
	 * @event schrack_checkout_type_onepage_save_order_before
	 */
	public function finalizeOrderInWws($observer) {
		// we assume that the payment is not finalized if a redirect is set
		if ($observer->getRedirectUrl()) {
			return;
		}

		$quote = $observer->getQuote();
		// we assume that the quote has somehow been finalized already (eg by employee in the WWS)
		if ($quote->getSchrackWwsShipMemo()) {
			return;
		}
		$checkout = $observer->getCheckout();
		$loggedInCustomer = $checkout->getLoggedInCustomer();

		try {
            $payment_check = $quote->getPayment();
            $payment_method = $payment_check->getMethod();

            if( Mage::helper('schrackpayment/method')->isExternalMethod($payment_method) ) {
                $messages = Mage::getModel('core/message_collection');
            }
            else {
                $messages = Mage::helper('wws/request')->finalizeWwsOrder($quote, $loggedInCustomer);
                $this->_removeInactiveQuoteFromSession($quote, $checkout);
                $quote->save();
            }
		} catch (Schracklive_Wws_Exception $e) {
			throw $e; // pass on
		} catch (Schracklive_Crm_Exception $e) {
			Mage::logException($e);
		} catch (Exception $e) {
			Mage::logException($e);
			throw $e;
		}

		// a popop message text is required (otherwise an empty popup is shown)
		$this->_prepareCheckoutMessagesForSection($checkout, $messages, 'review', 'review', $this->__('The order could not be processed.').' [$4df241]');
	}

	public function _removeInactiveQuoteFromSession($quote, $checkout) {
		if (!$quote->getIsActive()) {
			$checkout->clear();
		}
	}

	protected function _prepareCheckoutMessagesForSection($checkout, $messages, $gotoSection, $updateSection, $popupMessage) {
		// @todo clean-up
		$exitCheckout = $this->_wwsSignalsExit($messages);
		$stayInSection = $this->_wwsSignalsHold($messages);
		$success = false;
		if ($messages->getMessageByIdentifier('Schracklive-OrderFinalized')) {
			$success = true;
		}

		$checkout->addMessages($messages->getItems());

		if ($success) {
			$checkout->setRedirectUrl(Mage::getUrl('checkout/onepage/success'));
		} elseif ($exitCheckout) {
			$checkout->setRedirectUrl(Mage::getUrl('checkout/onepage/error'));
		} elseif ($stayInSection) {
			$checkout->setGotoSection($gotoSection);
			$checkout->setUpdateSection($updateSection);
			foreach ( $messages->getItems() as $item ) {
			    if ( is_string($item->getIdentifier()) && substr($item->getIdentifier(),0,5) == 'WWS-3' ) {
			        $popupMessage = $this->__('There was an error processing your order. Please contact us or try again later.');
                }
            }

			throw new Schracklive_Wws_Exception($popupMessage, 0, null, false); // require that the exception is not logged
		}
	}

	protected function _wwsSignalsExit(Mage_Core_Model_Message_Collection $messages) {
		$exitSignalSet = false;
		foreach ($messages->getItems() as $message) {
			if ($this->_isExitMessage($message)) {
				$exitSignalSet = true;
				break;
			}
		}
		return $exitSignalSet;
	}

	protected function _wwsSignalsHold(Mage_Core_Model_Message_Collection $messages) {
		$holdSignalSet = false;
		foreach ($messages->getItems() as $message) {
			if ($this->_isBlockingMessage($message)) {
				$holdSignalSet = true;
				break;
			}
		}
		return $holdSignalSet;
	}

	protected function _isExitMessage($message) {
		if ($message->getType() == Mage_Core_Model_Message::ERROR) {
			return true;
		}
		return false;
	}

	protected function _isBlockingMessage($message) {
		if ($message->getType() == Mage_Core_Model_Message::WARNING && $this->_isWwsMessage($message)) {
			return true;
		}
		return false;
	}

	protected function _isWwsMessage($message) {
		return strncmp('WWS-', $message->getIdentifier(), 4) ? false : true;
	}

	/**
	 * Wrapper used by unit tests
	 */
	protected function __($text) {
		return Mage::helper('wws')->__($text);
	}

	protected function _getSoapClient() {
		if (!$this->_soapClient) {
			$this->_soapClient = Mage::helper('wws')->createSoapClient();
		}
		return $this->_soapClient;
	}

}
