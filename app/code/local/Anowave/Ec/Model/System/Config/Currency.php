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
class Anowave_Ec_Model_System_Config_Currency
{
	/**
	 * Get options
	 * 
	 * @return array
	 */
	public function toOptionArray()
	{
		$currencies = array();
		
		$codes = Mage::app()->getStore()->getAvailableCurrencyCodes(true);
		
		foreach ($codes as $currency)
		{
			$currencies[] = array
			(
				'value' => $currency,
				'label' => $currency
			);
		}

		return $currencies;
	}
	
	/**
	 * Get current selected store view
	 * 
	 * @return int
	 */
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
}