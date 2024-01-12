<?php
class Schracklive_SchrackCatalogInventory_Model_Stock extends Mage_CatalogInventory_Model_Stock {

    const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    private $_helper;
    
    public function getId () {
        return Mage_Core_Model_Abstract::getId();
    }

    public function getIdByNumber ( $number ) {
        return $this->_getResource()->getIdByNumber($number);
    }

    public function getIdByNumberAndLocation ( $number, $location ) {
        if ( $location == null || $location <= ' ' ) {
            return $this->_getResource ()->getIdByNumber($number);
        } else {
            return $this->_getResource ()->getIdByNumberAndLocation($number,$location);
        }
    }

    public function getDeliveryTimeAbbreviation () {
        $this->_getHelper();
        $hours = $this->getDeliveryHours();
        if ( $hours < 1 ) {
            $res = $this->_helper->__('pku');
        }
        else {
            $days = (int) ceil($hours / 24.0);
            if ( $days > 9 ) {
                $weeks = (int) ceil($days / 5);
                $res = "" . $weeks . $this->_helper->__('W');
            }
            else {
                $res = "" . $days . $this->_helper->__('D');
            }
        }
        return $res;
    }

    public function getFormattedDeliveryTime ( $prefixDeliverable = true ) {
        $this->_getHelper();
        $in = $prefixDeliverable ? $this->_helper->__('Deliverable in') : $this->_helper->__('in');
        $hours = $this->getDeliveryHours();
        $days = (int) ceil($hours / 24.0);
        if ( $days > 9 ) {
            $weeks = (int) ceil($days / 5);
            $tx = $weeks == 1 ? 'week' : 'weeks';
            return $in.' '.$weeks.' '.$this->_helper->__($tx);
        } 
        else {
            $tx = $days == 1 ? 'day' : 'days';
            return $in.' '.$days.' '.$this->_helper->__($tx);
        }
    }

    public function isLocked () {
        $lockedUntil = $this->getLockedUntil();
        if ( ! $lockedUntil ) {
            return false;
        }
        $lockedUntil = (new DateTime($lockedUntil))->format(self::DATE_TIME_FORMAT);
        $now = (new DateTime())->format(self::DATE_TIME_FORMAT);
        return $now <= $lockedUntil;
    }

    private function _getHelper() {
        if ( ! isset($this->_helper) ) {
            $this->_helper = Mage::helper('schrackcataloginventory/stock');
        }
        return $this->_helper;
    }

    public function loadByStockNumbers(array $stockNos) {
        return $this->getCollection()->addFieldToFilter('stock_number', array('in' => $stockNos))->load();
    }
}

    

?>
