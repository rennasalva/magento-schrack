<?php

/**
 * Wishist data helper
 * NOTE: the wishlist is always identified by the parameter "id" (never "wishlist" or similar)
 *
 * @author c.friedl
 */
class SchrackLive_SchrackWishlist_Helper_Data extends Mage_Wishlist_Helper_Data {

    public function getAddBaseUrl() {
        return $this->_getUrl('wishlist/index/add');
    }
    public function getRemoveBaseUrl() {
        return $this->_getUrl('wishlist/index/remove');
    }
    
    public function isProductOnList($product) {
        try {
            $wl = Mage::getModel('schrackwishlist/wishlist')->loadByCustomer(Mage::getSingleton('customer/session')->getCustomer());
            return $wl->getIsProductOnList($product);
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError($this->__($e->getMessage()));
            return false;
        }        
    }
}

?>
