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

//require_once 'Customweb/PayUnity/Alias/Remove/ParameterBuilder.php';
//require_once 'Customweb/I18n/Translation.php';
//require_once 'Customweb/PayUnity/AbstractAdapter.php';
//require_once 'Customweb/PayUnity/Request.php';
//require_once 'Customweb/Payment/Alias/IRemoveAdapter.php';


/**
 * @Bean
 */
class Customweb_PayUnity_Alias_Remove_Adapter extends Customweb_PayUnity_AbstractAdapter implements Customweb_Payment_Alias_IRemoveAdapter
{
	public function remove(Customweb_Payment_Authorization_ITransaction $transaction)
	{
		$request = new Customweb_PayUnity_Request($this->getRegistrationUrl($transaction->getRegistrationId()), $this->getConfiguration());
		$request->setMethod(Customweb_PayUnity_Request::METHOD_DELETE);
		$request->setData($this->getParameterBuilder($transaction)->buildParameters());
		$response = $request->send();

		if ($response == null || !isset($response->id)
				|| ($response->result->code != '000.000.000'
				&& $response->result->code != '000.600.000'
				&& strpos($response->result->code, '000.100.1') !== 0)) {
			throw new Exception(Customweb_I18n_Translation::__($response->result->description));
		}
	}

	/**
	 * @param string $registrationId
	 * @return string
	 */
	protected function getRegistrationUrl($registrationId)
	{
		return $this->getConfiguration()->getBaseUrl() . '/v1/registrations/' . $registrationId;
	}

	/**
	 * @param Customweb_Payment_Authorization_ITransaction $transaction
	 * @return Customweb_PayUnity_Alias_Remove_ParameterBuilder
	 */
	protected function getParameterBuilder(Customweb_Payment_Authorization_ITransaction $transaction)
	{
		return new Customweb_PayUnity_Alias_Remove_ParameterBuilder($this->getContainer(), $transaction);
	}
}