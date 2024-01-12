<?php

class Schracklive_SchrackCustomer_Model_Address_Api extends Mage_Customer_Model_Address_Api {

	public function create($customerId, $addressData) {
		if ($addressData['schrack_wws_customer_id'] ||
			$addressData['schrack_wws_address_number']
			) {
			$this->_fault('data_invalid', 'May not create locations (WWS addresses).');
		}
		return parent::create($customerId, $addressData);
	}

	public function update($addressId, $addressData) {
		if ($addressData['schrack_wws_customer_id'] ||
			$addressData['schrack_wws_address_number']
			) {
			$this->_fault('data_invalid', 'May not update locations (WWS addresses).');
		}
		return parent::update($addressId, $addressData);
	}

	public function replaceLocation($wwsCustomerid, $wwsAddressNumber, $addressData) {
		return Mage::helper('schrackcustomer/address_api')->replaceLocation($wwsCustomerid, $wwsAddressNumber, $addressData);
	}

	public function deleteLocation($wwsCustomerid, $wwsAddressNumber) {
		return Mage::helper('schrackcustomer/address_api')->deleteLocation($wwsCustomerid, $wwsAddressNumber);
	}

}
