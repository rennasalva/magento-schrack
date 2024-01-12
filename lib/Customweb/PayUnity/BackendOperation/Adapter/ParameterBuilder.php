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

//require_once 'Customweb/PayUnity/AbstractParameterBuilder.php';
//require_once 'Customweb/Util/Invoice.php';


class Customweb_PayUnity_BackendOperation_Adapter_ParameterBuilder extends Customweb_PayUnity_AbstractParameterBuilder
{
	/**
	 * @return array
	 */
	public function buildCancelParameters()
	{
		return array_merge(
				$this->getAuthenticationParameters(),
				$this->getTestModeParameters(),
				array(
					'paymentType' => 'RV'
				)
		);
	}

	/**
	 * @param array $items
	 * @return array
	 */
	public function buildCaptureParameters($items)
	{
		return array_merge(
				$this->getAuthenticationParameters(),
				$this->getTestModeParameters(),
				$this->getBackofficeParameters(Customweb_Util_Invoice::getTotalAmountIncludingTax($items), 'CP'),
				$this->getPaymentMethod()->getCaptureParameters($this->getTransaction(), $items)
		);
	}

	/**
	 * @param array $items
	 * @return array
	 */
	public function buildRefundParameters($items)
	{
		return array_merge(
				$this->getAuthenticationParameters(),
				$this->getTestModeParameters(),
				$this->getBackofficeParameters(Customweb_Util_Invoice::getTotalAmountIncludingTax($items), 'RF'),
				$this->getPaymentMethod()->getRefundParameters($this->getTransaction(), $items)
		);
	}

	/**
	 * @param double $amount
	 * @param string $paymentType
	 * @return array
	 */
	protected function getBackofficeParameters($amount, $paymentType)
	{
		$parameters = array();
		//The amount has to always have to decimal places, according to documentation
		$parameters['amount']					= number_format($amount, 2, '.', '');
		$parameters['currency']					= $this->getOrderContext()->getCurrencyCode();
		$parameters['paymentType']				= $paymentType;
		return $parameters;
	}
}