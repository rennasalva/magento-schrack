<?php


class Schracklive_SchrackShipping_Block_Trackandtrace extends Mage_Core_Block_Template {

    private $helper;
    
    protected function _construct() {
        parent::_construct();
        
        $this->helper = Mage::helper('schrackshipping/trackandtrace');
    }


    protected function getStatusFromId($id) {
        return $this->helper->getStatusFromId($id);
    }
    protected function getStatusNameFromId($id) {
        return $this->helper->getStatusNameFromId($id);
    }
    protected function getStatusDescriptionFromId($id, $userDescription = null) {
        return $this->helper->getStatusDescriptionFromId($id, $userDescription);
    }
    protected function isMainStatus($id) {
        return $this->helper->isMainStatus($id);
    }
    protected function getDateFormatted($date) {
        return $this->helper->getDateFormatted($date);
    }
    protected function getTimeFormatted($date) {
        return $this->helper->getTimeFormatted($date);
    }
}

?>
