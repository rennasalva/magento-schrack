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

//require_once 'Customweb/Payment/Authorization/ITransactionHistoryItem.php';
//require_once 'Customweb/I18n/Translation.php';
//require_once 'Customweb/PayUnity/Authorization/OppTransactionCapture.php';
//require_once 'Customweb/Payment/Authorization/DefaultTransaction.php';

class Customweb_PayUnity_Authorization_OppTransaction extends Customweb_Payment_Authorization_DefaultTransaction {
	/**
	 *
	 * @var string
	 */
	protected $authorizationChannel = null;
	
	/**
	 *
	 * @var string
	 */
	protected $checkoutId = null;
	
	/**
	 *
	 * @var string
	 */
	protected $registrationId = null;
	
	/**
	 * @var int
	 */
	protected $updateRetryCounter = 0;

	/**
	 *
	 * @return string
	 */
	public function getAuthorizationChannel(){
		return $this->authorizationChannel;
	}

	/**
	 *
	 * @param string $authorizationChannel
	 * @return Customweb_PayUnity_Authorization_OppTransaction
	 */
	public function setAuthorizationChannel($authorizationChannel){
		$this->authorizationChannel = $authorizationChannel;
		return $this;
	}

	/**
	 *
	 * @return string
	 */
	public function getCheckoutId(){
		return $this->checkoutId;
	}

	/**
	 *
	 * @param string $checkoutId
	 * @return Customweb_PayUnity_Authorization_OppTransaction
	 */
	public function setCheckoutId($checkoutId){
		$this->checkoutId = $checkoutId;
		return $this;
	}

	/**
	 *
	 * @return string
	 */
	public function getRegistrationId(){
		return $this->registrationId;
	}

	/**
	 * @return Customweb_PayUnity_Authorization_OppTransaction
	 */
	public function increaseUpdateRetryCounter(){
		$this->updateRetryCounter++;
		return $this;
	}
	
	/**
	 * @return Customweb_PayUnity_Authorization_OppTransaction
	 */
	public function resetUpdateRetryCounter(){
		$this->updateRetryCounter = 0;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getUpdateRetryCounter(){
		return $this->updateRetryCounter;
	}

	/**
	 * Register an alias for this transaction.
	 *
	 * @param stdClass $response
	 * @return Customweb_PayUnity_Authorization_OppTransaction
	 */
	public function registerAliasDisplay($response){
		if (isset($response->card) && isset($response->card->last4Digits)) {
			$aliasForDisplay = 'xxxx xxxx xxxx ' . $response->card->last4Digits;
			if (isset($response->card->expiryMonth) && isset($response->card->expiryYear)) {
				$aliasForDisplay .= ' (' . $response->card->expiryMonth . '/' . substr($response->card->expiryYear, -2) . ')';
			}
			$this->setAliasForDisplay($aliasForDisplay);
		}
		else if (isset($response->bankAccount) && isset($response->bankAccount->iban)) {
			$this->setAliasForDisplay($this->maskIban($response->bankAccount->iban));
		}
		
		return $this;
	}

	public function setRegistrationId($registrationId){
		$this->registrationId = $registrationId;
		return $this;
	}

	private function maskIban($iban){
		$first = 4;
		$last = 4;
		$length = strlen($iban);
		$middle = $length - $first - $last;
		return substr($iban, 0, $first) . str_repeat('X', $middle) . substr($iban, $first + $middle);
	}

	/**
	 *
	 * @return Customweb_PayUnity_Authorization_OppTransaction
	 */
	public function approveUncertainAuthorization(){
		$this->setAuthorizationUncertain(false);
		$historyMessage = Customweb_I18n_Translation::__("The authorization is approved.");
		$this->createHistoryItem($historyMessage, Customweb_Payment_Authorization_ITransactionHistoryItem::ACTION_AUTHORIZATION);
		return $this;
	}

	/**
	 *
	 * @param string $errorMessage
	 * @return Customweb_PayUnity_Authorization_OppTransaction
	 */
	public function declineUncertainAuthorization($errorMessage){
		$this->setUncertainTransactionFinallyDeclined();
		$historyMessage = Customweb_I18n_Translation::__("The authorization is denied: !errorMessage", 
				array(
					"!errorMessage" => $errorMessage 
				));
		$this->createHistoryItem($historyMessage, Customweb_Payment_Authorization_ITransactionHistoryItem::ACTION_AUTHORIZATION);
		return $this;
	}

	protected function getTransactionSpecificLabels(){
		$labels = array();
		$parameters = $this->getAuthorizationParameters();
		
		$this->tryAddLabel("resultDetails.ConnectorTxID1", Customweb_I18n_Translation::__("Connector Transaction ID"), $parameters, $labels);
		$this->tryAddLabel("paymentBrand", Customweb_I18n_Translation::__("Payment Type"), $parameters, $labels);
		$this->tryAddLabel("bankAccount.mandate.id", Customweb_I18n_Translation::__("SEPA Mandate ID"), $parameters, $labels);
		$this->tryAddLabel("customer.phone", Customweb_I18n_Translation::__("Customer Phone Number"), $parameters, $labels);
		$this->tryAddLabel("customer.birthDate", Customweb_I18n_Translation::__("Customer Date of Birth"), $parameters, $labels);
		$this->tryAddLabel("customer.sex", Customweb_I18n_Translation::__("Customer Gender"), $parameters, $labels);
		
		return $labels;
	}

	/**
	 * Checks if the given key is set in the array, and if it is adds a label to the given labels array.
	 *
	 * @param string $key
	 * @param Customweb_I18n_LocalizableString $label
	 * @param array $parameters
	 * @param array $labels
	 */
	private function tryAddLabel($key, $label, $parameters, &$labels){
		if ($parameters != null && isset($parameters[$key])) {
			$labels[$key] = array(
				'label' => $label,
				'value' => $parameters[$key] 
			);
		}
	}

	protected function buildNewCaptureObject($captureId, $amount, $status = NULL){
		return new Customweb_PayUnity_Authorization_OppTransactionCapture($captureId, $amount, $status);
	}
}