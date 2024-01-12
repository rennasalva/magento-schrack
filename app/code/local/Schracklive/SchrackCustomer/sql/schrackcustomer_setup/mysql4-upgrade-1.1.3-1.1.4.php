<?php

$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup  */

$eavConfig = Mage::getSingleton('eav/config');
$addressEntityTypeId = $eavConfig->getEntityType('customer_address')->getEntityTypeId();
foreach(array('firstname') as $attributeCode) {
	$attribute  = $eavConfig->getAttribute($addressEntityTypeId, $attributeCode);
	$attribute->setIsRequired(FALSE);
	$attribute->save();
}