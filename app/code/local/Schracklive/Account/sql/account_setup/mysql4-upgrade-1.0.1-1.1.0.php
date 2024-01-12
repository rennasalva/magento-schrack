<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("

-- DROP TABLE IF EXISTS {$this->getTable('account_address')};
CREATE TABLE {$this->getTable('account_address')} (
  `address_id` int(10) unsigned NOT NULL auto_increment,
  `wws_customer_id` varchar(6) NOT NULL default '',
  `wws_address_number` int(10) unsigned NOT NULL default '0',
  `type` tinyint(3) unsigned NOT NULL default '0',
  `name1` varchar(80) NOT NULL default '',
  `name2` varchar(80) NOT NULL default '',
  `name3` varchar(80) NOT NULL default '',
  `street` varchar(255) NOT NULL default '',
  `postcode` varchar(20) NOT NULL default '',
  `city` varchar(255) NOT NULL default '',
  `country_id` char(2) NOT NULL default '',
  `created_at` datetime NOT NULL default '0000-00-00 00:00:00',
  `updated_at` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`address_id`),
  INDEX  (`wws_customer_id`, `wws_address_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    ");

$installer->endSetup();
