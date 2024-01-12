<?php

class Schracklive_Schrack_ProductdownloadsController extends Mage_Core_Controller_Front_Action {

    public function indexAction () {
        $request = $this->getRequest();
        $sku = $request->getParam('a');
        $baseUrl = Mage::getSingleton('core/session')->getProductDownloadsBaseUrl();
        if ( is_string($baseUrl) && $baseUrl > '' ) {
            $this->buildUrlAndRedirect($baseUrl,$sku);
        } else {
            $geoIpModel = Mage::getModel('geoip/country');
            $ipCountry = $geoIpModel->getCountry();
            if ( ! is_string($ipCountry) || trim($ipCountry) == '' ) {
                $ipCountry = 'COM';
            }
            Mage::register('sku',$sku);
            Mage::register('ipCountry',$ipCountry);
            $this->loadLayout();
            $this->renderLayout();
        }
    }

    public function postAction () {
        $request = $this->getRequest();
        $baseUrl = $request->getParam('base_url');
        $isRemember = $request->getParam('remember') == '1';
        $sku = $request->getParam("sku");
        if ( $isRemember ) {
            Mage::getSingleton('core/session')->setProductDownloadsBaseUrl($baseUrl);
        }
        $this->buildUrlAndRedirect($baseUrl,$sku);
    }

    private function buildUrlAndRedirect ( $baseUrl, $sku ) {
        $url = $baseUrl . "/sd/sd?a=$sku&focusDownloads=1";
        $this->_redirectUrl($url);
    }

}

