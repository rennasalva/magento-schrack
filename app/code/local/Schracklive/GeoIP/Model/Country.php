<?php

/**
 * Description of Country
 *
 */
class Schracklive_GeoIP_Model_Country extends Openstream_GeoIP_Model_Country {
    private $_requestedCountry;
    private $_redirectUrl;
    private $_redirectCountry;
    private $_needsRedirect;
    private $_isRedirectEnabled; // we will not redirect if the global config is switched off
    private $_environment; // host regexes will depend upon environment, if it is set and !== 'production'    
    private $_userIpCountry;
    private $_checkExcludedUris;

    private $_hostRegexes = array( // default (production)
        'AT'  => array('(www\.)?schrack.at',         'test-at.schrack.com'),
        'DE'  => array('(www\.)?schrack-technik.de', 'test-de.schrack.com'),
        'COM' => array('(www\.)?schrack.com',        'test-com.schrack.com'),
        'BA'  => array('(www\.)?schrack.ba',         'test-ba.schrack.com'),
        'BE'  => array('(www\.)?schrack.be',         'test-be.schrack.com'),
        'BG'  => array('(www\.)?schrack.bg',         'test-bg.schrack.com'),
        'CZ'  => array('(www\.)?schrack.cz',         'test-cz.schrack.com'),
        'HR'  => array('(www\.)?schrack.hr',         'test-hr.schrack.com'),
        'HU'  => array('(www\.)?schrack.hu',         'test-hu.schrack.com'),
        'NL'  => array('(www\.)?schrack-technik.nl', 'test-nl.schrack.com'),
        'PL'  => array('(www\.)?schrack.pl',         'test-pl.schrack.com'),
        'RO'  => array('(www\.)?schrack.ro',         'test-ro.schrack.com'),
        'RS'  => array('(www\.)?schrack.rs',         'test-rs.schrack.com'),
        'RU'  => array('(www\.)?schrack-technik.ru', 'test-ru.schrack.com' /*, 'schrack-at.schrack.lan' just for testing*/ ),
        'SA'  => array('(www\.)?schrack.sa',         'test-sa.schrack.com'),
        'SI'  => array('(www\.)?schrack.si',         'test-si.schrack.com'),
        'SK'  => array('(www\.)?schrack.sk',         'test-sl.schrack.com'),
        'HMMMMMMMMMM' => array('(www\.)?schrack(-technik)?.(com|ru|co\.rs)', 'x') // TODO wtf
    );

    private $_shopLocalUris = array(
        'schrack.at' => '/shop',
        'schrack-technik.de' => '/shop',
        'schrack.com' => '/shop',
        'schrack.ba' => '/trgovina',
        'schrack.be' => '/shop',
        'schrack.bg' => '/shop',
        'schrack.cz' => '/eshop',
        'schrack.hr' => '/trgovina',
        'schrack.hu' => '/shop',
        'schrack.pl' => '/sklep',
        'schrack.ro' => '/comenzi',
        'schrack-technik.ru' => '/shop',
        'schrack.rs' => '/prodavnica',
        'schrack.sa' => '/shop',
        'schrack.si' => '/trgovina',
        'schrack.sk' => '/eshop',
    );

    private $_maySeePrices       = array(
        'COM' => array('AD', 'FI', 'FR', 'GB', 'IE', 'IT', 'LU', 'MT', 'MC', 'PT', 'SM', 'ES', 'SE', 'VA', 'TW', 'EE'),
        'RU'  => array(),
// TODO: investigate why NL customers in NL dont see prices after implementation
        'NL'  => array('NL'),  //TODO: #2022111410000349 #2022103110000096
        'DE'  => array('DE', 'IT'),
        'BE' => array('BE'),
        'AT' => array('AT'),
        'CH' => array('CH')
        //'AT'  => array('AT')  einige kunden (linz) sehen keine preise.
    );


    //  COM -> add in Magento Admin Panel -> System -> General -> Countries Options -> Allow Countries
    private $_mayPerformCheckout = array(
        'COM' => array('AD', 'FI', 'FR', 'GB', 'IE', 'IT', 'LU', 'MT', 'MC', 'PT', 'SM', 'ES', 'SE', 'VA', 'TW', 'EE'),
        'RU'  => array(),
        'SA'  => array()
    );

