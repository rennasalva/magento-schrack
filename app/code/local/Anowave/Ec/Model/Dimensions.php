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
class Anowave_Ec_Model_Dimensions
{
	/**
	 * Dispatch dimension 
	 * 
	 * @param string $method
	 * @param Mage_Catalog_Model_Product $product
	 * @param Mage_Sales_Model_Order $order
	 * @param string $customer
	 * @return mixed|NULL
	 */
	public function dispatch($method = '', Mage_Catalog_Model_Product $product = null,  Mage_Sales_Model_Order $order = null, $customer = null)
	{
		if (method_exists($this, $method))
		{
			return call_user_func_array(array($this, $method), array
			(
				$product, 
				$order, 
				$customer
			));
		}
		else return null;
	}
	
	/**
	 * Get Customer Id
	 * 
	 * @param Mage_Catalog_Model_Product $product
	 * @param Mage_Sales_Model_Order $order
	 * @param Mage_Customer_Model_Customer $customer
	 * @return Ambigous <mixed, NULL, multitype:>
	 */
	public function getCustomerId(Mage_Catalog_Model_Product $product = null,  Mage_Sales_Model_Order $order = null, Mage_Customer_Model_Customer $customer = null)
	{
		if (Mage::getSingleton('customer/session')->isLoggedIn())
		{
			return $customer->getId();
		}
		
		return 0;
	}
	
	/**
	 * Get Number of Wishlist items
	 * 
	 * @param Mage_Catalog_Model_Product $product
	 * @param Mage_Sales_Model_Order $order
	 * @param Mage_Customer_Model_Customer $customer
	 */
	public function getWishlistCount(Mage_Catalog_Model_Product $product = null,  Mage_Sales_Model_Order $order = null, Mage_Customer_Model_Customer $customer = null)
	{
		if (Mage::getSingleton('customer/session')->isLoggedIn())
		{
			$collection = Mage::getModel('wishlist/wishlist')->loadByCustomer($customer, true)->getItemCollection();
			
			$items = array();
			
			foreach ($collection as $item)
			{
				$items[] = $item->getId();
			}
			
			return count($items);
		}
		
		return 0;
	}
	
	/**
	 * Get customer gender
	 * 
	 * @param Mage_Catalog_Model_Product $product
	 * @param Mage_Sales_Model_Order $order
	 * @param Mage_Customer_Model_Customer $customer
	 */
	public function getCustomerGender(Mage_Catalog_Model_Product $product = null,  Mage_Sales_Model_Order $order = null, Mage_Customer_Model_Customer $customer = null)
	{
		if (Mage::getSingleton('customer/session')->isLoggedIn())
		{
			return  Mage::getResourceSingleton('customer/customer')->getAttribute('gender')->getSource()->getOptionText($customer->getGender()); 
		}
		
		return null;
	}
	
	/**
	 * Get customer group 
	 * 
	 * @param Mage_Catalog_Model_Product $product
	 * @param Mage_Sales_Model_Order $order
	 * @param Mage_Customer_Model_Customer $customer
	 */
	public function getCustomerGroup(Mage_Catalog_Model_Product $product = null,  Mage_Sales_Model_Order $order = null, Mage_Customer_Model_Customer $customer = null)
	{
		if (Mage::getSingleton('customer/session')->isLoggedIn())
		{
			return Mage::getModel('customer/group')->load
			(
				$customer->getGroupId()
			)->getCustomerGroupCode();
		}
		
		return null;
	}
	
	/**
	 * Get number of orders by customer 
	 * 
	 * @param Mage_Catalog_Model_Product $product
	 * @param Mage_Sales_Model_Order $order
	 * @param Mage_Customer_Model_Customer $customer
	 * @return number
	 */
	public function getCustomerOrdersCount(Mage_Catalog_Model_Product $product = null,  Mage_Sales_Model_Order $order = null, Mage_Customer_Model_Customer $customer = null)
	{
		$orders = Mage::getResourceModel('sales/order_collection')->addFieldToSelect('*')->addFieldToFilter('customer_id', $customer->getId());
		
		if (!$orders->getSize())
		{
			return 0;
		}
		else 
		{
			return (int) $orders->getSize() - 1;
		}
		
		return 0;
	}
}