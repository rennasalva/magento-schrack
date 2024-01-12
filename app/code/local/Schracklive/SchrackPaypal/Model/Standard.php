<?php

class Schracklive_SchrackPaypal_Model_Standard extends Mage_Paypal_Model_Standard {
	
    /**
     * Return form field array
     *
     * @return array
     */
    public function getStandardCheckoutFormFields()
    {
        $orderIncrementId = $this->getCheckout()->getLastRealOrderId();               
        
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        $api = Mage::getModel('paypal/api_standard')->setConfigObject($this->getConfig());
        $api->setOrderId($orderIncrementId)
            ->setCurrencyCode($order->getBaseCurrencyCode())
            //->setPaymentAction()
            ->setOrder($order)
            ->setNotifyUrl(Mage::getUrl('paypal/ipn/'))
            ->setReturnUrl(Mage::getUrl('paypal/standard/success'))
            ->setCancelUrl(Mage::getUrl('paypal/standard/cancel'));
        
        $api->setSchrackWwsOrderNumber($order->getSchrackWwsOrderNumber());

        // export address
        $isOrderVirtual = $order->getIsVirtual();
        $address = $isOrderVirtual ? $order->getBillingAddress() : $order->getShippingAddress();
        if ($isOrderVirtual) {
            $api->setNoShipping(true);
        } elseif ($address->validate()) {
            $api->setAddress($address);
        }

        // add cart totals and line items
        $api->setPaypalCart(Mage::getModel('paypal/cart', array($order)))
            ->setIsLineItemsEnabled($this->_config->lineItemsEnabled)
        ;
        $api->setCartSummary($this->_getAggregatedCartSummary());
        $api->setLocale($api->getLocaleCode());     // Nagarro added: Code added from Magento 1.9 core
        $result = $api->getStandardCheckoutRequest();
        
		$result['cancel_return'] .= '?utm_nooverride=1';
		$result['return'] .= '?utm_nooverride=1';

        Mage::log('Order - Schrack WWS Order Number: ' . $order->getSchrackWwsOrderNumber() . ', subtotal: '. $order->getSubtotal() . ', base_grand_total: ' . $order->getBaseGrandTotal() . ', base_subtotal: ' . $order->getBaseSubtotal() . ', tax_amount: ' . $order->getTaxAmount(), null, '/payment/paypal.log');
        Mage::log('-> Request Sent to paypal: ' . print_r($result, true), null, '/payment/paypal.log');

        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $readConnection  = $resource->getConnection('core_read');

        $schrackWwsOrderId = $order->getSchrackWwsOrderNumber();
        $wwsCustomerID  = 0;
        $customerID     = 0;

        // Catch result construction and save the paypal request data to check in future again:
        $wwsOrderID = $result['invoice'];
        $customerEmail = $result['email'];

        // Compare wws-order-id from result with ww-order-id from order:
        if ($schrackWwsOrderId != $wwsOrderID) {
            Mage::log('Customer Schrack-WWS-Order-ID -> ' . $schrackWwsOrderId . "is  not equal to WWS-Order-ID from result (invoice field) -> " . $wwsOrderID, null, '/payment/paypal_err.log');
        }

        // Get customer number, WWS-Customer ID (if present):
        if ($order->getCustomerId()) {
            $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
            $wwsCustomerID = $customer->getSchrackWwsCustomerId();
            if ($customer->getEmail()) {
                $customerEmail = $customer->getEmail();
            } else {
                Mage::log('Customer eMail not found for WWS-order-ID: ' . $order->getSchrackWwsOrderNumber() , null, '/payment/paypal_err.log');
            }
        }

        if (!$wwsCustomerID || $wwsCustomerID == 0) {
            if ($order->getSchrackWwsCustomerId()) {
                $wwsCustomerID = $order->getSchrackWwsCustomerId();
            }
        }

        $tempBaseGrandTotal = str_replace(',', '.', $order->getBaseGrandTotal());
        list($number, $decimal) = explode('.', $tempBaseGrandTotal);
        $baseGrandTotal =  $number . '.' . substr($decimal, 0, 2);

        $baseCurrency   = $order->getBaseCurrencyCode();

        if (Mage::getModel('sales/quote')->load($order->getQuoteId())->getCustomerId() > 0) {
            $customerID = Mage::getModel('sales/quote')->load($order->getQuoteId())->getCustomerId();
        }

        $currentDatetime = date('Y-m-d H:i:s');

        // Flag: INSERT or UPDATE PayPal data ?:
        $foundExistingPayPalDataFromPreviousTransfer = false;

        // Search for existing WWS-Order-ID, if we have previous fetch-task-data about this order:
        $query = "SELECT * FROM paypal_get_payment_status WHERE schrack_wws_order_id LIKE '" . $schrackWwsOrderId . "'";
        $queryResult = $readConnection->query($query);

        if ($queryResult->rowCount() > 0) {
            // Why customer paid twice ?:
            $foundExistingPayPalDataFromPreviousTransfer = true;
            Mage::log('Customer paid twice: WWS-Order-ID -> ' . $schrackWwsOrderId, null, '/payment/paypal_err.log');
        }

        // 1. If found, then UPDATE existing PayPal DATA:
        if ($foundExistingPayPalDataFromPreviousTransfer == true) {
            $query  = "UPDATE paypal_get_payment_status";
            $query .= " SET customer_id = '" . $customerID . "',";
            $query .= " customer_email = '" . $customerEmail . "',";
            $query .= " schrack_wws_customer_id = '" . $wwsCustomerID . "',";
            $query .= " schrack_wws_order_id = '" . $schrackWwsOrderId . "',";
            $query .= " total_invoice_amount = '" . $baseGrandTotal . "',";
            $query .= " base_currency = '" . $baseCurrency . "',";
            $query .= " schrack_status = 'pending_payment',";
            $query .= " paypal_request = '" . serialize($result) . "',";
            $query .= " paypal_request_date = '" . $currentDatetime . "',";
            $query .= " updated_at = '" . $currentDatetime . "'";
            $query .= " WHERE schrack_wws_order_id LIKE '" . $schrackWwsOrderId . "'";
        } else {
            // 2. If WWS Order ID not previously existing, then INSERT new recordset
            $query = "INSERT INTO paypal_get_payment_status";
            $query .= " SET customer_id = " . $customerID . ",";
            $query .= " customer_email = '" . $customerEmail . "',";
            $query .= " schrack_wws_customer_id = '" . $wwsCustomerID . "',";
            $query .= " schrack_wws_order_id = '" . $schrackWwsOrderId . "',";
            $query .= " total_invoice_amount = '" . $baseGrandTotal . "',";
            $query .= " base_currency = '" . $baseCurrency . "',";
            $query .= " schrack_status = 'pending_payment',";
            $query .= " paypal_request = '" . serialize($result) . "',";
            $query .= " paypal_request_date = '" . $currentDatetime . "',";
            $query .= " created_at = '" . $currentDatetime . "'";
        }

        $writeConnection->query($query);

        return $result;
    }

