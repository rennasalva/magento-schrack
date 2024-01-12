<?php

class Orcamultimedia_Sapoci_Model_Checkout_Observer extends Mage_Checkout_Model_Observer {

    /**
     * Load data for customer quote and merge with current quote
     *
     * @return Mage_Checkout_Model_Session
     */
    public function loadCustomerQuote(){

        if(!Mage::helper('sapoci')->getIsPunchout())
            return parent::loadCustomerQuote();

        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $quote->setCustomer(Mage::getSingleton('customer/session')->getCustomer())
              ->setTotalsCollectedFlag(false)
              ->collectTotals()
              ->save();
              
        return;
    }

}