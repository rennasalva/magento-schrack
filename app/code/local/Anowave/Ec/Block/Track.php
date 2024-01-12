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
class Anowave_Ec_Block_Track extends Mage_Core_Block_Template
{
	/**
	 * Get product price with/without VAT depending on system configuration
	 * 
	 * @param Mage_Catalog_Model_Product $product
	 */
	
	public function getPrice(Mage_Catalog_Model_Product $product, Mage_Sales_Model_Order $order = null)
	{
		return Mage::helper('tax')->getPrice($product, $product->getFinalPrice(), true);
	}
	
	/**
	 * Get item price 
	 * 
	 * @param Mage_Sales_Model_Order_Item $item
	 * @param Mage_Sales_Model_Order $order
	 */
	public function getPriceItem(Mage_Sales_Model_Order_Item $item,  Mage_Sales_Model_Order $order = null)
	{
		if ((float) $item->getPriceInclTax() > 0)
		{
			return $item->getPriceInclTax();
		}
		else 
		{
			return $item->getPrice();
		}
	}
	
	/**
	 * Get final product price to include in details/impressions
	 * 
	 * @param Mage_Catalog_Model_Product $product
	 */
	public function getFinalPrice(Mage_Catalog_Model_Product $product)
	{
		return Mage::helper('ec/price')->getPrice($product);
	}
	
	public function getCurrency()
	{
		return Mage::app()->getStore()->getCurrentCurrencyCode();
	}
	
	/**
	 * Get order revenue with/without VAT depending on system configuration 
	 * 
	 * @param Mage_Sales_Model_Order $order
	 * @return number
	 */
	public function getRevenue(Mage_Sales_Model_Order $order)
	{
		$revenue = (float) $order->getGrandTotal();
		
		if (!Mage::getStoreConfig('ec/revenue/tax'))
		{
			$revenue -= (float) $order->getTaxAmount();
		}
		
		if (!Mage::getStoreConfig('ec/revenue/shipping'))
		{
			$revenue -= (float) $order->getShippingAmount();
		}

		return $revenue;
	}
	
	public function getDimensions(Mage_Catalog_Model_Product $product = null,  Mage_Sales_Model_Order $order = null, $customer = null)
	{
		if (!(int)@Mage::getStoreConfig('ec/definitions/dimensions'))
		{
			return array();
		}
		
		$dimensions = array();
		
		$model = Mage::getModel('ec/dimensions');
		
		foreach (range(1, 18) as $dimension)
		{
			$dimensions["dimension$dimension"] = $model->dispatch
			(
				Mage::getStoreConfig("ec/definitions/dimension$dimension"), $product, $order, $customer
			);
		}
		
		return array_filter($dimensions, array($this,'filter'));
	}
	
	/**
	 * LinkShare snippet
	 *
	 * @param Mage_Sales_Model_Order $order
	 */
	public function getLinkShare(Mage_Sales_Model_Order $order = null)
	{
		return Mage::app()->getLayout()->createBlock('ec/linkshare')->setData(array
		(
			'order' => $order
		))->toHtml();
	}
	
	/**
	 * Escape quotes in java scripts
	 *
	 * @param mixed $data
	 * @param string $quote
	 * @return mixed
	 */
	public function jsQuoteEscape($data, $quote = '\'')
	{
		return trim
		(
			$this->helper('ec')->jsQuoteEscape($data, $quote)
		);
	}
	
	/**
	 * Filter empty and non zero values 
	 * 
	 * @param mixed $value
	 * @return boolean
	 */
	public function filter($value)
	{
		return !empty($value) || $value === 0;
	}
	
	public function getCacheKeyInfo()
	{
		return array('block_id' => uniqid());
	}
	
	/**
	 * Extended HTML renderer 
	 * 
	 * @see Mage_Core_Block_Template::_toHtml()
	 */
        /*
	public function _toHtml()
	{
		return Mage::helper('ec')->filter
		(
			parent::_toHtml()
		);
	}
         * 
         */
}