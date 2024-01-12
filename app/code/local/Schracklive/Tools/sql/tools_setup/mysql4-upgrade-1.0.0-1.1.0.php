<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();
$installer->run("

    DROP TABLE IF EXISTS schrack_easylan_session;
    
    CREATE TABLE schrack_easylan_session (
      `customer_id` int(10) unsigned NOT NULL,
      `session_id` varchar(255) NOT NULL,
      `last_started_at` timestamp NOT NULL,
      PRIMARY KEY  (`customer_id`),
      KEY `IDX_SCHRACK_EASYLAN_SESSION_ID` (`session_id`),
      KEY `IDX_LAST_STARTED_AT` (`last_started_at`)
    ) ENGINE=INNODB DEFAULT CHARSET=UTF8;

");

$installer->endSetup();