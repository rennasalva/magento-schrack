<?php

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$installer->startSetup();

$installer->run("
    ALTER TABLE customer_entity ADD schrack_s4s_id varchar(64)       DEFAULT NULL AFTER schrack_confirmed_dsgvo;
    ALTER TABLE customer_entity ADD schrack_s4s_nickname varchar(64) DEFAULT NULL AFTER schrack_s4s_id;
    ALTER TABLE customer_entity ADD schrack_s4s_school varchar(128)  DEFAULT NULL AFTER schrack_s4s_nickname;
");

$installer->addAttribute('customer', 'schrack_s4s_id', array(
    'type' => 'static',
    'label' => 'schrack_s4s_id',
    'input' => 'text',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'required' => false,
    'user_defined' => true,
    'default' => '',
    'searchable' => true,
    'filterable' => true,
    'comparable' => false,
    'visible_on_front' => false,
    'unique' => false,
    'is_configurable' => false
));
$installer->addAttribute('customer', 'schrack_s4s_nickname', array(
    'type' => 'static',
    'label' => 'schrack_s4s_nickname',
    'input' => 'text',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'required' => false,
    'user_defined' => true,
    'default' => '',
    'searchable' => true,
    'filterable' => true,
    'comparable' => false,
    'visible_on_front' => false,
    'unique' => false,
    'is_configurable' => false
));
$installer->addAttribute('customer', 'schrack_s4s_school', array(
    'type' => 'static',
    'label' => 'schrack_s4s_nickname',
    'input' => 'text',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'required' => false,
    'user_defined' => true,
    'default' => '',
    'searchable' => true,
    'filterable' => true,
    'comparable' => false,
    'visible_on_front' => false,
    'unique' => false,
    'is_configurable' => false
));

$installer->endSetup();
