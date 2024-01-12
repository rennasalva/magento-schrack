<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$tabName = $this->getTable('service_permissions');

$installer->run("

CREATE TABLE service_permissions (
  `service_permissions_id` int(11) unsigned NOT NULL auto_increment,
  `wws_customer_id` varchar(6) DEFAULT NULL,
  `email` varchar(255) NOT NULL DEFAULT '',
  `services_csv` text,
  `other_customer_ids_csv` text,
  PRIMARY KEY (`service_permissions_id`),
  UNIQUE KEY `UNQ_SERVICE_PERMISSION` (`wws_customer_id`,`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    ");

$installer->endSetup();
