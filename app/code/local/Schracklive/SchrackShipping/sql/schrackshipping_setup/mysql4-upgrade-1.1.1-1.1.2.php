<?php
$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

$installer->run("
ALTER TABLE `sales_flat_quote` ADD COLUMN `schrack_address_phone` varchar(40) NULL DEFAULT NULL AFTER `schrack_address_type_new`;
");

$installer->endSetup();