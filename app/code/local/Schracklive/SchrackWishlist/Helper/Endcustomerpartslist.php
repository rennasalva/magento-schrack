<?php

class Schracklive_SchrackWishlist_Helper_Endcustomerpartslist extends Mage_Core_Helper_Abstract {

    public function getPartslist() {
        $session = $this->_getSession();
        $partslistModel = Mage::getModel('schrackwishlist/partslist');
        $partslistId = $session->getEndcustomerPartslistId();

        if (isset($partslistId)) {
            $partslistModel->load($partslistId);
        } else {
            $hashedId = $this->getHashedId();

            if ( isset($hashedId) ) {
                $partslistModel->loadByCustomerAndHashedId($this->getCustomerId(), $hashedId);
                $partslistId = $partslistModel->getId();
            }

            if ( ! isset($partslistId) ) {
                $partslistModel->create($this->getCustomerId(), $this->__('Endcustomer partslist'), $this->__('Automatically created by endcustomer partslist'), 1);
                $partslistId = $partslistModel->getId();
                $session->setEndcustomerPartslistId($partslistId);
            }
        }

        setcookie('pl', $partslistModel->getHashedId(), time()+60*60*24*30, '/');

        return $partslistModel;
    }

    public function getHashedId() {
        $hashedId = Mage::app()->getRequest()->getParam('pl', null);
        if ( ! isset($hashedId) ) {
            $hashedId = isset($_COOKIE['pl']) ? $_COOKIE['pl'] : null;
        }

        return $hashedId;
    }

    public function getPartslistUrl() {
        $partslistModel = $this->getPartslist();
        return $this->_getUrl('schrackwishlist/endcustomerpartslist', array(
            'idkey' => Mage::app()->getRequest()->getParam('idkey'),
            'pl' => $partslistModel->getHashedId()
        ));
    }

    public function getCustomerId() {
        $customerId = null;
        $idKey = Mage::app()->getRequest()->getParam('idkey');
        if (isset($idKey)) {
            $ecplCustomer = Mage::getModel('schrackwishlist/endcustomerpartslist_customer')->loadByIdKey($idKey);
            if ( !($ecplCustomer && $ecplCustomer->getCustomerId()) ) {
                throw new Exception('No customer id found by idkey');
            }
            $customer = Mage::getModel('customer/customer')->load($ecplCustomer->getCustomerId());
            if ($customer && $customer->getId()) {
                $customerId = $customer->getId();
                $this->_getSession()->setEndcustomerCustomerId($customerId);
                $this->_setCustomerIdToCookie($customerId);
            } else {
                throw new Exception('No customer id found by id');
            }
        } else {
            $customerId = $this->_getSession()->getEndcustomerCustomerId();
            if ( !isset($customerId) ) {
                $customerId = $this->_getCustomerIdFromCookie();
                if ( !isset($customerId) ) {
                    throw new Exception('No customer id found');
                }
            }
        }
        return $customerId;
    }

    /**
     * @return int
     * since magento uses a configured hostname for the cookie, we need to set our own customer id cookie
     */
    private function _getCustomerIdFromCookie() {
        $customerId = null;
        if ( isset($_COOKIE['cid']) ) {
            $customerId = $_COOKIE['cid'];
            $this->_setCustomerIdToCookie($customerId); // re-set cookie
        }
        return $customerId;
    }


    private function _setCustomerIdToCookie($customerId) {
        setcookie('cid', $customerId, time()+60*60*24*30, '/');
    }

    public function getEndcustomerCustomer() {
        $customerId = $this->getCustomerId();
        if ( isset($customerId) ) {
            $ecplCustomer = Mage::getModel('schrackwishlist/endcustomerpartslist_customer')->loadByCustomerId($customerId);
            return $ecplCustomer;
        } else {
            return null;
        }
    }

    public function getEndcustomerCustomerBySessionCustomer() {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        if ( $customer && $customer->getId() ) {
            $customerId = $customer->getId();
            $ecplCustomer = Mage::getModel('schrackwishlist/endcustomerpartslist_customer')->loadByCustomerId($customerId);
            return $ecplCustomer;
        } else {
            throw new Exception('No customer found in session.');
            return null;
        }
    }

    private function _getSession() {
        return Mage::getSingleton('core/session');
    }
} 