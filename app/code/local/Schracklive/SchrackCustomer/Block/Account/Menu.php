<?php

/**
 * Menu
 *
 * @author c.friedl
 */
class Schracklive_SchrackCustomer_Block_Account_Menu extends Mage_Core_Block_Template {
/**
     * find out whether we were called for all kinds of documents
     * (as opposed to: one specific document type)
     * @return boolean
     */
    protected function isActive($action, $module = 'customer', $controller = 'account') {
        $x = $this->getRequest();
        
        return ($action === $this->getRequest()->getActionName() && $module === $this->getRequest()->getModuleName()
                && $controller === $this->getRequest()->getControllerName());
    }

    protected function isPromotionsAvail () {
        /* @var $promoHelper Schracklive_Promotions_Helper_Data */
        $promoHelper = Mage::helper('promotions');
        return $promoHelper->hasPromotions();
    }
    
    protected function isActAsUserPossible ( $customer ) {
        if ( ! $customer ) {
            return false;
        }

        return Mage::helper('schrackcustomer')->mayActAsUser($customer);
    }

    protected function slfh($url) {
        echo "window.location.href='{$this->getUrl('customer/account/login', array('referer' => base64_encode($this->getUrl($url))))}';return false;";
    }
}

?>
