<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

$config  = Mage::getConfig()->getResourceConnectionConfig("common_db");
$commonDbName = $config->asArray()['dbname'];

$installer->run("

ALTER TABLE `$commonDbName`.`accept_offer_tracking` ADD `wws_error` VARCHAR(255) default NULL AFTER `case`;
ALTER TABLE `$commonDbName`.`accept_offer_tracking` ADD `net_total` DECIMAL(12,4) default NULL AFTER `has_qty_changes`;

");

$installer->endSetup();
