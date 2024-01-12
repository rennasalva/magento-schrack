<?php

class Schracklive_SchrackCheckout_Block_Onepage extends Mage_Checkout_Block_Onepage {

    public function getSteps() {
        $steps = array();
        $geoipHelper = Mage::helper('geoip');
        if ( !$this->isCustomerLoggedIn() ) {
            if ($geoipHelper->mayPerformCheckout()) {
                $steps['login'] = $this->getCheckout()->getStepData('login');
            } else {
                // Alternative route -> only for SA and RU:
                $steps['address'] = $this->getCheckout()->getStepData('address');
                $steps['review'] = $this->getCheckout()->getStepData('review');
                return $steps; //// bailing out, no other steps needed
            }
        } elseif ( !$geoipHelper->mayPerformCheckout() ) {
            // Alternative route -> only for SA and RU:
            //$steps['shipping'] = $this->getCheckout()->getStepData('shipping'); // Wrong route!!!
            $steps['address'] = $this->getCheckout()->getStepData('address');
            $steps['review'] = $this->getCheckout()->getStepData('review');
            return $steps; //// bailing out, no other steps needed
        }

        $steps['shipping_method'] = $this->getCheckout()->getStepData('shipping_method');

        $customer = $this->getCustomer();
        if (!$this->isCustomerLoggedIn() || !($customer->isContact() || $customer->isProspect() || $customer->isSystemContact())) {
                $steps['billing'] = $this->getCheckout()->getStepData('billing');
        }

        $stepCodes = array('shipping', 'payment', 'review');

        foreach ($stepCodes as $step) {
                $steps[$step] = $this->getCheckout()->getStepData($step);
        }

        return $steps;
    }

    public function getActiveStep() {
        return $this->getSchrackActiveStep();
    }

    public function getSchrackActiveStep ( $isAppcheckout = false ) {
        $default = $isAppcheckout ? 'shipping' : 'shipping_method';
        $res = $default;
        $geoipHelper = Mage::helper('geoip');
        if ( $this->isCustomerLoggedIn() ) {
            if ( !$geoipHelper->mayPerformCheckout() ) {
                // Alternative route -> only for SA and RU:
                $res = 'address';
            } else {
                $customer = $this->getCustomer();
                if ( $customer->isContact() || $customer->isProspect() || $customer->isSystemContact() ) {
                    // $res = 'shipping'; old system --> this is supposed to be changed in new design!!
                    $res = $default;
                } else {
                    $res = 'billing';
                }
            }
        } else {
            if ( $geoipHelper->mayPerformCheckout() ) {
                $res = 'login';
            } else {
                $res = 'address';
            }
        }
        return $res;
    }

}