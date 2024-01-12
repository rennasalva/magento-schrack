<?php

class Schracklive_SchrackCustomer_Model_Customer_Attribute_Backend_Password extends Mage_Customer_Model_Customer_Attribute_Backend_Password {

	public function beforeSave($object) {
		$password = trim($object->getPassword());
		$len = Mage::helper('core/string')->strlen($password);
		if ($len) {
			if ($len < 6) {
				Mage::throwException(Mage::helper('customer')->__('The password must have at least 6 characters. Leading or trailing spaces will be ignored.'));
			}
			if (strpos($password, ':') !== FALSE) {
				Mage::throwException(Mage::helper('customer')->__('Invalid characters in password. : are not allowed.'));
			}
			$object->setPasswordHash($object->hashPassword($password));
		}
	}

	public function validate($object) {
		return parent::validate($object);
	}

}

?>
