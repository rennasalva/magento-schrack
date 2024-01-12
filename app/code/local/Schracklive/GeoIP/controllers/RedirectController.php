<?php

class Schracklive_GeoIP_RedirectController extends Mage_Core_Controller_Front_Action {
    public function indexAction() {
        $helper = Mage::helper('geoip');
        $geoIp = $helper->getDeterminedGeoip(false);
        $override = $this->getRequest()->getParam('o'); // for debugging
        if ( $geoIp->getNeedsRedirect() || $override === 'yes' ) {
            $helper->handleWantsRedirectCookie();
            return $this->_redirectUrl($geoIp->getRedirectUrl());
        } else {
            return $this->_redirect('/');
        }
    }

    public function warnAction() {
        $helper = Mage::helper('geoip');
        $geoIp = $helper->getDeterminedGeoip(false);
        $override = $this->getRequest()->getParam('o'); // for debugging
        if (  $geoIp->getNeedsRedirect() || $override === 'yes' ) {
            if ( $helper->shouldShowRedirectWarning() || $override === 'yes' ) {
                $this->loadLayout();
                $block = $this->getLayout()->getBlock('geoip_redirect_warning');
                $block->setData('redirect_url', Mage::getUrl('geoip/redirect'));
                $block->setData('requested_url', $this->getRequest()->getServer('HTTP_HOST'));
                $block->setData('cookie_name', Schracklive_GeoIP_Helper_Data::COOKIE_NAME_SEEN_WARNING);
                $block->setData('cookie_expires', date('r', time() + Schracklive_GeoIP_Helper_Data::COOKIE_LIFETIME));
                $block->setTemplate('geoip/redirect_warning.phtml');
                $html = $block->toHtml();

                header('Content-Type:text/html; charset=UTF-8');
                die($html);
            }
        }

        return '';
    }
}