<?php

class Schracklive_SchrackCustomer_Helper_Address_Api {

	public function replaceLocation($wwsCustomerId, $wwsAddressNumber, $addressData) {
		$account = Mage::getModel('account/account')->loadByWwsCustomerId($wwsCustomerId);
		if (!$account->getId()) {
			throw new Mage_Api_Exception('customer_not_exists', "WWS customer id {$wwsCustomerId}");
		}

		$address = Mage::getModel('customer/address')->loadByWwsAddressNumber($wwsCustomerId, $wwsAddressNumber);
		if ( ! $address->getId() ) {
			$address = Mage::getModel('customer/address')->loadByWwsAddressNumber($wwsCustomerId, Schracklive_SchrackCustomer_Model_Address::NO_ADDRESS_NUMBER);
			// DLA: Could be the false one if there was more than one stored without contact number.
			//      And, yes, will overwrite the false one.
			//      But should be compensated when the other updates come back and will overwrite the remaining ones...
		}
		try {
			$this->_mapData($addressData);

			if ($address->getId()) {
				// update
				foreach ($addressData as $key => $value) {
					$address->setData($key, $value);
				}
			} else {
				// create
				$address->setData($addressData);
				$address->setCustomerId($account->getSystemContact()->getId());
				$address->setSchrackWwsAddressNumber($wwsAddressNumber);
			}
			if ( Mage::helper('schrackcore/model')->isModified($address) ) {
				$address->save();
			}

			/*
			 * DLA, 2016-04-20: default billing address no must always be 0 and cannot be changed ton another record!!!
			if ( $addressData['is_default_billing'] ) {
				$customer = $account->getSystemContact();
				if ($customer->getData('default_billing') != $address->getId()) {
					$customer->setData('default_billing', $address->getId());
					$customer->save();
				}
			}
			*/
			if ( $addressData['is_default_shipping'] ) {
				$customer = $account->getSystemContact();
				if ($customer->getData('default_shipping') != $address->getId()) {
					$customer->setData('default_shipping', $address->getId());
					$customer->save();
				}
			}
		} catch (Mage_Core_Exception $e) {
			throw new Mage_Api_Exception('data_invalid', $e->getMessage());
		}

		return $address->getId();
	}

	public function deleteLocation($wwsCustomerId, $wwsAddressNumber, $deleteEntireCustomer = false) {
		$address = Mage::getModel('customer/address')->loadByWwsAddressNumber($wwsCustomerId, $wwsAddressNumber);

		if (!$address->getId()) {
			// throw new Mage_Api_Exception('not_exists', "WWS customer id {$wwsCustomerId}, address number {$wwsAddressNumber} - cannot delete");
            // just create logfile entry intstead of exception:
            Mage::log("WWS customer id {$wwsCustomerId}, address number {$wwsAddressNumber} not existing - cannot delete address");
		}

		$account = Mage::getModel('account/account')->loadByWwsCustomerId($wwsCustomerId);
		$systemContact = $account->getSystemContact();
		if ( $systemContact && $systemContact->getDefaultShipping() == $address->getId()  ) {
		    if ( $deleteEntireCustomer ) {
		        return true; // whole account is getting deleted, so ignore delete of this default shipping
                             // address without any error, it will be deleted when account gets deleted anyhow
            }
			throw new Mage_Api_Exception('data_invalid', "WWS customer id {$wwsCustomerId}, address number {$wwsAddressNumber} - cannot delete default shipping address.");
		}

		try {
			$address->delete();
		} catch (Mage_Core_Exception $e) {
			throw new Mage_Api_Exception('not_deleted', $e->getMessage());
		}

		return true;
	}

	protected function _mapData(array &$data) {
		$map = array(
			'name1' => 'lastname',
			'name2' => 'middlename',
			'name3' => 'firstname',
		);
		foreach ($map as $alias => $name) {
			if (isset($data[$alias])) {
				$data[$name] = $data[$alias];
				unset($data[$alias]);
			}
		}
	}

}
