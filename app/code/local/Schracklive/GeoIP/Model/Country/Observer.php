<?php

class Schracklive_GeoIP_Model_Country_Observer {
    private $_coreHelper;

    public function __construct() {
        $this->_coreHelper = Mage::helper('schrackcore/http'); // core http helper for using what's otherwise known as $_SERVER
    }

    /**
     * if the user is not logged in, we will redirect them to the proper shop
     * - otherwise, we'll leave the request untouched
     * @param Varien_Event_Observer $observer
     */
    public function determineRedirection($observer) {
        if (Mage::app()->getRequest()->getModuleName() == 'api') return; // NEVER EVER DELETE THIS, because it keeps us from creating many useless sessions        
        $helper = Mage::helper('geoip');
        $geoIp = $helper->getDeterminedGeoip();
        if (  $geoIp->getNeedsRedirect() ) {
            $remoteAddr = $helper->calcRemoteAddr();
            Mage::log('GeoIP Observer: redirect needed, from (' . $remoteAddr . ', ' . $this->_coreHelper->getHttpHost() . ', ' . $this->_coreHelper->getRequestUri() . ') to ' . $geoIp->getRedirectUrl());

            if ( $helper->shouldShowRedirectWarning() ) {
                $this->_addRedirectWarningToSession($this->_coreHelper->getHttpHost(), Mage::getUrl('geoip/redirect/index'));
            } else if ( $helper->handleWantsRedirectCookie('0') === '1' ) {
                $logModel = Mage::getModel('geoip/log');
                $logModel->log($this->_coreHelper->getHttpHost(), $this->_coreHelper->getRequestUri(), $geoIp->getUserIpCountry(), $geoIp->getRedirectCountry(), $remoteAddr, $this->_coreHelper->getHttpUserAgent());
                $response = Mage::app()->getResponse();
                $response->setRedirect($geoIp->getRedirectUrl());
                $response->sendResponse();
                die;
            }
        }
    }

    private function _addRedirectWarningToSession($requestedUrl, $redirectUrl) {
        Mage::getSingleton('core/session')->addError(
            sprintf(Mage::helper('core')->__('On %s, only customers from this country are allowed. Please click <a href="%s">here</a> to be redirected.'), $requestedUrl, $redirectUrl)
        );
    }
}