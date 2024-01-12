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
class Anowave_Ec_Block_Experiments extends Mage_Core_Block_Template
{
	/**
	 * Global experiment data
	 * 
	 * @var (array)
	 */
	protected $experiment_data = array();
	
	/**
	 * Get A/B Experiments
	 */
	public function getExperiments()
	{
		$experiments = @Mage::helper('ec/experiments')->getExperiments();
		
		/**
		 * Get keys
		 * 
		 * @var array
		 */
		$keys = array_keys($experiments);
		
		/**
		 * Choose random experiment 
		 * 
		 * @var string
		 */
		$cookie = Mage::getModel('core/cookie')->get('experiment');
		
		if (!$cookie && $experiments)
		{
			/**
			 * Get random key 
			 * 
			 * @var int
			 */
			$key = rand(0, count($keys)-1);
			
			/**
			 * Get cookie
			 */
			$cookie = $keys[$key];

			/**
			 * Set cookie
			 */
			
			Mage::getModel('core/cookie')->set('experiment', $cookie, (3 * 24 * 3600),'/', $_SERVER['HTTP_HOST']);
		}
		
		if (isset($experiments[$cookie]))
		{
			/**
			 * Set experiment to triggered
			 */
			$experiments[$cookie]['triggered'] = function()
			{
				return 1;
			};
			
			/**
			 * Dispatch experiment event
			 */
			Mage::dispatchEvent('content_experiment', array
			(
				'experiment' => $experiments[$cookie]
			));
		}
		else 
		{
			Mage::getModel('core/cookie')->set('experiment', 0, 0, "/");
		}

		return $experiments;
	}

	/**
	 * Extended HTML renderer
	 *
	 * @see Mage_Core_Block_Template::_toHtml()
	 */
	public function _toHtml()
	{
		return Mage::helper('ec')->filter
		(
			parent::_toHtml()
		);
	}
}