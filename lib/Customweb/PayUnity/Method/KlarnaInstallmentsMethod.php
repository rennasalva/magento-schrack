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
//require_once 'Customweb/PayUnity/Method/DefaultMethod.php';
//require_once 'Customweb/I18n/Translation.php';


/**
 * @Method(paymentMethods={'KlarnaInstallments'})
 */
class Customweb_PayUnity_Method_KlarnaInstallmentsMethod extends Customweb_PayUnity_Method_DefaultMethod
{
	public function isDirectCapturingSupported()
	{
		return false;
	}

	public function preValidate(Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext)
	{
		parent::preValidate($orderContext, $paymentContext);

		$companyName = $orderContext->getBillingCompanyName();
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
}