<?php

/**
  * You are allowed to use this API in your web application.
 *
 * Copyright (C) 2018 by customweb GmbH
 *
 * This program is licenced under the customweb software licence. With the
 * purchase or the installation of the software in your application you
 * accept the licence agreement. The allowed usage is outlined in the
 * customweb software licence which can be found under
 * http://www.sellxed.com/en/software-license-agreement
 *
 * Any modification or distribution is strictly forbidden. The license
 * grants you the installation in one application. For multiuse you will need
 * to purchase further licences at http://www.sellxed.com/shop.
 *
 * See the customweb software licence agreement for more details.
 *
 */

//require_once 'Customweb/Http/Url.php';
//require_once 'Customweb/Http/Response.php';
//require_once 'Customweb/Http/Request.php';
//require_once 'Customweb/Http/ConnectionException.php';

/**
 * 
 * @deprecated Use instead the core implementation
 */
class Customweb_Http_Util {
	
	
	public final function __construct(){
		
	}
	
	public static function fileGetContent($url, $exceptions = false) {
		
		try {
			$request = new Customweb_Http_Request($url);
			$response = $request->send();
			
			if ($response->getStatusCode() != 200) {
                //############################################### SCHRACK_CUSTOM
                // doing extra logging -> Payment Exception
                //--------------------------------------------------------------
                Mage::log($response->getStatusCode().': '.$response->getStatusMessage(), null, '/payment/payment-exception.log');
                //##################################### SCHRACK_CUSTOM ***END***
				throw new Exception($response->getStatusCode().': '.$response->getStatusMessage());
				
			}
			return $response->getBody();
		}
		catch (Exception $e){
            //################################################### SCHRACK_CUSTOM
            // doing extra logging -> Payment Exception
            //------------------------------------------------------------------
            Mage::log($e->__toString(), null, '/payment/payment-exception.log');
            //######################################### SCHRACK_CUSTOM ***END***
			if($exceptions){
				throw $e;
			}
			return false;
		}		
	}
	
	
	
	
}