<?php

class Schracklive_SchrackCustomer_Block_Address_Book extends Mage_Customer_Block_Address_Book {

	public function mayEditDefaultBilling() {
		// group_id = 5 contact / group_id = 10 prospect (defined in DB-Table core_config_data: prospect_group/contact_group):
		if ($this->getCustomer()->isContact() || $this->getCustomer()->isProspect()) {
			return false;
		}
		return true;
	}

	public function getFilteredAddresses () {
	    $allAddresses = $this->getAdditionalAddresses();
        $addressFilter = Mage::registry('address_filter');
        if ( ! is_string($addressFilter) || $addressFilter == '' ) {
            return $allAddresses;
        }
	    $filteredAddresses = [];
	    foreach ( $allAddresses as $address ) {
	        if (    stripos(implode('|',$address->getStreet()),$addressFilter) !== false
                || stripos($address->getLastname(),$addressFilter) !== false
                || stripos($address->getFirstname(),$addressFilter) !== false
                || stripos($address->getCity(),$addressFilter) !== false
                || stripos($address->getPostcode(),$addressFilter) !== false
                 || stripos($address->getCountryModel()->getName(),$addressFilter) !== false
            ) {
                $filteredAddresses[] = $address;
            }
        }
	    return $filteredAddresses;
    }
}
