<?php
/**
 * Created by IntelliJ IDEA.
 * User: d.laslov
 * Date: 12.09.2014
 * Time: 11:27
 */

class Schracklive_SchrackCatalog_Block_Quantitywarningpopup extends Mage_Core_Block_Template {

    var $advisor = null;
    var $customer = null;
    var $loggedIn = null;
    var $qty;
    var $availableQty = 0;
    var $customerQty;
    var $sku;
    var $folloupProduct;
    var $isRestricted;

    public function getAdvisor () {
        if ( $this->advisor == null ) {
            $this->advisor = Mage::helper('schrack')->getAdvisor();
        }
        return $this->advisor;
    }

    public function getCustomer () {
        if ( $this->loggedIn == null ) {
            $session = Mage::getSingleton('customer/session');
            $this->loggedIn = $session->isLoggedIn();
            if ( $this->loggedIn ) {
                $this->customer = Mage::getSingleton('customer/session')->getCustomer();
            }
        }
        return $this->customer;
    }

    /**
     * @return mixed
     */
    public function getFolloupProduct()
    {
        return $this->folloupProduct;
    }

    /**
     * @param mixed $folloupProduct
     */
    public function setFolloupProduct($folloupProduct)
    {
        $this->folloupProduct = $folloupProduct;
    }

    /**
     * @return mixed
     */
    public function getQty()
    {
        return $this->qty;
    }

    /**
     * @param mixed $qty
     */
    public function setQty($qty)
    {
        $this->qty = $qty;
    }

    /**
     * @return mixed
     */
    public function getAvailableQty()
    {
        return $this->availableQty;
    }

    /**
     * @param mixed $qty
     */
    public function setAvailableQty($qty)
    {
        $this->availableQty = $qty;
    }

    /**
     * @return mixed
     */
    public function getCustomerQty()
    {
        return $this->customerQty;
    }

    /**
     * @param mixed $customerQty
     */
    public function setCustomerQty($customerQty)
    {
        $this->customerQty = $customerQty;
    }

    /**
     * @return mixed
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * @param mixed $sku
     */
    public function setSku($sku)
    {
        $this->sku = $sku;
    }

    /**
     * @return mixed
     */
    public function isRestricted()
    {
        return $this->isRestricted;
    }

    /**
     * @param mixed $isRestricted
     */
    public function setIsRestricted($isRestricted)
    {
        $this->isRestricted = $isRestricted;
    }
}