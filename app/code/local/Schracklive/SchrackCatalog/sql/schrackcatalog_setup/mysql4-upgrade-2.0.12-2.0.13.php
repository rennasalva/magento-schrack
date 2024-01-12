<?php

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$installer->startSetup();

$installer->run("ALTER TABLE catalog_attachment ADD INDEX entity_type_entity ( entity_type_id, entity_id );");

$installer->endSetup();

