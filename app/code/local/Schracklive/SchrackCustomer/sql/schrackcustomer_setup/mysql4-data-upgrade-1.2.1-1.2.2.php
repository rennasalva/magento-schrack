<?php

/*
 * All changes here are only because of the upgrade from Magento 1.4.0.1 to 1.5.1.0.
 * They reflect a data layout change in one of the Magento versions between.
 */

/* @var $installer Mage_Customer_Model_Entity_Setup */
$installer = $this;

$store = Mage::app()->getStore(Mage_Core_Model_App::ADMIN_STORE_ID);

/* @var $eavConfig Mage_Eav_Model_Config */
$eavConfig = Mage::getSingleton('eav/config');

$attributes = array(
	'schrack_wws_customer_id' => array(
		'is_user_defined' => 1,
		'is_visible' => 1,
		'sort_order' => 23,
		'adminhtml_only' => 1,
	),
	'schrack_wws_contact_number' => array(
		'is_user_defined' => 1,
		'is_visible' => 1,
		'sort_order' => 24,
		'adminhtml_only' => 1,
	),
	'schrack_main_contact' => array(
		'is_user_defined' => 1,
		'is_visible' => 1,
		'sort_order' => 25,
		'adminhtml_only' => 1,
	),
	'schrack_user_principal_name' => array(
		'is_user_defined' => 1,
		'is_visible' => 1,
		'sort_order' => 26,
		'adminhtml_only' => 1,
	),
	'schrack_wws_salesman_id' => array(
		'is_user_defined' => 1,
		'is_visible' => 1,
		'sort_order' => 27,
		'adminhtml_only' => 1,
	),
	'schrack_advisor_principal_name' => array(
		'is_user_defined' => 1,
		'is_visible' => 1,
		'sort_order' => 200,
		'adminhtml_only' => 1,
	),
	'schrack_advisors_principal_names' => array(
		'is_user_defined' => 1,
		'is_visible' => 1,
		'sort_order' => 210,
		'adminhtml_only' => 1,
	),
	'schrack_pickup' => array(
		'is_user_defined' => 1,
		'is_visible' => 1,
		'sort_order' => 220,
		'adminhtml_only' => 1,
	),
	'schrack_department' => array(
		'is_user_defined' => 1,
		'is_visible' => 1,
		'sort_order' => 230,
		'adminhtml_only' => 1,
	),
	'schrack_crm_role_id' => array(
		'is_user_defined' => 1,
		'is_visible' => 1,
		'sort_order' => 240,
		'adminhtml_only' => 1,
	),
	'schrack_address_id' => array(
		'is_user_defined' => 1,
		'is_visible' => 1,
		'sort_order' => 250,
		'adminhtml_only' => 1,
	),
	'schrack_interests' => array(
		'is_user_defined' => 1,
		'is_visible' => 1,
		'sort_order' => 260,
		'adminhtml_only' => 1,
	),
	'schrack_comments' => array(
		'is_user_defined' => 1,
		'is_visible' => 1,
		'sort_order' => 270,
		'adminhtml_only' => 1,
	),
	'schrack_emails' => array(
		'is_user_defined' => 1,
		'is_visible' => 1,
		'sort_order' => 280,
		'adminhtml_only' => 1,
	),
	'schrack_hideprices' => array(
		'is_user_defined' => 1,
		'is_visible' => 1,
		'sort_order' => 290,
		'adminhtml_only' => 1,
	),
	'schrack_noorderauthorization' => array(
		'is_user_defined' => 1,
		'is_visible' => 1,
		'sort_order' => 300,
		'adminhtml_only' => 1,
	),
	'schrack_newsletter' => array(
		'is_user_defined' => 1,
		'is_visible' => 1,
		'sort_order' => 310,
		'adminhtml_only' => 1,
	),
	'schrack_wws_branch_id' => array(
		'is_user_defined' => 1,
		'is_visible' => 1,
		'sort_order' => 400,
		'adminhtml_only' => 1,
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
	'schrack_wws_address_number' => array(
		'is_user_defined' => 1,
		'is_visible' => 1,
		'sort_order' => 200,
		'adminhtml_only' => 1,
	),
	'schrack_type' => array(
		'is_user_defined' => 1,
		'is_visible' => 1,
		'sort_order' => 400,
		'adminhtml_only' => 1,
	),
	'schrack_additional_phone' => array(
		'is_user_defined' => 1,
		'is_visible' => 1,
		'sort_order' => 400,
		'adminhtml_only' => 1,
	),
	'schrack_comments' => array(
		'is_user_defined' => 1,
		'is_visible' => 1,
		'sort_order' => 400,
		'adminhtml_only' => 1,
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
