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
    ALTER TABLE {$productTableName} ADD schrack_sts_statusglobal  VARCHAR(32) NOT NULL DEFAULT 'std' AFTER schrack_wws_ranking;
    ALTER TABLE {$productTableName} ADD schrack_sts_statuslocal   VARCHAR(32) NOT NULL DEFAULT 'std' AFTER schrack_sts_statusglobal;
    ALTER TABLE {$productTableName} ADD schrack_sts_showinventory INT(1)      NOT NULL DEFAULT 1     AFTER schrack_sts_statuslocal;
    ALTER TABLE {$productTableName} ADD schrack_sts_forsale       INT(1)      NOT NULL DEFAULT 0     AFTER schrack_sts_showinventory;
    ALTER TABLE {$productTableName} ADD schrack_sts_valid_until   DATE                 DEFAULT NULL  AFTER schrack_sts_forsale;
    UPDATE {$productTableName} SET schrack_sts_valid_until = schrack_valid_until;
    UPDATE {$productTableName} SET schrack_sts_showinventory = IF(schrack_is_on_request = 1,0,1);
    ALTER TABLE {$productTableName} DROP schrack_valid_until;
    ALTER TABLE {$productTableName} DROP schrack_is_on_request;
");

$installer->removeAttribute('catalog_product','schrack_valid_until');
$installer->removeAttribute('catalog_product','schrack_is_on_request');

$installer->addAttribute('catalog_product', 'schrack_sts_statusglobal', array(
    'type' => 'static',
    'label' => 'schrack_sts_statusglobal',
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
$installer->addAttribute('catalog_product', 'schrack_sts_statuslocal', array(
    'type' => 'static',
    'label' => 'schrack_sts_statuslocal',
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
$installer->addAttribute('catalog_product', 'schrack_sts_showinventory', array(
    'type' => 'static',
    'label' => 'schrack_sts_showinventory',
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
$installer->addAttribute('catalog_product', 'schrack_sts_forsale', array(
    'type' => 'static',
    'label' => 'schrack_sts_forsale',
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
$installer->addAttribute('catalog_product', 'schrack_sts_valid_until', array(
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

$installer->addAttributeToGroup( $entityTypeId, $attributeSetId, $attributeGroupId, 'schrack_sts_statusglobal',  213 );
$installer->addAttributeToGroup( $entityTypeId, $attributeSetId, $attributeGroupId, 'schrack_sts_statuslocal',   214 );
$installer->addAttributeToGroup( $entityTypeId, $attributeSetId, $attributeGroupId, 'schrack_sts_showinventory', 215 );
$installer->addAttributeToGroup( $entityTypeId, $attributeSetId, $attributeGroupId, 'schrack_sts_forsale',       216 );
$installer->addAttributeToGroup( $entityTypeId, $attributeSetId, $attributeGroupId, 'schrack_sts_valid_until',   217 );

$installer->endSetup();

