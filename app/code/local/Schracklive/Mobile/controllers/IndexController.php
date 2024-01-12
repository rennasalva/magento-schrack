<?php

class Schracklive_Mobile_IndexController extends Mage_Core_Controller_Front_Action {
    
    // group id that is allowed to get prices etc for customer other than themselves
    const CUSTOMER_SUDO_GROUP_ID = 4;

	// @todo gwt rid of the Zend controller, the Magento controller is good enough
	public function indexAction() {
		$httpRequest = $_REQUEST;
		$httpMethod = $_REQUEST['method'];
		
		$this->checkAccess($httpMethod);
		
		$toogleJson = ($httpMethod != '' && in_array( $httpMethod, get_class_methods('Schracklive_Mobile_Model_JsonHandler')));
		
		if ( $toogleJson ) {
			
			// JSON -------------------------------------------			
			
			// hier angeschrieben statt im __construct von JsonHandler damit Methodennamen beim get_class_methods passen
			Zend_Mail::setDefaultTransport(new Zend_Mail_Transport_Smtp('localhost'));
			
			$server = new Zend_Json_Server();
			$jsonRequest = new Zend_Json_Server_Request();			
			//$json_request_keys = array_keys($json_http_request);			
			$jsonRequest->setMethod($httpRequest['method']);
			unset($httpRequest['method']);
			$jsonRequest->setParams($httpRequest);
			$server = $server->setRequest($jsonRequest);
			$server->setAutoEmitResponse(false);
			$server->setClass(Mage::getConfig()->getModelClassName('mobile/jsonhandler'));
			$server->returnResponse(true);
			
			$logResponse = Mage::getStoreConfig('schrackdev/mobile/log') ? true : false;
			if ($logResponse) {
				$startDateTime = date('Y-m-d H:i:s');
			}
			
			$response = $server->handle();
		}
		else {
			
			// REST -------------------------------------------
			
			$server = new Zend_Rest_Server();
			$server->setClass(Mage::getConfig()->getModelClassName('mobile/handler'));
			$server->returnResponse(true);

			$logResponse = Mage::getStoreConfig('schrackdev/mobile/log') ? true : false;
			if ($logResponse) {
				$startDateTime = date('Y-m-d H:i:s');
			}

			$response = $server->handle();
		}

		if ($logResponse) {
			if( $toogleJson ) {
				
				// JSON -------------------------------------------
				$result_value_array = ($response->getResult()) ? $response->getResult() : array();
				$this->logResponse($startDateTime, print_r($server->getRequest()->getParams(), true), print_r($result_value_array, true));
				
			}
			else {
				
				// REST -------------------------------------------				
				$this->logResponse($startDateTime, join("\n", $server->getHeaders()), $response);
			}
		}

		if( $toogleJson ) {
			
			// JSON -------------------------------------------
			
			// $apiConfigCharset = Mage::getStoreConfig("api/config/charset");
				
			$this->getResponse()
					->clearHeaders()
					->setHeader('Content-Type','application/json')
					->setBody(json_encode($response->getResult()));      
		}
		else {
			
			// REST -------------------------------------------
			
			foreach ($server->getHeaders() as $header) {
				@list($h, $v) = explode(':', $header);
				if ($h && $v) {
					$this->getResponse()->setHeader($h, $v);

					Mage::Log("$h => $v");
				}
			}

			$this->getResponse()->setBody($response);		
		}		
        Mage::getSingleton('customer/session')->logout();
	}

    public function documentsDownloadAction() {
		$this->checkAccess('download');
        Mage::helper("schrackcustomer/order")->documentsDownloadAction();
        Mage::getSingleton('customer/session')->logout();
    }
    
	protected function checkAccess ( $httpMethod ) {
        $session = Mage::getSingleton('customer/session');
        if ($session->isLoggedIn()) {
            $session->logout();
        }
        if ( isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && strlen($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) > 8 ) {
        	$base64 = explode(' ',$_SERVER['REDIRECT_HTTP_AUTHORIZATION'])[1];
        	$usrPw = explode(':',base64_decode($base64));
        	$_SERVER['PHP_AUTH_USER'] = array_shift($usrPw);
        	$_SERVER['PHP_AUTH_PW'] = implode(':',$usrPw);
        }
		if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
			$httpd_username = filter_var($_SERVER['PHP_AUTH_USER'], FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_HIGH | FILTER_FLAG_ENCODE_LOW);
			$httpd_password = html_entity_decode(filter_var($_SERVER['PHP_AUTH_PW'], FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_HIGH | FILTER_FLAG_ENCODE_LOW));
			
			try {
				$session->login($httpd_username, $httpd_password);
			
                // check whether user is a system user that may do "sudo" to other customers
                $customer = $session->getCustomer();
                $reqCustomerId = $this->getRequest()->getParam('customer_id');
                if ( (int)$customer->getGroupId() === self::CUSTOMER_SUDO_GROUP_ID && isset($reqCustomerId)) {
					$loggedInCustomer = $session->getCustomer();
					$contact = Mage::helper('account')->getSystemContactByWwsCustomerId($reqCustomerId);
					if (!is_null($contact) && $contact->getId()) {
						$customer = $contact;
						$session->setLoggedInCustomer($loggedInCustomer); // i.e., the logged-in customer, as opposed to the customer we're acting on-behalf-of in case of a "sudo"
                        // so, if c.friedl@at.schrack.lan does something in the name of cust. 777777, 
                        // then c.friedl@at.schrack.lan is the loggedInCustomer
					}
                }
                else {
                    unset($_REQUEST['customer_id']);
                }
				$session->setCustomerAsLoggedIn($customer);
                $session->renewSession();
            
				return true;
			} catch (Exception $e) {
				header('WWW-Authenticate: Basic realm="Schrack iPhone"');
				header('HTTP/1.0 401 Unauthorized');
				echo 'Access denied';
				exit;
			}


			//$session = Mage::getSingleton('customer/session');
		} else {
            if ( $this->isMethodAnonymousAlowed($httpMethod) ) {
                unset($_REQUEST['customer_id']);
            }
            else {
                header('WWW-Authenticate: Basic realm="Schrack iPhone"');
                header('HTTP/1.0 401 Unauthorized');
                echo 'Access denied';
                exit;
            }
		}
	}
    
    private function isMethodAnonymousAlowed ( $httpMethod ) {
        return    $httpMethod == 'init'
               || $httpMethod == 'search' 
               || $httpMethod == 'searchSolr'
               || $httpMethod == 'suggestArticles'
               || $httpMethod == 'getArticle'
               || $httpMethod == 'getTranslationTimestamp'
               || $httpMethod == 'getTranslations'
                ;
    }

	protected function logResponse($startDateTime, $headers, $response) {
		$endDateTime = date('Y-m-d H:i:s');
		list(, $endTime) = explode(' ', $endDateTime);
		$dateTime = $startDateTime.(($startDateTime != $endDateTime) ? '-'.$endTime : '');
        $user = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : '(anonymous)';

		$log = fopen(Mage::getBaseDir('var').DS.'log'.DS.'schracklive_rest_server_iphone_response.log', 'a');
		fwrite($log, $dateTime.' '.$_SERVER['REMOTE_ADDR'].chr(10).
				$_SERVER['REQUEST_URI'].chr(10).
                $user.chr(10).
				$headers.chr(10).chr(10).
				$response.chr(10)
		);
		fclose($log);
	}

}

?>
