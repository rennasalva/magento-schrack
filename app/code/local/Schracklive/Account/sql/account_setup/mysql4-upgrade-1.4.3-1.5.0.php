<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$tabName = $this->getTable('account_customer');

$installer->run("

CREATE TABLE {$tabName} (
  account_id int(10) unsigned NOT NULL,
  customer_id int(10) unsigned NOT NULL,
  UNIQUE KEY `UNQ_ACCOUNT_CUSTOMER` (`account_id`,`customer_id`),
  KEY `ACCOUNT_CUSTOMER_ACCOUNT` (`account_id`),
  KEY `ACCOUNT_CUSTOMER_CUSTOMER` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    ");

$installer->endSetup();
