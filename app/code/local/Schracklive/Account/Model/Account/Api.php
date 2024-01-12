<?php

class Schracklive_Account_Model_Account_Api extends Mage_Api_Model_Resource_Abstract {

	protected $_attributes = array(
		'wws_customer_id',
		'wws_branch_id',
		'prefix',
		'name1',
		'name2',
		'name3',
		'email',
		'homepage',
		'advisor_principal_name',
		'advisors_principal_names',
		'match_code',
		'description',
		'information',
		'currency_code',
		'vat_identification_number',
		'company_registration_number',
		'gtc_accepted',
		'delivery_block',
		'sales_area',
		'rating',
		'enterprise_size',
		'account_type',
	);
	protected $_addressAttributes = array(
		'street',
		'postcode',
		'city',
		'country_id',
		'telephone',
		'fax',
	);
	protected $_mapAttributes = array(
		'account_id' => 'entity_id'
	);

	public function replaceCustomer($wwsCustomerId, $data) {
		$accountHelper = Mage::helper('account');
		try {
			$account = $accountHelper->updateOrCreateAccount($wwsCustomerId, $data);
			$systemContact = $accountHelper->updateOrCreateSystemContact($account, $data);
		} catch (Exception $e) {
			$this->_fault('data_invalid', $e->getMessage());
		}
		return $account->getId();
	}

	public function deleteCustomer($wwsCustomerId) {
		$account = Mage::getModel('account/account')->loadByWwsCustomerId($wwsCustomerId);

		if (!$account->getId()) {
			$this->_fault('not_exists');
		}

		try {
			$account->delete();
		} catch (Exception $e) {
			$this->_fault('not_deleted', $e->getMessage());
		}

		return true;
	}

	public function getCustomer($wwsCustomerId) {
		$account = Mage::getModel('account/account')->loadByWwsCustomerId($wwsCustomerId);

		if (!$account->getId()) {
			$this->_fault('not_exists');
		}

		$result = array();

		foreach ($this->_mapAttributes as $attributeAlias => $attributeCode) {
			$result[$attributeAlias] = $account->getData($attributeCode);
		}

		foreach ($this->_attributes as $attributeCode) {
			$result[$attributeCode] = $account->getData($attributeCode);
		}

		return $result;
	}

}
