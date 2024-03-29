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

//require_once 'Customweb/Util/Country.php';
//require_once 'Customweb/Payment/Authorization/Server/IAdapter.php';
//require_once 'Customweb/I18n/Translation.php';
//require_once 'Customweb/PayUnity/Method/DefaultMethod.php';
//require_once 'Customweb/PayUnity/IConstants.php';
//require_once 'Customweb/Payment/Authorization/Moto/IAdapter.php';


/**
 * @Method(paymentMethods={'SofortUeberweisung'})
 */
class Customweb_PayUnity_Method_SofortUeberweisungMethod extends Customweb_PayUnity_Method_DefaultMethod
{
	public function getAuthorizationParameters(Customweb_PayUnity_Authorization_OppTransaction $transaction, array $formData) {
		$parameters = parent::getAuthorizationParameters($transaction, $formData);
		if ($transaction->getAuthorizationMethod() == Customweb_Payment_Authorization_Server_IAdapter::AUTHORIZATION_METHOD_NAME
				|| $transaction->getAuthorizationMethod() == Customweb_Payment_Authorization_Moto_IAdapter::AUTHORIZATION_METHOD_NAME) {
			$parameters['bankAccount.country'] = $transaction->getTransactionContext()->getOrderContext()->getBillingCountryIsoCode();
		}
		return $parameters;
	}

	public function getPaymentTypeCode($capturingMode = null)
	{
		return Customweb_PayUnity_IConstants::PAYMENT_TYPE_DEBIT;
	}
	
	
	public function getAdditionalWidgetOptionString(Customweb_Payment_Authorization_ITransaction $transaction){
		$selected = $this->getPaymentMethodConfigurationValue('country_restriction');
		if(empty($selected)){
			return '';
		}
		$option = 'sofortCountries: {';
		foreach($selected as $value){
			$country = Customweb_Util_Country::getCountryByCode($value);
			$option .= $value .': "'.Customweb_I18n_Translation::__($country['name']).'",'; 
		}
		
		
		$option .= '},';
		return $option;
	}
}