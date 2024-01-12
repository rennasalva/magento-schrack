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
 
class Anowave_Ec_Model_Ab extends Mage_Core_Model_Abstract
{
	protected function _construct()
	{
		$this->_init('ec/ab');
	}
	
	public function loadStores()
	{
		$stores = array();
		
		if ($this->getId())
		{
			foreach (Mage::getModel('ec/store')->getCollection()->addFieldToFilter('ab_id', $this->getId()) as $store)
			{
				$stores[] = (int) $store->getAbStoreId();
			}	
		}
		
		$this->setStoreId($stores);
		
		return $this;
	}
}