    private $_backstagePasses = array(
            // Admission to all areas
            // "localhost";
            array('172.30.0.0', 16), // wav, intern, etc
            array('127.0.0.1', 32),
            array('10.31.0.0', 16),
            array('10.0.2.2', 32), // c.friedl localmachine to vm
            array('192.168.0.0', 16), // VPN ?
            // array('172.30.0.0', 24),
            array('86.32.126.3', 32),  // Kutschker
            // SUCHMASCHINEN???
            array('91.183.41.183', 32), // proxy BE
            array('78.133.208.194', 32), // proxy PL
            array('194.228.101.178', 32), // proxy CZ
            array('195.146.142.202', 32), // proxy SK
            array('195.56.121.42', 32), // proxy HU
            array('62.77.227.82', 32), // proxy HU
            // array('82.77.63.5', 32), // proxy RO
            array('86.35.126.6', 32), // proxy RO_NEU
            // Proxy BG _ist_ Proxy VIE
            array('91.221.217.78', 32), // proxy RS
            // Proxy BA geht ueber Proxy VIE
            array('85.114.43.234', 32), // proxy HR
            array('89.143.14.18', 32), // proxy SI
            array('80.122.98.154', 29),  // Plan2net
            array('213.205.42.1', 32), // Crawler (oder boeser Bot, keine Ahnung)
            array('93.83.155.16', 29),  // Schrack technik, SEY
            array('31.13.222.36', 28),  // Proxy BG
            array('188.118.228.228', 32), // brace
            array('84.114.89.16', 32), // brace
    );

    private $_excludedRemoteAddrRegexes = array( // excluded because geoip thought they are .com
        '194\.39\.', // bosch.de
        '194\.246\.', // benteler.de
    );

    private $_excludedLocalUriRegexes = array(
        '/api/', '/customer/account/loginByToken/', '/geoip/', '/typo3/', '^/magento-service/',
        '^/(shop|eshop|trgovina|sklep|prodavnica|comenzi)/paypal', '^/(shop|eshop|trgovina|sklep|prodavnica|comenzi)/mobile',
        '^/(shop|eshop|trgovina|sklep|prodavnica|comenzi)/catalog/product/getAvailability',
        '^/(shop|eshop|trgovina|sklep|prodavnica|comenzi)/customer/account', '^/(shop|eshop|trgovina|sklep|prodavnica|comenzi)/account',
    );

    private $_crawlerIpRegexes = array(
        '66\.249\.78\.',
        '89\.143\.229\.',
        '131\.253\.',
        '157\.55\.33\.',
        '157\.55\.',
        '178\.154\.',
        '193\.77\.14',
        '199\.30\.16\.143',
        '65\.55\.',
    );


    private $_zipCodeRegexes = array(
        'at' => '^\d{4,4}$',
        'ba' => '^\d+$',
        'be' => '^\d+$',
        'bg' => '^\d+$',
        'cz' => '^\d\d\d \d\d$',
        'de' => '^\d{5,5}$',
        'hr' => '^\d+$',
        'pl' => '^\d{1,2}-\d{1,3}$',
        'rs' => '^\d+$',
        'ro' => '^\d+$',
        'si' => '^\d+$',
        'sk' => '^\d+$',
    );

    private $_telCountryCodeRegexes = array(
        'at' => '^[1-9]{1,3}$',
        'ba' => '^[1-9]{1,3}$',
        'be' => '^[1-9]{1,3}$',
        'bg' => '^[1-9]{1,3}$',
        'co' => '^[1-9]{1,3}$',
        'cz' => '^[1-9]{1,3}$',
        'de' => '^[1-9]{1,3}$',
        'hr' => '^[1-9]{1,3}$',
        'hu' => '^[1-9]{1,3}$',
        'pl' => '^[1-9]{1,3}$',
        'ro' => '^[1-9]{1,3}$',
        'rs' => '^[1-9]{1,3}$',
        'ru' => '^[1-9]{1,3}$',
        'sa' => '^[1-9]{1,3}$',
        'si' => '^[1-9]{1,3}$',
        'sk' => '^[1-9]{1,3}$',
    );

    private $_telCityCodeRegexes = array(
        'at' => '^[0-9]{1,5}$',
        'ba' => '^[0-9]{1,5}$',
        'be' => '^[0-9]{1,5}$',
        'bg' => '^[0-9]{1,5}$',
        'co' => '^[0-9]{1,5}$',
        'cz' => '^[0-9]{1,5}$',
        'de' => '^[0-9]{1,5}$',
        'hr' => '^[0-9]{1,5}$',
        'hu' => '^[0-9]{1,5}$',
        'pl' => '^[0-9]{1,5}$',
        'ro' => '^[0-9]{1,5}$',
        'rs' => '^[0-9]{1,5}$',
        'ru' => '^[0-9]{1,5}$',
        'sa' => '^[0-9]{1,5}$',
        'si' => '^[0-9]{1,5}$',
        'sk' => '^[0-9]{1,5}$',
    );

