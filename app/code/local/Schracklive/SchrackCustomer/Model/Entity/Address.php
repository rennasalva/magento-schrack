<?php

class Schracklive_SchrackCustomer_Model_Entity_Address extends Mage_Customer_Model_Entity_Address {

	public function loadByWwsAddressNumber(Schracklive_SchrackCustomer_Model_Address $address, $wwsCustomerId, $wwsAddressNumber) {
		// TODO: optimize DB queries, so we get system contact id without loading account and system contact model

		$account = Mage::getModel('account/account')->loadByWwsCustomerId($wwsCustomerId);
		if (!$account->getId()) {
			return $this;
		}

        // TODO: Achtung den Aufruf getId() überprüfen! Darf nicht auf ein null-Objekt verweisen! (ea)

		// getIdByWwsAddressNumber()
		$select = $this->_getReadAdapter()->select()
				->from(array('e' => $this->getEntityTable()), array('e.'.$this->getEntityIdField()))
			->where('e.parent_id=?', $account->getSystemContact()->getId())
			->where('e.schrack_wws_address_number=?', $wwsAddressNumber);

		if ($id = $this->_getReadAdapter()->fetchOne($select)) {
			$this->load($address, $id);
		} else {
			$address->setData(array());
		}
		return $this;
	}

	protected function _beforeSave(Varien_Object $address) {
		parent::_beforeSave($address);

		$address->setUpdatedBy(Mage::helper('schrack/mysql4')->getChangeIdentifier());
		if (!$address->getId()) {
			$address->setCreatedBy($address->getUpdatedBy());
		}
	}

}
