<?php

class Schracklive_Geoip_Model_Customer_Observer {

	/**
	 * Generates the url the user should be redirected to after user authentication has failed at the current country shop,
	 * but the user was found in a different country shop. (ie: .pl user is redirected to .pl shop if they try to log into the .at shop
	 * without being registered in .at as well)
	 *
	 * @event schrack_customer_customer_not_authenticated
	 * @param Varien_Object      $observer
	 * @param Varien_Http_Client $testClient
	 * @throws Mage_Core_Exception
	 */
	public function queryRedirect($observer, Varien_Http_Client $testClient = null) {
		/** @var Schracklive_SchrackCustomer_Model_Customer $customer */
		$customer = $observer->getModel();
		$login = $observer->getLogin();
		$password = $observer->getPassword();

		if ($testClient) { // use external $client if supplied for unit testing
			$client = $testClient;
		} else {
            $url = Mage::getStoreConfig('schrack/general/shopSwitch');
			$client = new Varien_Http_Client($url, array('maxredirects' => 0));
		}
		$client->setParameterGet(array('email' => $login, 'password' => $password));
        
        try {
            $response = $client->request();

            $body = json_decode($response->getBody(), true);
            if (isset($body['wws_cust_id']) && isset($body['secureUrl'])) {
                $url = $this->_generateRedirectUrl($body['secureUrl'], $login);
                $customer->setRedirectUrl($url);
            }
        } catch ( Exception $ex ) {
            Mage::logException($ex);
            $response = $client->getLastResponse();
            Mage::log('switch response was: '.$response);
            $newEx = Mage::exception('Mage_Core', Mage::helper('customer')->__('Invalid login or password.') . ' (2)',
                Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD
            );
            throw $newEx;
        }
	}

	/**
	 * Creates the link to the "landing page"
	 */
	protected function _generateRedirectUrl($url, $login) {
		$key = Mage::helper('geoip')->generateKey($login, $_SERVER['REMOTE_ADDR']);
		$redirectUrl = $url.'/customer/account/?k='.$key;

		return $redirectUrl;
	}

}
