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


/**
 * Catalog products compare block
 *
 * @category    Mage
 * @package    Mage_Catalog
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Schracklive_SchrackCatalog_Block_Product_Compare_List extends Mage_Catalog_Block_Product_Compare_List

{
 

    /**
     * Retrieve Product Compare Attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        if (is_null($this->_attributes)) {
            $this->_attributes = $this->getItems()->getComparableAttributes();
        }
        $pattern = '(schrack_)';
        
        $arrayForSort = array(); 
        foreach ($this->_attributes as $_attribute) {
            if (preg_match($pattern,$_attribute->getAttributeCode()) == 0 ) {
                continue;
            }
            $arrayForSort[   $_attribute->getStoreLabel() ] = $_attribute;  
        }

        ksort($arrayForSort)  ;
        return $arrayForSort;
    }

       /**
     * Retrieve Product Compare items collection
     *
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Compare_Item_Collection
     */
    public function getItems()
    {
        if (is_null($this->_items)) {
            Mage::helper('catalog/product_compare')->setAllowUsedFlat(false);

            $this->_items = Mage::getResourceModel('catalog/product_compare_item_collection')
                ->useProductItem(true)
                ->setStoreId(Mage::app()->getStore()->getId());
               // ->setPage(1,5); // limit to 6 - WT

            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                $this->_items->setCustomerId(Mage::getSingleton('customer/session')->getCustomerId());
            } elseif ($this->_customerId) { // Nagarro added new condition from 1.9.x core
                $this->_items->setCustomerId($this->_customerId);
            } else {
                $this->_items->setVisitorId(Mage::getSingleton('log/visitor')->getId());
            }

            $this->_items
                ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
                ->loadComparableAttributes()
                ->addMinimalPrice()
                ->addTaxPercents();

            Mage::getSingleton('catalog/product_visibility')
                ->addVisibleInSiteFilterToCollection($this->_items);
        }

       $i = 1; 
       foreach ($this->_items as $k => $item) {
           if ($i > 6) {
               $this->_items->removeItemByKey($k);
           }
           $i++;
       }
       
        return $this->_items;
    }
    
}