<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

$installer->run("
ALTER TABLE `sales_flat_quote_item` ADD COLUMN `schrack_offer_unit` int(10) unsigned NOT NULL DEFAULT '1' AFTER `schrack_item_description`;
ALTER TABLE `sales_flat_quote_item` ADD COLUMN `schrack_offer_price_per_unit` decimal(12,4) NULL DEFAULT NULL AFTER `schrack_offer_unit`;
ALTER TABLE `sales_flat_quote_item` ADD COLUMN `schrack_offer_tax` decimal(12,4) NULL DEFAULT NULL AFTER `schrack_offer_price_per_unit`;
ALTER TABLE `sales_flat_quote_item` ADD COLUMN `schrack_offer_surcharge` decimal(12,4) NULL DEFAULT NULL AFTER `schrack_offer_tax`;
ALTER TABLE `sales_flat_quote_item` ADD COLUMN `schrack_offer_reference` varchar(10) NULL DEFAULT NULL AFTER `schrack_offer_surcharge`;
");

$installer->endSetup();