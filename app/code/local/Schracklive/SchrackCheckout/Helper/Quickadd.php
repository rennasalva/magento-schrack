<?php

class Schracklive_SchrackCheckout_Helper_Quickadd extends Mage_Core_Helper_Abstract {
    
    const QUERY_VAR_NAME = 'sku';
    
    /**
     * Retrieve suggest url
     *
     * @return string
     */
    public function getSuggestUrl()
    {
        return $this->_getUrl('checkout/cart/suggestquickadd', array(
            '_secure' => Mage::app()->getFrontController()->getRequest()->isSecure()
        ));
    }
    
    
    /**
     * Retrieve result page url and set "secure" param to avoid confirm
     * message when we submit form from secure page to unsecure
     *
     * @param   string $query
     * @return  string
     */
    public function getResultUrl($query = null)
    {
        return $this->_getUrl('checkout/result', array(
            '_query' => array(self::QUERY_VAR_NAME => $query),
            '_secure' => Mage::app()->getFrontController()->getRequest()->isSecure()
        ));
    }
    
    
    
    public function getQueryParamName() {
        return self::QUERY_VAR_NAME;
    }
 
    public function getPartslistButtonTarget() {
        $module = Mage::app()->getFrontController()->getRequest()->getModuleName();
        $controller = Mage::app()->getFrontController()->getRequest()->getControllerName();
        if ($module === 'wishlist' && $controller === 'partslist')
            return 'current-partslist';
        else
            return 'active-partslist';
    }
    
    public function getModuleName() {
        return Mage::app()->getFrontController()->getRequest()->getModuleName();
    }
    public function getControllerName() {
        return Mage::app()->getFrontController()->getRequest()->getControllerName();
    }
}

?>
