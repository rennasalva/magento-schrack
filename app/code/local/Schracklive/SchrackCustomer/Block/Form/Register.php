<?php

class Schracklive_SchrackCustomer_Block_Form_Register extends Mage_Customer_Block_Form_Register {

	public function getHadError() {
		return Mage::getSingleton('customer/session')->getData("had_error");
	}

    public function getZipCodeRegexes() {
        return Mage::getModel('geoip/country')->getZipCodeRegexes();
    }

}
