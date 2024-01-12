<?php

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$entityTypeId     = $installer->getEntityTypeId('catalog_product');
$attributeSetId   = $installer->getAttributeSetId($entityTypeId,'Schrack');
$attributeGroupId = $installer->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);
$productTableName = $this->getTable('catalog_product_entity');

$installer->startSetup();

$installer->run("
    ALTER TABLE {$productTableName} ADD schrack_sts_printed_manufacturer_number VARCHAR(255) DEFAULT NULL AFTER schrack_sts_main_supplier;
");

$installer->addAttribute('catalog_product', 'schrack_sts_printed_manufacturer_number', array(
    'type' => 'static',
    'label' => 'schrack_sts_printed_manufacturer_number',
    'input' => 'text',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'required' => true,
    'user_defined' => true,
    'default' => '',
    'searchable' => true,
    'filterable' => true,
    'comparable' => false,
    'visible_on_front' => true,
    'unique' => false,
    'is_configurable' => false
));
$installer->addAttributeToGroup( $entityTypeId, $attributeSetId, $attributeGroupId, 'schrack_sts_printed_manufacturer_number', 246 );

$installer->endSetup();

