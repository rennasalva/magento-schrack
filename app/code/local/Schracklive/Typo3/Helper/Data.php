<?php

class Schracklive_Typo3_Helper_Data
{

    public function getResponse($uri, $requestTimeout = 5, $cookies = array())
    {
        $options = array(
            'adapter' => 'Zend_Http_Client_Adapter_Curl',
            'timeout' => 3, // This gets mapped to CURLOPT_CONNECTTIMEOUT_MS; defaults to 30s in curl, and 10s in Zend
            'request_timeout' => $requestTimeout // This gets mapped to CURLOPT_TIMEOUT; defaults to 0 in curl, meaning no timeout
        );
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $options['useragent'] = $_SERVER['HTTP_USER_AGENT'];
        }
        $proxyHost = Mage::getStoreConfig('schrack/general/proxy_host');
        $proxyPort = Mage::getStoreConfig('schrack/general/proxy_port');
        $httpClient = null;
        if ($proxyHost && $proxyPort) {
            $options['proxy_host'] = $proxyHost;
            $options['proxy_port'] = $proxyPort;
            // default max_redirects is 5, so this is OK
            $httpClient = new Zend_Http_Client($uri, $options);
            // for whatever reason, proxies don't seem to work with the varien_http_client, so we use the underlying zend_http_client instead,
            // seeing as proxies are only used on dev machines anyway
        } else {
            $httpClient = new Varien_Http_Client($uri, $options);
        }
        if ($cookies) {
            $httpClient->setHeaders('Cookie', $cookies);
        }

        $response = $httpClient->request(Varien_Http_Client::GET);
        return $response;
    }
}
