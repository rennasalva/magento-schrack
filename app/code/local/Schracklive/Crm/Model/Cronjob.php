<?php

class Schracklive_Crm_Model_Cronjob extends Mage_Core_Model_Abstract {

	public function processPending() {
		$accounts = Mage::getResourceModel('account/account_collection')
				->addFieldToFilter('wws_customer_id', array('neq' => ''))
				->addFieldToFilter('crm_status', array('neq' => Schracklive_Crm_Helper_Data::STATUS_OK))
				->load();
		foreach ($accounts as $account) {
			$markAsProcessed = true;
			if (!$this->_processPendingAccount($account)) {
				continue;
			}
			if (!$this->_processPendingContacts($account)) {
				$markAsProcessed = false;
			}
			if (!$this->_processPendingAddresses($account)) {
				$markAsProcessed = false;
			}
			if ($markAsProcessed) {
				$account->setCrmStatus(Schracklive_Crm_Helper_Data::STATUS_OK);
				$account->save();
			}
		}
	}

	protected function _processPendingAccount(Schracklive_Account_Model_Account $account) {
		$success = true;
		if ($account->getCrmStatus() != Schracklive_Crm_Helper_Data::STATUS_ACCOUNT_PENDING) {
			return true;
		}

		try {
			if (Mage::getSingleton('crm/connector')->putNewAccount($account)) {
				$account->setCrmStatus(Schracklive_Crm_Helper_Data::STATUS_RELATIONS_PENDING);
				$account->save();
			} else {
				throw Mage::exception('Schracklive_Crm', Mage::helper('crm')->__('Could not create CRM account.'));
			}
		} catch (Exception $e) {
			Mage::logException($e);
			$success = false;
		}
		return $success;
	}

	protected function _processPendingContacts(Schracklive_Account_Model_Account $account) {
		$success = true;
		/** @var $contacts Schracklive_SchrackCustomer_Model_Entity_Customer_Collection */
		$contacts = Mage::getResourceModel('customer/customer_collection')
				->addAttributeToSelect('*') // required for putCustomer()
				->setAccountIdFilter($account->getAccountId())
				->addFieldToFilter('group_id', array('in' => array(Mage::getStoreConfig('schrack/shop/contact_group'),Mage::getStoreConfig('schrack/shop/prospect_group'))));
		foreach ($contacts as $contact) {
			try {
				$contact->setSchrackWwsCustomerId($account->getWwsCustomerId());
				$wwsContactNumber = Mage::getSingleton('crm/connector')->putCustomer($contact,false,true);
				if ( ! $wwsContactNumber ) {
					$wwsContactNumber = Schracklive_SchrackCustomer_Model_Customer::NO_CONTACT_NUMBER;
				}
				$contact->setSchrackWwsContactNumber($wwsContactNumber);
				$contact->setGroupId(Mage::getStoreConfig('schrack/shop/contact_group'));
				$contact->save();
			} catch (Exception $e) {
				Mage::logException($e);
				$success = false;
			}
		}
		return $success;
	}

	protected function _processPendingAddresses(Schracklive_Account_Model_Account $account) {
		$success = true;
		foreach ($account->getAddresses() as $address) {
			try {
				/* for sync reason we send always all addresses
				if ($address->getSchrackWwsAddressNumber() || $address->getSchrackType() == 1) {
					continue;
				}
				*/
				Mage::getSingleton('crm/connector')->putAddress($address);
				$address->save();
			} catch (Exception $e) {
				Mage::logException($e);
				$success = false;
			}
		}
		return $success;
	}

}
