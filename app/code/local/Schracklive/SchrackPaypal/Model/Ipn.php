<?php

/**
 * PayPal Instant Payment Notification processor model
 */
class Schracklive_SchrackPaypal_Model_Ipn extends Mage_Paypal_Model_Ipn {

	public function processIpnRequest(array $request, Zend_Http_Client_Adapter_Interface $httpAdapter = null) {
		parent::processIpnRequest($request, $httpAdapter);

        Mage::log('processIpnRequest: debugData: ' . print_r($this->_debugData, null, '/payment/paypal.log'));

		$paymentStatus = $this->_filterPaymentStatus($this->_request['payment_status']);
        
		if ($paymentStatus == Mage_Paypal_Model_Info::PAYMENTSTATUS_COMPLETED) {
			// paid && finalize wws
			$order = $this->_getOrder();

			if ($order && strtolower($order->getSchrackWwsStatus()) == 'la1') {
                
				$quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
				$real_customer = Mage::getModel('customer/customer')->load($order->getCustomerId());

                // Register in DB-Table "paypal_get_payment_status" to corresponding recordset (UPDATE or INSERT):
                $resource        = Mage::getSingleton('core/resource');
                $writeConnection = $resource->getConnection('core_write');
                $readConnection  = $resource->getConnection('core_read');

                $schrackWwsOrderId                      = $request['invoice'];
                $foundUnpaidPayPalOrders                = false;
                $foundAlreadyPaidAndShippedPayPalOrders = false;
                $currentDatetime                        = date('Y-m-d H:i:s');
                $paypalTransactionId                    = $this->_request['txn_id'];
                $baseCurrencyCode                       = $this->_request['mc_currency'];
                $baseGrandTotal                         = $this->_request['mc_gross'];
                $customerID                             = $order->getCustomerId();
                $customerEmail                          = $real_customer->getEmail();
                $wwsCustomerID                          = $real_customer->getSchrackWwsCustomerId();

                // Search for existing WWS-Order-ID, if we have previous fetch-task-data about this order:
                $query = "SELECT * FROM paypal_get_payment_status WHERE schrack_wws_order_id LIKE '" . $schrackWwsOrderId . "'";
                $queryResult = $readConnection->query($query);

                if ($queryResult->rowCount() > 0) {
                    // Previous paypal data found:
                    foreach ($queryResult as $recordset) {
                        if ($recordset['schrack_status'] == 'pending_payment') {
                            // Previous data recordset needs to be updated:
                            $foundUnpaidPayPalOrders = true;
                        }
                        if ($recordset['schrack_status'] == 'complete' && $recordset['paypal_status'] == 'COMPLETED') {
                            // Previous data recordset needs to be updated:
                            $foundAlreadyPaidAndShippedPayPalOrders = true;
                        }
                    }
                } else {
                    $query = "INSERT INTO paypal_get_payment_status";
                    $query .= " SET customer_id = " . $customerID . ",";
                    $query .= " customer_email = '" . $customerEmail . "',";
                    $query .= " schrack_wws_customer_id = '" . $wwsCustomerID . "',";
                    $query .= " schrack_wws_order_id = '" . $schrackWwsOrderId . "',";
                    $query .= " total_invoice_amount = '" . str_replace(',', '.', $baseGrandTotal) . "',";
                    $query .= " base_currency = '" . $baseCurrencyCode . "',";
                    $query .= " schrack_status = 'complete',";
                    $query .= " paypal_status = 'COMPLETED',";
                    $query .= " paypal_request = '" . serialize($request) . "',";
                    $query .= " paypal_request_date = '" . $currentDatetime . "',";
                    $query .= " transaction_id = '" . $paypalTransactionId . "',";
                    $query .= " created_at = '" . $currentDatetime . "'";

                    // Write new recordset, because something gone wrong, while payment process started directly after order process: (checkout-problem???):
                    $writeConnection->query($query);
                }

                if ($foundUnpaidPayPalOrders == true) {
                    $query  = "UPDATE paypal_get_payment_status";
                    $query .= " SET paypal_status = 'COMPLETED',";
                    $query .= " schrack_status = 'complete',";
                    $query .= " paypal_request = '" . serialize($request) . "',";
                    $query .= " paypal_request_date = '" . $currentDatetime . "',";
                    $query .= " transaction_id = '" . $paypalTransactionId . "',";
                    $query .= " updated_at = '" . $currentDatetime . "'";
                    $query .= " WHERE schrack_wws_order_id LIKE '" . $schrackWwsOrderId . "'";
                    $query .= " AND (paypal_status NOT LIKE 'COMPLETED'";
                    $query .= " OR paypal_status IS NULL)";
                    $query .= " AND schrack_status NOT LIKE 'complete'";

                    // Updates existing recordset with new status:
                    $writeConnection->query($query);
                }

                if ($paypalTransactionId) {
                    $infoTransactionId = ' PP-TID: ' . $paypalTransactionId;
                } else {
                    $infoTransactionId = '';
                }

                // Don't send ship-order=true twice to WWS:
                if ($foundAlreadyPaidAndShippedPayPalOrders == false) {
                    // Check ship_order status before send ship_order twice!!! :
                    $shipOrderAlreadySent = false;

                    $query2 = "SELECT ship_order_status FROM paypal_get_payment_status WHERE schrack_wws_order_id LIKE '" . $schrackWwsOrderId . "'";
                    $queryResult2 = $readConnection->query($query2);

                    if ($queryResult2->rowCount() > 0) {
                        foreach ($queryResult2 as $recordset2) {
                            if ($recordset2['ship_order_status'] == 1) {
                                $shipOrderAlreadySent = true;
                            }
                        }
                    }

                    // After make sure, that order has not get ship_order before, now can send ship_order to WWS:
                    if ($shipOrderAlreadySent == false) {
                        $query3 = "SELECT wws_order_id FROM wws_ship_order_request WHERE wws_order_id LIKE '" . $schrackWwsOrderId . "'";
                        $queryResult3 = $readConnection->query($query3);

                        if ($queryResult3->rowCount() > 0) {
                            Mage::log(date('Y-m-d H:i:s') . 'processIpnRequest: finalizeWwsOrder already done for : ' . $schrackWwsOrderId, null, '/payment/paypal_err.log');
                        } else {
                            $stockStatusAvailable = Mage::helper('wws/request')->stockLockDisabled($schrackWwsOrderId);
                            if ($schrackWwsOrderId && $stockStatusAvailable == true) {
                                Mage::log(date('Y-m-d H:i:s') . 'processIpnRequest: will finalizeWwsOrder ('.implode(', ', array('paymentInfo' => $request['invoice'] . $infoTransactionId, 'memo' => 'SHIP=TRUE')) . ')', null, '/payment/paypal.log');
                                Mage::helper('wws/request')->finalizeWwsOrder($quote, $real_customer, array('paymentInfo' => $request['invoice'] . $infoTransactionId, 'memo' => 'SHIP=TRUE'));
                            }
                        }

                        $query4 = "UPDATE paypal_get_payment_status SET ship_order_status = 1, ship_order_datetime = '" . date('Y-m-d H:i:s') . "' WHERE schrack_wws_order_id LIKE '" . $schrackWwsOrderId . "'";
                        $queryResult4 = $writeConnection->query($query4);
                    }
                }
            }          
		} else {
            // Email an Kundenbetreuer: da ging was schief ~ ~

	        try {
		        $recipient              = '';
                $pendingStatus          = '';
		        $recipientName          = 'Schrack Employee';
		        $emailTemplateVariables = array();
		        $templateId             = Mage::getStoreConfig('schrack/shop/paypal_fraud_email_templateid');
		        $order                  = $this->_getOrder();

                if (isset($this->_request['pending_reason'])) {
                    $pendingStatus = ' (Pending Reason = ' . $this->_request['pending_reason'] . ')';
                }

                Mage::log('processIpnRequest: payapl request failed after succesful order (WWS-Order-ID: ' . $order->getSchrackWwsOrderNumber() . '), PayPal Response Payment-Status = ' . $this->_request['payment_status'] . $pendingStatus, null, '/payment/paypal.log');

                if ($order->getCustomerId()) {
                    $real_customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
                    if ($real_customer) {
                        $customerWwsId     = $real_customer->getSchrackWwsCustomerId();
                        $customerFirstname = $real_customer->getFirstname();
                        $customerLastname  = $real_customer->getLastname();
                        $customerEmail     = $real_customer->getEmail();
                        $real_account      = $real_customer->getAccount();
                        if ($real_account) {
                            $accountAdvisor = $real_account->getAdvisor();
                        } else {
                            Mage::log('PayPal IPN failure. No adviser information found, can´t contact adviser (WWS-Order-ID: ' . $order->getSchrackWwsOrderNumber() . ')', null, '/payment/paypal.log');
                        }
                    } else {
                        Mage::log('PayPal IPN failure. No customer information found (WWS-Order-Id: ' . $order->getSchrackWwsOrderNumber() . ')', null, '/payment/paypal.log');
                    }
                }
				$subject = 'Paypal Error - WWS Order ID: ' .  $order->getSchrackWwsOrderNumber();

		        $senderName = Mage::getStoreConfig('general/store_information/name');
		        $senderMail = 'noreply@schrack.com';
		        $sender = array('name' => $senderName, 'email' => $senderMail);
                
                Mage::log('processIpnRequest: will send failure email  (' . $subject . ', ' . $order->getCustomerId() . ')', null, '/payment/paypal.log');

		        if ($accountAdvisor) {
				    $recipient = $accountAdvisor->getEmail();
		        }
		        else {
			        throw new Exception('PayPal IPN failure. No adviser information found, can´t contact adviser!');
		        }

		        if (is_null($templateId) || trim($recipient) == '') {
			        throw new Exception('PayPal IPN failure. No template defined or recipient missing!');
		        }
		        else {

			        $customerInfo  = '' . $customerWwsId . ' ' . $customerFirstname . ' ' . $customerLastname . ' ' . $customerEmail;
			        $orderNumber   = '' . $order->getSchrackWwsOrderNumber();
			        $invoiceNumber = '' . $request['invoice'];
			        $paymentStatus = '' . $paymentStatus;

			        $emailTemplateVariables['customerInfo']  = $customerInfo;
			        $emailTemplateVariables['orderNumber']   = $orderNumber;
			        $emailTemplateVariables['invoiceNumber'] = $invoiceNumber;
			        $emailTemplateVariables['paymentStatus'] = $paymentStatus;

			        if (!Mage::getModel('core/email_template')
				        ->setTemplateSubject($subject)
				        ->sendTransactional($templateId, $sender, $recipient, $recipientName, $emailTemplateVariables)) {
				        throw new Exception('PayPal IPN failure. Email could not be sent.');
			        }

		        }
	        } catch (Exception $e) {
				Mage::log($e->getCode() . ' ' . $e->getMessage(), null, '/payment/paypal.log');
		        return;
	        }
        }
	}

