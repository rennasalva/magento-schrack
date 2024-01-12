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

//require_once 'Customweb/Payment/Authorization/Server/IAdapter.php';
//require_once 'Customweb/I18n/Translation.php';
//require_once 'Customweb/Payment/Authorization/Method/CreditCard/ElementBuilder.php';
//require_once 'Customweb/PayUnity/Method/DefaultMethod.php';
//require_once 'Customweb/Payment/Authorization/Moto/IAdapter.php';



/**
 * @Method(paymentMethods={'CreditCard', 'AmericanExpress', 'Diners', 'DiscoverCard', 'MasterCard', 'Visa', 'VisaElectron', 'Maestro', 'CarteBleue', 'Dankort', 'Jcb', 'Vpay', 'BCMC', 'ChinaUnionpay'})
 */
class Customweb_PayUnity_Method_CreditCardMethod extends Customweb_PayUnity_Method_DefaultMethod {

	public function getVisibleFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $customerPaymentContext, $authorizationMethod){
		if ($authorizationMethod == Customweb_Payment_Authorization_Server_IAdapter::AUTHORIZATION_METHOD_NAME) {
			if ($aliasTransaction !== null && $aliasTransaction !== 'new') {
				return array();
			}
			
			$formBuilder = new Customweb_Payment_Authorization_Method_CreditCard_ElementBuilder();
			$formBuilder->setCardHolderFieldName('card.holder')->setCardNumberFieldName('card.number')->setCvcFieldName('card.cvv')->setExpiryMonthFieldName(
					'card.expiryMonth')->setExpiryYearFieldName('card.expiryYear')->setExpiryYearNumberOfDigits(4)->setFixedBrand(true)->setSelectedBrand(
					$this->getPaymentMethodName())->setCardHandlerByBrandInformationMap($this->getPaymentInformationMap(), 
					$this->getPaymentMethodName(), 'brand')->setCardHolderName(
					$orderContext->getBillingFirstName() . ' ' . $orderContext->getBillingLastName());
			return $formBuilder->build();
			

		}
		return array();
	}

	public function getAuthorizationParameters(Customweb_PayUnity_Authorization_OppTransaction $transaction, array $formData){
		$parameters = parent::getAuthorizationParameters($transaction, $formData);
		if ($transaction->getAuthorizationMethod() == Customweb_Payment_Authorization_Server_IAdapter::AUTHORIZATION_METHOD_NAME ||
				 $transaction->getAuthorizationMethod() == Customweb_Payment_Authorization_Moto_IAdapter::AUTHORIZATION_METHOD_NAME) {
			foreach (array(
				'card.holder',
				'card.number',
				'card.expiryMonth',
				'card.expiryYear',
				'card.cvv' 
			) as $key) {
				$formDataKey = str_replace('.', '_', $key);
				if (isset($formData[$formDataKey])) {
					$parameters[$key] = $formData[$formDataKey];
				}
			}
		}
		return $parameters;
	}

	public function getAliasForDisplay($response){
		$aliasForDisplay = 'xxxx xxxx xxxx ' . $response->card->last4Digits;
		if ($response->card->expiryMonth && $response->card->expiryYear) {
			$aliasForDisplay .= ' (' . $response->card->expiryMonth . '/' . substr($response->card->expiryYear, -2) . ')';
		}
		return $aliasForDisplay;
	}
	
	public function getPaymentMethodBrand()
	{
		if (strtolower($this->getPaymentMethodName()) == 'creditcard') {
			$activeBrands = $this->getPaymentMethodConfigurationValue('active_brands');
			return implode(" ", $activeBrands);
		}
		else {
			return parent::getPaymentMethodBrand();
		}
	}
	
	public function getAdditionalWidgetOptionString(Customweb_Payment_Authorization_ITransaction $transaction){
		return 'brandDetection: true,';
	}
	
	public function validate(
			Customweb_Payment_Authorization_IOrderContext $orderContext,
			Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext,
			array $formData
			) {
				parent::validate($orderContext, $paymentContext, $formData);
				if (strtolower($this->getPaymentMethodName()) == 'creditcard') {
					$activeBrands = $this->getPaymentMethodConfigurationValue('active_brands');
					if (empty($activeBrands)) {
						throw new Exception(Customweb_I18n_Translation::__('No brands have been configured.'));
					}
				}
	}
}