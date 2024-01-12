<?php

// define('SIMULATE_CHECKOUT_NOT_ALLOWED',1);

class Schracklive_GeoIP_Helper_Data extends Openstream_GeoIP_Helper_Data {

    const COOKIE_NAME_SEEN_WARNING = 'gsw';
    const COOKIE_NAME_WANTS_REDIRECT = 'gwd';
    const COOKIE_LIFETIME = 1209600; // 14 days;

    /**
     * @var string[][] Host names to check
     */
    private $hostsName = ["RU" => ['(www\.)?schrack-technik.ru', 'test-ru.schrack.com']];

	public function generateKey($login, $remoteAddress) {
		return base64_encode($login.":".hash('sha256', 'plan2net:'.$login.':'.$remoteAddress));
	}
    
    public function parseHttpXForwardedForHeader($header) {
        $ips = array_filter(
                array_map(trim, explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])),  // 1. trim the elements of the comma-separated list
                function($e) { return $e !== '127.0.0.1' && preg_match('/^\d+\.\d+\.\d+\.\d+$/', $e) && !preg_match('/^192\.168\./', $e); }); // 2. use only what looks like an external ip-address
        end($ips); // move to last element in an associative array
        $lastKey = key($ips);
        return isset($ips[$lastKey]) ? $ips[$lastKey] : null;
    }

    public function maySeePrices() {
        $data = $this->_getCheckData();
        $geoIp = Mage::getModel('geoip/country');
        // Check if user is not logged in
        if (Mage::getSingleton('customer/session')->isLoggedIn() != 1) {
            if ( defined('DEBUG') ) {
                return true; /// TODO debugging remove me
            }
//            if (Mage::getStoreConfig('schrack/general/redirectGeoIP') !== '1') {
//                return true;
//            }

            if ( $geoIp->hasRedirectWarning() ) {
                return ( $geoIp->maySeePrices($data['serverName'], $data['remoteAddr']) || Mage::getSingleton('customer/session')->isLoggedIn() );
            } else {
                return $geoIp->maySeePrices($data['serverName'], $data['remoteAddr']);
            }
        } else { // user is logged in
            return true;
       }
    }

    /**
     * Retrieve Country according to Domain name
     * @param $serverName
     * @return int|string|null
     */
    private function getCountryByDomainName($serverName) {
        foreach ($this->hostsName as $country => $regexes) {
            foreach ( $regexes as $regex ) {
                if ( preg_match('/^' . $regex . '$/i', $serverName) ) {
                    return $country;
                }
            }
        }
        return null;
    }

    public function mayPerformCheckout() {
        if ( defined('SIMULATE_CHECKOUT_NOT_ALLOWED') ) {
            return false; /// TODO debugging remove me
        }
        if (Mage::getStoreConfig('schrack/general/redirectGeoIP') !== '1')
            return true;

        $data = $this->_getCheckData();
        $geoIp = Mage::getModel('geoip/country');
        return $geoIp->mayPerformCheckout($data['serverName'], $data['remoteAddr']);
    }

    private function _getCheckData() {
        $request = Mage::app()->getRequest();
        $coreHelper = Mage::helper('schrackcore/http'); // core http helper for using what's otherwise known as $_SERVER

        // heuristic for divining correct header field behind proxy bars
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $remoteAddr = $coreHelper->parseHttpXForwardedForHeader($_SERVER['HTTP_X_FORWARDED_FOR']);
        } else {
            $remoteAddr = $coreHelper->getRemoteAddr();
        }

        return array('serverName' => $coreHelper->getHttpHost(), 'remoteAddr' => $remoteAddr);
    }

    /**
     * @param string $name name of cookie
     * @return boolean whether cookie was seen
     */
    private function _handleCookie($name, $value='1') {
        $cookieModel = Mage::getModel('core/cookie');
        $rv = $cookieModel->get($name);

        if ( $rv ) {
            $cookieModel->renew($name, self::COOKIE_LIFETIME, '/');
        } else {
            $cookieModel->set($name, $value, self::COOKIE_LIFETIME, '/');
        }

        return $rv;
    }

    public function handleSeenWarningCookie() {
        return $this->_handleCookie(self::COOKIE_NAME_SEEN_WARNING);
    }

    /**
     * @return boolean whether cookie was seen
     */
    public function handleWantsRedirectCookie($value = '1') {
        return $this->_handleCookie(self::COOKIE_NAME_WANTS_REDIRECT, $value);
    }

    public function createWarningBlock() {
        return $this->getLayout()
            ->createBlock('Mage_Core_Block_Html', '', array('template' => 'geoip/redirect_warning.phtml'));
    }


    public function getDeterminedGeoip($checkExcludedUris = true) {
        $geoIp = Mage::getModel('geoip/country');
        $geoIp->setCheckExcludedUris($checkExcludedUris);
        if (Mage::app()->getRequest()->getModuleName() == 'api') {
            return $geoIp;
        }

        $isLoggedIn = Mage::getSingleton('customer/session')->isLoggedIn();

        if ( $isLoggedIn || Mage::getStoreConfig('schrack/general/redirectGeoIP') !== '1' ) {
            return $geoIp; // no redirection
        }

        $request = Mage::app()->getRequest();
        $assumedIpCountry = $request->getParam('assumedIpCountry');
        $remoteAddr = $this->calcRemoteAddr();

        if (!$geoIp->isInBackstage($remoteAddr)) { // disallow testwise country-assumption for non-backstage ips
            $assumedIpCountry = null;
        }

        $coreHelper = Mage::helper('schrackcore/http');
        $geoIp->determineRedirection( $coreHelper->getHttpHost(),  $coreHelper->getRequestUri(), $remoteAddr,
            $coreHelper->getHttpUserAgent(), $request->isSecure(), $assumedIpCountry);

        return $geoIp;
    }

    /**
     * query the config flag, but also whether we are logged in and even want to use geoip
     * @return bool
     */
    public function hasRedirectWarning() {
        if ( Mage::app()->getRequest()->getModuleName() == 'api' ) { return false; } // NEVER EVER DELETE THIS, because it keeps us from creating many useless sessions
        $isLoggedIn = Mage::getSingleton('customer/session')->isLoggedIn();
        if ( $isLoggedIn ) {
            return false;
        }
        if ( Mage::getStoreConfig('schrack/general/redirectGeoIP') !== '1' ) {
            return false;
        }
        $geoIp = Mage::getModel('geoip/country');
        return $geoIp->hasRedirectWarning();
    }

    public function shouldShowRedirectWarning() {
        $geoIp = Mage::getModel('geoip/country');
        return ( $geoIp->hasRedirectWarning() && !$this->handleSeenWarningCookie() );
    }


    /**
     * @return null|string
     */
    public function calcRemoteAddr() {  // heuristic for divining correct header field behind proxy bars
        // $email = Mage::getSingleton('customer/session')->getCustomer()->getEmail();
        $coreHelper = Mage::helper('schrackcore/http');
        if ( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && ($res = $coreHelper->parseHttpXForwardedForHeader($_SERVER['HTTP_X_FORWARDED_FOR'])) ) {
            // nothing to do...
            // Mage::log("### $email calcRemoteAddr: $res from HTTP_X_FORWARDED_FOR",null,'backstage_ip.log');
        } else {
            $res = $coreHelper->getRemoteAddr();
            // Mage::log("### $email calcRemoteAddr: $res from REMOTE_ADDR",null,'backstage_ip.log');
        }
        /*
        foreach (getallheaders() as $name => $value) {
            Mage::log("### HEADERS: $name => |$value|",null,'backstage_ip.log');
            echo "$name: $value\n";
        }
        */
        return $res;
    }

    public function getZipCodeRegexes() {
        return json_encode(Mage::getModel('geoip/country')->getZipCodeRegexes());
    }

    public function isRemoteAddrInBackstage() {
        return Mage::getModel('geoip/country')->isInBackstage($this->calcRemoteAddr());
    }
}

?>
