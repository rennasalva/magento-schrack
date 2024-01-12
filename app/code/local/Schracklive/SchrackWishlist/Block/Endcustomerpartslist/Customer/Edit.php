<?php

class Schracklive_SchrackWishlist_Block_Endcustomerpartslist_Customer_Edit extends Mage_Core_Block_Template {
    private $_customer;
    private $_endcustomerCustomer;

	protected function _prepareLayout() {
		$this->getLayout()->getBlock('head')
				->setTitle($this->__('Edit Endcustomerpartslist Customer'));

		return parent::_prepareLayout();
	}

	public function mayEdit() {
		// @todo add ACL check
		return $this->getCustomer()->isContact() || $this->getCustomer()->isProspect();
	}

    public function getCustomer() {
        if (empty($this->_customer)) {
            $this->_customer = Mage::getSingleton('customer/session')->getCustomer();
        }
        return $this->_customer;
    }

    public function getEndcustomerCustomer() {
        try {
            if (empty($this->_endcustomerCustomer)) {
                $customer = Mage::getSingleton('customer/session')->getCustomer();
                if (!($customer && $customer->getId())) {
                    throw new Exception('Must be logged in.');
                }
                $this->_endcustomerCustomer = Mage::helper('schrackwishlist/endcustomerpartslist')->getEndcustomerCustomerBySessionCustomer();

                $idKey = $this->_endcustomerCustomer->getIdKey();
                if (!isset($idKey) || $idKey === null) {
                    $idKey = $this->_endcustomerCustomer->createIdKey();
                    $this->_endcustomerCustomer->setIdKey($idKey);
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::getSingleton('core/session')->addError($this->__($e->getMessage()));
        }

        return $this->_endcustomerCustomer;
    }

}

