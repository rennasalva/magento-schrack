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

class Anowave_Ec_Helper_Datalayer extends Anowave_Package_Helper_Data
{
	/**
	 * Customer registration 
	 * 
	 * @return JSON
	 */
	public function getPushEventRegistration(Mage_Customer_Model_Customer $customer)
	{
		/**
		 * Check if customer is subscriber 
		 */
		$subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail
		(
			$customer->getEmail()
		);
		
		return json_encode
		(
			array
			(
				'event' 		=> 'registration',
				'eventCategory' => __('Registration'),
				'eventAction'	=> __('Register'),
				'eventLabel' 	=> $this->jsQuoteEscape
				(
					Mage::app()->getStore()->getFrontendName()
				),
				'userId'		=> $customer->getId(),
				'subscribed'	=> ($subscriber && $subscriber->getId()) ? true : false
			)
		);
	}
	
	/**
	 * Impressions push JSON
	 * 
	 * @return JSON
	 */
	public function getPushImpressions()
	{
		$block = Mage::app()->getLayout()->getBlock('product_list');
			
		if ($block)
		{
			if(Mage::registry('current_category'))
			{
				$category = Mage::registry('current_category');
			}
			else
			{
				$in = array();
			
				if (!$in)
				{
					$in[] = Mage::app()->getStore()->getRootCategoryId();
				}
			
				$category = Mage::getModel('catalog/category')->load
				(
					end($in)
				);
			}
			
			/**
			 * DataLayer push
			 *
			 * @var array
			 */
			$data = array
			(
				'ecommerce' => array
				(
					'currencyCode'  => Mage::app()->getStore()->getCurrentCurrencyCode(),
					'impressions' 	=> array()
				)
			);
			
			$position = 1;
			
			foreach ($this->getLoadedProductCollection($block) as $product)
			{
				$data['ecommerce']['impressions'][] = array
				(
					'list' 		=> Mage::helper('ec')->getCategoryList($category),
					'id' 		=> $product->getSku(),
					'name' 		=> $product->getName(),
					//'price' 	=> Mage::helper('ec/price')->getPrice($product),
					'brand'		=> Mage::helper('ec')->getBrand($product),
					'category' 	=> Mage::helper('ec')->getCategory($category),
					'position' 	=> $position++
				);
			}
			

			return (object) array
			(
				'data' 				=> json_encode($data),
				'google_tag_params' => array
				(
					'ecomm_category' => $this->jsQuoteEscape(Mage::helper('ec')->getCategoryList($category))
				)
			);
		}
		
		return false;
	}
	