    private $_telTelphoneRegexes = array(
        'at' => '^[0-9]{1,10}$',
        'ba' => '^[0-9]{1,10}$',
        'be' => '^[0-9]{1,10}$',
        'bg' => '^[0-9]{1,10}$',
        'co' => '^[0-9]{1,10}$',
        'cz' => '^[0-9]{1,10}$',
        'de' => '^[0-9]{1,10}$',
        'hr' => '^[0-9]{1,10}$',
        'hu' => '^[0-9]{1,10}$',
        'pl' => '^[0-9]{1,10}$',
        'ro' => '^[0-9]{1,10}$',
        'rs' => '^[0-9]{1,10}$',
        'ru' => '^[0-9]{1,10}$',
        'sa' => '^[0-9]{1,10}$',
        'si' => '^[0-9]{1,10}$',
        'sk' => '^[0-9]{1,10}$',
    );

    private $_telSuffixRegexes = array(
        'at' => '^[0-9]{1,7}$',
        'ba' => '^[0-9]{1,7}$',
        'be' => '^[0-9]{1,7}$',
        'bg' => '^[0-9]{1,7}$',
        'co' => '^[0-9]{1,7}$',
        'cz' => '^[0-9]{1,7}$',
        'de' => '^[0-9]{1,7}$',
        'hr' => '^[0-9]{1,7}$',
        'hu' => '^[0-9]{1,7}$',
        'pl' => '^[0-9]{1,7}$',
        'ro' => '^[0-9]{1,7}$',
        'rs' => '^[0-9]{1,7}$',
        'ru' => '^[0-9]{1,7}$',
        'sa' => '^[0-9]{1,7}$',
        'si' => '^[0-9]{1,7}$',
        'sk' => '^[0-9]{1,7}$',
    );

    private $_telCompleteRegexes = array(
        'at' => '^\+{0,1}([1-9]\d*)[0-9]{4,17}(-\d+)?$',
        'ba' => '^\+{0,1}([1-9]\d*)[0-9]{4,17}(-\d+)?$',
        'be' => '^\+{0,1}([1-9]\d*)[0-9]{4,17}(-\d+)?$',
        'bg' => '^\+{0,1}([1-9]\d*)[0-9]{4,17}(-\d+)?$',
        'co' => '^\+{0,1}([1-9]\d*)[0-9]{4,17}(-\d+)?$',
        'cz' => '^\+{0,1}([1-9]\d*)[0-9]{4,17}(-\d+)?$',
        'de' => '^\+{0,1}([1-9]\d*)[0-9]{4,17}(-\d+)?$',
        'hr' => '^\+{0,1}([1-9]\d*)[0-9]{4,17}(-\d+)?$',
        'hu' => '^\+{0,1}([1-9]\d*)[0-9]{4,17}(-\d+)?$',
        'pl' => '^\+{0,1}([1-9]\d*)[0-9]{4,17}(-\d+)?$',
        'ro' => '^\+{0,1}([1-9]\d*)[0-9]{4,17}(-\d+)?$',
        'rs' => '^\+{0,1}([1-9]\d*)[0-9]{4,17}(-\d+)?$',
        'ru' => '^\+{0,1}([1-9]\d*)[0-9]{4,17}(-\d+)?$',
        'sa' => '^\+{0,1}([1-9]\d*)[0-9]{4,17}(-\d+)?$',
        'si' => '^\+{0,1}([1-9]\d*)[0-9]{4,17}(-\d+)?$',
        'sk' => '^\+{0,1}([1-9]\d*)[0-9]{4,17}(-\d+)?$',
    );


