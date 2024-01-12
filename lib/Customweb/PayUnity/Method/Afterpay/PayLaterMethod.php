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

//require_once 'Customweb/Util/Currency.php';
//require_once 'Customweb/Payment/Authorization/ErrorMessage.php';
//require_once 'Customweb/I18n/Translation.php';
//require_once 'Customweb/Payment/Authorization/IInvoiceItem.php';
//require_once 'Customweb/Filter/Input/String.php';
//require_once 'Customweb/PayUnity/Method/DefaultMethod.php';
//require_once 'Customweb/Util/Invoice.php';


/**
 * @Method(paymentMethods={'AfterpayPayLater'})
 */
class Customweb_PayUnity_Method_Afterpay_PayLaterMethod extends Customweb_PayUnity_Method_DefaultMethod {

	/**
	 *
	 * @return boolean
	 */
	public function isRiskBasedCapturingSupported(){
		return false;
	}

	public function getVisibleFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $customerPaymentContext, $authorizationMethod){
		return array_merge(
				parent::getVisibleFormFields($orderContext, $aliasTransaction, $failedTransaction, $customerPaymentContext, $authorizationMethod),
				$this->getPhoneNumberElements($orderContext, $customerPaymentContext),
				$this->getBirthdayElements($orderContext, $customerPaymentContext), $this->getGenderElements($orderContext, $customerPaymentContext));
	}
	
	/**
	 *
	 * @param Customweb_PayUnity_Authorization_OppTransaction $transaction
	 * @param array $items
	 * @return array
	 */
	public function getRefundParameters(Customweb_PayUnity_Authorization_OppTransaction $transaction, array $items){
		$parameters = array();
		
		$parameters['customParameters[AFTERPAY_Partial]'] = "true";
		$itemParameters = $this->getCartItemParametersInner($transaction, $items);
		foreach ($itemParameters as $key => $value){
			if(preg_match('/cart\.items\[\d+\]\.price/', $key) == 1){
				$itemParameters[$key] = Customweb_Util_Currency::formatAmount($value*-1,
						$transaction->getCurrencyCode());
			}
		}
		$parameters = array_merge($parameters, $itemParameters);
		
		return $parameters;
	}

	public function getAuthorizationParameters(Customweb_PayUnity_Authorization_OppTransaction $transaction, array $formData){
		$parameters = array();
		$orderContext = $transaction->getTransactionContext()->getOrderContext();
		
		$shippingPhoneNumber = $orderContext->getShippingAddress()->getPhoneNumber();
		if (!$orderContext->getBillingAddress()->getPhoneNumber() && (!isset($formData['phone_number']) || empty($formData['phone_number']))) {
			throw new Exception('The phone number needs to be set.');
		}
		if (isset($formData['phone_number']) && !empty($formData['phone_number'])) {
			$transaction->getTransactionContext()->getPaymentCustomerContext()->updateMap(array(
				'phone' => $formData['phone_number'] 
			));
			$parameters['customer.phone'] = Customweb_Filter_Input_String::_($formData['phone_number'], 25)->filter();
			if (empty($shippingPhoneNumber)) {
				$shippingPhoneNumber = $parameters['customer.phone'];
			}
		}
		elseif (empty($shippingPhoneNumber)) {
			$shippingPhoneNumber = $orderContext->getBillingAddress()->getPhoneNumber();
		}
		$parameters['customParameters[AFTERPAY_ShipReferencePerson_Phonenumber1]'] = $shippingPhoneNumber;
		
		$shippingBirtDay = '';
		if ($orderContext->getShippingAddress()->getDateOfBirth() instanceof DateTime) {
			$shippingBirtDay = $orderContext->getShippingAddress()->getDateOfBirth()->format('Y-m-d');
		}
		if (!($orderContext->getBillingAddress()->getDateOfBirth() instanceof DateTime) && !$this->isDateOfBirthValid($formData)) {
			throw new Exception('The date of birth needs to be set.');
		}
		if ($this->isDateOfBirthValid($formData)) {
			$dateOfBirth = DateTime::createFromFormat('Y-m-d',
					$formData['date_of_birth_year'] . '-' . $formData['date_of_birth_month'] . '-' . $formData['date_of_birth_day']);
			$transaction->getTransactionContext()->getPaymentCustomerContext()->updateMap(array(
				'birthDate' => $dateOfBirth 
			));
			$parameters['customer.birthDate'] = $dateOfBirth->format('Y-m-d');
			if (empty($shippingBirtDay)) {
				$shippingBirtDay = $parameters['customer.birthDate'];
			}
		}
		elseif (empty($shippingBirtDay)) {
			$shippingBirtDay = $orderContext->getBillingAddress()->getDateOfBirth()->format('Y-m-d');
		}
		$parameters['customParameters[AFTERPAY_ShipReferencePerson_Dateofbirth]'] = $shippingBirtDay . 'T00:00:00';
		
		$shippingGender = '';
		if ($orderContext->getShippingAddress()->getGender() == 'male') {
			$shippingGender = 'M';
		}
		elseif ($orderContext->getShippingAddress()->getGender() == 'female') {
			$shippingGender = 'F';
		}
		if (!$orderContext->getBillingAddress()->getGender() && (!isset($formData['gender']) || empty($formData['gender']))) {
			throw new Exception('The gender needs to be set.');
		}
		if (isset($formData['gender']) && !empty($formData['gender'])) {
			$transaction->getTransactionContext()->getPaymentCustomerContext()->updateMap(array(
				'gender' => $formData['gender'] 
			));
			if ($formData['gender'] == 'male') {
				$parameters['customer.sex'] = 'M';
			}
			elseif ($formData['gender'] == 'female') {
				$parameters['customer.sex'] = 'F';
			}
			if (empty($shippingGender)) {
				$shippingGender = $parameters['customer.sex'];
			}
		}
		elseif (empty($shippingGender)) {
			if ($orderContext->getBillingAddress()->getGender() == 'male') {
				$shippingGender = 'M';
			}
			elseif ($orderContext->getBillingAddress()->getGender() == 'female') {
				$shippingGender = 'F';
			}
		}
		$parameters['customParameters[AFTERPAY_ShipReferencePerson_Gender]'] = $shippingGender;
		
		$parameters['customParameters[AFTERPAY_BillReferencePerson_IsoLanguage]'] = $orderContext->getLanguage()->getIso2LetterCode();
		$parameters['customParameters[AFTERPAY_ShipReferencePerson_IsoLanguage]'] = $orderContext->getLanguage()->getIso2LetterCode();
		
		if ($orderContext->getCustomerRegistrationDate() instanceof DateTime) {
			$parameters['customParameters[AFTERPAY_Shopper_ProfileCreated]'] = $orderContext->getCustomerRegistrationDate()->format('Y-m-d\TH:i:s');
		}
		
		$shippingEmail = $orderContext->getShippingAddress()->getEMailAddress();
		if (!empty($shippingEmail)) {
			$parameters['customParameters[AFTERPAY_ShipReferencePerson_Emailaddress]'] = $shippingEmail;
		}
		return array_merge(parent::getAuthorizationParameters($transaction, $formData), $parameters);
	}

	public function getCartItemParameters(Customweb_PayUnity_Authorization_OppTransaction $transaction){
		return $this->getCartItemParametersInner($transaction, $transaction->getTransactionContext()->getOrderContext()->getInvoiceItems());
	}
	
	private function getCartItemParametersInner(Customweb_PayUnity_Authorization_OppTransaction $transaction, array $items){
		$parameters = array();
		$i = 0;
		$orderContext = $transaction->getTransactionContext()->getOrderContext();
		$itemTotalAmount = 0;
		foreach ($items as $item) {
			
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
			$parameters['cart.items[' . $i . '].type'] = '3';
			
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
			$parameters['cart.items[' . $i . '].description'] = Customweb_Filter_Input_String::_($name, 255)->filter();
			$i++;
		}
		$expectedAmount = Customweb_Util_Currency::roundAmount(Customweb_Util_Invoice::getTotalAmountIncludingTax($items), $orderContext->getCurrencyCode());
		$diff = Customweb_Util_Currency::compareAmount($itemTotalAmount, $expectedAmount, $orderContext->getCurrencyCode());
		if ($diff > 0) {
			$parameters['cart.items[' . $i . '].name'] = Customweb_Filter_Input_String::_(
					Customweb_I18n_Translation::__("Rounding Adjustment")->toString(), 255)->filter();
			$parameters['cart.items[' . $i . '].merchantItemId'] = Customweb_Filter_Input_String::_('rounding-adjustment', 255)->filter();
			$parameters['cart.items[' . $i . '].quantity'] = 1;
			$parameters['cart.items[' . $i . '].type'] = 3;
			$parameters['cart.items[' . $i . '].price'] = Customweb_Util_Currency::formatAmount($expectedAmount - $itemTotalAmount,
					$orderContext->getCurrencyCode());
			$parameters['cart.items[' . $i . '].currency'] = $orderContext->getCurrencyCode();
			$parameters['cart.items[' . $i . '].tax'] = number_format(0, 1);
		}
		elseif ($diff < 0) {
			$parameters['cart.items[' . $i . '].name'] = Customweb_Filter_Input_String::_(
					Customweb_I18n_Translation::__("Rounding Adjustment")->toString(), 255)->filter();
			$parameters['cart.items[' . $i . '].merchantItemId'] = Customweb_Filter_Input_String::_('rounding-adjustment', 255)->filter();
			$parameters['cart.items[' . $i . '].quantity'] = 1;
			$parameters['cart.items[' . $i . '].type'] = 3;
			$parameters['cart.items[' . $i . '].price'] = Customweb_Util_Currency::formatAmount($itemTotalAmount - $expectedAmount,
					$orderContext->getCurrencyCode());
			$parameters['cart.items[' . $i . '].currency'] = $orderContext->getCurrencyCode();
			$parameters['cart.items[' . $i . '].tax'] = number_format(0, 1);
		}
		return $parameters;
	}

	/**
	 * @param Customweb_I18n_LocalizableString $gatewayErrorMessage
	 * @param Customweb_I18n_LocalizableString $detailErrorMessage
	 * @param Object $response
	 * @return Customweb_Payment_Authorization_ErrorMessage
	 */
	public function getErrorMessage($gatewayErrorMessage, $detailErrorMessage, $response){
		$detailErrorMessage = Customweb_I18n_Translation::__(
				"We are sorry to have to inform you that your request for AfterPay Open Invoice on your order is not accepted by AfterPay. This is because of various (temporary) reasons.
			We advise you to choose a different payment method to complete your order.");
		if (isset($response->result->code) && (string) $response->result->code == '800.100.156') {
			$detailErrorMessage = Customweb_I18n_Translation::__(
					"We are sorry to have to inform you that your request for AfterPay Open Invoice on your order is not accepted by AfterPay. This is because one or more fields appeared to be incorrect.
We advise you to check all entered values and try again or choose a different payment method to complete your order.");
			if (isset($response->resultDetails->PP_ERROR_MESSAGE)) {
				$detailError = (string) $response->resultDetails->PP_ERROR_MESSAGE;
				$detailsErrors = explode("|", $detailError);
				$translatedErrors = array();
				foreach ($detailsErrors as $error) {
					$translatedErrors[] = $this->extractInvalidFieldErrorMessage($error);
				}
				$translatedErrors = array_unique($translatedErrors);
				if (!empty($translatedErrors)) {
					$detailErrorMessage = implode(" ", $translatedErrors);
				}
			}
		}
		elseif (isset($response->result->code) && (string) $response->result->code == '800.100.152') {
			if (isset($response->resultDetails->PP_ERROR_CODE)) {
				$afterpayErrorCode = (string) $response->resultDetails->PP_ERROR_CODE;
				$detailErrorMessage = $this->getDeclinedErrorMessage($afterpayErrorCode);
			}
		}
		return new Customweb_Payment_Authorization_ErrorMessage($detailErrorMessage, $gatewayErrorMessage);
	}

	private function extractInvalidFieldErrorMessage($errorMessage){
		$identifier = substr($errorMessage, 0, strpos($errorMessage, ":"));
		
		switch ($identifier) {
			case 'field.unknown.invalid':
				$message = Customweb_I18n_Translation::__('There is some information missing.');
				break;
			case 'field.billto.person.initials.missing':
			case 'field.shipto.person.initials.missing':
			case 'field.person.initials.missing':
				$message = Customweb_I18n_Translation::__('The initials are missing.');
				break;
			case 'field.billto.person.initials.invalid':
			case 'field.shipto.person.initials.invalid':
			case 'field.person.initials.invalid':
				$message = Customweb_I18n_Translation::__('The initials are invalid.');
				break;
			case 'field.billto.person.lastname.missing':
			case 'field.shipto.person.lastname.missing':
			case 'field.person.lastname.missing':
				$message = Customweb_I18n_Translation::__('The surname is missing.');
				break;
			
			case 'field.billto.person.lastname.invalid':
			case 'field.shipto.person.lastname.invalid':
			case 'field.person.lastname.invalid':
				$message = Customweb_I18n_Translation::__('The surname is invalid.');
				break;
			case 'field.billto.city.missing':
			case 'field.shipto.city.missing':
				$message = Customweb_I18n_Translation::__('The name of the city is missing.');
				break;
			case 'field.billto.city.invalid':
			case 'field.shipto.city.invalid':
				$message = Customweb_I18n_Translation::__('The name of the city is invalid.');
				break;
			case 'field.billto.housenumber.missing':
			case 'field.shipto.housenumber.missing':
				$message = Customweb_I18n_Translation::__('The house number is missing.');
				break;
			case 'field.billto.housenumber.invalid':
			case 'field.shipto.housenumber.invalid':
				$message = Customweb_I18n_Translation::__('The house number is invalid.');
				break;
			case 'field.billto.postalcode.missing':
			case 'field.shipto.postalcode.missing':
				$message = Customweb_I18n_Translation::__('The postal code is missing.');
				break;
			case 'field.billto.postalcode.invalid':
			case 'field.shipto.postalcode.invalid':
				$message = Customweb_I18n_Translation::__('The postal code is invalid.');
				break;
			case 'field.billto.person.gender.missing':
			case 'field.shipto.person.gender.missing':
				$message = Customweb_I18n_Translation::__('Gender is missing.');
				break;
			case 'field.billto.person.gender.invalid':
			case 'field.shipto.person.gender.invalid':
				$message = Customweb_I18n_Translation::__('Gender is invalid.');
				break;
			case 'field.billto.housenumberaddition.missing':
			case 'field.shipto.housenumberaddition.missing':
				$message = Customweb_I18n_Translation::__('The house number addition is missing.');
				break;
			case 'field.billto.housenumberaddition.invalid':
			case 'field.shipto.housenumberaddition.invalid':
				$message = Customweb_I18n_Translation::__('The house number addition is invalid.');
				break;
			case 'field.shipto.phonenumber1.missing':
			case 'field.shipto.phonenumber2.missing':
				$message = Customweb_I18n_Translation::__('The telephone number is missing.');
				break;
			case 'field.shipto.phonenumber1.invalid':
			case 'field.shipto.phonenumber2.invalid':
				$message = Customweb_I18n_Translation::__('The telephone number is invalid.');
				break;
			case 'field.billto.phonenumber1.missing':
			case 'field.billto.phonenumber2.missing':
				$message = Customweb_I18n_Translation::__('The telephone number is missing.');
				break;
			case 'field.billto.phonenumber1.invalid':
			case 'field.billto.phonenumber2.invalid':
				$message = Customweb_I18n_Translation::__('The telephone number is invalid.');
				break;
			case 'field.billto.person.emailaddress.missing':
			case 'field.shipto.person.emailaddress.missing':
				$message = Customweb_I18n_Translation::__('The email address is missing.');
				break;
			case 'field.billto.person.emailaddress.invalid':
			case 'field.shipto.person.emailaddress.invalid':
				$message = Customweb_I18n_Translation::__('The email address is invalid.');
				break;
			case 'field.billto.person.dateofbirth.missing':
			case 'field.shipto.person.dateofbirth.missing':
				$message = Customweb_I18n_Translation::__('The date of birth is missing.');
				break;
			case 'field.shipto.person.dateofbirth.invalid':
			case 'field.billto.person.dateofbirth.invalid':
				$message = Customweb_I18n_Translation::__('The date of birth is invalid.');
				break;
			case 'field.billto.isocountrycode.missing':
			case 'field.shipto.isocountrycode.missing':
				$message = Customweb_I18n_Translation::__('The country code of the phone number is missing.');
				break;
			case 'field.billto.isocountrycode.invalid':
			case 'field.shipto.isocountrycode.invalid':
				$message = Customweb_I18n_Translation::__('The country code of the phone number is invalid.');
				break;
			case 'field.billto.person.prefix.missing':
			case 'field.shipto.person.prefix.missing':
			case 'field.person.prefix.missing':
				$message = Customweb_I18n_Translation::__('The name prefix is missing.');
				break;
			case 'field.billto.person.prefix.invalid':
			case 'field.shipto.person.prefix.invalid':
			case 'field.person.prefix.invalid':
				$message = Customweb_I18n_Translation::__('The name prefix is invalid.');
				break;
			case 'field.billto.isolanguagecode.missing':
			case 'field.shipto.isolanguagecode.missing':
				$message = Customweb_I18n_Translation::__('The language preference is missing.');
				break;
			case 'field.billto.isolanguagecode.invalid':
			case 'field.shipto.isolanguagecode.invalid':
				$message = Customweb_I18n_Translation::__('The language preference is invalid.');
				break;
			case 'field.ordernumber.missing':
				$message = Customweb_I18n_Translation::__('The order number is missing.');
				break;
			case 'field.ordernumber.invalid':
				$message = Customweb_I18n_Translation::__('The order number is invalid.');
				break;
			case 'field.ordernumber.exists':
				$message = Customweb_I18n_Translation::__('The order number is already excisting.');
				break;
			case 'field.bankaccountnumber.missing':
				$message = Customweb_I18n_Translation::__('The IBAN is missing.');
				break;
			case 'field.bankaccountnumber.invalid':
				$message = Customweb_I18n_Translation::__('The IBAN is invalid.');
				break;
			case 'field.currency.missing':
				$message = Customweb_I18n_Translation::__('The currency preference is missing.');
				break;
			case 'field.currency.invalid':
				$message = Customweb_I18n_Translation::__('The currency preference is invalid.');
				break;
			case 'field.orderline.missing':
				$message = Customweb_I18n_Translation::__('The orderline is missing.');
				break;
			case 'field.orderline.invalid':
				$message = Customweb_I18n_Translation::__('The orderline is invalid.');
				break;
			case 'field.totalorderamount.missing':
				$message = Customweb_I18n_Translation::__('The total order amount is missing.');
				break;
			case 'field.totalorderamount.invalid':
				$message = Customweb_I18n_Translation::__('The total order amount is invalid.');
				break;
			case 'field.parenttransactionreference.missing':
				$message = Customweb_I18n_Translation::__('The parent transaction reference is missing.');
				break;
			case 'field.parenttransactionreference.invalid':
				$message = Customweb_I18n_Translation::__('The parent transaction reference is invalid.');
				break;
			case 'field.parenttransactionreference.exists':
				$message = Customweb_I18n_Translation::__('The parent transaction reference already exists.');
				break;
			case 'field.vat.missing':
				$message = Customweb_I18n_Translation::__('The VAT category is missing.');
				break;
			case 'field.vat.invalid':
				$message = Customweb_I18n_Translation::__('The VAT category is invalid.');
				break;
			case 'field.quantity.missing':
				$message = Customweb_I18n_Translation::__('The product quantity is missing.');
				break;
			case 'field.quantity.invalid':
				$message = Customweb_I18n_Translation::__('The product quantity is invalid.');
				break;
			case 'field.unitprice.missing':
				$message = Customweb_I18n_Translation::__('The unit price is missing.');
				break;
			case 'field.unitprice.invalid':
				$message = Customweb_I18n_Translation::__('The unit price is invalid.');
				break;
			case 'field.netunitprice.missing':
				$message = Customweb_I18n_Translation::__('The unit price is missing.');
				break;
			case 'field.netunitprice.invalid':
				$message = Customweb_I18n_Translation::__('The unit price is invalid.');
				break;
			case 'field.company.cocnumber.missing':
				$message = Customweb_I18n_Translation::__('The chamber of commerce number is missing.');
				break;
			case 'field.company.cocnumber.missing':
				$message = Customweb_I18n_Translation::__('The chamber of commerce number is incorrect.');
				break;
			case 'field.company.companyname.missing':
				$message = Customweb_I18n_Translation::__('The company name is missing.');
				break;
			case 'field.company.companyname.invalid':
				$message = Customweb_I18n_Translation::__('The comapny name is incorrect.');
				break;
			case 'field.company.department.missing':
				$message = Customweb_I18n_Translation::__('The department name is missing.');
				break;
			case 'field.company.department.invalid':
				$message = Customweb_I18n_Translation::__('The department name is invalid.');
				break;
			case 'field.company.establishmentnumber.missing':
				$message = Customweb_I18n_Translation::__('The establishment number is missing.');
				break;
			case 'field.company.establishmentnumber.invalid':
				$message = Customweb_I18n_Translation::__('The establishment number is invalid.');
				break;
			case 'field.company.vatnumber.missing':
				$message = Customweb_I18n_Translation::__('The VAT number is missing.');
				break;
			default:
				$message = Customweb_I18n_Translation::__('There is some information missing.');
				break;
		}
		return $message;
	}

	private function getDeclinedErrorMessage($code){
		switch ($code) {
			case "1":
				$message = Customweb_I18n_Translation::__('Your request to use Afterpay has been declined.');
				break;
			case "29":
				$message = Customweb_I18n_Translation::__('Your order amount is too high.');
				break;
			case "30":
				$message = Customweb_I18n_Translation::__("You've reached the maximum amount of Afterpay paments.");
				break;
			case "36":
				$message = Customweb_I18n_Translation::__('Your email address is invalid or incomplete.');
				break;
			case "40":
				$message = Customweb_I18n_Translation::__('Your age is below 18.');
				break;
			case "42":
				$message = Customweb_I18n_Translation::__('Your address is invalid or incomplete.');
				break;
			case "47":
				$message = Customweb_I18n_Translation::__('Your order amount is too low.');
				break;
			case "52":
				$message = Customweb_I18n_Translation::__('Declined by test data.');
				break;
			case "53":
				$message = Customweb_I18n_Translation::__('Your request to use Afterpay has been declined.');
				break;
			case "71":
				$message = Customweb_I18n_Translation::__('Your CoC number is incorrect.');
				break;
			default:
				$message = Customweb_I18n_Translation::__('Sorry, your payment request has been declined by AfterPay.');
		}
		return $message;
	}
}