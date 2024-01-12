<?php

class Schracklive_SchrackGoogleAnalytics_Helper_Data extends Mage_GoogleAnalytics_Helper_Data {

    static $autoPosition = 1;
    /** @var Schracklive_SchrackCatalog_Helper_Price $priceHelper */
    protected $_priceHelper;
    protected $_customer;

    public function resetAutoPosition() {
        Schracklive_SchrackGoogleAnalytics_Helper_Data::$autoPosition = 1;
    }

    public function generateUserId() {
        $userId = '';
        /** @var Schracklive_SchrackCustomer_Model_Customer $customer */
        $customer = $this->getCustomer();
        $account = $customer->getAccount();
        if ($customer && $account && is_object($account)) {
            $userId = "'userId': '".strtoupper(Mage::helper('schrack')->getCountryTld())."/".$account->getWwsCustomerId()."/".$customer->getSchrackWwsContactNumber()."'";
        }
        return $userId;
    }

    public function getCustomer() {
        $customer = null;
        $session = Mage::getSingleton('customer/session');
        if ($session && is_object($session->getCustomer())) {
            $customer = $session->getCustomer();
        }
        return $customer;
    }

    /**
     * @param Schracklive_SchrackCatalog_Model_Product  $product
     * @param int                                       $position
     * @param Schracklive_SchrackCatalog_Model_Category $category
     * @return string
     */
    public function getDataTags($product, $position = null, $category = null, $qty = 1) {
        if ($position === null) {
            $position = Schracklive_SchrackGoogleAnalytics_Helper_Data::$autoPosition++;
        }
        if (!$this->_priceHelper) {
            $this->_priceHelper = Mage::helper('schrackcatalog/price');
        }
        if (!$this->_customer) {
            $this->_customer = Mage::getSingleton('customer/session')->getCustomer();
        }
        $categoryName = '';
        if ($category) {
            $categoryName = $category->getName();
        } elseif ($product->getPreferredCategory()) {
            $categoryName = $product->getPreferredCategory()->getName();

        }
        $price = 0;
        try {
            $price = $product->getPrice();
            // DLA20180215: removed because performance impact and data privacy, useing list price instead
            // $this->_priceHelper->getUnformattedBasicPriceForCustomer ($product, $this->_customer, $qty);
        } catch ( Exception $ex ) {
            $price = -1;
        }
        return ' data-sku="'.$product->getSku().'" data-name="'.$product->getName().'" data-position="'.$position.'" data-category="'.$categoryName.'" data-price="'.number_format($price, 2, '.', '').'"';
    }
}
