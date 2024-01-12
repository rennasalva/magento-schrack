<?php

abstract class Schracklive_SchrackCustomer_Model_AbstractTracking extends Mage_Core_Model_Abstract {
    public function __construct() {
        parent::__construct();
        $this->setConnection(Mage::getSingleton('core/resource')->getConnection('common_db'));
    }

    /**
     * Set date of last update and country id for tracking table
     *
     * @return Mage_Wishlist_Model_Wishlist
     */
    protected function _beforeSave () {
        parent::_beforeSave();
        $this->setCountryId(Mage::getStoreConfig('schrack/general/country'));
        if (!$this->getTrackingId())
            $this->setCreatedAt(Mage::getSingleton('core/date')->gmtDate());
        return $this;
    }

}