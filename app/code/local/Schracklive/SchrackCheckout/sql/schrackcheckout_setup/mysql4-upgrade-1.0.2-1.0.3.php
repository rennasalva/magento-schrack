<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

$installer->run("
ALTER TABLE `sales_flat_quote_item` ADD COLUMN `schrack_offer_number` varchar(10) NULL DEFAULT NULL AFTER `schrack_offer_reference`;
");

$installer->endSetup();