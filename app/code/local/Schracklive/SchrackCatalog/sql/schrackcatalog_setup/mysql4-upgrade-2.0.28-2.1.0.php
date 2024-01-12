<?php

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$entityTypeId     = $installer->getEntityTypeId('catalog_product');
$attributeSetId   = $installer->getAttributeSetId($entityTypeId,'Schrack');
$attributeGroupId = $installer->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);
$productTableName = $this->getTable('catalog_product_entity');

$installer->startSetup();

$installer->run("
    ALTER TABLE {$productTableName} ADD schrack_sts_main_article_sku varchar(64) DEFAULT NULL AFTER schrack_is_cable;
    ALTER TABLE {$productTableName} ADD schrack_sts_sub_article_skus varchar(255) DEFAULT NULL AFTER schrack_sts_main_article_sku;
    ALTER TABLE {$productTableName} ADD schrack_sts_main_vpe_type varchar(16) DEFAULT NULL AFTER schrack_sts_sub_article_skus;
");

$installer->addAttribute('catalog_product', 'schrack_sts_main_article_sku', array(
    'type' => 'static',
    'label' => 'schrack_sts_main_article_sku',
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
$installer->addAttributeToGroup( $entityTypeId, $attributeSetId, $attributeGroupId, 'schrack_sts_main_article_sku', 248 );

$installer->addAttribute('catalog_product', 'schrack_sts_sub_article_skus', array(
    'type' => 'static',
    'label' => 'schrack_sts_sub_article_skus',
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
$installer->addAttributeToGroup( $entityTypeId, $attributeSetId, $attributeGroupId, 'schrack_sts_sub_article_skus', 249 );

$installer->addAttribute('catalog_product', 'schrack_sts_main_vpe_type', array(
    'type' => 'static',
    'label' => 'schrack_sts_main_vpe_type',
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
$installer->addAttributeToGroup( $entityTypeId, $attributeSetId, $attributeGroupId, 'schrack_sts_main_vpe_type', 250 );

$installer->endSetup();

