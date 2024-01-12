<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();
$installer->run("

ALTER TABLE `{$installer->getTable('sales_flat_quote')}` ADD `schrack_wws_order_number` varchar(9) DEFAULT '' NOT NULL;
ALTER TABLE `{$installer->getTable('sales_flat_quote')}` ADD `schrack_tax_total` decimal(12,4) DEFAULT '0' NOT NULL;

ALTER TABLE `{$installer->getTable('sales_flat_quote_item')}` ADD `schrack_row_total_excl_surcharge` decimal(12,4) DEFAULT '0' NOT NULL;
ALTER TABLE `{$installer->getTable('sales_flat_quote_item')}` ADD `schrack_surcharge` decimal(12,4) DEFAULT '0' NOT NULL;

ALTER TABLE `{$installer->getTable('sales_order')}` ADD `schrack_wws_order_number` varchar(9) DEFAULT '' NOT NULL;
ALTER TABLE `{$installer->getTable('sales_order')}` ADD `schrack_tax_total` decimal(12,4) DEFAULT '0' NOT NULL;

ALTER TABLE `{$installer->getTable('sales_flat_order_item')}` ADD `schrack_row_total_excl_surcharge` decimal(12,4) DEFAULT '0' NOT NULL;
ALTER TABLE `{$installer->getTable('sales_flat_order_item')}` ADD `schrack_surcharge` decimal(12,4) DEFAULT '0' NOT NULL;

");

$installer->endSetup();

/* make sure we have the same setup as all other order fields */
$installer->addAttribute('order', 'schrack_wws_order_number', array('type'=>'static','required'=>false,'label'=>'WWS Order Number'));
$installer->addAttribute('order', 'schrack_tax_total', array('type'=>'static','required'=>false,'label'=>'Tax'));
