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
class Anowave_Ec_Helper_Price extends Mage_Core_Helper_Data
{
	/**
	 * Get final product price 
	 * 
	 * @param Mage_Catalog_Model_Product $product
	 */
	public function getPrice(Mage_Catalog_Model_Product $product)
	{
		try 
		{
			/**
			 * Get price
			 *
			 * @var float
			 */
			if (Mage::app()->getStore()->getBaseCurrencyCode() != Mage::app()->getStore()->getCurrentCurrencyCode())
			{
				/**
				 * Convert price from base currency to current currency
				 * 
				 * @var float
				 */
				$price = Mage::helper('directory')->currencyConvert($product->getFinalPrice(), Mage::app()->getStore()->getBaseCurrencyCode(), Mage::app()->getStore()->getCurrentCurrencyCode());

				/**
				 * Get price with tax
				 * 
				 * @var float
				 */
				$price = (float) Mage::helper('tax')->getPrice($product, $price, (bool) (int) Mage::getStoreConfig('ec/prices/tax'));
			}
			else 
			{
				/**
				 * Get price with tax
				 *
				 * @var float
				 */
				$price = (float) Mage::helper('tax')->getPrice($product, $product->getFinalPrice(), (bool) (int) Mage::getStoreConfig('ec/prices/tax'));
			}

			if (!$price)
			{
				switch ($product->getTypeId())
				{
					case Mage_Catalog_Model_Product_Type::TYPE_BUNDLE:
						
						/**
						 * Get lowest price for bundle product type
						 * @var float
						 */
						
						$price = (float) Mage::getModel('bundle/product_price')->getTotalPrices($product,'min', (bool) (int) Mage::getStoreConfig('ec/prices/tax'));
							
						break;
				}
			}
		}
		catch (Exception $e)
		{
			return 0;
		}
		
		return $price;
	}
}