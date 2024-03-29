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
class Anowave_Ec_Model_Observer_Checkout extends Anowave_Ec_Model_Observer
{
	/**
	 * Add to cart complete
	 * 
	 * @param Varien_Event_Observer $observer
	 */
	public function add(Varien_Event_Observer $observer)
	{
		if (null !== $category = Mage::getSingleton('core/session')->getLastCategory())
		{
			try 
			{
				Mage::helper('ec/session')->getTrace()->add($observer->getProduct(), $category);
			}
			catch (\Exception $e)
			{
				echo '<pre>'; var_dump($e->getMessage()); echo '</pre>';
				die();
			}
		}
		
		return $this;
	}
	
	/**
	 * Detect and register last category 
	 * 
	 * @param Varien_Event_Observer $observer
	 * @return Anowave_Ec_Model_Observer_Checkout
	 */
	public function register(Varien_Event_Observer $observer)
	{
		$category = $observer->getProduct()->getCategory();
		
		if ($category)
		{	
			Mage::getSingleton('core/session')->setLastCategory($category->getId());
		}
		
		return $this;
	}
}