<?php

/* @var Mage_Catalog_Model_Resource_Eav_Mysql4_Setup $installer */
$installer = $this;

$installer->startSetup();

$installer->run("

CREATE TABLE schrack_file_size (
    `path` 				varchar(256) 		NOT NULL,
    `updated_at` 		datetime   		    NOT NULL,
    `size` 			    int(10) unsigned    NOT NULL,
    PRIMARY KEY (`path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$installer->endSetup();
