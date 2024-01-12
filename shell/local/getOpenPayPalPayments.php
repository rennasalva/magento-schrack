<?php
    ini_set('max_execution_time', '1200');
    ini_set('memory_limit', '2048M');
    set_time_limit(0);

    require_once 'shell.php';

    class Schracklive_Shell_GetOpenPayPalPayments extends Schracklive_Shell {


        public function __construct () {
            parent::__construct();
            // TODO
        }

        public function run () {
            $retrospectiveDays   = 500;
            $apiPlatform         = Mage::getStoreConfig('schrack/general/platform');
            if ($apiPlatform == 'LIVE') {
                $paypalGateway = 'https://api-3t.paypal.com/nvp';
            }
            if ($apiPlatform == 'TEST') {
                $paypalGateway = 'https://api-3t.sandbox.paypal.com/nvp';
            }
            $readConnection      = Mage::getSingleton('core/resource')->getConnection('core_read');
            $logOrdersUnfiltered = false;
            $logOrdersArray      = false;
            $logQueries1         = false;
            $logQueries2         = false;
            $logCurlSuccess      = false;
            $logCurl             = false;
            $fetchPaypal         = true;

            $apiUserName  = Mage::getStoreConfig('paypal/wpp/api_username');
            $apiPassword  = Mage::getStoreConfig('paypal/wpp/api_password');
            $apiSignature = Mage::getStoreConfig('paypal/wpp/api_signature');

            $postFields['USER']      = $apiUserName;
            $postFields['PWD']       = $apiPassword;
            $postFields['SIGNATURE'] = $apiSignature;
            $postFields['METHOD']    = 'TransactionSearch';
            $retrospectiveDate       = date('Y-m-d', strtotime('-' . $retrospectiveDays. ' days'));
            $postFields['STARTDATE'] = $retrospectiveDate  . 'T00:00:00Z';
            $postFields['VERSION']   = '79.0';

            $wwsOrderRecordsets = array();
            $query1  = "SELECT wiuor.wws_order_id AS wiuor_wws_order_id,";
            $query1 .= " wiuor.payment_method AS wiuor_payment_method,";
            $query1 .= " wiuor.user_email AS wiuor_user_email,";
            $query1 .= " wiuor.wws_customer_id AS wiuor_wws_customer_id,";
            $query1 .= " wiuor.request_datetime AS wiuor_request_datetime";
            $query1 .= " FROM wws_insert_update_order_request AS wiuor";
            $query1 .= " JOIN wws_ship_order_request AS wsor";
            $query1 .= " ON wiuor.wws_order_id = wsor.wws_order_id";

            if ($logQueries1) {
                Mage::log($query1, null, 'fetched_paypal_orders_query1.log');
            }

            $queryResult1 = $readConnection->query($query1);

            if ($queryResult1->rowCount() > 0) {
                foreach ($queryResult1 as $recordset) {
                    if (isset($wwsOrderRecordsets[$recordset['wiuor_wws_order_id']])) {
                        // Check, if date is latest, and take the recordset with the latest date
                        $previousRequestTime = strtotime($wwsOrderRecordsets[$recordset['wiuor_wws_order_id']]['request_datetime']);
                        $currentRequestTime  = strtotime($recordset['wiuor_request_datetime']);
                        if ($currentRequestTime > $previousRequestTime) {
                            $wwsOrderRecordsets[$recordset['wiuor_wws_order_id']]['payment_method']   = $recordset['wiuor_payment_method'];
                            $wwsOrderRecordsets[$recordset['wiuor_wws_order_id']]['user_email']       = $recordset['wiuor_user_email'];
                            $wwsOrderRecordsets[$recordset['wiuor_wws_order_id']]['wws_customer_id']  = $recordset['wiuor_wws_customer_id'];
                            $wwsOrderRecordsets[$recordset['wiuor_wws_order_id']]['request_datetime'] = $recordset['wiuor_request_datetime'];
                        }
                    } else {
                        // Not already set (-> Initialization !!)
                        $wwsOrderRecordsets[$recordset['wiuor_wws_order_id']]['payment_method']   = $recordset['wiuor_payment_method'];
                        $wwsOrderRecordsets[$recordset['wiuor_wws_order_id']]['user_email']       = $recordset['wiuor_user_email'];
                        $wwsOrderRecordsets[$recordset['wiuor_wws_order_id']]['wws_customer_id']  = $recordset['wiuor_wws_customer_id'];
                        $wwsOrderRecordsets[$recordset['wiuor_wws_order_id']]['request_datetime'] = $recordset['wiuor_request_datetime'];
                    }
                }
            }

            $paypalOrders = array();
            if (is_array($wwsOrderRecordsets) && !empty($wwsOrderRecordsets)) {
                foreach($wwsOrderRecordsets as $wws_order_id => $dataRow) {
                    // Filtering PayPal Orders
                    if ($dataRow['payment_method'] == 3) {
                        $paypalOrders[$wws_order_id]['payment_method']   = 'paypal';
                        $paypalOrders[$wws_order_id]['user_email']       = $dataRow['user_email'];
                        $paypalOrders[$wws_order_id]['wws_customer_id']  = $dataRow['wws_customer_id'];
                        $paypalOrders[$wws_order_id]['request_datetime'] = $dataRow['request_datetime'];

                        $logString  = $wws_order_id;
                        $logString .= " -> CID = " . $dataRow['wws_customer_id'];
                        $logString .= " -> " . $dataRow['user_email'];
                        $logString .= " -> " . 'paypal';
                        $logString .= " -> " . $dataRow['request_datetime'];

                        if ($logOrdersUnfiltered) {
                            Mage::log($logString, null, 'fetched_paypal_orders_unfiltered.log');
                        }
                    }
                }
            }

            if ($logOrdersArray) {
                Mage::log($paypalOrders, null, 'fetched_paypal_orders_array.log');
            }

            if (is_array($paypalOrders) && !empty($paypalOrders)) {
                foreach($paypalOrders as $wws_order_id => $dataRow) {
                    $query2  = "SELECT schrack_wws_status FROM sales_flat_order";
                    $query2 .= " WHERE schrack_wws_order_number LIKE '" . $wws_order_id . "'";
                    $query2 .= " AND schrack_wws_status NOT LIKE 'La1'";

                    if ($logQueries2) {
                        Mage::log($query2, null, 'fetched_paypal_orders_query1.log');
                    }

                    $queryResult2 = $readConnection->query($query2);

                    if ($queryResult2->rowCount() > 0) {
                        foreach ($queryResult2 as $recordset) {
                            $logString  = $wws_order_id;
                            $logString .= " -> WWS-Status = " . $recordset['schrack_wws_status'];
                            $logString .= " -> CID = " . $dataRow['wws_customer_id'];
                            $logString .= " -> " . $dataRow['user_email'];
                            $logString .= " -> " . $dataRow['payment_method'];
                            $logString .= " -> " . $dataRow['request_datetime'];

                            $paypalTransactionPayPalStatus = 'OPEN POSITION';
                            $paypalTransactionId           = '';
                            $paypalTransactionAmmount      = '';
                            $paypalTransactionCurrency     = '';
                            $curlResponseParam             = array();

                            if ($fetchPaypal) {
                                $postFields['INVNUM'] = $wws_order_id;

                                $curl = curl_init();

                                curl_setopt($curl, CURLOPT_URL, $paypalGateway);
                                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
                                curl_setopt($curl, CURLOPT_TIMEOUT, 60);
                                curl_setopt($curl, CURLOPT_POST, true);
                                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postFields));
                                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

                                if ($logCurl) {
                                    Mage::log(date('Y-m-d H:i:s') . ' getOpenPayPalPayments() -> used PayPal-API-Gateway with cURL: ' . $paypalGateway, null, '/payment/fetched_paypal_orders_curl.log');
                                    Mage::log(date('Y-m-d H:i:s') . ' getOpenPayPalPayments() -> used POST Parameters with cURL:', null, '/payment/fetched_paypal_orders_curl.log');
                                    Mage::log($postFields, null, '/payment/fetched_paypal_orders_curl.log');
                                }

                                $curlResponse = urldecode(curl_exec($curl));

                                if(!curl_errno($curl)) {
                                    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                                    if ($logCurlSuccess) {
                                        Mage::log(date('Y-m-d H:i:s') . ' getOpenPayPalPayments.php -> cURL-Success-HTTP Code: '  . $wws_order_id . ' (WWS-Order-ID) -- ' . $httpCode, null, '/payment/fetched_paypal_orders.log');
                                    }
                                } else {
                                    $strError = 'Curl error: ' . curl_error($curl);
                                    Mage::log(date('Y-m-d H:i:s') . ' getOpenPayPalPayments.php -> cURL-Error: ' . $wws_order_id . ' (WWS-Order-ID) -- ' . $strError, null, '/payment/fetched_paypal_orders_err.log');
                                }

                                curl_close($curl);

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

                                $logString .= ' -> PP-TID = ' . $paypalTransactionId;
                                $logString .= ' -> PayPal-Status = ' . $paypalTransactionPayPalStatus;

                                Mage::log($logString, null, 'fetched_paypal_orders.log');

                            }
                        }
                    }
                }
            }
        }
    }

    $shell = new Schracklive_Shell_GetOpenPayPalPayments();
    $shell->run();
