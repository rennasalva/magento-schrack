<?php

$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup  */

// addAttribute(entityName|entityId, attributeCode, type=>varchar|int|decimal|text|datetime|static;input=text|select;label=<string>;sort_order=<int>;visible=<bool>;required=<bool>;source=<model>;backend=<model>;frontent=<model>)

// customer
$installer->addAttribute('customer', 'schrack_wws_customer_id', array('type'=>'varchar','required'=>false,'sort_order'=>2,'label'=>'WWS Customer Id'));
$installer->addAttribute('customer', 'schrack_wws_contact_number', array('type'=>'int','required'=>false,'sort_order'=>3,'label'=>'WWS Contact Number'));
$installer->addAttribute('customer', 'schrack_advisor_id', array('type'=>'int','required'=>false,'label'=>'Advisor'));
// employee
$installer->addAttribute('customer', 'schrack_user_principal_name', array('type'=>'varchar','required'=>false,'label'=>'Schrack Username (Login)'));
$installer->addAttribute('customer', 'schrack_wws_salesman_id', array('type'=>'int','required'=>false,'label'=>'Schrack Salesman Id'));
$installer->addAttribute('customer', 'schrack_wws_branch_id', array('type'=>'varchar','required'=>false,'label'=>'Schrack Branch Id'));

$installer->addAttribute('customer_address', 'schrack_mobile_phone', array('type'=>'varchar','required'=>false,'label'=>'Mobile Phone'));

// fields of addresses must not be required
$eavConfig = Mage::getSingleton('eav/config');
$addressEntityTypeId = $eavConfig->getEntityType('customer_address')->getEntityTypeId();
foreach(array('street','city','country_id','postcode','telephone') as $attributeCode) {
	$attribute  = $eavConfig->getAttribute($addressEntityTypeId, $attributeCode);
	$attribute->setIsRequired(FALSE);
	$attribute->save();
}