    public function __construct()
    {
        parent::__construct();
        $this->_requestedCountry = null;
        $this->_redirectUrl = null;
        $this->_redirectCountry = null;
        $this->_needsRedirect = false;
        $test = (Mage::getStoreConfig('schrackdev/development/test') == '1');
        $qa = (Mage::getStoreConfig('schrackdev/development/qa') == '1') && false;
        $this->_environment = ($test ? ($qa ? 'test' : 'development') : 'production');
        if ($this->_environment === 'test')
            $this->_hostRegexes = array(
                'AT' => 'shop-at.schrack.lan',
                'DE' => 'shop-de.schrack.lan',
                'COM' => 'shop-co.schrack.lan',
                'BA' => 'shop-ba.schrack.lan',
                'BE' => 'shop-be.schrack.lan',
                'BG' => 'shop-bg.schrack.lan',
                'CZ' => 'shop-cz.schrack.lan',
                'HR' => 'shop-hr.schrack.lan',
                'HU' => 'shop-hu.schrack.lan',
                'NL' => 'shop-nl.schrack.lan',
                'PL' => 'shop-pl.schrack.lan',
                'RO' => 'shop-ro.schrack.lan',
                'RS' => 'shop-rs.schrack.lan',
                'SI' => 'shop-si.schrack.lan',
                'SK' => 'shop-sk.schrack.lan',
                'HMMMMMMMMMM' => 'shop-co.schrack.lan',
            );

        // in case of dev environment, we want to be able to test the redirection algorithm even if it is turned off in the config
        $this->_isRedirectEnabled = (Mage::getStoreConfig('schrack/general/redirectGeoIP') == '1' || $this->_environment === 'development');
        $this->_checkExcludedUris = true;
    }

    /**
     * @return boolean
     */
    public function getCheckExcludedUris()
    {
        return $this->_checkExcludedUris;
    }

    /**
     * @param boolean $checkExcludedUris
     */
    public function setCheckExcludedUris($checkExcludedUris)
    {
        $this->_checkExcludedUris = $checkExcludedUris;
    }

    public function getRedirectUrl() {
        return $this->_redirectUrl;
    }

    public function getRedirectCountry() {
        return $this->_redirectCountry;
    }

    public function getNeedsRedirect() {
        return $this->_needsRedirect;
    }

    public function getRequestedCountry() {
        return $this->_requestedCountry;
    }

    public function getUserIpCountry() {
        return $this->_userIpCountry;
    }



    /**
     * the main function: determine whether, and where to redirect the user
     *
     * @param string $serverName server name from request (our host)
     * @param string $localUri http_uri from request
     * @param string $remoteAddr remote_addr from request (user ip(
     * @param string $userAgent user_agent from request
     * @param bool $isSecure is https?
     * @param string $assumedIpCountry country id that should be assumed instead of using geoip
     * @return void
     */

    public function determineRedirection($serverName, $localUri, $remoteAddr, $userAgent, $isSecure, $assumedIpCountry = null) {
        if (!$this->_isRedirectEnabled) {
            $this->_needsRedirect = false;
            return;
        }

        foreach ($this->_excludedRemoteAddrRegexes as $match) {
            if (preg_match('/^'.$match.'/', $remoteAddr)) {
                Mage::log('GeoIP: Not redirecting for excluded remote addr, IP=' . $remoteAddr . ', serverName=' . $serverName . ', localUri = ' . $localUri);
                $this->_needsRedirect = false;
                return;
            }
        }

        foreach (array('131\.253\.24\.2\.', '65\.55\.52\.') as $match) {
            if (preg_match('/^'.$match.'/', $remoteAddr)) {
                Mage::log('GeoIP: Manually keeping bing from bouncing, IP=' . $remoteAddr . ', serverName=' . $serverName . ', localUri = ' . $localUri);
                $this->_needsRedirect = false;
                return;
            }
        }

        if (preg_match('/^66\.249\.78\./', $remoteAddr) || preg_match('/^89\.143\.229\./', $remoteAddr)
                 || preg_match('/^157\.55\.33\./', $remoteAddr)) {
            Mage::log('GeoIP: Manually keeping Googlebot, najdi.si, bing from bouncing, IP=' . $remoteAddr . ', serverName=' . $serverName . ', localUri = ' . $localUri);
            $this->_needsRedirect = false;
            return;
        }

        if ( $this->_checkExcludedUris ) {
            foreach ($this->_excludedLocalUriRegexes as $match) {
                if (preg_match('#' . $match . '#', $localUri)) {
                    $this->_needsRedirect = false;
                    return;
                }
            }
        }

        $serverName = preg_replace('/\:\d+$/', '', $serverName);


        if ($this->isCrawler($localUri, $userAgent, $remoteAddr)) {
            //Mage::log('GeoIP: Automatical crawler detected for IP=' . $remoteAddr . ', serverName=' . $serverName . ', localUri = ' . $localUri . ', userAgent=' . $userAgent);
            $this->_needsRedirect = false;
            return;
        }

        // ip country assumption is only for testing, and thus is allowed only from backstage ips
        if ($this->isInBackstage($remoteAddr) && $assumedIpCountry !== null)
            $ipCountry = $assumedIpCountry;
        else
            $ipCountry = $this->getCountryByIp($remoteAddr);

        $this->_userIpCountry = $ipCountry;

        $found = false;
        foreach ($this->_hostRegexes as $requestedCountry => $regex) {
            if (is_array($regex)) $regex = $regex[0];
            if (preg_match('/^' . $regex . '$/i', $serverName)) {
                $found = true;
                // possible todo: wartungsseite?
                $this->_requestedCountry = $requestedCountry;

                if ($ipCountry === '') {
                    $this->_needsRedirect = false;
                    return;
                }

                // if user is in backstage, they don't need redirect; however, if we have an assumedIpCountry, we still want to redirect for testing...
                if ($this->isInBackstage($remoteAddr) && $assumedIpCountry === null) {
                    $this->_needsRedirect = false;
                    return;
                }

                $newUrl = $this->redirectIfCountryMismatch($requestedCountry, $ipCountry, $serverName, $localUri, $isSecure, $remoteAddr, $userAgent);

                if ($newUrl !== null) {
                    $this->_needsRedirect = true;
                    $this->_redirectUrl = $newUrl;
                    $this->_redirectCountry = $this->getCountryFromUrl($newUrl);
                }

                break;
            }
        }

        if (!$found && !$this->isInBackstage($remoteAddr)) { // catch-all
            $this->_needsRedirect = true;
            $this->_redirectUrl = 'http://www.schrack.at/';
            $this->_redirectCountry = 'AT';
        }
    }