	/**
	 * Get push details & AdWords Dynamic remarketing data
	 * 
	 * @return array
	 */
	public function getPushDetail()
	{
		$block = Mage::app()->getLayout()->getBlock('product.info');
		
		if ($block)
		{
			if(Mage::registry('current_category'))
			{
				$category = Mage::registry('current_category');
			}
			else
			{
				$in = array();
				
				if (!$in)
				{
					$in[] = Mage::app()->getStore()->getRootCategoryId();
				}
			
				$category = Mage::getModel('catalog/category')->load
				(
					end($in)
				);
			}
			
			$ecomm = array
			(
				'i' => array(),
				'p' => array(),
				'v' => array()
			);
			
			/**
			 * Grouped products collection
			 * 
			 * @var ArrayAccess
			 */
			$grouped = array();
			
			/* Check if product is configurable */
			if ('grouped' == $block->getProduct()->getTypeId())
			{
				foreach ($block->getProduct()->getTypeInstance(true)->getAssociatedProducts($block->getProduct()) as $product)
				{
					$child = $product;
					
					/**
					 * Set category
					 */
					$child->setCategory($category);
					
					$grouped[] = $child;
				}
			}
			
			$data = array
			(
				'ecommerce' => array
				(
					'currencyCode' => Mage::app()->getStore()->getCurrentCurrencyCode(),
					'detail' => array
					(
						'actionField' => array
						(
							'list' => Mage::helper('ec')->getCategoryList($category)
						),
						'products' => array()
					)
				)
			);

			$products = array();

			if (!$grouped)
			{
				/**
				 * Push produuct
				 */
				$products[] = array
				(
					'name' 		=> $block->getProduct()->getName(),
					'id' 		=> $block->getProduct()->getSku(),
					'brand' 	=> Mage::helper('ec')->getBrand($block->getProduct()),
					'category' 	=> $block->getProduct()->getCategoryId4googleTagManager(),
					'price' 	=> Mage::helper('ec/price')->getPrice($block->getProduct())
				);
				
	
				$ecomm['i'][] = $this->getAdwordsRemarketingId($block->getProduct());
				$ecomm['p'][] = $this->jsQuoteEscape($block->getProduct()->getName());
				$ecomm['v'][] = Mage::helper('ec/price')->getPrice
				(
					$block->getProduct()
				);
			}
			else 
			{
				/**
				 * Push grouped products
				 */
				foreach ($grouped as $entity)
				{
					$products[] = array
					(
						'name' 		=> $entity->getName(),
						'id' 		=> $entity->getSku(),
						'brand' 	=> Mage::helper('ec')->getBrand($entity),
						'category' 	=> 'testio200000',
						//'price' 	=> Mage::helper('ec/price')->getPrice($entity)
					);
					
					$ecomm['i'][] = $this->getAdwordsRemarketingId($entity);
					$ecomm['p'][] = $this->jsQuoteEscape($entity->getName());
					//$ecomm['v'][] = Mage::helper('ec/price')->getPrice($entity);
				}
			}
			
			$data['ecommerce']['detail']['products'] = $products;
			
			/**
			 * Combine detail & impressions (related products, up-sell, cross-sell)
			 */
			
			if ($combine = $this->getUpSells())
			{
				foreach ($combine as $item)
				{
					$data['ecommerce']['impressions'][] = $item;
				}
			}
			
			/**
			 * Combine recently viewed products
			 */
			if ($combine = $this->getRecentlyViewed())
			{
				foreach ($combine as $item)
				{
					$data['ecommerce']['impressions'][] = $item;
				}
			}

			return (object) array
			(
				'data' 				=> json_encode($data),
				'grouped'			=> $grouped,
				'google_tag_params' => array
				(
					'ecomm_pagetype' 	=> 'product',
					'ecomm_prodid' 		=> json_encode($ecomm['i']),
					'ecomm_pname'		=> json_encode($ecomm['p']),
					'ecomm_pvalue'		=> json_encode($ecomm['v']),
					//'ecomm_totalvalue'	=> Mage::helper('ec/price')->getPrice
					(
						$block->getProduct()
					),
					'ecomm_category' => $this->jsQuoteEscape(Mage::helper('ec')->getCategoryList($category))
				),
				'fbq' => json_encode
				(
					array
					(
						'content_type' 		=> 'product',
						'content_name' 		=> $this->jsQuoteEscape($block->getProduct()->getName()),
						'content_category' 	=> $this->jsQuoteEscape(Mage::helper('ec')->getCategoryList($category)),
						'content_ids' 		=> array
						(
							$this->jsQuoteEscape($block->getProduct()->getSku())
						),
						'currency' 			=> Mage::app()->getStore()->getCurrentCurrencyCode(),
						'value' 			=> Mage::helper('ec/price')->getPrice($block->getProduct())
					)
				)
			);
		}
		
		return false;
	}
	
	public function getPushSearch()
	{
		$block = Mage::app()->getLayout()->getBlock('search_result_list');
		
		if ($block)
		{
			/**
			 * DataLayer push
			 *
			 * @var array
			 */
			$data = array
			(
				'ecommerce' => array
				(
					'currencyCode'  => Mage::app()->getStore()->getCurrentCurrencyCode(),
					'actionField' => array
					(
						'list' => __('Search Results')
					),
					'impressions' 	=> array()
				)
			);
			
			$position = 1;
			

			foreach ($this->getLoadedProductCollection($block) as $product)
			{
				$in = $product->getCategoryIds();
				
				if (!$in)
				{
					$in[] = Mage::app()->getStore()->getRootCategoryId();
				}
				
				$category = Mage::getModel('catalog/category')->load
				(
					end($in)
				);
				
				$data['ecommerce']['impressions'][] = array
				(
					'list' 		=> Mage::helper('ec')->getCategoryList($category),
					'id' 		=> $product->getSku(),
					'name' 		=> $product->getName(),
					//'price' 	=> $product->getFinalPrice(),
					'brand'		=> Mage::helper('ec')->getBrand($product),
					'category' 	=> Mage::helper('ec')->getCategory($category),
					'position' 	=> $position++
				);
			}
			
			if (!isset($category))
			{
				$category = null;
			}
			
			return (object) array
			(
				'data' 				=> json_encode($data),
				'google_tag_params' => array('ecomm_category' => $this->jsQuoteEscape(Mage::helper('ec')->getCategoryList($category)))
			);
		}
		
		return false;
	}
	
