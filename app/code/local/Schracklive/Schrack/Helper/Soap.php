<?php

class Schracklive_Schrack_Helper_Soap {

	/**
	 * Gets a fully-configured SOAP client.
	 *
	 * @param string $wsdl
	 * @param array  $options
	 * @return Zend_Soap_Client
	 */
	public function createClient($wsdl, array $options=array()) {
		$options['soap_version'] = SOAP_1_1;
		$options['schrack_log_calls'] = true;
		$options['schrack_log_errors'] = true;

		if (Mage::getStoreConfig('schrackdev/development/test')) {
			$options['cache_wsdl'] = false;
		}

		$soapClient = Mage::getModel('schrack/soap_client', array($wsdl, $options));

		return $soapClient;
	}

}
