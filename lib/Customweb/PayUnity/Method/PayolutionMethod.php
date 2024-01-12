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

//require_once 'Customweb/I18n/Translation.php';
//require_once 'Customweb/PayUnity/Method/DefaultMethod.php';



/**
 * @Method(paymentMethods={'PayolutionElv', 'PayolutionIns'})
 */
class Customweb_PayUnity_Method_PayolutionMethod extends Customweb_PayUnity_Method_DefaultMethod {

	public function isDirectCapturingSupported(){
		return false;
	}

	public function getAuthorizationParameters(Customweb_PayUnity_Authorization_OppTransaction $transaction, array $formData){
		$parameters = array();
		$company = $transaction->getTransactionContext()->getOrderContext()->getBillingAddress()->getCompanyName();
		if(!empty($company)){
			if(!isset($formData['company_uid']) || $formData['company_uid'] == ''){
				throw new Exception(Customweb_I18n_Translation::__('The company vat number number needs to be set.'));
			}
			$companyUID = $formData['company_uid'];
			$transaction->getTransactionContext()->getPaymentCustomerContext()->updateMap(array(
				'companyUID' => $companyUID
			));
			$parameters['customParameters[PAYOLUTION_COMPANY_NAME]'] = $company;
			$parameters['customParameters[PAYOLUTION_TRX_TYPE]'] = 'B2B';
			$parameters['customParameters[PAYOLUTION_COMPANY_UID]'] = $companyUID;
			
		}
		else{
			$dateOfBirth = $transaction->getTransactionContext()->getOrderContext()->getBillingAddress()->getDateOfBirth();
			if ($this->isDateOfBirthValid($formData)) {
				$dateOfBirth = DateTime::createFromFormat('Y-m-d', 
						$formData['date_of_birth_year'] . '-' . $formData['date_of_birth_month'] . '-' . $formData['date_of_birth_day']);
				$transaction->getTransactionContext()->getPaymentCustomerContext()->updateMap(array(
					'birthDate' => $dateOfBirth 
				));
			}
			if (!empty($dateOfBirth) && ($dateOfBirth instanceof $dateOfBirth)) {
				$parameters['customer.birthDate'] = $dateOfBirth->format('Y-m-d');
			}
			else {
				throw new Exception(Customweb_I18n_Translation::__('The date of birth needs to be set.'));
			}
		}
		return array_merge(parent::getAuthorizationParameters($transaction, $formData), $parameters);
	}

	public function getVisibleFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $customerPaymentContext, $authorizationMethod){
		$elements = parent::getVisibleFormFields($orderContext, $aliasTransaction, $failedTransaction, $customerPaymentContext, 
								$authorizationMethod);
		$company = $orderContext->getBillingAddress()->getCompanyName();
		if(!empty($company)){
			$elements = array_merge($elements, $this->getCompanyUIDElement($orderContext, $customerPaymentContext));	
		}
		else{
			$dateOfBirth = $orderContext->getBillingAddress()->getDateOfBirth();
			if (empty($dateOfBirth) || !($dateOfBirth instanceof DateTime)) {
				$elements = array_merge($elements, $this->getBirthdayElements($orderContext, $customerPaymentContext));
			}
		}

		return $elements;
	}
}