    private function getCountryFromUrl($url) {
        $test = (Mage::getStoreConfig('schrackdev/development/test') == '1');
        $qa = (Mage::getStoreConfig('schrackdev/development/qa') == '1') && false;
        $this->_environment = ($test ? ($qa ? 'test' : 'development') : 'production');
        if ($this->_environment === 'test') {
            $indicatorIndex = 1;
        } else {
            $indicatorIndex = 0;
        }
        foreach ($this->_hostRegexes as $country => $regex) {
            if(is_array($regex)) {
                $regex = $regex[$indicatorIndex];
            }
            if (preg_match('/' . $regex . '/i', $url)) {
                return $country;
            }
        }
        return 'AT';
    }

	/**
	 * NOTE: we do not yet support a nonstandard port
	 * @param boolean $isSecure
	 * @param string $domain
	 * @param string $localUri
	 * @return string
	 */
	private function createUrl($isSecure, $domain, $localUri = '/') {
        $proto = $isSecure ? 'https' : 'http';
        return $proto . '://' . $domain . $localUri;
    }

    // .it is a low-price country so that italians can view the .de shop
    private function isLowPriceCountry($ctry) {
        return $ctry === 'DE' || $ctry === 'IT';
    }

    /**
     * in general, if we have a domain for a country, we want users to redirect there
     *
     * @param string $requestedCountry
     * @param string $ipCountry
     * @param string $serverName
     * @param string $localUri
     * @param boolean $isSecure
     * @return null
     */
    private function redirectIfCountryMismatch($requestedCountry, $ipCountry, $serverName, $localUri, $isSecure, $remoteAddr, $userAgent = null) {
        $newDomain = $this->determineDomain($ipCountry, $serverName); // orig. schrack_preferred_domain
        $newUrl = null;
        if ($newDomain ||
            $this->isLowPriceCountry($requestedCountry) || ($requestedCountry === 'COM' &&  $this->matchUri($localUri, "^/shop/"))) {
                if ($this->isLowPriceCountry($requestedCountry)) { // user wants to go to a high price country
                    if (!$this->isLowPriceCountry($ipCountry)) { // ... but doesn't come from one
                        if ($newDomain === null) {
                            $newUrl = $this->createUrl($isSecure, 'www.schrack.com', '/country_sorry/');
                        } else {
                            $newUrl = $this->createUrl($isSecure, 'www.' . $newDomain, $this->_shopLocalUris[$newDomain]);
                        }
                        Mage::log('GeoIP: Bounce from (' . $requestedCountry . ', ' . $serverName . ', userAgent=' . $userAgent . ') to ' . $newUrl . ', remoteAddr: ' . $remoteAddr);
                    } else {
                        // Du kommst aus einem Tiefpreisland, und willst in den falschen Tiefpreisshop
                        // ... naja, lassen wir das mal so...
                        $newUrl = null;
                    }
                } else { // not requesting high-price country, we don't care where he goes
                    $newUrl = null;
                }
        }
        echo $newUrl;
        die();

        return $newUrl;
    }

