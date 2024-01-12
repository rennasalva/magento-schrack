<?php

class Schracklive_Wws_Helper_Request extends Mage_Core_Helper_Abstract {

    const FLAG_ORDER_REGULAR_WEBSHOP_ORDER      = 1;
    const FLAG_ORDER_CREATE_PRINTED_OFFER       = 0;
    const FLAG_ORDER_CREATE_NOT_PRINTED_OFFER   = 3; // only for schack employees
    const FLAG_ORDER_ORDER_EXISTING_OFFER       = 2;

	protected $_soapClient = null;

	public function setupQuoteForOffer($quote) {
		$customer = $quote->getCustomer();

		$shippingMethod = Mage::helper('schrackshipping/delivery')->getShippingMethod();
		$paymentMethod  = Mage::helper('schrackpayment/method')->getDefaultPaymentMethod();

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
            
    
	public function fillInWwsQuoteDetails(Mage_Sales_Model_Quote $wwsQuote, $loggedInCustomer = null) {
		return $this->_fillInWwsRequestDetails($wwsQuote, $loggedInCustomer, false);
	}

	public function fillInWwsOrderDetails(Mage_Sales_Model_Quote $wwsOrder, $loggedInCustomer = null, $sendAddress = false) {
		return $this->_fillInWwsRequestDetails($wwsOrder, $loggedInCustomer, $sendAddress);
	}

	protected function _fillInWwsRequestDetails($wwsRequest, $loggedInCustomer, $sendAddress) {
//Mage::log('_fillInWwsRequestDetails', null, '/prospects/prospects.log');
		$messages = Mage::getModel('core/message_collection');
		/* @var $messages Mage_Core_Model_Message_Collection */

		$this->_removeInvalidProducts($wwsRequest, $messages);

		$pool = $this->_getMessagePool($this->_prepareRequest($wwsRequest, $loggedInCustomer, $sendAddress), 'insert_update_order');

		$pool->sendMails($this->_buildMailArguments($wwsRequest->getCustomer(), $wwsRequest->getSchrackWwsOrderNumber()));
		if ($pool->signalsDeactivateQuote()) {
			$wwsRequest->setIsActive(0);
		}

		if ($pool->signalsRetryWithEmptyOrderNumber()) {
			$wwsRequest->setSchrackWwsOrderNumber('');
			$wwsRequest->save();
			$pool = $this->_getMessagePool($this->_prepareRequest($wwsRequest, $loggedInCustomer, $sendAddress), 'insert_update_order');
		}

		foreach ($pool->getTranslatedMessages()->getItems() as $message) {
			$messages->add($message);
		}

		return $messages;
	}

	/**
	 * @note required for tests (injection of mocks)
	 * @param Mage_Core_Model_Message_Collection $messages
	 * @param                                    $method
	 * @return Schracklive_Wws_Model_Message_Pool
	 */
	protected function _getMessagePool(Mage_Core_Model_Message_Collection $messages, $method) {
		$arguments = array(
			'messages' => $messages,
			'configuration' => Mage::getModel('wws/signal_configuration', $method),
		);
		return Mage::getModel('wws/message_pool', $arguments);
	}

	protected function _prepareRequest(Mage_Sales_Model_Quote $request, $loggedInCustomer, $sendAddress) {
//Mage::log('_prepareRequest', null, '/prospects/prospects.log');
		$arguments = array(
			'soapClient' => $this->_getSoapClient(),
			'quote' => $request,
			'loggedInCustomer' => $loggedInCustomer,
			'sendAddress' => $sendAddress,
			'ip' => $this->_getIp(),
		);
		try {
			// shop/app/code/local/Schracklive/Wws/Model/Action/Preparewwsrequest.php -> _construct($arguments) :
			$action = Mage::getModel('wws/action_preparewwsrequest', $arguments);
			$messages = $action->execute();
		} catch (Schracklive_Wws_RequestErrorException $e) {
			$messages = $action->getMessages();
			Mage::log($e->getMessage().' in '.$e->getFile().' on line '.$e->getLine(), Zend_Log::ERR);
		}

		return $messages;
	}

	protected function _removeInvalidProducts(Mage_Sales_Model_Quote $request, Mage_Core_Model_Message_Collection $messages) {
        foreach ($request->getAllItems() as $item) {
            $product = $item->getProduct();
            if ($product->getSchrackInvalidity()) {
                $sku = $product->getSku();
                $msg = sprintf('The product %1s cannot be ordered currently.', $sku);
                $msg = $this->__($msg);
                $messages->add(Mage::getSingleton('core/message')->notice($msg));
                $request->removeItem($item->getId());
            }
        }
    }

	public function finalizeWwsQuote(Mage_Sales_Model_Quote $wwsQuote, $loggedInCustomer = null, $eMailAddress = null, $schrackCustomOrderNumber = null, $printQuote = true) {
	    $flagOrder = $printQuote ? self::FLAG_ORDER_CREATE_PRINTED_OFFER : self::FLAG_ORDER_CREATE_NOT_PRINTED_OFFER;
		return $this->_finalizeWwsRequest($wwsQuote,$flagOrder,$loggedInCustomer, $eMailAddress, null, null, null, $schrackCustomOrderNumber);
	}

	public function finalizeWwsOrder(Mage_Sales_Model_Quote $wwsQuote, $loggedInCustomer = null, $arguments = array(), $paymentSource = null) {

		// $wwsOrder --> Schracklive_SchrackSales_Model_Quote (app/code/local/Schracklive/SchrackSales/Model/Quote.php)
		if (in_array($wwsQuote->getSchrackCustomertype(), array('oldLightProspect', 'oldFullProspect', 'newProspect', 'guest'))  ) {
			// Do nothing here, or something useful
		} else {
			if ($wwsQuote->getSchrackCheckWwsOrder()) {
                Mage::log('There seems to be an ongoing order process #1.: WWS-Order Number : ' . $wwsQuote->getSchrackWwsOrderNumber(), null, '/Schracklive_Wws_Exception.log');
                Mage::log('There seems to be an ongoing order process #2.: WWS-Qoute Id : ' . $wwsQuote->getId(), null, '/Schracklive_Wws_Exception.log');
				throw Mage::exception(' Schracklive_Wws', 'There seems to be an ongoing order process.');
			}
		}

		$messages = Mage::getModel('core/message_collection');
        $wwsQuote->setSchrackCheckWwsOrder(1);
/*
$intSchrackWwsOrderNumber = (int) $wwsOrder->getSchrackWwsOrderNumber();
if ($intSchrackWwsOrderNumber < 1 || $intSchrackWwsOrderNumber == '' || $intSchrackWwsOrderNumber == null) {
	$wwsOrder->setSchrackWwsOrderNumber(383333333);
}
*/

        $wwsQuote->save();
		try {
			$isOrderFinalized = $this->isOrderFinalized($wwsQuote->getSchrackWwsOrderNumber());

			// Proceed as regular, order is still open
			if (!$isOrderFinalized) {                
                $paymentInfo = ( array_key_exists('paymentInfo', $arguments) ) ? $arguments['paymentInfo'] : null;
                $memo = ( array_key_exists( 'memo', $arguments )) ? $arguments['memo'] : null;               
				$messages = $this->_finalizeWwsRequest($wwsQuote, self::FLAG_ORDER_REGULAR_WEBSHOP_ORDER,
                                                       $loggedInCustomer, null, $paymentInfo,
                                                       $memo, $paymentSource);
				$pool = $this->_getMessagePool($messages, 'ship_order');
				if ($pool->signalsDeactivateQuote()) {
                    $wwsQuote->setIsActive(0);
				}
				$pool->sendMails($this->_buildMailArguments($wwsQuote->getCustomer(), $wwsQuote->getSchrackWwsOrderNumber()));
				$messages = $pool->getTranslatedMessages();
			// Deactivate order if it is already finalized
			} elseif ($wwsQuote->getIsActive()) {
                $wwsQuote->setSchrackWwsShipMemo('[check order status]');
			}
		} catch (Exception $e) {
			sleep(1); // allow the WWS to recover from a temporary problem
			$isOrderFinalized = false;
			try {
				$isOrderFinalized = $this->isOrderFinalized($wwsQuote->getSchrackWwsOrderNumber());
			} catch (Exception $ex) {
				// WWS fails two times in a row
				Mage::logException($ex);
				throw $e;
			}
			if ($isOrderFinalized) {
				Mage::logException($e);
                $wwsQuote->setSchrackWwsShipMemo('[check order status]');
			} else {
				throw $e;
			}
		}
        $wwsQuote->setSchrackCheckWwsOrder(0);
        $wwsQuote->save();

		return $messages;
	}

	/**
	 * Tells the WWS that we are done with a quote or an order.
	 *
	 * @todo create an action
	 * @param Mage_Sales_Model_Quote $request
	 * @param int                    $flagOrder 0 = offer with printing, 1 = order, 2 = order from offer, 3 = offer without printing
	 * @param mixed                  $loggedInCustomer
	 * @param string                 $eMailAddress
     * @param string                 $paymentInfo
     * @param string                 $memo
     * @param string                 $paymentSource
     * @param string                 $schrackCustomOrderNumber
	 * @return Mage_Core_Model_Message_Collection
	 */
	protected function _finalizeWwsRequest( Mage_Sales_Model_Quote $request, $flagOrder, $loggedInCustomer = null, $eMailAddress = null, $paymentInfo = null, $memo = null, $paymentSource = null, $schrackCustomOrderNumber = null, $tabulatorFieldSeperator = false) {
        if ($tabulatorFieldSeperator == true) {
            $schrackCustomOrderNumber = str_replace(';', '&#59;', $schrackCustomOrderNumber);
        }

        if ($memo) {
            if ($schrackCustomOrderNumber) {
                $memo = $memo . ';KBESTNR=' . $schrackCustomOrderNumber;
            }
        } else {
            if ($schrackCustomOrderNumber) {
                $memo = 'KBESTNR=' . $schrackCustomOrderNumber;
            }
        }

		$arguments = array(
			'soapClient' => $this->_getSoapClient(),
			'quote' => $request,
			'flagOrder' => $flagOrder,
			'loggedInCustomer' => $loggedInCustomer,
            'eMailAddress' => $eMailAddress,
			'ip' => $this->_getIp(),
            'paymentInfo' => $paymentInfo,
            'memo' => $memo,
			'paymentSource' => $paymentSource,
		);

		try {
			/** @var $action Schracklive_Wws_Model_Action_Finalizewwsrequest */
			$action = Mage::getModel('wws/action_finalizewwsrequest', $arguments);
			$messages = $action->execute();
		} catch (Schracklive_Wws_RequestErrorException $e) {
			$messages = $action->getMessages();
			// exception 'Schracklive_Wws_RequestErrorException' with message
			Mage::log($e->getMessage().' in '.$e->getFile().' on line '.$e->getLine(), Zend_Log::ERR);
		}
		return $messages;
	}

    public function orderOfferWithoutQuote ( $order,
                                             Schracklive_SchrackCustomer_Model_Customer $customer,
                                             Schracklive_SchrackCustomer_Model_Customer $advisor,
                                             $pickup, $warehouse, $addressNumber, $customerReference ) {
		if ( ! isset($addressNumber) || $addressNumber < 1 ) {
			$addressNumber = 1;
		}
        $args = array(
            'client'         => Mage::helper('wws')->createSoapClient(),
            'wwsOrderNumber' => $order->getSchrackWwsOrderNumber(),
            'wwsCustomerId'  => $customer->getSchrackWwsCustomerId(),
            'flagOrder'      => self::FLAG_ORDER_ORDER_EXISTING_OFFER,
            'emailTo'        => $customer->getEmailAddress4wws(),
            'emailCc'        => $advisor ? $advisor->getEmailAddress() : null,
            'memo'           => array('WebSendNr='.$order->getSchrackWwsWebSendNo(),
                                      'Pickup='.$pickup,
                                      'AddressNumber='.$addressNumber
                                     )
        );
		if ( $pickup ) {
			$args['memo'][] = 'Warehouse='.$warehouse;
		}
		if ( isset($customerReference) && $customerReference > '' ) {
			$args['memo'][] = 'KBESTNR=' . $customerReference;
		}

        $messages = Mage::getModel('core/message_collection');

        /** @var $request Schracklive_Wws_Model_Request_Shiporder */
        $request = Mage::getModel('wws/request_shiporder', $args);
        try {
            $success = $request->call();
            $messages = $request->getMessages();
            if ( ! $success ) {
                $txt = false;
                if ( isset($messages) ) {
                    $lastMsg = $messages->getLastAddedMessage();
                    if ( isset($lastMsg) ) {
                        $txt = $messages->getLastAddedMessage()->getText();
                    }
                }
                if ( $txt ) {
                    throw new Schracklive_Wws_RequestErrorException('Error in SOAP call shiporder: ' . $txt);
                } else {
                    throw new Schracklive_Wws_RequestErrorException('Unknown Error in SOAP call shiporder!');
                }
            }
		} catch (Schracklive_Wws_RequestErrorException $e) {
            // exception 'Schracklive_Wws_RequestErrorException' with message
            Mage::log($e->getMessage().' in '.$e->getFile().' on line '.$e->getLine(), Zend_Log::ERR);
        }
        $pool = $this->_getMessagePool($messages, 'ship_order');
        $pool->sendMails($this->_buildMailArguments($customer,$order->getSchrackWwsOrderNumber()));
        $messages = $pool->getTranslatedMessages();

        return $messages;
    }

	public function setSoapClientForTesting($soapClient) {
		$this->_soapClient = $soapClient;
	}

	protected function _getSoapClient() {
		if (!$this->_soapClient) {
			$this->_soapClient = Mage::helper('wws')->createSoapClient();
		}
		return $this->_soapClient;
	}

	protected function _buildMailArguments(Schracklive_SchrackCustomer_Model_Customer $customer, $wwsOrderNumber) {
		$arguments = array(
			'to' => null,
			'cc' => null,
			'bcc' => null,
			'templateVars' => array(),
			'subject' => null,
			'body' => null,
		);
		if ($customer->isContact() || $customer->isSystemContact()) {
			$arguments['to'] = $customer->getEmailAddress4wws();
			$advisor = $customer->getAdvisor();
			if ($advisor) {
				$arguments['cc'] = $advisor->getEmail();
			}
		}
		if (Mage::getStoreConfig('schrackdev/development/debug_bcc')) {
			$arguments['bcc'] = Mage::getStoreConfig('schrackdev/development/debug_bcc');
		}

		$arguments['templateVars'] = array(
			'orderNumber' => $wwsOrderNumber,
			'customerNumber' => $customer->getWwsCustomerId(),
			'person' => $customer,
			'customer' => $customer->getAccount(),
		);

		return $arguments;
	}

	public function isOrderFinalized($wwsOrderNumber) {
//return true;
		$arguments = array(
			'client' => Mage::helper('wws')->createSoapClient(),
			'wwsOrderNumber' => $wwsOrderNumber,
		);
		$statusRequest = Mage::getModel('wws/request_getorderstatus', $arguments);
		if (!$statusRequest->call()) {
			throw Mage::exception('Schracklive_Wws', "Cannot determine order status.");
		}
		$orderStatus = $statusRequest->getOrderStatus();
		// @todo check if returned row matches requested order (customer id)
		return $orderStatus->isOrder && $orderStatus->isFinalized;
	}

	protected function _getIp() {
		// @todo: move up (to the controller)
		return (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '').((isset($_SERVER['X_FORWARDED_FOR']) && $_SERVER['X_FORWARDED_FOR']) ? '/'.$_SERVER['X_FORWARDED_FOR'] : '');
	}

	public function stockLockDisabled($wwsOrderId) {
        $resource = Mage::getSingleton('core/resource');
        $readConnection  = $resource->getConnection('core_read');
        $response = true; // Default
        $stockNumber = null;

        if ($wwsOrderId) {
            $order = Mage::getModel('sales/order')->load($wwsOrderId, 'schrack_wws_order_number');
            $shippingMethod = $order->getShippingMethod();

            if ($shippingMethod) {
                $stockNumber = str_replace('schrackdelivery_warehouse', '', $shippingMethod);
                $stockNumber = str_replace('schrackpickup_warehouse', '', $stockNumber);

                if ($stockNumber) {
                    $query = "SELECT * FROM cataloginventory_stock WHERE stock_number = " . intval($stockNumber);
                    $queryResult = $readConnection->query($query);

                    if ($queryResult->rowCount() > 0) {
                        $lockedUntil = null;
                        foreach ($queryResult as $recordset) {
                            if (isset($recordset['locked_until'])) {
                                $lockedUntil = $recordset['locked_until'];
                            }
                        }

                        if ($lockedUntil) {
                            $nowTS = strtotime('now');
                            $lockedUntilTS = strtotime($lockedUntil);
                            if ($lockedUntilTS > $nowTS) {
                                $msg  = "(stockLockDisabled) Stock Number " . $stockNumber . " for WWS Order ID = " . $wwsOrderId;
                                $msg .= " is locked until " . $lockedUntil;
                                Mage::log($msg, null, 'stock_lock_detected.log');
                                $response = false;
                            }
                        }
                    }
                } else {
                    $msg = "No Stock Number Found Here! (stockLockDisabled) WWS Order = " . $wwsOrderId;
                    Mage::log($msg, null, 'get_stock_failed.log');
                }
            } else {
                $msg = "No Shipping Method Found Here! (stockLockDisabled) WWS Order = " . $wwsOrderId;
                Mage::log($msg, null, 'get_stock_failed.log');
            }
        } else {
            $msg = "No WWS Order Number Found Here! (stockLockDisabled)";
            Mage::log($msg, null, 'get_stock_failed.log');
        }

        return $response;
	}

}
