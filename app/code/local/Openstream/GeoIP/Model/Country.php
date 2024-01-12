<?php

class Openstream_GeoIP_Model_Country extends Openstream_GeoIP_Model_Abstract {

    private $country;
    private $allowed_countries = array();

    public function __construct()
    {
        parent::__construct();
        $this->country = $this->getCountryByIp(Mage::helper('core/http')->getRemoteAddr());
        $allowCountries = explode(',', (string)Mage::getStoreConfig('general/country/allow'));
        $this->addAllowedCountry($allowCountries);
    }

    /**
     * Get Country code according to given IP, Check if cookie is set
     * if not set than check cache if not cached than retrieve from Maxmind Resources
     * in fallback situations Call built in GEOIP Wrapper
     * @param $ip
     * @return false|mixed|string|null
     */
    public function getCountryByIp($ip) {
        // Check if Cookie exists
        if (isset($_COOKIE['SessionCountryCode'])) {
            Mage::log("Through Cookie" . "-" . $_COOKIE['SessionCountryCode'], null,"maxmindGeoip.log", true);
            return $_COOKIE['SessionCountryCode'];
        }

        // Retrieve Shop Domain
        $domain = Mage::getStoreConfig('schrack/typo3/typo3url');
        // Explode https:// from domain
        $explodeMainDomain = explode("https://", $domain);

        // Check if IP exists in cache
        if ($this->checkIpExists($ip)) {
            // Retrieve IP Cached Data
            $data = $this->retrieveIpData($ip);
            // Retrieve updated at value and add 1 month to it
            $updateAt = strtotime($data[0]['updated_at']. '+ 6 month');
            // Retrieve recent Time Stamp
            $recentDate = strtotime(now());

            // If UpdateAt is Greater than Recent Time Stamp, Set Cookie and return Country Code
            if ($updateAt > $recentDate) {
                setcookie("SessionCountryCode", $data[0]['country'], 0, '/', $explodeMainDomain[0]);
                Mage::log("Returned through cache" . "-" . $ip, null,"maxmindGeoip.log", true);
                return $data[0]['country'];
            } else {
                // If Recent Time is greater than updatedAt
                // Create a Maxmind Request to retrieve data through their sources
                $maxmindRequest = $this->fetchCountryByIp($ip, $explodeMainDomain[0]);
                // Check Return type of maxmindRequest is a varchar, Then return the country
                if (is_string($maxmindRequest)) {
                    // Update Cache
                    $this->updateIpCountry($maxmindRequest, now(), $ip);
                    Mage::log("Returned through Maxmind API After cache check" . "-" . $ip, null,"maxmindGeoip.log", true);
                    return $maxmindRequest;
                }
            }
        } else {
            // Check if IP is and Internal IP or is Reserved by Maxmind
            if (!$this->checkIpInternal($ip)) {
                if (!$this->checkIpAddressForErrors($ip)) {
                    // Create a Maxmind Request to retrieve data through their sources
                    $maxmindRequest = $this->fetchCountryByIp($ip, $explodeMainDomain[0]);
                    // Check Return type of maxmindRequest is a varchar, Then return the country
                    if (is_string($maxmindRequest)) {
                        Mage::log("Returned through Maxmind API" . "-" . $ip, null,"maxmindGeoip.log", true);
                        // Check if IP is not cached
                        if (!$this->checkIpExists($ip)) {
                            // Create a new record of IP address in cache
                            $this->createIp($ip, $maxmindRequest, now(), now());
                        }
                        // Set Cookie
                        setcookie("SessionCountryCode", $maxmindRequest, 0, '/', $explodeMainDomain[0]);
                        return $maxmindRequest;
                    }
                } else {
                    Mage::log("Returned through Internal API" . "-" . $ip , null,"maxmindGeoip.log", true);
                    /** @var $wrapper Openstream_GeoIP_Model_Wrapper */
                    $wrapper = Mage::getSingleton('geoip/wrapper');
                    if ($wrapper->geoip_open($this->local_file, 0)) {
                        $country = $wrapper->geoip_country_code_by_addr($ip);
                        $wrapper->geoip_close();
                        setcookie("SessionCountryCode", $country, 0, '/', $explodeMainDomain[0]);
                        return $country;
                    } else {
                        return null;
                    }
                }
            }
        }
    }

    /**
     * Check if an IP Address is an Internal Shrack Address
     * @param $ip Address to check
     * @return bool
     */
    private function checkIpInternal($ip) {
        if ((ip2long($ip) >= ip2long('10.0.0.0')) && (ip2long($ip) <= ip2long('10.255.255.255'))) {
            return true;
        }

        if ((ip2long($ip) >= ip2long('172.16.0.0')) && (ip2long($ip) <= ip2long('172.31.255.255'))) {
            return true;
        }

        if ((ip2long($ip) >= ip2long('192.168.0.0')) && (ip2long($ip) <= ip2long('192.168.255.255'))) {
            return true;
        }

        return false;
    }

