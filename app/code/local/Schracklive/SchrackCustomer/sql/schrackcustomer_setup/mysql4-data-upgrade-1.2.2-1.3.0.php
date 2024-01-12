<?php

$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup  */

// update new static fields
$collection = Mage::getModel('customer/customer')->getCollection();
$collection
		->addAttributeToSelect('schrack_wws_customer_id')
		->addAttributeToSelect('schrack_wws_contact_number')
		->addAttributeToSelect('schrack_user_principal_name');

foreach ($collection as $customer) {
	if ($customer->getSchrackWwsCustomerId()) {
		$account = Mage::getModel('account/account')->loadByWwsCustomerId($customer->getSchrackWwsCustomerId());
		if ($account->getId()) {
			$customer->setSchrackAccountId($account->getId());
		}
	}
	$installer->updateTableRow(
			'customer_entity', 'entity_id', $customer->getId(), array(
		'schrack_wws_customer_id' => $customer->getSchrackWwsCustomerId(),
		'schrack_wws_contact_number' => $customer->getSchrackWwsContactNumber(),
		'schrack_user_principal_name' => $customer->getSchrackUserPrincipalName(),
		'schrack_account_id' => $customer->getSchrackAccountId(),
			)
	);
}

// delete old eav data
$customerEntityId = $installer->getEntityType('customer', 'entity_type_id');
foreach (array('schrack_wws_customer_id', 'schrack_wws_contact_number', 'schrack_user_principal_name') as $attributeCode) {
	$attribute = Mage::getModel('eav/entity_attribute')->loadByCode($customerEntityId, $attributeCode);
	$condition = $installer->getConnection()->quoteInto('attribute_id=?', $attribute['attribute_id']);
	$table = $attribute->getBackendTable();

	$installer->getConnection()->delete($table, $condition);
}

// update attributes
$installer->updateAttribute('customer', 'schrack_wws_customer_id', array('backend_type' => 'static'));
$installer->updateAttribute('customer', 'schrack_wws_contact_number', array('backend_type' => 'static'));
$installer->updateAttribute('customer', 'schrack_user_principal_name', array('backend_type' => 'static'));
