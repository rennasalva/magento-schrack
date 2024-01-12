<?php

class Schracklive_Wws_Model_Action_GetNewWwsCustomerId extends Schracklive_Wws_Model_Action_Abstract {

	protected $_requestName = 'insertwebcustomer';
	protected $_response = '';

	public function __construct(array $arguments) {
		$checkArguments = $this->_checkArguments($arguments, array(
			'account' => 'Schracklive_Account_Model_Account',
			'creator' => 'Schracklive_SchrackCustomer_Model_Customer',
			'memo' => array('string', ''),
		));
		extract($checkArguments);

		$this->_requestArguments = array();
		$attributes = array('title', 'name1', 'name2', 'name3', 'email');
		foreach ($attributes as $attribute) {
			$this->_requestArguments[$attribute] = $account->getData($attribute);
		}
		$this->_requestArguments['street'] = $account->getStreet1();
		$this->_requestArguments['postcode'] = $account->getPostcode();
		$this->_requestArguments['city'] = $account->getCity();
		$this->_requestArguments['country_id'] = $account->getCountryId();
		$this->_requestArguments['telephone'] = $account->getTelephone();
		$this->_requestArguments['fax'] = $account->getFax();
		$this->_requestArguments['creator_email'] = $creator->getEmail();
		$this->_requestArguments['memo'] = $memo;
        
        if ( isset($this->_requestArguments['memo']) && strlen(trim($this->_requestArguments['memo'])) > 0 ) {
            $this->_requestArguments['memo'] .= ';UID=';
        }
        else {
            $this->_requestArguments['memo'] = 'UID=';
        }
        // vat_identification_number
        $this->_requestArguments['memo'] .= $account->getVatIdentificationNumber();
        
 		parent::__construct($arguments);
	}

	protected function _buildResponse() {
		$this->_response = $this->_request->getWwsCustomerId();
	}

}
