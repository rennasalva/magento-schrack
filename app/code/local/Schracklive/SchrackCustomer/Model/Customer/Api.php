<?php

class Schracklive_SchrackCustomer_Model_Customer_Api extends Mage_Customer_Model_Customer_Api {

	public function create($customerData)
	{
		if ($customerData['schrack_wws_customer_id'] ||
			$customerData['schrack_wws_contact_number'] ||
			$customerData['schrack_user_principal_name']
			) {
			$this->_fault('data_invalid', 'May not create employees or WWS contacts.');
		}
		return parent::create($customerData);
	}

	public function update($customerId, $customerData)
	{
		if ($customerData['schrack_wws_customer_id'] ||
			$customerData['schrack_wws_contact_number'] ||
			$customerData['schrack_user_principal_name']
			) {
			$this->_fault('data_invalid', 'May not update employees or WWS contacts.');
		}
		return parent::update($customerId, $customerData);
	}

	public function replaceContact($wwsCustomerid, $wwsContactNumber, $customerData)
	{
		return Mage::helper('schrackcustomer/api')->replaceContact($wwsCustomerid, $wwsContactNumber, $customerData);
	}

	public function deleteContact($wwsCustomerid, $wwsContactNumber)
	{
		return Mage::helper('schrackcustomer/api')->deleteContact($wwsCustomerid, $wwsContactNumber);
	}

}
