<?php

class Schracklive_SchrackCustomer_Block_Widget_Shopcontact extends Mage_Core_Block_Template {

	protected $_advisor;

	public function _construct() {
		parent::_construct();

		// default template location
		if (!$this->getTemplate()) {
			$this->setTemplate('customer/widget/shopcontact.phtml');
		}
	}

	/**
	 * @return Schracklive_SchrackCustomer_Model_Customer
	 */
	public function getAdvisor() {
		if (!$this->_advisor) {
			$this->_advisor = new Varien_Object(); // a NULL object

			if ($this->getData('advisor')) {
				$advisor = Mage::getModel('customer/customer')->load($this->getData('advisor'));
				if ($advisor->getId()) {
					$this->_advisor = $advisor;
				}
			} else {
				if ($this->getData('customer')) {
					$customer = Mage::getModel('customer/customer')->load($this->getData('customer'));
					if ($customer->getId()) {
						$advisor = $customer->getAdvisor();
						if ($advisor) {
							$this->setData('advisor', $advisor->getId());
							$this->_advisor = $advisor;
						}
					}
				}
			}
		}

		return $this->_advisor;
	}

	public function getTelephone() {
		$telephone = $this->getAdvisor()->getSchrackTelephone();

        if (!$telephone) {
            $resource        = Mage::getSingleton('core/resource');
            $readConnection  = $resource->getConnection('core_read');

            if (Mage::helper('schrackcustomer')->getPersonalizedDefaultAdvisor()) {
                $query = "SELECT email FROM customer_entity WHERE schrack_user_principal_name LIKE '" . Mage::helper('schrackcustomer')->getPersonalizedDefaultAdvisor() . "'";

                $advisorEmail = $readConnection->fetchOne($query);

                if ($advisorEmail) {
                    $standardContact = Mage::getModel('customer/customer');
                    $standardContact->setWebsiteId(Mage::app()->getWebsite()->getId());
                    $advisor = $standardContact->loadByEmail($advisorEmail);
                    $telephone = $advisor->getSchrackTelephone();
                }
            }
        }

		return $telephone ? $telephone : Mage::getStoreConfig('general/store_information/phone');
	}

	public function getFax() {
		$fax = $this->getAdvisor()->getSchrackFax();

        if (!$fax) {
            $resource        = Mage::getSingleton('core/resource');
            $readConnection  = $resource->getConnection('core_read');

            if (Mage::helper('schrackcustomer')->getPersonalizedDefaultAdvisor()) {
                $query = "SELECT email FROM customer_entity WHERE schrack_user_principal_name LIKE '" . Mage::helper('schrackcustomer')->getPersonalizedDefaultAdvisor() . "'";
                
                $advisorEmail = $readConnection->fetchOne($query);

                if ($advisorEmail) {
                    $standardContact = Mage::getModel('customer/customer');
                    $standardContact->setWebsiteId(Mage::app()->getWebsite()->getId());
                    $advisor = $standardContact->loadByEmail($advisorEmail);
                    $fax = $advisor->getSchrackFax();
                }
            }
        }

		return $fax ? $fax : Mage::getStoreConfig('general/store_information/schrack_fax');
	}

	public function getAddress() {
		return nl2br(Mage::getStoreConfig('general/store_information/address'));
	}

	public function getHomepage() {
		return 'https://www.schrack.'.strtolower(Mage::helper('schrack')->getCountryTld());
	}

	public function getEmail() {
		return $this->getAdvisor()->getEmail();
	}

	public function getEmailLink() {
		$url = $this->getEmail();
		return '<a href="mailto:'.$url.'">'.$this->fontWrap($url).'</a>';
	}

	public function getHomepageLink() {
		$url = $this->getHomepage();
		return '<a href="'.$url.'">'.$this->fontWrap($url).'</a>';
	}

	public function getFontAttributes() {
		// @todo configure
		return 'size="1" color="#868686" face="Arial" style="font-family:Arial,sans-serif;color:#868686;font-size:12px"';
	}

	public function fontWrap($value) {
		return '<font '.$this->getFontAttributes().'>'.$value.'</font>';
	}

}