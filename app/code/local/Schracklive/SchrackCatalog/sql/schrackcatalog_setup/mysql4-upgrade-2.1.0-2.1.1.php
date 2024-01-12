<?php

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$entityTypeId     = $installer->getEntityTypeId('catalog_product');
$attributeSetId   = $installer->getAttributeSetId($entityTypeId,'Schrack');
$attributeGroupId = $installer->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);
$productTableName = $this->getTable('catalog_product_entity');

$installer->startSetup();

$installer->run("
    ALTER TABLE {$productTableName} ADD schrack_sts_main_vpe_size int(8) DEFAULT NULL AFTER schrack_sts_main_vpe_type;
");

$installer->addAttribute('catalog_product', 'schrack_sts_main_vpe_size', array(
    'type' => 'static',
    'label' => 'schrack_sts_main_vpe_size',
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
$installer->addAttributeToGroup( $entityTypeId, $attributeSetId, $attributeGroupId, 'schrack_sts_main_vpe_size', 251 );
$installer->endSetup();

