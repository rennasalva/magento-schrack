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

//require_once 'Customweb/Payment/Authorization/ErrorMessage.php';
//require_once 'Customweb/Form/Validator/Checked.php';
//require_once 'Customweb/Form/Control/Html.php';
//require_once 'Customweb/PayUnity/Method/DefaultMethod.php';
//require_once 'Customweb/Util/Invoice.php';
//require_once 'Customweb/Form/Control/MultiControl.php';
//require_once 'Customweb/Form/Element.php';
//require_once 'Customweb/Util/Currency.php';
//require_once 'Customweb/I18n/Translation.php';
//require_once 'Customweb/Payment/Authorization/IInvoiceItem.php';
//require_once 'Customweb/Util/Address.php';
//require_once 'Customweb/Form/Control/SingleCheckbox.php';
//require_once 'Customweb/Filter/Input/String.php';



/**
 * @Method(paymentMethods={'KlarnaInvoice'})
 */
class Customweb_PayUnity_Method_KlarnaInvoiceMethod extends Customweb_PayUnity_Method_DefaultMethod {

	private $allowedCountries = array(
		'de',
		'at'
	);

	public function getPaymentMethodBrand(){
		return 'KLARNA_INVOICE';
	}

	public function isDirectCapturingSupported(){
		return false;
	}

	/**
	 * @param Customweb_I18n_LocalizableString $gatewayErrorMessage
	 * @param Customweb_I18n_LocalizableString $detailErrorMessage
	 * @param Object $response
	 * @return Customweb_Payment_Authorization_ErrorMessage
	 */
	public function getErrorMessage($gatewayErrorMessage, $detailErrorMessage, $response) {
		if (empty($detailErrorMessage)) {
			$detailErrorMessage = Customweb_I18n_Translation::__("Unfortunately, we cannot handle this purchase via Klarna. Please select an alternative payment method to complete your order.");
		}
		return  new Customweb_Payment_Authorization_ErrorMessage($gatewayErrorMessage, $detailErrorMessage);
		
	}

	public function preValidate(Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext){
		parent::preValidate($orderContext, $paymentContext);

		$klarnaMerchantId = $this->getPaymentMethodConfigurationValue('klarna_merchant_id');
		if (empty($klarnaMerchantId)) {
			throw new Exception(Customweb_I18n_Translation::__("To use the Klarna payment method, please specify the Klarna merchant id (EID)."));
		}

		$companyName = $orderContext->getBillingAddress()->getCompanyName();
		if (!empty($companyName)) {
			throw new Exception(Customweb_I18n_Translation::__("The Klarna payment method cannot be used by companies."));
		}

		if (!Customweb_Util_Address::compareAddresses($orderContext->getBillingAddress(), $orderContext->getShippingAddress())) {
			throw new Exception(
					Customweb_I18n_Translation::__("To use the Klarna payment method, the billing and shipping addresses must not differ."));
		}

		if (!in_array(strtolower($orderContext->getBillingAddress()->getCountryIsoCode()), $this->allowedCountries)) {
			throw new Exception(
					Customweb_I18n_Translation::__("The Klarna payment method cannot be used in the customer's country."));
		}

		return true;
	}

	public function validate(Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext, array $formData){
		parent::validate($orderContext, $paymentContext, $formData);

		$paymentCustomerContext = $paymentContext->getMap();

		if (!$orderContext->getBillingAddress()->getDateOfBirth() && !$this->isDateOfBirthValid($formData)) {
			throw new Exception('The date of birth needs to be set.');
		}

		if (!$orderContext->getBillingAddress()->getPhoneNumber() && (!isset($formData['phone_number']) || empty($formData['phone_number']))) {
			throw new Exception('The phone number needs to be set.');
		}

		if (!$orderContext->getBillingAddress()->getGender() && (!isset($formData['gender']) || empty($formData['gender']))) {
			throw new Exception('The gender needs to be set.');
		}

		if (!isset($formData['klarna_conditions_checkbox']) || $formData['klarna_conditions_checkbox'] != 'accepted') {
			throw new Exception('Please accept the terms and conditions.');
		}
	}

