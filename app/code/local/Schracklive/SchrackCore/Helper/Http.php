<?php

class Schracklive_SchrackCore_Helper_Http extends Mage_Core_Helper_Http {
    public function parseHttpXForwardedForHeader($header) {
        $ips = array_filter(
                array_map('trim', explode(',', $header)),  // 1. trim the elements of the comma-separated list
                function($e) { return $e !== '127.0.0.1' && preg_match('/^\d+\.\d+\.\d+\.\d+$/', $e) && $e != '172.30.0.222'; } // 2. use only what looks like an external ip-address
        );
        end($ips); // move to last element in an associative array
        $lastKey = key($ips);
        return isset($ips[$lastKey]) ? $ips[$lastKey] : null;
    }

    /**
     * determine whether a given url is part of the schrackiverse or not
     *
     * @param $url
     * @return bool
     */
    public function isSchrackHostUrl($url) {
        $geoip = Mage::getModel('geoip/country');
        return ( preg_match('#//(' . implode('|', array_map(function($h) { return 'www.' . $h; }, $geoip->getShopHosts())) . '|image.schrack.com)/#', $url) );
    }
    
}

?>
