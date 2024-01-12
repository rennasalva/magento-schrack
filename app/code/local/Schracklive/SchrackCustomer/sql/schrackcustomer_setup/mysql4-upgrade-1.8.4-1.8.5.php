<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

$installer->run("
CREATE TABLE act_as_a_customer_whitelist (
  `id` int(11) unsigned NOT NULL auto_increment,
  `employee_mail_address` varchar(255) NOT NULL,  
  `wws_customer_number` varchar(10) NOT NULL DEFAULT 'all',  
  `active` tinyint(1) NULL DEFAULT NULL,
  PRIMARY KEY (`id`)  
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `act_as_a_customer_whitelist` SET `employee_mail_address` = 'f.gletthofer@schrack.com', active = 1;
INSERT INTO `act_as_a_customer_whitelist` SET `employee_mail_address` = 'j.wohlschlager@schrack.com', active = 1;
INSERT INTO `act_as_a_customer_whitelist` SET `employee_mail_address` = 'd.laslov@schrack.com', active = 1;
");

$installer->endSetup();
