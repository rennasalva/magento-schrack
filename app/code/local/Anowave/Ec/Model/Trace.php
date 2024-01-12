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


class Anowave_Ec_Model_Trace
{
	/**
	 * @var ArrayAccess
	 */
	protected $products = array();
	
	/**
	 * Add product
	 */
	public function add(Mage_Catalog_Model_Product $product, $category)
	{
		/**
		 * Add products to trace 
		 */
		$this->products[$product->getId()] = (int) $category;
		
		/**
		 * Update trace
		 */
		Mage::helper('ec/session')->setTrace($this);
		
		return $this;
	}
	
	/**
	 * Get product category 
	 * 
	 * @param Mage_Catalog_Model_Product $product
	 * @return Mage_Core_Model_Abstract
	 */
	public function get(Mage_Catalog_Model_Product $product)
	{
		if (isset($this->products[$product->getId()]))
		{
			return Mage::getModel('catalog/category')->load($this->products[$product->getId()]);
		}
		else 
		{
			$categories = (array) $product->getCategoryIds();
			
			if (!$categories)
			{
				$categories[] = Mage::app()->getStore()->getRootCategoryId();
			}
			
			return Mage::getModel('catalog/category')->load(end($categories));
		}
	}
}