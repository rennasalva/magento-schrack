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

//require_once 'Customweb/PayUnity/AbstractAdapter.php';
//require_once 'Customweb/PayUnity/Request.php';
//require_once 'Customweb/PayUnity/BackendOperation/Adapter/ParameterBuilder.php';


abstract class Customweb_PayUnity_BackendOperation_Adapter_AbstractAdapter extends Customweb_PayUnity_AbstractAdapter
{
	/**
	 * @param Customweb_Payment_Authorization_ITransaction $transaction
	 * @param array $parameters
	 * @return stdClass
	 */
	protected function sendBackofficeRequest(Customweb_Payment_Authorization_ITransaction $transaction, array $parameters)
	{
		$request = new Customweb_PayUnity_Request($this->getBackofficeUrl($transaction->getPaymentId()),$this->getConfiguration());
		$request->setMethod(Customweb_PayUnity_Request::METHOD_POST);
		$request->setData($parameters);
		return $request->send();
	}

	/**
	 * @param string $paymentId
	 * @return string
	 */
	protected function getBackofficeUrl($paymentId)
	{
		return $this->getConfiguration()->getBaseUrl() . '/v1/payments/' . $paymentId;
	}

	/**
	 * @param Customweb_Payment_Authorization_ITransaction $transaction
	 * @return Customweb_PayUnity_BackendOperation_Adapter_ParameterBuilder
	 */
	protected function getParameterBuilder(Customweb_Payment_Authorization_ITransaction $transaction)
	{
		return new Customweb_PayUnity_BackendOperation_Adapter_ParameterBuilder($this->getContainer(), $transaction);
	}
}