<?php

class Schracklive_Crm_Model_Customer_Observer {

	/**
	 *
	 * @event customer_save_before
	 */
	public function save($observer) {
		if (Mage::registry('schrack_api_session')) {
			return;
		}

		$customer = $observer->getCustomer();
		/* @var $customer Schracklive_SchrackCustomer_Model_Customer */

		if (!$customer->isRealContact()) {
		    Mage::log('Customer Entity-ID: ' . $customer->getId() . " is not real contact", null, 'contact_observer_error.log');
            Mage::log('Customer Entity-ID: ' . $customer->getId() . ' this->isContact() = ' . $customer->isContact(), null, 'contact_observer_error.log');
            Mage::log('Customer Entity-ID: ' . $customer->getId() . ' this->isInactiveContact() = ' . $customer->isInactiveContact(), null, 'contact_observer_error.log');
            Mage::log('Customer Entity-ID: ' . $customer->getId() . ' this->isDeletedContact() = ' . $customer->isDeletedContact(), null, 'contact_observer_error.log');
            Mage::log('Customer Entity-ID: ' . $customer->getId() . ' this->_isHumanContact() = ' . $customer->_isHumanContact(), null, 'contact_observer_error.log');
			if (!$customer->_isHumanContact()) {
                Mage::log($customer->isHumanContactDetailledLog(), null, 'contact_observer_error.log');
			}
			return;
		}
		if ($customer->getAccount()->getCrmStatus() != Schracklive_Crm_Helper_Data::STATUS_OK) {
            $crmState = $customer->getAccount()->getCrmStatus();
            $msg = 'Customer Entity-ID: ' . $customer->getId() . " -> CRM Status in account table is not empty (" . $crmState . ")";
            Mage::log($msg, null, 'contact_observer_error.log');
			return;
		}

		try {
			$wwsContactNumber = Mage::getSingleton('crm/connector')->putCustomer($customer,$customer->isDeletedContact() && $customer->getGroupId() != $customer->getOrigData('group_id'));
			if ( ! $wwsContactNumber ) {
                Mage::log('Customer Entity-ID: ' . $customer->getId() . " no wws id found", null, 'contact_observer_error.log');
				$wwsContactNumber = Schracklive_SchrackCustomer_Model_Customer::NO_CONTACT_NUMBER;
			}
			$customer->setSchrackWwsContactNumber($wwsContactNumber);
		} catch (Schracklive_Crm_Exception $e) {
			if ($e->getCode() == Schracklive_Crm_Model_Connector::EXCEPTION_EMAIL_NOT_UNIQUE) {
                Mage::log('Customer Entity-ID: ' . $customer->getId() . " email not unique", null, 'contact_observer_error.log');
				throw new Schracklive_SchrackCustomer_EmailNotUniqueException($e->getMessage());
			}
			Mage::logException($e);
            Mage::log('Customer Entity-ID: ' . $customer->getId() . " User could not be saved #1", null, 'contact_observer_error.log');
			throw Mage::exception('Schracklive_Crm', Mage::helper('crm')->__('User could not be saved.'));
		} catch (Exception $e) {
			Mage::logException($e);
            Mage::log('Customer Entity-ID: ' . $customer->getId() . " User could not be saved #2", null, 'contact_observer_error.log');
			throw Mage::exception('Schracklive_Crm', Mage::helper('crm')->__('User could not be saved.'));
		}
	}

	/**
	 *
	 * @event customer_delete_before
	 */
	public function delete($observer) {
		if (Mage::registry('schrack_api_session')) {
			return;
		}

		$customer = $observer->getCustomer();
		/* @var $customer Schracklive_SchrackCustomer_Model_Customer */

		if (!$customer->isRealContact()) {
			return;
		}

		try {
			if (!Mage::getSingleton('crm/connector')->deleteCustomer($customer)) {
				throw Mage::exception('Schracklive_Crm', 'CRM did not delete the contact.');
			}
		} catch (Exception $e) {
			Mage::logException($e);
			throw Mage::exception('Schracklive_Crm', Mage::helper('crm')->__('Contact could not be deleted.'));
		}
	}

}
