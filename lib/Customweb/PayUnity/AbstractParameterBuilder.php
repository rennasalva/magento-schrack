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

//require_once 'Customweb/PayUnity/Method/DefaultMethod.php';
//require_once 'Customweb/Util/Invoice.php';


abstract class Customweb_PayUnity_AbstractParameterBuilder
{
	/**
	 * @var Customweb_DependencyInjection_IContainer
	 */
	private $container = null;

	/**
	 * @var Customweb_PayUnity_Authorization_OppTransaction
	 */
	private $transaction = null;

	/**
	 * @var Customweb_PayUnity_Method_DefaultMethod
	 */
	private $paymentMethod = null;

	/**
	 * @param Customweb_DependencyInjection_IContainer $container
	 * @param Customweb_PayUnity_Authorization_OppTransaction $transaction
	 */
	public function __construct(
			Customweb_DependencyInjection_IContainer $container,
			Customweb_PayUnity_Authorization_OppTransaction $transaction
	) {
		$this->container = $container;
		$this->transaction = $transaction;
	}

	/**
	 * @return Customweb_PayUnity_Authorization_OppTransaction
	 */
	protected function getTransaction()
	{
		return $this->transaction;
	}

	/**
	 * @return Customweb_DependencyInjection_IContainer
	 */
	protected function getContainer()
	{
		return $this->container;
	}

	/**
	 * @return Customweb_PayUnity_Configuration
	 */
	protected function getConfiguration()
	{
		return $this->getContainer()->getBean('Customweb_PayUnity_Configuration');
	}

	/**
	 * @return Customweb_PayUnity_Method_DefaultMethod
	 */
	protected function getPaymentMethod()
	{
		if (!($this->paymentMethod instanceof Customweb_PayUnity_Method_DefaultMethod)) {
			$this->paymentMethod = $this->getContainer()->getBean('Customweb_PayUnity_Method_Factory')->getPaymentMethod($this->getTransaction()->getPaymentMethod(), $this->getTransaction()->getAuthorizationMethod());
		}
		return $this->paymentMethod;
	}

	/**
	 * @return Customweb_Payment_Authorization_ITransactionContext
	 */
	protected function getTransactionContext()
	{
		return $this->getTransaction()->getTransactionContext();
	}

	/**
	 * @return Customweb_Payment_Authorization_IOrderContext
	 */
	protected function getOrderContext()
	{
		return $this->getTransactionContext()->getOrderContext();
	}

	/**
	 * @return Customweb_Payment_Authorization_IPaymentCustomerContext
	 */
	protected function getPaymentCustomerContext()
	{
		return $this->getTransaction()->getPaymentCustomerContext();
	}

	/**
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected function getAuthenticationParameters()
	{
		$parameters = array();
		if($this->getConfiguration()->isAccessTokenConfigured()){
			$parameters['entityId']	= $this->getPaymentMethod()->getChannelId($this->getTransaction()->getTransactionContext()->getOrderContext()->getOrderAmountInDecimals());
		}
		else{
			$parameters['authentication.userId']	= $this->getConfiguration()->getUserId();
			$parameters['authentication.password']	= $this->getConfiguration()->getUserPassword();
			$parameters['authentication.entityId']	= $this->getPaymentMethod()->getChannelId($this->getTransaction()->getTransactionContext()->getOrderContext()->getOrderAmountInDecimals());
		}
		return $parameters;
	}

	/**
	 * @return array
	 */
	protected function getTestModeParameters()
	{
		$parameters = array();
		if ($this->getConfiguration()->isTestMode()) {
			$parameters['testMode'] = $this->getConfiguration()->getTestMode();
		}
		return $parameters;
	}

	/**
	 * Get additional parameters
	 *
	 * @return array
	 * @throws \Customweb_PayUnity_Exception_ConfigurationException
	 * @throws \Customweb_PayUnity_Exception_MissingConfigurationException
	 */
	protected function getAdditionalParameters()
	{
		$transactionAmount = Customweb_Util_Invoice::getTotalAmountExcludingTax(
			$this->getTransaction()->getTransactionContext()->getOrderContext()->getInvoiceItems()
		);
		$transactionTax    = Customweb_Util_Invoice::getTotalTaxAmount(
			$this->getTransaction()->getTransactionContext()->getOrderContext()->getInvoiceItems()
		);

		$parameters = array(
			'customParameters[SHOPPER_pluginId]'       => 'Magento',
			'customParameters[cwExternalId]'           => $this->getTransaction()->getExternalTransactionId(),
			'customParameters[transaction.net.amount]' => round($transactionAmount, 2),
			'customParameters[transaction.tax]'        => round($transactionTax, 2),
            //################################################### SCHRACK_CUSTOM
            // added additional parameter SHOPPER_Schrack_WWS_Order_Number
            //------------------------------------------------------------------
            'customParameters[SHOPPER_Schrack_WWS_Order_Number]' => $this->getTransaction()->getTransactionContext()->getOrderContext()->getSchrackWWSNumber()
            //######################################### SCHRACK_CUSTOM ***END***
        );

		foreach (explode("\n", $this->getConfiguration()->getConfigurationValue('custom_parameters')) as $customParameter) {
			$matches = array();
			if (preg_match('/^(customParameters\[.*?\])=(.*?)$/', $customParameter, $matches)) {
				$parameters[$matches[1]] = $matches[2];
			}
		}

		return $parameters;
	}
}