    /**
     * determine whether the user is allowed to see prices, esp. for .com shop
     * -> if we have a list for this country, then we return whether the ip-country is in this list
     * -> otherwise we return true
     *
     * @param string $requestedCountry
     * @param string $ipCountry
     * @return boolean
     */
    private function countryMaySeePrices($requestedCountry, $ipCountry) {
        if (isset($this->_maySeePrices[$requestedCountry]))
            return in_array($ipCountry, $this->_maySeePrices[$requestedCountry]);
        else
            return true;
    }

    /**
     * determine whether the user is allowed to perform the full checkout, esp. for .com shop
     * -> if we have a list for this country, then we return whether the ip-country is in this list
     * -> otherwise we return true
     *
     * @param string $requestedCountry
     * @param string $ipCountry
     * @return boolean
     */
    private function countryMayPerformCheckout($requestedCountry, $ipCountry) {
        if (isset($this->_mayPerformCheckout[$requestedCountry]))
            return in_array($ipCountry, $this->_mayPerformCheckout[$requestedCountry]);
        else
            return true;
    }


    private function getCountryByServerName($serverName) {
        foreach ($this->_hostRegexes as $country => $regexes) {
            foreach ( $regexes as $regex ) {
                if ( preg_match('/^' . $regex . '$/i', $serverName) ) {
                    return $country;
                }
            }
        }
        return null;
    }

    public function maySeePrices($serverName, $remoteAddr) {
        $requestedCountry = $this->getCountryByServerName($serverName);
        $ipCountry = $this->getCountryByIp($remoteAddr);

        if ( $this->isInBackstage($remoteAddr) ) {
            /// TESTING dEBUG SPeZIAl lustic:
            if (in_array($requestedCountry, array('CO', 'RU'))) { // hardcoded!!! just for debuggong!!!
                return false;
            } elseif ($requestedCountry === 'SA') {
                return true;
            }

            if ( isset($_REQUEST['assumedIpCountry']) && strlen($_REQUEST['assumedIpCountry']) === 2 ) {
                $ipCountry = $_REQUEST['assumedIpCountry'];
            } else {
                return true;
            }
        }

        $res = $this->countryMaySeePrices($requestedCountry, $ipCountry);
        if ( Mage::getSingleton('customer/session')->getCustomer() ) {
            $email = Mage::getSingleton('customer/session')->getCustomer()->getEmail();
            if ( isset($email) && $email > ' ' ) {
                Mage::log("maySeePrices: remoteAddr = $remoteAddr, ipCountry = $ipCountry, result = $res, email = $email", null, "geoip.log");
            }
        }
        return $res;
    }

    public function mayPerformCheckout($serverName, $remoteAddr) {
        $requestedCountry = $this->getCountryByServerName($serverName);
        if ( isset($this->_mayPerformCheckout[$requestedCountry]) && count($this->_mayPerformCheckout[$requestedCountry]) == 0 ) {
            // no allowed countries, no need for further check
            return false;
        }
        $ipCountry = $this->getCountryByIp($remoteAddr);

        if ( $this->isInBackstage($remoteAddr) ) {
            /// TESTING dEBUG SPeZIAl lustic:
            if (in_array($requestedCountry, array('CO', 'SA', 'RU'))) { // hardcoded!!! just for debuggong!!!
                Mage::log('mayPerformCheckout = false >>>> for SERVERNAME = ' . $serverName . ' AND REMOTEADRESS =' . $remoteAddr, null, 'geoip.log');
                return false;
            }

            if ( isset($_REQUEST['assumedIpCountry']) && strlen($_REQUEST['assumedIpCountry']) === 2 ) {
                Mage::log($ipCountry . $serverName . ' AND REMOTEADRESS =' . $remoteAddr, null, 'geoip.log');
                $ipCountry = $_REQUEST['assumedIpCountry'];
            } else {
                Mage::log('mayPerformCheckout 2 = true >>>> for SERVERNAME = ' . $serverName . ' AND REMOTEADRESS =' . $remoteAddr, null, 'geoip.log');
                return true;
            }
        }

        $resultMayPerformCheckout = $this->countrymayPerformCheckout($requestedCountry, $ipCountry);

        if ($resultMayPerformCheckout) {
            Mage::log('mayPerformCheckout = YES >>>> for SERVERNAME = ' . $serverName . ' AND REMOTEADRESS = ' . $remoteAddr, null, 'geoip.log');
        } else {
            $customerStatus = " (ALREADY LOGGED IN)";
            $customerSession = Mage::getSingleton('customer/session');
            if (!$customerSession->isLoggedIn()) {
                $customerStatus = " (NOT LOGGED IN)";
            }
            Mage::log('mayPerformCheckout = NO >>>> for SERVERNAME = ' . $serverName . ' AND REMOTEADRESS = ' . $remoteAddr . $customerStatus, null, 'geoip.log');
        }
        return $resultMayPerformCheckout;
    }

