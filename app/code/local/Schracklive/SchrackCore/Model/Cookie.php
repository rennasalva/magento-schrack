<?php
/**
 *
 *
 */

class Schracklive_SchrackCore_Model_Cookie extends Mage_Core_Model_Cookie
{
    /**
     *
     */
    public function set($name, $value, $period = null, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        $rememberme = false;

        if (is_null($period)) {
            $session = Mage::getSingleton('customer/session');
            $login = Mage::app()->getRequest()->getPost('login');
            if (isset($login['rememberme'])) {
                $rememberme = (bool) @$login['rememberme'];
            }
            if ( ($session && $session->getData('rememberme')) || $rememberme)
                $period = intval(Mage::getStoreConfig( 'web/cookie/keep_me_logged_in_cookie_lifetime', $this->getStore() ));
        }
        return parent::set($name, $value, $period, $path, $domain, $secure, $httponly);
    }
}
