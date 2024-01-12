<?php

/**
 * Api
 *
 * @author c.friedl
 */
class Schracklive_SchrackCheckout_Model_Cart_Api_V2 extends Mage_Checkout_Model_Cart_Api_V2 {
    /**
     * Create new quote for shopping cart
     *
     * @param int|string $store
     * @return int
     */
    public function createForCustomer($email, $password, $storeId = null)
    {
        if ($storeId === null) {
            $storeId = Mage::app()->getDefaultStoreView()->getId();
        }
                    
        $session = Mage::getModel('customer/session');
        $session->setStoreId($storeId);

        $success = $session->login($email, $password);
        
        if ($success) {

            try {
                /*@var $quote Mage_Sales_Model_Quote*/
                $quote = Mage::getModel('sales/quote');
                $quote->setStoreId($storeId)
                        ->setIsActive(true)
                        ->setIsMultiShipping(false)
                        ->setCustomerId($session->getCustomerId())
                        ->save();
            } catch (Mage_Core_Exception $e) {
                $this->_fault('create_quote_fault', $e->getMessage());
            }
            return (int) $quote->getId();
        } else {
            $this->_fault('login_fault');
            return null;
        }
    }
}

?>
