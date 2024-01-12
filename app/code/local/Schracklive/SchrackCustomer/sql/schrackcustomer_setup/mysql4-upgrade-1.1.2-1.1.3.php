<?php

$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup  */

$eavConfig = Mage::getSingleton('eav/config');
$customerEntityTypeId = $eavConfig->getEntityType('customer')->getEntityTypeId();
foreach(array('firstname') as $attributeCode) {
	$attribute  = $eavConfig->getAttribute($customerEntityTypeId, $attributeCode);
	$attribute->setIsRequired(FALSE);
	$attribute->save();
}