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

//require_once 'Customweb/I18n/Translation.php';


/**
 * @Bean
 */
final class Customweb_PayUnity_Configuration
{
	/**
	 * @var Customweb_Payment_IConfigurationAdapter
	 */
	private $configurationAdapter = null;

	/**
	 *
	 * @Inject({'Customweb_Payment_IConfigurationAdapter'})
	 */
	public function __construct(Customweb_Payment_IConfigurationAdapter $configurationAdapter)
	{
		$this->configurationAdapter = $configurationAdapter;
	}

	/**
	 * @return Customweb_Payment_IConfigurationAdapter
	 */
	public function getConfigurationAdapter()
	{
		return $this->configurationAdapter;
	}

	/**
	 * Return a configuration value by it's key.
	 *
	 * @param string $key
	 * @return string
	 */
	public function getConfigurationValue($key)
	{
		return $this->configurationAdapter->getConfigurationValue($key);
	}

	/**
	 * Return whether a test mode is active.
	 *
	 * @return boolean
	 */
	public function isTestMode()
	{
		if (strtolower($this->getConfigurationValue('operation_mode')) == 'live') {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Return the test mode. This can be INTERNAL or EXTERNAL.
	 *
	 * @return string
	 */
	public function getTestMode()
	{
		if (strtolower($this->getConfigurationValue('test_mode')) == 'external') {
			return 'EXTERNAL';
		} else {
			return 'INTERNAL';
		}
	}
	
	
	public function getAccessToken(){
		return trim($this->getConfigurationValue('access_token'));
	}
	
	public function isAccessTokenConfigured(){
		$token = $this->getAccessToken();
		return !empty($token);
	}

	/**
	 * Return the user id. It must be configured with SEND rights in the payment
	 * service provider's backend.
	 *
	 * @return string
	 */
	public function getUserId()
	{
		$userId = trim($this->getConfigurationValue('user_id'));
		if (empty($userId)) {
			throw new Exception(Customweb_I18n_Translation::__("You do not specify yet a user id. Please specify in the main configurations a user id."));
		}
		return $userId;
	}

	/**
	 * Return the user's password.
	 *
	 * @return string
	 */
	public function getUserPassword()
	{
		$password = $this->getConfigurationValue('user_password');
		if (empty($password)) {
			throw new Exception(Customweb_I18n_Translation::__("You do not specify yet a user password. Please specify in the main configurations a user password."));
		}
	
		return $password;
	}

	/**
	 * Return the global entity id.
	 *
	 * @return string
	 */
	public function getGlobalEntityId()
	{
		$entityId = $this->getConfigurationValue('global_entity_id');
		if (empty($entityId)) {
			throw new Exception(Customweb_I18n_Translation::__("You do not specify yet the global entity ID. Please specify in the main configurations a global entity id."));
		}
		return $entityId;
	}
	
	/**
	 * Return the transaction id schema.
	 *
	 * @return string
	 */
	public function getTransactionIdSchema()
	{
		return $this->getConfigurationValue('transaction_id_schema');
	}
	
	/**
	 * Return the descriptor schema.
	 *
	 * @return string
	 */
	public function getDescriptorSchema()
	{
		return $this->getConfigurationValue('descriptor_schema');
	}

	/**
	 * Return the url to be used to send transaction requests.
	 *
	 * @return string
	 */
	public function getBaseUrl()
	{
		if ($this->isTestMode()) {
			return rtrim('https://eu-test.oppwa.com/', '/');
		} else {
			return rtrim('https://eu-prod.oppwa.com/', '/');
		}
	}

}