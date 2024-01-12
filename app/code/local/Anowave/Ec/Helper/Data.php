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

class Anowave_Ec_Helper_Data extends Anowave_Package_Helper_Data
{
	const DEFAULT_CUSTOM_OPTION_FIELD = 'sku';
	
	/**
	 * Package Stock Keeping Unit
	 * 
	 * @var string
	 */
	protected $package = 'MAGE-GTM';
	
	/**
	 * License config key 
	 * 
	 * @var string
	 */
	protected $config = 'ec/config/license';
	
	/**
	 * Orders
	 * 
	 * @var mixed
	 */
	protected $orders = null;
	
	/**
	 * Check if Facebook Pixel Tracking is enabled
	 * 
	 * @return boolean
	 */
	public function facebook()
	{
		return (bool) Mage::getStoreConfig('ec/facebook/enable');
	}
	
	/**
	 * Get visitor
	 * 
	 * @return number
	 */
	public function getVisitorId()
	{
		if (Mage::getSingleton("customer/session")->isLoggedIn()) {
			return (int) Mage::getSingleton("customer/session")->getCustomerId();
		}
		
		return 0;
	}

    /**
     * Get S4Y ID
     *
     * @return string
     */
    public function getCrmId()
    {
        if (Mage::getSingleton("customer/session")->isLoggedIn()) {
            return Mage::getSingleton('customer/session')->getCustomer()->getSchrackS4yId();
        } else {
            return 0;
        }
    }


    /**
     * Get WWS ID
     *
     * @return string
     */
    public function getWwsId()
    {
        if (Mage::getSingleton("customer/session")->isLoggedIn()) {
            $wwsId = Mage::getSingleton('customer/session')->getCustomer()->getSchrackWwsCustomerId();
            if (!$wwsId) {
                $wwsId = 0;
            }

            return $wwsId;

        } else {
            return 0;
        }
    }


    /**
     * Get WWS ID
     *
     * @return string
     */
    public function getCrmAccountId()
    {
        $accountCrmId = '';

        if (Mage::getSingleton("customer/session")->isLoggedIn()) {
            $wwsId = Mage::getSingleton('customer/session')->getCustomer()->getSchrackWwsCustomerId();
            if (!$wwsId) {
                $accountCrmId = 0;
            } else {
                $resource       = Mage::getSingleton('core/resource');
                $readConnection = $resource->getConnection('core_read');

                $queryFindAccountData = "SELECT schrack_s4y_id FROM account WHERE wws_customer_id LIKE '" . $wwsId . "' ";
                $result = $readConnection->fetchAll($queryFindAccountData);

                if (is_array($result) && !empty($result)) {
                    foreach ($result as $index => $recordset) {
                        $accountCrmId = $recordset['schrack_s4y_id'];
                    }
                }
            }

            return $accountCrmId;

        } else {
            return 0;
        }
    }


    /**
     * Get Customer Type
     *
     * @return string
     */
    public function getSchrackCustomerType($currentURLSection = '') {
        $customerTypeGTM = 'normalCustomerType'; // = default

        if (Mage::getSingleton("customer/session")->isLoggedIn()) {
            // If customer is logged in, must be light-prospect, full-prospect or normal-customer (= default) :
            $customerTypeFromCustomer = Mage::getSingleton('customer/session')->getCustomer()->getSchrackCustomerType();
            if($customerTypeFromCustomer == 'light-prospect') {
                $customerTypeGTM = 'lightProspectCustomerType';
            }
            if($customerTypeFromCustomer == 'full-prospect') {
                $customerTypeGTM = 'fullProspectCustomerType';
            }
        } else {
            // If customer is NOT logged in, and inside checkout, try to find out, if customer is guest or new prospect:
            if ($currentURLSection != '' && $currentURLSection == 'checkout') {
                $customerTypeFromQuote = Mage::getSingleton('checkout/session')->getQuote()->getSchrackCustomerType();
                if($customerTypeFromQuote == 'newProspect') {
                    $customerTypeGTM = 'newProspectCustomerType';
                }
                if($customerTypeFromQuote == 'guest') {
                    $customerTypeGTM = 'guestCustomerType';
                }
            } else {
                // Customer is not logged in, and not in checkout, so it is some unknown user with completely unknown customertype-status:
                $customerTypeGTM = 'guestCustomerType';
            }
        }

        return $customerTypeGTM;
    }


	/**
	 * Check if module is active
	 */
	public function isActive()
	{
		return $this->filter((int) Mage::getStoreConfig('ec/config/active'));
	}
	
	/**
	 * Get visitor login state 
	 * 
	 * @return string
	 */
	public function getVisitorLoginState()
	{
		return Mage::getSingleton("customer/session")->isLoggedIn() ? 'Logged in':'Logged out';
	}
	
	/**
	 * Get visitor type
	 * 
	 * @return string
	 */
	public function getVisitorType()
	{
		return (string) Mage::getModel('customer/group')->load(Mage::getSingleton("customer/session")->getCustomerGroupId())->getCode();
	}
	
