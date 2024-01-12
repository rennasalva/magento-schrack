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
class Anowave_Ec_Model_Observer
{
    /**
     * Modifies transport layer and hooks tracking logic 
     * 
     * @param Varien_Event_Observer $observer
     * @return boolean
     */
	public function modify(Varien_Event_Observer $observer)
	{
		if (Mage::helper('ec')->isActive())
		{
			/**
			 * Debug mode
			 */
			if (Mage::getStoreConfig('ec/debug/debug') && @$_SERVER['REMOTE_ADDR'] === Mage::getStoreConfig('ec/debug/debug_ip'))
			{
				if (Mage::getStoreConfig('ec/debug/print_block_names'))
				{
					echo "<pre>{$observer->getBlock()->getNameInLayout()} @ {$observer->getBlock()->getType()}</pre>";
				}
			}
			
			/**
			 * Get transport layer
			 */
			$content = $observer->getTransport()->getHtml();

			/**
			 * Append data to blocks
			 */
			$template = $this->append
			(
				$observer->getBlock()
			);
			
			if ($template)
			{
				$content .= $template;
			}

			/**
			 * Augment transport layer
			 */
			$observer->getTransport()->setHtml
			(
				$this->decode
				(
					$this->alter($observer->getBlock(), $content)
				)
			);
		}

		return true;
	}
	
	/**
	 * Appends tracking logic to transport layer blocks 
	 * 
	 * @param Mage_Core_Block_Abstract $block
	 * @return NULL
	 */
	protected function append(Mage_Core_Block_Abstract $block)
	{
		switch ($block->getNameInLayout())
		{
			case 'footer':				return $this->getQueue();
			case 'checkout.cart':		return $this->getCart($block);
			case 'checkout.onepage':	return $this->getCheckout();
				
				default:
					foreach (array
					(
						array
						(
							Mage::getStoreConfig('ec/append/append_block_1'), Mage::getStoreConfig('ec/append/append_method_1')
						),
						array
						(
							Mage::getStoreConfig('ec/append/append_block_2'), Mage::getStoreConfig('ec/append/append_method_2')
						),
						array
						(
							Mage::getStoreConfig('ec/append/append_block_3'), Mage::getStoreConfig('ec/append/append_method_3')
						)
					) as $appendable)
					{
						if ($appendable[0] === $block->getNameInLayout() && method_exists($this, $appendable[1]))
						{
							return @call_user_func(array($this, $appendable[1]), $block);
						}
					}
				break;
		}

		return null;
	}
	
	/**
	 * Alters transport layer contents and hooks tracking logic 
	 * 
	 * @param Mage_Core_Block_Abstract $block
	 * @param string $content
	 * @return string|$content
	 */
	protected function alter(Mage_Core_Block_Abstract $block, $content)
	{	
		switch ($block->getNameInLayout())
		{
			case 'product.info.addtocart': 	return $this->getAjax($block, $content);

				default:
					
					switch ($block->getType())
					{
						case 'catalog/product_list':		return $this->getClick($block, $content);
						case 'checkout/cart_item_renderer': 
						case 'checkout/cart_item_renderer_configurable':
															return $this->getDelete($block, $content);
					}
		}
		
		return $content;
	}

	protected function getQueue()
	{
		return Mage::helper('ec')->filter
		(
			Mage::app()->getLayout()->createBlock('ec/track')->setTemplate('ec/footer.phtml')->toHtml()
		);
	}
	
	protected function getCheckout()
	{
		return Mage::helper('ec')->filter
		(
			Mage::app()->getLayout()->createBlock('ec/track')->setTemplate('ec/checkout.phtml')->toHtml()
		);
	}
	
	protected function getCart(Mage_Checkout_Block_Cart $block)
	{
		return Mage::app()->getLayout()->createBlock('ec/track')->setTemplate('ec/cart.phtml')->setData(array
		(
			'items' => $block->getItems(),
			'quote' => $block->getQuote()
 		))->toHtml();
	}
	
