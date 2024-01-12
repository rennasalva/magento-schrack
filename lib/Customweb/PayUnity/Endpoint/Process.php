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

//require_once 'Customweb/PayUnity/AESGCM.php';
//require_once 'Customweb/Payment/Endpoint/Controller/Abstract.php';
//require_once 'Customweb/Core/Logger/Factory.php';
//require_once 'Customweb/Core/Http/Response.php';



/**
 * @Controller("process")
 */
class Customweb_PayUnity_Endpoint_Process extends Customweb_Payment_Endpoint_Controller_Abstract {

	/**
	 * @var Customweb_Core_ILogger
	 */
	private $logger;

	/**
	 * @param Customweb_DependencyInjection_IContainer $container
	 */
	public function __construct(Customweb_DependencyInjection_IContainer $container) {
		parent::__construct($container);
		$this->logger = Customweb_Core_Logger_Factory::getLogger(get_class($this));
	}

	/**
	 *
	 * @Action("index")
	 */
	public function process(Customweb_Core_Http_IRequest $request){
		$idMap = $this->getTransactionId($request);
		$response = null;
		$this->logger->logInfo("The return process has been started for the transaction with external id " . $idMap['id'] . ".");
		for ($i = 0; $i < 5; $i++) {
			try {
				$this->getTransactionHandler()->beginTransaction();
				$transaction = $this->getTransactionHandler()->findTransactionByTransactionExternalId($idMap['id'], false);
				$adapter = $this->getAdapterFactory()->getAuthorizationAdapterByName($transaction->getAuthorizationMethod());
				$parameters = $request->getParameters();
				$response = $adapter->processAuthorization($transaction, $parameters);
				$this->getTransactionHandler()->persistTransactionObject($transaction);
				$this->getTransactionHandler()->commitTransaction();
				$this->logger->logInfo("The return process has been finished for the transaction " . $transaction->getTransactionId() . ".");
				return $response;
			}
			catch (Customweb_Payment_Exception_OptimisticLockingException $lockingException) {
				$this->getTransactionHandler()->rollbackTransaction();
				if($i == 4){
					$this->logger->logError("Optimistic locking exception while processing the transaction external id" . $idMap['id'] . ".");
					return $response;
				}
				sleep(1);
			}
		}
	}

	/**
	 *
	 * @Action("alias")
	 */
	public function processAlias(Customweb_Core_Http_IRequest $request){
		$idMap = $this->getTransactionId($request);
		$response = null;

		try {
			$this->getTransactionHandler()->beginTransaction();
			$transaction = $this->getTransactionHandler()->findTransactionByTransactionExternalId($idMap['id'], false);
			$this->logger->logInfo("The alias process has been started for the transaction " . $transaction->getTransactionId() . ".");
			$adapter = $this->getAdapterFactory()->getAuthorizationAdapterByName($transaction->getAuthorizationMethod());
			$parameters = $request->getParameters();
			$response = $adapter->processAliasAuthorization($transaction, $parameters);
			$this->getTransactionHandler()->persistTransactionObject($transaction);
			$this->getTransactionHandler()->commitTransaction();
			$this->logger->logInfo("The alias process has been finished for the transaction " . $transaction->getTransactionId() . ".");
			return $response;
		}
		catch (Customweb_Payment_Exception_OptimisticLockingException $lockingException) {
			$this->logger->logError("The alias process has been rolledback for the transaction " . $idMap['id'] . ".");
			$this->getTransactionHandler()->rollbackTransaction();
			return $response;
		}

	}

	/**
	 *
	 * @Action("async")
	 */
	public function processAsync(Customweb_Core_Http_IRequest $request){
		$idMap = $this->getTransactionId($request);
		$response = null;

		try {
			$this->getTransactionHandler()->beginTransaction();
			$transaction = $this->getTransactionHandler()->findTransactionByTransactionExternalId($idMap['id'], false);
			$this->logger->logInfo("The async process has been started for the transaction " . $transaction->getTransactionId() . ".");
			$adapter = $this->getAdapterFactory()->getAuthorizationAdapterByName($transaction->getAuthorizationMethod());
			$parameters = $request->getParameters();
			$response = $adapter->processAsynchronousAuthorization($transaction, $parameters);
			$this->getTransactionHandler()->persistTransactionObject($transaction);
			$this->getTransactionHandler()->commitTransaction();
			$this->logger->logInfo("The async process has been finished for the transaction " . $transaction->getTransactionId() . ".");
			return $response;
		}
		catch (Customweb_Payment_Exception_OptimisticLockingException $lockingException) {
			$this->getTransactionHandler()->rollbackTransaction();
			$this->logger->logError("The async process has been rolledback for the transaction " . $idMap['id'] . ".");
			return $response;
		}
	}

