<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

$installer->run("
CREATE TABLE IF NOT EXISTS `customer_temp_pwhash` (
  `email` varchar(255) NOT NULL,
  `pw_hash` varchar(255) NOT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$installer->endSetup();
