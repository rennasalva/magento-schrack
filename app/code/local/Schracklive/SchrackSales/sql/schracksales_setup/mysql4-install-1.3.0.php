<?php

// requires sales 1.4.0 or above

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();
$installer->run("

ALTER TABLE `{$installer->getTable('sales_flat_quote')}` ADD `schrack_wws_order_number` varchar(9) DEFAULT '' NOT NULL;
ALTER TABLE `{$installer->getTable('sales_flat_quote')}` ADD `schrack_tax_total` decimal(12,4) DEFAULT '0' NOT NULL;
ALTER TABLE `{$installer->getTable('sales_flat_quote')}` ADD `schrack_wws_customer_id` varchar(6);
ALTER TABLE `{$installer->getTable('sales_flat_quote')}` ADD `schrack_payment_terms` varchar(255);
ALTER TABLE `{$installer->getTable('sales_flat_quote')}` ADD `schrack_shipment_mode` varchar(255);
ALTER TABLE `{$installer->getTable('sales_flat_quote')}` ADD `schrack_wws_place_memo` mediumtext;
ALTER TABLE `{$installer->getTable('sales_flat_quote')}` ADD `schrack_wws_ship_memo` mediumtext;

ALTER TABLE `{$installer->getTable('sales_flat_quote_item')}` ADD `schrack_row_total_excl_surcharge` decimal(12,4) DEFAULT '0' NOT NULL;
ALTER TABLE `{$installer->getTable('sales_flat_quote_item')}` ADD `schrack_surcharge` decimal(12,4) DEFAULT '0' NOT NULL;
ALTER TABLE `{$installer->getTable('sales_flat_quote_item')}` ADD `schrack_backorder_qty` decimal(12,4);
ALTER TABLE `{$installer->getTable('sales_flat_quote_item')}` ADD `schrack_wws_place_memo` mediumtext;
ALTER TABLE `{$installer->getTable('sales_flat_quote_item')}` ADD `schrack_wws_ship_memo` mediumtext;
ALTER TABLE `{$installer->getTable('sales_flat_quote_item')}` ADD `schrack_row_total_surcharge` decimal(12,4) DEFAULT '0' NOT NULL;
ALTER TABLE `{$installer->getTable('sales_flat_quote_item')}` ADD `schrack_basic_price` decimal(12,4) DEFAULT '0' NOT NULL;

ALTER TABLE `{$installer->getTable('sales_flat_order')}` ADD `schrack_wws_order_number` varchar(9) DEFAULT '' NOT NULL;
ALTER TABLE `{$installer->getTable('sales_flat_order')}` ADD `schrack_tax_total` decimal(12,4) DEFAULT '0' NOT NULL;
ALTER TABLE `{$installer->getTable('sales_flat_order')}` ADD `schrack_wws_customer_id` varchar(6);
ALTER TABLE `{$installer->getTable('sales_flat_order')}` ADD `schrack_payment_terms` varchar(255);
ALTER TABLE `{$installer->getTable('sales_flat_order')}` ADD `schrack_shipment_mode` varchar(255);
ALTER TABLE `{$installer->getTable('sales_flat_order')}` ADD `schrack_wws_place_memo` mediumtext;
ALTER TABLE `{$installer->getTable('sales_flat_order')}` ADD `schrack_wws_ship_memo` mediumtext;
ALTER TABLE `{$installer->getTable('sales_flat_order')}` ADD `schrack_custom_order_number` varchar(20);

ALTER TABLE `{$installer->getTable('sales_flat_order_item')}` ADD `schrack_row_total_excl_surcharge` decimal(12,4) DEFAULT '0' NOT NULL;
ALTER TABLE `{$installer->getTable('sales_flat_order_item')}` ADD `schrack_surcharge` decimal(12,4) DEFAULT '0' NOT NULL;
ALTER TABLE `{$installer->getTable('sales_flat_order_item')}` ADD `schrack_backorder_qty` decimal(12,4);
ALTER TABLE `{$installer->getTable('sales_flat_order_item')}` ADD `schrack_wws_place_memo` mediumtext;
ALTER TABLE `{$installer->getTable('sales_flat_order_item')}` ADD `schrack_wws_ship_memo` mediumtext;
ALTER TABLE `{$installer->getTable('sales_flat_order_item')}` ADD `schrack_row_total_surcharge` decimal(12,4) DEFAULT '0' NOT NULL;
ALTER TABLE `{$installer->getTable('sales_flat_order_item')}` ADD `schrack_basic_price` decimal(12,4) DEFAULT '0' NOT NULL;

");

$installer->endSetup();
