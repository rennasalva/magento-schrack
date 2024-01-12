<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("        
    ALTER TABLE wws_ship_order_request ADD COLUMN payment_method tinyint(1) NULL AFTER flag_order;
    ALTER TABLE wws_ship_order_request ADD COLUMN ship_flag_true tinyint(1) NULL AFTER payment_method;
");

$installer->endSetup();