    /**
     * Aggregated cart summary label getter
     *
     * @return string
     */
    private function _getAggregatedCartSummary()
    {
        if ($this->_config->lineItemsSummary) {
            return $this->_config->lineItemsSummary;
        }
        return Mage::app()->getStore($this->getStore())->getFrontendName();
    }


    /**
     * Fetch PayPal orders directly from PayPal-Gateway which are in status of "pending_payment"
     *
     * @return string
     */
    public function processOpenPayPalOrders() {
        $sandbox            = intval(Mage::getStoreConfig('paypal/wpp/sandbox_flag'), 10);
        $intensiveLogging   = intval(Mage::getStoreConfig('paypal/wpp/intensive_logging_flag'), 10);
        $postFields         = array();
        $retrospectiveDays  = 30;
        $unpaidPayPalOrders = null;

        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $readConnection  = $resource->getConnection('core_read');

        $paypalGatewaySandbox = 'https://api-3t.sandbox.paypal.com/nvp';
        $paypalGatewayLive    = 'https://api-3t.paypal.com/nvp';

        $apiUserName  = Mage::getStoreConfig('paypal/wpp/api_username');
        $apiPassword  = Mage::getStoreConfig('paypal/wpp/api_password');
        $apiSignature = Mage::getStoreConfig('paypal/wpp/api_signature');

        if ($sandbox == 1) {
            $paypalGateway = $paypalGatewaySandbox;
            // Sandbox mode will always force intense logging:
            $intensiveLogging = 1;
        } else {
            $paypalGateway = $paypalGatewayLive;
        }

        $postFields['USER']      = $apiUserName;
        $postFields['PWD']       = $apiPassword;
        $postFields['SIGNATURE'] = $apiSignature;
        $postFields['METHOD']    = 'TransactionSearch';
        $retrospectiveDate       = date('Y-m-d', strtotime('-' . $retrospectiveDays. ' days'));
        $postFields['STARTDATE'] = $retrospectiveDate  . 'T00:00:00Z';
        $postFields['VERSION']   = '79.0';

        // Get all open paypal orders from table "paypal_get_payment_status"
        $query = "SELECT * FROM paypal_get_payment_status WHERE schrack_status LIKE 'pending_payment' AND DATE(created_at) <= '" . date('Y-m-d') . "' AND DATE(created_at) >= '". $retrospectiveDate . "'";
        $queryResult = $readConnection->query($query);

        if ($queryResult->rowCount() > 0) {
            foreach ($queryResult as $recordset) {
                $unpaidPayPalOrders[] = $recordset['schrack_wws_order_id'];
                if ($recordset['paypal_status'] == 'COMPLETED') {
                    // Why order has paypal_status == 'COMPLETED' and schrack_status == 'pending_payment' ???
                    $mail = new Zend_Mail('utf-8');
                    $mail->setFrom('shop_' . Mage::getStoreConfig('general/locale/code'), 'paypal-cronjob')
                         ->setSubject('ATTENTION: ')
                         ->setBodyHtml(strtoupper(Mage::getStoreConfig('schrack/general/country')) . ': please check schrack_wws_order_id: ' . $recordset['schrack_wws_order_id'] . ' in shop-database-table: paypal_get_payment_status');
                    $mail->addTo(Mage::getStoreConfig('schrackdev/customer/mappingDevelopmentMails'));
                    $mail->send();
                }
            }
        }

        if (is_array($unpaidPayPalOrders) && !empty($unpaidPayPalOrders)) {
            foreach ($unpaidPayPalOrders as $index => $wwsOrderID) {

                $postFields['INVNUM'] = $wwsOrderID;

                $curl = curl_init();

                curl_setopt($curl, CURLOPT_URL, $paypalGateway);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
                curl_setopt($curl, CURLOPT_TIMEOUT, 60);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postFields));
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

                if ($intensiveLogging == 1) {
                    Mage::log(date('Y-m-d H:i:s') . ' processOpenPayPalOrders() -> used PayPal-API-Gateway with cURL: ' . $paypalGateway, null, '/payment/paypal_debug.log');
                    Mage::log(date('Y-m-d H:i:s') . ' processOpenPayPalOrders() -> used POST Parameters with cURL:', null, '/payment/paypal_debug.log');
                    Mage::log($postFields, null, '/payment/paypal_debug.log');
                }

                $curlResponse = urldecode(curl_exec($curl));

                if(!curl_errno($curl)) {
                    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    Mage::log(date('Y-m-d H:i:s') . ' processOpenPayPalOrders.php -> cURL-Success-HTTP Code: '  . $wwsOrderID . ' (WWS-Order-ID) -- ' . $httpCode, null, '/payment/paypal_cron.log');
                } else {
                    $strError = 'Curl error: ' . curl_error($curl);
                    Mage::log(date('Y-m-d H:i:s') . ' processOpenPayPalOrders.php -> cURL-Error: ' . $wwsOrderID . ' (WWS-Order-ID) -- ' . $strError, null, '/payment/paypal_err.log');
                    echo $strError . "\n";
                    return $strError;
                }

                curl_close($curl);

                // Processing cURL Response -> Update table paypal_get_payment_status + send ship_order, if necessary and not already done:
                $paypalTransactionPayPalStatus = '';
                $paypalTransactionId           = '';
                $paypalTransactionAmmount      = '';
                $paypalTransactionCurrency     = '';
                $curlResponseParam             = array();

                $curlResponse = str_replace("'", "", $curlResponse);

                $arrResponseParamsValues = explode('&', $curlResponse);

                if (is_array($arrResponseParamsValues) && !empty ($arrResponseParamsValues)) {
                    foreach ($arrResponseParamsValues as $index => $paramsValues) {
                        list($param, $value) = explode('=', $paramsValues);
                        $curlResponseParam[$param] = $value;
                    }

                    if (isset($curlResponseParam['L_TRANSACTIONID0'])) $paypalTransactionId      = $curlResponseParam['L_TRANSACTIONID0'];
                    if (isset($curlResponseParam['L_AMT0'])) $paypalTransactionAmmount           = $curlResponseParam['L_AMT0'];
                    if (isset($curlResponseParam['L_CURRENCYCODE0'])) $paypalTransactionCurrency = $curlResponseParam['L_CURRENCYCODE0'];
                    if (isset($curlResponseParam['L_STATUS0'])) $paypalTransactionPayPalStatus   = strtoupper($curlResponseParam['L_STATUS0']);
                }

                // Saves real status without remapping for the test-system (= only used one time below):
                $paypalRealTransactionPayPalStatus = $paypalTransactionPayPalStatus;

                // Remap only in Test-System:
                $apiPlatform = Mage::getStoreConfig('schrack/general/platform');
                if ($apiPlatform == 'TEST' && $paypalTransactionPayPalStatus == 'UNDER REVIEW') {
                    // Mocking status (Under Review) only in Test-System:
                    $paypalTransactionPayPalStatus = 'COMPLETED';
                }

                $stockStatusAvailable = Mage::helper('wws/request')->stockLockDisabled($wwsOrderID);

                if ($paypalTransactionId
                    && $paypalTransactionAmmount
                    && $paypalTransactionPayPalStatus == 'COMPLETED'
                    && $stockStatusAvailable == true) {
                    // Success: payment successfully done (now we can send ship_order, if not done before):
                    $query  = "UPDATE paypal_get_payment_status SET";
                    $query .= " schrack_status = 'complete',";
                    $query .= " paypal_status = 'COMPLETED',";
                    $query .= " paypal_response = '" . $curlResponse . "',";
                    $query .= " paypal_response_date = '" . date('Y-m-d H:i:s') . "',";
                    $query .= " transaction_id ='" . $paypalTransactionId . "',";
                    $query .= " updated_at = '" . date('Y-m-d H:i:s') . "'";
                    $query .= " WHERE schrack_wws_order_id LIKE '" . $wwsOrderID . "'";
                    $query .= " AND schrack_status LIKE 'pending_payment'";
                    $query .= " AND (paypal_status NOT LIKE 'COMPLETED'";
                    $query .= " OR paypal_status IS NULL)";
                    $query .= " AND base_currency LIKE '" . $paypalTransactionCurrency . "'";
                    $query .= " AND total_invoice_amount LIKE '" . $paypalTransactionAmmount . "'";

                    if ($intensiveLogging == 1) Mage::log(date('Y-m-d H:i:s') . ' TRY TO EXECUTE -> ' . $query, null, '/payment/paypal_debug.log');

                    // Set open paypal orders recordset as "COMPLETED" (-> so it will not processed again):
                    $queryResult = $writeConnection->query($query);

                    if ($intensiveLogging == 1) Mage::log(date('Y-m-d H:i:s') . ' EXECUTED -> ' . $query, null, '/payment/paypal_debug.log');

                    // Update WWS Order and send SHIP-ORDER=TRUE:
                    $order = Mage::getModel('sales/order')->load($wwsOrderID, 'schrack_wws_order_number');
                    // Check, if order really is in Status = LA1:
                    if ($order) {
                        $quote = Mage::getModel('sales/quote')->getCollection()->addFieldToFilter('entity_id', $order->getQuoteId())->getFirstItem();
                        $real_customer = Mage::getModel('customer/customer')->load($order->getCustomerId());

                        // Check ship_order status before send ship_order twice!!! :
                        $shipOrderAlreadySent = false;

                        $query2 = "SELECT ship_order_status FROM paypal_get_payment_status WHERE schrack_wws_order_id LIKE '" . $wwsOrderID . "'";
                        $queryResult2 = $readConnection->query($query2);

                        if ($queryResult2->rowCount() > 0) {
                            foreach ($queryResult2 as $recordset2) {
                                if ($recordset2['ship_order_status'] == 1) {
                                    $shipOrderAlreadySent = true;
                                }
                            }
                        }

                        // After make sure, that order has not get ship_order before, now can send ship_order to WWS (Added: store-lock!):

                        if ($shipOrderAlreadySent == false) {
                            $query3 = "SELECT wws_order_id FROM wws_ship_order_request WHERE wws_order_id LIKE '" . $wwsOrderID . "'";
                            $queryResult3 = $readConnection->query($query3);

                            if ($queryResult3->rowCount() > 0) {
                                Mage::log(date('Y-m-d H:i:s') . ' processOpenPayPalOrders.php: finalizeWwsOrder already done for : ' . $wwsOrderID, null, '/payment/paypal_err.log');
                            } else {
                                Mage::log(date('Y-m-d H:i:s') . ' processOpenPayPalOrders.php: will finalizeWwsOrder ('.implode(', ', array('paymentInfo' => $wwsOrderID . ' PP-TID: ' . $paypalTransactionId, 'memo' => 'SHIP=TRUE')) . ')', null, '/payment/paypal.log');
                                Mage::helper('wws/request')->finalizeWwsOrder($quote, $real_customer, array('paymentInfo' => $wwsOrderID . ' PP-TID: ' . $paypalTransactionId, 'memo' => 'SHIP=TRUE'));
                            }

                            $query4 = "UPDATE paypal_get_payment_status SET ship_order_status = 1, ship_order_datetime = '" . date('Y-m-d H:i:s') . "' WHERE schrack_wws_order_id LIKE '" . $wwsOrderID . "'";
                            $queryResult4 = $writeConnection->query($query4);
                        }
                    } else {
                        Mage::log(date('Y-m-d H:i:s') . ' WWS-Order -> ' . $wwsOrderID . ' has delivery-status -> ' . $order->getSchrackWwsStatus(), null, '/payment/paypal_err.log');
                    }
                } else {
                    // Payment status is something else (still unpaid, under review, no money, internal server problem, etc.)
                    $query  = "UPDATE paypal_get_payment_status SET";
                    $query .= " schrack_status = 'pending_payment',";
                    $query .= " paypal_status = '" . strtoupper($paypalRealTransactionPayPalStatus) . "',";
                    $query .= " paypal_response = '" . $curlResponse . "',";
                    $query .= " paypal_response_date = '" . date('Y-m-d H:i:s') . "',";
                    $query .= " transaction_id ='" . $paypalTransactionId . "',";
                    $query .= " updated_at = '" . date('Y-m-d H:i:s') . "'";
                    $query .= " WHERE schrack_wws_order_id LIKE '" . $wwsOrderID . "'";
                    $query .= " AND (paypal_status NOT LIKE 'COMPLETED'";
                    $query .= " OR paypal_status IS NULL)";

                    if ($intensiveLogging == 1) Mage::log(date('Y-m-d H:i:s') . ' TRY TO EXECUTE -> ' . $query, null, '/payment/paypal_debug.log');

                    $queryResult = $writeConnection->query($query);

                    if ($intensiveLogging == 1) Mage::log(date('Y-m-d H:i:s') . ' EXECUTED -> ' . $query, null, '/payment/paypal_debug.log');
                }
            }
        }

        return 'done';
    }
}
