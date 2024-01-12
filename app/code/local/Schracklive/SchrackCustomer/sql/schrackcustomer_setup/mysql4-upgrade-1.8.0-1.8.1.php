<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

$installer->run("
ALTER TABLE `customer_entity` ADD COLUMN `schrack_default_payment_shipping` varchar(50) NULL AFTER `disable_auto_group_change`;
ALTER TABLE `customer_entity` ADD COLUMN `schrack_default_payment_pickup` varchar(50) NULL AFTER `schrack_default_payment_shipping`;
ALTER TABLE `customer_entity` ADD COLUMN `schrack_customer_type` varchar(100) NULL DEFAULT NULL AFTER `schrack_user_principal_name`;
ALTER TABLE `customer_entity` ADD COLUMN `schrack_newsletter` tinyint(1) NULL DEFAULT NULL AFTER `schrack_customer_type`;
ALTER TABLE `account` ADD COLUMN `vat_local_number` varchar(100) NULL DEFAULT NULL AFTER `account_type`;
ALTER TABLE `sales_flat_quote` ADD COLUMN `schrack_customertype` varchar(100) NULL DEFAULT NULL AFTER `is_pickup`;
ALTER TABLE `sales_flat_quote` ADD COLUMN `schrack_wws_order_memo` mediumtext NULL DEFAULT NULL AFTER `schrack_wws_ship_memo`;
");

$installer->endSetup();