	/**
	 * Track Add to Cart event 
	 * 
	 * @param Mage_Core_Block_Abstract $block
	 * @param string $content
	 */
	protected function getAjax(Mage_Core_Block_Abstract $block, $content = null)
	{
		if(Mage::registry('current_category'))
		{
			$category = Mage::registry('current_category');
		}
		else 
		{
			$collection = $block->getProduct()->getCategoryIds();
			
			if (!$collection)
			{
				$collection[] = Mage::app()->getStore()->getRootCategoryId();
			}
			
			$category = Mage::getModel('catalog/category')->load
			(
				end($collection)
			);
		} 
		
		$category = Mage::helper('ec')->getCategory($category);
		
		$doc = new DOMDocument('1.0','utf-8');
		$dom = new DOMDocument('1.0','utf-8');
		
		@$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
		
		$x = new DOMXPath($dom);
		
		foreach ($x->query(Mage::getStoreConfig('ec/selectors/cart')) as $button)
		{
			/**
			 * Reference existing click event(s)
			 */
			$click = $button->getAttribute('onclick');
			
			$button->setAttribute('onclick', 		'return AEC.ajax(this,dataLayer)');
			$button->setAttribute('data-id', 		Mage::helper('ec')->jsQuoteEscapeDataAttribute($block->getProduct()->getSku()));
			$button->setAttribute('data-name', 		Mage::helper('ec')->jsQuoteEscapeDataAttribute($block->getProduct()->getName()));
			$button->setAttribute('data-category', 	Mage::helper('ec')->jsQuoteEscapeDataAttribute($category));
			$button->setAttribute('data-brand',		Mage::helper('ec')->jsQuoteEscapeDataAttribute
			(
				Mage::helper('ec')->getBrand
				(
					$block->getProduct()
				)
			));
			$button->setAttribute('data-price', 	Mage::helper('ec/price')->getPrice($block->getProduct()));
			$button->setAttribute('data-click', 	$click);
			$button->setAttribute('data-event',		'addToCart');
			
			if ('grouped' == $block->getProduct()->getTypeId())
			{
				$button->setAttribute('data-grouped',1);
			}
			
			if ('configurable' == $block->getProduct()->getTypeId())
			{
				$button->setAttribute('data-configurable',1);
			}
			
			if (1 === (int) $block->getProduct()->getHasOptions())
			{
				$options = array();
				
				/**
				 * Get field to use for variants
				 * 
				 * @var string
				 */
				$field = Mage::helper('ec')->getOptionUseField();

				foreach ($block->getProduct()->getProductOptionsCollection() as $option)
				{
					$data = $block->getProduct()->getOptionById($option['option_id']);

					switch($data->getType())
					{
						case 'drop_down':
							foreach ($data->getValues() as $value) 
							{
								$options[] = array
								(
									'id' 	=> $value->getOptionTypeId(),
									'label' => $data->getTitle(),
									'value' => (string) $value->getData($field)
								);
							}
							break;
						case 'field':
							$options[] = array
							(
								'label' => $data->getTitle(),
								'value' => (string) $data->getData($field)
							);
							break;
					}
				}
				
				if ($options)
				{
					$button->setAttribute('data-options', json_encode($options));
				}
			}
		}
	
		return $this->getDOMContent($dom, $doc);
	}
	
	/**
	 * Track Remove From Cart event 
	 * 
	 * @param Mage_Core_Block_Abstract $block
	 * @param string $content
	 */
	protected function getDelete(Mage_Core_Block_Abstract $block, $content = null)
	{
		$collection = $block->getProduct()->getCategoryIds();
			
		if (!$collection)
		{
			$collection[] = Mage::app()->getStore()->getRootCategoryId();
		}
		
		$category = Mage::getModel('catalog/category')->load
		(
			end($collection)
		);
		
		$category = Mage::helper('ec')->getCategory($category);
			
		$doc = new DOMDocument('1.0','utf-8');
		$dom = new DOMDocument('1.0','utf-8');
		
		@$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
		
		$x = new DOMXPath($dom);
		
		foreach ($x->query(Mage::getStoreConfig('ec/selectors/cart_delete')) as $a)
		{
			$variant = array();
			$product = $block->getProduct();
			
			/**
			 * Determine product type
			 */
			switch ($product->getTypeId())
			{
				case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE:
					
					$options = $product->getTypeInstance(true)->getOrderOptions($product);
					
					if (isset($options['attributes_info']))
					{
						foreach ((array) $options['attributes_info'] as $option)
						{
							$variant[] = join(':', array
							(
								$option['label'], $option['value']
							));
						}
					}

					break;
			}
			
			$a->setAttribute('onclick', 		'return AEC.remove(this, dataLayer)');
			$a->setAttribute('data-id', 		$product->getSku());
			$a->setAttribute('data-name', 		Mage::helper('ec')->jsQuoteEscapeDataAttribute($product->getName()));
			$a->setAttribute('data-price', 		Mage::helper('ec/price')->getPrice($product));
			$a->setAttribute('data-category', 	Mage::helper('ec')->jsQuoteEscapeDataAttribute($category));
			$a->setAttribute('data-brand',		Mage::helper('ec')->jsQuoteEscapeDataAttribute
			(
				Mage::helper('ec')->getBrand
				(
					$product
				)
			));
			$a->setAttribute('data-quantity', 	$block->getQty());
			$a->setAttribute('data-variant', 	Mage::helper('ec')->jsQuoteEscapeDataAttribute(join('-', $variant)));
			$a->setAttribute('data-event',		'removeFromCart');
			
			if (false !== strpos($content, 'ajaxDelete'))
			{
				$a->setAttribute('data-mini-cart',1);
			}
		}
		
		return $this->getDOMContent($dom, $doc, false);
	}
	
