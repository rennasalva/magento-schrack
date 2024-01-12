<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */

$eavConfig = Mage::getSingleton('eav/config');
$addressEntityTypeId = $eavConfig->getEntityType('order_address')->getEntityTypeId();
foreach(array('firstname') as $attributeCode) {
	$attribute  = $eavConfig->getAttribute($addressEntityTypeId, $attributeCode);
	$attribute->setIsRequired(FALSE);
	$attribute->save();
}

?>