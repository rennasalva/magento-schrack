<?php

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

// string statusglobal
// string statuslocal
// bool showinventory
// bool forsale

$entityTypeId     = $installer->getEntityTypeId('catalog_product');
$attributeSetId   = $installer->getAttributeSetId($entityTypeId,'Schrack');
$attributeGroupId = $installer->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);
$productTableName = $this->getTable('catalog_product_entity');

$installer->startSetup();

$installer->run("
    ALTER TABLE {$productTableName} ADD schrack_sts_is_download INT(1) DEFAULT NULL AFTER schrack_sts_managed_inventory;
    ALTER TABLE {$productTableName} ADD schrack_sts_green_stamp VARCHAR(32) DEFAULT NULL AFTER schrack_qty_per_packaging_unit;
");

$installer->addAttribute('catalog_product', 'schrack_sts_is_download', array(
    'type' => 'static',
    'label' => 'schrack_sts_is_download',
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

$installer->addAttribute('catalog_product', 'schrack_sts_green_stamp', array(
    'type' => 'static',
    'label' => 'schrack_sts_green_stamp',
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

$installer->addAttributeToGroup( $entityTypeId, $attributeSetId, $attributeGroupId, 'schrack_sts_is_download', 240 );
$installer->addAttributeToGroup( $entityTypeId, $attributeSetId, $attributeGroupId, 'schrack_sts_green_stamp', 241 );

$installer->endSetup();

