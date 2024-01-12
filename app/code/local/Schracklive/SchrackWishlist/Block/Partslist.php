<?php

/**
 * Partslist
 *
 * @author c.friedl
 */
class Schracklive_SchrackWishlist_Block_Partslist extends Schracklive_SchrackWishlist_Block_Partslist_Abstract {
    /*
     * Constructor of block 
     */
    public function _construct()
    {
        parent::_construct();
    } 
    
    
   
    
    /**
     * Preparing global layout
     *
     * @return Mage_Wishlist_Block_Customer_Wishlist
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $headBlock = $this->getLayout()->getBlock('head');
        if ($headBlock) {
            $headBlock->setTitle($this->__('My Partslist'));
        }
    }
    
    /**
     * Returns default description to show in textarea field
     *
     * @param Schracklive_SchrackWishlist_Model_Partslist_Item $item
     * @return string
     */
    public function getCommentValue(Schracklive_SchrackWishlist_Model_Partslist_Item $item)
    {
        return $this->hasDescription($item) ? $this->getEscapedDescription($item) : Mage::helper('schrackwishlist/partslist')->defaultCommentString();
    }
    
     protected function getDirectionImageSkinUrl($order) {
        if ($this->getSortOrder() === $order) {
            if ($this->getDirection() === 'asc')
                return $this->getSkinUrl('images/sort-up.png');
            else
                return $this->getSkinUrl('images/sort-down.png');
        } else
            return $this->getSkinUrl('images/sort-inactive.png');
    }
    
    
    protected function getSortOrder() {
        return $this->getRequest()->getParam('sort_order', 'updated_at');
    }
    protected function getDirection() {
        if (strlen($this->getRequest()->getParam('sort_order')))
            return $this->getRequest()->getParam('direction', 'asc');
        else
            return $this->getRequest()->getParam('direction', 'desc');
    }
    
    
    protected function getPartslists() {
        $sortOrder = $this->getSortOrder();
        $direction = $this->getDirection() === 'desc' ? 'DES' : 'ASC';
        
        return Mage::getModel('schrackwishlist/partslist')->getCollection()
                ->addFieldToFilter('customer_id', array('=' => Mage::getSingleton('customer/session')->getCustomer()->getId()))
                ->addFieldToFilter('is_visible', array('=' => 1))
                ->addOrder($sortOrder, $direction);
    }
}

?>
