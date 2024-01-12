<?php
$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

$installer->run("
ALTER TABLE `sales_flat_quote` ADD COLUMN `schrack_address_type` tinyint(1) NULL DEFAULT NULL AFTER `schrack_customertype`;
ALTER TABLE `sales_flat_quote` ADD COLUMN `schrack_address_type_new` tinyint(1) NULL DEFAULT NULL AFTER `schrack_address_type`;
");

$installer->endSetup();