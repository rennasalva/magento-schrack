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
 * @package    Mage_Catalog
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

require_once "Mage/Catalog/controllers/Product/CompareController.php";
/**
 * Catalog comapare controller
 *
 * @category    Mage
 * @package    Mage_Catalog
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Schracklive_SchrackCatalog_Product_CompareController extends Mage_Catalog_Product_CompareController
{


    /**
     * Add item to compare list
     */
    public function addAction()
    {
        if (!$this->_validateFormKey()) {   // Nagarro added new if statement from 1.9.x core
            $this->_redirectReferer();
            return;
        }
        
        if ($productId = (int) $this->getRequest()->getParam('product')) {
            $product = Mage::getModel('catalog/product')
                ->setStoreId(Mage::app()->getStore()->getId())
                ->load($productId);

            if ( $product->getId() ) {
                Mage::getSingleton('catalog/product_compare_list')->addProduct($product);
                Mage::dispatchEvent('catalog_product_compare_add_product', array('product'=>$product));
            }

            Mage::helper('catalog/product_compare')->calculate();
        }
        $message = $this->__('Product %s successfully added to compare list', $product->getName());
        Mage::getSingleton('core/session')->addSuccess($message);
        Mage::getSingleton('core/session')->addSuccess($this->__('You can access your compare list <a href="%s">here.</a>', Mage::getUrl('catalog/product_compare')));
        $this->_redirectReferer();
    }

    /**
     * Remove item from compare list
     */
    public function removeAction()
    {
        if ($productId = (int) $this->getRequest()->getParam('product')) {
            $product = Mage::getModel('catalog/product')
                ->setStoreId(Mage::app()->getStore()->getId())
                ->load($productId);

            if($product->getId()) {
                $item = Mage::getModel('catalog/product_compare_item');
                if(Mage::getSingleton('customer/session')->isLoggedIn()) {
                    $item->addCustomerData(Mage::getSingleton('customer/session')->getCustomer());
                } elseif ($this->_customerId) {     // Nagarro added new condition from 1.9.x core
                    $item->addCustomerData(
                    Mage::getModel('customer/customer')->load($this->_customerId)
                    );
                } else {
                    $item->addVisitorId(Mage::getSingleton('log/visitor')->getId());
                }

                $item->loadByProduct($product);

                if($item->getId()) {
                    $item->delete();
                    /*Mage::getSingleton('catalog/session')->addSuccess(
                        $this->__('Product %s successfully removed from compare list', $product->getName())
                    );*/
                    Mage::dispatchEvent('catalog_product_compare_remove_product', array('product'=>$item));
                    Mage::helper('catalog/product_compare')->calculate();
                }
            }
        }
        $this->_redirectReferer();
    }
}