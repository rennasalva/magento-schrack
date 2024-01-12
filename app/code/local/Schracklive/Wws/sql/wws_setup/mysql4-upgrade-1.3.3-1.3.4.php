<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("
    UPDATE sales_flat_quote SET schrack_wws_order_number = '';
    ALTER TABLE sales_flat_quote ADD schrack_wws_order_number_created_at DATETIME DEFAULT NULL AFTER schrack_wws_order_number;
");

$installer->endSetup();
