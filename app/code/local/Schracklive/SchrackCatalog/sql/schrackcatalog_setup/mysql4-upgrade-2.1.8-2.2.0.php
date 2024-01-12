<?php

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$installer->startSetup();

$installer->run("
    CREATE TABLE schrack_catalog_importer_processed_message_log (
        origin_timestamp timestamp(3) NOT NULL,
        md5_sum varchar(64) NOT NULL,
        file_system_path varchar(255) NOT NULL,
        all_headers_json text,
        PRIMARY KEY (origin_timestamp, md5_sum)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$installer->endSetup();

