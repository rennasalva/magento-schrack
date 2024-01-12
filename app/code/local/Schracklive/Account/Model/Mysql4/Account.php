<?php

class Schracklive_Account_Model_Mysql4_Account extends Mage_Core_Model_Mysql4_Abstract {

	protected function _construct() {
		$this->_init('account/account', 'account_id');
	}

	protected function _beforeSave(Mage_Core_Model_Abstract $account) {
		$account->setUpdatedBy(Mage::helper('schrack/mysql4')->getChangeIdentifier());
		$account->setUpdatedAt(now());
		if (!$account->getId()) {
			$account->setCreatedBy($account->getUpdatedBy());
			$account->setCreatedAt($account->getUpdatedAt());
		}

		return $this;
	}

	public function loadByWwsCustomerId(Schracklive_Account_Model_Account $account, $wwsCustomerId) {
		$this->load($account, $wwsCustomerId, 'wws_customer_id');

		return $this;
	}

	public function getIdByWwsCustomerId($wwsCustomerId) {
		$select = $this->_getReadAdapter()->select()
						->from($this->getTable('account/account', $this->getIdFieldName()))
						->where('wws_customer_id=:customer_id');
		return $this->_getReadAdapter()->fetchOne($select, array('customer_id' => $wwsCustomerId));
	}

}
