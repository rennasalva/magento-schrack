<?php

class Schracklive_Crm_Model_Account_Observer {

	/**
	 *
	 * @event account_account_save_before
	 */
	public function save($observer) {
		if (Mage::registry('schrack_api_session')) {
			return;
		}

		$account = $observer->getAccount();
		if ($account->getCrmStatus() != Schracklive_Crm_Helper_Data::STATUS_OK) {
			return;
		}
		if (!$account->getData('wws_customer_id')) {
			return;
		}

		try {
			if (!Mage::getModel('crm/connector')->putAccount($account)) {
				Mage::log('CRM did not save the account.', Zend_Log::ERR);
				throw Mage::exception('Schracklive_Crm', Mage::helper('crm')->__('Company could not be saved.'));
			}
		} catch (Exception $e) {
			Mage::logException($e);
			throw Mage::exception('Schracklive_Crm', Mage::helper('crm')->__('Company could not be saved.'));
		}
	}

}
