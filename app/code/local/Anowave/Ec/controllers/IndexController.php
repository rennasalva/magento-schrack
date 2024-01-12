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
 * @copyright 	Copyright (c) 2015 Anowave (http://www.anowave.com/)
 * @license  	http://www.anowave.com/license-agreement/
 */

class Anowave_Ec_IndexController extends Mage_Core_Controller_Front_Action
{
	/**
	 * Server Side Push
	 *
	 * @todo: To be introduced in next releases
	 */
	public function pushAction()
	{
		/**
		 * Get Client ID
		 *
		 * @var string
		 */
		$cid = Mage::getSingleton('core/session')->getCID();

		if (!$cid)
		{
			/**
			 * Generate Client ID
			 *
			 * @var string
			 */
			$cid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',mt_rand(0, 0xffff), mt_rand(0, 0xffff),mt_rand(0, 0xffff),mt_rand(0, 0x0fff) | 0x4000,mt_rand(0, 0x3fff) | 0x8000,mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));

			/**
			 * Store Client ID in session
			 */
			Mage::getSingleton('core/session')->setCID($cid);
		}

		/**
		 * Get Analytics ID
		 *
		 * @var string
		 */
		$ua = Mage::getStoreConfig('ec/config/refund');

		if ('' === $ua)
		{
			exit();
		}

		$dp = ltrim(str_replace(array('http://', 'https://', $_SERVER['HTTP_HOST']), '', $_SERVER['HTTP_REFERER']),'/');

		/**
		 * Define default payload
		 *
		 * @var Closure
		 */
		$default = array
		(
			'v' 	=> 1,
			'tid' 	=> $ua,
			'cid' 	=> $cid,
			't'		=> 'pageview',
			'dp'	=> "/$dp",
			'dh'	=> $_SERVER['HTTP_HOST'],
			'ua'	=> $_SERVER['HTTP_USER_AGENT']
		);


		$payload = function() use ($default)
		{
			return $default;
		};

		/**
		 * Check for enhanced ecommerce data
		 */
		if (isset($_POST['data']) && isset($_POST['data'][0]['ecommerce']))
		{
			/**
			 * Get ecommerce data
			 *
			 * @var int
			 */
			$ecommerce = $_POST['data'][0]['ecommerce'];

			/**
			 * Measure details
			 */
			if (isset($ecommerce['detail']))
			{
				$payload = function() use ($default, $ecommerce)
				{
					$default['t'] 	= 'event';
					$default['ec'] 	= 'Ecommerce';
					$default['ea'] 	= 'Detail';
					$default['pa']	= 'detail';
					$default['ni']  = 1;

					$index = 1;

					foreach ($ecommerce['detail']['products'] as $product)
					{
						$default["pr{$index}id"] = $product['id'];
						$default["pr{$index}nm"] = $product['name'];
						$default["pr{$index}ca"] = $product['category'];
						$default["pr{$index}pr"] = $product['price'];
						$default["pr{$index}br"] = $product['brand'];

						$index++;
					}

					return $default;
				};
			}
			/**
			 * Measure impressions
			 */
			elseif (isset($ecommerce['impressions']))
			{
				$payload = function() use ($default, $ecommerce)
				{
					$index = 1;

					foreach ($ecommerce['impressions'] as $product)
					{
						$default["il1nm"] 				= $product['list'];
						$default["il1pi{$index}nm"] 	= $product['name'];
						$default["il1pi{$index}id"] 	= $product['id'];
						$default["il1pi{$index}ca"] 	= $product['category'];
						$default["il1pi{$index}pr"] 	= $product['price'];
						$default["il1pi{$index}br"] 	= $product['brand'];

						$index++;
					}

					return $default;
				};
			}
			/**
			 * Track transactions
			 */
			elseif (isset($ecommerce['purchase']))
			{
				$payload = function() use ($default, $ecommerce)
				{
					$default['pa']	= 'purchase';
					$default['ni']  = 1;
					$default['ti']	= $ecommerce['purchase']['actionField']['id'];
					$default['tr']	= $ecommerce['purchase']['actionField']['revenue'];
					$default['ts']	= $ecommerce['purchase']['actionField']['shipping'];
					$default['tt']	= $ecommerce['purchase']['actionField']['tax'];
					$default['ta']	= $ecommerce['purchase']['actionField']['affiliation'];

					$index = 1;

					foreach ($ecommerce['purchase']['products'] as $product)
					{
						$default["pr{$index}id"] = $product['id'];
						$default["pr{$index}nm"] = $product['name'];
						$default["pr{$index}ca"] = $product['category'];
						$default["pr{$index}pr"] = $product['price'];
						$default["pr{$index}br"] = $product['brand'];

						$index++;
					}

					return $default;
				};
			}
		}


		$analytics = curl_init('https://ssl.google-analytics.com/collect');

		curl_setopt($analytics, CURLOPT_HEADER, 		0);
		curl_setopt($analytics, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($analytics, CURLOPT_POST, 			1);
		curl_setopt($analytics, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($analytics, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($analytics, CURLOPT_USERAGENT,		'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');

		/**
		 * Get Universal Analytics ID
		 *
		 * @var string
		 */
		$ua = Mage::getStoreConfig('ec/config/refund');

		if ($ua)
		{
			$data = $payload();

			curl_setopt($analytics, CURLOPT_POSTFIELDS, utf8_encode
			(
				http_build_query($data)
			));
		}

		try
		{
			$response = curl_exec($analytics);

			if (!curl_error($analytics) && $response)
			{
				echo $response;
				exit();
			}
		}
		catch (Exception $e)
		{

		}

		exit();
	}

	/**
	 * Get checkout options
	 */
	public function optionsAction()
	{
		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody
		(
			json_encode(array
			(
				'shipping' 			=> $this->getQuote()->getShippingAddress()->getData(),
				'shippingMethod' 	=> $this->getShippingMethod(),
				'billing' 			=> $this->getQuote()->getBillingAddress()->getData(),
				'paymentMethod'		=> $this->getPaymentMethod(),
				'checkoutMethod'	=> $this->getQuote()->getCheckoutMethod()
			))
		);
	}

	/**
	 * Get quote
	 */
	private function getQuote()
	{
		return Mage::getSingleton('checkout/session')->getQuote();
	}
	
	private function getShippingMethod()
	{
		try 
		{
			if (null !== $this->getQuote()->getShippingAddress()->getShippingMethod())
			{
				foreach(Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getShippingRatesCollection() as $rate)
				{
					if ($this->getQuote()->getShippingAddress()->getShippingMethod() == $rate->getCode())
					{
						return $rate->getCarrierTitle();
					}
				}
			}
		}
		catch (Exception $e)
		{
			
		}
		
		return null;
	}
	
	private function getPaymentMethod()
	{
		try 
		{
			return Mage::getSingleton('checkout/session')->getQuote()->getPayment()->getMethodInstance()->getTitle();
		}
		catch (Exception $e)
		{
			return null;
		}
	}
}