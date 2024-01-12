<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();
$installer->run("

ALTER TABLE `{$installer->getTable('sales_flat_quote')}` ADD `schrack_wws_customer_id` varchar(6);
ALTER TABLE `{$installer->getTable('sales_flat_quote')}` ADD `schrack_payment_terms` varchar(255);
ALTER TABLE `{$installer->getTable('sales_flat_quote')}` ADD `schrack_shipment_mode` varchar(255);
ALTER TABLE `{$installer->getTable('sales_flat_quote')}` ADD `schrack_wws_place_memo` mediumtext;
ALTER TABLE `{$installer->getTable('sales_flat_quote')}` ADD `schrack_wws_ship_memo` mediumtext;

ALTER TABLE `{$installer->getTable('sales_flat_quote_item')}` ADD `schrack_backorder_qty` decimal(12,4);
ALTER TABLE `{$installer->getTable('sales_flat_quote_item')}` ADD `schrack_wws_place_memo` mediumtext;
ALTER TABLE `{$installer->getTable('sales_flat_quote_item')}` ADD `schrack_wws_ship_memo` mediumtext;

ALTER TABLE `{$installer->getTable('sales_order')}` ADD `schrack_wws_customer_id` varchar(6);
ALTER TABLE `{$installer->getTable('sales_order')}` ADD `schrack_payment_terms` varchar(255);
ALTER TABLE `{$installer->getTable('sales_order')}` ADD `schrack_shipment_mode` varchar(255);
ALTER TABLE `{$installer->getTable('sales_order')}` ADD `schrack_wws_place_memo` mediumtext;
ALTER TABLE `{$installer->getTable('sales_order')}` ADD `schrack_wws_ship_memo` mediumtext;

ALTER TABLE `{$installer->getTable('sales_flat_order_item')}` ADD `schrack_backorder_qty` decimal(12,4);
ALTER TABLE `{$installer->getTable('sales_flat_order_item')}` ADD `schrack_wws_place_memo` mediumtext;
ALTER TABLE `{$installer->getTable('sales_flat_order_item')}` ADD `schrack_wws_ship_memo` mediumtext;

");

$installer->endSetup();

/* make sure we have the same setup as all other order fields */
$installer->addAttribute('order', 'schrack_wws_customer_id', array('type'=>'static','required'=>false,'label'=>'WWS Customer Id'));
$installer->addAttribute('order', 'schrack_payment_terms', array('type'=>'static','required'=>false,'label'=>'WWS Customer Id'));
$installer->addAttribute('order', 'schrack_shipment_mode', array('type'=>'static','required'=>false,'label'=>'WWS Customer Id'));
$installer->addAttribute('order', 'schrack_wws_place_memo', array('type'=>'static','required'=>false,'label'=>'WWS Memo'));
$installer->addAttribute('order', 'schrack_wws_ship_memo', array('type'=>'static','required'=>false,'label'=>'WWS Memo'));