	/**
	 * Post back to PayPal to check whether this request is a valid one
	 *
	 * @param Zend_Http_Client_Adapter_Interface $httpAdapter
	 * @throws Exception
	 * @return void
	 */
	protected function _postBack(Zend_Http_Client_Adapter_Interface $httpAdapter) {
		$sReq = '';
		foreach ($this->_request as $k => $v) {
			$sReq .= '&' . $k . '=' . urlencode($v);
		}
		$sReq .= "&cmd=_notify-validate";
		$sReq = substr($sReq, 1);
		$this->_debugData['postback'] = $sReq;
		$this->_debugData['postback_to'] = $this->_config->getPaypalUrl();

		$adapterConfig = array('verifypeer' => $this->_config->verifyPeer);
		if (Mage::getStoreConfig('schrack/general/proxy_host') && Mage::getStoreConfig('schrack/general/proxy_port')) {
			$adapterConfig['proxy'] = Mage::getStoreConfig('schrack/general/proxy_host').':'.Mage::getStoreConfig('schrack/general/proxy_port');
		}
		$httpAdapter->setConfig($adapterConfig);
		$httpAdapter->write(Zend_Http_Client::POST, $this->_config->getPaypalUrl(), '1.1', array(), $sReq);
		try {
			$response = $httpAdapter->read();
		}
		catch (Exception $e) {
			$this->_debugData['http_error'] = array('error' => $e->getMessage(), 'code' => $e->getCode());
			throw $e;
		}
		$this->_debugData['postback_result'] = $response;

		$response = preg_split('/^\r?$/m', $response);
		$response = trim($response[count($response) - 1]);
		$this->_debugData['parsed_response'] = $response;
        Mage::log('_postBack: debugData = ' . print_r($this->_debugData, true), null, '/payment/paypal.log');

		if ($response != 'VERIFIED') {
			throw new Exception('PayPal IPN postback failure. See ' . self::DEFAULT_LOG_FILE . ' for details.');
		}
		unset($this->_debugData['postback'], $this->_debugData['postback_result']);
	}

