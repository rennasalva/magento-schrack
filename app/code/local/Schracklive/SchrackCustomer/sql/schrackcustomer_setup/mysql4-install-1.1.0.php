<?php

$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup  */

$installer->startSetup();

$installer->addAttribute('customer', 'schrack_telephone', array('type'=>'varchar','required'=>false,'label'=>'Telephone'));
$installer->addAttribute('customer', 'schrack_fax', array('type'=>'varchar','required'=>false,'label'=>'Fax'));
$installer->addAttribute('customer', 'schrack_mobile_phone', array('type'=>'varchar','required'=>false,'label'=>'Mobile Phone'));
// customer
$installer->addAttribute('customer', 'schrack_wws_customer_id', array('type'=>'varchar','required'=>false,'sort_order'=>2,'label'=>'WWS Customer Id'));
$installer->addAttribute('customer', 'schrack_wws_contact_number', array('type'=>'int','required'=>false,'sort_order'=>3,'label'=>'WWS Contact Number'));
$installer->addAttribute('customer', 'schrack_advisor_principal_name', array('type'=>'varchar','required'=>false,'label'=>'Advisor'));
$installer->addAttribute('customer', 'schrack_advisors_principal_names', array('type'=>'text','required'=>false,'label'=>'Additional Advisors'));
// employee
$installer->addAttribute('customer', 'schrack_user_principal_name', array('type'=>'varchar','required'=>false,'label'=>'Schrack Username (Login)'));
$installer->addAttribute('customer', 'schrack_wws_salesman_id', array('type'=>'int','required'=>false,'label'=>'Schrack Salesman Id'));
$installer->addAttribute('customer', 'schrack_wws_branch_id', array('type'=>'varchar','required'=>false,'label'=>'Schrack Branch Id'));

$installer->addAttribute('customer_address', 'schrack_type', array('type'=>'static','required'=>false,'label'=>'Address Type','source'=>'customer/address_attribute_source_type'));
$installer->addAttribute('customer_address', 'schrack_wws_address_number', array('type'=>'static','required'=>false,'label'=>'Address Number'));
$installer->addAttribute('customer_address', 'schrack_additional_phone', array('type'=>'varchar','required'=>false,'label'=>'Telephone 2'));

// fields of addresses must not be required
$eavConfig = Mage::getSingleton('eav/config');
$addressEntityTypeId = $eavConfig->getEntityType('customer_address')->getEntityTypeId();
foreach(array('street','city','country_id','postcode','telephone') as $attributeCode) {
	$attribute  = $eavConfig->getAttribute($addressEntityTypeId, $attributeCode);
	$attribute->setIsRequired(FALSE);
	$attribute->save();
}

$installer->run("

ALTER TABLE {$this->getTable('customer_entity')} ADD `created_by` varchar(255) NOT NULL default '';
ALTER TABLE {$this->getTable('customer_entity')} ADD `updated_by` varchar(255) NOT NULL default '';

ALTER TABLE {$this->getTable('customer_address_entity')} ADD `created_by` varchar(255) NOT NULL default '';
ALTER TABLE {$this->getTable('customer_address_entity')} ADD `updated_by` varchar(255) NOT NULL default '';
ALTER TABLE {$this->getTable('customer_address_entity')} ADD `schrack_type` tinyint(4);
ALTER TABLE {$this->getTable('customer_address_entity')} ADD `schrack_wws_address_number` int(11);

    ");

$installer->endSetup();