	/**
	 * Get visitor lifetime value
	 * 
	 * @return float
	 */
	public function getVisitorLifetimeValue()
	{
		$value = 0;
		
		foreach ($this->getOrders() as $order) 
		{
			$value += $order->getGrandTotal();
		}
		
		if (Mage::getSingleton("customer/session")->isLoggedIn()) 
		{
			return round($value,2);
		} 
		
		return 0;
	}
	
	/**
	 * Retrieve visitor's avarage purchase amount
	 * 
	 * @return float
	 */
	public function getVisitorAvgTransValue()
	{
		$value = 0;
		$count = 0;
	
		foreach ($this->getOrders() as $order)
		{
			$value += $order->getGrandTotal();
				
			$count++;
		}
		
		if ($value && $count)
		{
			return round($value/$count,2);
		}
		
		return 0;
	}
	
	/**
	 * Get visitor existing customer
	 * 
	 * @return string
	 */
	public function getVisitorExistingCustomer()
	{
		return $this->getVisitorLifetimeValue() ? 'Yes' : 'No';
	}
	
	/**
	 * Get standard custom dimensions
	 * 
	 * @param void
	 * @return string JSON
	 */
	public function getCustomDimensions()
	{
		$dimensions = array
		(
			'pageType' => $this->getPageType()
		);
		
		/**
		 * Array of callbacks adding dimensions
		 */
		foreach (array
		(
			function ($dimensions)
			{
				$dimensions['pageName'] = Mage::helper('ec')->getSanitized
				(
					Mage::app()->getLayout()->getBlock('head')->getTitle()
				);
				
				return $dimensions;
			},
			function ($dimensions)
			{
				if(Mage::app()->getRequest()->getControllerName() == 'result' || Mage::app()->getRequest()->getControllerName() == 'advanced')
				{
					if (Mage::app()->getLayout()->getBlock('search_result_list'))
					{
						$dimensions['resultsCount'] = Mage::app()->getLayout()->getBlock('search_result_list')->getLoadedProductCollection()->getSize();
					}
				}
				
				return $dimensions;
			},
			function ($dimensions)
			{
				/**
				 * Check if category page
				 */
				if('catalog' == Mage::app()->getRequest()->getModuleName() && 'category' == Mage::app()->getRequest()->getControllerName())
				{
					/**
					 * Get applied layer filter(s)
					 */
					$filters = array();
					
					foreach ((array) Mage::getSingleton('catalog/layer')->getState()->getFilters() as $filter)
					{
						$filters[] = array
						(
							'label' => Mage::helper('ec')->getSanitized($filter->getName()),
							'value' => Mage::helper('ec')->getSanitized($filter->getLabel())
						);
					}
					
					$dimensions['filters'] = $filters;
					
					/**
					 * Count visible products
					 */
					if (Mage::app()->getLayout()->getBlock('product_list') && $filters)
					{
						$dimensions['resultsCount'] = Mage::helper('ec/datalayer')->getLoadedProductCollection()->getSize();
					}
				}
				
				return $dimensions;	
			}, 
			function ($dimensions)
			{
				if (Mage::getSingleton("customer/session")->isLoggedIn())
				{
					$dimensions['avgTransVal'] = Mage::helper('ec')->getVisitorAvgTransValue();
				}
				
				return $dimensions;
			}
		) as $dimension)
		{
			$dimensions = (array) call_user_func($dimension, $dimensions);
		}

		return json_encode($dimensions);
	}
	
	/**
	 * Prevent XSS attacks 
	 * 
	 * @param string $content
	 */
	public function getSanitized($content)
	{
		return strip_tags($content);
	}

	/**
	 * Determine page type
	 * 
	 * @return string
	 */
	public function getPageType()
	{
		if (Mage::getBlockSingleton('page/html_header')->getIsHomePage())
		{
			return 'home';
		}
		else if('catalog' == Mage::app()->getRequest()->getModuleName() && 'category' == Mage::app()->getRequest()->getControllerName())
		{
			return 'category';
		}
		else if ('catalog' == Mage::app()->getRequest()->getModuleName() && 'product' == Mage::app()->getRequest()->getControllerName())
		{
			return 'product';
		}
		else if('checkout' == Mage::app()->getRequest()->getModuleName() && 'cart' == Mage::app()->getRequest()->getControllerName() && 'index' == Mage::app()->getRequest()->getActionName())
		{
			return 'cart';
		}
		else if('checkout' == Mage::app()->getRequest()->getModuleName() && 'onepage' == Mage::app()->getRequest()->getControllerName() && 'index' == Mage::app()->getRequest()->getActionName())
		{
			return 'checkout';
		}
		else if(Mage::app()->getRequest()->getControllerName() == 'result' || Mage::app()->getRequest()->getControllerName() == 'advanced')
		{
			return 'searchresults';
		}
		else 
		{
			return 'other';
		}
	}
	
	/**
	 * Load customer orders
	 */
	protected function getOrders()
	{
		if (!$this->orders)
		{
			$this->orders = Mage::getResourceModel('sales/order_collection')->addFieldToSelect('*')->addFieldToFilter('customer_id',Mage::getSingleton("customer/session")->getId());
		}

		return $this->orders;
	}
	
