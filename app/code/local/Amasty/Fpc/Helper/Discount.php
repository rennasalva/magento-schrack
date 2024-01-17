<?php

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Fpc
 */
class Amasty_Fpc_Helper_Discount extends Mage_Core_Helper_Abstract
{
    protected $_tags = array();
    protected $loadLimit = 20;
    
    /* collect all tags */
    public function getSpecialProducts($lastChangeTime, $params)
    {
        $this->_tags = array();
        foreach (Mage::app()->getWebsites() as $website) {
            $websiteId = $website->getId();
            foreach (Mage::getModel('customer/group')->getCollection() as $group) {
                $groupId = $group->getCustomerGroupId();
                foreach ($params as $type) {
                    if (stripos($type, 'special') !== false) {
                        $this->getSpecialPriceTags($lastChangeTime, $type, $groupId, $websiteId);
                    } else {
                        $isPartialSearch = $this->getCatalogRuleTags($lastChangeTime, $type, $groupId, $websiteId);
                        if (!$isPartialSearch)
                            break 3;
                    }
                }
            }
        }
        return $this->_tags;
    }

    /* get  products  with special price*/
    protected function getSpecialPriceTags($lastChangeTime, $field, $groupId, $websiteId)
    {
        $productsCollection = Mage::getModel('catalog/product')
            ->getCollection()
            ->addPriceData($groupId, $websiteId)
            ->addAttributeToFilter(
                'price', array('gt' => new Zend_Db_Expr('final_price'))
            )
            ->addAttributeToFilter(
                $field, array('date' => true, 'to' => now()))
            ->addAttributeToFilter(
                $field, array('date' => true, 'from' => $lastChangeTime));

        foreach ($productsCollection as $product) {
            if (!in_array('catalog_product_' . $product->getId(), $this->_tags)) {
                $this->_tags[] = 'catalog_product_' . $product->getId();
                $this->getCategoryByProduct($product);
                $this->getParentProductIds($product);
            }
        }
    }


    /* get products where catalog price rule was applied */
    protected function getCatalogRuleTags($lastChangeTime, $field, $groupId, $websiteId)
    {
        $loadCount = 0;
        $catalogPriceCollection = Mage::getModel('catalogrule/rule_product_price')->getCollection();
        $catalogPriceCollection->addFieldToFilter('website_id', $websiteId);
        $catalogPriceCollection->addFieldToFilter('customer_group_id', $groupId);
        $catalogPriceCollection
            ->addFieldToFilter(
                $field, array('date' => true, 'to' => now()))
            ->addFieldToFilter(
                $field, array('date' => true, 'from' => $lastChangeTime));

        foreach ($catalogPriceCollection as $productEntity) {
            if ($loadCount > $this->loadLimit) {
                $this->_tags[] = 'catalog_product';
                $this->_tags[] = 'catalog_category';
                return false;

            } else {
                if (!in_array('catalog_product_' . $productEntity->getProductId(), $this->_tags)) {
                    $this->_tags[] = 'catalog_product_' . $productEntity->getProductId();
                    $product = Mage::getModel('catalog/product')->load($productEntity->getProductId());
                    $loadCount++;
                    $this->getCategoryByProduct($product);
                    $this->getParentProductIds($product);
                }
            }
        }
        return true;
    }

    /* get categories for product where special price was applied */
    protected function getCategoryByProduct($product)
    {
        foreach ($product->getCategoryIds() as $categoryId) {
            if (!in_array('catalog_category_' . $categoryId, $this->_tags)) {
                $category = Mage::getModel('catalog/category')->load($categoryId);
                foreach ($category->getParentCategories() as $parent) {
                    $parent->getEntityId();
                    if (!in_array('catalog_category_' . $parent->getEntityId(), $this->_tags)) {
                        $this->_tags[] = 'catalog_category_' . $parent->getEntityId();
                    }
                }
                $this->_tags[] = 'catalog_category_' . $categoryId;
            }
        }
    }

    /* get parent products for child */
    protected function getParentProductIds($child)
    {
        $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($child->getId());
        if ($parentIds) {
            foreach ($parentIds as $productId) {
                if (!in_array('catalog_product_' . $productId, $this->_tags)) {
                    $this->_tags[] = 'catalog_product_' . $productId;
                }
            }
        }
    }
}