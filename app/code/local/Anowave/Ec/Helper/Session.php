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
class Anowave_Ec_Helper_Session extends Mage_Core_Helper_Data
{
	/**
	 * Get trace 
	 * 
	 * @return Ambigous <string, Varien_Object, mixed>
	 */
	public function getTrace()
	{
		$trace = (string) Mage::getSingleton('core/session')->getTrace();
		
		if ($trace)
		{
			$trace = unserialize($trace);
		}
		else 
		{
			$trace = Mage::getModel('ec/trace');
		}
		
		return $trace;
	}
	
	/**
	 * Set trace 
	 * 
	 * @param Varien_Object $trace
	 * @return Anowave_Ec_Helper_Session
	 */
	public function setTrace(\Anowave_Ec_Model_Trace $trace)
	{
		Mage::getSingleton('core/session')->setTrace(serialize($trace));
		
		return $this;
	}
}