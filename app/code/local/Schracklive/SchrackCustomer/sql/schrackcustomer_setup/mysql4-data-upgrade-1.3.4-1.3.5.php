<?php

/*
 * All changes here are only because of the upgrade from Magento 1.4.0.1 to 1.5.1.0.
 * They reflect a data layout change in one of the Magento versions between.
 * FYI: should have been included in mysql4-data-upgrade-1.2.1-1.2.2.php and mysql4-upgrade-1.3.1-1.3.2.php
 */

/* @var $installer Mage_Customer_Model_Entity_Setup */
$installer = $this;

$store = Mage::app()->getStore(Mage_Core_Model_App::ADMIN_STORE_ID);

/* @var $eavConfig Mage_Eav_Model_Config */
$eavConfig = Mage::getSingleton('eav/config');

$attributes = array(
	'schrack_telephone' => array(
		'is_user_defined' => 1,
		'is_visible' => 1,
		'sort_order' => 100,
		'adminhtml_only' => 0,
	),
	'schrack_fax' => array(
		'is_user_defined' => 1,
		'is_visible' => 1,
		'sort_order' => 101,
		'adminhtml_only' => 0,
	),
	'schrack_mobile_phone' => array(
		'is_user_defined' => 1,
		'is_visible' => 1,
		'sort_order' => 102,
		'adminhtml_only' => 0,
	),
	'schrack_salutatory' => array(
		'is_user_defined' => 1,
		'is_visible' => 1,
		'sort_order' => 41,
		'adminhtml_only' => 0,
	),
	'schrack_acl_role_id' => array(
		'is_user_defined' => 1,
		'is_visible' => 1,
		'sort_order' => 95,
		'adminhtml_only' => 0,
	),
);

foreach ($attributes as $attributeCode => $data) {
	$attribute = $eavConfig->getAttribute('customer', $attributeCode);
	$attribute->setWebsite($store->getWebsite());
	$attribute->addData($data);

	$usedInForms = array(
		'customer_account_create',
		'customer_account_edit',
		'checkout_register',
	);
	if (!empty($data['adminhtml_only'])) {
		$usedInForms = array('adminhtml_customer');
	} else {
		$usedInForms[] = 'adminhtml_customer';
	}
	if (!empty($data['admin_checkout'])) {
		$usedInForms[] = 'adminhtml_checkout';
	}
	$attribute->setData('used_in_forms', $usedInForms);

	$attribute->save();
}

$attributes = array(
	'schrack_type' => array(
		'is_user_defined' => 1,
		'is_visible' => 1,
		'sort_order' => 400,
		'adminhtml_only' => 0,
	),
	'schrack_additional_phone' => array(
		'is_user_defined' => 1,
		'is_visible' => 1,
		'sort_order' => 400,
		'adminhtml_only' => 0,
	),
);


foreach ($attributes as $attributeCode => $data) {
	$attribute = $eavConfig->getAttribute('customer_address', $attributeCode);
	$attribute->setWebsite($store->getWebsite());
	$attribute->addData($data);

	$usedInForms = array(
		'adminhtml_customer_address',
		'customer_address_edit',
		'customer_register_address'
	);
	$attribute->setData('used_in_forms', $usedInForms);

	$attribute->save();
}
