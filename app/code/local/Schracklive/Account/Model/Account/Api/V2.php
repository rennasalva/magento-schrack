<?php

class Schracklive_Account_Model_Account_Api_V2 extends Schracklive_Account_Model_Account_Api {

	public function replaceCustomer($wwsCustomerId, $accountData) {
		return parent::replaceCustomer($wwsCustomerId, get_object_vars($accountData));
	}

}
