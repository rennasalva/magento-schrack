<?php

/**
 * Api
 *
 * @author c.friedl
 */
class Schracklive_GeoIP_Model_Api_V2 extends Mage_Api_Model_Resource_Abstract {
    /**
     * Query geoIP module for redirecting user to the correct country
     *
     * @param string $userIP
     * @param string $serverName
     * @param $localUri
     * @return string|null country code for redirection (null if no redirection needed)
     */
	public function getRedirectCountry($userIP, $serverName, $localUri, $userAgent = null) {
        $coreHelper = Mage::helper('schrackcore/http'); // core http helper for using what's otherwise known as $_SERVER
		/* @var $geoIP Schracklive_GeoIP_Model_Country */
        $geoIP = Mage::getSingleton('geoip/country');
        $geoIP->determineRedirection($serverName, $localUri, $userIP, $userAgent, false);
        if ( $geoIP->getNeedsRedirect() && ! $this->hasRedirectWarning() ) {
            Mage::log("GeoIP API getRedirectCountry($userIP, $serverName, $localUri): bounce to " . $geoIP->getRedirectCountry());
            $logModel = Mage::getModel('geoip/log');            
            $logModel->log($serverName, $localUri, $geoIP->getUserIpCountry(), $geoIP->getRedirectCountry(), $userIP, $userAgent);
            return $geoIP->getRedirectCountry();
        } else {
            return null;
        }
    }
    
    /**
     * Query geoIP module for redirecting user to the correct shop url
     *
     * @param string $userIP
     * @param string $serverName
     * @param $localUri
     * @return string/null url for redirection (null if no redirection needed)
     */
    public function getRedirectUrl($userIP, $serverName, $localUri, $userAgent = null) {
        /* @var $geoIP Schracklive_GeoIP_Model_Country */
        $geoIP = Mage::getSingleton('geoip/country');
        $geoIP->determineRedirection($serverName, $localUri, $userIP, $userAgent, false);
        if ( $geoIP->getNeedsRedirect() && ! $this->hasRedirectWarning() ) {
            $coreHelper = Mage::helper('schrackcore/http'); // core http helper for using what's otherwise known as $_SERVER
            Mage::log("GeoIP API getRedirectUrl($userIP, $serverName, $localUri): bounce to " . $geoIP->getRedirectUrl());
            $logModel = Mage::getModel('geoip/log');
            $logModel->log($serverName, $localUri, $geoIP->getUserIpCountry(), $geoIP->getRedirectCountry(), $userIP, $userAgent);
            return $geoIP->getRedirectUrl();
        } else {
            return null;
        }
    }

    public function hasRedirectWarning() {
        $geoIP = Mage::getSingleton('geoip/country');
        return $geoIP->hasRedirectWarning();
    }
}

?>