	/**
	 * Track product click 
	 * 
	 * @param Mage_Core_Block_Abstract $block
	 * @param string $content
	 */
	protected function getClick(Mage_Core_Block_Abstract $block, $content = null)
	{
		/**
		 * Check for cached data
		 */
		if ($this->useCache())
		{
			$cache = Mage::helper('ec/cache')->load(Anowave_Ec_Helper_Cache::CACHE_LISTING);
			
			if ($cache)
			{
				return $cache;
			}
		}
		
		$doc = new DOMDocument('1.0','utf-8');
		$dom = new DOMDocument('1.0','utf-8');
		
		@$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
		
		$products = array();
		

		foreach ($block->getLoadedProductCollection() as $product)
		{
			$products[] = $product;
		}

		$query = new DOMXPath($dom);
		
		foreach ($query->query(Mage::getStoreConfig('ec/selectors/list'), $dom) as $key => $element)
		{
			if (isset($products[$key]))
			{
				if (Mage::registry('current_category'))
				{
					$category = Mage::registry('current_category');
				}
				else 
				{
					$collection = $products[$key]->getCategoryIds();
					
					if (!$collection)
					{
						$collection[] = Mage::app()->getStore()->getRootCategoryId();
					}
					
					$category = Mage::getModel('catalog/category')->load
					(
						end($collection)
					);
				}
		
				/**
				 * Product click tracking
				 */
				foreach ($query->query(Mage::getStoreConfig('ec/selectors/click'), $element) as $a)
				{
					$click = $a->getAttribute('onclick');
					
					$a->setAttribute('data-id', 		$products[$key]->getSku());
					$a->setAttribute('data-name', 		Mage::helper('ec')->jsQuoteEscapeDataAttribute($products[$key]->getName()));
					$a->setAttribute('data-price', 		Mage::helper('ec/price')->getPrice($products[$key]));
					$a->setAttribute('data-category', 	Mage::helper('ec')->jsQuoteEscapeDataAttribute(Mage::helper('ec')->getCategory($category)));
					$a->setAttribute('data-list',		Mage::helper('ec')->jsQuoteEscapeDataAttribute(Mage::helper('ec')->getCategoryList($category)));
					$a->setAttribute('data-brand',		Mage::helper('ec')->jsQuoteEscapeDataAttribute
					(
						Mage::helper('ec')->getBrand($products[$key])
					));
					$a->setAttribute('data-quantity', 	1);
					$a->setAttribute('data-click',		$click);
					$a->setAttribute('onclick',			'return AEC.click(this,dataLayer)');
					$a->setAttribute('data-event',		'productClick');
				}
				
				/**
				 * Direct "Add to cart" tracking from categories
				 */
				foreach ($query->query(Mage::getStoreConfig('ec/selectors/click_ajax'), $element) as $a)
				{
					$click = $a->getAttribute('onclick');
						
					$a->setAttribute('data-id', 		$products[$key]->getSku());
					$a->setAttribute('data-name', 		Mage::helper('ec')->jsQuoteEscapeDataAttribute($products[$key]->getName()));
					$a->setAttribute('data-price', 		Mage::helper('ec/price')->getPrice($products[$key]));
					$a->setAttribute('data-category', 	Mage::helper('ec')->jsQuoteEscapeDataAttribute(Mage::helper('ec')->getCategory($category)));
					$a->setAttribute('data-list',		Mage::helper('ec')->jsQuoteEscapeDataAttribute(Mage::helper('ec')->getCategoryList($category)));
					$a->setAttribute('data-brand',		Mage::helper('ec')->jsQuoteEscapeDataAttribute
					(
						Mage::helper('ec')->getBrand($products[$key])
					));
					$a->setAttribute('data-quantity', 	1);
					$a->setAttribute('data-click',		$click);
					$a->setAttribute('onclick',			'return AEC.ajaxList(this,dataLayer)');
					$a->setAttribute('data-event',		'addToCart');
				}
			}
		}
		
		$content = $this->getDOMContent($dom, $doc);
		
		/**
		 * Save content to cache
		 */
		if ($this->useCache())
		{
			Mage::helper('ec/cache')->save($content, Anowave_Ec_Helper_Cache::CACHE_LISTING);
		}
		
		return $content;
	}

