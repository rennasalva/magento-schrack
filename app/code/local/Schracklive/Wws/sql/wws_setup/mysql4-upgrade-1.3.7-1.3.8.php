<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("        
    ALTER TABLE wws_insert_update_order_request ADD COLUMN customerdata varchar(2024) NULL AFTER request_datetime;    
");

$installer->endSetup();