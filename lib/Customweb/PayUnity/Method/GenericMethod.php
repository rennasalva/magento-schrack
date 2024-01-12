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

//require_once 'Customweb/I18n/Translation.php';
//require_once 'Customweb/PayUnity/Method/DefaultMethod.php';


/**
 * @Method(paymentMethods={'Generic'})
 */
class Customweb_PayUnity_Method_GenericMethod extends Customweb_PayUnity_Method_DefaultMethod
{
	public function getPaymentMethodBrand()
	{
		return $this->getPaymentMethodConfigurationValue('payment_methods');
	}

	public function validate(
			Customweb_Payment_Authorization_IOrderContext $orderContext,
			Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext,
			array $formData
	) {
		parent::validate($orderContext, $paymentContext, $formData);
		$paymentMethodBrands = $this->getPaymentMethodBrand();
		if (empty($paymentMethodBrands)) {
			throw new Exception(Customweb_I18n_Translation::__('No payment methods have been configured for the generic method.'));
		}
	}

	/**
	 * @param Customweb_Payment_Authorization_ITransaction $transaction
	 * @param stdClass $response
	 * @return boolean
	 */
	protected function validatePaymentBrand(Customweb_Payment_Authorization_ITransaction $transaction, $response)
	{
		if (!isset($response->paymentBrand) || !in_array($response->paymentBrand, explode(' ', $this->getPaymentMethodBrand()))) {
			return false;
		}
		return true;
	}
}