<?php

class Schracklive_SchrackPage_Block_Template_Links extends Mage_Page_Block_Template_Links {
    public $_idRand;

    /**
     * Add link to the list
     *
     * @param string $label
     * @param string $url
     * @param string $title
     * @param boolean $prepare
     * @param array $urlParams
     * @param int $position
     * @param string|array $liParams
     * @param string|array $aParams
     * @param string $beforeText
     * @param string $afterText
     * @param string $inMenu whether to show the link in the popup menu or not
     * @return Mage_Page_Block_Template_Links
     */
    public function addLink($label, $url='', $title='', $prepare=false, $urlParams=array(),
        $position=null, $liParams=null, $aParams=null, $beforeText='', $afterText='', $inMenu = false)
    {
        if ($position !== null)
            $position = intval($position);
        $pos = $this->_getNewPosition($position);
        parent::addLink($label, $url, $title, $prepare, $urlParams, $position, $liParams, $aParams, $beforeText, $afterText);
        $this->_links[$pos]->setInMenu($inMenu);
        ksort($this->_links, SORT_NUMERIC);
        return $this;
    }
     
    
    

	protected function _addPickupLink() {
		if (Mage::getSingleton('customer/session')->isLoggedIn()) {
			$customer = Mage::getSingleton('customer/session')->getCustomer();
			if ($customer->getSchrackPickup() > 0) {
				$pickuplocs = Mage::getStoreConfig('carriers/schrackpickup');
				for ($i = 1; isset($pickuplocs['id' . $i]); $i++) {
					if ($pickuplocs['id' . $i] == $customer->getSchrackPickup()) {
						$this->addLink(
								$this->__('Selected Pickup Warehouse: %s', $this->escapeHtml($pickuplocs['name' . $i])), 
								Mage::getUrl('customer/account/edit/'),
                                '', false, array(), 20, null, null, '', '', true
						);
						
						break;
					}
				}
			}
		}
	}
    
    
        public function getLinks()
    {
        return $this->_links;
    }

	public function getWelcome() {
        if (empty($this->_data['welcome'])) {
            if (Mage::isInstalled() && Mage::getSingleton('customer/session')->isLoggedIn()) {
				$customer = Mage::getSingleton('customer/session')->getCustomer();
				$salutation = $customer->getName();
                $this->_data['welcome'] = $this->__($this->htmlEscape($salutation));
            } else {
                $this->_data['welcome'] = Mage::getStoreConfig('design/header/welcome');
            }
        }

		return $this->_data['welcome'];
	}

    public function getLoggedInCustomer() {
        return Mage::getSingleton('customer/session')->getLoggedInCustomer();
    }
}