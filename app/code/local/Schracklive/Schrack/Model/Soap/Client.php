<?php

class Schracklive_Schrack_Model_Soap_Client extends Zend_Soap_Client {

	const SLOW_CALL_SECONDS = 2;

	protected $_schrackOptions = array(
		'system' => '',
		'socket_timeout' => 0,
		'log_calls' => false,
		'log_errors' => false, // @todo turn logging into service
		'log_transfer' => false,
		'log_id' => '',
		'log_info' => '', // $_SERVER['REMOTE_ADDR']
	);
	protected $_lastLogHeader = '';

    /**
     * prepared default Values for GrayLog GELF message.
     * can be extended with additional infos
     *
     * @var array
     * LOG LEVELS
     ***************************************************************************
     * 0 - Emergency: system is unusable
     * 1 - Alert: action must be taken immediately
     * 2 - Critical: critical conditions
     * 3 - Error: error conditions
     * 4 - Warning: warning conditions
     * 5 - Notice: normal but significant condition
     * 6 - Informational: informational messages
     * 7 - Debug: debug-level messages
     ***************************************************************************
     * @property level
     * @property short_message
     * @property details
     * @property script
     * @property script_line
     * @property _optional? you can add any additional information by adding a new key as long it is prefixed with _
     *
     */
    public $_grayLogMessage = null;
    private $_logResponseFilter = null;
    private $_logRequestFilter  = null;
    protected $_connection_timeout = -1;

    /**
     * @return int
     */
    public function getConnectionTimeout (){
        return $this->_connection_timeout;
    }

    /**
     * @param int $connection_timeout
     */
    public function setConnectionTimeout ( $connection_timeout ){
        $this->_connection_timeout = $connection_timeout;
    }

	public function __construct($wsdl = null, $options = null) {
		// in case we're called from getModel() we get only one paramenter
		if (is_array($wsdl)) {
			list($wsdl, $options) = $wsdl;
		}
		if (!is_array($options)) {
			$options = array();
		}
		$this->_processSchrackOptions($options);

		if (!$this->_schrackOptions['system']) {
			throw new Zend_Soap_Client_Exception('Option "schrack_system" is required.');
		}

		$cacheDir = ini_get('soap.wsdl_cache_dir');
		ini_set('soap.wsdl_cache_dir', Mage::getConfig()->getOptions()->getCacheDir());
		parent::__construct($wsdl, $options);
		ini_set('soap.wsdl_cache_dir', $cacheDir);

        $this->_grayLogMessage = array(
            "level" => 7,
            "short_message" => "",
            "details" => "",
            "script" => "unknown",
            "script_line" => 0
        );
	}

    public function setRequestLogFilter ( Schracklive_Schrack_Model_Soap_AbstractLogFilter $filter ) {
        $this->_logRequestFilter = $filter;
    }
    
    public function setResponseLogFilter ( Schracklive_Schrack_Model_Soap_AbstractLogFilter $filter ) {
        $this->_logResponseFilter = $filter;
    }
    
	public function setSchrackLogId($logId) {
		$this->_schrackOptions['log_id'] = $logId;
	}

	public function logLastCallAsError($message) {
		$this->_logError($this->_lastLogHeader.chr(10).$message);
	}

