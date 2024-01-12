<?php
/**
 * Anowave Google Tag Manager Enhanced Ecommerce (UA) Tracking
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Anowave license that is
 * available through the world-wide-web at this URL:
 * http://www.anowave.com/license-agreement/
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category 	Anowave
 * @package 	Anowave_Ec
 * @copyright 	Copyright (c) 2016 Anowave (http://www.anowave.com/)
 * @license  	http://www.anowave.com/license-agreement/
 */
class Anowave_Ec_Model_Api
{
	const TAG_MANAGER_VARIABLE_TYPE_DATALAYER_VARIABLE 		= 'v'; 
	const TAG_MANAGER_VARIABLE_TYPE_CONSTANT_VARIABLE 		= 'c';
	const TAG_MANAGER_VARIABLE_TYPE_JAVASCRIPT 				= 'jsm';
	const TAG_MANAGER_TRIGGER_TYPE_CUSTOM_EVENT 			= 'customEvent';
	
	
	/**
	 * Tag types
	 */
	const TAG_MANAGER_TAG_TYPE_UNIVERSAL_ANALYTICS 			= 'ua';
	const TAG_MANAGER_TAG_TYPE_SOCIAL			 			= 'social';
	/*
	 * Trigger names
	 */
	const TRIGGER_ADD_TO_CART 								= 'Event Equals Add To Cart';
	const TRIGGER_REMOVE_FROM_CART 							= 'Event Equals Remove From Cart';
	const TRIGGER_CHECKOUT 									= 'Event Equals Checkout';
	const TRIGGER_CHECKOUT_OPTION 							= 'Event Equals Checkout Option';
	const TRIGGER_PRODUCT_CLICK								= 'Event Equals Product Click';
	const TRIGGER_REMARKETING_TAG							= 'Event Equals Dynamic Remarketing';
	const TRIGGER_SOCIAL_INTERACTION 						= 'Event Equals Social Interaction';
	const TRIGGER_USER_TIMING								= 'Event Equals User Timing';
	const TRIGGER_PROMOTION_CLICK							= 'Event Equals Promotion Click';
	const TRIGGER_PROMOTION_VIEW							= 'Event Equals Promotion View Non Interactive';
	const TRIGGER_IMPRESSION								= 'Event Equals Impression';



	/**
	 * Tag names
	 */
	const TAG_ADD_TO_CART									= 'EE Add To Cart';
	const TAG_REMOVE_FROM_CART								= 'EE Remove From Cart';
	//const TAG_CHECKOUT									= 'EE Checkout Step';
	//const TAG_CHECKOUT_OPTION								= 'EE Checkout Step Option';
	const TAG_PRODUCT_CLICK									= 'EE Product Click';
	const TAG_PRODUCT_IMPRESSION							= 'EE Product Impression Payload';
	const TAG_SOCIAL_INTERACTION							= 'EE Social Interaction';
	const TAG_ADWORDS_DYNAMIC_REMARKETING					= 'EE AdWords Dynamic Remarketing';
	const TAG_USER_TIMING									= 'EE User Timing';
	const TAG_PROMOTION_CLICK								= 'EE Promotion Click';
	const TAG_PROMOTION_VIEW								= 'EE Promotion View';


	/**
	 * @var Google_Service_TagManager
	 */
	private $service = null;

	/**
	 * OAuth2
	 *
	 * @var Google_Service_Oauth2
	 */
	private $oauth = null;

	/**
	 * OAuth Scopes
	 *
	 * @var array
	 */

	private $client = null;

	private $scopes = array
	(
		'https://www.googleapis.com/auth/userinfo.profile',
		'https://www.googleapis.com/auth/tagmanager.readonly',
		'https://www.googleapis.com/auth/tagmanager.edit.containers',
		'https://www.googleapis.com/auth/tagmanager.edit.containerversions',
		'https://www.googleapis.com/auth/tagmanager.publish',
		'https://www.googleapis.com/auth/tagmanager.manage.accounts'
	);

	public function __construct()
	{
		set_time_limit(0);
	}