	/**
	 * Process completed payment (either full or partial)
	 */
	protected function _registerPaymentCapture($skipFraudDetection = false) {
		if ($this->getRequestData('transaction_entity') == 'auth') {
			return;
		}
                $parentTransactionId = $this->getRequestData('parent_txn_id');  // Nagarro added: to detect fraud detection from Magento 1.9 core
		$this->_importPaymentInformation();
		$payment = $this->_order->getPayment();
		$payment->setTransactionId($this->getRequestData('txn_id'))
			->setPreparedMessage($this->_createIpnComment(''))
			->setParentTransactionId($parentTransactionId)
			->setShouldCloseParentTransaction('Completed' === $this->getRequestData('auth_status'))
			->setIsTransactionClosed(0)
			->registerCaptureNotification($this->getRequestData('mc_gross'),
                                           $skipFraudDetection && $parentTransactionId); // Nagarro added: from Magento 1.9 core   
		$this->_order->save();

		// notify customer
		// Fix bug in comparison, invoice always ended up as a bool
		if (($invoice = $payment->getCreatedInvoice()) && !$this->_order->getEmailSent()) {
			$comment = $this->_order->sendNewOrderEmail()->addStatusHistoryComment(
				Mage::helper('paypal')->__('Notified customer about invoice #%s.', $invoice->getIncrementId())
			)
				->setIsCustomerNotified(true)
				->save();
		}
	}
    
    
    /**
     * Load and validate order, instantiate proper configuration
     * - we are now sending the schrack_wws_order_number as 'invoice', therefore we need to override this function
     *
     * @return Mage_Sales_Model_Order
     * @throws Exception
     */
    protected function _getOrder()
    {
        if (empty($this->_order)) {
            // get proper order
            $id = $this->_request['invoice'];
            $this->_order = Mage::getModel('sales/order')->load($id, 'schrack_wws_order_number');

            if (!$this->_order->getId()) {  // Nagarro added: From Magento 1.9 core exception handling change in if statement
                $this->_debugData['exception'] = sprintf('Wrong order ID: "%s".', $id);
                $this->_debug();
                Mage::app()->getResponse()
                    ->setHeader('HTTP/1.1','503 Service Unavailable')
                    ->sendResponse();
                exit;
            }
            // re-initialize config with the method code and store id
            $methodCode = $this->_order->getPayment()->getMethod();
            $this->_config = Mage::getModel('paypal/config', array($methodCode, $this->_order->getStoreId()));
            if (!$this->_config->isMethodActive($methodCode) || !$this->_config->isMethodAvailable()) {
                throw new Exception(sprintf('Method "%s" is not available.', $methodCode));
            }

            $this->_verifyOrder();
        }
        return $this->_order;
    }

}
