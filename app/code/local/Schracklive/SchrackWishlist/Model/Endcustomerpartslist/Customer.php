<?php

class Schracklive_SchrackWishlist_Model_Endcustomerpartslist_Customer extends Mage_Core_Model_Abstract {
    
    public function __construct() {
        parent::__construct();
        $this->_setResourceModel('schrackwishlist/endcustomerpartslist_customer');
    }
    
    /**
     * For now, always set active flag
     *
     * @return Mage_Wishlist_Model_Wishlist
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();
        $this->setIsActive(1);
        return $this;
    }

    public function getCustomer() {
        $customer = Mage::getModel('customer/customer')->load($this->getCustomerId());
        return $customer;
    }

    public function loadByIdKey($idKey) {
        return $this->getCollection()
            ->addFieldToFilter('id_key', $idKey)
            ->getFirstItem();
    }

    public function loadByCustomerId($customerId) {
        return $this->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->getFirstItem();
    }


    /**
     * creates a new idkey for this customer from the system customer data.
     * will NOT set the idkey in this instance, but instead return it
     * for later setting.
     */
    public function createIdKey() {
        $customer = $this->getCustomer();

        $idString = $customer->getSchrackWwsCustomerId() . $customer->getSchrackWwsContactNumber() . $customer->getEmail() . sha1(uniqid(mt_rand(), true) );
        return $idString;
    }

    public function getBannerUrl() {
        $url = parent::getBannerUrl();
        if ( !($url && strlen($url)) ) {
            $customer = $this->getCustomer();
            $url = Mage::getStoreConfig('schrack/general/imageserver') . '/endcustomerpartslist/' . $customer->getSchrackWwsCustomerId() . '/banner.png';
        }
        return $url;
    }
    public function getWelcomeUrl() {
        $url = parent::getWelcomeUrl();
        if ( !($url && strlen($url)) ) {
            $customer = $this->getCustomer();
            $url = Mage::getStoreConfig('schrack/general/imageserver') . '/endcustomerpartslist/' . $customer->getSchrackWwsCustomerId() . '/welcome.png';
        }
        return $url;
    }
}

?>