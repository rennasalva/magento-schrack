<?php
/**
 * Created by IntelliJ IDEA.
 * User: c.friedl
 * Date: 24.11.2014
 * Time: 11:29
 */

class Schracklive_SchrackCatalog_Block_Product_View_Accessories extends Mage_Catalog_Block_Product_Abstract {
    protected function _construct() {
        parent::_construct();
        $this->addData(array(
            'cache_lifetime'    => 3600,
        ));
        $this->setCacheKey('catalog_product_view_accessories_'. md5(serialize(Mage::app()->getRequest()->getParams())) . '_' . Mage::getSingleton('customer/session')->getCustomer()->getId());
    }
} 