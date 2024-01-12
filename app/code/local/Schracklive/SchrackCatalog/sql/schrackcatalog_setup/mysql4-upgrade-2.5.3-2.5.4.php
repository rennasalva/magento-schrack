<?php

/* @var Mage_Catalog_Model_Resource_Eav_Mysql4_Setup $installer */
$installer = $this;


$installer->startSetup();

$installer->run("
    ALTER TABLE catalog_product_entity 
        ADD COLUMN schrack_sts_special_transport int(1) AFTER schrack_main_producer, 
        ADD COLUMN schrack_sts_transport_rate_pv int(1) AFTER schrack_sts_special_transport,
        ADD COLUMN schrack_sts_trans_rate_bat int(1) AFTER schrack_sts_transport_rate_pv, 
        ADD COLUMN schrack_sts_plus_deli_time int AFTER schrack_sts_trans_rate_bat;
");

$installer->addAttribute('catalog_product', 'schrack_sts_special_transport', array(
    'type' => 'static',
    'label' => 'schrack_sts_special_transport',
    'input' => 'int',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'required' => false,
    'user_defined' => true,
    'default' => '0',
    'searchable' => true,
    'filterable' => true,
    'comparable' => false,
    'visible_on_front' => false,
    'unique' => false,
    'is_configurable' => false
));

$installer->addAttribute('catalog_product', 'schrack_sts_transport_rate_pv', array(
    'type' => 'static',
    'label' => 'schrack_sts_transport_rate_pv',
    'input' => 'int',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'required' => false,
    'user_defined' => true,
    'default' => '0',
    'searchable' => true,
    'filterable' => true,
    'comparable' => false,
    'visible_on_front' => false,
    'unique' => false,
    'is_configurable' => false
));

$installer->addAttribute('catalog_product', 'schrack_sts_trans_rate_bat', array(
    'type' => 'static',
    'label' => 'schrack_sts_trans_rate_bat',
    'input' => 'int',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'required' => false,
    'user_defined' => true,
    'default' => '0',
    'searchable' => true,
    'filterable' => true,
    'comparable' => false,
    'visible_on_front' => false,
    'unique' => false,
    'is_configurable' => false
));

$installer->addAttribute('catalog_product', 'schrack_sts_plus_deli_time', array(
    'type' => 'static',
    'label' => 'schrack_sts_plus_deli_time',
    'input' => 'int',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'required' => false,
    'user_defined' => true,
    'default' => '0',
    'searchable' => true,
    'filterable' => true,
    'comparable' => false,
    'visible_on_front' => false,
    'unique' => false,
    'is_configurable' => false
));

$installer->endSetup();
