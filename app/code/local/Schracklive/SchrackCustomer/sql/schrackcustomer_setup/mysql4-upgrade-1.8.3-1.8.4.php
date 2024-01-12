<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

$installer->run("
ALTER TABLE `account` ADD COLUMN `schrack_s4y_id` varchar(50) NULL DEFAULT NULL AFTER `wws_customer_id`;
");

$installer->endSetup();