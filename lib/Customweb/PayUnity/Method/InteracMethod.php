<?php

/**
 *  * You are allowed to use this API in your web application.
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

//require_once 'Customweb/PayUnity/Method/DefaultMethod.php';
//require_once 'Customweb/PayUnity/IConstants.php';



/**
 * @Method(paymentMethods={'Interac'})
 */
class Customweb_PayUnity_Method_PoliMethod extends Customweb_PayUnity_Method_DefaultMethod {

	public function getPaymentTypeCode($capturingMode = null)
	{
		return Customweb_PayUnity_IConstants::PAYMENT_TYPE_PREAUTHORIZATION;
	}
	
	public function getAuthorizationParameters(Customweb_PayUnity_Authorization_OppTransaction $transaction, array $formData){
		$parameters = parent::getAuthorizationParameters($transaction, $formData);
		$billingAddress = $transaction->getTransactionContext()->getOrderContext()->getBillingAddress();
		$parameters['bankAccount.country'] = $billingAddress->getCountryIsoCode();
		$parameters['bankAccount.holder'] = $billingAddress->getFirstName().' '.$billingAddress->getlastName();
		return $parameters;
	}	
	
	/**
	 * @param Customweb_Payment_Authorization_ITransaction $transaction
	 * @param stdClass $response
	 * @return boolean
	 */
	protected function validatePaymentType(Customweb_Payment_Authorization_ITransaction $transaction, $response)
	{
		if (!isset($response->paymentType)) {
			return false;
		}
		if ($response->paymentType == 'DB' || $response->paymentType == 'RC' || $response->paymentType == 'PA'){
				return true;
		}
		return false;
	}

}