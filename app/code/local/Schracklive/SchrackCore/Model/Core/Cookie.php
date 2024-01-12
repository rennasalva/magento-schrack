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
    public function set1($name, $value, $period = null, $path = null, $domain = null, $secure = null, $httponly = null)
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
    
    /**
     * Set cookie
     *
     * @param string $name The cookie name
     * @param string $value The cookie value
     * @param int $period Lifetime period
     * @param string $path
     * @param string $domain
     * @param int|bool $secure
     * @return Mage_Core_Model_Cookie
     */
    public function set($name, $value, $period = null, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        /**
         * Check headers sent
         */
        if (!$this->_getResponse()->canSendHeaders(false)) {
            return $this;
        }

        if ($period === true) {
            $period = 3600 * 24 * 365;
        } elseif (is_null($period)) {
            $period = $this->getLifetime();
        }

        if ($period == 0) {
            $expire = 0;
        }
        else {
            $expire = time() + $period;
        }
		
        if (is_null($path)) {
            $path = $this->getPath();
        }
        if (is_null($domain)) {
            $domain = $this->getDomain();
        }
        if (is_null($secure)) {
            $secure = $this->isSecure();
        }
        if (is_null($httponly)) {
            $httponly = $this->getHttponly();
        }
		// Custom Code:
		// Set "keep-me-logged-in" Flag to session data:
		$session = Mage::getSingleton('customer/session');				
				
		if ($session && ($session->getData('rememberme') || $this->get('keepmeloggedin'))) {
			if ( $this->get('keepmeloggedin') && $session->getData('delete_rememberme') ) {				
				setcookie("keepmeloggedin", "", time()-3600, '/');
				return $this;
			}
			$expire = time() + (intval(Mage::getStoreConfig( 'web/cookie/keep_me_logged_in_cookie_lifetime', $this->getStore() )));
			$value = $this->_getRequest()->getCookie($name, false);
			if ($path == '/' && $name == 'frontend') {
				setcookie('keepmeloggedin', $value, $expire, $path, $domain, $secure, $httponly);
			}
			if ($name == 'frontend') {
				setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
				return $this;
			}
		}	
        
		setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);

        return $this;
    }
}
