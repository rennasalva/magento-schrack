<?php

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$entityTypeId     = $installer->getEntityTypeId('catalog_product');
$attributeSetId   = $installer->getAttributeSetId($entityTypeId,'Schrack');
$attributeGroupId = $installer->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);
$productTableName = $this->getTable('catalog_product_entity');

$installer->startSetup();


$installer->run("ALTER TABLE {$productTableName} ADD schrack_is_on_request INT(1) NOT NULL DEFAULT 0 AFTER schrack_packingunit;");
$installer->addAttribute('catalog_product', 'schrack_is_on_request', array(
    'type' => 'static',
    'label' => 'schrack_is_on_request',
    'input' => 'text',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'required' => true,
    'user_defined' => true,
    'default' => '0',
    'searchable' => true,
    'filterable' => true,
    'comparable' => false,
    'visible_on_front' => false,
    'unique' => false,
    'is_configurable' => false
));
$installer->addAttributeToGroup(
	$entityTypeId,
	$attributeSetId,
	$attributeGroupId,
	'schrack_is_on_request',
    155
);

$installer->run("ALTER TABLE {$productTableName} ADD schrack_valid_until DATE DEFAULT NULL AFTER schrack_is_on_request;");
$installer->addAttribute('catalog_product', 'schrack_valid_until', array(
    'type' => 'static',
    'label' => 'schrack_valid_until',
    'input' => 'date',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'required' => true,
    'user_defined' => true,
    'default' => '0',
    'searchable' => true,
    'filterable' => true,
    'comparable' => false,
    'visible_on_front' => false,
    'unique' => false,
    'is_configurable' => false
));
$installer->addAttributeToGroup(
	$entityTypeId,
	$attributeSetId,
	$attributeGroupId,
	'schrack_valid_until',
    156
);

$installer->run("UPDATE eav_attribute SET frontend_input = 'text' WHERE attribute_code = 'schrack_is_cable';");


$installer->endSetup();

