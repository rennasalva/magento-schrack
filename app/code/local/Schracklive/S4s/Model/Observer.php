<?php

class Schracklive_S4s_Model_Observer {

    public function s4sUpdate ( $observer ) {
        /** @var Varien_Event_Observer $observer */
        if ( ! Mage::getStoreConfig('schrack/s4s/user_record_update_url') ) {
            return; // s4s not connected yet
        }
        $customer = $observer->getData('customer');
        $helper = Mage::helper('s4s');
        $helper->sendUpdateToS4s($customer);
    }
}