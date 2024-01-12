<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();
$installer->run("

ALTER TABLE `{$installer->getTable('sales_flat_quote_item')}` ADD `schrack_drum_number` varchar(255) NULL;

ALTER TABLE `{$installer->getTable('sales_flat_order_item')}` ADD `schrack_drum_number` varchar(255) NULL;

");

$installer->endSetup();
