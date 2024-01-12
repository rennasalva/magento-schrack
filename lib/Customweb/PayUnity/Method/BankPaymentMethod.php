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

//require_once 'Customweb/Form/Validator/Checked.php';
//require_once 'Customweb/Payment/Authorization/Server/IAdapter.php';
//require_once 'Customweb/Form/HiddenElement.php';
//require_once 'Customweb/Form/Control/Html.php';
//require_once 'Customweb/Core/Util/Rand.php';
//require_once 'Customweb/PayUnity/Method/DefaultMethod.php';
//require_once 'Customweb/Payment/Authorization/Recurring/IAdapter.php';
//require_once 'Customweb/Form/Element.php';
//require_once 'Customweb/Form/Control/HiddenInput.php';
//require_once 'Customweb/I18n/Translation.php';
//require_once 'Customweb/Form/Control/SingleCheckbox.php';
//require_once 'Customweb/Form/ElementFactory.php';
//require_once 'Customweb/Payment/Authorization/Moto/IAdapter.php';


/**
 * @Method(paymentMethods={'DirectDebitsSepa'})
 */
class Customweb_PayUnity_Method_BankPaymentMethod extends Customweb_PayUnity_Method_DefaultMethod {
	/**
	 * Length of mandate ID.
	 * Payon API limits this to 256 characters, we choose 32.
	 *
	 * @var int
	 */
	const MANDATE_ID_LENGTH = 32;
	/**
	 * Disallowed characters
	 *
	 * @var string
	 */
	const MANDATE_DISALLOWED = "1234567890IlO";

	public function getVisibleFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $customerPaymentContext, $authorizationMethod){
		$elements = array();
		if ($authorizationMethod == Customweb_Payment_Authorization_Server_IAdapter::AUTHORIZATION_METHOD_NAME) {
			if ($aliasTransaction !== null && $aliasTransaction !== 'new') {
				$control = new Customweb_Form_Control_Html("payunity-iban-alias", $aliasTransaction->getAliasForDisplay());
				$elements[] = new Customweb_Form_Element(Customweb_I18n_Translation::__("Selected IBAN"), $control);
				return $elements;
			}
			$elements[] = Customweb_Form_ElementFactory::getAccountOwnerNameElement('bankAccount.holder',
					$orderContext->getBillingFirstName() . ' ' . $orderContext->getBillingLastName());
			$elements[] = Customweb_Form_ElementFactory::getIbanNumberElement('bankAccount.iban');
			$elements[] = Customweb_Form_ElementFactory::getBankCodeElement('bankAccount.bic');
		}
		return array_merge($elements, $this->getMandateElements($orderContext));
	}

	private function getMandateElements(Customweb_Payment_Authorization_IOrderContext $orderContext){
		$customer = $orderContext->getBillingAddress()->getFirstName() . ' ' . $orderContext->getBillingAddress()->getLastName();
		$mandateId = Customweb_Core_Util_Rand::getRandomString(self::MANDATE_ID_LENGTH, self::MANDATE_DISALLOWED);

		$mandateIdControl = new Customweb_Form_Control_HiddenInput('bankAccount.mandate.id', $mandateId);
		$mandateIdElement = new Customweb_Form_HiddenElement($mandateIdControl);

		$mandateTextControl = new Customweb_Form_Control_Html('mandate_text',
				$this->getMandateText($mandateId, $customer, $orderContext->getCustomerId()));
		$mandateTextElement = new Customweb_Form_Element(Customweb_I18n_Translation::__('Mandate text'), $mandateTextControl);
		$mandateTextElement->setRequired(false);

		$mandateAgreeControl = new Customweb_Form_Control_SingleCheckbox('payunity-agree-mandate', 'true',
				Customweb_I18n_Translation::__("I agree to the presented terms."));
		$mandateAgreeControl->addValidator(
				new Customweb_Form_Validator_Checked($mandateAgreeControl,
						Customweb_I18n_Translation::__("You must agree to the presented mandate text.")));
		$mandateAgreeElement = new Customweb_Form_Element(Customweb_I18n_Translation::__("Agreement"), $mandateAgreeControl);
		$mandateAgreeControl->setRequired(true);

		return array(
			$mandateIdElement,
			$mandateTextElement,
			$mandateAgreeElement
		);
	}

	private function getMandateText($mandateReference, $customer, $customerId){
		$creditorId = $this->getPaymentMethodConfigurationValue('debtor_id');
		$merchantName = $this->getPaymentMethodConfigurationValue('merchant_name');
		$date = date(Customweb_I18n_Translation::__('Y-m-d H:i')->toString());
		$customerIdText = '';
		if ($this->getPaymentMethodConfigurationValue('display_customer_id') == 'yes' && !empty($customerId)) {
			$customerIdText = ' ' . Customweb_I18n_Translation::__("(!CUSTOMER_ID)", array(
				'!CUSTOMER_ID' => $customerId
			))->toString();
		}

		return Customweb_I18n_Translation::__(
				"Creditor identifier: !CREDITOR_ID<br/> Mandate reference!CUSTOMER_ID: !MANDATE_REFERENCE<br/> Date: !DATE<br/> Customer: !CUSTOMER<br/> <br/> By signing this mandate form, you authorise (A) !MERCHANT_NAME to send instructions to your bank to debit your account and (B) your bank to debit your account in accordance with the instruction from !MERCHANT_NAME. <br/> As part of your rights, you are entitled to a refund from your bank under the terms and conditions of your agreement with your bank. A refund must be claimed within 8 weeks starting from the date on which your account was debited. Your rights are explained in a statement that you can obtain from your bank.",
				array(
					'!CREDITOR_ID' => $creditorId,
					'!CUSTOMER_ID' => $customerIdText,
					'!CUSTOMER' => $customer,
					'!MANDATE_REFERENCE' => $mandateReference,
					'!DATE' => $date,
					'!MERCHANT_NAME' => $merchantName
				));
	}

	public function getAuthorizationParameters(Customweb_PayUnity_Authorization_OppTransaction $transaction, array $formData){
		if ((!isset($formData['payunity-agree-mandate']) || $formData['payunity-agree-mandate'] != 'true') &&
				$transaction->getAuthorizationMethod() != Customweb_Payment_Authorization_Recurring_IAdapter::AUTHORIZATION_METHOD_NAME) {
			throw new Exception(Customweb_I18n_Translation::__("You must agree to the presented mandate text."));
		}
		$parameters = parent::getAuthorizationParameters($transaction, $formData);
		if ($transaction->getAuthorizationMethod() == Customweb_Payment_Authorization_Server_IAdapter::AUTHORIZATION_METHOD_NAME ||
				$transaction->getAuthorizationMethod() == Customweb_Payment_Authorization_Moto_IAdapter::AUTHORIZATION_METHOD_NAME) {
			foreach (array(
				'bankAccount.holder',
				'bankAccount.iban',
				'bankAccount.bic',
				'bankAccount.mandate.id',
				'bankAccount.country'
			) as $key) {
				$formDataKey = str_replace('.', '_', $key);
				if (isset($formData[$formDataKey])) {
					$parameters[$key] = $formData[$formDataKey];
				}
			}
		}
		if ($transaction->getAuthorizationMethod() != Customweb_Payment_Authorization_Recurring_IAdapter::AUTHORIZATION_METHOD_NAME &&
				($transaction->getAlias() == null || $transaction->getAlias() == 'new')) {
			$parameters['bankAccount.country'] = $transaction->getTransactionContext()->getOrderContext()->getBillingCountryIsoCode();
		}
		return $parameters;
	}
}