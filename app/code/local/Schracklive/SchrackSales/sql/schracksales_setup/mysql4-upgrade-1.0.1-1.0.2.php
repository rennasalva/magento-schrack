<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();
$installer->run("

ALTER TABLE `{$installer->getTable('sales_flat_quote_item')}` ADD `schrack_row_total_surcharge` decimal(12,4) DEFAULT '0' NOT NULL;

ALTER TABLE `{$installer->getTable('sales_flat_order_item')}` ADD `schrack_row_total_surcharge` decimal(12,4) DEFAULT '0' NOT NULL;

");

$installer->endSetup();
