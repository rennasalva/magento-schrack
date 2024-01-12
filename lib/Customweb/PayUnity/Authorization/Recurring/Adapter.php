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

//require_once 'Customweb/PayUnity/Authorization/AbstractAdapter.php';
//require_once 'Customweb/I18n/Translation.php';
//require_once 'Customweb/Payment/Exception/RecurringPaymentErrorException.php';
//require_once 'Customweb/PayUnity/Authorization/Recurring/ParameterBuilder.php';
//require_once 'Customweb/PayUnity/Request.php';
//require_once 'Customweb/Payment/Authorization/Recurring/IAdapter.php';


/**
 * @Bean
 */
class Customweb_PayUnity_Authorization_Recurring_Adapter extends Customweb_PayUnity_Authorization_AbstractAdapter implements Customweb_Payment_Authorization_Recurring_IAdapter
{
	public function getAdapterPriority()
	{
		return 1001;
	}

	public function getAuthorizationMethodName()
	{
		return self::AUTHORIZATION_METHOD_NAME;
	}

	public function isPaymentMethodSupportingRecurring(Customweb_Payment_Authorization_IPaymentMethod $paymentMethod)
	{
		return $this->getPaymentMethod($paymentMethod)->isRecurringPaymentSupported();
	}

	public function createTransaction(Customweb_Payment_Authorization_Recurring_ITransactionContext $transactionContext)
	{
		

		return $this->createTransactionInternal($transactionContext);
	}

	public function process(Customweb_Payment_Authorization_ITransaction $transaction)
	{
		

		$response = null;
		try {
			$request = new Customweb_PayUnity_Request($this->getRegistrationUrl($transaction->getTransactionContext()->getInitialTransaction()->getRegistrationId()),$this->getConfiguration());
			$request->setMethod(Customweb_PayUnity_Request::METHOD_POST);
			$request->setData($this->getParameterBuilder($transaction)->buildAuthorizationParameters());
			$response = $request->send('Recurring');
		} catch (Exception $e) {
			$transaction->setAuthorizationFailed(Customweb_I18n_Translation::__($e->getMessage()));
		}

		$this->finalizeAuthorization($transaction, $response);
		
		if ($transaction->isAuthorizationFailed()) {
			throw new Customweb_Payment_Exception_RecurringPaymentErrorException(end($transaction->getErrorMessages()));
		}
	}

	/**
	 * @param Customweb_Payment_Authorization_ITransaction $transaction
	 * @return Customweb_PayUnity_Authorization_Recurring_ParameterBuilder
	 */
	protected function getParameterBuilder(Customweb_Payment_Authorization_ITransaction $transaction)
	{
		return new Customweb_PayUnity_Authorization_Recurring_ParameterBuilder($this->getContainer(), $transaction);
	}

	
}