    private function matchHostAndUri($host, $hostPattern, $uri, $uriPattern) {
        return $this->matchHost($host, $hostPattern) && $this->matchUri($uri, $uriPattern);
    }

    private function matchHost($host, $hostPattern) {
        return preg_match('/' . $hostPattern . '/i', $host);
    }

    private function matchUri($uri, $uriPattern) {
        return preg_match('#' . $uriPattern . '#', $uri);
    }

    /**
     *
     * @param string $ipCountry
     * @param string $serverName
     * @return string|null null if no change required
     *
     * - if user comes from certain countries, redirect to .at
     * - for certain eastern countries, redirect to .ru
     * - for countries where we have a domain, redirect them there
     * - for some other countries, redirect them to .com
     * - for the rest, don't redirect at all
     */
    public function determineDomain($ipCountry, $serverName) {
        if ( in_array($ipCountry, array('AT', 'CH', 'LI')) && !$this->matchHost($serverName, "schrack\.at$") ) {
            return "schrack.at";
        } else if (in_array($ipCountry, array('RU', 'UA', 'KZ', 'GE', 'BY', 'EE', 'LV', 'LT'))) {
            if ( !$this->matchHost($serverName, "schrack(-technik\.ru|\.at)$") ) {
                return "schrack-technik.ru";
            } else
                return null;
        } else if ($ipCountry === "DE" && !$this->matchHost($serverName, "(schrack|schrack-technik)\.de$")) {
            return "schrack-technik.de";
        } else if ($ipCountry == "BE" && !$this->matchHost($serverName, "schrack\.be$")) {
            return "schrack.be";
        } else if ($ipCountry === "PL" && !$this->matchHost($serverName, "schrack\.pl$")) {
            return "schrack.pl";
        } else if ($ipCountry === "CZ" && !$this->matchHost($serverName, "schrack\.cz$")) {
            return "schrack.cz";
        } else if ($ipCountry === "SK" && !$this->matchHost($serverName, "schrack\.sk$")) {
            return "schrack.sk";
        } else if ($ipCountry === "HU" && !$this->matchHost($serverName, "schrack\.hu$")) {
            return "schrack.hu";
        } else if ($ipCountry === "RO" && !$this->matchHost($serverName, "schrack\.ro$")) {
            return "schrack.ro";
        } else if ($ipCountry === "BG" && !$this->matchHost($serverName, "schrack\.bg$")) {
            return "schrack.bg";
        }
        else if ( in_array($ipCountry, array ('RS', 'ME')) && !$this->matchHost($serverName, "schrack\.rs$") ) {
            // Auch schrack.co.rs wird auf RS umgenudelt
            // Montenegro wird aus RS bedient.
            return "schrack.rs";
        } else if ($ipCountry === "BA" && !$this->matchHost($serverName, "schrack\.ba$")) {
            return "schrack.ba";
        } else if ($ipCountry === "HR" && !$this->matchHost($serverName, "schrack\.hr$")) {
            return "schrack.hr";
        } else if ($ipCountry === "SA" && !$this->matchHost($serverName, "schrack\.sa$")) {
            return "schrack.sa";
        } else if ($ipCountry === "SI" && !$this->matchHost($serverName, "schrack\.si$")) {
            return "schrack.si";
            // minus |NL|FR
        } else if ( in_array($ipCountry, array ('AD', 'FI', 'FR', 'GB', 'IT', 'LU', 'MT', 'MC', 'NL', 'PT', 'SM', 'SE', 'ES', 'VA')) && !$this->matchHost($serverName, "schrack\.com$")) {
            return "schrack.com";
        } else
            return null;
    }

