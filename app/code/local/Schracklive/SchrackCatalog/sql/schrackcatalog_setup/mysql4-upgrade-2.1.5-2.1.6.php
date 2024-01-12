<?php

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$entityTypeId     = $installer->getEntityTypeId('catalog_product');
$productTableName = $this->getTable('catalog_product_entity');

$installer->startSetup();

$installer->run("
    ALTER TABLE {$productTableName} MODIFY schrack_main_category_id VARCHAR(255) DEFAULT NULL;

    CREATE TABLE schrack_category_reverse_group_id (
        reverse_group_id varchar(255) NOT NULL default '',
        category_entity_id int(10) unsigned NOT NULL DEFAULT '0',
        PRIMARY KEY (reverse_group_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$installer->endSetup();

