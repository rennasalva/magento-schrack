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

//require_once 'Customweb/Core/String.php';
//require_once 'Customweb/PayUnity/Method/DefaultMethod.php';
//require_once 'Customweb/Payment/Util.php';



/**
 * @Method(paymentMethods={'Paydirekt'})
 */
class Customweb_PayUnity_Method_PaydirektMethod extends Customweb_PayUnity_Method_DefaultMethod {

	/**
	 *
	 * @param Customweb_PayUnity_Authorization_OppTransaction $transaction
	 * @param array $formData
	 * @return array
	 */
	public function getAuthorizationParameters(Customweb_PayUnity_Authorization_OppTransaction $transaction, array $formData){
		$parameters = parent::getAuthorizationParameters($transaction, $formData);
		$parameters['merchantTransactionId'] = $this->getMerchantTransactionId($transaction);
		return $parameters;
	}
	
	
	public function getMerchantTransactionId(Customweb_PayUnity_Authorization_OppTransaction $transaction){
		$merchantTransactionId = Customweb_Payment_Util::applyOrderSchema($this->getGlobalConfiguration()->getTransactionIdSchema(),
				$transaction->getExternalTransactionId(), 255);
		return Customweb_Core_String::_($merchantTransactionId)->replace('_', '-')->replace(' ', '')->toString();
	}
}