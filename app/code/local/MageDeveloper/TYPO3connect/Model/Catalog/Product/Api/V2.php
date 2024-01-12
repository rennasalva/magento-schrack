<?php
/**
 * MageDeveloper TYPO3connect Module
 * ---------------------------------
 *
 * @category    Mage
 * @package    MageDeveloper_TYPO3connect
 * @copyright   Magento Developers / magedeveloper.de <kontakt@magedeveloper.de>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MageDeveloper_TYPO3connect_Model_Catalog_Product_Api_V2 extends Mage_Catalog_Model_Product_Api_V2
{
	/**
	 * detail
	 * Get full product information
	 * by a given product id
	 * 
	 * @param int $productId
	 * @return array|bool
	 */
	public function detail($productId, $store = null, $identifierType = null)	
	{
 		$product = $this->_getProduct($productId, $store, $identifierType);

		$result = array();
		
		if ($product->getId() == $productId) {
		
			foreach ($product->getTypeInstance(true)->getEditableAttributes($product) as $attribute) {
				$result[$attribute->getAttributeCode()] = $product->getData($attribute->getAttributeCode());
			}
			
			$attributeData = array();
			$attributes = $product->getAttributes();
			foreach ($attributes as $attribute) {
			    if ($attribute->getIsVisibleOnFront()) {
			        $value = $attribute->getFrontend()->getValue($product);
					if ($value == '') {
						$value = $attribute->getDefaultValue();
					}
			        $attributeData[] = array(	'key'	=> $attribute->getAttributeCode(),
			        							'value' => $value,
			        							'label'	=> $attribute->getFrontendLabel(),
									   );
			    } 
			}
	
	        $result = array_merge($result, array( // Basic product data
	            'product_id'	 	=> $product->getId(),
	            'sku'       	 	=> $product->getSku(),
	            'attribute_set_id'  => $product->getAttributeSetId(),
	            'qty'			 	=> (int)Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty(),
	            'type'      	 	=> $product->getTypeId(),
	            'price'				=> $product->getPrice(),
	            'special_price'		=> $product->getSpecialPrice(),
	            'final_price'		=> $product->getFinalPrice(1),
			    'manage_stock'  	=> $product->getStockItem()->getManageStock(),
			    'is_saleable'		=> $product->isSaleable(),
			    'is_in_stock'		=> $product->isInStock(),
			    'is_disabled'		=> $product->isAvailable(),
			    'weight'			=> $product->getWeight(),
	            'currency'			=> Mage::app()->getStore()->getCurrentCurrencyCode(),
	            'upsell'			=> implode(',', $product->getUpSellProductIds()),
	            'crosssell'			=> implode(',', $product->getCrossSellProductIds()),
	            )
	        );
			
			$result['additional_attributes'] = $attributeData;
			
			// Configurable product
			if ($product->isConfigurable())
			{
				$result["options"] = $this->getConfigByProduct($productId, $store = null, $identifierType = null);
				$result["associated_products"] = implode(',', $product->getTypeInstance()->getUsedProductIds());
			}
			
			// Grouped product
			if ($product->isGrouped())
			{
				$assoc = array();
				
				$associatedProducts = $product->getTypeInstance()->getAssociatedProducts($product);
				
				$minimalPrice = null;
				foreach ($associatedProducts as $_assocItem)
				{
					$assoc[$_assocItem->getId()] = array(
						"id"    => $_assocItem->getId(),
						"qty"   => (int)$_assocItem->getQty(),
						"price"	=> $_assocItem->getPrice(),
					);
					
					if ($minimalPrice === null)
					{
						$minimalPrice = $_assocItem->getPrice();
					}
					
					if ($_assocItem->getPrice() < $minimalPrice)
					{
						$minimalPrice = $_assocItem->getPrice();
					}
					
					
				}
				
				$result["minimal_price"]		= $minimalPrice;
				$result["associated_products"] 	= $assoc;
			}
			
		}
		return json_encode($result);
	}


	/**
	 * filtered
	 * Gets filtered product ids
	 * 
	 * @param array $tags Array with tags to filter
	 * @param array $categories Array with categories to filter
	 * @param array $skus Array with skus to filter
	 * @param string $storeCode Store View Code
	 * @return array
	 */
	public function filtered($tags = array(), $categories = array(), $skus = array(), $storeCode = null)
	{
		$storeId = $this->_getStoreId($storeCode);
		
		// Product Ids from Tags
		$productIdsTag = array();
		// Product Ids from Categories
		$productIdsCategory = array();
		// Product Ids from Skus
		$productIdsSkus = array();
		// Final product Ids
		$productIds = array();
		
		
		
		// Product Ids by Tag
		$tagIds = array();
		foreach ($tags as $_tag)
		{
			$tagIds = $this->getProductIdsByTag($_tag, $storeId);
			$productIdsTag = array_merge($productIdsTag, $tagIds);
		}

		// Product Ids by Category
		foreach ($categories as $_cat)
		{
			if ($_cat != "")
			{
				$category = Mage::getModel("catalog/category")
								->setStoreId( $storeId )
						 		->loadByAttribute("name", $_cat);
						 
				
				if ($category && $category->getId())
				{
					// We want the product ids of the category
					$collection = $category->getProductCollection();
					
					foreach ($collection as $_product) {
						$productIdsCategory[] = $_product->getId();
					}
					
				}
				
			}
			
		}
		
		// Product Ids by Sku
		$productIdsSkus = $this->getProductIdsBySkus($skus, $storeId);
		
		$tagsCount = (count($tags)>0)?true:false;
		$catCount = (count($categories)>0)?true:false;
		$skuCount = (count($skus)>0)?true:false;
		
		
		if ($tagsCount && !$catCount )
		{
			// Tags only filter
			$productIds = $productIdsTag;
		} 
		else if (!$tagsCount && $catCount)
		{
			// Categories only filter
			$productIds = $productIdsCategory;
		}
		else 
		{
			// Tags and categories
			// When we have tags and categories we need to compute 
			// in which product ids are same
			$productIds = array_intersect($productIdsTag, $productIdsCategory);
			
		}
		
		// Only from skus
		if (empty($productIds))
		{
			$productIds = $productIdsSkus;
		}
		else
		{
			$productIds = array_intersect($productIds, $productIdsSkus);
		}
		
		
		/*
		Mage::log("____________STORE ID_______________");
		Mage::log($storeId);
		
		Mage::log("____________TAGS_______________");
		Mage::log($tags);
		
		Mage::log("____________CATEGORIES_______________");
		Mage::log($categories);
		
		Mage::log("____________TAGS IDS_______________");
		Mage::log($productIdsTag);
		
		Mage::log("____________CATEGORIES IDS_______________");
		Mage::log($productIdsCategory);
		
		Mage::log("____________FINAL IDS_______________");
		Mage::log($productIds);
		*/
		
		return $productIds;
	}



	/**
	 * Get a product collection by given tag string
	 * 
	 * @param string $tagString Tag String
	 * @return array
	 */
	public function getProductIdsByTag($tagString, $storeId)
	{
		$tagByName = Mage::getModel("tag/tag")->loadByName($tagString);
		$tag = null;
		
		if ($tagByName->getId())
		{
			$tag = Mage::getModel("tag/tag")->load($tagByName->getId());
		}
		
		if (!$tag || !$tag->getId() || !$tag->isAvailableInStore($storeId))
		{
			return array();
		}
		
		return $tag->getRelatedProductIds();
	}


	/**
	 * Gets product ids by given skus
	 * 
	 * @param array $skus
	 * @return array
	 */
	public function getProductIdsBySkus(array $skus)
	{
		$productIds = array();
		
		$collection = Mage::getModel("catalog/product")
						->getCollection()
						->addAttributeToSelect(array("id"))
						->addFieldToFilter("sku", array("in" => $skus))
						->load();
		
		foreach ($collection as $_item)
		{
			$productIds[] = $_item->getId();
		}
		
		return $productIds;
	}
	
	
	/**
	 * Gets a config options from a product
	 * 
	 * @param int $productId Id of the product
	 * @return string
	 */	
	public function getConfigByProduct($productId, $store = null, $identifierType = null)
	{
		$currentProduct 	= $this->_getProduct($productId, $store, $identifierType);
		
		$allowedAttributes 	= $currentProduct->getTypeInstance(true)
            								 ->getConfigurableAttributes($currentProduct);
		
        $attributes = array();
        $options    = array();
		
        $preconfiguredFlag = $currentProduct->hasPreconfiguredValues();
        if ($preconfiguredFlag) {
            $preconfiguredValues = $currentProduct->getPreconfiguredValues();
            $defaultValues       = array();
        }

		$allowProducts = $this->_getAllowProducts($currentProduct);
		
		
        foreach ($allowProducts as $product) {
            $productId  = $product->getId();

            foreach ($allowedAttributes as $attribute) {
                $productAttribute   = $attribute->getProductAttribute();
                $productAttributeId = $productAttribute->getId();
                $attributeValue     = $product->getData($productAttribute->getAttributeCode());
                
                if (!isset($options[$productAttributeId])) {
                    $options[$productAttributeId] = array();
                }

                if (!isset($options[$productAttributeId][$attributeValue])) {
                    $options[$productAttributeId][$attributeValue] = array();
                }
                $options[$productAttributeId][$attributeValue][] = $productId;
            }
        }


        foreach ($allowedAttributes as $attribute) {

            $productAttribute = $attribute->getProductAttribute();
            $attributeId = $productAttribute->getId();
            $info = array(
               'id'        => $productAttribute->getId(),
               'code'      => $productAttribute->getAttributeCode(),
               'label'     => $attribute->getLabel(),
               'options'   => array()
            );


            $optionPrices = array();
            $prices = $attribute->getPrices();
            if (is_array($prices)) {
                foreach ($prices as $value) {
                    if(!$this->_validateAttributeValue($attributeId, $value, $options)) {
                        continue;
                    }
					
                    $currentProduct->setConfigurablePrice(
                        $this->_preparePrice($currentProduct, $value['pricing_value'], $value['is_percent'])
                    );
                    $currentProduct->setParentId(true);
                   
                    $configurablePrice = $currentProduct->getConfigurablePrice();
					
                    if (isset($options[$attributeId][$value['value_index']])) {
                        $productsIndex = $options[$attributeId][$value['value_index']];
                    } else {
                        $productsIndex = array();
                    }

                    $info['options'][] = array(
                        'id'        => $value['value_index'],
                        'label'     => $value['label'],
                        'price'     => $configurablePrice,
                        'oldPrice'  => $this->_prepareOldPrice($currentProduct, $value['pricing_value'], $value['is_percent']),
                        'products'  => $productsIndex,
                    );
                    $optionPrices[] = $configurablePrice;
                }
            }
            /**
             * Prepare formated values for options choose
             */
            foreach ($optionPrices as $optionPrice) {
                foreach ($optionPrices as $additional) {
                    $this->_preparePrice($currentProduct, abs($additional-$optionPrice));
                }
            }
            if($this->_validateAttributeInfo($info)) {
               $attributes[$attributeId] = $info;
            }

            // Add attribute default value (if set)
            if ($preconfiguredFlag) {
                $configValue = $preconfiguredValues->getData('super_attribute/' . $attributeId);
                if ($configValue) {
                    $defaultValues[$attributeId] = $configValue;
                }
            }
        }

		$prices = array(
		    'basePrice'         => $this->_registerJsPrice($currentProduct->getFinalPrice()),
            'oldPrice'          => $this->_registerJsPrice($currentProduct->getPrice()),
		);

        $config = array(
            'attributes'        => $attributes,
			'prices'			=> $prices,
            'productId'         => $currentProduct->getId(),
            'chooseText'        => Mage::helper('catalog')->__('Choose an Option...'),
        );

        if ($preconfiguredFlag && !empty($defaultValues)) {
            $config['defaultValues'] = $defaultValues;
        }

		return $config;
	}	


    /**
     * Validating of super product option value
     *
     * @param array $attributeId
     * @param array $value
     * @param array $options
     * @return boolean
     */
    protected function _validateAttributeValue($attributeId, &$value, &$options)
    {
        if(isset($options[$attributeId][$value['value_index']])) {
        	
            return true;
        }

        return false;
    }

    /**
     * Validation of super product option
     *
     * @param array $info
     * @return boolean
     */
    protected function _validateAttributeInfo(&$info)
    {
        if(count($info['options']) > 0) {
            return true;
        }
        return false;
    }
	
    /**
     * Replace ',' on '.' for js
     *
     * @param float $price
     * @return string
     */
    protected function _registerJsPrice($price)
    {
        return str_replace(',', '.', $price);
    }

	
    /**
     * Calculation real price
     *
     * @param float $price
     * @param bool $isPercent
     * @return mixed
     */
    protected function _preparePrice(Mage_Catalog_Model_Product $product, $price, $isPercent = false)
    {
        if ($isPercent && !empty($price)) {
            $price = $product->getFinalPrice() * $price / 100;
        }

        return $this->_registerJsPrice($price, true);
    }

    /**
     * Calculation price before special price
     *
     * @param float $price
     * @param bool $isPercent
     * @return mixed
     */
    protected function _prepareOldPrice(Mage_Catalog_Model_Product $product, $price, $isPercent = false)
    {
        if ($isPercent && !empty($price)) {
            $price = $product->getPrice() * $price / 100;
        }

        return $this->_registerJsPrice($price, true);
    }

    /**
     * Get Allowed Products
     *
     * @return array
     */
    protected function _getAllowProducts(Mage_Catalog_Model_Product $product)
    {
		$products = array();
		
		$skipSaleableCheck = Mage::helper('catalog/product')->getSkipSaleableCheck();
		
		$allProducts = $product->getTypeInstance(true)
                			   ->getUsedProducts(null, $product);
                			   
		foreach ($allProducts as $_product) {
			Mage::log("HIER");
			//if ($_product->isSaleable() || $skipSaleableCheck) {
				$products[] = $_product;
			//}
		}
        
        return $products;
    }
	



}
	