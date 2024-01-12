<?php

class Schracklive_Schrack_Helper_Backend extends Mage_Core_Helper_Abstract {

	public function getFrontendUrl ( $path = '', $params = array(), $paramsAsQuery = true ) {
        if ( ! is_array($params) ) {
            $params = array($params);
        }
        $sql = "SELECT value FROM core_config_data WHERE path = 'web/unsecure/base_url' AND scope = 'default';";
        $url = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchOne($sql);
        if ( ! $url ) {
            $url = Mage::getBaseUrl();
        }
        if ( substr($url,-1) != '/' ) {
            $url .= '/';
        }
        if( $path && $path > '' ) {
            $url .= $path;
        }
        if ( substr($url,-1) != '/' ) {
            $url .= '/';
        }
        $first = true;
        foreach ( $params as $key => $val ) {
            if ( $paramsAsQuery ) {
                if ( $first ) {
                    $first = false;
                    $url .= '?';
                } else {
                    $url .= '&';
                }
                $url .= ($key . '=' . $val);
            } else {
                $url .= ($key . '/' . $val . '/');
            }
        }
        return $url;
    }

}