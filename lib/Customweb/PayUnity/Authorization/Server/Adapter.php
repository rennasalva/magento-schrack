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

//require_once 'Customweb/PayUnity/Authorization/AbstractAdapter.php';
//require_once 'Customweb/I18n/Translation.php';
//require_once 'Customweb/Payment/Authorization/Server/IAdapter.php';
//require_once 'Customweb/PayUnity/Request.php';
//require_once 'Customweb/PayUnity/Authorization/Server/ParameterBuilder.php';
//require_once 'Customweb/Payment/Authorization/ITransaction.php';
//require_once 'Customweb/Core/Http/Response.php';



/**
 * @Bean
 */
class Customweb_PayUnity_Authorization_Server_Adapter extends Customweb_PayUnity_Authorization_AbstractAdapter implements 
		Customweb_Payment_Authorization_Server_IAdapter {

	public function getAdapterPriority(){
		return 200;
	}

	public function getAuthorizationMethodName(){
		return self::AUTHORIZATION_METHOD_NAME;
	}

	public function createTransaction(Customweb_Payment_Authorization_Server_ITransactionContext $transactionContext, $failedTransaction){
		return $this->createTransactionInternal($transactionContext, $failedTransaction);
	}

	public function processAuthorization(Customweb_Payment_Authorization_ITransaction $transaction, array $parameters){
		if ($transaction->getTransactionContext()->getAlias() instanceof Customweb_Payment_Authorization_ITransaction) {
			$result = $this->processAliasAuthorization($transaction, $parameters);
		}
		else {
			$response = null;
			try {
				$request = new Customweb_PayUnity_Request($this->getPaymentUrl(), $this->getConfiguration());
				$request->setMethod(Customweb_PayUnity_Request::METHOD_POST);
				$request->setData($this->getParameterBuilder($transaction)->buildAuthorizationParameters($parameters));
				$response = $request->send();
			}
			catch (Exception $e) {
				$transaction->setAuthorizationFailed(Customweb_I18n_Translation::__($e->getMessage()));
			}
			$result = $this->finalizeAuthorization($transaction, $response);
		}
		
		if (is_string($result) && strpos($result, 'redirect:') === 0) {
			$result = $result;
			$url = substr($result, strlen('redirect:'));
			$response = new Customweb_Core_Http_Response();
			$response->appendHeader('Location: ' . $url);
			return $response;
		}
		else if (is_string($result)) {
			$response = new Customweb_Core_Http_Response();
			$response->setBody($result);
			return $response;
		}
	}

	/**
	 *
	 * @param Customweb_Payment_Authorization_ITransaction $transaction
	 * @return Customweb_PayUnity_Authorization_Widget_ParameterBuilder
	 */
	protected function getParameterBuilder(Customweb_Payment_Authorization_ITransaction $transaction){
		return new Customweb_PayUnity_Authorization_Server_ParameterBuilder($this->getContainer(), $transaction);
	}
}