	/**
	 * Assign order id to block
	 * 
	 * @param Varien_Event_Observer $observer
	 */
	public function setOrder(Varien_Event_Observer $observer)
	{
		if (!$this->isCommandLineInterface())
		{
			$orderIds = $observer->getEvent()->getOrderIds();
			
	        if (empty($orderIds) || !is_array($orderIds)) 
	        {
	            return;
	        }
	        
	        $block = Mage::app()->getFrontController()->getAction()->getLayout()->getBlock('ec_purchase');
	        
	        if ($block) 
	        {
	            $block->setOrderIds($orderIds);
	            $block->setAdwords(new Varien_Object(array
	            (
	            	'google_conversion_id' 			=> Mage::getStoreConfig('ec/adwords/conversion_id'),
	            	'google_conversion_language' 	=> Mage::app()->getLocale()->getLocaleCode(),
	            	'google_conversion_format' 		=> Mage::getStoreConfig('ec/adwords/conversion_format'),
	            	'google_conversion_label' 		=> Mage::getStoreConfig('ec/adwords/conversion_label'),
	            	'google_conversion_color' 		=> Mage::getStoreConfig('ec/adwords/conversion_color'),
	            	'google_conversion_currency' 	=> Mage::app()->getStore()->getCurrentCurrencyCode()
	            )));
	        }
	        else 
	        {
	        	return true;
	        }
		}
	}
	

	/**
	 * Make customer data available after successfull login
	 * 
	 * @param Varien_Event_Observer $observer
	 */
	public function setLogin(Varien_Event_Observer $observer)
	{
		Mage::getSingleton('core/session')->setCustomerLogin(true);

		return true;
	}

	/**
	 * Track new registrations
	 * 
	 * @param Varien_Event_Observer $observer
	 */
	public function setRegister(Varien_Event_Observer $observer)
	{
		/**
		 * Create a temporary session variable
		 */
		Mage::getSingleton('core/session')->setEventRegistration(true);
		
		return true;
	}
	
	/**
	 * Retrieves body 
	 * 
	 * @param DOMDocument $dom
	 * @param DOMDocument $doc
	 * @param string $decode
	 */
	protected function getDOMContent(DOMDocument $dom, DOMDocument $doc, $decode = true)
	{
		$head = $dom->getElementsByTagName('head')->item(0);
		$body = $dom->getElementsByTagName('body')->item(0);
		
		if ($head instanceof DOMElement)
		{
			foreach ($head->childNodes as $child)
			{
				$doc->appendChild($doc->importNode($child, true));
			}
		}

		if ($body instanceof DOMElement)
		{
			foreach ($body->childNodes as $child)
			{
			    $doc->appendChild($doc->importNode($child, true));
			}
		}

		$content = @$doc->saveHTML();
		
		return html_entity_decode($content, ENT_COMPAT, 'UTF-8');
	}
	
	/**
	 * Decode special characters 
	 * 
	 * @param string $content
	 */
	private function decode($content)
	{
		return $content;
	}
	
	/**
	 * Check command line interface (usually CRONJOB)
	 * 
	 * @return boolean
	 */
	private function isCommandLineInterface()
	{
		return (php_sapi_name() === 'cli' OR defined('STDIN'));
	}
	
	/**
	 * Check if cache is used
	 */
	private function useCache()
	{
		return Mage::helper('ec/cache')->useCache();
	}
}