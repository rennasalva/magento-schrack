<?php

class Schracklive_Schrack_Helper_Data extends Mage_Core_Helper_Abstract {

	const SCHRACK_ATTRIBUTESET_NAME  = 'Schrack';
    
    var $_schrackAttributeSetId;
    
	/**
	 * Get the country TLD to use for domains
	 *
	 * @return string
	 */
	function getCountryTld() {
		return Mage::getStoreConfig('schrack/general/country');
	}

	/**
	 * Get the country code to use for connection with the WWS
	 * @return mixed
	 * @throws Schracklive_Schrack_Exception|Mage_Core_Exception
	 */
	function getWwsCountry() {
		$countries = array(
			0 => 'AT',
			10 => 'HU',
			20 => 'HR',
			30 => 'BE',
			35 => 'RS',
			40 => 'CZ',
			50 => 'SI',
			55 => 'BA',
			60 => 'PL',
			70 => 'RO',
			80 => 'SK',
			90 => 'BG',
            100 => 'SA',
            110 => 'RU',
		);
		$wwsCountryId = (int)Mage::getStoreConfig('schrack/general/wws_country');
		if (isset($countries[$wwsCountryId])) {
			return $countries[$wwsCountryId];
		} else {
			throw Mage::exception('Schracklive_Schrack', 'Invalid WWS country id: '.$wwsCountryId);
		}
	}
    
    function getSchrackAttributeSetID () {
        if ( ! $this->_schrackAttributeSetId ) {
            $attributSetCollection = Mage::getModel("eav/entity_attribute_set")->getCollection();
            $attributeSet = $attributSetCollection->addFieldToFilter("attribute_set_name",self::SCHRACK_ATTRIBUTESET_NAME)->getFirstItem();
            $this->_schrackAttributeSetId = $attributeSet->getAttributeSetId();
        }
        return $this->_schrackAttributeSetId;
    }

    function getAdvisor ()
    {
        $advisor = Mage::getSingleton('customer/session')->getCustomer()->getAdvisor();
        if (!is_object($advisor)) {
            $principalName = Mage::getStoreConfig('schrack/shop/default_advisor');
            if ($principalName) {
                $advisor = Mage::getModel('customer/customer')->loadByUserPrincipalName($principalName);
            }
        }
        return $advisor;
    }

    public function fixUrlPath ( $path ) {
        if ( strncmp($path,'/shop/',6) == 0 ) {
            $path = Mage::getUrl(substr($path,6));
        }
        return $path;
    }

    public function getMaximumOrderAmount () {
        $maxAmount = Mage::getStoreConfig('sales/maximum_order/amount');
        if ( ! is_numeric($maxAmount) || $maxAmount < 1 ) {
            $maxAmount = 100;
        }
        return $maxAmount;
    }
}