    public function guidv4()
    {
        // $data = random_bytes(16); // PHP 7 -> waiting for SCHRACK Magento BACKEND !!!!
        $data = openssl_random_pseudo_bytes(16);
        assert(strlen($data) == 16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

	public function __call($name, $arguments) {
		$startTimestamp = time();
		$startDateTime = date('Y-m-d H:i:s T', $startTimestamp);
		if (!$this->_schrackOptions['log_id']) {
			$this->_schrackOptions['log_id'] = $this->guidv4(); // TODO
		}

		$this->_lastLogHeader = $startDateTime.' '.$this->_schrackOptions['log_id'];
		$log = $this->_openCallLogWriting(chr(10).$this->_lastLogHeader.' '.$name.' '.$this->_schrackOptions['log_info']);
		try {
			$result = $this->_callMethod($name, $arguments);
		} catch (SoapFault $e) {
			$faultActor = property_exists($e, 'faultactor') ? '['.$e->faultactor.'] ' : '';
			$message = 'SOAP-FAULT "'.$e->faultcode.'": '.$faultActor.$e->faultstring;
			$this->_closeCallLogWriting($log, $message);
			$this->_logError($this->_lastLogHeader.chr(10).$message);
			$e->soaprequest = $this->getLastRequest();
    		$this->_logTransfer();
			throw $e;
		} catch (Exception $e) {
			$this->_closeCallLogWriting($log, 'FAILURE '.$e->getMessage());
			$this->_logError($this->_lastLogHeader.chr(10).$e->getMessage());
			throw $e;
		}

		$endTimestamp = time();
		if ($endTimestamp - $startTimestamp > self::SLOW_CALL_SECONDS) {
			$logText = chr(10).date('Y-m-d H:i:s T', $endTimestamp).' '.$this->_schrackOptions['log_id'].' SLOW';
		} else {
			$logText = '';
		}
        
        $customerString = PHP_EOL . 'User: ';
        $customerMail = null;
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        if ( $customer ) {
            $customerMail = $customer->getEmail();
        }
        if ( $customerMail && strlen($customerMail) > 0 ) {
            $customerString .= $customerMail . '/' . $customer->getSchrackWwsCustomerId();
        } else {
            $customerString .= '(anonymous)';
        }
        $realCustomer = Mage::getSingleton('customer/session')->getLoginCustomer();
        // i don't quite get it, since loginCustomer doesn't seem to be set anywhere....
        if ( !$realCustomer ) {
            $realCustomer = Mage::getSingleton('customer/session')->getLoggedInCustomer();
        }
        if ( $realCustomer ) {
            $customerString .= '/' . $realCustomer->getEmail();
        }
        
		$this->_closeCallLogWriting($log, $logText);
		$this->_logTransfer();
		return $result;
	}

	protected function _openCallLogWriting($text) {
		$log = null;
		if ($this->_schrackOptions['log_calls']) {
			$log = @fopen(Mage::getBaseDir('var').DS.'log'.DS.'schracklive_soap_client_'.$this->_schrackOptions['system'].'.log', 'a');
			if ($log) {
				@fwrite($log, $text);
				@fflush($log);
			}
		}
		return $log;
	}

	protected function _callMethod($name, $arguments) {
		$result = null;
		$callException = null;
		$defaultSocketTimeout = 0;
		if ($this->_schrackOptions['socket_timeout']) {
			$defaultSocketTimeout = (int)ini_get('default_socket_timeout');
			ini_set('default_socket_timeout', $this->_schrackOptions['socket_timeout']);
		}
		try {
			$result = parent::__call($name, $arguments);
		} catch (Exception $callException) {
			// simply catch the exception so we can reset the socket timeout
		}
		if ($defaultSocketTimeout) {
			ini_set('default_socket_timeout', $defaultSocketTimeout);
		}
		if ($callException) {
			throw $callException;
		}
		return $result;
	}

	protected function _closeCallLogWriting($log, $text='') {
		if ($log) {
			if ($text) {
				@fwrite($log, ' '.$text);
			}
			@fclose($log);
		}
	}

	protected function _logError($logHeader) {
		if (!$this->_schrackOptions['log_errors']) {
			return;
		}

		$log = @fopen(Mage::getBaseDir('var').DS.'log'.DS.'schracklive_soap_client_'.$this->_schrackOptions['system'].'_error.log',
						'a');
		if ($log) {
			@fwrite($log, chr(10).$logHeader);
			$this->_writeRequestLog($log);
			$this->_writeResponseLog($log);
			@fclose($log);
		}
	}

	protected function _logTransfer($isError=false) {
		if (!$this->_schrackOptions['log_transfer']) {
			return;
		}
        
        $customerString = PHP_EOL . 'User: ';
        $customerMail = null;
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        if ( $customer ) {
            $customerMail = $customer->getEmail();
        }
        if ( $customerMail && strlen($customerMail) > 0 ) {
            $customerString .= $customerMail . '/' . $customer->getSchrackWwsCustomerId();
        } else {
            $customerString .= '(anonymous)';
        }
        $realCustomer = Mage::getSingleton('customer/session')->getLoginCustomer();
        // i don't quite get it, since loginCustomer doesn't seem to be set anywhere....
        if ( !$realCustomer ) {
            $realCustomer = Mage::getSingleton('customer/session')->getLoggedInCustomer();
        }
        if ( $realCustomer ) {
            $customerString .= '/' . $realCustomer->getEmail();
        }
        
        $logHeader = $this->_lastLogHeader.' '.date('H:i:s', time()).$customerString;

		$log = @fopen(Mage::getBaseDir('var').DS.'log'.DS.'schracklive_soap_client_'.$this->_schrackOptions['system'].'_request.log','a');
		if ($log) {
			$this->_writeRequestLog($log, $logHeader);
			fclose($log);
		}
		$log = @fopen(Mage::getBaseDir('var').DS.'log'.DS.'schracklive_soap_client_'.$this->_schrackOptions['system'].'_response.log','a');
		if ($log) {
			$this->_writeResponseLog($log, $logHeader);
			@fclose($log);
		}
	}

	protected function _writeRequestLog($log, $logHeader='') {
		if ($logHeader) {
			@fwrite($log, chr(10).$logHeader);
		}
        $request = $this->getLastRequestHeaders().$this->getLastRequest();
        if ( $this->_logRequestFilter ) {
            $this->_logRequestFilter->filterLog($request);
        }
		@fwrite($log, chr(10).$request);
	}

	protected function _writeResponseLog($log, $logHeader='') {
		if ($logHeader) {
			@fwrite($log, chr(10).$logHeader);
		}
		$response = $this->getLastResponseHeaders().chr(10).$this->getLastResponse();
		if (!trim($response)) {
			$response = '[SOAP response missing.]';
		}
        if ( $this->_logResponseFilter ) {
            $this->_logResponseFilter->filterLog($response);
        }

        // Writes relevant response fields to database :
        if ($response) {
            if (stristr($response, 'insert_update_orderResponse')) {
                $dataOrderNumber    = 0;
                $dataCustomerNumber = '';
                $dataUserEmail      = '';
                $dataCustomerNumber = '';
                $dataAmountNet      = 0;
                $dataAmountTax      = 0;
                $dataAmountTot      = 0;
                $dataExitCode       = 1; // Default = 1: everything okay!
                $dataMemo           = '';
                $dataExitMessage    = '';
                $intHasDiscount     = 0;

                $dataHeaderSubstring = substr($logHeader, 24);
                $dataHeaderSubstringColumns = explode(' ', $dataHeaderSubstring);
                $dataUniqueLogId = $dataHeaderSubstringColumns[0];
                $dataUserEmailSubstring = $dataHeaderSubstringColumns[2];
                $dataUserEmailSubstringColumns = explode('/', $dataUserEmailSubstring);
                $dataUserEmail = $dataUserEmailSubstringColumns[0];
                preg_match('/<OrderNumber.*?>(.*)<\/OrderNumber>/', $response, $matches);
                if (isset($matches[1])) $dataOrderNumber = $matches[1];
                preg_match('/<CustomerNumber.*?>(.*)<\/CustomerNumber>/', $response, $matches);
                if (isset($matches[1])) $dataCustomerNumber = $matches[1];
                preg_match('/<AmountNet.*?>(.*?)<\/AmountNet>/', $response, $matches);
                if (isset($matches[1])) $dataAmountNet = $matches[1];
                preg_match('/<AmountVat.*?>(.*?)<\/AmountVat>/', $response, $matches);
                if (isset($matches[1])) $dataAmountTax = $matches[1];
                preg_match('/<AmountTot.*?>(.*?)<\/AmountTot>/', $response, $matches);
                if (isset($matches[1])) $dataAmountTot = $matches[1];
                preg_match('/<exit_code.*?>(.*)<\/exit_code>/', $response, $matches);
                if (isset($matches[1])) $dataExitCode = intval($matches[1], 10);
                preg_match('/<exit_msg.*?>(.*)<\/exit_msg>/', $response, $matches);
                if (isset($matches[1])) $dataExitMessage = $matches[1];
                preg_match('/<Memo.*?>(.*?)<\/Memo>/', $response, $matches);
                if (isset($matches[1])) $dataMemo = $matches[1];

                // Parsing memo field:
                if ($dataMemo) {
                    // Searching for DISCOUNT-Flag:
                    if (stristr($dataMemo, 'DISCOUNT')) {
                        $intHasDiscount = 1;
                        if (stristr($dataMemo, ';')) {
                            $memoValuKeyPairs = explode(';', $dataMemo);
                            foreach ($memoValuKeyPairs as $index => $memoValuKeyPair) {
                                list($cashDiscountKey, $cashDiscountInterestrate) = explode('=', $memoValuKeyPair);
                                if ($cashDiscountKey == 'DISCOUNT') {
                                    $cashDiscountInterestrateDecimal = floatval(str_replace(array('.', ','), '.', $cashDiscountInterestrate));
                                    Mage::log($dataOrderNumber . ' --> MEMO-DISCOUNT = ' . $cashDiscountInterestrateDecimal, null, 'insert_update_order_response_discountinformation.log');
                                }
                            }
                        } else {
                            list($cashDiscountKey, $cashDiscountInterestrate) = explode('=', $dataMemo);
                            if ($cashDiscountKey == 'DISCOUNT') {
                                $cashDiscountInterestrateDecimal = floatval(str_replace(array('.', ','), '.', $cashDiscountInterestrate));
                                Mage::log($dataOrderNumber . ' --> MEMO-DISCOUNT = ' . $cashDiscountInterestrateDecimal, null, 'insert_update_order_response_discountinformation.log');
                            }
                        }
                    }
                }

////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/// Disable/Enable E-Mail about Barskonto:
/*
if ($intHasDiscount == 1) {
    $mail = new Zend_Mail('utf-8');
    $country = strtoupper(Mage::getStoreConfig('schrack/general/country'));
    $mailSubject = 'Barskonto COUNTRY = ' . $country . '  >>> WWS-Order-ID: ' . $dataOrderNumber;
    $mailText = date('Y-m-d H:i:s') . ' -- ' . $mailSubject;
    try {
        $mail->setFrom(Mage::getStoreConfig('web/secure/base_url'))
            ->setSubject($mailSubject)
            ->setBodyHtml($mailText)
            ->addTo( Mage::getStoreConfig('schrackdev/customer/mappingDevelopmentMails') )
            ->send();
    } catch (Exception $ex) {
        Mage::log($mailText . ' Mail Transfer Failed: ' . Mage::getStoreConfig('schrackdev/customer/mappingDevelopmentMails'), null, '/barskonto_mail_err.log');
        Mage::log($ex, null, 'barskonto_mail_err.log');
    }
}
*/
////////////////////////////////////////////////////////////////////////////////////////////////////////////////

                $resource = Mage::getSingleton('core/resource');
                $writeConnection = $resource->getConnection('core_write');

                if ($dataUniqueLogId && $dataOrderNumber) {
                    // At First: write response:
                    $query = "INSERT INTO wws_insert_update_order_response"
                           . " SET unique_log_id = '" . $dataUniqueLogId . "',"
                           . " wws_order_id = '" . $dataOrderNumber . "',"
                           . " user_email = '" . $dataUserEmail . "',"
                           . " wws_customer_id = '" . $dataCustomerNumber . "',"
                           . " amount_net = " . $dataAmountNet . ","
                           . " amount_tax = " . $dataAmountTax . ","
                           . " amount_tot = " . $dataAmountTot . ","
                           . " base_currency = '" . Mage::getStoreConfig('currency/options/base') . "',"
                           . " exit_code = " . $dataExitCode . ","
                           . " exit_message = '" . $dataExitMessage . "',"
                           . " memo_string = '" . $dataMemo . "',"
                           . " has_discount = " . $intHasDiscount . ","
                           . " response_datetime = '" . date('Y-m-d H:i:s') . "'"
                           ;
                    try {
                        $writeConnection->query($query);
                    } catch (Exception $e) {
                        //----------------------------------------- graylog test
                        $this->_grayLogMessage['level'] = 3;
                        $this->_grayLogMessage['short_message'] = "Query execution failed.";
                        $this->_grayLogMessage['details'] = "Failed query:\n".$query."\n\nError Message:\n".$e->getMessage();
                        $this->_grayLogMessage['script'] = "app/code/local/Schracklive/Schrack/Model/Soap/Client.php";
                        $this->_grayLogMessage['script_line'] = 400;
                        //---------------------------------------------- execute
                        // $gelf = $this->prepareGrayLogGELF($this->_grayLogMessage);
                        // $this->sendGelfToGraylog($gelf);
                        //------------------------------------------------------
                        Mage::log($e->getMessage(), null, 'insert_update_order_response_db_error.log');
                        Mage::log($query, null, 'insert_update_order_response_db_error.log');
                    }

                    // At Second: mark corresponding WWS SOAP Request as 'successful':
                    $query = "UPDATE wws_insert_update_order_request"
                           . " SET response_fetched_successfully = 1,"
                           . " wws_order_id = '" . $dataOrderNumber . "'"
                           . " WHERE unique_log_id LIKE '" . $dataUniqueLogId . "'"
                           ;
                    try {
                        $writeConnection->query($query);
                    } catch (Exception $e) {
                        //----------------------------------------- graylog test
                        $this->_grayLogMessage['level'] = 3;
                        $this->_grayLogMessage['short_message'] = "Query execution failed.";
                        $this->_grayLogMessage['details'] = "Failed query:\n".$query."\n\nError Message:\n".$e->getMessage();
                        $this->_grayLogMessage['script'] = "app/code/local/Schracklive/Schrack/Model/Soap/Client.php";
                        $this->_grayLogMessage['script_line'] = 415;
                        //---------------------------------------------- execute
                        // $gelf = $this->prepareGrayLogGELF($this->_grayLogMessage);
                        // $this->sendGelfToGraylog($gelf);
                        //------------------------------------------------------
                        Mage::log($e->getMessage(), null, 'insert_update_order_response_db_error.log');
                        Mage::log($query, null, 'insert_update_order_response_db_error.log');
                    }
                } else {
                    // Some Error occured from WWS :
                    // Example:
                    //     <exit_code xsi:type="xsd:int">436</exit_code>
                    //     <exit_msg xsi:type="xsd:string">Artikel [LIVT4259--] Keine gueltige Menge/Verpackung fuer Versand: [1]</exit_msg>
                    if (is_int($dataExitCode) && $dataExitCode > 1) {
                        $query = "INSERT INTO wws_insert_update_order_response"
                               . " SET unique_log_id = '" . $dataUniqueLogId . "',"
                               . " user_email = '" . $dataUserEmail . "',"
                               . " exit_code = " . $dataExitCode . ","
                               . " exit_message = '" . $dataExitMessage . "',"
                               . " response_datetime = '" . date('Y-m-d H:i:s') . "'"
                               ;

                        try {
                            $writeConnection->query($query);
                        } catch (Exception $e) {
                            //------------------------------------- graylog test
                            $this->_grayLogMessage['level'] = 3;
                            $this->_grayLogMessage['short_message'] = "Query execution failed.";
                            $this->_grayLogMessage['details'] = "Failed query:\n".$query."\n\n".
                                                                "Error Message:\n".$e->getMessage();
                            $this->_grayLogMessage['script'] = "app/code/local/Schracklive/Schrack/Model/Soap/Client.php";
                            $this->_grayLogMessage['script_line'] = 453;
                            //------------------------------------------ execute
                            // $gelf = $this->prepareGrayLogGELF($this->_grayLogMessage);
                            // $this->sendGelfToGraylog($gelf);
                            //--------------------------------------------------
                            Mage::log($e->getMessage(), null, 'insert_update_order_response_db_error.log');
                            Mage::log($query, null, 'insert_update_order_response_db_error.log');
                        }
                    }
                }
            }
        } else {
            Mage::log(date('Y-m-d H:i:s') . 'No Response Returned', null, 'insert_update_order_response_db_error.log');
        }
		@fwrite($log, chr(10).$response.chr(10));
	}


    /**
     * @param $gelf json
     * @return void
     */
    //======================================================== sendGelfToGraylog
    protected function sendGelfToGraylog($gelf){
    //==========================================================================
        //---------------------------------- establish tcp connection to graylog
        $fp = fsockopen("sl-graylog1.schrack.com", 1504,
             $errno, $errstr,30);
        //-------------------------------------------------- write log to server
        if (!$fp) {
            echo "$errstr ($errno)<br />\n";
        } else {
            fwrite($fp, $gelf);
            while (!feof($fp)) {
                echo fgets($fp, 1025);
            }
            fclose($fp);
        }
    } //============================================ sendGelfToGraylog ***END***


    /**
     * @param $message array
     * @return string
     */
    //======================================================= prepareGrayLogGELF
    protected function prepareGrayLogGELF($message) {
    //==========================================================================
        //----------------------------------------------------------- build GELF
        $GELF = array(
            "version" => "1.1",
            "host" => gethostname(),
            "short_message" => $message['short_message'],
            "full_message" => $message['short_message'] . "\nFurther details: \n\n" . $message['details'],
            "timestamp" => time(),
            "level" => $message['level'],
            "_occurrence" => $message['script'] . "\nLine:\n" . $message['script_line']
        );
        //--------------------------------------------- adding additinonal Infos
        foreach($message as $k => $v):
            if (substr($k,0,1) == '_'){
                $GELF[$k] = $v;
            }
        endforeach;
        //--------------------------------------------------------------- RETURN
        return json_encode($GELF);
    } //=========================================== prepareGrayLogGELF ***END***


    protected function _processSchrackOptions(&$options) {
		$optionsArray = $options; // we may not use unset within a foreach loop
		foreach ($optionsArray as $name => $value) {
			if (substr($name, 0, 8) == 'schrack_') {
				$this->_schrackOptions[substr($name, 8)] = $value;
				unset($options[$name]); // we need to filter out custom options for Zend_Soap_Client
			}
		}
	}

    public function _doRequest(Zend_Soap_Client_Common $client, $request, $location, $action, $version, $one_way = null) {
        if ( ! is_integer($this->_connection_timeout) || $this->_connection_timeout < 0 ) {
            return parent::_doRequest($client, $request, $location, $action, $version, $one_way);
        } else {
            $curl = curl_init($location);
            curl_setopt($curl, CURLOPT_VERBOSE, FALSE);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($curl, CURLOPT_POST, TRUE);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
            curl_setopt($curl, CURLOPT_HEADER, FALSE);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: text/xml"));
            curl_setopt($curl, CURLOPT_TIMEOUT, $this->_connection_timeout);
            $password = $this->getHttpPassword();
            if ($password) {
                curl_setopt($curl, CURLOPT_USERPWD, "{$this->getHttpLogin()}:$password");
                curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            }
            $response = curl_exec($curl);
            if (curl_errno($curl)) {
                throw new Exception(curl_error($curl));
            }
            curl_close($curl);
            if (!$one_way) {
                return ($response);
            }
        }
    }

}
