<?php

class Schracklive_Crm_Model_Customer_Address_Observer {

	/**
	 * 
	 * @event customer_address_save_before
	 */
	public function save($observer) {
		if (Mage::registry('schrack_api_session')) {
			return;
		}

		$address = $observer->getCustomerAddress();
		/* @var $address Schracklive_SchrackCustomer_Model_Address */
		if (!$address->belongsToAccount()) {
			return;
		}

		if ($address->getCustomer()->getAccount()->getCrmStatus() != Schracklive_Crm_Helper_Data::STATUS_OK) {
			return;
		}

		try {
			Mage::getSingleton('crm/connector')->putAddress($address);
		} catch (Exception $e) {
			Mage::logException($e);
			throw Mage::exception('Schracklive_Crm', Mage::helper('crm')->__('Address could not be saved.'));
		}
	}

	/**
	 *
	 * @event customer_address_delete_before
	 */
	public function delete($observer) {
		if (Mage::registry('schrack_api_session')) {
			return;
		}

		$address = $observer->getCustomerAddress();
		/* @var $address Schracklive_SchrackCustomer_Model_Address */
		if (!$address->belongsToAccount()) {
			return;
		}

		if ($address->getSchrackWwsAddressNumber() === '0') {
			return;
		}

		try {
			if (!Mage::getSingleton('crm/connector')->deleteAddress($address)) {
				throw Mage::exception('Schracklive_Crm', 'CRM did not delete the address.');
			}
		} catch (Exception $e) {
			Mage::logException($e);
			throw Mage::exception(Mage::helper('crm')->__('Address could not be deleted.'));
		}
	}

}
