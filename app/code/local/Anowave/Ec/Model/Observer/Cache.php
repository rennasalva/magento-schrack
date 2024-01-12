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
class Anowave_Ec_Model_Observer_Cache extends Anowave_Ec_Model_Observer
{
	/**
	 * Refresh cache
	 * 
	 * @param Varien_Event_Observer $observer
	 */
	public function refresh(Varien_Event_Observer $observer)
	{
		$types = Mage::app()->getRequest()->getPost('types');
		
		if (in_array('ec', $types))
		{
			Mage::helper('ec/cache')->remove();
		}
	}
}