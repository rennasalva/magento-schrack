<?php
/**
  * You are allowed to use this API in your web application.
 *
 * Copyright (C) 2015 by customweb GmbH
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

//require_once 'Customweb/Util/Address.php';
//require_once 'Customweb/Util/String.php';
//require_once 'Customweb/PayUnity/Method/OpenInvoiceMethod.php';
//require_once 'Customweb/I18n/Translation.php';


/**
 * @Method(paymentMethods={'OpenInvoice'}, processors={'Klarna'})
 */
class Customweb_PayUnity_Method_OpenInvoice_KlarnaMethod extends Customweb_PayUnity_Method_OpenInvoiceMethod
{
	public function getPaymentMethodBrand()
	{
		return 'KLARNA_INVOICE';
	}

	public function preValidate(Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext)
	{
		parent::preValidate($orderContext, $paymentContext);

		$companyName = $orderContext->getBillingAddress()->getCompanyName();
		if (!empty($companyName)) {
			throw new Exception(Customweb_I18n_Translation::__(
					"The Klarna payment method cannot be used by companies."
			));
		}

		if (!Customweb_Util_Address::compareAddresses($orderContext->getBillingAddress(), $orderContext->getShippingAddress()))
		{
			throw new Exception(Customweb_I18n_Translation::__(
					"To use the Klarna payment method, the billing and shipping addresses must not differ."
			));
		}

		return true;
	}

	public function validate(
			Customweb_Payment_Authorization_IOrderContext $orderContext,
			Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext,
			array $formData
	) {
		parent::validate($orderContext, $paymentContext, $formData);

		$paymentCustomerContext = $paymentContext->getMap();

		if (!$orderContext->getBillingDateOfBirth() && (!isset($paymentCustomerContext['birthDate']) || !($paymentCustomerContext['birthDate'] instanceof DateTime)) && !$this->isDateOfBirthValid($formData)) {
			throw new Exception('The date of birth needs to be set.');
		}

		if (!$orderContext->getBillingPhoneNumber() && (!isset($paymentCustomerContext['phone']) || empty($paymentCustomerContext['phone'])) && (!isset($formData['phone_number']) || empty($formData['phone_number']))) {
			throw new Exception('The phone number needs to be set.');
		}
	}

	public function getVisibleFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $customerPaymentContext, $authorizationMethod)
	{
		return array_merge(
			parent::getVisibleFormFields($orderContext, $aliasTransaction, $failedTransaction, $customerPaymentContext, $authorizationMethod),
			$this->getPhoneNumberElements($orderContext, $customerPaymentContext),
			$this->getBirthdayElements($orderContext, $customerPaymentContext)
		);
	}

	public function getAuthorizationParameters(Customweb_PayUnity_Authorization_OppTransaction $transaction, array $formData) {
		$parameters = array();

		if($this->isDateOfBirthValid($formData)) {
			$dateOfBirth = DateTime::createFromFormat('Y-m-d', $formData['date_of_birth_year'] . '-' . $formData['date_of_birth_month'] . '-' . $formData['date_of_birth_day']);
			$transaction->getTransactionContext()->getPaymentCustomerContext()->updateMap(array(
				'birthDate' => $dateOfBirth
			));
			$parameters['customer.birthDate'] = $dateOfBirth->format('Y-m-d');
		}

		if(isset($formData['phone_number']) && !empty($formData['phone_number'])) {
			$transaction->getTransactionContext()->getPaymentCustomerContext()->updateMap(array(
				'phone' => $formData['phone_number']
			));
			$parameters['customer.phone'] = Customweb_Util_String::substrUtf8($formData['phone_number'], 0, 25);
		}

		return array_merge(
			parent::getAuthorizationParameters($transaction, $formData),
			$parameters
		);
	}

	private function isDateOfBirthValid(array $formData) {
		return isset($formData['date_of_birth_year']) && isset($formData['date_of_birth_month']) && isset($formData['date_of_birth_day'])
		&& !empty($formData['date_of_birth_year']) && !empty($formData['date_of_birth_month']) && !empty($formData['date_of_birth_day']);
	}
}