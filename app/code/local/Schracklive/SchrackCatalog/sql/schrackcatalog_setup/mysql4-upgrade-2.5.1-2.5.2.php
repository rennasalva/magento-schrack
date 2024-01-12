<?php

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$installer->startSetup();
$productTableName = $this->getTable('catalog_product_entity');

$installer->run("
    ALTER TABLE {$productTableName} ADD schrack_sts_unmanaged_stocks varchar(255) NULL DEFAULT NULL AFTER schrack_sts_not_available;
");

$installer->addAttribute('catalog_product', 'schrack_sts_unmanaged_stocks', array(
    'type'             => 'static',
    'label'            => 'schrack_sts_unmanaged_stocks',
    'input'            => 'text',
    'global'           => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'required'         => false,
    'user_defined'     => true,
    'default'          => '',
    'searchable'       => true,
    'filterable'       => true,
    'comparable'       => false,
    'visible_on_front' => true,
    'unique'           => false,
    'is_configurable'  => false
));


$installer->endSetup();