	/**
	 * Get recently viewed products
	 */
	public function getRecentlyViewed()
	{
		$impressions = array();
		
		foreach (array
		(
			Mage::app()->getLayout()->getBlock('left.reports.product.viewed'), Mage::app()->getLayout()->getBlock('right.reports.product.viewed')
			
		) as $block)
		{
			if ($block)
			{
				$position = 1;
					
				foreach ($block->getItemsCollection() as $product)
				{
					$categories = (array) $product->getCategoryIds();
			
					if (!$categories)
					{
						$categories[] = Mage::app()->getStore()->getRootCategoryId();
					}
			
					$category = Mage::getModel('catalog/category')->load
					(
						end($categories)
					);
			
					$impressions[] = array
					(
						'list' 		=> __('Recently Viewed'),
						'id' 		=> $product->getSku(),
						'name' 		=> $product->getName(),
						'price' 	=> $product->getFinalPrice(),
						'brand'		=> Mage::helper('ec')->getBrand($product),
						'category' 	=> Mage::helper('ec')->getCategory($category),
						'position' 	=> $position++
					);
				}
			}
		}
		
		return $impressions;
	}

	public function getUpSells()
	{
		$block = Mage::app()->getLayout()->getBlock('product.info.upsell');
		
		if ($block && $block->getProduct())
		{
			$impressions = array();
			
			$position = 1;
			
			$collection = $block->getProduct()->getUpSellProductCollection()->setPositionOrder()->addStoreFilter()->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes());
			
			foreach ($collection as $product)
			{
				$categories = (array) $product->getCategoryIds();
				
				if (!$categories)
				{
					$categories[] = Mage::app()->getStore()->getRootCategoryId();
				}
				
				$category = Mage::getModel('catalog/category')->load
				(
					end($categories)
				);
				
				$impressions[] = array
				(
					'list' 		=> __('Related Products'),
					'id' 		=> $product->getSku(),
					'name' 		=> $product->getName(),
					'price' 	=> $product->getFinalPrice(),
					'brand'		=> Mage::helper('ec')->getBrand($product),
					'category' 	=> Mage::helper('ec')->getCategory($category),
					'position' 	=> $position++
				);
			}

			return $impressions;
		}
		
		return array();
	}
	
	/**
	 * Get current loaded collection
	 * 
	 * @param Mage_Catalog_Block_Product_List $block
	 */
	public function getLoadedProductCollection(Mage_Catalog_Block_Product_List $block = null)
	{
		if (!$block)
		{
			$block = Mage::app()->getLayout()->getBlock('product_list');
		}
		
		if ($block)
		{
			$collection = $block->getLoadedProductCollection();
			
			if ($collection)
			{
				/**
				 * Simulate _beforeToHtml()
				 */
				$toolbar = $block->getToolbarBlock();
				
				if ($toolbar)
				{	
					if ($orders = $block->getAvailableOrders()) 
					{
						$toolbar->setAvailableOrders($orders);
					}
					if ($sort = $block->getSortBy()) 
					{
						$toolbar->setDefaultOrder($sort);
					}
					
					if ($dir = $block->getDefaultDirection()) 
					{
						$toolbar->setDefaultDirection($dir);
					}
					
					if ($modes = $block->getModes()) 
					{
						$toolbar->setModes($modes);
					}
					
					if ('all' == $limit = $toolbar->getLimit())
					{
						$limit = 0;
					}
					
					$collection->setCurPage($toolbar->getCurrentPage())->setPageSize($limit)->setOrder($toolbar->getCurrentOrder(), $toolbar->getCurrentDirection());
						
					return $collection;
				}
			}
		}
		
		return array();		
	}
	
	/**
	 * Retrieve ecomm_prodid attribute 
	 * 
	 * @param Mage_Catalog_Model_Product $product
	 */
	public function getAdWordsRemarketingId(Mage_Catalog_Model_Product $product)
	{
		$attribute = Mage::getStoreConfig('ec/dynamic_remarketing/attribute');
		
		/**
		 * @todo: Implement logic to pull attributes that are not included in collections by default
		 */
		if (false)
		{
			$product = Mage::getModel('catalog/product')->load($product->getId());
		}
		
		if ('' !== $attribute)
		{
			if ('id' == $attribute)
			{
				return $product->getId();
			}
			else 
			{
				$value = $product->getData($attribute);
				
				if (is_string($value))
				{
					return $this->jsQuoteEscape($value);
				}
			}
		}
		
		return $this->jsQuoteEscape
		(
			$product->getSku()
		);
	}
	
	/**
	 * Escape string for JSON 
	 * 
	 * @see Mage_Core_Helper_Abstract::jsQuoteEscape()
	 */
	public function jsQuoteEscape($data, $quote='\'')
	{
		return trim
		(
			Mage::helper('ec')->jsQuoteEscape($data)
		);
	}
}