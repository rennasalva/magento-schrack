<?php

/**
 * Internally track customers as to what product they visited, aggregated per session
 * 
 */
class Schracklive_SchrackCustomer_Helper_Tracking extends Mage_Core_Helper_Abstract {
    const COOKIE_NAME = 'ls';
    public function track($session, $product) {
        if (Mage::getStoreConfig('schrack/customer/useTracking') === '1' && $this->maySetCookie()) {            
            $trackingSessionId = $this->createTrackingSessionId();
            $sku = $product->getSku();
            $oldTracking = Mage::getModel('schrackcustomer/tracking')->loadLastBySessionId($trackingSessionId);
            $tracking = Mage::getModel('schrackcustomer/tracking');
            if ($session->isLoggedIn()) {
                $customer = $session->getCustomer();
                $schrackWwsCustomerId = $customer->getSchrackWwsCustomerId();
                $schrackWwsContactNumber = $customer->getSchrackWwsContactNumber();
                $tracking->setSchrackWwsCustomerId($schrackWwsCustomerId);
                $tracking->setSchrackWwsContactNumber($schrackWwsContactNumber);
            } else if ($oldTracking) {
                $tracking->setSchrackWwsCustomerId($oldTracking->getSchrackWwsCustomerId());
                $tracking->setSchrackWwsContactNumber($oldTracking->getSchrackWwsContactNumber());
            }
            $tracking->setSku($sku);
            $tracking->setSessionId($trackingSessionId);
            $tracking->save();
        }
        return $this;
    }

    public function trackAcceptOffer ( $order, $case, $hasQtychanges = false, $wwsError = null ) {
        // setBaseSubtotal
        if ( ! Mage::getStoreConfig('schrack/customer/useTracking') ) {
            return $this;
        }
        $session = Mage::getSingleton('customer/session');
        if ( ! $session || ! $session->isLoggedIn() ) {
            Mage::log('Not logged in or invalid session', null, 'accept_offer.log');
            throw new Exception('Not logged in or invalid session'); // SNH
        }
        $model = Mage::getModel('schrackcustomer/acceptoffertracking');
        $model->setCase($case);
        $model->setOfferNumber($order->getSchrackWwsOfferNumber());
        $model->setOrderNumber($order->getSchrackWwsOrderNumber());
        $model->setNetTotal($order->getSubtotal());
        $model->setHasQtyChanges($hasQtychanges ? 1 : 0);
        $customer = $session->getCustomer();
        $model->setCustomerId($customer->getSchrackWwsCustomerId());
        $model->setContactNumber($customer->getSchrackWwsContactNumber());
        if ( $wwsError ) {
            $model->setWwsError($wwsError);
        }
        $model->save();
    }
    
    public function maySetCookie() {
        if (Mage::getStoreConfig('schrackdev/development/test') === '1')
            return true;
        $accept = Mage::getModel('core/cookie')->get('cc_cookie_accept');
        $decline = Mage::getModel('core/cookie')->get('cc_cookie_decline');
        return ($accept === 'cc_cookie_accept' || (!strlen($accept) && !strlen($decline)));
    }
    
    public function createTrackingSessionId() {
        $id = $this->readTrackingSessionId();
        if (!strlen($id)) {
            $id = Mage::helper('schrack/tokens')->createTokenString(self::COOKIE_NAME);
        }
        
        Mage::getModel('core/cookie')->set('ls', $id, 3600*24*365, '/', null, false, true);
        return $id;
    }
    
    public function readTrackingSessionId() {
        return Mage::getModel('core/cookie')->get(self::COOKIE_NAME);
    }
}
