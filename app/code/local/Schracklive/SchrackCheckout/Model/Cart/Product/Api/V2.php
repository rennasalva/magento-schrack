<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package    Mage_Checkout
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Shopping cart api for product
 *
 * @category    Mage
 * @package    Mage_Checkout
 * @author      Magento Core Team <core@magentocommerce.com>
 */

/**
 * NOTE: this extends Mage_Checkout_Model_Cart_Product_Api instead of Mage_Checkout_Model_Cart_Product_Api_V2,
 * because ...V2 itself extends ...Api, and I wanted to avoid having to copy/paste/modify 2 classes instead
 * of 1 just to be able to extend V2 for one single method.
 */
class Schracklive_SchrackCheckout_Model_Cart_Product_Api_V2 extends Mage_Checkout_Model_Cart_Product_Api_V2
{

    /**
     * - from Core Api.php - is called in Core V2 _prepareProductsData, thus copied here
     * @param type $data
     * @return null
     */
    protected function _V1_prepareProductsData($data)
    {
        if (!is_array($data)) {
            return null;
        }

        $_data = array();
        if (is_array($data) && is_null($data[0])) {
            $_data[] = $data;
        } else {
            $_data = $data;
        }

        return $_data;
    }
    /**
     * Return an Array of Object attributes.
     *
     * @param Mixed $data
     * @return Array
     */
    protected function _prepareProductsData($data){
        if (is_object($data)) {
            $arr = get_object_vars($data);
            $assocArr = array();
            foreach ($arr as $key => $value) {                
                if (is_array($value)) {
                    foreach ($value as $v) {
                        if (is_object($v) && count(get_object_vars($v))==2
                            && isset($v->key) && isset($v->value)) {
                            $assocArr[$v->key] = $v->value;
                        }
                    }
                    
                }
            }
            if (empty($assocArr)) {
                return $arr;
            } else {
                $arr[$key] = $assocArr;
            }
            $arr = $this->_prepareProductsData($arr);
            return $this->_V1_prepareProductsData($arr);
        }
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_object($value) || is_array($value)) {
                    $data[$key] = $this->_prepareProductsData($value);
                } else {
                    $data[$key] = $value;
                }
            }
            return $this->_V1_prepareProductsData($data);
        }
        return $data;
    }
    
    
    /**
     * @param  $quoteId
     * @param  $productsData
     * @param  $store
     * @return bool
     */
    public function add($quoteId, $productsData, $store=null)
    {        
        $quote = $this->_getQuote($quoteId, $store);
        if (empty($store)) {
            $store = $quote->getStoreId();
        }

        $productsData = $this->_prepareProductsData($productsData);
        if (empty($productsData)) {
            $this->_fault('invalid_product_data');
        }

        $errors = array();
        foreach ($productsData as $productItem) {
            if (isset($productItem['product_id'])) {
                $productByItem = $this->_getProduct($productItem['product_id'], $store, "id");
            } else if (isset($productItem['sku'])) {
                $productByItem = $this->_getProduct($productItem['sku'], $store, "sku");
            } else {
                $errors[] = Mage::helper('checkout')->__("One item of products do not have identifier or sku");
                continue;
            }

            $productRequest = $this->_getProductRequest($productItem);
            try {
                $result = $quote->addProduct($productByItem, $productRequest);
                if (is_string($result)) {
                    Mage::throwException($result);
                }
            } catch (Mage_Core_Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            $this->_fault("add_product_fault", implode(PHP_EOL, $errors));
        }

        try {
            $quote->collectTotals()->save();
        } catch(Exception $e) {
            $this->_fault("add_product_quote_save_fault", $e->getMessage());
        }

        return true;
    }
    
    /**
     * Return loaded product instance
     *
     * @param  int|string $productId (SKU or ID)
     * @param  int|string $store
     * @param  string $identifierType
     * @return Mage_Catalog_Model_Product
     */
    protected function _getProduct($productId, $store = null, $identifierType = null)
    {
        $product = Mage::helper('schrackcatalog/product')->getProduct($productId,
                        $this->_getStoreId($store),
                        $identifierType
        );
        return $product;
    }
}
