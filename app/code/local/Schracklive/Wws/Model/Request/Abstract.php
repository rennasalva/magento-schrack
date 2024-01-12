<?php

/**
 * This class talks via SOAP in terms of the WWS.
 *
 */
abstract class Schracklive_Wws_Model_Request_Abstract extends Schracklive_Schrack_Model_Abstract {

	const EXCEPTION_ERROR = 1;
	const EXCEPTION_SOAP_FAULT = 2;
	const EXCEPTION_SOAP_FAILURE = 3;
	const EXCEPTION_WWS_FAILURE = 4;
	const EXCEPTION_WWS_ERROR = 5;
	const EXCEPTION_WWS_FATAL_ERROR = 6;

	protected $_logId;
	protected $_config = array();
	protected $_soapClient = null;
	protected $_soapMethod = '';
	protected $_soapArguments = array();
	protected $_soapResponse = null;
	protected $_messages = null;
	protected $_startDateTime = '';
	protected $_exitCode = null;

    public function guidv4()
    {
        // $data = random_bytes(16); // PHP 7 -> waiting for SCHRACK Magento BACKEND !!!!
        $data = openssl_random_pseudo_bytes(16);
        assert(strlen($data) == 16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

	public function __construct(array $arguments) {
		$this->_checkArgument($arguments, 'client', 'Schracklive_Schrack_Model_Soap_Client');

		$this->_messages = $this->_createMessages();
		$this->_soapClient = $arguments['client'];
		$this->_logId = $this->guidv4(); // TODO
		$this->_init();
	}

	protected function _createMessages() {
		return Mage::getModel('core/message_collection');
	}

	protected function _init() {
		// @todo move config to constructor arguments (use Action-Models as builders)
		// consider using a helper, see getWwsAuthentication()
		$this->_config['test'] = Mage::getStoreConfig('schrackdev/development/test');
		$this->_config['qa'] = Mage::getStoreConfig('schrackdev/development/qa');
		$this->_config['country'] = Mage::helper('schrack')->getWwsCountry();
		$this->_config['email'] = Mage::getStoreConfig('schrack/wws/email');
	}

	public function getSoapMethodName() {
		return $this->_soapMethod;
	}

	public function call() {
		$this->_buildArguments();
		$this->_addStandardArguments();
		$this->_performRequest();
		try {
			if ($this->_isResponseValid()) {
				return $this->_processResponse();
			}
		} catch (Exception $e) {
			$this->_soapClient->logLastCallAsError($e->getMessage());
			throw $e;
		}
		return false;
	}

	protected function _buildArguments() {
		
	}

	protected function _addStandardArguments() {
		$wwsAuth = Mage::helper('wws')->getWwsAuthentication();
		$countryPrefix = '';
		$senderIdPrefix = '';
		if ($this->_config['test']) {
            // Auf dem Shop-Produktivsystem ist dieser Wert = 0, auf dem Shop-Testsystem und lokales Test System = 1
			$countryPrefix = 'Test_';
			$senderIdPrefix = 'Test_';
		}
		if ($this->_config['qa']) {
		    // Auf dem Shop-Produktivsystem ist dieser Wert = 0, auf dem Shop-Testsystem = 1
		    // Auf dem lokalen Test System muß dieser Wert = 0 sein, weil es offensichtlich eine IP Whitelist im WWS-
		    // -Testsystem gibt, die natürlich die lokale IP nicht kennt, und daher den Response verweigert!
			$countryPrefix = 'Dev_';
		}

		array_unshift($this->_soapArguments, $countryPrefix.$this->_config['country'], $senderIdPrefix.$wwsAuth->getSenderId().',TX='.$this->_logId, $wwsAuth->getUser(), $wwsAuth->getPassword());
	}

	protected function _performRequest() {
        $lockFileHandle = false;
        $customerDataSerialized = '';
	    // Schreibe in die Datenbank, dass ein insert_update_order_request rausgeht (Zeit + relevante Daten)
	    // Das ist quasi eine Vorankündigüng, die bei korrekter weiterer Verarbeitung tatsächlich in einem erfolgreichen SOAP Request (+ WWS-Response) enden sollte.
        if ($this->_soapMethod == 'insert_update_order') {
            $resource = Mage::getSingleton('core/resource');

            // Guck nach, ob Daten am Call hängen (sollte ein ARRAY sein), und schreibe bestimmte Daten in die DB:
            if (isset($this->_soapArguments['tt_order'][0])) {
                $ttOrderRequest = $this->_soapArguments['tt_order'][0];
                $dataWwsOrderNumber   = '';
                $dataPickupMethod     = 0;
                $dataPaymentMethod    = 0;
                $dataUserEmail        = '';
                $dataWwsCustomerId    = '';
                $dataWwsContactNumber = 0;

                if (isset($ttOrderRequest['OrderNumber']) && $ttOrderRequest['OrderNumber']) {
                    $dataWwsOrderNumber = $ttOrderRequest['OrderNumber'];
                }
                if (isset($ttOrderRequest['PickupMethod']) && $ttOrderRequest['PickupMethod']) {
                    $dataPickupMethod = intval($ttOrderRequest['PickupMethod'], 10);
                }
                $dataPaymentMethodDefinitionRage = array(0 => 'schrackpo',
                1 => 'schrackcod',
                2 => 'checkmo',
                3 => 'paypal_standard',
                4 => 'payunitycw_visa',
                5 => 'payunitycw_mastercard',
                9 => 'free',
                99 => 'cart_offer');

                $dataPaymentMethodDefinitionRageGerman = array(0 => 'Lieferschein/Rechnung',
                1 => 'Nachnahme',
                2 => 'Überweisung',
                3 => 'PayPal Standard',
                4 => 'Kreditkarte Visa',
                5 => 'Kreditkarte Mastercard',
                9 => 'Kostenlos',
                99 => 'Warenkorb Angebot' );

                if (isset($ttOrderRequest['PaymentMethod']) && $ttOrderRequest['PaymentMethod']) {
                    $dataPaymentMethod = intval($ttOrderRequest['PaymentMethod'], 10);
                }

                // Sonderfall: Angebot im Warenkorb erstellen:
                if (Mage::registry('order_type') && Mage::registry('order_type') == 'cart_offer') {
                    $dataPaymentMethod = 99;
                }

                // Special Case : Barzahlung bei Abholung (ist im Quote: Bezahlart = PayPal)
                if ($dataPickupMethod == 1 && $dataPaymentMethod == 1) {
                    $dataPaymentMethodDefinition       = 'schrackcash';
                    $dataPaymentMethodDefinitionGerman = 'Barzahlung';
                } else {
                    $dataPaymentMethodDefinition = $dataPaymentMethodDefinitionRage[$dataPaymentMethod];
                    $dataPaymentMethodDefinitionGerman = $dataPaymentMethodDefinitionRageGerman[$dataPaymentMethod];
                }
                if (isset($ttOrderRequest['OrderedByUser']) && $ttOrderRequest['OrderedByUser']) {
                    $dataUserEmail = $ttOrderRequest['OrderedByUser'];
                }
                if (isset($ttOrderRequest['CustomerNumber']) && $ttOrderRequest['CustomerNumber']) {
                    $dataWwsCustomerId = $ttOrderRequest['CustomerNumber'];
                }
                if (isset($ttOrderRequest['CustContactNumber']) && $ttOrderRequest['CustContactNumber']) {
                    $dataWwsContactNumber = $ttOrderRequest['CustContactNumber'];
                }

                if ($dataWwsContactNumber == 0 && stristr($dataWwsCustomerId, 'CUST')) {
                    $customerData = array();
                    $customerDataSerialized = '';

                    // Get firstname and lastname:
                    $readConnection = $resource->getConnection('core_read');

                    $queryQuoteCustomerData  = "SELECT * FROM sales_flat_quote WHERE customer_email LIKE '" . $dataUserEmail . "'";
                    $queryQuoteCustomerData .= " ORDER BY updated_at DESC LIMIT 1";

                    $queryResult = $readConnection->query($queryQuoteCustomerData);

                    if ($queryResult->rowCount() > 0) {
                        foreach ($queryResult as $recordset) {
                            $quoteId                       = $recordset['entity_id'];
                            $customerData['gender']        = $recordset['customer_prefix'];
                            $customerData['firstname']     = $recordset['customer_firstname'];
                            $customerData['lastname']      = $recordset['customer_lastname'];
                            $memofield                     = $recordset['schrack_wws_order_memo'];
                            $memoArray                     = explode(';', $memofield);
                            if (is_array($memoArray) && !empty($memoArray)) {
                                foreach($memoArray as $index => $value){
                                    if (stristr($value, 'UID=')) {
                                        $customerData['uid'] = str_replace('UID=', '', $value);
                                    }
                                    if (stristr($value, 'TAXID=')) {
                                        $customerData['local_uid'] = str_replace('TAXID=', '', $value);
                                    }
                                }
                            }
                        }

                        $queryQuoteCustomerData = "SELECT * FROM sales_flat_quote_address WHERE quote_id = " . $quoteId;

                        $queryResult = $readConnection->query($queryQuoteCustomerData);

                        if ($queryResult->rowCount() > 0) {
                            foreach ($queryResult as $recordset) {
                                $customerData['company_phone'] = $recordset['telephone'];
                            }
                        }
                    }

                    $customerData['email'] = $dataUserEmail;
                
                    // Save customer data in case of mysterious "undefined" data in schrack support mails:
                    if (isset($ttOrderRequest['CustPhone']) && $ttOrderRequest['CustPhone']) {
                        $customerData['customer_contact_person_phone'] = $ttOrderRequest['CustPhone'];
                    }
                    if (isset($ttOrderRequest['CustName1']) && $ttOrderRequest['CustName1']) {
                        $customerData['customer_companyname1'] = $ttOrderRequest['CustName1'];
                    }
                    if (isset($ttOrderRequest['CustName2']) && $ttOrderRequest['CustName2']) {
                        $customerData['customer_companyname2'] = $ttOrderRequest['CustName2'];
                    }
                    if (isset($ttOrderRequest['CustName3']) && $ttOrderRequest['CustName3']) {
                        $customerData['customer_company_contactperson'] = $ttOrderRequest['CustName3'];
                    }
                    if (isset($ttOrderRequest['CustStr']) && $ttOrderRequest['CustStr']) {
                        $customerData['customer_street'] = $ttOrderRequest['CustStr'];
                    }
                    if (isset($ttOrderRequest['CustCity']) && $ttOrderRequest['CustCity']) {
                        $customerData['customer_city'] = $ttOrderRequest['CustCity'];
                    }
                    if (isset($ttOrderRequest['CustZip']) && $ttOrderRequest['CustZip']) {
                        $customerData['customer_postcode'] = $ttOrderRequest['CustZip'];
                    }
                    if (isset($ttOrderRequest['CustCtry']) && $ttOrderRequest['CustCtry']) {
                        $customerData['customer_country'] = $ttOrderRequest['CustCtry'];
                    }

                    $customerDataSerialized = serialize($customerData);
                }
            }

            // Check, if a previous prospect order, was changed meanwhile to a customer order (with wws id) in checkout:
            if (strlen($dataWwsOrderNumber) > 3 && strlen($dataWwsCustomerId) > 3) {
                $query  = "SELECT * FROM wws_insert_update_order_request WHERE wws_order_id LIKE '" . $dataWwsOrderNumber . "'";
                $query .= " AND wws_customer_id LIKE '%CUST%'";
                $query .= " ORDER BY request_datetime DESC LIMIT 1";

                // If there is a match, then customer changed in checkout, from guest order to customer order (with wws id):
                $readConnection = $resource->getConnection('core_read');
                $queryResult = $readConnection->query($query);

                if ($queryResult->rowCount() > 0) {
                    $this->_soapArguments['tt_order'][0]['OrderNumber'] = '';
                    $dataWwsOrderNumber = '';
                }
            }

            $dataRequestDatetime = date('Y-m-d H:i:s');
            if ($this->_logId) {
                $dataUniqueId = $this->_logId;
            } else {
                $dataUniqueId = 'no log-id (this->_logId) given';
            }

            $writeConnection = $resource->getConnection('core_write');

            $query  = "INSERT INTO wws_insert_update_order_request";
            $query .= " SET unique_log_id = '" . $dataUniqueId . "',";
            $query .= " wws_order_id = '" . $dataWwsOrderNumber . "',";
            $query .= " pickup_method = " . $dataPickupMethod . ",";
            $query .= " payment_method = " . $dataPaymentMethod . ",";
            $query .= " payment_method_definition = '" . $dataPaymentMethodDefinition . "',";
            $query .= " payment_method_definition_german = '" . $dataPaymentMethodDefinitionGerman . "',";
            $query .= " user_email = '" . $dataUserEmail . "',";
            $query .= " wws_customer_id = '" . $dataWwsCustomerId . "',";
            $query .= " wws_contact_number = " . $dataWwsContactNumber . ",";
            $query .= " response_fetched_successfully = 0,";
            $query .= " request_datetime = '" . $dataRequestDatetime . "'";

            if ($customerDataSerialized != '') {
                $query .= " , customerdata = '" . $customerDataSerialized . "'";
            }

            try {
                $writeConnection->query($query);
            } catch (Exception $e) {
                Mage::log($e->getMessage(), null, 'insert_update_order_request_db_error.log');
                Mage::log($query, null, 'insert_update_order_request_db_error.log');
            }
        }

        $isPayPalOrderWithoutMemoShipTrue = false;

        if ($this->_soapMethod == 'ship_order') {

            if (isset($this->_soapArguments['tt_ship'][0])) {
                $ttOrderRequest = $this->_soapArguments['tt_ship'][0];
                $lockName = '/tmp/' . $this->_soapArguments[0] . '_' . $ttOrderRequest['OrderNumber'] . '.lock';
                $lockFileHandle = fopen($lockName,"w");
                if ( ! flock($lockFileHandle, LOCK_EX) ) {
                    fclose($lockFileHandle);
                    throw Exception("Unexpected error 723");
                }

                $dataWwsOrderNumber   = '';
                $dataUserEmail        = '';
                $dataWwsCustomerId    = '';
                $dataFlagOrder        = '';
                $dataFlagMemo         = '';
                $intPaymentMethod     = 99;
                $flagShipTrueQuery    = '';

                if (isset($ttOrderRequest['OrderNumber']) && $ttOrderRequest['OrderNumber']) {
                    $dataWwsOrderNumber = $ttOrderRequest['OrderNumber'];
                }
                if (isset($ttOrderRequest['MailTo']) && $ttOrderRequest['MailTo']) {
                    $dataUserEmail = $ttOrderRequest['MailTo'];
                }
                if (isset($ttOrderRequest['CustomerNumber']) && $ttOrderRequest['CustomerNumber']) {
                    $dataWwsCustomerId = $ttOrderRequest['CustomerNumber'];
                }
                if (isset($ttOrderRequest['FlagOrder']) && $ttOrderRequest['FlagOrder']) {
                    $dataFlagOrder = $ttOrderRequest['FlagOrder'];
                }
                if (isset($ttOrderRequest['Memo']) && $ttOrderRequest['Memo']) {
                    $dataFlagMemo = $ttOrderRequest['Memo'];
                }
            }

            // Nur bei Aufträgen kann es eine Bezahlart geben, die ungleich 99 ist:
            if (intval($dataFlagOrder) == 1) {
                // Trmittle über die WWS Order ID, um welche Bezahlart es sich handelt
                $queryPaymentMethod  = "SELECT payment_method FROM wws_insert_update_order_request";
                $queryPaymentMethod .= " WHERE wws_order_id LIKE '" . $dataWwsOrderNumber . "' AND response_fetched_successfully = 1";
                $queryPaymentMethod .= " ORDER BY request_datetime DESC LIMIT 1";

                $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
                $queryResult = $readConnection->query($queryPaymentMethod);

                if ($queryResult->rowCount() > 0) {
                    foreach ($queryResult as $recordset) {
                        $intPaymentMethod = $recordset['payment_method'];
                        // ...wenn es PayPal ist...
                        if ($intPaymentMethod == 3) {
                            // ... aber kein SHIP=TRUE enthält..
                            if (!$dataFlagMemo || !stristr($dataFlagMemo, 'SHIP=TRUE')){
                                $isPayPalOrderWithoutMemoShipTrue = true;
                            }
                        }
                    }
                }
            }

            // ACHTUNG: bei FlagOrder == 0 (Angebot aus dem Warenkorb erstellen) oder FlagOrder == 2 (Angebotsübernahme)
            //          wird im ship_order KEINE Bezahlart übergeben. Daher in beiden Fällen immer Bezahlart = 99
            ///////////////////////////////////////////////////////////////////////////////////////////////////////////

            // Logge in die Tabelle wws_ship_order_request (-> neues Feld ship_flag_true)
            if ($dataFlagMemo && stristr($dataFlagMemo, 'SHIP=TRUE')) {
                $flagShipTrueQuery = " ship_flag_true = 1,";
            }

            $dataRequestDatetime = date('Y-m-d H:i:s');

            $resource = Mage::getSingleton('core/resource');
            $writeConnection = $resource->getConnection('core_write');

            $query  = "INSERT INTO wws_ship_order_request";
            $query .= " SET wws_order_id = '" . $dataWwsOrderNumber . "',";
            $query .= " user_email = '" . $dataUserEmail . "',";
            $query .= " wws_customer_id = '" . $dataWwsCustomerId . "',";
            $query .= " flag_order = " . intval($dataFlagOrder) . ",";
            $query .= " payment_method = " . $intPaymentMethod . ",";
            $query .= $flagShipTrueQuery;
            $query .= " request_datetime = '" . $dataRequestDatetime . "'";

            try {
                $writeConnection->query($query);
            } catch (Exception $e) {
                Mage::log($e->getMessage(), null, 'ship_order_request_db_error.log');
                Mage::log($query, null, 'ship_order_request_db_error.log');
            }
        }

        // TODO
        if ($this->_soapMethod == '' || $this->_soapMethod == null) {


        }

		$this->_soapClient->setSchrackLogId($this->_logId);

		$this->_startDateTime = date('Y-m-d H:i:s');

		try {
            if ( $this->shouldMockWws() ) {
                $this->mockWws();
            } else {
                if ($isPayPalOrderWithoutMemoShipTrue == false) {
                    // Final Step: jsut fires the SOAP CAll:
                    $this->_soapResponse = call_user_func_array(array($this->_soapClient, $this->_soapMethod), $this->_soapArguments);
                }
            }
		} catch (SoapFault $e) {
		    if ( $lockFileHandle ) { flock($lockFileHandle, LOCK_UN); fclose($lockFileHandle); unlink($lockName); $lockFileHandle = false; }
			$this->_handleWwsSoapFault($e);
		} catch (Exception $e) {
		    if ( $lockFileHandle ) { flock($lockFileHandle, LOCK_UN); fclose($lockFileHandle); unlink($lockName); $lockFileHandle = false; }
			throw Mage::exception('Schracklive_Wws', $e->getMessage(), self::EXCEPTION_SOAP_FAILURE);
		}
        if ( $lockFileHandle ) { flock($lockFileHandle, LOCK_UN); fclose($lockFileHandle); unlink($lockName); $lockFileHandle = false; }
	}

	protected function _isResponseValid() {
		if (!is_array($this->_soapResponse)) {
			throw $this->_wwsException('WWS returned unstructured response.', self::EXCEPTION_WWS_FAILURE);
		}
		return $this->_hasValidExitCode();
	}

	protected function _hasValidExitCode() {
		if (!array_key_exists('exit_code', $this->_soapResponse)) {
			throw $this->_wwsException('WWS returned no exit code.', self::EXCEPTION_WWS_FAILURE);
		}
		if (empty($this->_soapResponse['exit_code'])) {
			throw $this->_wwsException('WWS returned empty exit code.', self::EXCEPTION_WWS_FAILURE);
		}

		$this->_exitCode = $this->_soapResponse['exit_code'];

		if ($this->_soapResponse['exit_code'] != 1) {
			return $this->_processWwsMessage($this->_soapResponse['exit_code'], isset($this->_soapResponse['exit_msg']) ? $this->_soapResponse['exit_msg'] : '');
		}
		return true;
	}

	protected function _processResponse() {
		return true;
	}

	protected function _checkReturnedFieldsOfAllRows($param, array $requiredFields) {
		if (!array_key_exists($param, $this->_soapResponse) || !is_array($this->_soapResponse[$param])) {
			throw $this->_wwsException("Required array parameter '{$param}' is missing in WWS response.", self::EXCEPTION_WWS_FAILURE);
		}
		foreach ($this->_soapResponse[$param] as $idx => $unused) {
			$this->_checkReturnedFields($param, $requiredFields, $idx);
		}
	}

	protected function _checkReturnedFieldsOfOneRow($param, array $requiredFields, $idx = 0) {
		if (!array_key_exists($param, $this->_soapResponse) || !is_array($this->_soapResponse[$param])) {
			throw $this->_wwsException("Required array parameter '{$param}' is missing in WWS response.", self::EXCEPTION_WWS_FAILURE);
		}
		if (!array_key_exists($idx, $this->_soapResponse[$param]) || !is_object($this->_soapResponse[$param][$idx])) {
			throw $this->_wwsException("Required array index {$idx} ({$param}) is missing in WWS response.", self::EXCEPTION_WWS_FAILURE
			);
		}
		$this->_checkReturnedFields($param, $requiredFields, $idx);
	}

	protected function _checkReturnedFields($param, array $requiredFields, $idx) {
		foreach ($requiredFields as $field) {
			if (!property_exists($this->_soapResponse[$param][$idx], $field)) {
				throw $this->_wwsException("Required field '{$field}' ({$param}) is missing in WWS response.", self::EXCEPTION_WWS_FAILURE
				);
			}
			if (is_null($this->_soapResponse[$param][$idx]->$field)) {
				throw $this->_wwsException("Required field '{$field}' ({$param}) has NULL value in WWS response.", self::EXCEPTION_WWS_FAILURE
				);
			}
		}
	}

	protected function _isStatusOfAllRowsValid($param) {
		if (!array_key_exists($param, $this->_soapResponse) || !is_array($this->_soapResponse[$param])) {
			throw $this->_wwsException("Required array parameter {$param} is missing in WWS response.", self::EXCEPTION_WWS_FAILURE
			);
		}
		$valid = true;
		foreach ($this->_soapResponse[$param] as $idx => $unused) {
			if (!$this->_isStatusValid($param, $idx)) {
				$valid = false;
			}
		}
		return $valid;
	}

	protected function _isStatusOfOneRowValid($param, $idx = 0) {
		if (!array_key_exists($param, $this->_soapResponse) || !is_array($this->_soapResponse[$param])) {
			throw $this->_wwsException("Required array parameter {$param} is missing in WWS response.", self::EXCEPTION_WWS_FAILURE
			);
		}
		if (count($this->_soapResponse[$param]) == 0) {
			throw $this->_wwsException("Empty array parameter {$param} in WWS response.", self::EXCEPTION_WWS_FAILURE
			);
		}
		return $this->_isStatusValid($param, $idx);
	}

	protected function _isStatusValid($param, $idx) {
		if ($this->_soapResponse[$param][$idx]->xstatus < 1) {
			throw $this->_wwsException('Unexpected WWS status code '.$this->_soapResponse[$param][$idx]->xstatus.': '.$this->_soapResponse[$param][$idx]->xerror, self::EXCEPTION_WWS_FAILURE
			);
		}

		if ($this->_soapResponse[$param][$idx]->xstatus == 1) {
			$ok = true;
		} else {
			$ok = $this->_processWwsMessage($this->_soapResponse[$param][$idx]->xstatus, $this->_soapResponse[$param][$idx]->xerror);
		}
		$this->_soapResponse[$param][$idx]->_ok = true;
		return $ok;
	}

	protected function _handleWwsSoapFault(SoapFault $e) {
		// @todo use Magento dispatcher to move this code out of this class
		$detail = '';
		if (isset($e->detail) && is_object($e->detail) && is_object($e->detail->FaultDetail)) {
			$detail = 'Fault Detail: '.$e->detail->FaultDetail->errorMessage;
			if ($e->detail->FaultDetail->requestID) {
				$detail .= chr(10).'Request Id: '.$e->detail->FaultDetail->requestID;
			}
		}

		$faultcode = strtoupper($e->faultcode);
		if ($this->_config['email'] &&
				($faultcode == 'SERVER' || $faultcode == 'SOAP-ENV:SERVER')) {
			$client = $this->_soapClient;

			$requestDocument = new DOMDocument();
			$requestDocument->strictErrorChecking = false;
			$requestDocument->recover = true;
			$requestDocument->formatOutput = true;
			// only output <SOAP-ENV:Body>
			// remove all attributes
			$requestDocument->loadXML($e->soaprequest, LIBXML_NONET | LIBXML_NOEMPTYTAG | LIBXML_NOCDATA);

			$body = <<<EOT
Location: {$client->getLocation()}
URI: {$client->getUri()}
WSDL: {$client->getWsdl()}
Method: {$client->getLastMethod()}
Fault Message: {$e->faultstring}
$detail
EOT;
			$subject = 'Server-Absturz im SOAP-Service'
					.' ['.Mage::helper('schrack')->getWwsCountry().']'
					.' "'.Mage::getStoreConfig('general/store_information/name').'"';

			$mail = new Zend_Mail('utf-8');
			$mail->setFrom('no-reply@schrack.com', Mage::getStoreConfig('general/store_information/name'))
					->addTo(Mage::getStoreConfig('schrack/wws/email'))
					->setSubject($subject)
					->setBodyText($body)
					->createAttachment($requestDocument->saveXML(), 'text/xml', Zend_Mime::DISPOSITION_INLINE, Zend_Mime::ENCODING_QUOTEDPRINTABLE, 'soap-request.xml');
			$mail->send();
		}

		throw Mage::exception('Schracklive_Wws', 'SOAP failure "'.$e->faultcode.'":'.chr(10).$e->faultstring.chr(10).$detail, self::EXCEPTION_SOAP_FAULT);
	}

	/**
	 * @param string $message
	 * @param int    $code
	 * @return Schracklive_Wws_Exception
	 */
	protected function _wwsException($message = '', $code = 0) {
		$log = fopen(Mage::getBaseDir('var').DS.'log'.DS.'schracklive_soap_client_wws_error.log', 'a');
		fwrite($log, chr(10).$this->_startDateTime.' '.$this->_soapMethod.' '.$message);
		fclose($log);

		return Mage::exception('Schracklive_Wws', $message, $code);
	}

	/**
	 * @param int $code WWS exit/status code
	 * @param string $text
	 * @return bool
	 */
	protected function _processWwsMessage($code, $text) {
		$ok = true;
		$message = $this->parseWwsMessage($code, $text);
		if ($message) {
			$this->_messages->add($message);
			if ($message instanceof Mage_Core_Model_Message_Error) {
				$ok = false;
			}
		}
		return $ok;
	}

	/**
	 * @param int $code WWS exit/status code
	 * @param string $text WWS message
	 * @return Mage_Core_Message
	 */
	public function parseWwsMessage($code, $text) {
		if (!$text) {
			$text = 'WWS code '.$code;
		}

		$storeMessage = true;
		if ($code >= 200 && $code <= 299) {
			$type = 'notice';
		} elseif ($code >= 300 && $code <= 399) {
			$type = 'warning';
		} elseif ($code >= 500 && $code <= 599) {
			$storeMessage = false; // ignore this error class
		} else {
			$type = 'error';
		}

		$message = null;
		if ($storeMessage) {
			$message = Mage::getSingleton('core/message')->$type("{$text} [#{$code}]");
			$message->setIdentifier('WWS-'.$code);
		}
		return $message;
	}

	/**
	 * @return int
	 */
	public function getExitCode() {
		return $this->_exitCode;
	}

	/**
	 * @return Mage_Core_Model_Message_Collection
	 */
	public function getMessages() {
		return $this->_messages;
	}

	/**
	 * @return array
	 */
	public function getArguments() {
		return $this->_soapArguments;
	}

	public function getResponse() {
		return $this->_soapResponse;
	}

	public function getSoapRequest() {
		return $this->_soapClient->getLastRequest();
	}

	public function getSoapResponse() {
		return $this->_soapClient->getLastResponse();
	}

	/**
	 * @param $message
	 * @return Schracklive_Wws_Exception
	 */
	public function exception($message) {
		return $this->_wwsException($message, self::EXCEPTION_WWS_FAILURE);
	}

	protected function shouldMockWws () {
        return false;
    }

	protected function mockWws () {
    }

}
