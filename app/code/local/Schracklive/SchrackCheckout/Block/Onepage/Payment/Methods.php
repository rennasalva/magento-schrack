<?php

class Schracklive_SchrackCheckout_Block_Onepage_Payment_Methods extends Mage_Checkout_Block_Onepage_Payment_Methods {

    protected function _canUseMethod ( $method ) {
        if ( Mage::getStoreConfig('schrack/shop/enable_limit_depended_schrack_purchase_order_in_checkout') ) {
            $methodCode =  $method->getCode();
            if ( $methodCode == 'schrackpo' ) {
                if ( $customer = Mage::getSingleton('customer/session')->getCustomer() ) {
                    $account = $customer->getAccount();
                    if ($account) {
                        $limitWeb = $account->getLimitWeb();
                        if ( $limitWeb >= 2 ) {
                            return parent::_canUseMethod($method);
                        }
                    }
                }
                return false;
            }
        }
        return parent::_canUseMethod($method);
    }

    /*
     * WARNING overwrites grandparents method!
     * 
     */

    public function getMethods() {
        $methods = $this->getData('methods');
        if ($methods === null) {
            $quote = $this->getQuote();
            $store = $quote ? $quote->getStoreId() : null;
            $methods = array();
            foreach ($this->helper('payment')->getStoreMethods($store, $quote) as $method) {
                if ($this->_canUseMethod($method) && $method->isApplicableToQuote(
                    $quote,
                    Mage_Payment_Model_Method_Abstract::CHECK_ZERO_TOTAL
                )) {
                    $this->_assignMethod($method);
                    $methods[] = $method;
                }
            }
            uasort($methods, array('Schracklive_SchrackCheckout_Block_Onepage_Payment_Methods', 'cmp_methods'));
            $this->setData('methods', $methods);
        }
        return $methods;
    }
    
    public function cmp_methods($m1, $m2) {        
        if (isset($m1['sort_order']) && isset($m2['sort_order']) && intval($m1['sort_order']) > 0 && intval($m2['sort_order']) > 0) {
            return (intval($m1['sort_order']) > intval($m2['sort_order']) ? 1 : (intval($m1['sort_order']) < intval($m2['sort_order']) ? -1 : 0));
        } else {
            return (isset($m1['sort_order']) && intval($m1['sort_order']) > 0 ? -1 : (isset($m2['sort_order']) && intval($m2['sort_order']) > 0 ? 1 : 0));
        }
            
    }

}