	/**
	 * Check if GTM snippet is located after <body> opening tag
	 * 
	 * @return boolean
	 */
	public function isAfterBody()
	{
		return true;
	}
	
	/**
	 * Check if GTM install snippet is located before </body> closing tag
	 * 
	 * @return boolean
	 */
	public function isBeforeBodyClose()
	{
		return false;
	}
	
	/**
	 * Check if GTM install snippet is located inside <head></head> tag
	 *
	 * @return boolean
	 */
	public function isInsideHead()
	{
		return Anowave_Ec_Model_System_Config_Position::GTM_LOCATION_HEAD == (int) Mage::getStoreConfig('ec/config/code_position');
	}
	
	/**
	 * Escape string for JSON 
	 * 
	 * @see Mage_Core_Helper_Abstract::jsQuoteEscape()
	 */
	public function jsQuoteEscape($data, $quote='\'')
	{
		return Mage::helper('core')->jsQuoteEscape($data);
	}
	
	/**
	 * Escape quotes used in attribute(s) 
	 * 
	 * @param unknown $data
	 */
	public function jsQuoteEscapeDataAttribute($data)
	{
		return str_replace(array(chr(34), chr(39)),array('&quot;','&apos;'),$data);
	}
	
	/**
	 * Prepare GTM install snippet for <head> insertion
	 * 
	 * @return string
	 */
	public function getHeadSnippet()
	{
        // PATCH, because original code is not working as expected:
		if (intval(Mage::getStoreConfig('ec/config/active'), 10) == 1) {
//		    return Mage::getStoreConfig('ec/config/code_head');
        } else {
            return '';
        }
	}
	
	public function getBodySnippet()
	{
        // PATCH, because original code is not working as expected:
        if (intval(Mage::getStoreConfig('ec/config/active'), 10) == 1) {
//		    return Mage::getStoreConfig('ec/config/code_body');
        } else {
            return '';
        }
    }
	
	/**
	 * Get list name
	 * 
	 * @param Mage_Catalog_Model_Category $category
	 */
	public function getCategoryList(Mage_Catalog_Model_Category $category = null)
	{
		if(Mage::app()->getRequest()->getControllerName() == 'result' || Mage::app()->getRequest()->getControllerName() == 'advanced')
		{
			return __('Search Results');
		}
		
		if ($category)
		{
			return $category->getName();
		}
		
		return __('');
	}
	
	/**
	 * Get category name
	 * 
	 * @param Mage_Catalog_Model_Category $category
	 */
	public function getCategory(Mage_Catalog_Model_Category $category)
	{
		if (Mage::getStoreConfig('ec/preferences/use_category_segments'))
		{
			return $this->getCategorySegments($category);
		}
		else 
		{
			return trim
			(
				$category->getName()
			);
		}
	}
	
	/**
	 * Retrieve category and it's parents separated by chr(47)
	 * 
	 * @param Mage_Catalog_Model_Category $category
	 * @return string
	 */
	public function getCategorySegments(Mage_Catalog_Model_Category $category)
	{
		$segments = array();
		
		foreach ($category->getParentCategories() as $parent) 
		{
		    $segments[] = $parent->getName();
		}
		
		if (!$segments)
		{
			$segments[] = $category->getName();
		}
		
		return trim(join(chr(47), $segments));
	}
	
	/**
	 * Get product manufacturer
	 * 
	 * @param Mage_Catalog_Model_Product $product
	 */
	public function getBrand(Mage_Catalog_Model_Product $product)
	{
		$attributes = array
		(
			'manufacturer','brand'
		);
		
		foreach (array('manufacturer','brand') as $code)
		{
			$attribute = Mage::getResourceModel('catalog/eav_attribute')->loadByCode(\Mage_Catalog_Model_Product::ENTITY,$code);
			
			if ($attribute->getId() && $attribute->usesSource())
			{
				return (string) $product->getAttributeText($code);
			}
		}
		
		return '';
	}
	
	/**
	 * Load product by SKU and get its brand.
	 * 
	 * @param string $sku
	 */
	public function getBrandBySku($identifier)
	{
		if (strlen($identifier))
		{
			$product = Mage::getModel('catalog/product')->loadByAttribute('sku', $identifier);
			
			if ($product && $product instanceof Mage_Catalog_Model_Product)
			{
				return $product->getAttributeText('manufacturer');
			}
		}
		
		return '';
	}
	
	/**
	 * Get option use field
	 * 
	 * @return string
	 */
	public function getOptionUseField()
	{
		$field = (string) Mage::getStoreConfig('ec/preferences/use_custom_option_field');
		
		if ('' === $field)
		{
			$field = self::DEFAULT_CUSTOM_OPTION_FIELD;
		}
		
		return $field;
	}

	/**
	 * Get eventTimeout config value
	 * 
	 * @return int
	 */
	public function getTimeoutValue() 
	{
		$timeout = (int) Mage::getStoreConfig('ec/blocker/eventTimeout');
		
		if (!$timeout)
		{
			$timeout = 2000;
		}
		
		return $timeout;
	}
}