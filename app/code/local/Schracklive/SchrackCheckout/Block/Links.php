<?php

class Schracklive_SchrackCheckout_Block_Links extends Mage_Checkout_Block_Links {

    public function addCheckoutLink() {
        // hack - hardcoded no checkout link in .ru
        if ( Mage::getSingleton('customer/session')->getCustomer()->isAllowed('customerOrder','order') && Mage::getStoreConfig('schrack/general/country') !== 'ru' ) {
            if (!$this->helper('checkout')->canOnepageCheckout()) {
                return $this;
            }
            $parentBlock = $this->getLayout()->getBlock('top.links'); // ->getBlock('top.links');
            if ($parentBlock && Mage::helper('core')->isModuleOutputEnabled('Mage_Checkout')) {
                $text = $this->__('To Checkout'); // Schrack: distinct link from headline "Checkout"
                $parentBlock->addLink($text, 'checkout', $text, true, array(), 60, null, 'class="top-link-checkout checkout" id="'.$this->__('To Checkout').'"', '', '', false);
            }
            return $this;
        }
    }

    /**
     * Add shopping cart link to parent block
     *
     * @return Mage_Checkout_Block_Links
     */
    public function addCartLink() {
        if (Mage::getSingleton('customer/session')->getCustomer()->isAllowed('customerOrder','order')) {
            $parentBlock = $this->getLayout()->getBlock('top.links'); // ->getBlock('top.links');
            if ($parentBlock && Mage::helper('core')->isModuleOutputEnabled('Mage_Checkout')) {
                $count = $this->helper('checkout/cart')->getSummaryCount();
                if ($count > 0)
                    $text = '<span class="cart"><span class="balloon">' . $count . '</span>' . $this->__('My Cart') . '</span>';
                else
                    $text = '<span class="cart">' . $this->__('My Cart') . '</span>';
                $parentBlock->addLink($text, 'checkout/cart', $text, true, array(), 50, null, 'class="MyCart shopping-cart" id="'.$this->__('My Cart').'"');
            }
        }
        return $this;
    }

    public function addQuickaddLink()
    {   
        $parentBlock = $this->getLayout()->getBlock('top.links'); // ->getBlock('top.links');
        if ($parentBlock && Mage::helper('core')->isModuleOutputEnabled('Mage_Checkout')) {
            $text = $this->__('Quickadd');
            $parentBlock->addLink($text, 'checkout/cart', $text, true, array(), 51, null, 'class="quickadd quick-add" id="'.$this->__('Quickadd').'"');
        }
        return $this;
    }

}
