<?php

class Schracklive_SchrackWishlist_Block_Endcustomerpartslist_Abstract extends Mage_Core_Block_Template {
    /**
     * @return Schracklive_SchrackWishlist_Model_Endcustomerpartslist_Customer
     * @throws Exception
     */
    public function getCustomer() {
        $customerId = Mage::helper('schrackwishlist/endcustomerpartslist')->getCustomerId();
        if ( isset($customerId) ) {
            $customer = Mage::getModel('schrackwishlist/endcustomerpartslist_customer')->loadByCustomerId($customerId);
            return $customer;
        } else {
            throw new Exception('no customer found');
        }
    }

    public function getUrl($route='', $params=array()) {
        $match = array();
        if ( preg_match('#^wishlist/endcustomerpartslist(.+)$#', $route, $match) ) {
            return Mage::getStoreConfig('schrack/endcustomerpartslist/base_url') . $match[1];
        }
        return parent::getUrl($route, $params);
    }

    public function getCustomerText($fieldName, $default = '') {
        $customer = $this->getCustomer();
        if ( $customer->getHasOwnCompanyInfo() ) {
            return $customer->getData($fieldName);
        } else {
            return $default;
        }
    }

    public function getCustomerTextHtml($fieldName, $title, $default = '') {
        $customer = $this->getCustomer();
        if ( $customer->getHasOwnCompanyInfo() ) {
            $value = $customer->getData($fieldName);
        } else {
            $value = $default;
        }
        if ( strlen($value) > 0 ) {
            return "<span class=\"title\"> $title:</span> $value<br/>";
        } else {
            return '';
        }
    }
} 