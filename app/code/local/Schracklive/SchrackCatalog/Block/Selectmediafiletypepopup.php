<?php
/**
 * Created by IntelliJ IDEA.
 * User: p.huber
 * Date: 08.06.2015
 * Time: 16:27
 */

class Schracklive_SchrackCatalog_Block_Selectmediafiletypepopup extends Mage_Core_Block_Template {

    private $advisor = null;
    private $customer = null;
    private $loggedIn = null;
    private $mediaFileData = array();
    private $isRestricted;
    private $preselectedFiletypes = array();

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
     * @return array $mediaFileData
     */
    public function getMediaFileData()
    {
        return $this->mediaFileData;
    }

    /**
     * @param array $mediaFileData
     */
    public function setMediaFileData(array $mediaFileData)
    {
        $this->mediaFileData = $mediaFileData;
    }

    /**
     * @return array $preselectedFiletypes
     */
    public function getPreselectedFiletypes()
    {
        return $this->preselectedFiletypes;
    }

    /**
     * @param array $preselectedFiletypes
     */
    public function setPreselectedFiletypes(array $preselectedFiletypes)
    {
        $this->preselectedFiletypes = $preselectedFiletypes;
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