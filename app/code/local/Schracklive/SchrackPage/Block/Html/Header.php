<?php

class Schracklive_SchrackPage_Block_Html_Header extends Mage_Page_Block_Html_Header {
    
    public function _construct()
    {
        $this->addData(array('cache_lifetime' => false));
        $this->addCacheTag(array(
            Mage_Core_Model_Store::CACHE_TAG,
            Mage_Cms_Model_Block::CACHE_TAG
        ));
        $this->setTemplate('page/html/header.phtml');
    }

	public function getWelcome() {
        if (empty($this->_data['welcome'])) {
            if (Mage::isInstalled() && Mage::getSingleton('customer/session')->isLoggedIn()) {
				$customer = Mage::getSingleton('customer/session')->getCustomer();
				$salutation = $customer->getName();
				if ($customer->isContact()) {
					$salutation .= ' ('.$customer->getSchrackWwsCustomerId().')';
				} elseif ($customer->isEmployee()) {
					$salutation .= ' ('.$customer->getSchrackUserPrincipalName().')';
				}
                $this->_data['welcome'] = $this->__($this->escapeHtml($salutation));
            } else {
                $this->_data['welcome'] = Mage::getStoreConfig('design/header/welcome');
            }
        }

		return $this->_data['welcome'];
	}

	public function getMenu() {
		return $this->getChildHtml('topMenu');
	}
    
    /**
     * protect against BREACH attack, see http://blog.ircmaxell.com/2013/08/dont-worry-about-breach.html
     */
    public function breachProtection() {
        $randomData = mcrypt_create_iv(25, MCRYPT_DEV_URANDOM);
        return "<!--"
            . substr(
                base64_encode($randomData),
                0,
                ord($randomData[24]) % 32
            )
            . "-->";
    }
    
    protected function shouldShowLoginForm() {
        $session = Mage::getSingleton('customer/session');
        $param = Mage::app()->getRequest()->getParam('showLoginForm');
        return (!$session->isLoggedIn() && $param === '1');
    }
}
