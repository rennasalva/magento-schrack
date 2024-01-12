<?php

/**
 * A helper to re-unify methods defined in Mage_Customer_Model_Address_Abstract.
 *
 * They are overridden in Schracklive_SchrackCustomer_Model_Address
 * and Schracklive_SchrackSales_Model_Quote_Address
 * and Schracklive_SchrackSales_Model_Order_Address.
 */
class Schracklive_SchrackCustomer_Helper_Address {

	const DEFAULT_ADDRESS_TYPE = 2;

	/**
	 * Validate address attribute values.
	 *
	 * @param Mage_Customer_Model_Address_Abstract $address
	 * @return bool
	 */
	public function validate(Mage_Customer_Model_Address_Abstract $address) {
		$errors = array();
		$helper = Mage::helper('customer');
		$address->implodeStreetAddress();

		if (!Zend_Validate::is($address->getStreet(1), 'NotEmpty')) {
			$errors[] = $helper->__('Please enter street.');
		}

		if (!Zend_Validate::is($address->getCity(), 'NotEmpty')) {
			$errors[] = $helper->__('Please enter city.');
		}

		$_havingOptionalZip = Mage::helper('directory')->getCountriesWithOptionalZip();
		if (!in_array($address->getCountryId(), $_havingOptionalZip) && !Zend_Validate::is($address->getPostcode(), 'NotEmpty')) {
			$errors[] = $helper->__('Please enter the zip/postal code.');
		}

		if (!Zend_Validate::is($address->getCountryId(), 'NotEmpty')) {
			$errors[] = $helper->__('Please enter country.');
		}

		if (empty($errors) || $address->getShouldIgnoreValidation()) {
			return true;
		}

        return $errors;
	}

	/**
	 * Tells if the address is one of a Schrack account.
	 *
	 * return bool
	 */
	public function belongsToAccount(Mage_Customer_Model_Address_Abstract $address) {
		if ($address->getCustomer() && $address->getCustomer()->isSystemContact()) {
			return true;
		}
		return false;
	}

}
