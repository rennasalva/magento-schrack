<?php

class Schracklive_SchrackPayment_Helper_Method {
    
    private $_paymentMethod = null;
    
    public function getDefaultPaymentMethod () {
        if ( ! isset($this->_paymentMethod) ) {
            if ( Mage::getStoreConfig('payment/schrackpo/active') ) {
                $this->_paymentMethod = 'schrackpo';
            } else if ( Mage::getStoreConfig('payment/paypal_standard/active') ) {
                $this->_paymentMethod = 'paypal_standard';
            } else {
                $this->_paymentMethod = 'checkmo';
            }
        }
        return $this->_paymentMethod;
    }

    /**
     * @param string $methodName short-name of payment method
     * @return bool whether this method is "external": PayPal or PayUnity
     */
    public function isExternalMethod($methodName = null) {
        $result = false;

        // Check if $methodName is paypal or payunity payment:
        if (stristr($methodName, 'paypal') || stristr($methodName, 'payunitycw')) {
            $result = true;
        }
        return $result;
    }
}

?>