    /**
     * crawlers will never be redirected
     *
     * @param string $localUri
     * @param string $userAgent
     * @return bool
     */
    private function isCrawler($localUri, $userAgent, $remoteAddr) {
        foreach ($this->_crawlerIpRegexes as $regex) {
            if (preg_match('/^' . $regex . '/', $remoteAddr)) {
                return true;
            }
        }
        return $this->matchesAllPatterns($localUri, new GeoIPPattern(true, "^/(robots.txt|sitemap.xml)$"))
            || preg_match('/(Googlebot|Google Page Speed|Google-Site-Verification|Slurp|search.msn.com|nutch|simpy|bot|ASPSeek|crawler|msnbot|Libwww-perl|FAST|Baidu|Mediapartners-Google"
                        . "|Yandex|bingbot)"/i', $userAgent)
            || preg_match('/search.msn.com/i', $userAgent) || preg_match('/googlebot/i', $userAgent) || preg_match('/bingbot/i', $userAgent) || preg_match('/yandex/i', $userAgent);
    }

    public function hasRedirectWarning() {
        return ( Mage::getStoreConfig('schrack/geoip/hasRedirectWarning') === '1' );
    }

    /**
     *
     * @param string $url
     * @param string|string[] $patterns pattern AND pattern AND pattern...
     * @return boolean
     */
    public function matchesAllPatterns($url, $patterns, $ignoreCase = false) {
        if (!is_array($patterns))
            $patterns = array($patterns);
        $optString = '';
        if ($ignoreCase) {
            $optString .= 'i';
        }
        foreach ($patterns as $pattern) {
            $res = preg_match('#' . $pattern->regex . '#' . $optString, $url);
            if (($pattern->pos && !$res) || (!$pattern->pos && $res))
                return false;
        }
        return true;
    }

    /**
     *
     * @param string $url
     * @param string|string[] $patterns pattern AND pattern AND pattern...
     * @return boolean
     */
    public function matchesAnyPattern($url, $patterns, $ignoreCase = false) {
        if (!is_array($patterns))
            $patterns = array($patterns);
        $optString = '';
        if ($ignoreCase) {
            $optString .= 'i';
        }
        foreach ($patterns as $pattern) {
            $res = preg_match('#' . $pattern->regex . '#' . $optString, $url);
            if (($pattern->pos && $res) || (!$pattern->pos && !$res))
                return true;
        }
        return false;
    }

    /**
     *
     * @param string $ip
     * @return boolean
     */
    public function isInBackstage($remoteIp) {
        $logdebug = 0;

        if ($logdebug) $email = Mage::getSingleton('customer/session')->getCustomer()->getEmail();
        foreach ($this->_backstagePasses as $pass) {
            if ($this->isIpInNetwork($remoteIp, $pass[0], $pass[1])) {
                if ($logdebug) Mage::log("Email :" . $email . " isInBackstage: " . $remoteIp . " : FALSE", null, 'backstage_ip.log');
                return true;
            }
        }
        if ($logdebug) Mage::log("Email :" . $email . " isInBackstage: " . $remoteIp . " : FALSE", null, 'backstage_ip.log');
        return false;
    }

    /**
     * from http://stackoverflow.com/questions/10421613/match-ipv4-address-given-ip-range-mask
     *
     * @param string $ip
     * @param string $net_addr
     * @param string $net_mask
     * @return boolean
     */
    public function isIpInNetwork($ip, $net_addr, $net_mask){
        if($net_mask <= 0){ return false; }
        $ip_binary_string = sprintf("%032b",ip2long($ip));
        $net_binary_string = sprintf("%032b",ip2long($net_addr));
        return (substr_compare($ip_binary_string, $net_binary_string, 0, $net_mask) === 0);
    }

    /**
     * return zip code regex for a country id
     *
     * @param $countryId
     * @return string|null
     */
    public function getZipCodeRegex($countryId) {
        if ( isset($this->_zipCodeRegexes[$countryId]) ) {
            return $this->_zipCodeRegexes[$countryId];
        } else {
            return '^\w+$';
        }
    }

    public function getZipCodeRegexes() {
        return $this->_zipCodeRegexes;
    }

    public function getTelCountryCodeRegexes($countryId) {
        return $this->_telCountryCodeRegexes[$countryId];
    }

    public function getTelCityCodeRegexes($countryId) {
        return $this->_telCityCodeRegexes[$countryId];
    }

    public function getTelTelphoneRegexes($countryId) {
        return $this->_telTelphoneRegexes[$countryId];
    }

    public function getTelSuffixRegexes($countryId) {
        return $this->_telSuffixRegexes[$countryId];
    }

    public function getTelCompleteRegexes($countryId) {
        return $this->_telSuffixRegexes[$countryId];
    }

    public function getShopHosts() {
        return array_keys($this->_shopLocalUris);
    }
}

class GeoIPPattern {
    public $pos; // positive/negative (boolean)
    public $regex; // pattern

    public function __construct($pos, $regex) {
        $this->pos = $pos;
        $this->regex = $regex;
    }
}

?>
