<?php

/**
 * Url
 *
 * @author c.friedl
 */
class Schracklive_SchrackCore_Helper_Url {
    /**
     * return the url with possibly an optional id from the original request
     * 
     * @param string $route base url / route
     * @param string $idParamName name of otpional id parameter
     */
    public function getUrlWithPossibleId($route, $idParamName = 'id') {
        $param = Mage::app()->getRequest()->getParam($idParamName);
        if (isset($param))
            return Mage::getUrl($route, array($idParamName => $param));
        else
            return Mage::getUrl($route);
    }

    public function getUrlWithCurrentProtocol ( $routePath=null, $routeParams=null ) {
        if ( Mage::app()->getRequest()->isSecure() ) {
            if ( $routeParams == null ) {
                $routeParams = array();
            }
            $routeParams['_secure'] = true;
        }
        $res = Mage::getUrl($routePath,$routeParams);
        return $res;
    }

    public function ensureValidMediaUrl ( $url, $forceProtocolForOwnServers = false ) {
        // Bugfix for mysterious 'ß' in URL:
        $url = str_replace('ßpage=', '?page=', $url);

        if ( filter_var($url,FILTER_VALIDATE_URL) === false ) {
            $url = Mage::getStoreConfig('schrack/general/imageserver') . $url;

            if ( $forceProtocolForOwnServers == 'http' ) {
                $url = str_replace('https://','http://',$url);
            } else if ( $forceProtocolForOwnServers == 'https' ) {
                $url = str_replace('http://','https://',$url);
            } else {
                $url = $this->ensureCurrentProtocol($url);
            }
        }
        return $url;
    }

    public function ensureCurrentProtocol ( $url ) {
        $isSecure = Mage::app()->getRequest()->isSecure();
        $baseUrl = Mage::app()->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK,$isSecure);
        $baseScheme = $this->getScheme($baseUrl);
        $urlScheme = $this->getScheme($url);
        if ( $baseScheme !== $urlScheme ) {
            if ( $urlScheme === '' ) {
                while ( $url[1] !== '/'  ) {
                    $url = '/' . $url;
                }
            }
            $subUrl = substr($url,strlen($urlScheme));
            if ( $baseScheme === '' && $subUrl[0] === ':' ) {
                $subUrl = substr($subUrl,1);
            } else if ( $subUrl[0] !== ':' ) {
                $subUrl = ':' . $subUrl;
            }
            $url = $baseScheme . $subUrl;
        }
        return $url;
    }

    private function getScheme ( $url ) {
        $urlArray = parse_url($url);
        if ( isset($urlArray['scheme']) ) {
            return $urlArray['scheme'];
        } else {
            return '';
        }
    }

    /**
     * check whether given url is local to our server, i.e. the typo3 machine
     * copied from Mage_Core_Controller_Varien_Action::_isUrlInternal() and amended
     * @param $url the url
     * @return bool
     */
    public function isUrlServerLocal($url) {
        if (strpos($url, 'http') !== false) {
            /**
             * Url must start from base secure or base unsecure url
             */
            if ((strpos($url, Mage::app()->getStore()->getBaseUrl()) === 0)
                || (strpos($url, Mage::app()->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, true)) === 0)
            ) {
                return true;
            }
            return Mage::getModel('core/url')->parseUrl($url)->getHost() ===  $_SERVER['HTTP_HOST'];
        }
        return false;
    }
}

?>
