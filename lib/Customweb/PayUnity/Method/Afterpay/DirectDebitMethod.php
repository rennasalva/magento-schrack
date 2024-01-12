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

//require_once 'Customweb/Payment/Authorization/Method/Sepa/ElementBuilder.php';
//require_once 'Customweb/Payment/Authorization/Method/Sepa/Iban.php';
//require_once 'Customweb/PayUnity/Method/Afterpay/PayLaterMethod.php';



/**
 * @Method(paymentMethods={'AfterpayDirectDebit'})
 */
class Customweb_PayUnity_Method_Afterpay_DirectDebitMethod extends Customweb_PayUnity_Method_Afterpay_PayLaterMethod{

	public function getVisibleFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $customerPaymentContext, $authorizationMethod){
		$builder = new Customweb_Payment_Authorization_Method_Sepa_ElementBuilder();
		
		$builder->setIbanFieldName('iban');
		$elements = $builder->build();
		
		return array_merge(
				parent::getVisibleFormFields($orderContext, $aliasTransaction, $failedTransaction, $customerPaymentContext, $authorizationMethod), 
				$elements);
	}

	public function getAuthorizationParameters(Customweb_PayUnity_Authorization_OppTransaction $transaction, array $formData){
		$parameters = parent::getAuthorizationParameters($transaction, $formData);		
		if (isset($formData['iban']) && !empty($formData['iban'])) {
			$iban = $formData['iban'];
			$handler = new Customweb_Payment_Authorization_Method_Sepa_Iban();
			$iban = $handler->sanitize($iban);
			$handler->validate($iban);
			$parameters['customParameters[AFTERPAY_IBAN]'] = $iban;
		}
		else{
			throw new Exception('The IBAN needs to be set.');
		}		
		return $parameters;
	}

}