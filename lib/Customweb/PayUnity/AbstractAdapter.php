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



abstract class Customweb_PayUnity_AbstractAdapter
{
	/**
	 * @var Customweb_DependencyInjection_IContainer
	 */
	private $container;

	/**
	 * @param Customweb_DependencyInjection_IContainer $container
	 */
	public function __construct(Customweb_DependencyInjection_IContainer $container)
	{
		$this->container = $container;
	}

	/**
	 * @return Customweb_DependencyInjection_IContainer
	 */
	public function getContainer()
	{
		return $this->container;
	}

	/**
	 * @return Customweb_PayUnity_Configuration
	 */
	public function getConfiguration()
	{
		return $this->getContainer()->getBean('Customweb_PayUnity_Configuration');
	}

	/**
	 * @return Customweb_Payment_Endpoint_IAdapter
	 */
	public function getEndpointAdapter()
	{
		return $this->getContainer()->getBean('Customweb_Payment_Endpoint_IAdapter');
	}

	/**
	 * @return Customweb_PayUnity_Method_Factory
	 */
	public function getMethodFactory()
	{
		return $this->getContainer()->getBean('Customweb_PayUnity_Method_Factory');
	}
	
	/**
	 *
	 * @param string $checkoutId
	 * @return string
	 */
	protected function getCheckoutStatusUrl($checkoutId){
		return $this->getConfiguration()->getBaseUrl() . '/v1/checkouts/' . $checkoutId . '/payment';
	}

	/**
	 * @param stdClass $object
	 * @return array
	 */
	protected function flattenObject($object, $keyPrefix = '', &$result = array())
	{
		$parameters = (array) $object;
		foreach ($parameters as $key => $value) {
			if (is_array($value) || is_object($value)) {
				$this->flattenObject($value, $keyPrefix . $key . '.', $result);
			} else {
				$result[$keyPrefix . $key] = $value;
			}
		}
		return $result;
	}
}