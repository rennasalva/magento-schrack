<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

/*
$installer->run("

-- DROP TABLE IF EXISTS {$this->getTable('account')};
CREATE TABLE {$this->getTable('account')} (
  `account_id` int(10) unsigned NOT NULL auto_increment,
  `wws_customer_id` varchar(6) NOT NULL default '',
  `wws_branch_id` varchar(6) NOT NULL default '',
  `prefix` varchar(255) NOT NULL default '',
  `name1` varchar(80) NOT NULL default '',
  `name2` varchar(80) NOT NULL default '',
  `name3` varchar(80) NOT NULL default '',
  `street` varchar(255) NOT NULL default '',
  `postcode` varchar(20) NOT NULL default '',
  `city` varchar(255) NOT NULL default '',
  `country_id` char(2) NOT NULL default '',
  `advisor_id` int(10) NOT NULL default 0,
  `created_at` datetime NOT NULL default '0000-00-00 00:00:00',
  `updated_at` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`account_id`),
  KEY  (`wws_customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    ");
*/

$installer->endSetup();
