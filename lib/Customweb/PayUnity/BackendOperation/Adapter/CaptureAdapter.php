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

//require_once 'Customweb/PayUnity/BackendOperation/Adapter/AbstractAdapter.php';
//require_once 'Customweb/Payment/BackendOperation/Adapter/Service/ICapture.php';
//require_once 'Customweb/I18n/Translation.php';


/**
 * @Bean
 */
class Customweb_PayUnity_BackendOperation_Adapter_CaptureAdapter extends Customweb_PayUnity_BackendOperation_Adapter_AbstractAdapter implements Customweb_Payment_BackendOperation_Adapter_Service_ICapture
{
	public function capture(Customweb_Payment_Authorization_ITransaction $transaction)
	{
		

		$this->partialCapture($transaction, $transaction->getUncapturedLineItems(), true);
	}

	public function partialCapture(Customweb_Payment_Authorization_ITransaction $transaction, $items, $close)
	{
		

		$transaction->partialCaptureDry($items, $close);
		$response = $this->sendBackofficeRequest($transaction, $this->getParameterBuilder($transaction)->buildCaptureParameters($items));
		if ($response->result->code != '000.000.000'
			&& $response->result->code != '000.600.000'
			&& strpos($response->result->code, '000.100.1') !== 0) {
			throw new Exception(Customweb_I18n_Translation::__($response->result->description));
		}
		$transactionCapture = $transaction->partialCapture($items, $close, Customweb_I18n_Translation::__($response->result->description));
		$transactionCapture->setParameters($this->flattenObject($response));
	}

	
}