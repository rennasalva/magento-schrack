<?php

$installer = $this;
$installer->startSetup();

$installer->run("
    CREATE TABLE schrack_custom_sku (
       `entity_id` 		 int(10) unsigned	NOT NULL AUTO_INCREMENT,
       `wws_customer_id` varchar(6)  NOT NULL,
       `custom_sku`      varchar(64) NOT NULL,
       `sku`             varchar(64) NOT NULL,
        PRIMARY KEY (`entity_id`),
        KEY `IDX_SCHRACK_CUSTOM_SKU_CUSTOMER` (`wws_customer_id`),
        KEY `IDX_SCHRACK_CUSTOM_SKU_CUSTOM_SKU` (`custom_sku`),
        KEY `IDX_SCHRACK_CUSTOM_SKU_SKU` (`sku`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();