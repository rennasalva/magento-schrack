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

//require_once 'Customweb/Core/DateTime.php';
//require_once 'Customweb/Payment/Authorization/ITransactionHistoryItem.php';
//require_once 'Customweb/PayUnity/Authorization/OppTransaction.php';
//require_once 'Customweb/I18n/Translation.php';
//require_once 'Customweb/Payment/Authorization/IAdapter.php';
//require_once 'Customweb/PayUnity/AbstractAdapter.php';
//require_once 'Customweb/PayUnity/Request.php';
//require_once 'Customweb/PayUnity/IConstants.php';
//require_once 'Customweb/Core/Logger/Factory.php';

abstract class Customweb_PayUnity_Authorization_AbstractAdapter extends Customweb_PayUnity_AbstractAdapter implements
		Customweb_Payment_Authorization_IAdapter {

	/**
	 *
	 * @param Customweb_Payment_Authorization_IPaymentMethod $method
	 * @return Customweb_PayUnity_Method_DefaultMethod
	 */
	public function getPaymentMethod(Customweb_Payment_Authorization_IPaymentMethod $method){
		return $this->getMethodFactory()->getPaymentMethod($method, $this->getAuthorizationMethodName());
	}

	public function isAuthorizationMethodSupported(Customweb_Payment_Authorization_IOrderContext $orderContext){
		return $this->getPaymentMethod($orderContext->getPaymentMethod())->isAuthorizationMethodSupported($this->getAuthorizationMethodName());
	}

	public function isDeferredCapturingSupported(Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext){
		return $orderContext->getPaymentMethod()->existsPaymentMethodConfigurationValue('capturing');
	}

	public function preValidate(Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext){
		$this->getPaymentMethod($orderContext->getPaymentMethod())->preValidate($orderContext, $paymentContext);
	}

	public function validate(Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext, array $formData){
		$this->getPaymentMethod($orderContext->getPaymentMethod())->validate($orderContext, $paymentContext, $formData);
	}

	public function getVisibleFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $paymentCustomerContext){
		return $this->getPaymentMethod($orderContext->getPaymentMethod())->getVisibleFormFields($orderContext, $aliasTransaction, $failedTransaction,
				$paymentCustomerContext, $this->getAuthorizationMethodName());
	}

	public function processAliasAuthorization(Customweb_Payment_Authorization_ITransaction $transaction, array $parameters){
		$response = null;
		try {
			$request = new Customweb_PayUnity_Request(
					$this->getRegistrationUrl($transaction->getTransactionContext()->getAlias()->getRegistrationId()), $this->getConfiguration());
			$request->setMethod(Customweb_PayUnity_Request::METHOD_POST);
			$request->setData($this->getParameterBuilder($transaction)->buildAliasAuthorizationParameters());
			$response = $request->send();
		}
		catch (Exception $e) {
			$transaction->setAuthorizationFailed(Customweb_I18n_Translation::__($e->getMessage()));
		}
		return $this->finalizeAuthorization($transaction, $response);
	}

	public function processAsynchronousAuthorization(Customweb_Payment_Authorization_ITransaction $transaction, array $parameters){
		try {
			$request = new Customweb_PayUnity_Request($this->getPaymentStatusUrl($parameters['id']), $this->getConfiguration());
			$request->setMethod(Customweb_PayUnity_Request::METHOD_GET);
			$request->setData($this->getParameterBuilder($transaction)->buildStatusParameters());
			$response = $request->send();
			return $this->finalizeAuthorization($transaction, $response);
		}
		catch (Exception $e) {
			Customweb_Core_Logger_Factory::getLogger(get_class($this))->logInfo("Error quering transaction status.", $e);
		}
		return $this->redirect($transaction);
	}

	/**
	 * @param Customweb_Payment_Authorization_ITransactionContext $transactionContext
	 * @param $failedTransaction
	 */
	protected function createTransactionInternal(Customweb_Payment_Authorization_ITransactionContext $transactionContext, $failedTransaction = null){
		$transaction = new Customweb_PayUnity_Authorization_OppTransaction($transactionContext);
		$transaction->setAuthorizationMethod($this->getAuthorizationMethodName());
		$transaction->setLiveTransaction(!$this->getConfiguration()->isTestMode());
		$transaction->setUpdateExecutionDate(Customweb_Core_DateTime::_()->addMinutes(45));
		return $transaction;
	}

	/**
	 * @param Customweb_Payment_Authorization_ITransaction $transaction
	 * @param stdClass $response
	 * @return string
	 * @throws Exception
	 */
	public function finalizeAuthorization(Customweb_PayUnity_Authorization_OppTransaction $transaction, $response){
		$logger = Customweb_Core_Logger_Factory::getLogger(get_class($this));
		if (!$transaction->isAuthorizationFailed() && !$transaction->isAuthorized()) {
			if ($response == null) {
				$transaction->setAuthorizationFailed(Customweb_I18n_Translation::__('No response has been received.'));
			}
			elseif (!isset($response->id)) {
				$transaction->setAuthorizationFailed($this->getErrorMessage($transaction, $response));
				$transaction->setAuthorizationParameters($this->flattenObject($response));
			}
			else {
				$transaction->setPaymentId($response->id);
				$logger->logInfo('Finalize tranasction using payment id '.$response->id);
				if ($response->result->code == '000.200.000') {
					if (isset($response->redirect)) {
						return $this->getRedirectionForm($response);
					}
					else {
						$logger->logInfo("Received Code: 000.200.000 Pending. External id: " . $transaction->getExternalTransactionId());
						//Can be ideal pending state do nothing
						$transaction->setAuthorizationParameters($this->flattenObject($response));
						return $this->redirect($transaction);
					}
				}
				elseif ($response->result->code == '900.100.300') {
					$logger->logInfo("Received Code: 900.100.300 Still Pending. External id: " . $transaction->getExternalTransactionId());
					//Ideal uncertain result, will be updated later
					return $this->redirect($transaction);
				}
				elseif ($response->result->code == '000.000.000' || $response->result->code == '000.600.000' ||
						 strpos($response->result->code, '000.100.1') === 0) {
				 	$transaction->setUpdateExecutionDate(null);
					if ($response->paymentType == Customweb_PayUnity_IConstants::PAYMENT_TYPE_DEBIT ||
							(isset($response->workflow) && ($response->workflow == 'PA.CP' || $response->workflow == 'PA.RC'))) {
						$logger->logInfo("Recevied successful status. Authorize Transaction and internal capture. External id: " . $transaction->getExternalTransactionId());
						$transaction->authorize(Customweb_I18n_Translation::__($response->result->description));
						$captureItem = $transaction->capture();
						$captureItem->setCaptureId($response->id);
					}
					else{
						$logger->logInfo("Recevied successful status. Authorize Transaction. External id: " . $transaction->getExternalTransactionId());
						$transaction->authorize(Customweb_I18n_Translation::__($response->result->description));
					}
					$transaction->setAuthorizationParameters($this->flattenObject($response));
				}
				elseif (strpos($response->result->code, '000.400.') === 0 || $response->result->code == '800.400.500') {
					$logger->logInfo("Recevied successful code. Authorize Transaction, Mark Uncertain. External id: " . $transaction->getExternalTransactionId());
					$transaction->authorize(Customweb_I18n_Translation::__($response->result->description));
					$transaction->setAuthorizationUncertain();
					$transaction->setUpdateExecutionDate(Customweb_Core_DateTime::_()->addMinutes(60));
					$transaction->setAuthorizationParameters($this->flattenObject($response));
				}
				else {
					$transaction->setUpdateExecutionDate(null);
					$logger->logInfo("Received failure code ". $response->result->code. " External id: " . $transaction->getExternalTransactionId());
					$transaction->setAuthorizationFailed($this->getErrorMessage($transaction, $response));
					$transaction->setAuthorizationParameters($this->flattenObject($response));
				}
				if (isset($response->registrationId)) {
					$transaction->setRegistrationId($response->registrationId);
					$transaction->registerAliasDisplay($response);
				}
				$alias = $transaction->getTransactionContext()->getAlias();
				if($alias instanceof Customweb_PayUnity_Authorization_OppTransaction){
					$transaction->setRegistrationId($alias->getRegistrationId());
				}
			}
		}
		if ($transaction->isAuthorized() && $transaction->isAuthorizationUncertain()) {
			if ($response->result->code == '000.000.000' || $response->result->code == '000.600.000' ||
					 strpos($response->result->code, '000.100.1') === 0) {
				$transaction->approveUncertainAuthorization();
			} elseif (strpos($response->result->code, '000.400.') === 0
				|| $response->result->code == '800.400.500' || $response->result->code== '200.300.404') {
				// Do nothing, transaction is still uncertain
			} else {
				$transaction->setAuthorizationParameters(array_merge($transaction->getAuthorizationParameters(), array('finalCode' => $response->result->code)));
				$transaction->declineUncertainAuthorization($this->getErrorMessage($transaction, $response));
			}
		}
		if ($transaction->isAuthorized() && $transaction->isCapturePossible() && isset($response->result) && ($response->result->code == '000.000.000' || $response->result->code == '000.600.000' ||
				strpos($response->result->code, '000.100.1') === 0)){
			if ($response->paymentType == Customweb_PayUnity_IConstants::PAYMENT_TYPE_DEBIT ||
				(isset($response->workflow) && ($response->workflow == 'PA.CP' || $response->workflow == 'PA.RC'))) {
				$logger->logInfo("Recevied successful status. Capture transaction. External id: " . $transaction->getExternalTransactionId());
				$captureItem = $transaction->capture();
				$captureItem->setCaptureId($response->id);
				try {
					$shopCapture = $this->getContainer()->getBean('Customweb_Payment_BackendOperation_Adapter_Shop_ICapture');
					$shopCapture->capture($transaction);
				}
				catch (Exception $e) {
					$transaction->createHistoryItem(Customweb_I18n_Translation::__('The capture in the Shopsystem failed.'),
							Customweb_Payment_Authorization_ITransactionHistoryItem::ACTION_CAPTURING);
				}
			}
		}
			
		return $this->redirect($transaction);
	}

	/**
	 *
	 * @param Customweb_Payment_Authorization_ITransaction $transaction
	 */
	protected function redirect(Customweb_Payment_Authorization_ITransaction $transaction){
		if ($transaction->isAuthorizationFailed()) {
			return 'redirect:' . $transaction->getFailedUrl();
		}
		else {
			return 'redirect:' . $transaction->getSuccessUrl();
		}
	}

	/**
	 *
	 * @param stdClass $response
	 */
	protected function getRedirectionForm($response){
		$method='POST';
		if(property_exists($response->redirect, 'method')){
			$method = $response->redirect->method;
		}
		$html = '';
		$html = '<html><head></head><body onload="onLoadEvent();">';
		$html .= '<form method="'.$method.'" action="' . $response->redirect->url . '" id="payunityForm">';
		foreach ($response->redirect->parameters as $parameter) {
			$html .= '<input type="hidden" name="' . $parameter->name . '" value="' . $parameter->value . '">';
		}
		$html .= '<noscript><input type="submit" name="submit" value="' . Customweb_I18n_Translation::__('Continue') . '" /></noscript>';
		$html .= '</form>';
		$html .= '<script type="text/javascript">function onLoadEvent() { document.getElementById(\'payunityForm\').submit(); }</script>';
		$html .= '</body></html>';
		return $html;
	}

	/**
	 *
	 * @param Customweb_Payment_Authorization_ITransaction $transaction
	 * @param stdClass $response
	 * @throws Customweb_Payment_Exception_PaymentErrorException
	 */
	protected function validateResponse(Customweb_Payment_Authorization_ITransaction $transaction, $response){
		$this->getPaymentMethod($transaction->getPaymentMethod())->validateResponse($transaction, $response);
	}

	/**
	 *
	 * @param Customweb_Payment_Authorization_ITransaction $transaction
	 * @param Customweb_Payment_Authorization_ErrorMessage $response
	 */
	protected function getErrorMessage(Customweb_Payment_Authorization_ITransaction $transaction, $response){
		$gatewayErrorMessage = Customweb_I18n_Translation::__($response->result->description);
		$detailErrorMessage = null;
		if (isset($response->resultDetails) && isset($response->resultDetails->faultString) && !empty($response->resultDetails->faultString)) {
			$detailErrorMessage = Customweb_I18n_Translation::__($response->resultDetails->faultString);
		}
		return $this->getPaymentMethod($transaction->getPaymentMethod())->getErrorMessage($gatewayErrorMessage, $detailErrorMessage, $response);
	}

	/**
	 *
	 * @return string
	 */
	protected function getCheckoutUrl(){
		return $this->getConfiguration()->getBaseUrl() . '/v1/checkouts';
	}

	/**
	 *
	 * @return string
	 */
	protected function getPaymentUrl(){
		return $this->getConfiguration()->getBaseUrl() . '/v1/payments';
	}

	/**
	 *
	 * @return string
	 */
	protected function getPaymentStatusUrl($paymentId){
		return $this->getConfiguration()->getBaseUrl() . '/v1/payments/' . $paymentId;
	}
	
	/**
	 *
	 * @return string
	 */
	protected function getPaymentStatusUrlForMerchantId(){
		return $this->getConfiguration()->getBaseUrl() . '/v1/payments' ;
	}

	/**
	 *
	 * @param string $checkoutId
	 * @return string
	 */
	protected function getJavascriptUrl($checkoutId){
		return $this->getConfiguration()->getBaseUrl() . '/v1/paymentWidgets.js?checkoutId=' . $checkoutId;
	}



	/**
	 *
	 * @param string $registrationId
	 * @return string
	 */
	protected function getRegistrationUrl($registrationId){
		return $this->getConfiguration()->getBaseUrl() . '/v1/registrations/' . $registrationId . '/payments';
	}

	/**
	 *
	 * @param Customweb_Payment_Authorization_ITransaction $transaction
	 * @return Customweb_PayUnity_Authorization_AbstractParameterBuilder
	 */
	abstract protected function getParameterBuilder(Customweb_Payment_Authorization_ITransaction $transaction);
}