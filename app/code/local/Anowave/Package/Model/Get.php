<?php
/**
 * Anowave Package Get
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
 * @package 	Anowave_Package
 * @copyright 	Copyright (c) 2016 Anowave (http://www.anowave.com/)
 * @license  	http://www.anowave.com/license-agreement/
 */
 
class Anowave_Package_Model_Get
{
	/**
	 * Get module to update 
	 * 
	 * @param Varien_Event_Observer $observer
	 */
	public function get(Varien_Event_Observer $observer)
	{
		/**
		 * Construct helper class name 
		 * 
		 * @var string
		 */
		$helper = Mage::getStoreConfig('package/config/get') . '_Helper_Data';
		
		if (class_exists($helper))
		{
			return $this->update
			(
				new $helper()
			);
		}
		
		return true;
	}
	
	/**
	 * Upgrade module 
	 * 
	 * @param Anowave_Package_Helper_Data $helper
	 */
	final private function update(Anowave_Package_Helper_Data $helper)
	{
		return true;
	}
}