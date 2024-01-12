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

$installer->removeAttribute('catalog_product', 'schrack_vpes');

$installer->addAttribute('catalog_product', 'schrack_vpes', array(
    'type' => 'text',
    'label' => 'schrack_vpes',
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

$installer->addAttributeToGroup( $entityTypeId, $attributeSetId, $attributeGroupId, 'schrack_vpes', 223 );

$installer->endSetup();

