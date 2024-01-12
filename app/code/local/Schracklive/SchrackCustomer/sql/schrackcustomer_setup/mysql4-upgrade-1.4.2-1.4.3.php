<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

$config  = Mage::getConfig()->getResourceConnectionConfig("common_db");
$commonDbName = $config->asArray()['dbname'];


$installer->run("
CREATE TABLE IF NOT EXISTS `$commonDbName`.`accept_offer_tracking` (
  `tracking_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `country_id` varchar(2) NOT NULL,
  `customer_id` varchar(6) DEFAULT NULL,
  `contact_number` int(11) DEFAULT NULL,
  `offer_number` varchar(20) DEFAULT NULL,
  `order_number` varchar(9) NOT NULL,
  `case` varchar(64) NOT NULL,
  `has_qty_changes` int(1) DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`tracking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$installer->endSetup();
