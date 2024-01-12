<?php

/**
 * Wishlist
 *
 * @author c.friedl
 */
class Schracklive_SchrackWishlist_Model_Wishlist extends Mage_Wishlist_Model_Wishlist {    
    
    /**
     * Create new wishlist for customer
     *
     * @param mixed $customer
     * @param string $description description of new wishlist (with a default)
     * @return Mage_Wishlist_Model_Wishlist
     */
    public function create($customer, $description = null)
    {
        die('wishlsitcreate');
        if ($customer instanceof Mage_Customer_Model_Customer) {
            $customer = $customer->getId();
        }
        if ($description === null)
            $description = Mage::helper('adminhtml')->__('My Wishlist'); // there is no __() in models...

        $customerIdFieldName = $this->_getResource()->getCustomerIdFieldName();
        $this->setCustomerId($customer);
        $this->setDescription($description);
        $this->setSharingCode($this->_getSharingRandomCode());
        $this->save();        

        return $this;
    }
    
    /**
     * 
     * @param mixed $customer
     * @param int $wishlistId
     * @return type
     */
     public function loadByCustomerAndId($customer, $wishlistId) {
        if ($customer instanceof Mage_Customer_Model_Customer) {
            $customer = $customer->getId();
        }        
        
        $wishlists = Mage::getModel('wishlist/wishlist')->getCollection()
                ->addFieldToFilter('customer_id', array('=' => $customer))
                ->addFieldToFilter('wishlist_id', array('=' => $wishlistId));
        if (count($wishlists) === 1)
            return $wishlists->getFirstItem();
        else {
            throw new Exception('wishlist could not be found');
            return NULL;
        }
    }    

    public function getIsProductOnList($product) {
        if ($product instanceof Mage_Catalog_Model_Product) {
            $productId = $product->getId();
        } else {
            $productId = (int) $product;      
        }
        $count = $this->getItemCollection()->addFieldToFilter('wishlist_id', $this->getId())
                ->addFieldToFilter('product_id', $productId)
                ->count();
        return ($count === 1);
    }

    public function truncate() {
        foreach ($this->getItemCollection() as $item) {
            $item->delete();
            $item->isDeleted(true);
        }
    }
    
    /**
     * 
     * @param type $product
     * @return Schracklive_SchrackWishlist_Model_Wishlist_Item
     */
    public function getItemByProduct($product) {
        return $this->getItemCollection()
                ->addFieldToFilter('wishlist_id', $this->getId())
                ->addFieldToFilter('product_id', $product->getId())
                ->getFirstItem();
    }

}

?>