	/**
	 * Get Client
	 *
	 * @return Google_Client
	 */
	public function getClient()
	{
		if (!$this->client)
		{
			$this->client = new Google_Client();

			/**
			 * Set Application name
			*/
			$this->client->setApplicationName('Anowave');
			$this->client->setClientId('606603072955-f1aa5hg4sju2gsaj70voq6v0errut3gf.apps.googleusercontent.com');
			$this->client->setClientSecret('STtsqOh53TDXUMEGFlszhadf');

			/**
			 * Set scopes
			*/
			$this->client->setScopes($this->scopes);

			$this->client->setState
			(
				Mage::helper("adminhtml")->getUrl("adminhtml/system_config/edit", array('section' => 'ec'))
			);

			/**
			 * Set redirect URI
			 */
			$this->client->setRedirectUri('http://oauth.anowave.com/');


			/**
			 * Check authorisation code
			 */
			if (isset($_GET['code']))
			{
				$this->getClient()->authenticate($_GET['code']);

				Mage::getSingleton('core/session')->setAccessToken
				(
					$this->client->getAccessToken()
				);

				header('Location: ' . Mage::helper("adminhtml")->getUrl("adminhtml/system_config/edit", array('section' => 'ec')));
				exit();
			}

			/**
			 * Check session access token
			*/
			$token = Mage::getSingleton('core/session')->getAccessToken();

			if ($token)
			{
				$this->client->setAccessToken($token);
			}
		}

		return $this->client;
	}

	/**
	 * Create Google Tag Manager entries
	 *
	 * @param string $entry
	 * @return mixed|NULL
	 */
	public function create($entry)
	{
		if (method_exists($this, $entry))
		{
			$store = $this->getStoreId();

			return call_user_func_array(array($this, $entry), array
			(
				trim(Mage::getStoreConfig('ec/api/google_gtm_account_id', $store)),
				trim(Mage::getStoreConfig('ec/api/google_gtm_container',$store))
			));
		}

		return array();
	}

	public function getStoreId()
	{
		if (strlen($code = Mage::getSingleton('adminhtml/config_data')->getStore()))
		{
			return (int) Mage::getModel('core/store')->load($code)->getId();
		}
		elseif (strlen($code = Mage::getSingleton('adminhtml/config_data')->getWebsite()))
		{
			return (int) Mage::app()->getWebsite(Mage::getModel('core/website')->load($code)->getId())->getDefaultStore()->getId();
		}
		else
		{
			return 0;
		}
	}