	public function getVisibleFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $customerPaymentContext, $authorizationMethod){
		return array_merge(
				parent::getVisibleFormFields($orderContext, $aliasTransaction, $failedTransaction, $customerPaymentContext, $authorizationMethod),
				$this->getPhoneNumberElements($orderContext, $customerPaymentContext, false),
				$this->getBirthdayElements($orderContext, $customerPaymentContext, false), $this->getGenderElements($orderContext, $customerPaymentContext, false),
				$this->getConditionsElement($orderContext));
	}

	public function getAuthorizationParameters(Customweb_PayUnity_Authorization_OppTransaction $transaction, array $formData){
		$parameters = array();

		if ($this->isDateOfBirthValid($formData)) {
			$dateOfBirth = DateTime::createFromFormat('Y-m-d',
					$formData['date_of_birth_year'] . '-' . $formData['date_of_birth_month'] . '-' . $formData['date_of_birth_day']);
			$transaction->getTransactionContext()->getPaymentCustomerContext()->updateMap(array(
				'birthDate' => $dateOfBirth
			));
			$parameters['customer.birthDate'] = $dateOfBirth->format('Y-m-d');
		}

		if (isset($formData['phone_number']) && !empty($formData['phone_number'])) {
			$transaction->getTransactionContext()->getPaymentCustomerContext()->updateMap(array(
				'phone' => $formData['phone_number']
			));
			$parameters['customer.phone'] = Customweb_Filter_Input_String::_($formData['phone_number'], 25)->filter();
		}

		if (isset($formData['gender']) && !empty($formData['gender'])) {
			$transaction->getTransactionContext()->getPaymentCustomerContext()->updateMap(array(
				'gender' => $formData['gender']
			));
			if ($formData['gender'] == 'male')
				$parameters['customer.sex'] = 'M';
			elseif ($formData['gender'] == 'female')
				$parameters['customer.sex'] = 'F';
		}

		$i = 1;
		foreach ($transaction->getTransactionContext()->getOrderContext()->getInvoiceItems() as $invoiceItem) {
			if (round($invoiceItem->getQuantity()) == 0) {
				continue;
			}
			$flag = 32;
			if ($invoiceItem->getType() == Customweb_Payment_Authorization_IInvoiceItem::TYPE_SHIPPING) {
				$flag = 40;
			}
			if ($invoiceItem->getType() == Customweb_Payment_Authorization_IInvoiceItem::TYPE_FEE) {
				$flag = 48;
			}
			$parameters['customParameters[KLARNA_CART_ITEM' . $i . '_FLAGS]'] = $flag;
			$i++;
		}

		return array_merge(parent::getAuthorizationParameters($transaction, $formData), $parameters);
	}

	public function getCaptureParameters(Customweb_PayUnity_Authorization_OppTransaction $transaction, array $items){
		if (Customweb_Util_Currency::roundAmount(Customweb_Util_Invoice::getTotalAmountIncludingTax($items), $transaction->getCurrencyCode())
				!= Customweb_Util_Currency::roundAmount($transaction->getAuthorizationAmount(), $transaction->getCurrencyCode())) {
			throw new Exception(Customweb_I18n_Translation::__('Partial captures of Klarna payments cannot be created in the shop. This can only be done in the PSP backend.'));
		}

		$parameters = array();

		$i = 0;
		foreach ($transaction->getTransactionContext()->getOrderContext()->getInvoiceItems() as $invoiceItem) {
			if (round($invoiceItem->getQuantity()) == 0) {
				continue;
			}
			foreach ($items as $item) {
				if (round($item->getQuantity()) == 0) {
					break;
				}
				if ($invoiceItem->getSku() == $item->getSku()) {
					$parameters['cart.items[' . $i . '].name'] = Customweb_Filter_Input_String::_(strip_tags($item->getName()), 255)->filter();
					$parameters['cart.items[' . $i . '].merchantItemId'] = Customweb_Filter_Input_String::_($item->getSku(), 255)->filter();
					$parameters['cart.items[' . $i . '].quantity'] = round($item->getQuantity());
					$parameters['cart.items[' . $i . '].type'] = $item->getType();
					if ($item->getType() == Customweb_Payment_Authorization_IInvoiceItem::TYPE_DISCOUNT) {
						$price = -1 * $item->getAmountIncludingTax() / round($item->getQuantity());
					}
					else {
						$price = $item->getAmountIncludingTax() / round($item->getQuantity());
					}
					$parameters['cart.items[' . $i . '].price'] = Customweb_Util_Currency::formatAmount($price,
							$transaction->getTransactionContext()->getOrderContext()->getCurrencyCode());
					$parameters['cart.items[' . $i . '].currency'] = $transaction->getTransactionContext()->getOrderContext()->getCurrencyCode();
					$parameters['cart.items[' . $i . '].tax'] = number_format($item->getTaxRate(), 1);
					$i++;
				}
			}
		}
		$parameters['paymentBrand'] = $this->getPaymentMethodBrand();

		return array_merge(
			parent::getCaptureParameters($transaction, $items),
			$parameters
		);

	}

	public function getRefundParameters(Customweb_PayUnity_Authorization_OppTransaction $transaction, array $items) {
		if (Customweb_Util_Currency::roundAmount(Customweb_Util_Invoice::getTotalAmountIncludingTax($items), $transaction->getCurrencyCode())
				!= Customweb_Util_Currency::roundAmount($transaction->getAuthorizationAmount(), $transaction->getCurrencyCode())) {
			throw new Exception(Customweb_I18n_Translation::__('Partial refunds of Klarna payments cannot be created in the shop. This can only be done in the PSP backend.'));
		}

		return parent::getRefundParameters($transaction, $items);
	}

	private function getConditionsElement(Customweb_Payment_Authorization_IOrderContext $orderContext){
		$conditionElementId = 'payunity-klarna-conditions-' . uniqid();
		$consentElementId = 'payunity-klarna-consent-' . uniqid();
		$klarnaMerchantId = $this->getPaymentMethodConfigurationValue('klarna_merchant_id');
		$invoiceFee = $this->getInvoiceFee($orderContext);

		$htmlContent = '<span id=\'' . $conditionElementId . '\'></span>';
		$htmlContent .= '<script src=\'https://cdn.klarna.com/public/kitt/core/v1.0/js/klarna.min.js\'></script>';
		$htmlContent .= '<script src=\'https://cdn.klarna.com/public/kitt/toc/v1.1/js/klarna.terms.min.js\'></script>';
		$htmlContent .= '<script type=\'text/javascript\'>';
		$htmlContent .= 'function klarnaTermsCallback() {';
		$htmlContent .= 'new Klarna.Terms.Invoice({ el: \'' . $conditionElementId . '\',  eid: \'' . $klarnaMerchantId . '\', locale: \'' .
				 $this->getLanguageCode($orderContext) . '\', charge: ' . $invoiceFee. ' });';
		$htmlContent .= 'new Klarna.Terms.Consent({ el: \'' . $consentElementId . '\',  eid: \'' . $klarnaMerchantId . '\', locale: \'' .
				 $this->getLanguageCode($orderContext) . '\' });';
		$htmlContent .= '};';
		$htmlContent .= 'function klarnaCallback() { var klarnaTermsScript = document.createElement(\'script\'); klarnaTermsScript.src = \'https://cdn.klarna.com/public/kitt/toc/v1.1/js/klarna.terms.min.js\'; document.getElementsByTagName(\'head\')[0].appendChild(klarnaTermsScript); klarnaTermsScript.onload=klarnaTermsCallback; };';
		$htmlContent .= 'var klarnaScript = document.createElement(\'script\'); klarnaScript.src = \'https://cdn.klarna.com/public/kitt/core/v1.0/js/klarna.min.js\'; document.getElementsByTagName(\'head\')[0].appendChild(klarnaScript); klarnaScript.onload=klarnaCallback;';
		$htmlContent .= '</script>';
		$htmlControl = new Customweb_Form_Control_Html('klarna_conditions_popup', $htmlContent);

		$checkboxControl = new Customweb_Form_Control_SingleCheckbox('klarna_conditions_checkbox', 'accepted',
				Customweb_I18n_Translation::__(
						'I agree that that Klarna can use my adress data for identity and scoring checks. I am aware that I can revoke my !consent at any time in the future. The general terms and conditions of the merchant apply.',
						array(
							'!consent' => '<span id="' . $consentElementId . '"></span>'
						)));
		$checkboxControl->addValidator(
				new Customweb_Form_Validator_Checked($checkboxControl,
						Customweb_I18n_Translation::__('Please accept the terms and conditions.')));

		$control = new Customweb_Form_Control_MultiControl('klarna_conditions', array(
			$htmlControl,
			$checkboxControl
		));

		$elements = array();
		$element = new Customweb_Form_Element(Customweb_I18n_Translation::__('Terms and Conditions'), $control,
				Customweb_I18n_Translation::__('Please read and accept the terms and conditions.'));
		$elements[] = $element;
		return $elements;
	}

	private function getInvoiceFee(Customweb_Payment_Authorization_IOrderContext $orderContext) {
		$feeAmount = 0;
		foreach ($orderContext->getInvoiceItems() as $item) {
			if ($item->getType() == Customweb_Payment_Authorization_IInvoiceItem::TYPE_FEE) {
				$feeAmount += $item->getAmountIncludingTax();
			}
		}
		return $feeAmount;
	}

	private function getLanguageCode(Customweb_Payment_Authorization_IOrderContext $orderContext){
		$countryCode = strtolower($orderContext->getBillingAddress()->getCountryIsoCode());
		$map = array(
			'de' => 'de_de',
			'at' => 'de_at',
			'nl' => 'nl_nl',
			'se' => 'sv_se',
			'no' => 'nb_no',
			'fi' => 'fi_fi',
			'dk' => 'da_dk'
		);
		if (isset($map[$countryCode])) {
			return $map[$countryCode];
		}
		else {
			return 'de_de';
		}
	}

	public function getCartItemParameters(Customweb_PayUnity_Authorization_OppTransaction $transaction){
		$parameters = array();

		$i = 0;
		$orderContext = $transaction->getTransactionContext()->getOrderContext();
		$itemTotalAmount = 0;
		foreach ($orderContext->getInvoiceItems() as $item) {

			if (round($item->getQuantity()) == 0) {
				continue;
			}
			$name = $item->getName();
			if (empty($name)) {
				$name = Customweb_I18n_Translation::__('No Name Provided');
			}

			$parameters['cart.items[' . $i . '].name'] = Customweb_Filter_Input_String::_(strip_tags($name), 255)->filter();
			$parameters['cart.items[' . $i . '].merchantItemId'] = Customweb_Filter_Input_String::_($item->getSku(), 255)->filter();
			$parameters['cart.items[' . $i . '].quantity'] = round($item->getQuantity());
			$parameters['cart.items[' . $i . '].type'] = $item->getType();
			if ($item->getType() == Customweb_Payment_Authorization_IInvoiceItem::TYPE_DISCOUNT) {
				$price = -1 * $item->getAmountIncludingTax() / round($item->getQuantity());
			}
			else {
				$price = $item->getAmountIncludingTax() / round($item->getQuantity());
			}
			$itemTotalAmount += Customweb_Util_Currency::roundAmount($price, $orderContext->getCurrencyCode()) * round($item->getQuantity());
			$parameters['cart.items[' . $i . '].price'] = Customweb_Util_Currency::formatAmount($price, $orderContext->getCurrencyCode());
			$parameters['cart.items[' . $i . '].currency'] = $orderContext->getCurrencyCode();
			$parameters['cart.items[' . $i . '].tax'] = number_format($item->getTaxRate(), 1);
			$i++;
		}

		$expectedAmount = Customweb_Util_Currency::roundAmount($orderContext->getOrderAmountInDecimals(), $orderContext->getCurrencyCode());
		$diff = Customweb_Util_Currency::compareAmount($itemTotalAmount, $expectedAmount, $orderContext->getCurrencyCode());
		if ($diff > 0) {
			$parameters['cart.items[' . $i . '].name'] = Customweb_Filter_Input_String::_(Customweb_I18n_Translation::__("Rounding Adjustment")->toString(), 255)->filter();
			$parameters['cart.items[' . $i . '].merchantItemId'] = Customweb_Filter_Input_String::_('rounding-adjustment', 255)->filter();
			$parameters['cart.items[' . $i . '].quantity'] = 1;
			$parameters['cart.items[' . $i . '].type'] = 'discount';
			$parameters['cart.items[' . $i . '].price'] = Customweb_Util_Currency::formatAmount($expectedAmount - $itemTotalAmount, $orderContext->getCurrencyCode());
			$parameters['cart.items[' . $i . '].currency'] = $orderContext->getCurrencyCode();
			$parameters['cart.items[' . $i . '].tax'] = number_format(0, 1);
		} elseif ($diff < 0) {
			$parameters['cart.items[' . $i . '].name'] = Customweb_Filter_Input_String::_(Customweb_I18n_Translation::__("Rounding Adjustment")->toString(), 255)->filter();
			$parameters['cart.items[' . $i . '].merchantItemId'] = Customweb_Filter_Input_String::_('rounding-adjustment', 255)->filter();
			$parameters['cart.items[' . $i . '].quantity'] = 1;
			$parameters['cart.items[' . $i . '].type'] = 'fee';
			$parameters['cart.items[' . $i . '].price'] = Customweb_Util_Currency::formatAmount($itemTotalAmount - $expectedAmount, $orderContext->getCurrencyCode());
			$parameters['cart.items[' . $i . '].currency'] = $orderContext->getCurrencyCode();
			$parameters['cart.items[' . $i . '].tax'] = number_format(0, 1);
		}

		return $parameters;
	}
}
