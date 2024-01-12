<?php

class Schracklive_MockCrm_Model_Connector extends Schracklive_Crm_Model_Connector
{

	public function getCustomerInfo($wwsCustomerId)
	{
		return array(
			'wws_branch_id' => '99',
			'prefix' => 'Firma',
			'name1' => 'Test 1',
			'name2' => 'Firmen- 2',
			'name3' => 'name 3',
			'street' => 'Fubarstr. 7',
			'postcode' => '1234',
			'city' => 'Entenhausen',
			'country_id' => '',
			'advisor_principal_name' => '',
			'advisors_principal_names' => '',
			'gtc_accepted' => true,
		);
	}

	public function putAccount(Schracklive_Account_Model_Account $account)
	{
		return true;
	}

	public function putCustomer(Mage_Customer_Model_Customer $customer)
	{
		return 666;
	}

	public function putAddress($address)
	{
		return 666;
	}

}
