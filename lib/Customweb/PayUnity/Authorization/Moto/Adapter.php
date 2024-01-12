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
//require_once 'Customweb/Util/Url.php';
//require_once 'Customweb/Payment/Authorization/Server/IAdapter.php';
//require_once 'Customweb/I18n/Translation.php';
//require_once 'Customweb/PayUnity/Authorization/Moto/ParameterBuilder.php';
//require_once 'Customweb/PayUnity/Request.php';
//require_once 'Customweb/Payment/Authorization/ITransaction.php';
//require_once 'Customweb/Payment/Authorization/Hidden/IAdapter.php';
//require_once 'Customweb/PayUnity/Authorization/Server/Adapter.php';
//require_once 'Customweb/Payment/Authorization/Moto/IAdapter.php';


/**
 * @Bean
 */
class Customweb_PayUnity_Authorization_Moto_Adapter extends Customweb_PayUnity_Authorization_AbstractAdapter implements Customweb_Payment_Authorization_Moto_IAdapter
{
	public function getAdapterPriority()
	{
		return 1001;
	}

	public function getAuthorizationMethodName()
	{
		return self::AUTHORIZATION_METHOD_NAME;
	}

	public function preValidate(
			Customweb_Payment_Authorization_IOrderContext $orderContext,
			Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext
	) {
		if (!$this->getPaymentMethod($orderContext->getPaymentMethod())->isAuthorizationMethodSupported(Customweb_Payment_Authorization_Server_IAdapter::AUTHORIZATION_METHOD_NAME)
				&& !$this->getPaymentMethod($orderContext->getPaymentMethod())->isAuthorizationMethodSupported(Customweb_Payment_Authorization_Hidden_IAdapter::AUTHORIZATION_METHOD_NAME)) {
			throw new Exception(Customweb_I18n_Translation::__("The payment method '!paymentMethod' cannot be used for Moto payments.", array('!paymentMethod' => $orderContext->getPaymentMethod()->getPaymentMethodDisplayName())));
		}
		$this->getPaymentMethod($orderContext->getPaymentMethod())->preValidate($orderContext, $paymentContext);
	}

	public function validate(
			Customweb_Payment_Authorization_IOrderContext $orderContext,
			Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext,
			array $formData
	) {
		if (!$this->getPaymentMethod($orderContext->getPaymentMethod())->isAuthorizationMethodSupported(Customweb_Payment_Authorization_Server_IAdapter::AUTHORIZATION_METHOD_NAME)
				&& !$this->getPaymentMethod($orderContext->getPaymentMethod())->isAuthorizationMethodSupported(Customweb_Payment_Authorization_Hidden_IAdapter::AUTHORIZATION_METHOD_NAME)) {
			throw new Exception(Customweb_I18n_Translation::__("The payment method '!paymentMethod' cannot be used for Moto payments.", array('!paymentMethod' => $orderContext->getPaymentMethod()->getPaymentMethodDisplayName())));
		}
		$this->getPaymentMethod($orderContext->getPaymentMethod())->validate($orderContext, $paymentContext, $formData);
	}

	public function createTransaction(Customweb_Payment_Authorization_Moto_ITransactionContext $transactionContext, $failedTransaction)
	{
		

		return $this->createTransactionInternal($transactionContext, $failedTransaction);
	}

	public function getVisibleFormFields(
			Customweb_Payment_Authorization_IOrderContext $orderContext,
			$aliasTransaction,
			$failedTransaction,
			$paymentCustomerContext
	) {
		

		return $this->getAdapter()->getVisibleFormFields($orderContext, $aliasTransaction, $failedTransaction, $paymentCustomerContext);
	}

	public function getFormActionUrl(Customweb_Payment_Authorization_ITransaction $transaction) {
		

		return $this->getEndpointAdapter()->getUrl('process', 'index', array(
			'cw_transaction_id' => $transaction->getExternalTransactionId()
		));
	}

	public function getParameters(Customweb_Payment_Authorization_ITransaction $transaction) {
		

		return array();
	}

	public function processAuthorization(
			Customweb_Payment_Authorization_ITransaction $transaction,
			array $parameters
	) {
		

		if ($transaction->getTransactionContext()->getAlias() instanceof Customweb_Payment_Authorization_ITransaction) {
			return $this->processAliasAuthorization($transaction, $parameters);
		} else {
			$response = null;
			try {
				$request = new Customweb_PayUnity_Request($this->getPaymentUrl(), $this->getConfiguration());
				$request->setMethod(Customweb_PayUnity_Request::METHOD_POST);
				$request->setData($this->getParameterBuilder($transaction)->buildAuthorizationParameters($parameters));
				$response = $request->send();
			} catch (Exception $e) {
				$transaction->setAuthorizationFailed(Customweb_I18n_Translation::__($e->getMessage()));
			}

			return $this->finalizeAuthorization($transaction, $response);
		}
	}

	protected function redirect(Customweb_Payment_Authorization_ITransaction $transaction)
	{
		if ($transaction->isAuthorized()) {
			return 'redirect:' . Customweb_Util_Url::appendParameters(
				$transaction->getTransactionContext()->getBackendSuccessUrl(),
				$transaction->getTransactionContext()->getCustomParameters()
			);
		} elseif ($transaction->isAuthorizationFailed()) {
			return 'redirect:' . Customweb_Util_Url::appendParameters(
				$transaction->getTransactionContext()->getBackendFailedUrl(),
				$transaction->getTransactionContext()->getCustomParameters()
			);
		} else {
			throw new Exception('The transaction is in an invalid state.');
		}
	}

	/**
	 * @return Customweb_PayUnity_Authorization_Server_Adapter
	 */
	protected function getAdapter()
	{
		return new Customweb_PayUnity_Authorization_Server_Adapter($this->getContainer());
	}

	/**
	 * @param Customweb_Payment_Authorization_ITransaction $transaction
	 * @return Customweb_PayUnity_Authorization_Recurring_ParameterBuilder
	 */
	protected function getParameterBuilder(Customweb_Payment_Authorization_ITransaction $transaction)
	{
		return new Customweb_PayUnity_Authorization_Moto_ParameterBuilder($this->getContainer(), $transaction);
	}

	
}