	/**
	 * @Action('webhook')
	 */
	public function processWebhook(Customweb_Core_Http_IRequest $request){
		$configuration = $this->getContainer()->getBean('Customweb_PayUnity_Configuration');
		$secretKey = trim($configuration->getConfigurationValue('webhook_secret_key'));
		$headers = array_change_key_case($request->getParsedHeaders(), CASE_LOWER);
		$errorMessage = '';
		$this->logger->logInfo("The webhook process has been started.");
		try {
			$initializationVector = $headers['x-initialization-vector'];
			$authenticationTag = $headers['x-authentication-tag'];
			$body = $request->getBody();

			$decrypted = Customweb_PayUnity_AESGCM::decrypt(hex2bin($secretKey), hex2bin($initializationVector), hex2bin($body), null,
					hex2bin($authenticationTag));
			$decoded = json_decode($decrypted);
			if($decoded == null){
				$this->logger->logInfo("The webhook process could not decrypt the message.");
				return Customweb_Core_Http_Response::_("The webhook process could not decrypt the message.")->setStatusCode(500);
			}
			if(isset($decoded->type) && $decoded->type == "test"){
				return Customweb_Core_Http_Response::_("Test OK");
			}
			if (!isset($decoded->payload->customParameters->cwExternalId) || !isset($decoded->type)) {
				$this->logger->logInfo("The webhook could not extract external id from the custom parameters.");
				return Customweb_Core_Http_Response::_("The webhook could not extract external id from the custom parameters.")->setStatusCode(500);
			}

			$externalTransactionId = $decoded->payload->customParameters->cwExternalId;
			$this->logger->logInfo("The webhook process has been started for the transaction with external id " . $externalTransactionId . ".");
			for ($i = 0; $i < 5; $i++) {
				try {
					$this->getTransactionHandler()->beginTransaction();
					$transaction = $this->getTransactionHandler()->findTransactionByTransactionExternalId($externalTransactionId, false);
					if ($decoded->type == 'PAYMENT') {
						if (!isset($decoded->payload->paymentType) ||
								($decoded->payload->paymentType != 'PA' && $decoded->payload->paymentType != 'DB' && $decoded->payload->paymentType != 'RC')) {
							$this->getTransactionHandler()->commitTransaction();
							$paymentType = "Not Set";
							if(isset($decoded->payload->paymentType)){
								$paymentType  = (string) $decoded->payload->paymentType;
							}
							$this->logger->logInfo("The webhook process has been finished for the transaction with external id " . $externalTransactionId . ". Unhandled Payment Type: ".$paymentType);
							return Customweb_Core_Http_Response::_("");
						}
						if ($decoded->payload->result->code == '800.400.500') {
							$this->getTransactionHandler()->commitTransaction();
							$this->logger->logInfo("The webhook process has been finished for the transaction with external id " . $externalTransactionId . ". Waiting for confirmation (800.400.500)");
							return Customweb_Core_Http_Response::_("");
						}
						$this->logger->logInfo("Start finalizing the transaction with external id " . $externalTransactionId );
						$adapter = $this->getAdapterFactory()->getAuthorizationAdapterByName($transaction->getAuthorizationMethod());
						$adapter->finalizeAuthorization($transaction, $decoded->payload);
					}
					elseif ($decoded->type == 'REGISTRATION') {
						if (!isset($decoded->action) || $decoded->action != 'CREATED') {
							$this->getTransactionHandler()->commitTransaction();
							$action = "Not Set";
							if(isset($decoded->action)){
								$action  = (string) $decoded->action;
							}
							$this->getTransactionHandler()->commitTransaction();
							$error = "The webhook process has been finished for the transaction with external id " . $externalTransactionId . ". Unhandled Registration Action: ".$action;
							$this->logger->logInfo($error);
							return Customweb_Core_Http_Response::_($error)->setStatusCode(500);
						}
						$this->logger->logInfo("Updating alias parameter for transaction with external id " . $externalTransactionId );
						$transaction->setRegistrationId($decoded->payload->id);
						$transaction->registerAliasDisplay($decoded->payload);
					}
					else{
						$this->getTransactionHandler()->commitTransaction();
						$error = "The webhook has been called with unknown type for the transaction with external id " . $externalTransactionId . ". Unhandled Type: ".$decoded->type;
						$this->logger->logInfo($error);
						return Customweb_Core_Http_Response::_($error)->setStatusCode(500);
					}
					$this->getTransactionHandler()->persistTransactionObject($transaction);
					$this->getTransactionHandler()->commitTransaction();
					$this->logger->logInfo("The webhook process has been finished for the transaction with external id " . $externalTransactionId . ".");
					return Customweb_Core_Http_Response::_("");
				}
				catch (Customweb_Payment_Exception_OptimisticLockingException $lockingException) {
					$this->getTransactionHandler()->rollbackTransaction();
					if($i == 4){
						throw $lockingException;
					}
					sleep(1);
				}
			}
		}
		catch (Exception $e) {
			$errorMessage = $e->getMessage();
			$this->logger->logException($e);
			return Customweb_Core_Http_Response::_($errorMessage)->setStatusCode(500);
		}

	}
}