    /**
     * Request maxmind and check if IP is Reserved
     * @param $ip requested IP
     * @return bool
     */
    private function checkIpAddressForErrors($ip) {
        // Get Account ID of Maxmind API
        $accountId = Mage::getStoreConfig('maxmind/geoip/account/id');
        // Get License Key of Maxmind API
        $licenseKey = Mage::getStoreConfig('maxmind/geoip/license/key');
        // Get Maxmind API URL
        $url = Mage::getStoreConfig('maxmind/geoip/url');

        // Set a new Curl Handling request
        $ch = curl_init();
        // Set Proxy
        // Test Enviroment
        if (substr($this->getHostName(), 0, 2) == 'tl') {
            curl_setopt($ch, CURLOPT_PROXY, '172.22.4.250:8080');
        }
        // Live Enviroment
        if (substr($this->getHostName(), 0, 2) == 'sl') {
            curl_setopt($ch, CURLOPT_PROXY, '172.30.0.250:8080');
        }
        // Set user name for Authentication
        curl_setopt($ch, CURLOPT_USERPWD, $accountId . ":" . $licenseKey);
        // Set URL to curl too
        curl_setopt($ch, CURLOPT_URL, $url . "" . $ip);
        // Return the response
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // Set Timeout time to execute the command in
        curl_setopt($ch, CURLOPT_TIMEOUT, 0.5);
        // Include header in output
        curl_setopt($ch , CURLOPT_HEADER, true);
        // Exec the Curl Handling Command
        $result = curl_exec($ch);
        // Retrieve Server Error Code
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // Close the Curl Handling Request
        curl_close($ch);

        if ($httpCode >= 300 && $httpCode <= 511) {
            Mage::log($httpCode . "-" . $ip, null,"maxmindGeoip.log", true);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Retrieve Data Through MaxMind API (REST)
     * @param $ip ip address to look for
     * @param $domain Effected Domain to store Cookie Value
     * @return varchar Return ISO2CODE Country Code
     */
    private function fetchCountryByIp($ip, $domain) {
        // Get Account ID of Maxmind API
        $accountId = Mage::getStoreConfig('maxmind/geoip/account/id');
        // Get License Key of Maxmind API
        $licenseKey = Mage::getStoreConfig('maxmind/geoip/license/key');
        // Get Maxmind API URL
        $url = Mage::getStoreConfig('maxmind/geoip/url');

        // Set a new Curl Handling request
        $ch = curl_init();
        // Set Proxy
        // Test Enviroment
        if (substr($this->getHostName(), 0, 2) == 'tl') {
            curl_setopt($ch, CURLOPT_PROXY, '172.22.4.250:8080');
        }
        // Live Enviroment
        if (substr($this->getHostName(), 0, 2) == 'sl') {
            curl_setopt($ch, CURLOPT_PROXY, '172.30.0.250:8080');
        }
        // Set user name for Authentication
        curl_setopt($ch, CURLOPT_USERPWD, $accountId . ":" . $licenseKey);
        // Set URL to curl too
        curl_setopt($ch, CURLOPT_URL, $url . "" . $ip);
        // Return the response
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // Set Timeout time to execute the command in
        curl_setopt($ch, CURLOPT_TIMEOUT, 0.5);
        // Exec the Curl Handling Command
        $result = curl_exec($ch);
        // Close the Curl Handling Request
        curl_close($ch);
        // Decode Returned Json Data
        $decodeData = json_decode($result);
        // Extract Country ISO Code from JSON Data
        $extract = $decodeData->registered_country->iso_code;
        // Set a Country Code Cookie
        setcookie("SessionCountryCode", $extract, 0, '/', $domain);
        // Write requests to Log File with IP Address
        Mage::log($extract . "-" . $ip, null,"maxmindGeoip.log", true);

        return $extract;
    }

    /**
     * Get the hostname
     * @return false|string
     */
    private function getHostName() {
        return gethostname();
    }

    /**
     * Check if IP Address is already cached in to maxmind_geoip_log
     * @param $ip IP Address to check
     * @return bool
     */
    private function checkIpExists($ip) {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');

        $sql = "SELECT `ip` FROM `maxmind_geoip_log` WHERE `ip` = ?";
        $query = $connection->fetchOne($sql, $ip);

        if ($query) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * Cache a new IP Address in Maxmind table
     * @param $ip IP Address to cache
     * @param $country Country Code
     * @param $createdAt Creation Date of cache entry
     * @param $updatedAt Update Date of cache entry
     * @return bool
     */
    private function createIp($ip, $country, $createdAt, $updatedAt) {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');

        $sql = "INSERT INTO `maxmind_geoip_log`(`ip`, `country`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?)";

        $query = $connection->query($sql, [$ip, $country, $createdAt, $updatedAt]);

        if ($query) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * Update IP Address country and date According to IP
     * @param $country
     * @param $updateDate
     * @return void
     */
    private function updateIpCountry($country, $updateDate, $ip) {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');

        $sql = "UPDATE `maxmind_geoip_log` SET `country` = ?, `updated_at` = ? WHERE `ip` = ?";
        $query = $connection->query($sql, [$country, $updateDate, $ip]);
    }



    /**
     * Retrieve all data from maxmind_geoip_log according to specific IP
     * @param $ip IP address to search
     * @return mixed
     */
    private function retrieveIpData($ip) {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');

        $sql = "SELECT * FROM `maxmind_geoip_log` WHERE `ip` = ?";
        $query = $connection->fetchAll($sql, $ip);

        return $query;
    }


    public function getCountry()
    {
        return $this->country;
    }

    public function isCountryAllowed($country = '')
    {
        $country = $country ? $country : $this->country;
        if (count($this->allowed_countries) && $country) {
            return in_array($country, $this->allowed_countries);
        } else {
            return true;
        }
    }

    public function addAllowedCountry($countries)
    {
        $countries = is_array($countries) ? $countries : array($countries);
        $this->allowed_countries = array_merge($this->allowed_countries, $countries);

        return $this;
    }
}