	/**
	 * Create container variables
	 */
	public function ec_api_variables($account, $container)
	{
		$log = array();
		$set = array();


		/**
		 * Get existing variables
		 */
		$variables = $this->getService()->accounts_containers_variables->listAccountsContainersVariables($account, $container)->getVariables();

		/**
		 * Check which variables already exist
		*/
		foreach ($variables as $variable)
		{
			$set[$variable->name] = true;
		}

		/**
		 * Variables schema
		 */
		$schema = array
		(
			'ua' => array
			(
				'name' 		=> 'ua',
				'type'		=> self::TAG_MANAGER_VARIABLE_TYPE_CONSTANT_VARIABLE,
				'parameter' => array
				(
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'value',
						'value' => Mage::getStoreConfig('ec/api/google_gtm_ua')
					)
				)
			),
			'google_tag_params' => array
			(
				'name' 		=> 'google_tag_params',
				'type'		=> self::TAG_MANAGER_VARIABLE_TYPE_DATALAYER_VARIABLE,
				'parameter' => array
				(
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'name',
						'value' => 'google_tag_params'
					),
					array
					(
						'type' 	=> 'integer',
						'key' 	=> 'dataLayerVersion',
						'value' => 2
					)
				)
			),
			'timing category' => array
			(
				'name' 		=> 'timing category',
				'type'		=> self::TAG_MANAGER_VARIABLE_TYPE_DATALAYER_VARIABLE,
				'parameter' => array
				(
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'name',
						'value' => 'timingCategory'
					),
					array
					(
						'type' 	=> 'integer',
						'key' 	=> 'dataLayerVersion',
						'value' => 2
					)
				)
			),
			'timing label' => array
			(
				'name' 		=> 'timing label',
				'type'		=> self::TAG_MANAGER_VARIABLE_TYPE_DATALAYER_VARIABLE,
				'parameter' => array
				(
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'name',
						'value' => 'timingLabel'
					),
					array
					(
						'type' 	=> 'integer',
						'key' 	=> 'dataLayerVersion',
						'value' => 2
					)
				)
			),
			'timing var' => array
			(
				'name' 		=> 'timing var',
				'type'		=> self::TAG_MANAGER_VARIABLE_TYPE_DATALAYER_VARIABLE,
				'parameter' => array
				(
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'name',
						'value' => 'timingVar'
					),
					array
					(
						'type' 	=> 'integer',
						'key' 	=> 'dataLayerVersion',
						'value' => 2
					)
				)
			),
			'timing value' => array
			(
				'name' 		=> 'timing value',
				'type'		=> self::TAG_MANAGER_VARIABLE_TYPE_DATALAYER_VARIABLE,
				'parameter' => array
				(
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'name',
						'value' => 'timingValue'
					),
					array
					(
						'type' 	=> 'integer',
						'key' 	=> 'dataLayerVersion',
						'value' => 2
					)
				)
			),
			'social network' => array
			(
				'name' 		=> 'social network',
				'type'		=> self::TAG_MANAGER_VARIABLE_TYPE_DATALAYER_VARIABLE,
				'parameter' => array
				(
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'name',
						'value' => 'socialNetwork'
					),
					array
					(
						'type' 	=> 'integer',
						'key' 	=> 'dataLayerVersion',
						'value' => 2
					)
				)
			),
			'social action' => array
			(
				'name' 		=> 'social action',
				'type'		=> self::TAG_MANAGER_VARIABLE_TYPE_DATALAYER_VARIABLE,
				'parameter' => array
				(
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'name',
						'value' => 'socialAction'
					),
					array
					(
						'type' 	=> 'integer',
						'key' 	=> 'dataLayerVersion',
						'value' => 2
					)
				)
			),
			'social target' => array
			(
				'name' 		=> 'social target',
				'type'		=> self::TAG_MANAGER_VARIABLE_TYPE_DATALAYER_VARIABLE,
				'parameter' => array
				(
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'name',
						'value' => 'socialTarget'
					),
					array
					(
						'type' 	=> 'integer',
						'key' 	=> 'dataLayerVersion',
						'value' => 2
					)
				)
			),
			'visitor' => array
			(
				'name' 		=> 'visitor',
				'type'		=> self::TAG_MANAGER_VARIABLE_TYPE_DATALAYER_VARIABLE,
				'parameter' => array
				(
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'name',
						'value' => 'visitorId'
					),
					array
					(
						'type' 	=> 'integer',
						'key' 	=> 'dataLayerVersion',
						'value' => 2
					)
				)
			),
			'label' => array
			(
				'name' 		=> 'label',
				'type'		=> self::TAG_MANAGER_VARIABLE_TYPE_DATALAYER_VARIABLE,
				'parameter' => array
				(
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'name',
						'value' => 'eventLabel'
					),
					array
					(
						'type' 	=> 'integer',
						'key' 	=> 'dataLayerVersion',
						'value' => 2
					)
				)
			),
			'ee transaction id' => array
			(
				'name' 		=> 'ee transaction id',
				'type'		=> self::TAG_MANAGER_VARIABLE_TYPE_DATALAYER_VARIABLE,
				'parameter' => array
				(
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'name',
						'value' => 'ecommerce.purchase.actionField.id'
					),
					array
					(
						'type' 	=> 'integer',
						'key' 	=> 'dataLayerVersion',
						'value' => 2
					)
				)
			),
			'ee transaction revenue' => array
			(
				'name' 		=> 'ee transaction revenue',
				'type'		=> self::TAG_MANAGER_VARIABLE_TYPE_DATALAYER_VARIABLE,
				'parameter' => array
				(
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'name',
						'value' => 'ecommerce.purchase.actionField.revenue'
					),
					array
					(
						'type' 	=> 'integer',
						'key' 	=> 'dataLayerVersion',
						'value' => 2
					)
				)
			),
			'ee transaction products array' => array
			(
				'name' 		=> 'ee transaction products array',
				'type'		=> self::TAG_MANAGER_VARIABLE_TYPE_DATALAYER_VARIABLE,
				'parameter' => array
				(
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'name',
						'value' => 'ecommerce.purchase.products'
					),
					array
					(
						'type' 	=> 'integer',
						'key' 	=> 'dataLayerVersion',
						'value' => 2
					)
				)
			)
		);

		foreach ($schema as $variable => $parameters)
		{
			try
			{
				if (!isset($set[$variable]))
				{
					$response = $this->getService()->accounts_containers_variables->create($account, $container, new Google_Service_TagManager_Variable($parameters));

					if ($response instanceof Google_Service_TagManager_Variable && $response->variableId)
					{
						$log[] = Mage::helper('core')->__('Created variable ' . $response->name);
					}
					else
					{
						$log[] = Mage::helper('core')->__('Failed to create variable ' . $response->name);
					}
				}
			}
			catch (Exception $e)
			{
				$log[] = $e->getMessage();
			}
		}

		return $log;
	}

	/**
	 * Create container triggers
	 */
	public function ec_api_triggers($account, $container)
	{
		$log = array();
		$set = array();

		$triggers = $this->getService()->accounts_containers_triggers->listAccountsContainersTriggers($account, $container)->getTriggers();

		foreach ($triggers as $trigger)
		{
			$set[$trigger->name] = true;
		}
		/**
		 * Triggers schema
		 */
		$schema = array
		(
			self::TRIGGER_ADD_TO_CART => array
			(
				'name' 				=> self::TRIGGER_ADD_TO_CART,
				'type'				=> self::TAG_MANAGER_TRIGGER_TYPE_CUSTOM_EVENT,
				'customEventFilter' => array
				(
					array
					(
						'type' => 'equals',
						'parameter' => array
						(
							array
							(
								'type' 	=> 'template',
								'key' 	=> 'arg0',
								'value' => '{{_event}}'
							),
							array
							(
								'type' 	=> 'template',
								'key' 	=> 'arg1',
								'value' => 'addToCart'
							)
						)
					)
				)
			),
			self::TRIGGER_REMOVE_FROM_CART => array
			(
				'name' 				=> self::TRIGGER_REMOVE_FROM_CART,
				'type'				=> self::TAG_MANAGER_TRIGGER_TYPE_CUSTOM_EVENT,
				'customEventFilter' => array
				(
					array
					(
						'type' => 'equals',
						'parameter' => array
						(
							array
							(
								'type' 	=> 'template',
								'key' 	=> 'arg0',
								'value' => '{{_event}}'
							),
							array
							(
								'type' 	=> 'template',
								'key' 	=> 'arg1',
								'value' => 'removeFromCart'
							)
						)
					)
				)
			),
			self::TRIGGER_PRODUCT_CLICK => array
			(
				'name' 				=> self::TRIGGER_PRODUCT_CLICK,
				'type'				=> self::TAG_MANAGER_TRIGGER_TYPE_CUSTOM_EVENT,
				'customEventFilter' => array
				(
					array
					(
						'type' => 'equals',
						'parameter' => array
						(
							array
							(
								'type' 	=> 'template',
								'key' 	=> 'arg0',
								'value' => '{{_event}}'
							),
							array
							(
								'type' 	=> 'template',
								'key' 	=> 'arg1',
								'value' => 'productClick'
							)
						)
					)
				)
			),
			self::TRIGGER_PROMOTION_CLICK => array
			(
				'name' 				=> self::TRIGGER_PROMOTION_CLICK,
				'type'				=> self::TAG_MANAGER_TRIGGER_TYPE_CUSTOM_EVENT,
				'customEventFilter' => array
				(
					array
					(
						'type' => 'equals',
						'parameter' => array
						(
							array
							(
								'type' 	=> 'template',
								'key' 	=> 'arg0',
								'value' => '{{_event}}'
							),
							array
							(
								'type' 	=> 'template',
								'key' 	=> 'arg1',
								'value' => 'promotionClick'
							)
						)
					)
				)
			),
			self::TRIGGER_PROMOTION_VIEW => array
			(
				'name' 				=> self::TRIGGER_PROMOTION_VIEW,
				'type'				=> self::TAG_MANAGER_TRIGGER_TYPE_CUSTOM_EVENT,
				'customEventFilter' => array
				(
					array
					(
						'type' => 'equals',
						'parameter' => array
						(
							array
							(
								'type' 	=> 'template',
								'key' 	=> 'arg0',
								'value' => '{{_event}}'
							),
							array
							(
								'type' 	=> 'template',
								'key' 	=> 'arg1',
								'value' => 'promoViewNonInteractive'
							)
						)
					)
				)
			),
			self::TRIGGER_CHECKOUT => array
			(
				'name' 				=> self::TRIGGER_CHECKOUT,
				'type'				=> self::TAG_MANAGER_TRIGGER_TYPE_CUSTOM_EVENT,
				'customEventFilter' => array
				(
					array
					(
						'type' => 'equals',
						'parameter' => array
						(
							array
							(
								'type' 	=> 'template',
								'key' 	=> 'arg0',
								'value' => '{{_event}}'
							),
							array
							(
								'type' 	=> 'template',
								'key' 	=> 'arg1',
								'value' => 'checkout'
							)
						)
					)
				)
			),
			self::TRIGGER_CHECKOUT_OPTION => array
			(
				'name' 				=> self::TRIGGER_CHECKOUT_OPTION,
				'type'				=> self::TAG_MANAGER_TRIGGER_TYPE_CUSTOM_EVENT,
				'customEventFilter' => array
				(
					array
					(
						'type' => 'equals',
						'parameter' => array
						(
							array
							(
								'type' 	=> 'template',
								'key' 	=> 'arg0',
								'value' => '{{_event}}'
							),
							array
							(
								'type' 	=> 'template',
								'key' 	=> 'arg1',
								'value' => 'checkoutOption'
							)
						)
					)
				)
			),
			self::TRIGGER_SOCIAL_INTERACTION => array
			(
				'name' 				=> self::TRIGGER_SOCIAL_INTERACTION,
				'type'				=> self::TAG_MANAGER_TRIGGER_TYPE_CUSTOM_EVENT,
				'customEventFilter' => array
				(
					array
					(
						'type' => 'equals',
						'parameter' => array
						(
							array
							(
								'type' 	=> 'template',
								'key' 	=> 'arg0',
								'value' => '{{_event}}'
							),
							array
							(
								'type' 	=> 'template',
								'key' 	=> 'arg1',
								'value' => 'socialInt'
							)
						)
					)
				)
			),
			self::TRIGGER_REMARKETING_TAG => array
			(
				'name' 				=> self::TRIGGER_REMARKETING_TAG,
				'type'				=> self::TAG_MANAGER_TRIGGER_TYPE_CUSTOM_EVENT,
				'customEventFilter' => array
				(
					array
					(
						'type' => 'equals',
						'parameter' => array
						(
							array
							(
								'type' 	=> 'template',
								'key' 	=> 'arg0',
								'value' => '{{_event}}'
							),
							array
							(
								'type' 	=> 'template',
								'key' 	=> 'arg1',
								'value' => 'fireRemarketingTag'
							)
						)
					)
				)
			),
			self::TRIGGER_USER_TIMING => array
			(
				'name' 				=> self::TRIGGER_USER_TIMING,
				'type'				=> self::TAG_MANAGER_TRIGGER_TYPE_CUSTOM_EVENT,
				'customEventFilter' => array
				(
					array
					(
						'type' => 'equals',
						'parameter' => array
						(
							array
							(
								'type' 	=> 'template',
								'key' 	=> 'arg0',
								'value' => '{{_event}}'
							),
							array
							(
								'type' 	=> 'template',
								'key' 	=> 'arg1',
								'value' => 'trackTime'
							)
						)
					)
				)
			),
			self::TRIGGER_IMPRESSION => array
			(
				'name' 				=> self::TRIGGER_IMPRESSION,
				'type'				=> self::TAG_MANAGER_TRIGGER_TYPE_CUSTOM_EVENT,
				'customEventFilter' => array
				(
					array
					(
						'type' => 'equals',
						'parameter' => array
						(
							array
							(
								'type' 	=> 'template',
								'key' 	=> 'arg0',
								'value' => '{{_event}}'
							),
							array
							(
								'type' 	=> 'template',
								'key' 	=> 'arg1',
								'value' => 'impression'
							)
						)
					)
				)
			)
		);

		foreach ($schema as $trigger => $parameters)
		{
			try
			{
				if (!isset($set[$trigger]))
				{
					$response = $this->getService()->accounts_containers_triggers->create($account, $container, new Google_Service_TagManager_Trigger($parameters));

					if ($response instanceof Google_Service_TagManager_Trigger && $response->triggerId)
					{
						$log[] = Mage::helper('core')->__('Created trigger ' . $response->name);
					}
					else
					{
						$log[] = Mage::helper('core')->__('Failed to create trigger ' . $response->name);
					}
				}
			}
			catch (Exception $e)
			{
				$log[] = $e->getMessage();
			}
		}

		return $log;
	}

	/**
	 * Create tags
	 *
	 * @param string $account
	 * @param int $container
	 */
	public function ec_api_tags($account, $container)
	{
		$log = array();
		$set = array();

		$tags = $this->getService()->accounts_containers_tags->listAccountsContainersTags($account, $container)->getTags();

		foreach ($tags as $tag)
		{
			$set[$tag->name] = true;
		}

		/**
		 * Get available triggers
		 */
		$triggers = $this->getTriggersMap($account, $container);

		$schema = array
		(
			self::TAG_ADD_TO_CART => array
			(
				'name' 				=> self::TAG_ADD_TO_CART,
				'firingTriggerId' 	=> array
				(
					$triggers[self::TRIGGER_ADD_TO_CART]
				),
				'type' 				=> self::TAG_MANAGER_TAG_TYPE_UNIVERSAL_ANALYTICS,
				'parameter' 		=> array
				(
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'trackingId',
						'value' => '{{ua}}'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'trackType',
						'value' => 'TRACK_EVENT'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'eventCategory',
						'value' => 'Ecommerce'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'eventAction',
						'value' => 'Add To Cart'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'eventLabel',
						'value' => '{{label}}'
					),
					array
					(
						'type' 	=> 'boolean',
						'key' 	=> 'enableEcommerce',
						'value' => true
					),
					array
					(
						'type' 	=> 'boolean',
						'key' 	=> 'useEcommerceDataLayer',
						'value' => true
					)
				)
			),
			self::TAG_REMOVE_FROM_CART => array
			(
				'name' 				=> self::TAG_REMOVE_FROM_CART,
				'firingTriggerId' 	=> array
				(
					$triggers[self::TRIGGER_REMOVE_FROM_CART]
				),
				'type' 				=> self::TAG_MANAGER_TAG_TYPE_UNIVERSAL_ANALYTICS,
				'parameter' 		=> array
				(
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'trackingId',
						'value' => '{{ua}}'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'trackType',
						'value' => 'TRACK_EVENT'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'eventCategory',
						'value' => 'Ecommerce'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'eventAction',
						'value' => 'Remove From Cart'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'eventLabel',
						'value' => '{{label}}'
					),
					array
					(
						'type' 	=> 'boolean',
						'key' 	=> 'enableEcommerce',
						'value' => true
					),
					array
					(
						'type' 	=> 'boolean',
						'key' 	=> 'useEcommerceDataLayer',
						'value' => true
					)
				)
			),
			self::TAG_PRODUCT_CLICK => array
			(
				'name' 				=> self::TAG_PRODUCT_CLICK,
				'firingTriggerId' 	=> array
				(
					$triggers[self::TRIGGER_PRODUCT_CLICK]
				),
				'type' 				=> self::TAG_MANAGER_TAG_TYPE_UNIVERSAL_ANALYTICS,
				'parameter' 		=> array
				(
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'trackingId',
						'value' => '{{ua}}'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'trackType',
						'value' => 'TRACK_EVENT'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'eventCategory',
						'value' => 'Ecommerce'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'eventAction',
						'value' => 'Click'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'eventLabel',
						'value' => '{{label}}'
					),
					array
					(
						'type' 	=> 'boolean',
						'key' 	=> 'enableEcommerce',
						'value' => true
					),
					array
					(
						'type' 	=> 'boolean',
						'key' 	=> 'useEcommerceDataLayer',
						'value' => true
					)
				)
			),
			self::TAG_PROMOTION_CLICK => array
			(
				'name' 				=> self::TAG_PROMOTION_CLICK,
				'firingTriggerId' 	=> array
				(
					$triggers[self::TRIGGER_PROMOTION_CLICK]
				),
				'type' 				=> self::TAG_MANAGER_TAG_TYPE_UNIVERSAL_ANALYTICS,
				'parameter' 		=> array
				(
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'trackingId',
						'value' => '{{ua}}'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'trackType',
						'value' => 'TRACK_EVENT'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'eventCategory',
						'value' => 'Ecommerce'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'eventAction',
						'value' => 'Promotion Click'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'eventLabel',
						'value' => '{{label}}'
					),
					array
					(
						'type' 	=> 'boolean',
						'key' 	=> 'enableEcommerce',
						'value' => true
					),
					array
					(
						'type' 	=> 'boolean',
						'key' 	=> 'useEcommerceDataLayer',
						'value' => true
					)
				)
			),
			self::TAG_PROMOTION_VIEW => array
			(
				'name' 				=> self::TAG_PROMOTION_VIEW,
				'firingTriggerId' 	=> array
				(
					$triggers[self::TRIGGER_PROMOTION_VIEW]
				),
				'type' 				=> self::TAG_MANAGER_TAG_TYPE_UNIVERSAL_ANALYTICS,
				'parameter' 		=> array
				(
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'trackingId',
						'value' => '{{ua}}'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'trackType',
						'value' => 'TRACK_EVENT'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'eventCategory',
						'value' => 'Promotion'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'eventAction',
						'value' => 'Promotion View'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'eventLabel',
						'value' => '{{label}}'
					),
					array
					(
						'type' 	=> 'boolean',
						'key' 	=> 'enableEcommerce',
						'value' => true
					),
					array
					(
						'type' 	=> 'boolean',
						'key' 	=> 'useEcommerceDataLayer',
						'value' => true
					),
					array
					(
						'type' 	=> 'boolean',
						'key' 	=> 'nonInteraction',
						'value' => true
					)
				)
			),
			/*
			self::TAG_CHECKOUT => array
			(
				'name' 				=> self::TAG_CHECKOUT,
				'firingTriggerId' 	=> array
				(
					$triggers[self::TRIGGER_CHECKOUT]
				),
				'type' 				=> self::TAG_MANAGER_TAG_TYPE_UNIVERSAL_ANALYTICS,
				'parameter' 		=> array
				(
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'trackingId',
						'value' => '{{ua}}'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'trackType',
						'value' => 'TRACK_EVENT'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'eventCategory',
						'value' => 'Ecommerce'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'eventAction',
						'value' => 'Checkout'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'eventLabel',
						'value' => '{{label}}'
					),
					array
					(
						'type' 	=> 'boolean',
						'key' 	=> 'enableEcommerce',
						'value' => true
					),
					array
					(
						'type' 	=> 'boolean',
						'key' 	=> 'useEcommerceDataLayer',
						'value' => true
					)
				)
			),
			self::TAG_CHECKOUT_OPTION => array
			(
				'name' 				=> self::TAG_CHECKOUT_OPTION,
				'firingTriggerId' 	=> array
				(
					$triggers[self::TRIGGER_CHECKOUT_OPTION]
				),
				'type' 				=> self::TAG_MANAGER_TAG_TYPE_UNIVERSAL_ANALYTICS,
				'parameter' 		=> array
				(
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'trackingId',
						'value' => '{{ua}}'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'trackType',
						'value' => 'TRACK_EVENT'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'eventCategory',
						'value' => 'Ecommerce'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'eventAction',
						'value' => 'Checkout Option'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'eventLabel',
						'value' => '{{label}}'
					),
					array
					(
						'type' 	=> 'boolean',
						'key' 	=> 'enableEcommerce',
						'value' => true
					),
					array
					(
						'type' 	=> 'boolean',
						'key' 	=> 'useEcommerceDataLayer',
						'value' => true
					)
				)
			),
			*/
			self::TAG_SOCIAL_INTERACTION => array
			(
				'name' 				=> self::TAG_SOCIAL_INTERACTION,
				'firingTriggerId' 	=> array
				(
					$triggers[self::TRIGGER_SOCIAL_INTERACTION]
				),
				'type' 				=> self::TAG_MANAGER_TAG_TYPE_UNIVERSAL_ANALYTICS,
				'parameter' 		=> array
				(
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'trackingId',
						'value' => '{{ua}}'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'trackType',
						'value' => 'TRACK_SOCIAL'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'socialNetwork',
						'value' => '{{social network}}'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'socialAction',
						'value' => '{{social action}}'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'socialActionTarget',
						'value' => '{{social target}}'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'eventLabel',
						'value' => '{{label}}'
					)
				)
			),
			self::TAG_USER_TIMING => array
			(
				'name' 				=> self::TAG_USER_TIMING,
				'firingTriggerId' 	=> array
				(
					$triggers[self::TRIGGER_USER_TIMING]
				),
				'type' 				=> self::TAG_MANAGER_TAG_TYPE_UNIVERSAL_ANALYTICS,
				'parameter' 		=> array
				(
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'trackingId',
						'value' => '{{ua}}'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'trackType',
						'value' => 'TRACK_TIMING'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'timingCategory',
						'value' => '{{timing category}}'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'timingVar',
						'value' => '{{timing var}}'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'timingLabel',
						'value' => '{{timing label}}'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'timingValue',
						'value' => '{{timing value}}'
					)
				)
			),
			self::TAG_PRODUCT_IMPRESSION => array
			(
				'name' 				=> self::TAG_PRODUCT_IMPRESSION,
				'firingTriggerId' 	=> array
				(
					$triggers[self::TRIGGER_IMPRESSION]
				),
				'type' 				=> self::TAG_MANAGER_TAG_TYPE_UNIVERSAL_ANALYTICS,
				'parameter' 		=> array
				(
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'trackingId',
						'value' => '{{ua}}'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'trackType',
						'value' => 'TRACK_EVENT'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'eventCategory',
						'value' => 'Ecommerce'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'eventAction',
						'value' => 'Impression'
					),
					array
					(
						'type' 	=> 'template',
						'key' 	=> 'eventLabel',
						'value' => '{{label}}'
					),
					array
					(
						'type' 	=> 'boolean',
						'key' 	=> 'enableEcommerce',
						'value' => true
					),
					array
					(
						'type' 	=> 'boolean',
						'key' 	=> 'useEcommerceDataLayer',
						'value' => true
					)
				)
			)
		);
		
		foreach ($schema as $tag => $parameters)
		{
			try
			{
				if (!isset($set[$tag]))
				{
					$response = $this->getService()->accounts_containers_tags->create($account, $container, new Google_Service_TagManager_Tag($parameters));
						
					if ($response instanceof Google_Service_TagManager_Tag && $response->tagId)
					{
						$log[] = Mage::helper('core')->__('Created tag ' . $response->name);
					}
					else
					{
						$log[] = Mage::helper('core')->__('Failed to create tag ' . $response->name);
					}
				}
			}
			catch (Exception $e)
			{
				$log[] = $e->getMessage();
			}
		}
		
		return $log;
	}
	
	public function ec_api_version($account, $container)
	{
		$log = array();
		$set = array();
		
		$versions = $this->getService()->accounts_containers_versions->listAccountsContainersVersions($account, $container);
		
		return true;
	}
	
	public function getService()
	{
		if (!$this->service)
		{
			$this->service = new Google_Service_TagManager
			(
				$this->getClient()
			);
		}
		
		return $this->service;
	}
	
	public function getOauth()
	{
		if (!$this->oauth)
		{
			$this->oauth = new Google_Service_Oauth2
			(
				$this->getClient()
			);
		}
		
		return $this->oauth;
	}
	
	public function getContainers($account)
	{
		if ($this->getClient()->isAccessTokenExpired())
		{
			return array();
		}
		
		return $this->getService()->accounts_containers->listAccountsContainers($account)->getContainers();
	}
	
	public function getTriggers($account, $container)
	{
		return $this->getService()->accounts_containers_triggers->listAccountsContainersTriggers($account, $container)->getTriggers();
	}
	
	public function getTriggersMap($account, $container)
	{
		$map = array();
		
		foreach ($this->getTriggers($account, $container) as $trigger)
		{
			$map[$trigger->name] = $trigger->triggerId;
		}

		return $map;
	}
}