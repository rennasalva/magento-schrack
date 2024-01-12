<?php

class Schracklive_SchrackCustomer_Model_Address_Api_V2 extends Mage_Customer_Model_Address_Api_V2 {

	public function create($customerId, $addressData) {
		if ($addressData->schrack_wws_customer_id ||
				$addressData->schrack_wws_address_number
		) {
			$this->_fault('data_invalid', 'May not create locations (WWS addresses).');
		}
		return parent::create($addressData);
	}

	public function update($addressId, $addressData) {
		if ($addressData->schrack_wws_customer_id ||
				$addressData->schrack_wws_address_number
		) {
			$this->_fault('data_invalid', 'May not update locations (WWS addresses).');
		}
		return parent::update($addressId, $addressData);
	}

	public function replaceLocation($wwsCustomerId, $wwsAddressNumber, $addressData) {
		return Mage::helper('schrackcustomer/address_api')->replaceLocation($wwsCustomerId, $wwsAddressNumber,
				get_object_vars($addressData));
	}

	public function deleteLocation($wwsCustomerId, $wwsAddressNumber) {
		return Mage::helper('schrackcustomer/address_api')->deleteLocation($wwsCustomerId, $wwsAddressNumber);
	}

}
